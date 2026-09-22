<?php
/**
 * Cria (ou atualiza) uma empresa fictícia "Teste Básico" — nenhum dado real — assinando
 * DE VERDADE o plano Básico (plano_atual='basico', licenca_ate 1 ano à frente) e
 * tipo_conta='completo', pra testar exatamente as restrições reais desse plano (não um
 * ambiente sem limite nenhum, como o de scripts/seed_empresa_eletrocenter.php):
 *   - 1 usuário (só o admin já ocupa a vaga — tentar cadastrar um 2º usuário pela tela deve
 *     ser bloqueado pelo limite do plano, mesmo comportamento de um cliente Básico de verdade)
 *   - 30 OS por mês
 *   - Sem cadastro automático por foto (scan_equip_habilitado=false)
 *   - Sem Mentor IA (mentor_habilitado=false)
 *   - Sem foto no cadastro geral de Estoque (estoque_imagem_habilitado=false) — só via
 *     Marketplace/vitrine do Diretório, que continuam sem essa restrição
 *   - Estoque e vitrine do Diretório ilimitados em quantidade (igual aos outros planos)
 *   - 10 buscas de placa por IA no Marketplace (scan_placa_mes=10, continua ligado)
 *
 * Idempotente: se "Teste Básico" já existir (busca por nome_fantasia/razao_social LIKE), só
 * atualiza os campos de assinatura/plano e cria o admin se ainda não existir. O backbone de
 * status/CRM/categorias/financeiro é semeado sempre que a empresa (nova ou já existente) ainda
 * não tiver nenhum os_status.
 *
 * Por padrão roda em modo SIMULAÇÃO (não grava nada, só mostra o que faria). Pra gravar:
 *   php scripts/seed_empresa_teste_basico.php --aplicar
 *
 * Opções:
 *   --empresa=ID   força o id da empresa (padrão: busca por nome_fantasia/razao_social LIKE
 *                  '%Teste Básico%' — se bater com mais de uma, o script para e pede pra
 *                  escolher explicitamente)
 *
 * Pra apagar depois (ajuste {EID} pelo id impresso no resumo final — os_status/crm_estagios/
 * categorias_equipamento/fin_contas/fin_categorias/configuracoes/usuarios somem sozinhos via
 * ON DELETE CASCADE em empresa_id):
 *   DELETE FROM empresas WHERE id = {EID};
 */

define('BASE_PATH', dirname(__DIR__));
spl_autoload_register(function (string $class) {
    $path = BASE_PATH . '/' . str_replace(['App\\', '\\'], ['app/', '/'], $class) . '.php';
    if (file_exists($path)) require $path;
});

$aplicar = in_array('--aplicar', $argv, true);

$argOpt = function (string $nome, $default) use ($argv) {
    foreach ($argv as $a) {
        if (str_starts_with($a, "--{$nome}=")) return substr($a, strlen($nome) + 3);
    }
    return $default;
};
$empresaArg = $argOpt('empresa', null);

$db = App\Core\DB::pdo();

echo ($aplicar ? "MODO APLICAR — vai gravar de verdade no banco.\n" : "MODO SIMULAÇÃO — nada será gravado (rode com --aplicar pra gravar de verdade).\n");
echo str_repeat('-', 78) . "\n";

// ---------------------------------------------------------------------------------------
// Resolve empresa (cria se não existir)
// ---------------------------------------------------------------------------------------

if ($empresaArg !== null) {
    $stmt = $db->prepare("SELECT id, nome_fantasia FROM empresas WHERE id = ?");
    $stmt->execute([(int) $empresaArg]);
    $empresa = $stmt->fetch();
    if (!$empresa) { fwrite(STDERR, "Empresa #{$empresaArg} não encontrada.\n"); exit(1); }
} else {
    $stmt = $db->query("SELECT id, nome_fantasia, razao_social FROM empresas WHERE nome_fantasia LIKE '%Teste Básico%' OR razao_social LIKE '%Teste Básico%'");
    $candidatos = $stmt->fetchAll();
    if (count($candidatos) > 1) {
        fwrite(STDERR, "Mais de uma empresa bateu com 'Teste Básico' — escolha uma com --empresa=ID:\n");
        foreach ($candidatos as $c) fwrite(STDERR, "  #{$c['id']} — {$c['nome_fantasia']} ({$c['razao_social']})\n");
        exit(1);
    }
    $empresa = $candidatos[0] ?? null;
}

$licencaAte = date('Y-m-d', strtotime('+1 year')); // assinatura Básico "ativa" de verdade, 1 ano de fôlego

