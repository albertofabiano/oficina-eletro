<?php

namespace App\Services;

/**
 * Padroniza imagens para a web (SEO): remove o fundo (opcional, via rembg local),
 * coloca fundo branco, encaixa no tamanho padrão e salva em WebP.
 *
 * O rembg roda SOB DEMANDA e SERIALIZADO (flock: 1 por vez) pra proteger a RAM do VPS.
 */
class ImageService
{
    private const REMBG    = '/opt/rembg-venv/bin/rembg';
    private const MODELO   = 'u2netp';                          // modelo leve (menos RAM)
    private const MODELDIR = '/var/www/fixaos/storage/rembg';   // cache do modelo (U2NET_HOME)
    private const LOCK     = '/tmp/rembg.lock';
    private const TIMEOUT  = 90;                                // segundos

    /** rembg instalado e pronto? */
    public static function rembgDisponivel(): bool
    {
        return is_file(self::REMBG) && is_executable(self::REMBG);
    }

    /**
     * [remove fundo] → fundo branco → tamanho padrão → WebP.
     * @param array{tamanho?:int,removerFundo?:bool,qualidade?:int,fundo?:string} $opt
     */
    /**
     * @param array{tamanho?:int,largura?:int,altura?:int,removerFundo?:bool,qualidade?:int,fundo?:string} $opt
     *   'tamanho' define um canvas quadrado; 'largura'/'altura' (se informados) sobrepõem 'tamanho'
     *   e permitem um canvas retangular (ex: logo 900×300).
     */
    public static function padronizar(string $srcPath, string $destPath, array $opt = []): bool
    {
        $tamanho      = (int) ($opt['tamanho'] ?? 800);
        $largura      = (int) ($opt['largura'] ?? $tamanho);
        $altura       = (int) ($opt['altura']  ?? $tamanho);
        $qualidade    = (int) ($opt['qualidade'] ?? 82);
        $removerFundo = !empty($opt['removerFundo']);
        $fundo        = ($opt['fundo'] ?? 'branco') === 'transparente' ? 'transparente' : 'branco';

        if (!is_file($srcPath)) return false;

        // 1) opcional: remove o fundo → PNG transparente temporário.
        //    Reduz a imagem ANTES do rembg (saída é pequena mesmo) → rápido e leve de RAM.
        $trabalho = $srcPath;
        $tmpCut   = null;
        if ($removerFundo && self::rembgDisponivel()) {
            $entrada = self::redimensionarTemp($srcPath, min(1200, max($largura, $altura) + 100)) ?: $srcPath;
            $tmpCut  = sys_get_temp_dir() . '/cut_' . bin2hex(random_bytes(6)) . '.png';
            $okCut   = self::removerFundo($entrada, $tmpCut);
            if ($entrada !== $srcPath) @unlink($entrada);
            if ($okCut) {
                $trabalho = $tmpCut;
            } else {
                $tmpCut = null;              // falhou → segue com a original (só fundo branco/pad)
                $fundo  = 'branco';
            }
        }

        // 2) carrega
        $src = self::carregar($trabalho);
        if (!$src) { if ($tmpCut) @unlink($tmpCut); return false; }
        $ow = imagesx($src); $oh = imagesy($src);

        // 3) canvas (quadrado por padrão; retangular se largura/altura vierem diferentes)
        $canvas = imagecreatetruecolor($largura, $altura);
        if ($fundo === 'transparente') {
            imagealphablending($canvas, false);
            imagesavealpha($canvas, true);
            imagefilledrectangle($canvas, 0, 0, $largura, $altura, imagecolorallocatealpha($canvas, 0, 0, 0, 127));
            imagealphablending($canvas, true);
        } else {
            imagefilledrectangle($canvas, 0, 0, $largura, $altura, imagecolorallocate($canvas, 255, 255, 255));
        }

        // 4) encaixa mantendo proporção, centralizado, com margem leve
        $margemW = (int) round($largura * 0.04);
        $margemH = (int) round($altura * 0.04);
        $alvoW   = $largura - 2 * $margemW;
        $alvoH   = $altura - 2 * $margemH;
        $ratio   = min($alvoW / $ow, $alvoH / $oh);
        $nw = max(1, (int) round($ow * $ratio));
        $nh = max(1, (int) round($oh * $ratio));
        $ox = (int) (($largura - $nw) / 2);
        $oy = (int) (($altura - $nh) / 2);
        imagecopyresampled($canvas, $src, $ox, $oy, 0, 0, $nw, $nh, $ow, $oh);

        // 5) salva WebP
        if ($fundo === 'transparente') imagesavealpha($canvas, true);
        $ok = imagewebp($canvas, $destPath, $fundo === 'transparente' ? 90 : $qualidade);

        imagedestroy($src);
        imagedestroy($canvas);
        if ($tmpCut) @unlink($tmpCut);
        return (bool) $ok;
    }

