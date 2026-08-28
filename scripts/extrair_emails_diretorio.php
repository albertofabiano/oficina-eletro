<?php
/**
 * Extrai pra `diretorio_leads_email` os e-mails das empresas do Diretório (`empresas`,
 * ativo=1 AND listagem_publica=1, com email preenchido) — base própria pra captar clientes
 * (convidar a reivindicar o perfil grátis / conhecer o FixaOS completo), separada de
 * `leads_prospeccao` (base bruta de CNPJ, sem ficha nenhuma criada ainda) porque a mensagem
 * certa pra quem já tem perfil no diretório é diferente ("reivindique", não "cadastre-se").
 *
 * Valida cada e-mail (trim + lowercase + filter_var FILTER_VALIDATE_EMAIL) antes de gravar —
 * a base de CNPJ importada tem lixo real (ex.: "avelinofisco@gmail.com." com ponto sobrando
 * no final, achado numa amostra de 200 linhas) que faria bounce se fosse usado num disparo.
 *
 * Idempotente: UNIQUE KEY em empresa_id, roda de novo sem duplicar (ON DUPLICATE KEY UPDATE
 * só atualiza os dados cadastrais, nunca mexe nas colunas de controle de envio/abertura —
 * assim não reseta o histórico de quem já recebeu convite se a extração rodar de novo depois).
 *
 * Por padrão roda em modo SIMULAÇÃO (não grava nada, só conta e mostra amostra). Pra gravar:
 *   php scripts/extrair_emails_diretorio.php --aplicar
 */

define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/app/Helpers/functions.php';
spl_autoload_register(function (string $class) {
    $path = BASE_PATH . '/' . str_replace(['App\\', '\\'], ['app/', '/'], $class) . '.php';
    if (file_exists($path)) require $path;
});

$aplicar = in_array('--aplicar', $argv, true);
$db = App\Core\DB::pdo();

echo ($aplicar ? "MODO APLICAR — vai gravar de verdade no banco.\n" : "MODO SIMULAÇÃO — nada será gravado (rode com --aplicar pra gravar de verdade).\n");
echo str_repeat('-', 78) . "\n";

$stmt = $db->query(
    "SELECT id, nome_fantasia, email, telefone, cidade, uf, cnpj, reivindicada
     FROM empresas
     WHERE ativo = 1 AND listagem_publica = 1
       AND email IS NOT NULL AND email <> ''"
);

$validos = [];
$invalidos = [];
while ($e = $stmt->fetch()) {
    $email = strtolower(trim((string) $e['email']));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $invalidos[] = ['id' => $e['id'], 'nome' => $e['nome_fantasia'], 'email' => $e['email']];
        continue;
    }
    $e['email'] = $email;
    $validos[] = $e;
}

echo "Empresas com e-mail preenchido: " . (count($validos) + count($invalidos)) . "\n";
echo "E-mails válidos: " . count($validos) . "\n";
echo "E-mails inválidos (descartados, formato incorreto): " . count($invalidos) . "\n";
if ($invalidos) {
    echo "\nAmostra dos inválidos:\n";
    foreach (array_slice($invalidos, 0, 10) as $i) {
        echo "  - id {$i['id']} ({$i['nome']}): \"{$i['email']}\"\n";
    }
}
echo str_repeat('-', 78) . "\n";

if (!$aplicar) {
    echo "Amostra dos primeiros 10 válidos:\n";
    foreach (array_slice($validos, 0, 10) as $v) {
        echo "  - {$v['nome_fantasia']} — {$v['email']} — {$v['cidade']}/{$v['uf']}\n";
    }
    echo "\nRode com --aplicar pra gravar de verdade.\n";
    exit(0);
}

$stmtIns = $db->prepare(
    "INSERT INTO diretorio_leads_email
       (empresa_id, nome_fantasia, email, telefone, cidade, uf, cnpj, reivindicada)
     VALUES (?,?,?,?,?,?,?,?)
     ON DUPLICATE KEY UPDATE
       nome_fantasia = VALUES(nome_fantasia),
       email = VALUES(email),
       telefone = VALUES(telefone),
       cidade = VALUES(cidade),
       uf = VALUES(uf),
       cnpj = VALUES(cnpj),
       reivindicada = VALUES(reivindicada)"
);

$gravadas = 0;
foreach ($validos as $v) {
    $stmtIns->execute([
        $v['id'], $v['nome_fantasia'], $v['email'], $v['telefone'] ?: null,
        $v['cidade'] ?: null, $v['uf'] ?: null, $v['cnpj'] ?: null, (int) $v['reivindicada'],
    ]);
    $gravadas++;
}

echo "Gravadas/atualizadas em diretorio_leads_email: {$gravadas}\n";
