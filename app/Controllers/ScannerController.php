<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\DB;
use App\Services\VisionService;

/**
 * Pareamento "celular como scanner do PC".
 * O PC cria uma sessão (QR/código), o celular abre a página, bate a foto e/ou digita,
 * envia, e o PC lê o resultado por polling e preenche o formulário da OS.
 * Sem WebSockets: polling simples, na stack atual (PHP/MySQL).
 */
class ScannerController extends Controller
{
    private const EXPIRA_MIN = 20;

    /** PC: cria a sessão de pareamento e devolve token + QR + código. */
    public function nova(): void
    {
        $db = DB::pdo();
        $db->prepare("DELETE FROM scanner_sessoes WHERE expira_em < NOW()")->execute();

        $token  = bin2hex(random_bytes(16));
        $codigo = '';
        $alfa   = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        for ($i = 0; $i < 6; $i++) $codigo .= $alfa[random_int(0, strlen($alfa) - 1)];

        $modo = in_array($this->post('modo', ''), ['equipamento', 'placa', 'fotos_whatsapp'], true) ? $this->post('modo', '') : 'equipamento';
        $clienteId  = (int) $this->post('cliente_id', 0) ?: null;
        $equipTexto = trim((string) $this->post('equipamento', '')) ?: null;
        $db->prepare(
            "INSERT INTO scanner_sessoes (token, codigo, empresa_id, usuario_id, modo, cliente_id, equipamento_texto, status, expira_em)
             VALUES (?, ?, ?, ?, ?, ?, ?, 'aguardando', DATE_ADD(NOW(), INTERVAL " . self::EXPIRA_MIN . " MINUTE))"
        )->execute([$token, $codigo, $this->empresaId(), $this->usuarioId(), $modo, $clienteId, $equipTexto]);

        $this->json([
            'token'  => $token,
            'codigo' => $codigo,
            'url'    => url('/scan/' . $token),
            'qr'     => url('/scanner/qr?token=' . $token),
        ]);
    }

    /** PC: QR (SVG) apontando para a página do celular. */
    public function qr(): void
    {
        $token = (string) $this->get('token', '');
        if (!$this->sessao($token)) { http_response_code(404); exit; }

        $url = url('/scan/' . $token);
        $svg = shell_exec('qrencode -t SVG -m 1 -o - ' . escapeshellarg($url));
        if (!$svg) { http_response_code(500); exit; }

        header('Content-Type: image/svg+xml; charset=utf-8');
        header('Cache-Control: no-store');
        echo $svg;
        exit;
    }

    /** PC: polling do status da sessão. */
    public function status(): void
    {
        $sess = $this->sessao((string) $this->get('token', ''));
        if (!$sess) { $this->json(['status' => 'expirado'], 410); }

        $this->json([
            'status'    => $sess['status'],
            'resultado' => $sess['resultado'] ? json_decode($sess['resultado'], true) : null,
            'erro'      => $sess['erro_msg'],
        ]);
    }

    /** Celular: parear digitando o código (fallback quando a câmera não lê o QR). */
    public function entrada(): void
    {
        $erro   = null;
        $codigo = strtoupper(trim((string) $this->get('codigo', '')));
        if ($codigo !== '') {
            $st = DB::pdo()->prepare(
                "SELECT token FROM scanner_sessoes
                 WHERE codigo = ? AND expira_em > NOW()
                 ORDER BY id DESC LIMIT 1"
            );
            $st->execute([$codigo]);
            $tok = $st->fetchColumn();
            if ($tok) { $this->redirect(url('/scan/' . $tok)); }
            $erro = 'Código não encontrado ou expirado. Confira na tela do computador.';
        }
        $this->view('scanner.entrada', ['titulo' => 'Parear celular', 'erro' => $erro], 'scanner');
    }

    /** Celular: página de captura da etiqueta (ou das fotos do equipamento, no modo fotos_whatsapp). */
    public function pagina(string $token): void
    {
        $sess = $this->sessaoPublica($token);
        if (!$sess) {
            $this->view('scanner.expirado', ['titulo' => 'Sessão expirada'], 'scanner');
            return;
        }
        $modo = $sess['modo'] ?? 'equipamento';

        if ($modo === 'fotos_whatsapp') {
            $this->view('scanner.fotos_whatsapp', [
                'titulo' => 'Fotografar equipamento',
                'token'  => $token,
            ], 'scanner');
            return;
        }

        $placa = $modo === 'placa';
        $this->view('scanner.pagina', [
            'titulo' => $placa ? 'Escanear placa' : 'Escanear etiqueta',
            'token'  => $token,
            'modo'   => $modo,
        ], 'scanner');
    }

