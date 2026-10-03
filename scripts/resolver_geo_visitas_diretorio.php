<?php
// Resolve a fila de IPs de visita ao perfil do Diretório (diretorio_visitas_ip_pendente)
// contra uma API de geolocalização por IP, soma o resultado em diretorio_visitas_regiao
// (empresa_id + cidade + uf → total) e some com a fila — nunca mantém o IP cru além do
// necessário pra resolver (minimização de dado).
//
// Desenho pensado especificamente pra não ter o risco já discutido com o usuário (ver
// CLAUDE.md): geolocalizar DENTRO da requisição que serve a página atrasaria toda visita —
// inclusive o Googlebot, que não tem sessão pra ser deduplicado e sentiria isso em TODO
// rastreamento, podendo reduzir o crawl budget. Aqui a geolocalização roda só neste script,
// fora do ciclo de requisição — DiretorioController::empresa() só grava o IP na fila (um
// INSERT rápido) quando a visita já passou pelo filtro de robô/dedup de sessão.
//
// Usa ip-api.com (sem chave, gratuito) via o endpoint em lote (/batch, até 100 IPs por
// chamada) — mesmo padrão de chamada externa simples (file_get_contents + stream_context)
// já usado em DiretorioController::geocode() (ViaCEP/Nominatim), sem lib nova. Free tier tem
// limite de requisições por minuto; rodar via cron a cada poucos minutos (não a cada poucos
// segundos) é o suficiente pra nunca chegar perto do limite, já que cada chamada resolve até
// 100 IPs de uma vez.
//
// Rodar via cron real, ex. a cada 10 minutos:
//   */10 * * * * php /var/www/fixaos/scripts/resolver_geo_visitas_diretorio.php >> /var/www/fixaos/storage/logs/visitas_geo_cron.log 2>&1
//
// Uso: php scripts/resolver_geo_visitas_diretorio.php [--lote=100]

define('BASE_PATH', dirname(__DIR__));
spl_autoload_register(function (string $class) {
    $path = BASE_PATH . '/' . str_replace(['App\\', '\\'], ['app/', '/'], $class) . '.php';
    if (file_exists($path)) require $path;
});
require BASE_PATH . '/app/Helpers/functions.php';

use App\Core\DB;

$appConfig = require BASE_PATH . '/config/app.php';
date_default_timezone_set($appConfig['timezone']);

$lote = 100; // teto por chamada do endpoint /batch da ip-api.com
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--lote=')) $lote = max(1, min(100, (int) substr($arg, 7)));
}

$db = DB::pdo();

$pendentes = $db->prepare("SELECT id, empresa_id, ip FROM diretorio_visitas_ip_pendente ORDER BY id LIMIT ?");
$pendentes->bindValue(1, $lote, PDO::PARAM_INT);
$pendentes->execute();
$fila = $pendentes->fetchAll();

if (!$fila) {
    echo "[" . date('Y-m-d H:i:s') . "] fila vazia, nada a resolver\n";
    exit;
}

$ipsUnicos = array_values(array_unique(array_column($fila, 'ip')));

// Corpo do /batch: um objeto por IP, só os campos que de fato usamos (menos dado trafegado).
$corpo = array_map(fn ($ip) => ['query' => $ip, 'fields' => 'status,country,regionName,region,city,query'], $ipsUnicos);

$ctx = stream_context_create([
    'http' => [
        'method'  => 'POST',
        'header'  => "Content-Type: application/json\r\nUser-Agent: FixaOS/1.0\r\n",
        'content' => json_encode($corpo),
        'timeout' => 8,
    ],
]);
$resposta = @file_get_contents('http://ip-api.com/batch', false, $ctx);
$resultado = $resposta ? json_decode($resposta, true) : null;

if (!is_array($resultado)) {
    // Falha de rede/API — não apaga a fila, tenta de novo na próxima rodada. Não é erro fatal
    // do script (mesmo princípio de "não travar a ação principal por causa de efeito
    // colateral" já usado noutros pontos do projeto): a página que gerou a visita já respondeu
    // há muito tempo, só a geolocalização desse lote específico fica pra depois.
    echo "[" . date('Y-m-d H:i:s') . "] falha ao consultar ip-api.com, tentando de novo na próxima rodada\n";
    exit;
}

// IP → {cidade, uf} ou null (geolocalização falhou/IP privado/país fora do Brasil — produto é
// só nacional, não faz sentido um registro de "cidade" pra visita de fora do país).
$regiaoPorIp = [];
foreach ($resultado as $r) {
    $ip = $r['query'] ?? null;
    if ($ip === null) continue;
    if (($r['status'] ?? '') !== 'success' || ($r['country'] ?? '') !== 'Brazil') {
        $regiaoPorIp[$ip] = null;
        continue;
    }
    $cidade = trim((string) ($r['city'] ?? ''));
    $uf     = trim((string) ($r['region'] ?? '')); // "region" da ip-api já vem como sigla (SP, RJ...)
    $regiaoPorIp[$ip] = ($cidade !== '' && $uf !== '') ? ['cidade' => $cidade, 'uf' => strtoupper($uf)] : null;
}

$upsert = $db->prepare(
    "INSERT INTO diretorio_visitas_regiao (empresa_id, cidade, uf, total) VALUES (?, ?, ?, 1)
     ON DUPLICATE KEY UPDATE total = total + 1"
);

$resolvidos = 0;
$empresasTocadas = [];
foreach ($fila as $item) {
    $regiao = $regiaoPorIp[$item['ip']] ?? null;
    if ($regiao !== null) {
        $upsert->execute([$item['empresa_id'], $regiao['cidade'], $regiao['uf']]);
        $resolvidos++;
        $empresasTocadas[$item['empresa_id']] = true;
    }
}

// Sempre apaga da fila, resolvido ou não — um IP que não deu pra geolocalizar (privado, fora
// do Brasil, erro pontual da API) não deve ficar acumulando pra sempre; o dado já não serviria
// mesmo tentando de novo depois.
$ids = array_column($fila, 'id');
$place = implode(',', array_fill(0, count($ids), '?'));
$db->prepare("DELETE FROM diretorio_visitas_ip_pendente WHERE id IN ($place)")->execute($ids);

printf(
    "[%s] processados=%d resolvidos=%d empresas=%d\n",
    date('Y-m-d H:i:s'), count($fila), $resolvidos, count($empresasTocadas)
);
