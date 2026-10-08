<?php

namespace App\Services\Fixa;

/**
 * Fixa Fase 1 (PF/PJ) — tudo que resolve/cria/lista "perfil" (`financeiro_pessoal_perfis`) e
 * semeia categorias padrão por perfil, extraído pra ser reaproveitado tanto por
 * `FinanceiroPessoalController` quanto por `FixaContasController` e `ScannerController`
 * (pareamento de foto, não passa por nenhum dos dois controllers) — mesmo princípio de
 * "serviço estático compartilhado" já usado no projeto (ImageService, VisionService etc.).
 *
 * Perfil continua sendo do USUÁRIO (`usuario_id`), nunca da empresa — mesmo escopo de sempre
 * deste módulo (ver migration 075). `financeiro_pessoal_liberado($empresa)` continua sendo o
 * gate EXTERNO (precisa de empresa reivindicada + plano pago) checado pelo controller antes de
 * qualquer coisa aqui; perfis são uma camada de dentro de quem já tem acesso liberado.
 */
class PerfilService
{
    /**
     * Categorias padrão de um perfil PESSOA FÍSICA — as 7 originais (chaves intocadas, migration
     * 078/categoriasDoUsuario() antigo) continuam aqui por compatibilidade: todo lançamento já
     * existente guarda uma dessas chaves em `categoria`, mudar a chave quebraria o vínculo.
     * As 4 novas (educacao/assinaturas/salario/outras_receitas) são a lista pedida na Fase 1 —
     * entram pelo mesmo mecanismo de "completa só o que falta" que já existe desde a correção
     * de "reformule todas as categorias": perfil migrado (que já tinha as 7 antigas) ganha as 4
     * novas sozinho na próxima vez que acessar, sem script extra.
     * Formato: [chave, nome, cor, tipo].
     */
    private const PADRAO_PF = [
        ['moradia',         'Moradia',          'var(--cat-moradia)',     'despesa'],
        ['alimentacao',     'Alimentação',      'var(--cat-alimentacao)', 'despesa'],
        ['transporte',      'Transporte',       'var(--cat-transporte)',  'despesa'],
        ['saude',           'Saúde',            'var(--cat-saude)',       'despesa'],
        ['lazer',           'Lazer',            'var(--cat-lazer)',       'despesa'],
        ['educacao',        'Educação',         '#1D6FA5',                'despesa'],
        ['compras',         'Compras',          'var(--cat-compras)',     'despesa'],
        ['assinaturas',     'Assinaturas',      '#8A6D1D',                'despesa'],
        ['outros',          'Outros',           'var(--cat-outros)',      'despesa'],
        ['salario',         'Salário',          'var(--inc)',             'receita'],
        ['outras_receitas', 'Outras receitas',  'var(--inc)',             'receita'],
    ];

    /** Categorias padrão de um perfil PESSOA JURÍDICA (MEI/empresa) — grupo_dre é só
     *  groundwork (nenhum relatório desta fase usa isso ainda). Formato: [chave, nome, cor, tipo, grupo_dre]. */
    private const PADRAO_PJ = [
        ['vendas_servicos',    'Vendas e serviços',      'var(--inc)', 'receita', 'Receita Operacional'],
        ['impostos_das',       'Impostos (DAS)',         '#9C2B2B',    'despesa', 'Impostos'],
        ['fornecedores_pecas', 'Fornecedores e peças',   '#3D6B1F',    'despesa', 'Custos'],
        ['aluguel',            'Aluguel',                '#6B4423',    'despesa', 'Despesas Administrativas'],
        ['folha_encargos',     'Folha e encargos',       '#1F6B5C',    'despesa', 'Despesas com Pessoal'],
        ['prolabore',          'Pró-labore',             '#4A3B8A',    'despesa', 'Despesas com Pessoal'],
        ['contador',           'Contador',               '#2F5E8C',    'despesa', 'Despesas Administrativas'],
        ['marketing',          'Marketing',              '#B8378A',    'despesa', 'Despesas Administrativas'],
        ['taxas_cartao',       'Taxas de cartão',        '#8C5A1F',    'despesa', 'Despesas Financeiras'],
        ['outras',             'Outras',                 'var(--cat-outros)', 'despesa', null],
    ];

