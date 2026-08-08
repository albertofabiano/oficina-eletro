<?php
// Envia, pelo WhatsApp da PLATAFORMA (instância `fixaos`, mesma usada pra boas-vindas),
// o anúncio dos novos status "Laudo Técnico" e "Sem Conserto" pra todas as empresas REAIS
// do sistema (ativo=1 AND reivindicada=1 — inclui quem não está pagando, conforme pedido).
// Manda o texto e, se os caminhos das imagens forem informados, os 2 prints de exemplo logo em seguida.
//
// SEGURANÇA: por padrão roda em modo DRY-RUN — só lista quem receberia a mensagem, não
// envia nada. Pra enviar de verdade, use --enviar.
//
// Uso:
//   php scripts/enviar_anuncio_novos_status.php
//   php scripts/enviar_anuncio_novos_status.php --enviar
//   php scripts/enviar_anuncio_novos_status.php --enviar --img-sem-conserto=/caminho/sem_conserto.png --img-laudo=/caminho/laudo.png
//
// Rodar de dentro de /var/www/fixaos. Suba as imagens pro VPS antes (ex.: scp do seu PC).

define('BASE_PATH', dirname(__DIR__));
spl_autoload_register(function (string $class) {
    $path = BASE_PATH . '/' . str_replace(['App\\', '\\'], ['app/', '/'], $class) . '.php';
    if (file_exists($path)) require $path;
});

use App\Core\DB;
use App\Services\WhatsAppService;

$enviarDeVerdade = in_array('--enviar', $argv, true);

function argValor(array $argv, string $chave): ?string
{
    foreach ($argv as $a) {
        if (str_starts_with($a, $chave . '=')) return substr($a, strlen($chave) + 1);
    }
    return null;
}

$imgSemConserto = argValor($argv, '--img-sem-conserto');
$imgLaudo       = argValor($argv, '--img-laudo');

// Valida os arquivos de imagem, se informados (falha alto e cedo — não quero mandar metade
// das empresas com imagem e a outra metade sem por um caminho errado).
$imagens = [];
foreach (['sem-conserto' => $imgSemConserto, 'laudo' => $imgLaudo] as $rotulo => $caminho) {
    if ($caminho === null) continue;
    if (!is_file($caminho)) {
        fwrite(STDERR, "Arquivo não encontrado ({$rotulo}): {$caminho}\n");
        exit(1);
    }
    $ext = strtolower(pathinfo($caminho, PATHINFO_EXTENSION));
    $mime = match ($ext) {
        'png'         => 'image/png',
        'jpg', 'jpeg' => 'image/jpeg',
        'webp'        => 'image/webp',
        default       => null,
    };
    if (!$mime) {
        fwrite(STDERR, "Formato não suportado ({$rotulo}): {$caminho} (use png, jpg ou webp)\n");
        exit(1);
    }
    $imagens[] = [
        'rotulo'   => $rotulo,
        'base64'   => base64_encode(file_get_contents($caminho)),
        'fileName' => 'exemplo-' . $rotulo . '.' . $ext,
        'mimetype' => $mime,
    ];
}

$mensagem = "Olá! Passando pra contar uma novidade no FixaOS 🔧\n\n"
    . "Adicionamos 2 novos status de OS, cada um com seu próprio documento de impressão:\n\n"
    . "📋 *Laudo Técnico* — pra quando o equipamento está em diagnóstico. Ao selecionar esse status, "
    . "o menu Imprimir passa a mostrar só o documento de Laudo Técnico, já formatado com o defeito "
    . "relatado e o que você escreveu no laudo.\n\n"
    . "⚠️ *Sem Conserto* — pra quando o equipamento não tem conserto. Também gera um documento "
    . "próprio, já explicando pro cliente que não teve cobrança e que o aparelho está disponível "
    . "pra retirada.\n\n"
    . "Em ambos os casos, o sistema mostra só o documento relevante pra aquele status — sem poluir "
    . "o menu com opções que não fazem sentido ali.\n\n"
    . ($imagens
        ? "As imagens em anexo são só um *exemplo ilustrativo* (dados fictícios de teste), pra você "
          . "visualizar como cada documento fica com o respectivo status selecionado — não é referente "
          . "à sua conta.\n\n"
        : '')
    . "Qualquer dúvida, é só responder aqui. Conte com a gente! 💙\n"
    . "— Equipe FixaOS";

$db = DB::pdo();
$empresas = $db->query(
    "SELECT id, nome_fantasia, razao_social, whatsapp, telefone FROM empresas WHERE ativo = 1 AND reivindicada = 1"
)->fetchAll();

echo ($enviarDeVerdade ? "ENVIANDO DE VERDADE" : "DRY-RUN (nada será enviado — use --enviar pra disparar)") . "\n";
echo "Imagens anexadas: " . ($imagens ? implode(', ', array_column($imagens, 'rotulo')) : '(nenhuma — só texto)') . "\n";
echo str_repeat('-', 60) . "\n";

$enviados = 0;
$semNumero = 0;
$falhas = 0;

foreach ($empresas as $emp) {
    $nome   = $emp['nome_fantasia'] ?: $emp['razao_social'];
    $numero = $emp['whatsapp'] ?: $emp['telefone'];

    if (!$numero) {
        echo "[SEM NÚMERO] empresa {$emp['id']} ({$nome})\n";
        $semNumero++;
        continue;
    }

    if (!$enviarDeVerdade) {
        echo "[ENVIARIA] empresa {$emp['id']} ({$nome}) -> {$numero}" . ($imagens ? ' (+ ' . count($imagens) . ' imagem(ns))' : '') . "\n";
        continue;
    }

    $ok = WhatsAppService::enviarTextoPlataforma($numero, $mensagem);
    foreach ($imagens as $img) {
        $ok = WhatsAppService::enviarImagemPlataforma($numero, $img['base64'], $img['fileName'], '', $img['mimetype']) && $ok;
    }

    if ($ok) {
        echo "[OK] empresa {$emp['id']} ({$nome}) -> {$numero}\n";
        $enviados++;
    } else {
        echo "[FALHOU] empresa {$emp['id']} ({$nome}) -> {$numero}\n";
        $falhas++;
    }

    sleep(2); // evita rajada de envios na mesma instância
}

echo str_repeat('-', 60) . "\n";
if ($enviarDeVerdade) {
    echo "Concluído. Enviados: {$enviados} | Falhas: {$falhas} | Sem número: {$semNumero}\n";
} else {
    echo "Total que receberia: " . (count($empresas) - $semNumero) . " | Sem número: {$semNumero}\n";
    echo "Confira a lista acima e rode de novo com --enviar quando estiver pronto.\n";
}
