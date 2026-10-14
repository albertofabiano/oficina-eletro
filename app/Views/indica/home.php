<?php
// Fixa Indica — home pública. $categorias, $produtos (destaque), $lojasPresentes, $guias,
// $contagens vêm do Indica\PublicoController@home.
$whatsappContato = \App\Services\Indica\CatalogoService::config('whatsapp_contato', '');
$mostrarFiltroLoja = count($lojasPresentes ?? []) > 1;
?>
<div class="hero">
  <svg class="traces" aria-hidden="true" width="100%" height="100%">
    <defs>
      <pattern id="pcb" width="160" height="160" patternUnits="userSpaceOnUse">
        <path d="M0 40 H60 L85 65 H160 M40 0 V38 M85 65 V160 M120 0 V30 L160 70 M0 120 H40 L70 150 H160" fill="none" stroke="#6D2BE0" stroke-width="1.4" opacity=".55"/>
        <circle cx="60" cy="40" r="3.4" fill="none" stroke="#6D2BE0" stroke-width="1.4" opacity=".6"/>
        <circle cx="85" cy="65" r="3.4" fill="none" stroke="#F2641E" stroke-width="1.4" opacity=".5"/>
        <circle cx="40" cy="120" r="3.4" fill="none" stroke="#6D2BE0" stroke-width="1.4" opacity=".6"/>
      </pattern>
    </defs>
    <rect width="100%" height="100%" fill="url(#pcb)"/>
  </svg>

  <div class="wrap hero-in">
    <div>
      <span class="tagline"><i>⚡</i> Um produto FixaOS · afiliado Mercado Livre e AliExpress</span>
      <h1>A ferramenta certa é a que <em>aguenta o dia a dia</em> da bancada.</h1>
      <p class="lead">Nada aqui é lista gerada sozinha. Cada produto indicado passa por uma triagem de quem entende de assistência técnica — e o que não vale a pena, não entra.</p>

      <form class="search" role="search" action="/busca" method="GET">
        <label for="q" style="position:absolute;left:-9999px">Buscar</label>
        <input id="q" type="search" name="q" placeholder="Ferro de solda, fonte, multímetro, flex de celular…" value="<?= e($_GET['q'] ?? '') ?>">
        <button type="submit">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
          Buscar
        </button>
      </form>

      <?php if (!empty($categorias)): ?>
      <div class="chips">
        <?php foreach (array_slice($categorias, 0, 4) as $c): ?>
          <a href="/categoria/<?= e($c['slug']) ?>"><?= e($c['nome']) ?></a>
        <?php endforeach; ?>
        <a href="/guias">Guias</a>
      </div>
      <?php endif; ?>
    </div>

    <aside class="cred">
      <div class="who">
        <div class="av">FI</div>
        <div>
          <b>Quem monta essa lista</b>
          <span>Equipe FixaOS · perto de quem conserta todo dia</span>
        </div>
      </div>
      <ul>
        <li>
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#4ADE9B" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
          <span><b>Pensado pra bancada,</b> não pra catálogo — o critério é se a ferramenta aguenta a rotina de uma assistência técnica.</span>
        </li>
        <li>
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#4ADE9B" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
          <span><b>Compra no Mercado Livre ou no AliExpress,</b> com a proteção ao comprador de cada um. Nada passa pelo nosso caixa.</span>
        </li>
        <li>
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#4ADE9B" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
          <span><b>Comissão declarada.</b> Ganhamos da plataforma, nunca do fabricante — e o preço pra você é o mesmo.</span>
        </li>
      </ul>
    </aside>
  </div>

  <div class="rule on-dark"></div>

  <div class="assure">
    <div class="wrap assure-in">
      <div>
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#A78BFA" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/></svg>
        <div><b>Proteção ao comprador</b>Compra Garantida no Mercado Livre, Proteção ao Comprador no AliExpress</div>
      </div>
      <div>
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#A78BFA" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="6" width="15" height="12" rx="2"/><path d="M16 10h4l3 3v5h-7"/><circle cx="6" cy="18.5" r="2"/><circle cx="18" cy="18.5" r="2"/></svg>
        <div><b>Prazo sempre visível</b>Entrega nacional (Mercado Livre) ou importada (AliExpress) — indicado em cada card</div>
      </div>
      <div>
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#A78BFA" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v4m0 12v4M2 12h4m12 0h4M5 5l2.8 2.8M16.2 16.2 19 19M19 5l-2.8 2.8M7.8 16.2 5 19"/></svg>
        <div><b>Lista revisada com frequência</b>Item esgotado ou que piorou de qualidade é removido da vitrine</div>
      </div>
    </div>
  </div>