if ($empresa) {
    $empresaId = (int) $empresa['id'];
    echo "Empresa já existe: #{$empresaId} — {$empresa['nome_fantasia']}\n";
    echo "Vai atualizar: ativo=1, tipo_conta=completo, plano_atual=basico, licenca_ate={$licencaAte}.\n";
    $novaEmpresa = false;

    $stmtOs = $db->prepare("SELECT COUNT(*) FROM os_status WHERE empresa_id = ?");
    $stmtOs->execute([$empresaId]);
    $temEsqueleto = (int) $stmtOs->fetchColumn() > 0;
    if (!$temEsqueleto) {
        echo "Empresa existe mas não tem nenhum os_status — vai semear o esqueleto (status/CRM/categorias/financeiro) mesmo assim.\n";
    }
} else {
    $empresaId = null; // definido só depois do INSERT (modo --aplicar) ou fictício em simulação
    echo "Empresa não encontrada — será criada do zero (empresa fictícia, nenhum dado real).\n";
    $novaEmpresa = true;
    $temEsqueleto = false;
}

// ---------------------------------------------------------------------------------------
// Usuário — só o admin, de propósito: o plano Básico permite só 1 usuário, então esse
// login já ocupa a vaga inteira do plano (tentar criar um 2º pela tela deve travar).
// ---------------------------------------------------------------------------------------

$senhaPadrao = 'Teste@2026';
$usuariosDesejados = [
    ['Admin Teste Básico', 'admin@testebasico.teste', 'admin'],
];

// E-mail é único GLOBALMENTE no sistema (não só por empresa), então checa contra a base
// inteira antes de tentar criar.
$usuariosParaCriar = [];
foreach ($usuariosDesejados as [$nome, $email, $perfil]) {
    $stmtC = $db->prepare("SELECT COUNT(*) FROM usuarios WHERE email = ?");
    $stmtC->execute([$email]);
    if ((int) $stmtC->fetchColumn() > 0) {
        echo "  - {$email} já existe em algum lugar do sistema — pulando esse login.\n";
        continue;
    }
    $usuariosParaCriar[] = [$nome, $email, $perfil];
}

echo count($usuariosParaCriar) . " usuário(s) serão criados (senha: {$senhaPadrao}).\n";
echo str_repeat('-', 78) . "\n";

if (!$aplicar) {
    echo "Rode com --aplicar pra gravar de verdade.\n";
    exit(0);
}

// ---------------------------------------------------------------------------------------
// Aplica
// ---------------------------------------------------------------------------------------

if ($novaEmpresa) {
    $stmtE = $db->prepare(
        "INSERT INTO empresas (razao_social, nome_fantasia, email, cidade, uf, tipo_conta, plano, plano_atual, trial_ate, licenca_ate, ativo)
         VALUES ('Teste Plano Básico Ltda (fictícia)', 'Teste Básico', 'contato@testebasico.teste', 'São Paulo', 'SP', 'completo', 'basico', 'basico', ?, ?, 1)"
    );
    $stmtE->execute([$licencaAte, $licencaAte]);
    $empresaId = (int) $db->lastInsertId();
    echo "Empresa criada: #{$empresaId} — Teste Básico\n";
} else {
    $db->prepare(
        "UPDATE empresas SET ativo = 1, tipo_conta = 'completo', plano = 'basico', plano_atual = 'basico',
                trial_ate = ?, licenca_ate = ?
         WHERE id = ?"
    )->execute([$licencaAte, $licencaAte, $empresaId]);
    echo "Empresa #{$empresaId} atualizada — assinatura Básico ativa até {$licencaAte}.\n";
}