    /** Celular: recebe as fotos do equipamento (base64, em memória) e manda direto pro WhatsApp — nada é gravado em disco. */
    public function enviarFotosWhatsapp(string $token): void
    {
        $sess = $this->sessaoPublica($token);
        if (!$sess) { $this->json(['ok' => false, 'erro' => 'Sessão expirada. Gere um novo QR no computador.'], 410); }
        if (($sess['modo'] ?? '') !== 'fotos_whatsapp') { $this->json(['ok' => false, 'erro' => 'Sessão inválida.'], 400); }

        $eid   = (int) $sess['empresa_id'];
        $fotos = $this->post('fotos', []);
        if (!is_array($fotos) || !$fotos) { $this->json(['ok' => false, 'erro' => 'Nenhuma foto recebida.'], 400); }
        $fotos = array_slice($fotos, 0, 10);

        $imagens = [];
        foreach ($fotos as $durl) {
            if (!is_string($durl) || !preg_match('~^data:image/(jpe?g|png|webp);base64,~', $durl)) continue;
            $b64 = substr($durl, strpos($durl, ',') + 1);
            $bin = base64_decode($b64, true);
            if ($bin === false || strlen($bin) < 100 || strlen($bin) > 4_000_000) continue;
            $imagens[] = $b64;
        }
        if (!$imagens) { $this->json(['ok' => false, 'erro' => 'Fotos inválidas.'], 400); }

        $legenda = 'Estado de entrada' . (!empty($sess['equipamento_texto']) ? ' — ' . $sess['equipamento_texto'] : '');
        $clienteId = !empty($sess['cliente_id']) ? (int) $sess['cliente_id'] : null;
        $r = \App\Services\WhatsAppService::enviarFotosParaEmpresaECliente($eid, $clienteId, $imagens, $legenda);

        // Descarta os binários explicitamente (nada persistido)
        unset($imagens, $fotos);

        $db = DB::pdo();
        if ($r['ok']) {
            $db->prepare("UPDATE scanner_sessoes SET status='pronto', resultado=? WHERE token=? AND empresa_id=?")
               ->execute([json_encode(['enviado' => true], JSON_UNESCAPED_UNICODE), $token, $eid]);
            $this->json(['ok' => true]);
        } else {
            $db->prepare("UPDATE scanner_sessoes SET status='erro', erro_msg=? WHERE token=? AND empresa_id=?")
               ->execute([$r['erro'], $token, $eid]);
            $this->json(['ok' => false, 'erro' => $r['erro']]);
        }
    }