</div>

<?php if (!empty($categorias)): ?>
<section id="categorias">
  <div class="wrap">
    <div class="sec-h">
      <div>
        <span class="eyebrow">Por onde começar</span>
        <h2 style="margin-top:9px">Escolha pelo serviço que você faz</h2>
        <p>Organizado por tipo de bancada, não por departamento de loja.</p>
      </div>
    </div>
    <div class="cats">
      <?php foreach ($categorias as $c): ?>
      <a href="/categoria/<?= e($c['slug']) ?>" class="cat">
        <div class="ic"><?= e($c['icone'] ?: '🔧') ?></div>
        <h3><?= e($c['nome']) ?></h3>
        <?php if (!empty($c['texto_seo'])): ?><p><?= e(mb_strimwidth($c['texto_seo'], 0, 70, '…')) ?></p><?php endif; ?>
        <span class="n">VER INDICAÇÕES →</span>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if (!empty($produtos)): ?>
<section id="indicados" style="padding-top:0">
  <div class="wrap">
    <div class="sec-h">
      <div>
        <span class="eyebrow">Vitrine</span>
        <h2 style="margin-top:9px">Mais indicados pelos técnicos</h2>
        <p>Cada card diz em uma linha por que o produto está aqui — e de qual loja ele vem. Se não tiver motivo, não vira indicação.</p>
      </div>
      <a href="/busca" class="more">Ver tudo →</a>
    </div>

    <?php if ($mostrarFiltroLoja): ?>
    <div class="storebar" role="group" aria-label="Filtrar por loja">
      <button class="sfilter on" data-store="all" aria-pressed="true">Todas as lojas</button>
      <button class="sfilter" data-store="ml" aria-pressed="false"><i class="ml-d"></i> Mercado Livre</button>
      <button class="sfilter" data-store="ae" aria-pressed="false"><i class="ae-d"></i> AliExpress</button>
    </div>
    <?php endif; ?>

    <div class="prods">
      <?php foreach ($produtos as $p): $origem = 'home'; include __DIR__ . '/_card_produto.php'; endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<section id="lojas" style="padding-top:0">
  <div class="wrap">
    <div class="sec-h">
      <div>
        <span class="eyebrow">Mercado Livre ou AliExpress</span>
        <h2 style="margin-top:9px">Qual loja compensa em cada caso</h2>
        <p>Indicamos pelas duas porque elas resolvem problemas diferentes. A escolha é sua — só não queremos que você descubra o prazo depois de pagar.</p>
      </div>
    </div>

    <div class="shops">
      <div class="shop ml">
        <h3><span class="store ml"><i></i>Mercado Livre</span></h3>
        <div class="when">Quando você precisa <strong>agora</strong>.</div>
        <dl>
          <div><dt>Prazo</dt><dd>De um a poucos dias no Full. Serviço parado não espera importação.</dd></div>
          <div><dt>Garantia</dt><dd><b>Compra Garantida</b> e devolução pela própria plataforma, com nota fiscal brasileira.</dd></div>
          <div><dt>Preço</dt><dd>Mais alto que o importado, mas sem imposto ou taxa surpresa no caminho.</dd></div>
          <div><dt>Indicamos para</dt><dd>Ferramenta principal, instrumento de medição e qualquer peça que você precise hoje.</dd></div>
        </dl>
      </div>

      <div class="shop ae">
        <h3><span class="store ae"><i></i>AliExpress</span></h3>
        <div class="when">Quando o preço compensa <strong>a espera</strong>.</div>
        <dl>
          <div><dt>Prazo</dt><dd>Semanas, não dias. Alguns anúncios têm estoque no Brasil — quando tem, avisamos no card.</dd></div>
          <div><dt>Garantia</dt><dd><b>Proteção ao Comprador</b> com reembolso, mas a troca é mais lenta e sem assistência local.</dd></div>
          <div><dt>Imposto</dt><dd>Em vendedores do <b>Remessa Conforme</b> o imposto é cobrado junto no checkout — o valor que aparece ali já é o final.</dd></div>
          <div><dt>Indicamos para</dt><dd>Consumo e reposição: pontas, espátulas, pinças, lupa, kits — comprado com antecedência.</dd></div>
        </dl>
      </div>
    </div>
  </div>
