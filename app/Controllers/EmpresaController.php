<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\DB;
use App\Services\ImageService;

class EmpresaController extends Controller
{
    public function index(): void
    {
        $eid  = $this->empresaId();
        $db   = DB::pdo();

        $stmt = $db->prepare("SELECT * FROM empresas WHERE id = ?");
        $stmt->execute([$eid]);
        $empresa = $stmt->fetch();

        $stmtCfg = $db->prepare("SELECT chave, valor FROM configuracoes WHERE empresa_id = ?");
        $stmtCfg->execute([$eid]);
        $configs = [];
        foreach ($stmtCfg->fetchAll() as $r) $configs[$r['chave']] = $r['valor'];

        $nfInteresse = false;
        try { $qni = $db->prepare("SELECT 1 FROM nf_interesse WHERE empresa_id = ?"); $qni->execute([$eid]); $nfInteresse = (bool) $qni->fetchColumn(); } catch (\Throwable $e) {}

        $this->view('empresa.index', [
            'titulo'  => 'Dados da Empresa',
            'empresa' => $empresa,
            'configs' => $configs,
            'nfInteresse' => $nfInteresse,
        ], $this->layoutAtual());
    }

    /** Registra/remove o interesse da empresa em emitir Nota Fiscal (medidor de demanda). */
    public function interesseNf(): void
    {
        if (!csrf_verify()) { $this->flash('error', 'Token inválido.'); $this->redirectPreservandoPainel(url('/empresa')); }
        $eid = $this->empresaId();
        $db  = DB::pdo();
        try {
            $q = $db->prepare("SELECT 1 FROM nf_interesse WHERE empresa_id = ?"); $q->execute([$eid]);
            if ($q->fetchColumn()) {
                $db->prepare("DELETE FROM nf_interesse WHERE empresa_id = ?")->execute([$eid]);
                $this->flash('success', 'Interesse em nota fiscal removido.');
            } else {
                $db->prepare("INSERT IGNORE INTO nf_interesse (empresa_id) VALUES (?)")->execute([$eid]);
                $this->flash('success', 'Registramos seu interesse em nota fiscal! Avisaremos quando estiver disponível na sua cidade.');
            }
        } catch (\Throwable $e) { $this->flash('error', 'Não foi possível registrar agora.'); }
        $this->redirectPreservandoPainel(url('/empresa'));
    }

    public function salvar(): void
    {
        $ajax = (bool) $this->post('_ajax', false);

        if (!csrf_verify()) {
            if ($ajax) { $this->json(['sucesso' => false, 'erro' => 'Token inválido. Atualize a página.'], 419); }
            $this->flash('error', 'Token inválido.'); $this->redirectBack();
        }

        $eid = $this->empresaId();
        $db  = DB::pdo();

        // Upload de logo
        $logoPath = null;
        if (!empty($_FILES['logo']['tmp_name'])) {
            $logoPath = $this->processarLogo($_FILES['logo'], $eid);
            if ($logoPath === false) {
                $erro = 'Erro no upload da logo. Use JPG, PNG ou SVG até 2MB.';
                if ($ajax) { $this->json(['sucesso' => false, 'erro' => $erro], 422); }
                $this->flash('error', $erro);
                $this->redirectPreservandoPainel(url('/empresa'));
            }
        }

        $idioma = in_array($this->post('idioma'), ['pt_BR','es_MX']) ? $this->post('idioma') : 'pt_BR';
        $_SESSION['usuario']['idioma'] = $idioma;

        $sql = "UPDATE empresas SET razao_social=?, nome_fantasia=?, cnpj=?, email=?, telefone=?, whatsapp=?,
                cep=?, logradouro=?, numero=?, complemento=?, bairro=?, cidade=?, uf=?, idioma=?" .
               ($logoPath ? ", logo=?" : "") .
               " WHERE id=?";

        $params = [
            $this->post('razao_social'),
            $this->post('nome_fantasia'),
            only_numbers($this->post('cnpj', '')),
            $this->post('email'),
            $this->post('telefone'),
            $this->post('whatsapp'),
            only_numbers($this->post('cep', '')),
            $this->post('logradouro'),
            $this->post('numero'),
            $this->post('complemento'),
            $this->post('bairro'),
            $this->post('cidade'),
            $this->post('uf'),
            $idioma,
        ];

        if ($logoPath) $params[] = $logoPath;
        $params[] = $eid;

        $db->prepare($sql)->execute($params);

        // Taxas de cartão (JSON): débito + crédito por nº de parcelas + repassar padrão
        $pct = fn($v) => round((float) str_replace(',', '.', (string) $v), 2);
        $taxaCred = [];
        foreach ((array) $this->post('taxa_credito', []) as $p => $v) {
            $p = (int) $p; $val = $pct($v);
            if ($p >= 1 && $p <= 24 && $val > 0) $taxaCred[$p] = $val;
        }
        ksort($taxaCred);
        $taxasCartao = json_encode([
            'pix'             => $pct($this->post('taxa_pix', '0')),
            'debito'          => $pct($this->post('taxa_debito', '0')),
            'credito'         => $taxaCred,
            'repassar_padrao' => $this->post('cartao_repassar_padrao') ? 1 : 0,
            'modo_recebimento'=> $this->post('modo_recebimento_credito') === 'mes_a_mes' ? 'mes_a_mes' : 'mesmo_dia',
        ], JSON_UNESCAPED_UNICODE);

        // Configurações
        $configs = [
            'os_prefixo'                  => strtoupper(trim($this->post('os_prefixo', ''))),
            'os_digitos'                  => $this->post('os_digitos', '6'),
            'os_numero_inicial'           => $this->post('os_numero_inicial', '1'),
            'garantia_padrao_dias'        => $this->post('garantia_padrao_dias', '90'),
            'prazo_retirada_dias'         => $this->post('prazo_retirada_dias', '30'),
            'comissao_tecnico_percentual' => $this->post('comissao_tecnico_percentual', '0'),
            'comissao_tecnico_modo'       => $this->post('comissao_tecnico_modo') === 'total' ? 'total' : 'mao_obra',
            'texto_entrada_equipamento'   => $this->post('texto_entrada_equipamento', ''),
            'texto_garantia'              => $this->post('texto_garantia', ''),
            'taxas_cartao'                => $taxasCartao,
        ];

        $stmt = $db->prepare(
            "INSERT INTO configuracoes (empresa_id, chave, valor) VALUES (?,?,?)
             ON DUPLICATE KEY UPDATE valor = VALUES(valor)"
        );
        foreach ($configs as $k => $v) $stmt->execute([$eid, $k, $v]);

        // Atualizar nome da empresa na sessão
        if ($this->post('nome_fantasia')) {
            $_SESSION['usuario']['empresa_nome'] = $this->post('nome_fantasia');
        }

        // Geocodificar endereço automaticamente via Nominatim
        $logradouro = $this->post('logradouro', '');
        $numero     = $this->post('numero', '');
        $cidade     = $this->post('cidade', '');
        $uf         = $this->post('uf', '');
        if ($logradouro && $cidade) {
            $q   = urlencode("$logradouro $numero, $cidade, $uf, Brasil");
            $ctx = stream_context_create(['http' => [
                'header'  => 'User-Agent: FixaOS/1.0',
                'timeout' => 3,
            ]]);
            $res = @file_get_contents("https://nominatim.openstreetmap.org/search?q=$q&format=json&limit=1", false, $ctx);
            $geo = $res ? json_decode($res, true) : [];
            if (!empty($geo[0]['lat'])) {
                $db->prepare("UPDATE empresas SET latitude=?, longitude=? WHERE id=?")
                   ->execute([(float)$geo[0]['lat'], (float)$geo[0]['lon'], $eid]);
            }
        }

        if ($ajax) { $this->json(['sucesso' => true, 'mensagem' => 'Dados salvos com sucesso!']); }
        $this->flash('success', 'Dados salvos com sucesso!');
        $this->redirectPreservandoPainel(url('/empresa'));
    }