    /** Celular: recebe foto e/ou campos, guarda e marca a sessão como pronta. */
    public function receber(string $token): void
    {
        // Sem login: o token do QR (aleatório, 20 min, escopo de 1 empresa) é a própria autorização.
        $sess = $this->sessaoPublica($token);
        if (!$sess) { $this->json(['ok' => false, 'erro' => 'Sessão expirada. Gere um novo QR no computador.'], 410); }

        $eid = (int) $sess['empresa_id'];

        $fotoPath = null;
        if (!empty($_FILES['foto']['tmp_name']) && is_uploaded_file($_FILES['foto']['tmp_name'])) {
            $fotoPath = $this->salvarFoto($_FILES['foto'], $eid);
        }

        // ── MODO PLACA (marketplace): lê o part number e busca compatibilidade no Brasil ──
        if (($sess['modo'] ?? '') === 'placa') {
            DB::pdo()->prepare("UPDATE scanner_sessoes SET status='processando' WHERE token=? AND empresa_id=?")
                ->execute([$token, $eid]);

            $codManual = trim((string) $this->post('codigo', ''));
            $dados = null;
            if ($codManual !== '') {
                $dados = \App\Services\PecaService::porCodigo($codManual);
            } elseif ($fotoPath) {
                $verif = scan_ia_verificar($eid, 'placa');
                if (!$verif['liberado']) {
                    DB::pdo()->prepare("UPDATE scanner_sessoes SET status='erro', erro_msg=?, foto_path=? WHERE token=? AND empresa_id=?")
                        ->execute([$verif['mensagem'], $fotoPath, $token, $eid]);
                    $this->json(['ok' => false, 'erro' => $verif['mensagem'], 'limite' => true]);
                }
                DB::pdo()->prepare("UPDATE scanner_sessoes SET ia_usada=1 WHERE token=? AND empresa_id=?")->execute([$token, $eid]);
                $dados = \App\Services\PecaService::identificar(BASE_PATH . '/storage/uploads/' . $fotoPath);
            }

            if (!$dados || ($dados['codigo'] ?? '') === '') {
                DB::pdo()->prepare("UPDATE scanner_sessoes SET status='erro', erro_msg=?, foto_path=? WHERE token=? AND empresa_id=?")
                    ->execute(['Não consegui ler o código da placa. Tente uma foto mais nítida do part number ou digite o código.', $fotoPath, $token, $eid]);
                $this->json(['ok' => false, 'erro' => 'Não consegui ler o código da placa. Tente uma foto mais nítida ou digite o part number.']);
            }

            DB::pdo()->prepare("UPDATE scanner_sessoes SET status='pronto', resultado=?, foto_path=? WHERE token=? AND empresa_id=?")
                ->execute([json_encode($dados, JSON_UNESCAPED_UNICODE), $fotoPath, $token, $eid]);
            $this->json(['ok' => true]);
        }

        $marca  = trim((string) $this->post('marca', ''));
        $modelo = trim((string) $this->post('modelo', ''));
        $serie  = trim((string) $this->post('serie', ''));
        $tipo   = trim((string) $this->post('tipo', ''));

        // Ponto de encaixe da IA (base sem IA hoje): se nada foi digitado e há foto,
        // tenta ler a etiqueta. VisionService devolve null enquanto a chave não existir.
        $avisoLimite = null;
        if ($marca === '' && $modelo === '' && $serie === '' && $fotoPath) {
            $verif = scan_ia_verificar($eid, 'equipamento');
            if (!$verif['liberado']) {
                $avisoLimite = $verif['mensagem'];
            } else {
                DB::pdo()->prepare("UPDATE scanner_sessoes SET ia_usada=1 WHERE token=? AND empresa_id=?")->execute([$token, $eid]);
                $lido = VisionService::lerEtiqueta(BASE_PATH . '/storage/uploads/' . $fotoPath);
                if ($lido) {
                    $marca  = trim((string) ($lido['marca']  ?? ''));
                    $modelo = trim((string) ($lido['modelo'] ?? ''));
                    $serie  = trim((string) ($lido['serie']  ?? ''));
                    $tipo   = trim((string) ($lido['tipo']   ?? ''));
                }
            }
        }

        // Etiqueta não mostrou marca/tipo mas tem modelo: descobre na web e aprende (cache-first, custo travado).
        if ($modelo !== '' && ($marca === '' || $tipo === '')) {
            $desc = \App\Services\EquipService::descobrirPorModelo($modelo);
            if ($desc) {
                if ($marca === '' && !empty($desc['marca'])) $marca = $desc['marca'];
                if ($tipo  === '' && !empty($desc['tipo']))  $tipo  = $desc['tipo'];
            }
        }

        $resultado = json_encode(compact('marca', 'modelo', 'serie', 'tipo'), JSON_UNESCAPED_UNICODE);
        DB::pdo()->prepare(
            "UPDATE scanner_sessoes SET status='pronto', resultado=?, foto_path=?, erro_msg=? WHERE token=? AND empresa_id=?"
        )->execute([$resultado, $fotoPath, $avisoLimite, $token, $eid]);

        $this->json(['ok' => true]);
    }

