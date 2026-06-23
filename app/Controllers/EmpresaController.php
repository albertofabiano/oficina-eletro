<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\DB;

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

        $this->view('empresa.index', [
            'titulo'  => 'Dados da Empresa',
            'empresa' => $empresa,
            'configs' => $configs,
        ]);
    }

    public function salvar(): void
    {
        if (!csrf_verify()) { $this->flash('error', 'Token inválido.'); $this->redirectBack(); }

        $eid = $this->empresaId();
        $db  = DB::pdo();

        // Upload de logo
        $logoPath = null;
        if (!empty($_FILES['logo']['tmp_name'])) {
            $logoPath = $this->processarLogo($_FILES['logo'], $eid);
            if ($logoPath === false) {
                $this->flash('error', 'Erro no upload da logo. Use JPG, PNG ou SVG até 2MB.');
                $this->redirect(url('/empresa'));
            }
        }

        $sql = "UPDATE empresas SET razao_social=?, nome_fantasia=?, cnpj=?, email=?, telefone=?, whatsapp=?,
                cep=?, logradouro=?, numero=?, complemento=?, bairro=?, cidade=?, uf=?" .
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
        ];

        if ($logoPath) $params[] = $logoPath;
        $params[] = $eid;

        $db->prepare($sql)->execute($params);

        // Configurações
        $configs = [
            'os_prefixo'                  => strtoupper(trim($this->post('os_prefixo', ''))),
            'os_digitos'                  => $this->post('os_digitos', '6'),
            'os_numero_inicial'           => $this->post('os_numero_inicial', '1'),
            'garantia_padrao_dias'        => $this->post('garantia_padrao_dias', '90'),
            'prazo_retirada_dias'         => $this->post('prazo_retirada_dias', '30'),
            'comissao_tecnico_percentual' => $this->post('comissao_tecnico_percentual', '0'),
            'texto_entrada_equipamento'   => $this->post('texto_entrada_equipamento', ''),
            'texto_garantia'              => $this->post('texto_garantia', ''),
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

        $this->flash('success', 'Dados salvos com sucesso!');
        $this->redirect(url('/empresa'));
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
        $this->redirect(url('/empresa'));
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
}
