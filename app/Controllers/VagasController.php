<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\DB;

/**
 * Mural de vagas de emprego exclusivo do setor de assistência técnica — pedido do usuário como
 * mais um recurso de assinante (ver CLAUDE.md "Vagas de emprego"). Só empresa com plano pago
 * ativo publica (mesmo gate `perfil_diretorio_completo()` já usado no Diretório); candidato
 * nunca cadastra nada no sistema — o contato é sempre direto pro WhatsApp da empresa.
 */
class VagasController extends Controller
{
    public const REGIMES = [
        'clt'        => 'CLT',
        'pj'         => 'PJ',
        'freelancer' => 'Freelancer',
        'estagio'    => 'Estágio',
    ];
    public const JORNADAS = [
        'integral'     => 'Integral',
        'meio_periodo' => 'Meio período',
        'flexivel'     => 'Flexível',
    ];
    public const MODALIDADES = [
        'presencial' => 'Presencial',
        'remoto'     => 'Remoto',
        'hibrido'    => 'Híbrido',
    ];
    public const NIVEIS = [
        'estagiario' => 'Estagiário',
        'junior'     => 'Júnior',
        'pleno'      => 'Pleno',
        'senior'     => 'Sênior',
    ];

    // ── Painel interno (empresa dona da vaga) ──────────────────────────────

    public function painel(): void
    {
        $eid = $this->empresaId();
        $db  = DB::pdo();

        $stmtE = $db->prepare("SELECT * FROM empresas WHERE id = ?");
        $stmtE->execute([$eid]);
        $empresa = $stmtE->fetch();
        $planoCompleto = perfil_diretorio_completo($empresa);

        $stmt = $db->prepare("SELECT * FROM vagas_emprego WHERE empresa_id = ? ORDER BY status = 'aberta' DESC, criado_em DESC");
        $stmt->execute([$eid]);

        $this->view('vagas.painel', [
            'titulo'        => 'Vagas de Emprego',
            'vagas'         => $stmt->fetchAll(),
            'planoCompleto' => $planoCompleto,
            'empresa'       => $empresa,
        ]);
    }

    public function salvar(): void
    {
        $this->guard();
        $dados = $this->dadosDoPost();
        if ($dados === null) return;

        $eid = $this->empresaId();
        DB::pdo()->prepare(
            "INSERT INTO vagas_emprego
                (empresa_id, titulo, descricao, requisitos, beneficios, regime, jornada, modalidade,
                 nivel, salario_min, salario_max, salario_a_combinar, cidade, uf)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        )->execute([
            $eid, $dados['titulo'], $dados['descricao'], $dados['requisitos'], $dados['beneficios'],
            $dados['regime'], $dados['jornada'], $dados['modalidade'], $dados['nivel'],
            $dados['salario_min'], $dados['salario_max'], $dados['salario_a_combinar'],
            $dados['cidade'], $dados['uf'],
        ]);

        $this->flash('success', 'Vaga publicada!');
        $this->redirect(url('/empresa/vagas'));
    }

    public function atualizar(string $id): void
    {
        $this->guard();
        $dados = $this->dadosDoPost();
        if ($dados === null) return;

        DB::pdo()->prepare(
            "UPDATE vagas_emprego SET
                titulo = ?, descricao = ?, requisitos = ?, beneficios = ?, regime = ?, jornada = ?,
                modalidade = ?, nivel = ?, salario_min = ?, salario_max = ?, salario_a_combinar = ?,
                cidade = ?, uf = ?
             WHERE id = ? AND empresa_id = ?"
        )->execute([
            $dados['titulo'], $dados['descricao'], $dados['requisitos'], $dados['beneficios'],
            $dados['regime'], $dados['jornada'], $dados['modalidade'], $dados['nivel'],
            $dados['salario_min'], $dados['salario_max'], $dados['salario_a_combinar'],
            $dados['cidade'], $dados['uf'], (int) $id, $this->empresaId(),
        ]);

        $this->flash('success', 'Vaga atualizada!');
        $this->redirect(url('/empresa/vagas'));
    }

    /** Encerrar/reabrir — não exclui, só tira/coloca de volta na listagem pública. */
    public function alternarStatus(string $id): void
    {
        $this->guard();
        $db = DB::pdo();
        $stmt = $db->prepare("SELECT status FROM vagas_emprego WHERE id = ? AND empresa_id = ?");
        $stmt->execute([(int) $id, $this->empresaId()]);
        $atual = $stmt->fetchColumn();
        if ($atual === false) { $this->flash('error', 'Vaga não encontrada.'); $this->redirect(url('/empresa/vagas')); }

        $novo = $atual === 'aberta' ? 'encerrada' : 'aberta';
        $db->prepare("UPDATE vagas_emprego SET status = ? WHERE id = ? AND empresa_id = ?")
           ->execute([$novo, (int) $id, $this->empresaId()]);

        $this->flash('success', $novo === 'aberta' ? 'Vaga reaberta.' : 'Vaga encerrada.');
        $this->redirect(url('/empresa/vagas'));
    }