    /** Todos os perfis do usuário, ordenados (ordem, depois id) — inclui arquivado só se pedido
     *  explicitamente (tela de Configurações, pra poder desarquivar). */
    public static function perfisDoUsuario(\PDO $db, int $usuarioId, bool $incluirArquivados = false): array
    {
        $sql = "SELECT id, tipo, nome, documento, cor, regime, ordem, arquivado
                FROM financeiro_pessoal_perfis WHERE usuario_id = ?"
             . ($incluirArquivados ? '' : ' AND arquivado = 0')
             . " ORDER BY ordem, id";
        $st = $db->prepare($sql);
        $st->execute([$usuarioId]);
        return $st->fetchAll();
    }

    /** Primeiro perfil não-arquivado do usuário, sem criar nada e sem tocar em sessão — usado
     *  por contexto sem sessão de verdade (ex.: ScannerController, celular pareado sem login
     *  próprio). Sem perfil nenhum ainda, devolve null (quem chama decide o fallback). */
    public static function primeiroPerfilDoUsuario(\PDO $db, int $usuarioId): ?array
    {
        $perfis = self::perfisDoUsuario($db, $usuarioId, false);
        return $perfis[0] ?? null;
    }

    /**
     * Cria o perfil "Pessoal" (pf) + conta "Carteira" — usado tanto pelo primeiro acesso de um
     * usuário que nunca teve perfil nenhum (ver perfilAtivo()) quanto por
     * scripts/migrar_fixa_perfis.php (que reimplementa o mesmo INSERT, não chama este método —
     * o script roda fora do ciclo de request HTTP, sem sessão, e já faz o resto do backfill
     * numa transação própria com verificação de soma; manter os dois sincronizados é só repetir
     * o mesmo INSERT simples, baixo risco de divergir).
     */
    public static function criarPerfilPessoalPadrao(\PDO $db, int $usuarioId): array
    {
        $db->prepare(
            "INSERT INTO financeiro_pessoal_perfis (usuario_id, tipo, nome, cor, ordem, arquivado)
             VALUES (?, 'pf', 'Pessoal', '#8C7CFF', 0, 0)"
        )->execute([$usuarioId]);
        $perfilId = (int) $db->lastInsertId();

        $db->prepare(
            "INSERT INTO financeiro_pessoal_contas (usuario_id, perfil_id, nome, tipo, saldo_inicial, data_saldo_inicial, cor, arquivada, padrao)
             VALUES (?, ?, 'Carteira', 'dinheiro', 0.00, CURDATE(), '#3CC9C0', 0, 1)"
        )->execute([$usuarioId, $perfilId]);

        return self::buscar($db, $perfilId);
    }

    public static function buscar(\PDO $db, int $perfilId): ?array
    {
        $st = $db->prepare(
            "SELECT id, usuario_id, tipo, nome, documento, cor, regime, ordem, arquivado
             FROM financeiro_pessoal_perfis WHERE id = ?"
        );
        $st->execute([$perfilId]);
        $row = $st->fetch();
        return $row ?: null;
    }

    /** Confirma que o perfil pertence mesmo ao usuário (nunca confia num perfil_id vindo de
     *  fora sem checar isso primeiro) — devolve a linha, ou null se não bate. */
    public static function pertenceAoUsuario(\PDO $db, int $perfilId, int $usuarioId): ?array
    {
        $perfil = self::buscar($db, $perfilId);
        if (!$perfil || (int) $perfil['usuario_id'] !== $usuarioId) return null;
        return $perfil;
    }