if (!$temEsqueleto) {
    // Backbone de status nativo — mesmo conjunto de LandingController::registrar(), pra essa
    // conta de teste começar exatamente como um cadastro Básico de verdade começaria.
    $statusNativos = [
        ['orcamento',        'Orçamento',        '#0d6efd', '#ffffff', 1, 'aberta',       0, 0],
        ['em_analise',       'Em análise',       '#6f42c1', '#ffffff', 2, 'em_andamento', 1, 0],
        ['aguardando_pecas', 'Aguardando peças', '#fd7e14', '#ffffff', 3, 'aguardando',   1, 0],
        ['pronto',           'Pronto',           '#198754', '#ffffff', 4, 'concluida',    1, 0],
        ['laudo_tecnico',    'Laudo Técnico',    '#0891b2', '#ffffff', 5, 'em_andamento', 1, 0],
        ['fechado',          'Fechado',          '#20c997', '#ffffff', 6, 'entregue',     0, 0],
        ['sem_conserto',     'Sem Conserto',     '#dc3545', '#ffffff', 7, 'cancelada',    1, 0],
        ['sem_defeito',      'Não Apresenta Defeito', '#42c266', '#ffffff', 8, 'concluida', 1, 1],
        ['aprovado',         'Aprovado',         '#20c997', '#ffffff', 9, 'aberta',     1, 0],
    ];
    $stmtS = $db->prepare(
        "INSERT INTO os_status (empresa_id, codigo, nome, cor, cor_fonte, ordem, tipo, permite_fechar, sem_valor, bloqueado)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)"
    );
    foreach ($statusNativos as $s) $stmtS->execute([$empresaId, ...$s]);
    $db->prepare("INSERT INTO os_status (empresa_id, nome, cor, cor_fonte, ordem, tipo, permite_fechar, sem_valor, bloqueado)
                  VALUES (?, 'Em Reparo', '#0dcaf0', '#ffffff', 10, 'em_andamento', 1, 0, 0)")
       ->execute([$empresaId]);

    $db->prepare("INSERT IGNORE INTO equip_acessorios (empresa_id, nome) VALUES (?, 'sem acessórios')")
       ->execute([$empresaId]);

    $estagios = [
        ['Primeiro Contato', '#0d6efd', 1, 'aberto'],
        ['Orçamento Enviado', '#ffc107', 2, 'aberto'],
        ['Em Negociação',    '#fd7e14', 3, 'aberto'],
        ['Ganho',            '#198754', 4, 'ganho'],
        ['Perdido',          '#dc3545', 5, 'perdido'],
    ];
    $stmtCRM = $db->prepare("INSERT INTO crm_estagios (empresa_id, nome, cor, ordem, tipo) VALUES (?, ?, ?, ?, ?)");
    foreach ($estagios as $e) $stmtCRM->execute([$empresaId, ...$e]);

    $cats = [
        ['Celular/Smartphone','bi-phone'],['Tablet','bi-tablet'],['Notebook','bi-laptop'],
        ['Desktop/PC','bi-pc-display'],['Televisão','bi-tv'],['Monitor','bi-display'],
        ['Geladeira/Freezer','bi-snow'],['Máquina de Lavar','bi-water'],
        ['Micro-ondas','bi-box2'],['Ar Condicionado','bi-wind'],
        ['Impressora','bi-printer'],['Videogame','bi-joystick'],['Outro','bi-tools'],
    ];
    $stmtCat = $db->prepare("INSERT INTO categorias_equipamento (empresa_id, nome, icone) VALUES (?, ?, ?)");
    foreach ($cats as $c) $stmtCat->execute([$empresaId, $c[0], $c[1]]);

    $db->prepare("INSERT INTO fin_contas (empresa_id, nome, tipo) VALUES (?, 'Caixa', 'caixa')")
       ->execute([$empresaId]);
    $db->prepare("INSERT INTO fin_categorias (empresa_id, tipo, nome, cor) VALUES (?, 'receita', 'Serviços', '#198754')")
       ->execute([$empresaId]);

    $db->prepare("INSERT IGNORE INTO produto_estados (empresa_id, nome) VALUES (?, 'Novo')")->execute([$empresaId]);
    $db->prepare("INSERT IGNORE INTO produto_tipos   (empresa_id, nome) VALUES (?, 'Acessórios')")->execute([$empresaId]);
    $db->prepare("INSERT IGNORE INTO produto_marcas  (empresa_id, nome) VALUES (?, 'Genérica')")->execute([$empresaId]);

    $configs = [
        ['os_prefixo','OS'],['os_digitos','6'],['garantia_padrao_dias','90'],
        ['prazo_retirada_dias','30'],['comissao_tecnico_percentual','20'],
    ];
    $stmtCfg = $db->prepare("INSERT INTO configuracoes (empresa_id, chave, valor) VALUES (?, ?, ?)");
    foreach ($configs as $c) $stmtCfg->execute([$empresaId, $c[0], $c[1]]);

    $db->prepare(
        "INSERT INTO marketplace_creditos (empresa_id, saldo_creditos) VALUES (?, 10)
         ON DUPLICATE KEY UPDATE saldo_creditos = saldo_creditos + 10"
    )->execute([$empresaId]);
    $db->prepare(
        "INSERT INTO marketplace_historico_creditos (empresa_id, tipo, quantidade, justificativa)
         VALUES (?, 'compra', 10, 'Bônus de boas-vindas — 10 créditos gratuitos')"
    )->execute([$empresaId]);

    echo "Backbone (status/CRM/categorias/financeiro) semeado.\n";
}

$stmtU = $db->prepare(
    "INSERT INTO usuarios (empresa_id, nome, email, senha, perfil, ativo, email_verificado)
     VALUES (?, ?, ?, ?, ?, 1, 1)"
);
$senhaHash = password_hash($senhaPadrao, PASSWORD_BCRYPT, ['cost' => 12]);
$criados = [];
foreach ($usuariosParaCriar as [$nome, $email, $perfil]) {
    $stmtU->execute([$empresaId, $nome, $email, $senhaHash, $perfil]);
    $criados[] = [$nome, $email, $perfil];
}

echo str_repeat('-', 78) . "\n";
echo "Empresa: #{$empresaId} — Teste Básico (fictícia, tipo_conta=completo, plano_atual=basico, licenca_ate={$licencaAte})\n";
echo count($criados) . " login(s) criado(s) (senha: {$senhaPadrao}):\n";
foreach ($criados as [$nome, $email, $perfil]) {
    echo "  - {$email} — {$nome} ({$perfil})\n";
}
echo "Pra desfazer: DELETE FROM empresas WHERE id = {$empresaId};\n";
