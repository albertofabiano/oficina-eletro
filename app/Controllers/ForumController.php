<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\DB;
use App\Services\ImageService;

class ForumController extends Controller
{
    private \PDO $db;
    private int $eid;
    private int $uid;

    private string $appUrl;

    public function __construct()
    {
        $this->db     = DB::pdo();
        $this->eid    = $this->empresaId();
        $this->uid    = $this->usuarioId();
        $cfg          = require BASE_PATH . '/config/app.php';
        $this->appUrl = rtrim($cfg['url'], '/');
    }

    // ── helpers ─────────────────────────────────────────────────────────

    private function categorias(): array
    {
        return $this->db->query(
            "SELECT fc.*,
             COUNT(DISTINCT ft.id)  AS total_topicos,
             COUNT(DISTINCT fr.id)  AS total_respostas,
             (SELECT ft2.titulo FROM forum_topicos ft2
              WHERE ft2.forum_categoria_id = fc.id
              ORDER BY ft2.criado_em DESC LIMIT 1) AS ultimo_topico,
             (SELECT ft2.criado_em FROM forum_topicos ft2
              WHERE ft2.forum_categoria_id = fc.id
              ORDER BY ft2.criado_em DESC LIMIT 1) AS ultimo_em
             FROM forum_categorias fc
             LEFT JOIN forum_topicos ft ON ft.forum_categoria_id = fc.id
             LEFT JOIN forum_respostas fr ON fr.forum_topico_id = ft.id
             WHERE fc.ativo = 1
             GROUP BY fc.id
             ORDER BY fc.ordem"
        )->fetchAll();
    }

    private function layout(): string
    {
        return \App\Core\Auth::check() ? 'main' : 'forum_publico';
    }

    private function badgePerfil(string $perfil): string
    {
        return match($perfil) {
            'tecnico'     => 'Técnico',
            'admin'       => 'Admin',
            'superadmin'  => 'Admin',
            'gerente'     => 'Gerente',
            'recepcionista' => 'Recepcionista',
            default       => ucfirst($perfil),
        };
    }