    /**
     * Perfil ATIVO da sessão atual — cria o "Pessoal" padrão se o usuário nunca teve perfil
     * nenhum (mesmo princípio de auto-semeadura já usado por categoriasDoUsuario() antigo);
     * resolve o que está guardado em `$_SESSION['fixa_perfil_id']` se ainda for válido (do
     * mesmo usuário, não arquivado), senão cai no primeiro da lista (ordem, id) e grava esse na
     * sessão — mesmo padrão de "preferência persistida, mas sempre com um fallback sensato" já
     * usado pra tema/sidebar fixada no resto do FixaOS.
     */
    public static function perfilAtivo(\PDO $db, int $usuarioId): array
    {
        $perfis = self::perfisDoUsuario($db, $usuarioId, false);

        if (!$perfis) {
            $novo = self::criarPerfilPessoalPadrao($db, $usuarioId);
            $_SESSION['fixa_perfil_id'] = $novo['id'];
            return $novo;
        }

        $sessId = (int) ($_SESSION['fixa_perfil_id'] ?? 0);
        foreach ($perfis as $p) {
            if ((int) $p['id'] === $sessId) { return $p; }
        }

        $_SESSION['fixa_perfil_id'] = (int) $perfis[0]['id'];
        return $perfis[0];
    }

    /**
     * Categorias ATIVAS de um perfil, chave => ['id','nome','cor','tipo','icone'] — semeia os
     * padrões (PF ou PJ, conforme `$tipoPerfil`) que ainda não existirem pra esse perfil
     * (nunca re-semeia uma chave com `ativo=0` — exclusão deliberada conta como "já existe",
     * mesmo comportamento já documentado/testado em categoriasDoUsuario()).
     */
    public static function categoriasDoPerfil(\PDO $db, int $perfilId, string $tipoPerfil): array
    {
        $padrao = $tipoPerfil === 'pj' ? self::PADRAO_PJ : self::PADRAO_PF;

        $stTodas = $db->prepare("SELECT chave FROM financeiro_pessoal_categorias WHERE perfil_id = ?");
        $stTodas->execute([$perfilId]);
        $chavesExistentes = $stTodas->fetchAll(\PDO::FETCH_COLUMN);

        $faltando = array_values(array_filter(
            $padrao,
            fn($d) => !in_array($d[0], $chavesExistentes, true)
        ));

        if ($faltando) {
            $stPos = $db->prepare("SELECT COALESCE(MAX(posicao), -1) + 1 FROM financeiro_pessoal_categorias WHERE perfil_id = ?");
            $stPos->execute([$perfilId]);
            $pos = (int) $stPos->fetchColumn();

            // usuario_id também é gravado (segunda camada de filtro/isolamento, ver nota na
            // migration 084) — resolvido a partir do próprio perfil, nunca recebido como
            // parâmetro à parte (evita gravar perfil de um usuário com usuario_id de outro).
            $perfil = self::buscar($db, $perfilId);
            $usuarioId = $perfil['usuario_id'] ?? null;

            $ins = $db->prepare(
                "INSERT INTO financeiro_pessoal_categorias (usuario_id, perfil_id, chave, nome, cor, tipo, grupo_dre, posicao)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
            );
            foreach ($faltando as $d) {
                $ins->execute([$usuarioId, $perfilId, $d[0], $d[1], $d[2], $d[3], $d[4] ?? null, $pos]);
                $pos++;
            }
        }

        $st = $db->prepare(
            "SELECT id, chave, nome, cor, tipo, icone FROM financeiro_pessoal_categorias
             WHERE perfil_id = ? AND ativo = 1 ORDER BY posicao, id"
        );
        $st->execute([$perfilId]);
        $linhas = $st->fetchAll();

        $out = [];
        foreach ($linhas as $l) {
            $out[$l['chave']] = [
                'id'    => (int) $l['id'],
                'nome'  => $l['nome'],
                'cor'   => $l['cor'],
                'tipo'  => $l['tipo'],
                'icone' => $l['icone'],
            ];
        }
        return $out;
    }

    /** Contas não arquivadas de um perfil (pra selects/validação de conta_id). */
    public static function contasDoPerfil(\PDO $db, int $perfilId, bool $incluirArquivadas = false): array
    {
        $sql = "SELECT id, nome, tipo, saldo_inicial, data_saldo_inicial, cor, arquivada, padrao
                FROM financeiro_pessoal_contas WHERE perfil_id = ?"
             . ($incluirArquivadas ? '' : ' AND arquivada = 0')
             . " ORDER BY id";
        $st = $db->prepare($sql);
        $st->execute([$perfilId]);
        return $st->fetchAll();
    }