    public function removerLogo(): void
    {
        $eid = $this->empresaId();
        $db  = DB::pdo();

        $stmt = $db->prepare("SELECT logo FROM empresas WHERE id = ?");
        $stmt->execute([$eid]);
        $logo = $stmt->fetchColumn();

        if ($logo) {
            $arquivo = BASE_PATH . '/storage/uploads/logos/' . basename($logo);
            if (file_exists($arquivo)) @unlink($arquivo);
            $db->prepare("UPDATE empresas SET logo = NULL WHERE id = ?")->execute([$eid]);
        }

        $this->flash('success', 'Logo removida.');
        $this->redirectPreservandoPainel(url('/empresa'));
    }

    // ── Exportar banco de dados da empresa ─────────────────────────────
    public function exportar(): void
    {
        $eid = $this->empresaId();
        $db  = DB::pdo();

        // Apenas clientes e ordens de serviço (com relacionamentos necessários)
        $tabelas = [
            'clientes',
            'equipamentos',
            'ordens_servico',
            'os_pecas',
            'os_servicos',
            'os_historico',
        ];

        $empresa = $db->prepare("SELECT nome_fantasia FROM empresas WHERE id = ?")->execute([$eid])
            ? $db->query("SELECT nome_fantasia FROM empresas WHERE id = {$eid}")->fetchColumn()
            : 'empresa';

        $nomeArq  = 'backup_' . preg_replace('/[^a-z0-9]/i', '_', $empresa) . '_' . date('Ymd_His') . '.sql';

        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="' . $nomeArq . '"');
        header('Cache-Control: no-cache');

        echo "-- FixaOS Backup\n";
        echo "-- Empresa ID: {$eid}\n";
        echo "-- Data: " . date('Y-m-d H:i:s') . "\n";
        echo "-- Empresa: {$empresa}\n\n";
        echo "SET NAMES utf8mb4;\n";
        echo "SET FOREIGN_KEY_CHECKS = 0;\n\n";

        foreach ($tabelas as $tabela) {
            // Verificar se tabela tem empresa_id
            try {
                $cols = $db->query("SHOW COLUMNS FROM `{$tabela}`")->fetchAll(\PDO::FETCH_COLUMN);
            } catch (\Exception $e) { continue; }

            if (!in_array('empresa_id', $cols)) continue;

            $stmt = $db->prepare("SELECT * FROM `{$tabela}` WHERE empresa_id = ?");
            $stmt->execute([$eid]);
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            if (!$rows) continue;

            echo "-- Tabela: {$tabela} (" . count($rows) . " registros)\n";

            foreach ($rows as $row) {
                $colunas = '`' . implode('`, `', array_keys($row)) . '`';
                $valores = implode(', ', array_map(function($v) use ($db) {
                    if ($v === null) return 'NULL';
                    return $db->quote($v);
                }, array_values($row)));
                echo "INSERT INTO `{$tabela}` ({$colunas}) VALUES ({$valores});\n";
            }
            echo "\n";
        }

        echo "SET FOREIGN_KEY_CHECKS = 1;\n";
        echo "-- Fim do backup\n";
        exit;
    }

