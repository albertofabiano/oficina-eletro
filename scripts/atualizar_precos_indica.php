<?php
// Atualiza preço/disponibilidade de todo produto do Fixa Indica que tem link_ml preenchido,
// consultando a API do Mercado Livre (App\Services\Indica\MercadoLivreService::buscarItem()).
// Produto sem link_ml (cadastrado só com dados manuais) não é tocado.
//
// Rodar via cron real, 1x/dia (preço de produto não muda minuto a minuto):
//   0 6 * * * php /var/www/fixaos/scripts/atualizar_precos_indica.php >> /var/www/fixaos/storage/logs/precos_indica_cron.log 2>&1
//
// Sem cron configurado, os preços só ficam desatualizados — não há fallback throttled aqui
// (diferente de lembretes/notificações), porque não faz sentido chamar a API do Mercado Livre
// a cada requisição de visitante só pra manter o preço em dia.
//
// Uso: php scripts/atualizar_precos_indica.php

define('BASE_PATH', dirname(__DIR__));
spl_autoload_register(function (string $class) {
    $path = BASE_PATH . '/' . str_replace(['App\\', '\\'], ['app/', '/'], $class) . '.php';
    if (file_exists($path)) require $path;
});
require BASE_PATH . '/app/Helpers/functions.php';

$appConfig = require BASE_PATH . '/config/app.php';
date_default_timezone_set($appConfig['timezone']);

if (!App\Services\Indica\MercadoLivreService::configurado()) {
    fwrite(STDERR, "config/mercadolivre.php não preenchido — nada a fazer.\n");
    exit(0);
}
if (!App\Services\Indica\MercadoLivreService::conectado()) {
    fwrite(STDERR, "Mercado Livre não conectado (ver /admin/config) — nada a fazer.\n");
    exit(0);
}

$db = App\Core\DB::pdo();
$produtos = $db->query(
    "SELECT id, nome, link_ml, preco FROM indica_produtos
     WHERE ativo = 1 AND link_ml IS NOT NULL AND link_ml != ''"
)->fetchAll();

$total = count($produtos);
$atualizados = 0;
$falhas = 0;

echo "Atualizando preço de {$total} produto(s)...\n";

foreach ($produtos as $p) {
    $item = App\Services\Indica\MercadoLivreService::buscarItem($p['link_ml']);
    if (!$item) {
        $falhas++;
        echo "  [falha] #{$p['id']} {$p['nome']}\n";
        // Meio segundo de intervalo entre chamadas — evita martelar a API em lote grande,
        // mesmo numa falha (rate limit do Mercado Livre não é documentado publicamente, mas
        // um pequeno intervalo é defesa barata contra bloqueio temporário).
        usleep(500000);
        continue;
    }

    $upd = $db->prepare(
        "UPDATE indica_produtos SET preco = ?, disponivel = ?, preco_atualizado_em = NOW()
         WHERE id = ?"
    );
    $upd->execute([$item['preco'], $item['disponivel'] ? 1 : 0, $p['id']]);
    $atualizados++;

    $precoAntigo = $p['preco'] !== null ? number_format((float) $p['preco'], 2, ',', '.') : '—';
    $precoNovo   = $item['preco'] !== null ? number_format((float) $item['preco'], 2, ',', '.') : '—';
    echo "  [ok] #{$p['id']} {$p['nome']} — R$ {$precoAntigo} -> R$ {$precoNovo}" . ($item['disponivel'] ? '' : ' (indisponível)') . "\n";

    usleep(500000);
}

echo "Concluído: {$atualizados} atualizado(s), {$falhas} falha(s) de {$total}.\n";