    public function excluir(string $id): void
    {
        $this->guard();
        // Sem candidatura/currículo vinculado (contato é sempre via WhatsApp, fora do sistema),
        // então excluir de verdade não deixa órfão — diferente de servicos_catalogo, não há
        // motivo pra manter histórico de uma vaga apagada.
        DB::pdo()->prepare("DELETE FROM vagas_emprego WHERE id = ? AND empresa_id = ?")
                 ->execute([(int) $id, $this->empresaId()]);
        $this->flash('success', 'Vaga excluída.');
        $this->redirect(url('/empresa/vagas'));
    }

    /** Lê e valida o POST comum a salvar()/atualizar(); floga erro + null se inválido. */
    private function dadosDoPost(): ?array
    {
        $eid = $this->empresaId();
        $db  = DB::pdo();

        if (!perfil_diretorio_completo($this->empresaAtual($db, $eid))) {
            $this->flash('error', 'Publicar vaga é um recurso exclusivo de assinantes do FixaOS.');
            $this->redirect(url('/empresa/vagas'));
            return null;
        }

        $titulo     = trim((string) $this->post('titulo', ''));
        $descricao  = trim((string) $this->post('descricao', ''));
        $requisitos = trim((string) $this->post('requisitos', '')) ?: null;
        $beneficios = trim((string) $this->post('beneficios', '')) ?: null;
        $regime     = (string) $this->post('regime', 'clt');
        $jornada    = (string) $this->post('jornada', 'integral');
        $modalidade = (string) $this->post('modalidade', 'presencial');
        $nivel      = (string) $this->post('nivel', '');
        $cidade     = trim((string) $this->post('cidade', '')) ?: null;
        $uf         = strtoupper(trim((string) $this->post('uf', ''))) ?: null;
        $aCombinar  = $this->post('salario_a_combinar') ? 1 : 0;
        $salMin     = null;
        $salMax     = null;
        if (!$aCombinar) {
            $v = moeda_float($this->post('salario_min', 0));
            $salMin = $v > 0 ? $v : null;
            $v = moeda_float($this->post('salario_max', 0));
            $salMax = $v > 0 ? $v : null;
        }

        if ($titulo === '' || $descricao === '') {
            $this->flash('error', 'Informe pelo menos o título e a descrição da vaga.');
            $this->redirect(url('/empresa/vagas'));
            return null;
        }
        if (!array_key_exists($regime, self::REGIMES))         $regime     = 'clt';
        if (!array_key_exists($jornada, self::JORNADAS))       $jornada    = 'integral';
        if (!array_key_exists($modalidade, self::MODALIDADES)) $modalidade = 'presencial';
        if ($nivel !== '' && !array_key_exists($nivel, self::NIVEIS)) $nivel = '';

        // Sem cidade/UF informados na vaga, cai na localização cadastrada da própria empresa —
        // é o caso comum (contratando pra própria loja); só precisa digitar diferente quando a
        // franquia/rede está contratando pra outra unidade.
        if (!$cidade || !$uf) {
            $empresa = $this->empresaAtual($db, $eid);
            $cidade  = $cidade ?: ($empresa['cidade'] ?? null);
            $uf      = $uf     ?: ($empresa['uf'] ?? null);
        }

        return [
            'titulo' => $titulo, 'descricao' => $descricao, 'requisitos' => $requisitos,
            'beneficios' => $beneficios, 'regime' => $regime, 'jornada' => $jornada,
            'modalidade' => $modalidade, 'nivel' => $nivel ?: null,
            'salario_min' => $salMin, 'salario_max' => $salMax, 'salario_a_combinar' => $aCombinar,
            'cidade' => $cidade, 'uf' => $uf,
        ];
    }

    private function empresaAtual(\PDO $db, int $eid): array
    {
        static $cache = [];
        if (!isset($cache[$eid])) {
            $stmt = $db->prepare("SELECT * FROM empresas WHERE id = ?");
            $stmt->execute([$eid]);
            $cache[$eid] = $stmt->fetch() ?: [];
        }
        return $cache[$eid];
    }

    private function guard(): void
    {
        if (!csrf_verify()) { $this->flash('error', 'Sessão expirada. Tente novamente.'); $this->redirect(url('/empresa/vagas')); }
        if (!Auth::can('config', 'editar')) { $this->flash('error', 'Você não tem permissão para gerenciar vagas.'); $this->redirect(url('/empresa/vagas')); }
    }

    // ── Público (sem login) ─────────────────────────────────────────────────