</section>

<div class="crit" id="criterio">
  <div class="wrap">
    <section style="padding:56px 0">
      <div class="sec-h">
        <div>
          <span class="eyebrow">Transparência</span>
          <h2 style="margin-top:9px">Como um produto entra nesta lista</h2>
          <p>Três filtros, na ordem. O que não passa no primeiro nem chega no segundo.</p>
        </div>
      </div>

      <div class="crit-grid">
        <div>
          <span class="num">FILTRO 01</span>
          <h3>Passa pela mão de quem conserta</h3>
          <p>A avaliação parte de quem lida com reparo no dia a dia — ferramenta que não sobrevive a uma rotina de oficina não vira indicação.</p>
        </div>
        <div>
          <span class="num">FILTRO 02</span>
          <h3>Tem reposição no Brasil</h3>
          <p>Ponta, bateria, fonte, cabo: se a peça de reposição some do mercado em pouco tempo, o produto sai da vitrine. Barato que vira lixo não é barato.</p>
        </div>
        <div>
          <span class="num">FILTRO 03</span>
          <h3>Vendedor com histórico</h3>
          <p>Só anúncios de vendedores com reputação consolidada e envio rastreado — no Mercado Livre e no AliExpress. A compra, a nota e a garantia ficam todas com a loja.</p>
        </div>
      </div>

      <div class="disclose">
        <div class="ic">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9.5"/><path d="M12 16v-5M12 8h.01"/></svg>
        </div>
        <div>
          <h3>Sobre a comissão, sem rodeio</h3>
          <p>O Fixa Indica é afiliado do <strong>Mercado Livre</strong> e do <strong>AliExpress</strong>. Quando você compra por um link daqui, a plataforma nos paga uma comissão — <strong>o preço que você paga é exatamente o mesmo</strong>. Nenhuma marca paga para aparecer nesta lista, nenhum fabricante tem contato com a gente, e a loja de cada item é escolhida por onde ele compensa, não por qual paga mais. É isso que mantém a indicação honesta: só ganhamos se você comprar algo que preste e voltar.</p>
        </div>
      </div>
    </section>
  </div>
</div>

<section id="quem">
  <div class="wrap about">
    <div class="about-card">
      <svg class="traces" aria-hidden="true" width="100%" height="100%"><rect width="100%" height="100%" fill="url(#pcb)"/></svg>
      <div class="av">FI</div>
      <h3>Fixa Indica</h3>
      <div class="role">Vitrine de bancada · um produto FixaOS</div>
      <div class="stats">
        <div><div class="v"><?= (int) ($contagens['produtos'] ?? 0) ?></div><div class="l">produtos na vitrine hoje</div></div>
        <div><div class="v"><?= (int) ($contagens['categorias'] ?? 0) ?></div><div class="l">categorias organizadas</div></div>
        <div><div class="v"><?= (int) ($contagens['guias'] ?? 0) ?></div><div class="l">guias de problema e solução</div></div>
      </div>
    </div>

    <div>
      <span class="eyebrow">Quem indica</span>
      <h2 style="margin-top:10px">Quem cuida da lista sabe o que é abrir aparelho todo dia.</h2>
      <p>O Fixa Indica nasceu de uma pergunta que toda assistência técnica já ouviu: "qual ferro de solda eu compro?", "essa fonte presta?", "vale a pena essa peça?". Até aqui, a resposta ia passando de boca em boca.</p>
      <p>Esta página organiza essa conversa. É um produto do <strong>FixaOS</strong>, o sistema de gestão pra assistência técnica — mesma equipe, mesmo entendimento de bancada.</p>
      <p>Se um produto decepcionar depois de indicado, ele sai da lista.</p>
      <div class="sig">— Equipe Fixa Indica</div>
    </div>
  </div>