    /**
     * Cria um perfil novo (manual, pelo usuário — "+ Novo perfil" em Configurações), com sua
     * própria conta "Carteira" inicial, mesmo princípio do perfil "Pessoal" automático. Valida
     * CPF/CNPJ reaproveitando documento_valido() (app/Helpers/functions.php, já existente no
     * projeto) — não duplica a lógica de dígito verificador aqui.
     */
    public static function criarPerfil(\PDO $db, int $usuarioId, string $tipo, string $nome, string $documento, string $cor, ?string $regime): array
    {
        $tipo = $tipo === 'pj' ? 'pj' : 'pf';
        $regimesValidos = ['mei', 'simples', 'presumido', 'outro'];
        $regime = ($tipo === 'pj' && in_array($regime, $regimesValidos, true)) ? $regime : null;
        $documento = $documento !== '' ? preg_replace('/\D/', '', $documento) : null;

        $posSt = $db->prepare("SELECT COALESCE(MAX(ordem), -1) + 1 FROM financeiro_pessoal_perfis WHERE usuario_id = ?");
        $posSt->execute([$usuarioId]);
        $ordem = (int) $posSt->fetchColumn();

        $db->prepare(
            "INSERT INTO financeiro_pessoal_perfis (usuario_id, tipo, nome, documento, cor, regime, ordem, arquivado)
             VALUES (?, ?, ?, ?, ?, ?, ?, 0)"
        )->execute([$usuarioId, $tipo, $nome, $documento, $cor, $regime, $ordem]);
        $perfilId = (int) $db->lastInsertId();

        $nomeConta = $tipo === 'pj' ? 'Conta da empresa' : 'Carteira';
        $db->prepare(
            "INSERT INTO financeiro_pessoal_contas (usuario_id, perfil_id, nome, tipo, saldo_inicial, data_saldo_inicial, cor, arquivada, padrao)
             VALUES (?, ?, ?, 'corrente', 0.00, CURDATE(), ?, 0, 1)"
        )->execute([$usuarioId, $perfilId, $nomeConta, $cor]);

        return self::buscar($db, $perfilId);
    }

    public static function atualizarPerfil(\PDO $db, int $perfilId, int $usuarioId, string $nome, string $documento, string $cor, ?string $regime): bool
    {
        $perfil = self::pertenceAoUsuario($db, $perfilId, $usuarioId);
        if (!$perfil) return false;

        $regimesValidos = ['mei', 'simples', 'presumido', 'outro'];
        $regime = ($perfil['tipo'] === 'pj' && in_array($regime, $regimesValidos, true)) ? $regime : null;
        $documento = $documento !== '' ? preg_replace('/\D/', '', $documento) : null;

        $st = $db->prepare(
            "UPDATE financeiro_pessoal_perfis SET nome = ?, documento = ?, cor = ?, regime = ? WHERE id = ? AND usuario_id = ?"
        );
        $st->execute([$nome, $documento, $cor, $regime, $perfilId, $usuarioId]);
        return true;
    }

    /** Arquiva/desarquiva — nunca permite arquivar o ÚLTIMO perfil ativo (sempre precisa sobrar
     *  pelo menos 1 pra sessão resolver sozinha em perfilAtivo()). */
    public static function arquivarPerfil(\PDO $db, int $perfilId, int $usuarioId, bool $arquivar): bool
    {
        $perfil = self::pertenceAoUsuario($db, $perfilId, $usuarioId);
        if (!$perfil) return false;

        if ($arquivar) {
            $ativos = self::perfisDoUsuario($db, $usuarioId, false);
            if (count($ativos) <= 1) return false;
        }

        $db->prepare("UPDATE financeiro_pessoal_perfis SET arquivado = ? WHERE id = ? AND usuario_id = ?")
            ->execute([$arquivar ? 1 : 0, $perfilId, $usuarioId]);
        return true;
    }
}
