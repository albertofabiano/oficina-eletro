<?php
// Gera favicon do FixaOS (B: navy + F branco + ponto laranja) em varios tamanhos.
$OUT = $argv[1] ?? '/var/www/fixaos/public';

function roundedRect($img,$x1,$y1,$x2,$y2,$r,$c){
    $x1=(int)$x1;$y1=(int)$y1;$x2=(int)$x2;$y2=(int)$y2;$r=(int)$r;
    imagefilledrectangle($img,$x1+$r,$y1,$x2-$r,$y2,$c);
    imagefilledrectangle($img,$x1,$y1+$r,$x2,$y2-$r,$c);
    imagefilledarc($img,$x1+$r,$y1+$r,2*$r,2*$r,180,270,$c,IMG_ARC_PIE);
    imagefilledarc($img,$x2-$r,$y1+$r,2*$r,2*$r,270,360,$c,IMG_ARC_PIE);
    imagefilledarc($img,$x1+$r,$y2-$r,2*$r,2*$r,90,180,$c,IMG_ARC_PIE);
    imagefilledarc($img,$x2-$r,$y2-$r,2*$r,2*$r,0,90,$c,IMG_ARC_PIE);
}

// Desenha o icone no espaco 100x100 escalado por k, sobre canvas transparente
function desenhar($size){
    $M = 1024; // master em alta resolucao
    $im = imagecreatetruecolor($M,$M);
    imagesavealpha($im,true);
    $transp = imagecolorallocatealpha($im,0,0,0,127);
    imagefill($im,0,0,$transp);
    $k = $M/100;
    $navy   = imagecolorallocate($im,0x1e,0x3a,0x5f);
    $branco = imagecolorallocate($im,255,255,255);
    $laranja= imagecolorallocate($im,0xf9,0x73,0x16);
    // squircle navy
    roundedRect($im, 2*$k,2*$k, 98*$k,98*$k, 24*$k, $navy);
    // F branco
    imagefilledrectangle($im,(int)(34*$k),(int)(26*$k),(int)(47*$k),(int)(75*$k),$branco); // stem
    imagefilledrectangle($im,(int)(34*$k),(int)(26*$k),(int)(70*$k),(int)(39*$k),$branco); // top
    imagefilledrectangle($im,(int)(34*$k),(int)(44*$k),(int)(58*$k),(int)(56*$k),$branco); // mid
    // ponto laranja
    imagefilledellipse($im,(int)(70*$k),(int)(70*$k),(int)(18*$k),(int)(18*$k),$laranja);
    // downscale suave
    if($size === $M) return $im;
    $out = imagecreatetruecolor($size,$size);
    imagesavealpha($out,true);
    $t = imagecolorallocatealpha($out,0,0,0,127);
    imagefill($out,0,0,$t);
    imagecopyresampled($out,$im,0,0,0,0,$size,$size,$M,$M);
    imagedestroy($im);
    return $out;
}

function salvarPng($size,$path){
    $im = desenhar($size);
    imagepng($im,$path);
    imagedestroy($im);
    return $path;
}

// PNGs
salvarPng(16,  "$OUT/favicon-16.png");
salvarPng(32,  "$OUT/favicon-32.png");
salvarPng(48,  "$OUT/favicon-48.png");
salvarPng(180, "$OUT/apple-touch-icon.png");
salvarPng(192, "$OUT/icon-192.png");
salvarPng(512, "$OUT/icon-512.png");

// favicon.ico com PNGs embutidos (16,32,48)
function buildIco($sizes,$out){
    $imgs=[];
    foreach($sizes as $s){ ob_start(); $im=desenhar($s); imagepng($im); imagedestroy($im); $imgs[$s]=ob_get_clean(); }
    $n=count($imgs);
    $ico = pack('vvv',0,1,$n);
    $offset = 6 + 16*$n;
    foreach($imgs as $s=>$data){
        $w = $s>=256?0:$s; $h=$w;
        $ico .= pack('CCCCvvVV',$w,$h,0,0,1,32,strlen($data),$offset);
        $offset += strlen($data);
    }
    foreach($imgs as $data){ $ico .= $data; }
    file_put_contents($out,$ico);
}
buildIco([16,32,48], "$OUT/favicon.ico");

// favicon.svg
$svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100">'
     . '<rect x="2" y="2" width="96" height="96" rx="24" fill="#1e3a5f"/>'
     . '<rect x="34" y="26" width="13" height="49" rx="2.5" fill="#ffffff"/>'
     . '<rect x="34" y="26" width="36" height="13" rx="2.5" fill="#ffffff"/>'
     . '<rect x="34" y="44" width="24" height="12" rx="2.5" fill="#ffffff"/>'
     . '<circle cx="70" cy="70" r="9" fill="#f97316"/></svg>';
file_put_contents("$OUT/favicon.svg",$svg);

// manifest
$man = json_encode([
  'name'=>'FixaOS','short_name'=>'FixaOS',
  'icons'=>[
    ['src'=>'/icon-192.png','sizes'=>'192x192','type'=>'image/png'],
    ['src'=>'/icon-512.png','sizes'=>'512x512','type'=>'image/png'],
  ],
  'theme_color'=>'#1e3a5f','background_color'=>'#1e3a5f','display'=>'standalone',
], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
file_put_contents("$OUT/site.webmanifest",$man);

echo "OK gerado em $OUT\n";
foreach(['favicon.ico','favicon.svg','favicon-16.png','favicon-32.png','apple-touch-icon.png','icon-192.png','icon-512.png','site.webmanifest'] as $f){
    echo "  $f — " . (file_exists("$OUT/$f")? filesize("$OUT/$f").' bytes':'FALHOU') . "\n";
}