    // Importação/restauração de banco REMOVIDA (2026-07-12): risco de sobrescrever os
    // dados da empresa + execução de SQL arbitrário do arquivo. Backup agora é só DOWNLOAD
    // (ver exportar()). Se um dia precisar restaurar, é operação manual/suporte, não pela UI.

    public function migracaoShoficina(): void
    {
        $this->view('empresa.migracao_shoficina', ['titulo' => 'Migração SHOficina']);
    }

    public function executarMigracao(): void
    {
        if (!csrf_verify()) { $this->flash('error', 'Token inválido.'); $this->redirect(url('/empresa/migracao-shoficina')); }

        if (empty($_FILES['arquivo_sql']['tmp_name'])) {
            $this->flash('error', 'Nenhum arquivo enviado.');
            $this->redirect(url('/empresa/migracao-shoficina'));
        }

        $file = $_FILES['arquivo_sql'];
        if (pathinfo($file['name'], PATHINFO_EXTENSION) !== 'sql') {
            $this->flash('error', 'Apenas arquivos .sql são aceitos.');
            $this->redirect(url('/empresa/migracao-shoficina'));
        }

        if ($file['size'] > 50 * 1024 * 1024) {
            $this->flash('error', 'Arquivo muito grande. Máximo 50MB.');
            $this->redirect(url('/empresa/migracao-shoficina'));
        }

        $conteudo = file_get_contents($file['tmp_name']);

        // Validar que é um arquivo de migração SHOficina
        if (!str_contains($conteudo, 'Migração SHOficina') && !str_contains($conteudo, 'migracao_shoficina') && !str_contains($conteudo, '_mapa_cli')) {
            $this->flash('error', 'Arquivo inválido. Use apenas arquivos gerados pela ferramenta de migração FixaOS.');
            $this->redirect(url('/empresa/migracao-shoficina'));
        }

        $db = DB::pdo();
        $eid = $this->empresaId();

        try {
            $db->exec("SET FOREIGN_KEY_CHECKS = 0");

            // Substituir empresa_id fixo (1) pelo ID real da empresa logada
            $conteudo = str_replace('empresa_id,', 'empresa_id,', $conteudo);
            $conteudo = preg_replace('/VALUES \(1,/', "VALUES ($eid,", $conteudo);

            // Executar linha por linha
            $linhas = explode("\n", $conteudo);
            $buffer = '';
            $inseridos = ['clientes'=>0,'equipamentos'=>0,'ordens_servico'=>0,'os_servicos'=>0,'os_pecas'=>0];

            foreach ($linhas as $linha) {
                $linha = trim($linha);
                if ($linha === '' || str_starts_with($linha, '--')) continue;

                $buffer .= ' ' . $linha;

                if (str_ends_with(rtrim($linha), ';')) {
                    $sql = trim($buffer);
                    $buffer = '';
                    if ($sql === ';' || $sql === '') continue;

                    try {
                        $db->exec($sql);
                        // Contar inserções
                        foreach (array_keys($inseridos) as $t) {
                            if (stripos($sql, "INSERT INTO $t") !== false) {
                                $inseridos[$t]++;
                            }
                        }
                    } catch (\Exception $e) {
                        // Ignorar erros de duplicidade, continuar
                        if (!str_contains($e->getMessage(), 'Duplicate')) {
                            // Log mas não para
                        }
                    }
                }
            }

            $db->exec("SET FOREIGN_KEY_CHECKS = 1");

            $resumo = "Importação concluída! " .
                "Clientes: {$inseridos['clientes']} | " .
                "OS: {$inseridos['ordens_servico']} | " .
                "Equipamentos: {$inseridos['equipamentos']} | " .
                "Serviços: {$inseridos['os_servicos']} | " .
                "Peças: {$inseridos['os_pecas']}";

            $this->flash('success', $resumo);

        } catch (\Exception $e) {
            $db->exec("SET FOREIGN_KEY_CHECKS = 1");
            $this->flash('error', 'Erro na importação: ' . $e->getMessage());
        }

        $this->redirect(url('/empresa/migracao-shoficina'));
    }