    public function publico(): void
    {
        $page   = max(1, (int) $this->get('page', 1));
        $filtro = [
            'busca'      => trim((string) $this->get('busca', '')),
            'uf'         => strtoupper(trim((string) $this->get('uf', ''))),
            'cidade'     => trim((string) $this->get('cidade', '')),
            'regime'     => (string) $this->get('regime', ''),
            'modalidade' => (string) $this->get('modalidade', ''),
            'nivel'      => (string) $this->get('nivel', ''),
        ];

        $db = DB::pdo();
        // Só vaga aberta, de empresa ativa e com licença paga em dia — se o plano vencer depois
        // de publicar, a vaga some da lista pública sozinha (mesmo critério de acesso já usado
        // pra publicar), sem precisar de uma rotina separada pra "despublicar" nada.
        $where  = "v.status = 'aberta' AND e.ativo = 1 AND e.licenca_ate >= CURDATE()";
        $params = [];

        if ($filtro['busca'] !== '') {
            $where .= " AND (v.titulo LIKE ? OR v.descricao LIKE ?)";
            $b = "%{$filtro['busca']}%";
            array_push($params, $b, $b);
        }
        if ($filtro['uf'] !== '')         { $where .= " AND v.uf = ?"; $params[] = $filtro['uf']; }
        if ($filtro['cidade'] !== '')     { $where .= " AND v.cidade LIKE ?"; $params[] = "%{$filtro['cidade']}%"; }
        if (array_key_exists($filtro['regime'], self::REGIMES))         { $where .= " AND v.regime = ?";     $params[] = $filtro['regime']; }
        if (array_key_exists($filtro['modalidade'], self::MODALIDADES)) { $where .= " AND v.modalidade = ?"; $params[] = $filtro['modalidade']; }
        if (array_key_exists($filtro['nivel'], self::NIVEIS))           { $where .= " AND v.nivel = ?";      $params[] = $filtro['nivel']; }

        $perPage = 15;
        $stmtC = $db->prepare("SELECT COUNT(*) FROM vagas_emprego v JOIN empresas e ON e.id = v.empresa_id WHERE {$where}");
        $stmtC->execute($params);
        $total  = (int) $stmtC->fetchColumn();
        $offset = ($page - 1) * $perPage;

        $stmt = $db->prepare(
            "SELECT v.*, e.nome_fantasia AS empresa_nome, e.logo AS empresa_logo,
                    e.cidade AS empresa_cidade, e.uf AS empresa_uf
             FROM vagas_emprego v JOIN empresas e ON e.id = v.empresa_id
             WHERE {$where}
             ORDER BY v.criado_em DESC
             LIMIT {$perPage} OFFSET {$offset}"
        );
        $stmt->execute($params);
        $vagas = $stmt->fetchAll();

        $paginator = [
            'data' => $vagas, 'total' => $total, 'per_page' => $perPage,
            'current_page' => $page, 'last_page' => (int) max(1, ceil($total / $perPage)),
        ];

        $tituloFull = 'Vagas de Emprego para Assistência Técnica — FixaOS';
        $metaDesc   = 'Vagas de emprego pra técnico de eletrônicos em assistências técnicas de todo o Brasil. Contato direto com a empresa pelo WhatsApp, sem cadastro.';
        $noindex    = (bool) ($filtro['busca'] || $filtro['uf'] || $filtro['cidade'] || $filtro['regime'] || $filtro['modalidade'] || $filtro['nivel'] || $page > 1);

        $this->view('vagas.publico', [
            'titulo' => 'Vagas de Emprego', 'vagas' => $vagas, 'paginator' => $paginator,
            'filtro' => $filtro, 'tituloFull' => $tituloFull, 'metaDesc' => $metaDesc, 'noindex' => $noindex,
        ], 'landing');
    }

    public function ver(string $id): void
    {
        $db = DB::pdo();
        $stmt = $db->prepare(
            "SELECT v.*, e.nome_fantasia AS empresa_nome, e.logo AS empresa_logo, e.whatsapp AS empresa_whatsapp,
                    e.telefone AS empresa_telefone, e.cidade AS empresa_cidade, e.uf AS empresa_uf, e.slug AS empresa_slug
             FROM vagas_emprego v JOIN empresas e ON e.id = v.empresa_id
             WHERE v.id = ? AND v.status = 'aberta' AND e.ativo = 1 AND e.licenca_ate >= CURDATE()"
        );
        $stmt->execute([(int) $id]);
        $vaga = $stmt->fetch();
        if (!$vaga) {
            $this->view('vagas.nao_encontrada', ['titulo' => 'Vaga não encontrada', 'noindex' => true], 'landing');
            return;
        }

        $appCfg    = require BASE_PATH . '/config/app.php';
        $baseUrl   = rtrim($appCfg['url'], '/');
        $tituloFull = $vaga['titulo'] . ' — ' . $vaga['empresa_nome'] . ' | Vagas FixaOS';
        $metaDesc   = mb_substr(strip_tags($vaga['descricao']), 0, 155);
        $canonical  = $baseUrl . '/vagas/' . $vaga['id'];

        $this->view('vagas.ver', [
            'titulo' => $vaga['titulo'], 'vaga' => $vaga,
            'tituloFull' => $tituloFull, 'metaDesc' => $metaDesc, 'canonical' => $canonical,
        ], 'landing');
    }
}
