<?php
/*
 * Testes do pedido de avaliação no Google (OrdemServicoController::enviarPedidoAvaliacaoGoogle())
 * — réplica isolada da leitura do link configurado (chave 'google_review_link' em
 * `configuracoes`, mesmo padrão key/value de texto_garantia/os_prefixo) contra SQLite em
 * memória, e da montagem da mensagem enviada. Não instancia o Controller de verdade
 * (json()/csrf_verify() exigem sessão/exit, e WhatsAppService::enviarTexto() faz chamada de
 * rede de verdade — mesma limitação de sempre pra testar controller isolado neste projeto).
 * Rodar com: php tests/os_avaliacao_google_test.php
 */

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/app/Helpers/functions.php';

$falhas = 0; $total = 0;
function assert_igual($esperado, $obtido, string $descricao): void
{
    global $falhas, $total;
    $total++;
    if ($esperado === $obtido) { echo "  OK  $descricao\n"; return; }
    $falhas++;
    echo "FALHA $descricao\n      esperado: " . var_export($esperado, true) . "\n      obtido:   " . var_export($obtido, true) . "\n";
}

/** Réplica exata da leitura de config feita em enviarPedidoAvaliacaoGoogle(). */
function lerGoogleReviewLink(PDO $db, int $empresaId): string
{
    $stmt = $db->prepare("SELECT valor FROM configuracoes WHERE empresa_id=? AND chave='google_review_link'");
    $stmt->execute([$empresaId]);
    return trim((string) $stmt->fetchColumn());
}

/** Réplica exata da normalização do complemento (opcional) em enviarPedidoAvaliacaoGoogle(). */
function normalizarComplementoAvaliacao(string $bruto): string
{
    $complemento = trim($bruto);
    if (mb_strlen($complemento) > 300) $complemento = mb_substr($complemento, 0, 300);
    return $complemento;
}

/** Réplica exata da montagem da mensagem em enviarPedidoAvaliacaoGoogle(). */
function montarMensagemAvaliacao(string $clienteNome, string $empresaNome, string $numeroOs, string $link, string $complementoBruto = ''): string
{
    $complemento = normalizarComplementoAvaliacao($complementoBruto);
    return "Olá, " . primeiro_nome($clienteNome) . "! Aqui é da {$empresaNome}. 🙌\n\n"
         . "Muito obrigado por confiar no nosso trabalho na sua OS nº {$numeroOs}!"
         . ($complemento !== '' ? "\n\n{$complemento}" : '')
         . "\n\nSe puder, avalie nosso atendimento no Google — leva menos de 1 minuto e ajuda muito a gente:\n\n{$link}";
}

$db = new PDO('sqlite::memory:');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->exec('CREATE TABLE configuracoes (empresa_id INTEGER, chave TEXT, valor TEXT)');
$db->exec("INSERT INTO configuracoes (empresa_id, chave, valor) VALUES (1, 'google_review_link', 'https://g.page/r/exemplo/review')");
$db->exec("INSERT INTO configuracoes (empresa_id, chave, valor) VALUES (2, 'google_review_link', '  ')"); // salvo em branco por engano
$db->exec("INSERT INTO configuracoes (empresa_id, chave, valor) VALUES (1, 'texto_garantia', 'algo')"); // ruído, outra chave

assert_igual('https://g.page/r/exemplo/review', lerGoogleReviewLink($db, 1), 'lerGoogleReviewLink: empresa com link configurado');
assert_igual('', lerGoogleReviewLink($db, 2), 'lerGoogleReviewLink: valor salvo só com espaço vira vazio (trim), trata como não configurado');
assert_igual('', lerGoogleReviewLink($db, 3), 'lerGoogleReviewLink: empresa sem a chave nenhuma -> vazio, não quebra');

$msg = montarMensagemAvaliacao('Maria da Silva Santos', 'Eletroli Assistência Técnica', '1042', 'https://g.page/r/exemplo/review');
assert_igual(true, str_starts_with($msg, 'Olá, Maria!'), 'montarMensagemAvaliacao: usa só o primeiro nome do cliente na saudação');
assert_igual(true, str_contains($msg, 'Eletroli Assistência Técnica'), 'montarMensagemAvaliacao: nome da empresa no corpo');
assert_igual(true, str_contains($msg, 'OS nº 1042'), 'montarMensagemAvaliacao: número da OS no corpo');
assert_igual(true, str_ends_with($msg, 'https://g.page/r/exemplo/review'), 'montarMensagemAvaliacao: link vem no final, pronto pra virar preview clicável no WhatsApp');

// ── Complemento opcional (pedido do usuário: modal com opção de complementar a mensagem) ────
$semComplemento = montarMensagemAvaliacao('João', 'Eletroli', '1', 'https://g.page/r/x', '');
assert_igual(false, str_contains($semComplemento, "!\n\n\n\n"), 'montarMensagemAvaliacao: complemento vazio não deixa linha em branco sobrando');

$comComplemento = montarMensagemAvaliacao('João', 'Eletroli', '1', 'https://g.page/r/x', '  Foi um prazer resolver rápido!  ');
assert_igual(true, str_contains($comComplemento, "\n\nFoi um prazer resolver rápido!\n\n"), 'montarMensagemAvaliacao: complemento (com espaço nas pontas, já trimado) entra entre o agradecimento e o pedido de avaliação');

assert_igual('', normalizarComplementoAvaliacao('   '), 'normalizarComplementoAvaliacao: só espaço vira vazio');
assert_igual('abc', normalizarComplementoAvaliacao('  abc  '), 'normalizarComplementoAvaliacao: trim nas pontas');
assert_igual(300, mb_strlen(normalizarComplementoAvaliacao(str_repeat('x', 500))), 'normalizarComplementoAvaliacao: corta em 300 caracteres, não deixa mensagem gigante');

echo "\n{$total} testes, " . ($total - $falhas) . " OK, {$falhas} falha(s)\n";
exit($falhas > 0 ? 1 : 0);