</section>

<?php if (!empty($guias)): ?>
<section style="padding-top:0">
  <div class="wrap">
    <div class="sec-h">
      <div>
        <span class="eyebrow">Antes de comprar, entenda</span>
        <h2 style="margin-top:9px">Guias: problema e solução</h2>
        <p>Conteúdo escrito pra quem vai colocar a mão no aparelho, não pra encher página.</p>
      </div>
      <a href="/guias" class="more">Todos os guias →</a>
    </div>
    <div class="guides">
      <?php foreach ($guias as $g): ?>
      <a href="/guias/<?= e($g['slug']) ?>" class="guide">
        <h3><?= e($g['titulo']) ?></h3>
        <?php if (!empty($g['meta_description'])): ?><p><?= e($g['meta_description']) ?></p><?php endif; ?>
        <span class="go">Ver guia completo →</span>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if ($whatsappContato !== ''): ?>
<section style="padding-top:0">
  <div class="wrap">
    <div class="wa-box">
      <span class="eyebrow">Alguma dúvida sobre um produto?</span>
      <h2 style="margin-top:10px">Fale direto com a gente pelo WhatsApp</h2>
      <p>Perguntou e não achou a resposta aqui? É só mandar mensagem.</p>
      <a class="btn-wa" href="https://wa.me/<?= e(preg_replace('/\D/', '', $whatsappContato)) ?>" target="_blank" rel="noopener">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M17.5 14.4c-.3-.1-1.7-.8-2-.9-.3-.1-.5-.2-.7.2-.2.3-.8.9-1 1.1-.2.2-.4.2-.7.1-1.6-.8-2.7-1.4-3.8-3.2-.3-.5.3-.5.8-1.6.1-.2.1-.4 0-.5-.1-.1-.6-1.4-.8-1.9-.2-.5-.4-.4-.6-.4h-.5c-.2 0-.5.1-.8.4-.3.3-1 1-1 2.4s1 2.8 1.2 3c.2.2 2.2 3.4 5.4 4.6 2.7 1 2.7.8 3.2.8.5 0 1.6-.6 1.8-1.3.2-.6.2-1.1.2-1.2-.1-.1-.2-.2-.5-.3z"/><path d="M12 2a10 10 0 0 0-8.5 15.2L2 22l4.9-1.5A10 10 0 1 0 12 2zm0 18.2c-1.5 0-3-.4-4.2-1.1l-.3-.2-3 .9.9-2.9-.2-.3A8.2 8.2 0 1 1 20.2 12 8.2 8.2 0 0 1 12 20.2z"/></svg>
        Chamar no WhatsApp
      </a>
    </div>
  </div>
</section>
<?php endif; ?>

<script type="application/ld+json">
<?= json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'WebSite',
    'name' => 'Fixa Indica',
    'url' => indica_url('/'),
    'potentialAction' => [
        '@type' => 'SearchAction',
        'target' => indica_url('/busca') . '?q={termo}',
        'query-input' => 'required name=termo',
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
</script>

<?php if ($mostrarFiltroLoja): ?>
<script>
(function () {
  var botoes = document.querySelectorAll('.sfilter');
  var cards = document.querySelectorAll('.prod');
  botoes.forEach(function (b) {
    b.addEventListener('click', function () {
      botoes.forEach(function (x) { x.classList.remove('on'); x.setAttribute('aria-pressed', 'false'); });
      b.classList.add('on');
      b.setAttribute('aria-pressed', 'true');
      var loja = b.dataset.store;
      cards.forEach(function (c) {
        c.hidden = !(loja === 'all' || c.dataset.store === loja);
      });
    });
  });
})();
</script>
<?php endif; ?>
