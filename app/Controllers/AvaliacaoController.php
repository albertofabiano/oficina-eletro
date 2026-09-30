<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\DB;

/**
 * Página pública de redirecionamento pro link de avaliação do Google Meu Negócio de uma
 * empresa (ver "Pedir avaliação no Google" em OrdemServicoController::enviarPedidoAvaliacaoGoogle()).
 *
 * Existe só por causa da prévia de link do WhatsApp: mandando o link `g.page/r/.../review` cru
 * na mensagem, o WhatsApp busca os metadados Open Graph da PRÓPRIA página do Google pra montar
 * a prévia — e ela usa um recorte largo da logo colorida do Google como imagem, que o WhatsApp
 * espreme/corta de um jeito feio e pixelado no card de prévia (achado real, reportado pelo
 * usuário com print). Não tem como controlar isso, porque a página é do Google, não nossa.
 *
 * A saída: mandar um link do próprio FixaOS (/avaliar/{empresaId}) — essa página responde 200
 * com metadados Open Graph NOSSOS (sem imagem nenhuma, só título/descrição), e só DEPOIS
 * redireciona o navegador de verdade pro link do Google (meta refresh + JS). O crawler do
 * WhatsApp lê o HTML estático da resposta (nunca executa JS nem segue meta refresh), então
 * pega os metadados certos; a pessoa de carne e osso é redirecionada quase instantaneamente,
 * sem precisar clicar em nada — mantém a promessa de "leva menos de 1 minuto" da mensagem.
 *
 * Rota pública de propósito (sem AuthMiddleware, mesmo padrão de /os/acompanhar/{token} e
 * /scan/{token}) — é o cliente da assistência (ou o crawler do WhatsApp) quem acessa, nunca
 * alguém logado no FixaOS. `empresaId` na URL não expõe nada sensível (só decide qual link de
 * avaliação pública mostrar), por isso não precisa de token opaco.
 */
class AvaliacaoController extends Controller
{
    public function redirecionarGoogle(string $id): void
    {
        $eid = (int) $id;

        $stmt = DB::pdo()->prepare("SELECT valor FROM configuracoes WHERE empresa_id=? AND chave='google_review_link'");
        $stmt->execute([$eid]);
        $link = trim((string) $stmt->fetchColumn());

        // Sem link configurado (ou removido depois de uma mensagem antiga já ter sido mandada)
        // — manda pra home do FixaOS em vez de mostrar uma página quebrada/vazia.
        if ($link === '' || !filter_var($link, FILTER_VALIDATE_URL)) {
            $this->redirect(url('/'));
            return;
        }

        $stmtNome = DB::pdo()->prepare("SELECT nome_fantasia FROM empresas WHERE id=?");
        $stmtNome->execute([$eid]);
        $nomeEmpresa = trim((string) $stmtNome->fetchColumn()) ?: 'a assistência técnica';

        $tituloEsc = e("Avalie {$nomeEmpresa} no Google");
        $descEsc   = e("Sua opinião sobre o atendimento da {$nomeEmpresa} ajuda muito — leva menos de 1 minuto.");
        $linkEsc   = e($link);
        $linkJson  = json_encode($link, JSON_UNESCAPED_SLASHES);

        header('Content-Type: text/html; charset=UTF-8');
        echo <<<HTML
<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{$tituloEsc}</title>
<meta name="description" content="{$descEsc}">
<meta property="og:title" content="{$tituloEsc}">
<meta property="og:description" content="{$descEsc}">
<meta property="og:type" content="website">
<meta http-equiv="refresh" content="0;url={$linkEsc}">
<script>location.replace({$linkJson});</script>
<style>body{font-family:system-ui,sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;background:#0f1117;color:#e5e7eb;text-align:center;padding:24px}</style>
</head>
<body>
<p>Abrindo a avaliação no Google... <a href="{$linkEsc}" style="color:#60a5fa">Clique aqui se não for redirecionado.</a></p>
</body>
</html>
HTML;
        exit;
    }
}