    // ── Página pública: lista de categorias ──────────────────────────────
    public function publico(): void
    {
        $cats = $this->categorias();
        $desc = 'Fórum de técnicos de eletrônica — ' . array_sum(array_column($cats, 'total_topicos')) . ' tópicos sobre defeitos de placa, firmware e dicas de reparo.';

        $schema = json_encode([
            '@context' => 'https://schema.org',
            '@type'    => 'WebSite',
            'name'     => 'FixaOS Fórum de Técnicos',
            'url'      => $this->appUrl . '/forum',
            'description' => $desc,
            'potentialAction' => [
                '@type'       => 'SearchAction',
                'target'      => $this->appUrl . '/forum/buscar?q={search_term_string}',
                'query-input' => 'required name=search_term_string',
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $this->view('forum.index', [
            'titulo'     => 'Fórum de Técnicos em Eletrônica | FixaOS',
            'metaDesc'   => $desc,
            'canonical'  => $this->appUrl . '/forum',
            'schemaJson' => $schema,
            'categorias' => $cats,
            'appUrl'     => $this->appUrl,
        ], $this->layout());
    }

    // ── Página pública: tópicos de uma categoria ─────────────────────────
    public function categoriaPub(string $id): void
    {
        $stmt = $this->db->prepare("SELECT * FROM forum_categorias WHERE id = ? AND ativo = 1");
        $stmt->execute([(int)$id]);
        $categoria = $stmt->fetch();
        if (!$categoria) { header('Location: ' . $this->appUrl . '/forum'); exit; }

        $page    = (int)($this->get('page') ?? 1);
        $busca   = trim($this->get('busca', ''));
        $perPage = 20;
        $where   = "ft.forum_categoria_id = ?";
        $params  = [(int)$id];

        if ($busca) {
            $where .= " AND (ft.titulo LIKE ? OR ft.conteudo LIKE ? OR ft.marca LIKE ? OR ft.modelo LIKE ?)";
            $b = "%{$busca}%"; array_push($params, $b, $b, $b, $b);
        }

        $total = (int)$this->db->prepare("SELECT COUNT(*) FROM forum_topicos ft WHERE {$where}")
                               ->execute($params) ? $this->db->prepare("SELECT COUNT(*) FROM forum_topicos ft WHERE {$where}") : 0;
        $stmtC = $this->db->prepare("SELECT COUNT(*) FROM forum_topicos ft WHERE {$where}");
        $stmtC->execute($params);
        $total = (int)$stmtC->fetchColumn();

        $stmtT = $this->db->prepare(
            "SELECT ft.*, e.nome_fantasia AS empresa_nome,
             COALESCE(u.nome, 'Usuário removido') AS autor_nome, u.perfil AS autor_perfil,
             COUNT(DISTINCT fr.id) AS total_respostas,
             COUNT(DISTINCT fcu.id) AS total_curtidas,
             MAX(fr.criado_em) AS ultima_resposta
             FROM forum_topicos ft
             LEFT JOIN empresas e ON e.id = ft.empresa_id
             LEFT JOIN usuarios u ON u.id = ft.usuario_id
             LEFT JOIN forum_respostas fr ON fr.forum_topico_id = ft.id
             LEFT JOIN forum_curtidas fcu ON fcu.forum_topico_id = ft.id
             WHERE {$where}
             GROUP BY ft.id
             ORDER BY ft.fixado DESC, COALESCE(MAX(fr.criado_em), ft.criado_em) DESC
             LIMIT {$perPage} OFFSET " . (($page - 1) * $perPage)
        );
        $stmtT->execute($params);

        $desc   = "Tópicos sobre {$categoria['nome']} — defeitos, soluções e dicas de reparo de eletrônicos.";
        $schema = json_encode([
            '@context' => 'https://schema.org',
            '@type'    => 'BreadcrumbList',
            'itemListElement' => [
                ['@type'=>'ListItem','position'=>1,'name'=>'Fórum','item'=>$this->appUrl.'/forum'],
                ['@type'=>'ListItem','position'=>2,'name'=>$categoria['nome'],'item'=>$this->appUrl.'/forum/categoria/'.$id],
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $this->view('forum.categoria', [
            'titulo'    => $categoria['nome'] . ' — Fórum FixaOS',
            'metaDesc'  => $desc,
            'canonical' => $this->appUrl . '/forum/categoria/' . $id,
            'schemaJson'=> $schema,
            'categoria' => $categoria,
            'topicos'   => $stmtT->fetchAll(),
            'paginator' => ['total'=>$total,'current_page'=>$page,'last_page'=>(int)ceil($total/$perPage),'per_page'=>$perPage],
            'busca'     => $busca,
            'appUrl'    => $this->appUrl,
        ], $this->layout());
    }

    // ── Página pública: ver tópico (com SEO) ─────────────────────────────
    public function topicoPub(string $id): void
    {
        $stmt = $this->db->prepare(
            "SELECT ft.*, fc.nome AS cat_nome, fc.id AS cat_id, fc.cor AS cat_cor, fc.icone AS cat_icone,
             e.nome_fantasia AS empresa_nome,
             COALESCE(u.nome, 'Usuário removido') AS autor_nome, u.perfil AS autor_perfil
             FROM forum_topicos ft
             JOIN forum_categorias fc ON fc.id = ft.forum_categoria_id
             LEFT JOIN empresas e ON e.id = ft.empresa_id
             LEFT JOIN usuarios u ON u.id = ft.usuario_id
             WHERE ft.id = ?"
        );
        $stmt->execute([(int)$id]);
        $topico = $stmt->fetch();
        if (!$topico) { header('Location: ' . $this->appUrl . '/forum'); exit; }

        // Incrementar views
        $this->db->prepare("UPDATE forum_topicos SET visualizacoes = visualizacoes + 1 WHERE id = ?")
                 ->execute([(int)$id]);

        // Respostas com perfil do autor
        $stmtR = $this->db->prepare(
            "SELECT fr.*,
             e.nome_fantasia AS empresa_nome,
             COALESCE(u.nome, 'Usuário removido') AS autor_nome, u.perfil AS autor_perfil,
             COUNT(DISTINCT fcu.id) AS total_curtidas,
             EXISTS(SELECT 1 FROM forum_curtidas WHERE forum_resposta_id = fr.id AND usuario_id = ?) AS curtiu
             FROM forum_respostas fr
             LEFT JOIN empresas e ON e.id = fr.empresa_id
             LEFT JOIN usuarios u ON u.id = fr.usuario_id
             LEFT JOIN forum_curtidas fcu ON fcu.forum_resposta_id = fr.id
             WHERE fr.forum_topico_id = ?
             GROUP BY fr.id
             ORDER BY fr.melhor_resposta DESC, fr.criado_em ASC"
        );
        $stmtR->execute([$this->uid, (int)$id]);
        $respostas = $stmtR->fetchAll();

        // Arquivos
        $stmtA = $this->db->prepare("SELECT * FROM forum_arquivos WHERE forum_topico_id = ? ORDER BY criado_em");
        $stmtA->execute([(int)$id]);
        $arquivos = $stmtA->fetchAll();

        // Curtiu?
        $stmtC = $this->db->prepare("SELECT COUNT(*) FROM forum_curtidas WHERE forum_topico_id = ? AND usuario_id = ?");
        $stmtC->execute([(int)$id, $this->uid]);
        $curtiu = (bool)$stmtC->fetchColumn();

        // Schema.org DiscussionForumPosting
        $schema = json_encode([
            '@context'     => 'https://schema.org',
            '@type'        => 'DiscussionForumPosting',
            'headline'     => $topico['titulo'],
            'text'         => mb_substr(strip_tags($topico['conteudo']), 0, 500),
            'datePublished'=> $topico['criado_em'],
            'dateModified' => $topico['atualizado_em'],
            'url'          => $this->appUrl . '/forum/topico/' . $id,
            'author'       => [
                '@type' => 'Person',
                'name'  => $topico['autor_nome'],
                'jobTitle' => $this->badgePerfil($topico['autor_perfil'] ?? 'tecnico'),
            ],
            'about' => array_filter([
                $topico['marca'] ?? null,
                $topico['modelo'] ?? null,
                $topico['cat_nome'],
            ]),
            'interactionStatistic' => [
                '@type'                => 'InteractionCounter',
                'interactionType'      => 'https://schema.org/CommentAction',
                'userInteractionCount' => count($respostas),
            ],
            'breadcrumb' => [
                '@type' => 'BreadcrumbList',
                'itemListElement' => [
                    ['@type'=>'ListItem','position'=>1,'name'=>'Fórum','item'=>$this->appUrl.'/forum'],
                    ['@type'=>'ListItem','position'=>2,'name'=>$topico['cat_nome'],'item'=>$this->appUrl.'/forum/categoria/'.$topico['cat_id']],
                    ['@type'=>'ListItem','position'=>3,'name'=>$topico['titulo'],'item'=>$this->appUrl.'/forum/topico/'.$id],
                ],
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $metaDesc = mb_substr(strip_tags($topico['conteudo']), 0, 160);
        if ($topico['marca'] || $topico['modelo']) {
            $metaDesc = trim($topico['marca'] . ' ' . $topico['modelo']) . ' — ' . $metaDesc;
        }

        $this->view('forum.topico', [
            'titulo'     => $topico['titulo'],
            'metaDesc'   => $metaDesc,
            'canonical'  => $this->appUrl . '/forum/topico/' . $id,
            'schemaJson' => $schema,
            'topico'     => $topico,
            'respostas'  => $respostas,
            'arquivos'   => $arquivos,
            'curtiu'     => $curtiu,
            'appUrl'     => $this->appUrl,
        ], $this->layout());
    }

    // ── Busca global pública ─────────────────────────────────────────────
    public function buscar(): void
    {
        $q       = trim($this->get('q', ''));
        $results = [];

        if (strlen($q) >= 3) {
            $b = "%{$q}%";
            $stmt = $this->db->prepare(
                "SELECT ft.id, ft.titulo, ft.criado_em, ft.marca, ft.modelo,
                 fc.nome AS cat_nome, fc.id AS cat_id,
                 COALESCE(u.nome, 'Usuário removido') AS autor_nome, u.perfil AS autor_perfil,
                 COUNT(DISTINCT fr.id) AS total_respostas
                 FROM forum_topicos ft
                 JOIN forum_categorias fc ON fc.id = ft.forum_categoria_id
                 LEFT JOIN usuarios u ON u.id = ft.usuario_id
                 LEFT JOIN forum_respostas fr ON fr.forum_topico_id = ft.id
                 WHERE ft.titulo LIKE ? OR ft.conteudo LIKE ? OR ft.marca LIKE ? OR ft.modelo LIKE ?
                 GROUP BY ft.id
                 ORDER BY ft.criado_em DESC
                 LIMIT 30"
            );
            $stmt->execute([$b, $b, $b, $b]);
            $results = $stmt->fetchAll();
        }

        $this->view('forum.busca', [
            'titulo'   => 'Buscar no Fórum — FixaOS',
            'metaDesc' => 'Busca no fórum de técnicos de eletrônica.',
            'canonical'=> $this->appUrl . '/forum/buscar',
            'q'        => $q,
            'results'  => $results,
            'appUrl'   => $this->appUrl,
        ], $this->layout());
    }

    // ── Criar tópico (GET) — requer login ────────────────────────────────
    // ── Cadastro simples e gratuito para participar do fórum ─────────────
    // Cria uma conta leve tipo_conta='diretorio' (fora do diretório público),
    // que dá acesso só ao fórum — sem cobrança, sem sistema completo.
    public function cadastro(): void
    {
        $this->view('forum.cadastro', [
            'titulo'    => 'Criar conta grátis — Fórum FixaOS',
            'metaDesc'  => 'Cadastre-se gratuitamente para participar do fórum de técnicos em eletrônica do FixaOS.',
            'canonical' => $this->appUrl . '/forum/cadastrar',
            'appUrl'    => $this->appUrl,
        ], 'forum_publico');
    }

    public function registrar(): void
    {
        $back = $this->appUrl . '/forum/cadastrar';
        if (!csrf_verify()) { $this->flash('error', 'Token inválido.'); $this->redirect($back); }
        // Honeypot anti-bot.
        if (trim((string) $this->post('website', '')) !== '') { $this->redirect($this->appUrl . '/forum'); }

        $nome  = trim($this->post('nome', ''));
        $email = trim(mb_strtolower($this->post('email', '')));
        $senha = (string) $this->post('senha', '');

        if (mb_strlen($nome) < 2 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($senha) < 6) {
            $this->flash('error', 'Preencha nome, e-mail válido e senha (mínimo 6 caracteres).');
            $this->redirect($back);
        }

        $chk = $this->db->prepare("SELECT COUNT(*) FROM usuarios WHERE email = ?");
        $chk->execute([$email]);
        if ((int) $chk->fetchColumn() > 0) {
            $this->flash('error', 'Já existe uma conta com esse e-mail. Faça login para participar.');
            $this->redirect($this->appUrl . '/login');
        }

        $senhaHash = password_hash($senha, PASSWORD_BCRYPT);
        $nome100   = mb_substr($nome, 0, 100);

        // Conta leve: tipo_conta='diretorio', fora do diretório público (listagem_publica=0).
        $this->db->beginTransaction();
        try {
            $this->db->prepare(
                "INSERT INTO empresas (razao_social, nome_fantasia, email, tipo_conta, listagem_publica, ativo)
                 VALUES (?, ?, ?, 'diretorio', 0, 1)"
            )->execute([$nome100, $nome100, mb_substr($email, 0, 100)]);
            $empresaId = (int) $this->db->lastInsertId();

            $this->db->prepare(
                "INSERT INTO usuarios (empresa_id, nome, email, senha, perfil, ativo)
                 VALUES (?, ?, ?, ?, 'admin', 1)"
            )->execute([$empresaId, $nome100, mb_substr($email, 0, 100), $senhaHash]);

            $this->db->commit();
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) { $this->db->rollBack(); }
            $this->flash('error', 'Não foi possível criar sua conta agora. Tente novamente em instantes.');
            $this->redirect($back);
        }

        // Capta como lead (best-effort).
        try {
            $this->db->exec("CREATE TABLE IF NOT EXISTS lista_espera (
              id INT AUTO_INCREMENT PRIMARY KEY, email VARCHAR(150) NOT NULL,
              origem VARCHAR(40) NOT NULL DEFAULT 'landing', convidado TINYINT NOT NULL DEFAULT 0,
              convidado_em TIMESTAMP NULL, criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
              UNIQUE KEY uq_email (email)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            $this->db->prepare("INSERT IGNORE INTO lista_espera (email, origem) VALUES (?, 'forum')")
               ->execute([mb_substr($email, 0, 150)]);
        } catch (\Throwable $e) { /* silencioso */ }

        // Login automático → volta pro fórum já participando.
        try {
            $stmtLogin = $this->db->prepare(
                "SELECT u.*, e.nome_fantasia AS empresa_nome FROM usuarios u
                 JOIN empresas e ON e.id = u.empresa_id
                 WHERE u.empresa_id = ? AND u.email = ? LIMIT 1"
            );
            $stmtLogin->execute([$empresaId, $email]);
            if ($novo = $stmtLogin->fetch()) { \App\Core\Auth::login($novo, []); }
        } catch (\Throwable $e) { /* redireciona pro login abaixo */ }

        $this->flash('success', 'Conta criada! Bem-vindo à comunidade. 🎉');
        $this->redirect(\App\Core\Auth::check() ? $this->appUrl . '/forum' : $this->appUrl . '/login');
    }

    public function criar(): void
    {
        $catId = $this->get('categoria');
        $cats  = $this->db->query("SELECT * FROM forum_categorias WHERE ativo=1 ORDER BY ordem")->fetchAll();
        $this->view('forum.criar', [
            'titulo'     => 'Novo Tópico — Fórum',
            'categorias' => $cats,
            'cat_id'     => $catId,
        ]);
    }

    // ── Salvar tópico (POST) ─────────────────────────────────────────────
    public function salvar(): void
    {
        if (!csrf_verify()) { $this->flash('error', 'Token inválido.'); $this->redirectBack(); }

        $titulo   = trim($this->post('titulo', ''));
        $conteudo = trim($this->post('conteudo', ''));
        $catId    = (int)$this->post('forum_categoria_id');

        if (!$titulo || !$conteudo || !$catId) {
            $this->flash('error', 'Preencha todos os campos obrigatórios.');
            $this->redirectBack();
        }

        $versao = trim($this->post('versao_firmware', ''));
        $urlExt = trim($this->post('url_externa', ''));

        $this->db->prepare(
            "INSERT INTO forum_topicos (forum_categoria_id, empresa_id, usuario_id, titulo, conteudo, marca, modelo, versao_firmware, url_externa)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        )->execute([$catId, $this->eid, $this->uid, $titulo, $conteudo,
            trim($this->post('marca', '')), trim($this->post('modelo', '')),
            $versao ?: null, $urlExt ?: null]);
        $topicoId = (int)$this->db->lastInsertId();

        $this->flash('success', 'Tópico criado com sucesso!');
        $this->redirect($this->appUrl . '/forum/topico/' . $topicoId);
    }

    // ── Responder (POST) ─────────────────────────────────────────────────
    public function responder(string $topicoId): void
    {
        if (!csrf_verify()) { $this->json(['error' => 'Token inválido'], 403); }

        $conteudo = trim($this->post('conteudo', ''));
        if (!$conteudo) { $this->json(['error' => 'Resposta vazia'], 422); }

        $this->db->prepare(
            "INSERT INTO forum_respostas (forum_topico_id, empresa_id, usuario_id, conteudo)
             VALUES (?, ?, ?, ?)"
        )->execute([(int)$topicoId, $this->eid, $this->uid, $conteudo]);
        $respostaId = (int)$this->db->lastInsertId();

        if (!empty($_FILES['arquivos']['name'][0])) {
            $this->processarArquivos($_FILES['arquivos'], null, $respostaId);
        }

        $this->flash('success', 'Resposta publicada!');
        $this->redirect($this->appUrl . '/forum/topico/' . $topicoId . '#resposta-' . $respostaId);
    }

    // ── Curtir ────────────────────────────────────────────────────────────
    public function curtir(): void
    {
        if (!csrf_verify()) { $this->json(['error' => 'Token inválido'], 403); }

        $topicoId   = $this->post('topico_id') ? (int)$this->post('topico_id') : null;
        $respostaId = $this->post('resposta_id') ? (int)$this->post('resposta_id') : null;
        if (!$topicoId && !$respostaId) { $this->json(['error' => 'ID inválido'], 422); }

        $col = $topicoId ? 'forum_topico_id' : 'forum_resposta_id';
        $ref = $topicoId ?? $respostaId;

        $existe = $this->db->prepare("SELECT id FROM forum_curtidas WHERE usuario_id=? AND {$col}=?");
        $existe->execute([$this->uid, $ref]);

        if ($existe->fetch()) {
            $this->db->prepare("DELETE FROM forum_curtidas WHERE usuario_id=? AND {$col}=?")->execute([$this->uid, $ref]);
            $curtiu = false;
        } else {
            $this->db->prepare("INSERT INTO forum_curtidas (usuario_id, {$col}) VALUES (?,?)")->execute([$this->uid, $ref]);
            $curtiu = true;
        }

        $count = $this->db->prepare("SELECT COUNT(*) FROM forum_curtidas WHERE {$col}=?");
        $count->execute([$ref]);
        $this->json(['success'=>true,'curtiu'=>$curtiu,'total'=>(int)$count->fetchColumn()]);
    }

    // ── Melhor resposta ───────────────────────────────────────────────────
    public function melhorResposta(string $respostaId): void
    {
        if (!csrf_verify()) { $this->json(['error' => 'Token inválido'], 403); }

        $stmt = $this->db->prepare(
            "SELECT fr.forum_topico_id, ft.usuario_id
             FROM forum_respostas fr
             JOIN forum_topicos ft ON ft.id = fr.forum_topico_id
             WHERE fr.id = ?"
        );
        $stmt->execute([(int)$respostaId]);
        $row = $stmt->fetch();
        if (!$row || $row['usuario_id'] != $this->uid) { $this->json(['error'=>'Sem permissão'],403); }

        $this->db->prepare("UPDATE forum_respostas SET melhor_resposta=0 WHERE forum_topico_id=?")->execute([$row['forum_topico_id']]);
        $this->db->prepare("UPDATE forum_respostas SET melhor_resposta=1 WHERE id=?")->execute([(int)$respostaId]);
        $this->db->prepare("UPDATE forum_topicos SET resolvido=1 WHERE id=?")->execute([$row['forum_topico_id']]);
        $this->json(['success'=>true]);
    }

    // ── Download de arquivo ───────────────────────────────────────────────
    public function download(string $id): void
    {
        $stmt = $this->db->prepare("SELECT * FROM forum_arquivos WHERE id = ?");
        $stmt->execute([(int)$id]);
        $arq = $stmt->fetch();
        if (!$arq) { http_response_code(404); exit('Arquivo não encontrado.'); }

        $caminho = BASE_PATH . '/storage/uploads/forum/' . $arq['nome_arquivo'];
        if (!file_exists($caminho)) { http_response_code(404); exit('Arquivo não encontrado.'); }

        $this->db->prepare("UPDATE forum_arquivos SET downloads=downloads+1 WHERE id=?")->execute([(int)$id]);
        header('Content-Type: ' . ($arq['tipo_mime'] ?: 'application/octet-stream'));
        header('Content-Disposition: attachment; filename="' . $arq['nome_original'] . '"');
        header('Content-Length: ' . filesize($caminho));
        readfile($caminho);
        exit;
    }

    // ── Excluir tópico ────────────────────────────────────────────────────
    public function excluirTopico(string $id): void
    {
        $stmt = $this->db->prepare("SELECT * FROM forum_topicos WHERE id=? AND (empresa_id=? OR usuario_id=?)");
        $stmt->execute([(int)$id, $this->eid, $this->uid]);
        if (!$stmt->fetch()) { $this->flash('error','Sem permissão.'); $this->redirect($this->appUrl.'/forum'); }
        $this->db->prepare("DELETE FROM forum_topicos WHERE id=?")->execute([(int)$id]);
        $this->flash('success','Tópico removido.');
        $this->redirect($this->appUrl . '/forum');
    }

    // ── Excluir resposta ──────────────────────────────────────────────────
    public function excluirResposta(string $id): void
    {
        if (!csrf_verify()) { $this->json(['error'=>'Token inválido'],403); }
        $stmt = $this->db->prepare("SELECT * FROM forum_respostas WHERE id=? AND (empresa_id=? OR usuario_id=?)");
        $stmt->execute([(int)$id, $this->eid, $this->uid]);
        if (!$stmt->fetch()) { $this->json(['error'=>'Sem permissão'],403); }
        $this->db->prepare("DELETE FROM forum_respostas WHERE id=?")->execute([(int)$id]);
        $this->json(['success'=>true]);
    }

    // ── Upload ────────────────────────────────────────────────────────────
    private function processarArquivos(array $files, ?int $topicoId, ?int $respostaId): void
    {
        $tiposPermitidos = ['image/jpeg','image/png','image/webp','image/gif','application/pdf',
            'application/zip','application/x-zip-compressed','application/octet-stream','text/plain'];
        $maxSize = 50 * 1024 * 1024;
        $dir     = BASE_PATH . '/storage/uploads/forum/';

        for ($i = 0; $i < count($files['name']); $i++) {
            if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;
            if ($files['size'][$i] > $maxSize) continue;
            $mime = mime_content_type($files['tmp_name'][$i]);
            $ext  = strtolower(pathinfo($files['name'][$i], PATHINFO_EXTENSION));
            if (!in_array($mime, $tiposPermitidos) && !in_array($ext, ['jpg','jpeg','png','webp','gif','pdf','zip','bin','fw','rom','hex','txt','rar'])) continue;
            $nomeArq = uniqid('forum_', true) . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $files['name'][$i]);
            $destino = $dir . $nomeArq;
            if (!move_uploaded_file($files['tmp_name'][$i], $destino)) continue;
            $tamanho = $files['size'][$i];

            // Comprimir pra WebP só as imagens estáticas (jpeg/png). GIF fica intocado
            // pra não perder animação (GD só lê o primeiro frame ao converter).
            if (in_array($mime, ['image/jpeg', 'image/png'], true)) {
                $nomeWebp = preg_replace('/\.[^.]+$/', '', $nomeArq) . '.webp';
                if (ImageService::paraWebp($destino, $dir . $nomeWebp, 85, 2000)) {
                    @unlink($destino);
                    $nomeArq = $nomeWebp;
                    $mime    = 'image/webp';
                    $tamanho = filesize($dir . $nomeWebp) ?: $tamanho;
                }
            }

            $this->db->prepare(
                "INSERT INTO forum_arquivos (forum_topico_id, forum_resposta_id, empresa_id, usuario_id, nome_original, nome_arquivo, tipo_mime, tamanho)
                 VALUES (?,?,?,?,?,?,?,?)"
            )->execute([$topicoId, $respostaId, $this->eid, $this->uid, $files['name'][$i], $nomeArq, $mime, $tamanho]);
        }
    }
}