    /** Remove o fundo via rembg (u2netp), serializado por flock (1 por vez) pra não estourar RAM. */
    public static function removerFundo(string $srcPath, string $destPngPath): bool
    {
        if (!self::rembgDisponivel()) return false;

        $fp = @fopen(self::LOCK, 'c');
        if (!$fp) return false;
        if (!flock($fp, LOCK_EX)) { fclose($fp); return false; }   // espera a vez

        try {
            $cmd = 'U2NET_HOME=' . escapeshellarg(self::MODELDIR)
                 . ' HOME=/tmp OMP_NUM_THREADS=2 OPENBLAS_NUM_THREADS=2 timeout ' . self::TIMEOUT . ' '
                 . escapeshellarg(self::REMBG) . ' i -m ' . escapeshellarg(self::MODELO)
                 . ' ' . escapeshellarg($srcPath) . ' ' . escapeshellarg($destPngPath)
                 . ' 2>/tmp/rembg_err.log';
            exec($cmd, $out, $code);
            return $code === 0 && is_file($destPngPath) && filesize($destPngPath) > 0;
        } finally {
            flock($fp, LOCK_UN);
            fclose($fp);
        }
    }

    /**
     * Converte uma imagem existente em disco pra WebP, preservando a proporção original
     * (sem "padronizar" em quadrado). Se $maxLado for informado, reduz proporcionalmente
     * quando o lado maior passar do limite.
     */
    public static function paraWebp(string $srcPath, string $destPath, int $qualidade = 85, ?int $maxLado = null): bool
    {
        $src = self::carregar($srcPath);
        if (!$src) return false;

        $w = imagesx($src);
        $h = imagesy($src);

        if ($maxLado && max($w, $h) > $maxLado) {
            $escala = $maxLado / max($w, $h);
            $nw = max(1, (int) round($w * $escala));
            $nh = max(1, (int) round($h * $escala));
            $dst = imagecreatetruecolor($nw, $nh);
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            imagefilledrectangle($dst, 0, 0, $nw, $nh, imagecolorallocatealpha($dst, 0, 0, 0, 127));
            imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
            imagedestroy($src);
            $src = $dst;
        }

        imagesavealpha($src, true);
        $ok = imagewebp($src, $destPath, $qualidade);
        imagedestroy($src);
        return (bool) $ok;
    }

    /** Converte um binário de imagem em memória (ex: base64 já decodificado) pra WebP em disco. */
    public static function binarioParaWebp(string $binario, string $destPath, int $qualidade = 85, ?int $maxLado = null): bool
    {
        $tmp = sys_get_temp_dir() . '/wp_' . bin2hex(random_bytes(6));
        if (file_put_contents($tmp, $binario) === false) return false;
        $ok = self::paraWebp($tmp, $destPath, $qualidade, $maxLado);
        @unlink($tmp);
        return $ok;
    }

    /** Reduz a imagem pra no máx. $max px (lado maior) e salva JPEG temporário. Devolve o caminho ou null. */
    private static function redimensionarTemp(string $srcPath, int $max)
    {
        $src = self::carregar($srcPath);
        if (!$src) return null;
        $w = imagesx($src); $h = imagesy($src);
        $escala = min(1, $max / max($w, $h));
        if ($escala >= 1) { imagedestroy($src); return null; } // já é pequena, usa a original
        $nw = max(1, (int) round($w * $escala));
        $nh = max(1, (int) round($h * $escala));
        $dst = imagecreatetruecolor($nw, $nh);
        imagefilledrectangle($dst, 0, 0, $nw, $nh, imagecolorallocate($dst, 255, 255, 255));
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
        $tmp = sys_get_temp_dir() . '/rz_' . bin2hex(random_bytes(6)) . '.jpg';
        $ok  = imagejpeg($dst, $tmp, 90);
        imagedestroy($src); imagedestroy($dst);
        return $ok ? $tmp : null;
    }

    private static function carregar(string $path)
    {
        $info = @getimagesize($path);
        if (!$info) return null;
        switch ($info['mime'] ?? '') {
            case 'image/jpeg': $im = @imagecreatefromjpeg($path); break;
            case 'image/png':  $im = @imagecreatefrompng($path);  break;
            case 'image/webp': $im = @imagecreatefromwebp($path); break;
            case 'image/gif':  $im = @imagecreatefromgif($path);  break;
            case 'image/bmp':  $im = @imagecreatefrombmp($path);  break;
            default: return null;
        }
        if (!$im) return null;
        imagealphablending($im, true);
        return $im;
    }
}
