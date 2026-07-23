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

        $modo = in_array($this->post('modo', ''), ['equipamento', 'placa'], true) ? $this->post('modo', '') : 'equipamento';
        $db->prepare(
            "INSERT INTO scanner_sessoes (token, codigo, empresa_id, usuario_id, modo, status, expira_em)
             VALUES (?, ?, ?, ?, ?, 'aguardando', DATE_ADD(NOW(), INTERVAL " . self::EXPIRA_MIN . " MINUTE))"
        )->execute([$token, $codigo, $this->empresaId(), $this->usuarioId(), $modo]);

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

    /** Celular: página de captura da etiqueta. */
    public function pagina(string $token): void
    {
        $sess = $this->sessaoPublica($token);
        if (!$sess) {
            $this->view('scanner.expirado', ['titulo' => 'Sessão expirada'], 'scanner');
            return;
        }
        $placa = ($sess['modo'] ?? '') === 'placa';
        $this->view('scanner.pagina', [
            'titulo' => $placa ? 'Escanear placa' : 'Escanear etiqueta',
            'token'  => $token,
            'modo'   => $sess['modo'] ?? 'equipamento',
        ], 'scanner');
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