    public function perfilPublico(): void
    {
        $eid = $this->empresaId();
        $db  = DB::pdo();
        $stmt = $db->prepare("SELECT * FROM empresas WHERE id = ?");
        $stmt->execute([$eid]);
        $empresa = $stmt->fetch();

        $planoCompleto = perfil_diretorio_completo($empresa);

        // Avaliações do diretório (contestadas primeiro, verificadas antes)
        $av = $db->prepare("SELECT * FROM diretorio_avaliacoes WHERE empresa_id = ? ORDER BY (situacao='contestada') DESC, verificada DESC, criado_em DESC");
        $av->execute([$eid]);
        $avaliacoes = $av->fetchAll();

        // Galeria de fotos
        $fq = $db->prepare("SELECT * FROM empresa_fotos WHERE empresa_id = ? ORDER BY principal DESC, ordem, id");
        $fq->execute([$eid]);
        $fotos = $fq->fetchAll();

        $servicos = [];
        $visitas  = null;
        if ($planoCompleto) {
            $stmtServ = $db->prepare("SELECT * FROM empresa_servicos WHERE empresa_id = ? ORDER BY ordem, nome");
            $stmtServ->execute([$eid]);
            $servicos = $stmtServ->fetchAll();

            // Visitas do diretório (últimos 30 dias + total)
            $sv = $db->prepare("SELECT dia, total FROM diretorio_visitas WHERE empresa_id = ? AND dia >= CURDATE() - INTERVAL 29 DAY");
            $sv->execute([$eid]);
            $porDia = [];
            foreach ($sv->fetchAll() as $r) { $porDia[$r['dia']] = (int) $r['total']; }
            $visitas = ['total' => (int) ($empresa['visitas'] ?? 0), 'hoje' => 0, 'mes' => 0, 'labels' => [], 'dados' => []];
            for ($i = 29; $i >= 0; $i--) {
                $d = date('Y-m-d', strtotime("-$i day"));
                $qtd = $porDia[$d] ?? 0;
                $visitas['labels'][] = date('d/m', strtotime($d));
                $visitas['dados'][]  = $qtd;
                $visitas['mes'] += $qtd;
                if ($d === date('Y-m-d')) $visitas['hoje'] = $qtd;
            }
        }

        $this->view('empresa.perfil_publico', [
            'titulo' => 'Perfil Público', 'empresa' => $empresa,
            'avaliacoes' => $avaliacoes, 'fotos' => $fotos,
            'planoCompleto' => $planoCompleto,
            'servicos' => $servicos, 'visitas' => $visitas,
        ]);
    }

    /** Empresa responde publicamente a uma avaliação do seu perfil. */
    public function responderAvaliacao(string $id): void
    {
        $back = url('/empresa/perfil-publico') . '#avaliacoes';
        if (!csrf_verify()) { $this->flash('error', 'Token inválido.'); $this->redirect($back); }
        $eid = $this->empresaId();
        $db  = DB::pdo();
        $resposta = trim($this->post('resposta', ''));

        $st = $db->prepare("SELECT id FROM diretorio_avaliacoes WHERE id = ? AND empresa_id = ?");
        $st->execute([(int)$id, $eid]);
        if (!$st->fetch()) { $this->flash('error', 'Avaliação não encontrada.'); $this->redirect($back); }

        if ($resposta === '') {
            $db->prepare("UPDATE diretorio_avaliacoes SET resposta=NULL, resposta_em=NULL WHERE id=? AND empresa_id=?")->execute([(int)$id, $eid]);
            $this->flash('success', 'Resposta removida.');
        } else {
            $db->prepare("UPDATE diretorio_avaliacoes SET resposta=?, resposta_em=NOW() WHERE id=? AND empresa_id=?")->execute([mb_substr($resposta, 0, 1000), (int)$id, $eid]);
            $this->flash('success', 'Resposta publicada. 👍');
        }
        $this->redirect($back);
    }

    /** Empresa contesta uma avaliação injusta: some do público e vai à moderação master. */
    public function contestarAvaliacao(string $id): void
    {
        $back = url('/empresa/perfil-publico') . '#avaliacoes';
        if (!csrf_verify()) { $this->flash('error', 'Token inválido.'); $this->redirect($back); }
        $eid    = $this->empresaId();
        $db     = DB::pdo();
        $motivo = trim($this->post('motivo', ''));

        $st = $db->prepare("SELECT situacao FROM diretorio_avaliacoes WHERE id = ? AND empresa_id = ?");
        $st->execute([(int)$id, $eid]);
        $av = $st->fetch();
        if (!$av) { $this->flash('error', 'Avaliação não encontrada.'); $this->redirect($back); }
        if ($av['situacao'] === 'contestada') { $this->flash('error', 'Esta avaliação já está em análise.'); $this->redirect($back); }
        if ($motivo === '') { $this->flash('error', 'Explique o motivo da contestação.'); $this->redirect($back); }

        $db->prepare("UPDATE diretorio_avaliacoes SET situacao='contestada', contestacao_motivo=? WHERE id=? AND empresa_id=?")
           ->execute([mb_substr($motivo, 0, 1000), (int)$id, $eid]);
        $this->flash('success', 'Avaliação enviada para análise. Ela fica oculta do público até a moderação decidir.');
        $this->redirect($back);
    }

    /** Ativa o destaque no diretório pra empresa, de graça — sem InfinitePay, sem aprovação do master. */
    public function ativarDestaqueGratis(): void
    {
        $back = url('/empresa/perfil-publico');
        if (!csrf_verify()) { $this->flash('error', 'Token inválido.'); $this->redirect($back); }
        $eid = $this->empresaId();
        DB::pdo()->prepare("UPDATE empresas SET diretorio_destaque='basico', diretorio_destaque_ate=NULL WHERE id=?")
                  ->execute([$eid]);
        $this->flash('success', 'Destaque ativado! Sua empresa já aparece no topo das buscas do diretório. 🎉');
        $this->redirect($back);
    }

