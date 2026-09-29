<?php
/**
 * Remove de vez a empresa/usuário de demonstração do FixaOS (`demo@fixaos.com.br`,
 * "Assistencia Modelo (Demo)") — a conta pública, sem senha visível a ninguém, acessada
 * direto pelo botão "Ver demonstração" que existia na landing/e-mails/perfil público.
 * Removida a pedido do usuário: totalmente aberta (qualquer visitante entrava sem
 * cadastro) e gerava carga desnecessária no servidor (o reset horário via cron,
 * tools/demo_seed.php, já apagado — ver CLAUDE.md "Fim da conta de demonstração").
 *
 * Só apaga `empresas`/`usuarios` — o `ON DELETE CASCADE` em `empresa_id`, presente em
 * praticamente toda tabela do sistema (mesmo padrão já usado por outros scripts deste
 * projeto pra remover uma empresa inteira, ex. seed_empresa_eletrocenter.php), cuida do
 * resto sozinho. Se alguma tabela mais antiga não tiver cascade configurado, o DELETE
 * falha com um erro de FK claro (nomeando a tabela) em vez de deixar dado órfão.
 *
 * Uso:
 *   php scripts/remover_empresa_demo.php              # simulação, só mostra o que seria apagado
 *   php scripts/remover_empresa_demo.php --aplicar     # apaga de verdade
 */

define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/app/Helpers/functions.php';
spl_autoload_register(function (string $class) {
    $path = BASE_PATH . '/' . str_replace(['App\\', '\\'], ['app/', '/'], $class) . '.php';
    if (file_exists($path)) require $path;
});

$aplicar = in_array('--aplicar', $argv, true);
$db = App\Core\DB::pdo();

echo ($aplicar ? "MODO APLICAR — vai apagar de verdade.\n" : "MODO SIMULAÇÃO — nada será apagado (rode com --aplicar pra apagar de verdade).\n");
echo str_repeat('-', 78) . "\n";

$stmt = $db->prepare("SELECT id, empresa_id FROM usuarios WHERE email = ? LIMIT 1");
$stmt->execute(['demo@fixaos.com.br']);
$usuario = $stmt->fetch();

if (!$usuario) {
    echo "Nenhum usuário com e-mail demo@fixaos.com.br encontrado — nada a fazer (já foi removida?).\n";
    exit(0);
}
$eid = (int) $usuario['empresa_id'];

$empresa = $db->prepare("SELECT nome_fantasia, razao_social FROM empresas WHERE id = ?");
$empresa->execute([$eid]);
$nomeEmpresa = $empresa->fetch();

echo "Empresa demo encontrada: #{$eid} \"" . ($nomeEmpresa['nome_fantasia'] ?? '?') . "\"\n\n";

$contagens = [
    'ordens_servico'      => 'ordens de serviço',
    'clientes'            => 'clientes',
    'fin_lancamentos'     => 'lançamentos financeiros',
    'marketplace_anuncios'=> 'anúncios no marketplace',
    'empresa_fotos'       => 'fotos do perfil público',
    'diretorio_avaliacoes'=> 'avaliações recebidas',
];
foreach ($contagens as $tabela => $rotulo) {
    try {
        $c = $db->prepare("SELECT COUNT(*) FROM `{$tabela}` WHERE empresa_id = ?");
        $c->execute([$eid]);
        echo "  " . str_pad((string) $c->fetchColumn(), 5, ' ', STR_PAD_LEFT) . "  {$rotulo}\n";
    } catch (\Throwable) {
        // tabela pode não existir nesta instalação — não trava o levantamento por isso
    }
}

echo str_repeat('-', 78) . "\n";

if (!$aplicar) {
    echo "\nRode com --aplicar pra apagar a empresa #{$eid} e todo o histórico acima.\n";
    exit(0);
}

try {
    $db->prepare("DELETE FROM usuarios WHERE empresa_id = ?")->execute([$eid]);
    $db->prepare("DELETE FROM empresas WHERE id = ?")->execute([$eid]);
    echo "Empresa #{$eid} e todo o histórico vinculado (cascade) foram apagados.\n";
} catch (\Throwable $e) {
    echo "FALHOU: " . $e->getMessage() . "\n";
    echo "Alguma tabela referenciando empresa_id pode não ter ON DELETE CASCADE configurado —\n";
    echo "veja o nome da tabela na mensagem de erro acima e limpe manualmente antes de tentar de novo.\n";
    exit(1);
}