    /**
     * Usuário já logado no próprio celular: fotografa a etiqueta direto (sem QR/pareamento,
     * pois o aparelho que está com a câmera é o mesmo que está preenchendo a OS).
     */
    public function lerDireto(): void
    {
        if (!csrf_verify()) { $this->json(['ok' => false, 'erro' => 'Token inválido. Recarregue a página.'], 403); }

        $eid = $this->empresaId();

        if (empty($_FILES['foto']['tmp_name']) || !is_uploaded_file($_FILES['foto']['tmp_name'])) {
            $this->json(['ok' => false, 'erro' => 'Nenhuma foto recebida.'], 400);
        }
        $fotoPath = $this->salvarFoto($_FILES['foto'], $eid);
        if (!$fotoPath) { $this->json(['ok' => false, 'erro' => 'Não consegui salvar a foto. Tente de novo.'], 400); }

        // Mesmo sem QR/pareamento, registra uma "sessão" para contar no limite mensal de
        // buscas de IA da empresa (scan_uso_mes conta por scanner_sessoes.ia_usada=1).
        $db     = DB::pdo();
        $token  = bin2hex(random_bytes(16));
        $codigo = strtoupper(bin2hex(random_bytes(3)));
        $db->prepare(
            "INSERT INTO scanner_sessoes (token, codigo, empresa_id, usuario_id, modo, status, foto_path, expira_em)
             VALUES (?, ?, ?, ?, 'equipamento', 'processando', ?, DATE_ADD(NOW(), INTERVAL 20 MINUTE))"
        )->execute([$token, $codigo, $eid, $this->usuarioId(), $fotoPath]);

        $verif = scan_ia_verificar($eid, 'equipamento');
        if (!$verif['liberado']) {
            $db->prepare("UPDATE scanner_sessoes SET status='erro', erro_msg=? WHERE token=?")->execute([$verif['mensagem'], $token]);
            $this->json(['ok' => false, 'erro' => $verif['mensagem'], 'limite' => true]);
        }
        $db->prepare("UPDATE scanner_sessoes SET ia_usada=1 WHERE token=?")->execute([$token]);

        $lido   = VisionService::lerEtiqueta(BASE_PATH . '/storage/uploads/' . $fotoPath);
        $marca  = trim((string) ($lido['marca']  ?? ''));
        $modelo = trim((string) ($lido['modelo'] ?? ''));
        $serie  = trim((string) ($lido['serie']  ?? ''));
        $tipo   = trim((string) ($lido['tipo']   ?? ''));

        if ($modelo === '' && $marca === '' && $serie === '' && $tipo === '') {
            $db->prepare("UPDATE scanner_sessoes SET status='erro', erro_msg=? WHERE token=?")
               ->execute(['Não consegui ler a etiqueta.', $token]);
            $this->json(['ok' => false, 'erro' => 'Não consegui ler a etiqueta. Tente uma foto mais nítida.']);
        }

        if ($modelo !== '' && ($marca === '' || $tipo === '')) {
            $desc = \App\Services\EquipService::descobrirPorModelo($modelo);
            if ($desc) {
                if ($marca === '' && !empty($desc['marca'])) $marca = $desc['marca'];
                if ($tipo  === '' && !empty($desc['tipo']))  $tipo  = $desc['tipo'];
            }
        }

        $db->prepare("UPDATE scanner_sessoes SET status='pronto', resultado=? WHERE token=?")
           ->execute([json_encode(compact('marca', 'modelo', 'serie', 'tipo'), JSON_UNESCAPED_UNICODE), $token]);

        $this->json(['ok' => true, 'marca' => $marca, 'modelo' => $modelo, 'serie' => $serie, 'tipo' => $tipo]);
    }

    // ---------- helpers ----------

    /** Sessão válida, não expirada e da MESMA empresa do usuário logado (multi-tenant). */
    private function sessao(string $token): ?array
    {
        if ($token === '') return null;
        $st = DB::pdo()->prepare(
            "SELECT * FROM scanner_sessoes WHERE token = ? AND empresa_id = ? AND expira_em > NOW() LIMIT 1"
        );
        $st->execute([$token, $this->empresaId()]);
        return $st->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    /** Sessão válida só pelo token do QR (sem exigir login) — usada nas páginas do celular. */
    private function sessaoPublica(string $token): ?array
    {
        if ($token === '') return null;
        $st = DB::pdo()->prepare(
            "SELECT * FROM scanner_sessoes WHERE token = ? AND expira_em > NOW() LIMIT 1"
        );
        $st->execute([$token]);
        return $st->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    private function salvarFoto(array $file, int $eid): ?string
    {
        $dir = BASE_PATH . '/storage/uploads/scanner';
        if (!is_dir($dir)) @mkdir($dir, 0775, true);

        $ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'heic', 'heif'], true)) $ext = 'jpg';

        $nome = 'scan_' . $eid . '_' . time() . '_' . bin2hex(random_bytes(3)) . '.' . $ext;
        if (move_uploaded_file($file['tmp_name'], $dir . '/' . $nome)) {
            return 'scanner/' . $nome;
        }
        return null;
    }
}