    public function salvarPerfilPublico(): void
    {
        if (!csrf_verify()) { $this->flash('error', 'Token inválido.'); $this->redirect(url('/empresa/perfil-publico')); }

        $eid = $this->empresaId();
        $db  = DB::pdo();

        $atual = $db->prepare("SELECT cidade, uf, slug, licenca_ate FROM empresas WHERE id = ?");
        $atual->execute([$eid]);
        $atual = $atual->fetch() ?: [];
        $planoCompleto = perfil_diretorio_completo($atual);

        // Cidade/UF só ficam editáveis aqui p/ quem tem plano completo; sem plano, usa o que já
        // está salvo (também editável em Configurações → Empresa) — só pra montar o slug.
        $cidade = $planoCompleto ? trim($this->post('cidade', '')) : trim($atual['cidade'] ?? '');
        $uf     = $planoCompleto ? strtoupper(substr(trim($this->post('uf', '')), 0, 2)) : ($atual['uf'] ?? '');

        $nome = trim($this->post('nome_fantasia', ''));

        // Slug a partir de nome + cidade (só quando há nome). Usa mapa manual de acentos
        // em vez de iconv//TRANSLIT (que pode falhar silenciosamente conforme locale do servidor).
        $slug = null;
        if ($nome !== '') {
            $mapaAcentos = ['á'=>'a','à'=>'a','ã'=>'a','â'=>'a','ä'=>'a','é'=>'e','è'=>'e','ê'=>'e','ë'=>'e',
                'í'=>'i','ì'=>'i','î'=>'i','ï'=>'i','ó'=>'o','ò'=>'o','ô'=>'o','õ'=>'o','ö'=>'o',
                'ú'=>'u','ù'=>'u','û'=>'u','ü'=>'u','ç'=>'c','ñ'=>'n',
                'Á'=>'a','À'=>'a','Ã'=>'a','Â'=>'a','Ä'=>'a','É'=>'e','È'=>'e','Ê'=>'e','Ë'=>'e',
                'Í'=>'i','Ì'=>'i','Î'=>'i','Ï'=>'i','Ó'=>'o','Ò'=>'o','Ô'=>'o','Õ'=>'o','Ö'=>'o',
                'Ú'=>'u','Ù'=>'u','Û'=>'u','Ü'=>'u','Ç'=>'c','Ñ'=>'n'];
            $rawSlug  = strtr($nome . '-' . $cidade, $mapaAcentos);
            $rawSlug  = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $rawSlug));
            $novoSlug = trim($rawSlug, '-');
            if ($novoSlug !== '') {
                $slug = $novoSlug;
                $stmtSl = $db->prepare("SELECT id FROM empresas WHERE slug = ? AND id != ?");
                $stmtSl->execute([$slug, $eid]);
                if ($stmtSl->fetch()) $slug .= '-' . $eid;
            }
        }
        // Nunca apaga um slug já existente com um vazio (mantém o antigo se o cálculo falhar).
        if ($slug === null) $slug = $atual['slug'] ?? null;

        // Não publica no diretório sem nome da empresa.
        $listar = (int)$this->post('listagem_publica', 0);
        if ($listar && $nome === '') {
            $listar = 0;
            $this->flash('warning', 'Informe o nome da empresa para aparecer no diretório.');
        }

        if ($planoCompleto) {
            $db->prepare("UPDATE empresas SET nome_fantasia=?, cidade=?, uf=?, descricao_publica=?, site_url=?, instagram=?, whatsapp_publico=?, email_publico=?, facebook=?, youtube=?, tiktok=?, listagem_publica=?, especialidades=?, horario_funcionamento=?, slug=? WHERE id=?")
               ->execute([
                   ($nome ?: null),
                   ($cidade ?: null),
                   ($uf ?: null),
                   $this->post('descricao_publica', ''),
                   $this->post('site_url', ''),
                   $this->post('instagram', ''),
                   only_numbers($this->post('whatsapp_publico', '')),
                   trim($this->post('email_publico', '')) ?: null,
                   trim($this->post('facebook', '')) ?: null,
                   trim($this->post('youtube', '')) ?: null,
                   trim($this->post('tiktok', '')) ?: null,
                   $listar,
                   $this->post('especialidades', ''),
                   trim($this->post('horario_funcionamento', '')) ?: null,
                   $slug,
                   $eid,
               ]);

            // Serviços: apagar e reinserir (só quem tem plano completo edita essa lista).
            $db->prepare("DELETE FROM empresa_servicos WHERE empresa_id=?")->execute([$eid]);
            $servNomes  = $_POST['serv_nome']  ?? [];
            $servIcones = $_POST['serv_icone'] ?? [];
            $stmtS = $db->prepare("INSERT INTO empresa_servicos (empresa_id, nome, icone, ordem) VALUES (?,?,?,?)");
            foreach ($servNomes as $i => $sn) {
                if (trim($sn)) $stmtS->execute([$eid, trim($sn), $servIcones[$i] ?? 'bi-tools', $i]);
            }

            // Upload foto capa — nome SEO-friendly + conversão para WebP
            if (!empty($_FILES['foto_capa']['tmp_name'])) {
                $dir  = BASE_PATH . '/storage/uploads/';
                $tmp  = $_FILES['foto_capa']['tmp_name'];
                $mime = mime_content_type($tmp);

                $mapa = ['á'=>'a','à'=>'a','ã'=>'a','â'=>'a','é'=>'e','ê'=>'e','í'=>'i','ó'=>'o','ô'=>'o','õ'=>'o','ú'=>'u','ç'=>'c','Á'=>'a','Ã'=>'a','Ç'=>'c','É'=>'e','Ó'=>'o'];
                $nomeSlug = strtr($nome ?: 'empresa', $mapa);
                $nomeSlug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $nomeSlug));
                $nomeSlug = trim($nomeSlug, '-');

                $convertido = false;
                if (function_exists('imagewebp') && in_array($mime, ['image/jpeg','image/png','image/gif','image/webp'])) {
                    $src = match($mime) {
                        'image/jpeg' => @imagecreatefromjpeg($tmp),
                        'image/png'  => @imagecreatefrompng($tmp),
                        'image/gif'  => @imagecreatefromgif($tmp),
                        'image/webp' => @imagecreatefromwebp($tmp),
                        default      => null,
                    };
                    if ($src) {
                        $fn = 'capa-' . $nomeSlug . '-' . $eid . '.webp';
                        if (imagewebp($src, $dir . $fn, 85)) {
                            imagedestroy($src);
                            $convertido = true;
                            $db->prepare("UPDATE empresas SET foto_capa=? WHERE id=?")->execute([$fn, $eid]);
                        }
                    }
                }
                if (!$convertido) {
                    $ext = strtolower(pathinfo($_FILES['foto_capa']['name'], PATHINFO_EXTENSION));
                    $fn  = 'capa-' . $nomeSlug . '-' . $eid . '.' . $ext;
                    if (move_uploaded_file($tmp, $dir . $fn)) {
                        $db->prepare("UPDATE empresas SET foto_capa=? WHERE id=?")->execute([$fn, $eid]);
                    }
                }
            }
        } else {
            $db->prepare("UPDATE empresas SET nome_fantasia=?, descricao_publica=?, whatsapp_publico=?, horario_funcionamento=?, listagem_publica=?, slug=? WHERE id=?")
               ->execute([
                   ($nome ?: null),
                   $this->post('descricao_publica', ''),
                   only_numbers($this->post('whatsapp_publico', '')),
                   trim($this->post('horario_funcionamento', '')) ?: null,
                   $listar,
                   $slug,
                   $eid,
               ]);
        }

        // Upload da logo (reaproveita a mesma logo usada em todo o sistema — impressão, etc).
        if (!empty($_FILES['logo']['tmp_name'])) {
            $logoPath = $this->processarLogo($_FILES['logo'], $eid);
            if ($logoPath === false) {
                $this->flash('error', 'Perfil salvo, mas a logo não foi enviada. Use JPG, PNG, SVG ou WebP até 2MB.');
                $this->redirect(url('/empresa/perfil-publico'));
            }
            $db->prepare("UPDATE empresas SET logo=? WHERE id=?")->execute([$logoPath, $eid]);
        }

        $this->flash('success', 'Perfil público atualizado! URL: /assistencias/' . $slug);
        $this->redirect(url('/empresa/perfil-publico'));
    }

    // ── Galeria de fotos (perfil reivindicado) ───────────────────────────
    public function uploadFoto(): void
    {
        $back = url('/empresa/perfil-publico') . '#fotos';
        if (!csrf_verify()) { $this->flash('error', 'Token inválido.'); $this->redirect($back); }
        $eid = $this->empresaId();
        $db  = DB::pdo();

        if (empty($_FILES['foto']['tmp_name'])) { $this->flash('error', 'Selecione uma imagem.'); $this->redirect($back); }

        $c = $db->prepare("SELECT COUNT(*) FROM empresa_fotos WHERE empresa_id = ?");
        $c->execute([$eid]);
        $qtd = (int)$c->fetchColumn();
        if ($qtd >= 4) { $this->flash('error', 'Você já tem 4 fotos. Remova uma para adicionar outra.'); $this->redirect($back); }

        $fn = $this->processarFoto($_FILES['foto'], $eid);
        if ($fn === false) { $this->flash('error', 'Erro no upload. Use JPG, PNG ou WebP até 4MB.'); $this->redirect($back); }

        $principal = $qtd === 0 ? 1 : 0; // a primeira já entra como principal
        $db->prepare("INSERT INTO empresa_fotos (empresa_id, arquivo, principal, ordem) VALUES (?,?,?,?)")
           ->execute([$eid, $fn, $principal, $qtd]);
        $this->flash('success', 'Foto adicionada! 📸');
        $this->redirect($back);
    }

    public function removerFoto(string $id): void
    {
        $back = url('/empresa/perfil-publico') . '#fotos';
        if (!csrf_verify()) { $this->flash('error', 'Token inválido.'); $this->redirect($back); }
        $eid = $this->empresaId();
        $db  = DB::pdo();

        $st = $db->prepare("SELECT arquivo, principal FROM empresa_fotos WHERE id = ? AND empresa_id = ?");
        $st->execute([(int)$id, $eid]);
        $foto = $st->fetch();
        if (!$foto) { $this->flash('error', 'Foto não encontrada.'); $this->redirect($back); }

        @unlink(BASE_PATH . '/storage/uploads/fotos/' . basename($foto['arquivo']));
        $db->prepare("DELETE FROM empresa_fotos WHERE id = ? AND empresa_id = ?")->execute([(int)$id, $eid]);

        if ($foto['principal']) { // promove a próxima a principal
            $db->prepare("UPDATE empresa_fotos SET principal = 1 WHERE empresa_id = ? ORDER BY ordem, id LIMIT 1")->execute([$eid]);
        }
        $this->flash('success', 'Foto removida.');
        $this->redirect($back);
    }

    public function fotoPrincipal(string $id): void
    {
        $back = url('/empresa/perfil-publico') . '#fotos';
        if (!csrf_verify()) { $this->flash('error', 'Token inválido.'); $this->redirect($back); }
        $eid = $this->empresaId();
        $db  = DB::pdo();

        $st = $db->prepare("SELECT id FROM empresa_fotos WHERE id = ? AND empresa_id = ?");
        $st->execute([(int)$id, $eid]);
        if (!$st->fetch()) { $this->flash('error', 'Foto não encontrada.'); $this->redirect($back); }

        $db->prepare("UPDATE empresa_fotos SET principal = 0 WHERE empresa_id = ?")->execute([$eid]);
        $db->prepare("UPDATE empresa_fotos SET principal = 1 WHERE id = ? AND empresa_id = ?")->execute([(int)$id, $eid]);
        $this->flash('success', 'Foto principal definida.');
        $this->redirect($back);
    }

    private function processarFoto(array $file, int $eid): string|false
    {
        $permit = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/bmp'];
        $finfo  = finfo_open(FILEINFO_MIME_TYPE);
        $mime   = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        if (!in_array($mime, $permit)) return false;
        if ($file['size'] > 8 * 1024 * 1024) return false;

        $dir = BASE_PATH . '/storage/uploads/fotos/';
        if (!is_dir($dir)) @mkdir($dir, 0775, true);
        $fn  = 'foto-' . $eid . '-' . time() . '-' . random_int(1000, 9999) . '.webp';
        $tmp = $file['tmp_name'];

        // Padroniza em 800×800 WebP (mesmo tratamento do marketplace: contém sobre fundo branco)
        $src = match($mime) {
            'image/jpeg' => @imagecreatefromjpeg($tmp),
            'image/png'  => @imagecreatefrompng($tmp),
            'image/webp' => @imagecreatefromwebp($tmp),
            'image/gif'  => @imagecreatefromgif($tmp),
            'image/bmp'  => @imagecreatefrombmp($tmp),
            default      => null,
        };
        if (!$src) return false;

        $ow = imagesx($src);
        $oh = imagesy($src);

        $canvas = imagecreatetruecolor(800, 800);
        $branco = imagecolorallocate($canvas, 255, 255, 255);
        imagefill($canvas, 0, 0, $branco);
        imagealphablending($src, false);
        imagesavealpha($src, true);

        $ratio = min(800 / $ow, 800 / $oh);
        $nw = (int)($ow * $ratio);
        $nh = (int)($oh * $ratio);
        $ox = (int)((800 - $nw) / 2);
        $oy = (int)((800 - $nh) / 2);
        imagecopyresampled($canvas, $src, $ox, $oy, 0, 0, $nw, $nh, $ow, $oh);

        $ok = imagewebp($canvas, $dir . $fn, 87);
        imagedestroy($src);
        imagedestroy($canvas);
        return $ok ? $fn : false;
    }

    private function processarLogo(array $file, int $eid): string|false
    {
        $tiposPermitidos = ['image/jpeg', 'image/png', 'image/gif', 'image/svg+xml', 'image/webp'];
        $extMap = [
            'image/jpeg'   => 'jpg',
            'image/png'    => 'png',
            'image/gif'    => 'gif',
            'image/svg+xml'=> 'svg',
            'image/webp'   => 'webp',
        ];

        // Validar tipo
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime, $tiposPermitidos)) return false;

        // Validar tamanho (2MB)
        if ($file['size'] > 2 * 1024 * 1024) return false;

        $ext     = $extMap[$mime] ?? 'jpg';
        $dir     = BASE_PATH . '/storage/uploads/logos/';
        $nomeArq = 'empresa_' . $eid . '_' . time() . '.' . $ext;
        $destino = $dir . $nomeArq;

        // Remover logo antiga
        $db = DB::pdo();
        $stmt = $db->prepare("SELECT logo FROM empresas WHERE id = ?");
        $stmt->execute([$eid]);
        $logoAntiga = $stmt->fetchColumn();
        if ($logoAntiga) {
            $arquivoAntigo = $dir . basename($logoAntiga);
            if (file_exists($arquivoAntigo)) @unlink($arquivoAntigo);
        }

        if (!move_uploaded_file($file['tmp_name'], $destino)) return false;

        // Redimensionar se for imagem raster e extensão PHP disponível
        if (in_array($mime, ['image/jpeg','image/png','image/gif','image/webp']) && function_exists('imagecreatefromjpeg')) {
            $this->redimensionar($destino, $mime, 400, 200);

            // Comprimir pra WebP (exceto se já for webp)
            if ($mime !== 'image/webp') {
                $nomeWebp = 'empresa_' . $eid . '_' . time() . '.webp';
                $destinoWebp = $dir . $nomeWebp;
                if (ImageService::paraWebp($destino, $destinoWebp, 87)) {
                    @unlink($destino);
                    $nomeArq = $nomeWebp;
                }
            }
        }

        return $nomeArq;
    }

    private function redimensionar(string $path, string $mime, int $maxW, int $maxH): void
    {
        $src = match($mime) {
            'image/jpeg' => @imagecreatefromjpeg($path),
            'image/png'  => @imagecreatefrompng($path),
            'image/gif'  => @imagecreatefromgif($path),
            'image/webp' => @imagecreatefromwebp($path),
            default      => null,
        };
        if (!$src) return;

        $w = imagesx($src);
        $h = imagesy($src);
        if ($w <= $maxW && $h <= $maxH) { imagedestroy($src); return; }

        $ratio  = min($maxW / $w, $maxH / $h);
        $nw     = (int)($w * $ratio);
        $nh     = (int)($h * $ratio);
        $dst    = imagecreatetruecolor($nw, $nh);

        // Preservar transparência PNG/GIF
        if (in_array($mime, ['image/png','image/gif'])) {
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            $trans = imagecolorallocatealpha($dst, 0, 0, 0, 127);
            imagefilledrectangle($dst, 0, 0, $nw, $nh, $trans);
        }

        imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);

        match($mime) {
            'image/jpeg' => imagejpeg($dst, $path, 90),
            'image/png'  => imagepng($dst, $path),
            'image/gif'  => imagegif($dst, $path),
            'image/webp' => imagewebp($dst, $path, 90),
            default      => null,
        };

        imagedestroy($src);
        imagedestroy($dst);
    }

    // ───────────── WhatsApp da empresa (conexão própria, envia do número da loja) ─────────────
    public function whatsapp(): void
    {
        if (!\App\Core\Auth::isAdmin()) {
            $this->flash('error', 'Apenas o administrador pode conectar o WhatsApp da empresa.');
            $this->redirect(url('/dashboard'));
        }
        $eid    = $this->empresaId();
        $estado = \App\Services\WhatsAppService::statusEmpresa($eid);
        $qr     = $estado === 'open' ? null : \App\Services\WhatsAppService::qrEmpresa($eid);
        $numero = $estado === 'open' ? \App\Services\WhatsAppService::numeroEmpresa($eid) : null;
        $this->view('empresa.whatsapp', [
            'titulo' => 'WhatsApp da Empresa',
            'estado' => $estado,
            'qr'     => $qr,
            'numero' => $numero,
        ]);
    }

    /** Polling de status (JSON) — usado pela tela de conexão. */
    public function whatsappStatus(): void
    {
        $eid = $this->empresaId();
        if (session_status() === PHP_SESSION_ACTIVE) { session_write_close(); }
        $estado = \App\Services\WhatsAppService::statusEmpresa($eid);
        $this->json([
            'estado' => $estado,
            'numero' => $estado === 'open' ? \App\Services\WhatsAppService::numeroEmpresa($eid) : null,
        ]);
    }

    public function whatsappDesconectar(): void
    {
        if (!csrf_verify()) { $this->json(['ok' => false, 'erro' => 'Token inválido.'], 403); }
        if (!\App\Core\Auth::isAdmin()) { $this->json(['ok' => false, 'erro' => 'Apenas administrador.'], 403); }
        $ok = \App\Services\WhatsAppService::desconectarEmpresa($this->empresaId());
        $this->json(['ok' => $ok]);
    }

    /** Log de ações dos usuários (auditoria) — só admin. */
    public function logs(): void
    {
        if (!\App\Core\Auth::isAdmin()) {
            $this->flash('error', 'Apenas o administrador pode ver o log de ações.');
            $this->redirect(url('/empresa'));
        }
        $eid = $this->empresaId();
        $db  = DB::pdo();

        $fUser = (int) ($_GET['usuario'] ?? 0);
        $fMod  = trim($_GET['modulo'] ?? '');

        $where = ['l.empresa_id = ?']; $params = [$eid];
        if ($fUser) { $where[] = 'l.usuario_id = ?'; $params[] = $fUser; }
        if ($fMod !== '') { $where[] = 'l.modulo = ?'; $params[] = $fMod; }
        $w = implode(' AND ', $where);

        $st = $db->prepare(
            "SELECT l.*, u.nome AS usuario_nome
             FROM log_acoes l LEFT JOIN usuarios u ON u.id = l.usuario_id
             WHERE $w ORDER BY l.criado_em DESC LIMIT 300"
        );
        $st->execute($params);
        $logs = $st->fetchAll();

        $stU = $db->prepare("SELECT id, nome FROM usuarios WHERE empresa_id = ? ORDER BY nome");
        $stU->execute([$eid]);
        $stM = $db->prepare("SELECT DISTINCT modulo FROM log_acoes WHERE empresa_id = ? ORDER BY modulo");
        $stM->execute([$eid]);

        $this->view('empresa.logs', [
            'titulo'   => 'Log de Ações',
            'logs'     => $logs,
            'usuarios' => $stU->fetchAll(),
            'modulos'  => $stM->fetchAll(\PDO::FETCH_COLUMN),
            'fUser'    => $fUser,
            'fMod'     => $fMod,
        ]);
    }

    /** Página de planos e assinatura. */
    public function planos(): void
    {
        $cfg = require BASE_PATH . '/config/planos.php';
        $st  = DB::pdo()->prepare("SELECT plano_atual, licenca_ate, trial_ate, tipo_conta, creditos_os, creditos_scan_equip, creditos_scan_placa FROM empresas WHERE id = ?");
        $st->execute([$this->empresaId()]);
        $emp = $st->fetch() ?: [];
        $this->view('empresa.planos', [
            'titulo'           => 'Planos e Assinatura',
            'planos'           => $cfg['planos'],
            'ciclos'           => $cfg['ciclos'],
            'credito'          => $cfg['credito_os'] ?? null,
            'creditoScanEquip' => $cfg['credito_scan_equip'] ?? null,
            'creditoScanPlaca' => $cfg['credito_scan_placa'] ?? null,
            'emp'              => $emp,
            'usoScanEquip'     => scan_uso_mes($this->empresaId(), 'equipamento'),
            'usoScanPlaca'     => scan_uso_mes($this->empresaId(), 'placa'),
            'planoAtualCfg'    => plano_da_empresa($emp),
            'pagamentoAtivo'   => \App\Services\InfinitePayService::ativo(),
        ]);
    }
}
