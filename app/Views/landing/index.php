<?php $titulo = ''; ?>

<!-- ═══ HERO ═══ -->
<section class="hero">
  <div class="container position-relative">
    <div class="row align-items-center g-5">
      <div class="col-lg-6">
        <div class="hero-tag">
          <i class="bi bi-lightning-charge-fill"></i> 15 dias grátis · sem cartão de crédito
        </div>
        <h1>O sistema de gestão para<br><em>assistência técnica</em> mais prático do Brasil</h1>
        <p class="hero-sub">
          Ordens de serviço, clientes, estoque, PDV e financeiro num sistema que você aprende em minutos. Envie <strong style="color:#25d366">orçamento e comprovante em PDF pelo WhatsApp</strong>, registre <strong style="color:#fb923c">fotos na entrada</strong> do aparelho e ganhe uma <strong style="color:#fff">página pública no Google</strong> que traz clientes.
        </p>

        <div class="d-flex flex-wrap gap-3 mt-4">
          <a href="<?= url('/cadastrar') ?>" class="btn-brand btn px-4 py-3 fs-5 fw-bold">
            <i class="bi bi-rocket-takeoff-fill me-2"></i>Começar teste grátis de 15 dias
          </a>
          <a href="#planos" class="btn-ghost btn px-4 py-3 fs-6">
            <i class="bi bi-tag-fill me-2"></i>Ver planos e preços
          </a>
        </div>
        <div class="d-flex flex-wrap gap-3 mt-3">
          <a href="<?= url('/login') ?>" class="btn-ghost btn px-4 py-2 fs-6">
            <i class="bi bi-box-arrow-in-right me-2"></i>Já sou cliente · Entrar
          </a>
          <a href="<?= url('/assistencias') ?>" class="btn-ghost btn px-4 py-2 fs-6" style="color:#5eead4;border-color:rgba(94,234,212,.4)">
            <i class="bi bi-geo-alt-fill me-2"></i>Ver o diretório
          </a>
        </div>
        <div class="d-flex flex-wrap gap-4 mt-4">
          <?php foreach (['✓ 15 dias grátis','✓ Sem cartão de crédito','✓ Sem instalação','✓ Suporte humano'] as $item): ?>
          <span style="color:var(--muted);font-size:.85rem"><?= $item ?></span>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="col-lg-6 d-none d-lg-block">
        <div class="dash-mock">
          <div class="dash-topbar">
            <div class="dash-dot" style="background:#ff5f57"></div>
            <div class="dash-dot" style="background:#febc2e"></div>
            <div class="dash-dot" style="background:#28c840"></div>
            <span style="color:#555;font-size:.72rem;margin-left:.5rem">FixaOS — Dashboard</span>
          </div>
          <svg viewBox="0 0 580 320" xmlns="http://www.w3.org/2000/svg" style="width:100%">
            <rect width="580" height="320" fill="#111318"/>
            <!-- Sidebar -->
            <rect width="120" height="320" fill="#0b0d10"/>
            <rect x="10" y="16" width="100" height="30" rx="6" fill="#1e3a5f"/>
            <text x="60" y="35" text-anchor="middle" font-family="Arial Black" font-weight="900" font-size="13" fill="#fff">Fixa<tspan fill="#f97316">OS</tspan></text>
            <?php foreach ([60,88,108,128,148,175,195,215] as $i => $y): ?>
            <rect x="10" y="<?= $y ?>" width="100" height="16" rx="4" fill="<?= $i===0?'rgba(249,115,22,.15)':'transparent' ?>"/>
            <rect x="22" y="<?= $y+4 ?>" width="<?= [72,55,65,60,68,50,58,54][$i] ?>" height="7" rx="3" fill="<?= $i===0?'#f97316':'#374151' ?>"/>
            <?php endforeach; ?>
            <!-- KPI cards -->
            <?php $kpis=[
              [128,12,100,52,'rgba(59,130,246,.15)','#60a5fa','OS Abertas','18'],
              [234,12,100,52,'rgba(34,197,94,.12)','#4ade80','Concluídas','47'],
              [340,12,100,52,'rgba(249,115,22,.12)','#fb923c','Faturamento','R$12k'],
              [446,12,120,52,'rgba(168,85,247,.12)','#c084fc','Ticket Médio','R$268'],
            ];foreach($kpis as[$x,$y,$w,$h,$bg,$c,$l,$v]):?>
            <rect x="<?=$x?>" y="<?=$y?>" width="<?=$w?>" height="<?=$h?>" rx="8" fill="<?=$bg?>"/>
            <text x="<?=$x+8?>" y="<?=$y+16?>" fill="<?=$c?>" font-size="7.5" font-family="Inter,Arial"><?=$l?></text>
            <text x="<?=$x+8?>" y="<?=$y+40?>" fill="#fff" font-size="17" font-weight="bold" font-family="Inter,Arial"><?=$v?></text>
            <?php endforeach;?>
            <!-- Chart -->
            <rect x="128" y="72" width="198" height="110" rx="8" fill="#181b22"/>
            <text x="138" y="88" fill="#6b7280" font-size="7.5" font-family="Inter,Arial">Faturamento — últimos 6 meses</text>
            <?php $bars=[[138,40],[158,65],[178,50],[198,80],[218,72],[238,95]];
            foreach($bars as[$bx,$bh]):?>
            <rect x="<?=$bx?>" y="<?=170-$bh?>" width="14" height="<?=$bh?>" rx="3" fill="rgba(249,115,22,.7)"/>
            <?php endforeach;?>
            <!-- Status pie placeholder -->
            <rect x="334" y="72" width="110" height="110" rx="8" fill="#181b22"/>
            <text x="344" y="88" fill="#6b7280" font-size="7.5" font-family="Inter,Arial">Status</text>
            <circle cx="389" cy="138" r="30" fill="none" stroke="#374151" stroke-width="14"/>
            <circle cx="389" cy="138" r="30" fill="none" stroke="#f97316" stroke-width="14" stroke-dasharray="70 119" stroke-dashoffset="30"/>
            <circle cx="389" cy="138" r="30" fill="none" stroke="#4ade80" stroke-width="14" stroke-dasharray="40 149" stroke-dashoffset="-40"/>
            <circle cx="389" cy="138" r="30" fill="none" stroke="#60a5fa" stroke-width="14" stroke-dasharray="25 164" stroke-dashoffset="-80"/>
            <!-- OS List -->
            <rect x="128" y="190" width="438" height="122" rx="8" fill="#181b22"/>
            <text x="138" y="206" fill="#6b7280" font-size="7.5" font-family="Inter,Arial">Últimas Ordens de Serviço</text>
            <rect x="128" y="210" width="438" height="1" fill="#1f2937"/>
            <?php
            $rows=[
              ['#000201','Samsung TV 55"','Em Reparo','#60a5fa','R$ 380'],
              ['#000200','iPhone 14 Pro','Pronto p/ Retirada','#4ade80','R$ 520'],
              ['#000199','Geladeira Brastemp','Aguard. Peças','#fb923c','R$ 290'],
              ['#000198','Notebook Dell','Em Diagnóstico','#a78bfa','R$ 180'],
            ];
            foreach($rows as $i=>[$num,$equip,$status,$cor,$val]):
              $ry=220+$i*24;
            ?>
            <text x="138" y="<?=$ry+9?>" fill="#9ca3af" font-size="7" font-family="Inter,Arial"><?=$num?></text>
            <text x="195" y="<?=$ry+9?>" fill="#e5e7eb" font-size="7" font-family="Inter,Arial"><?=$equip?></text>
            <rect x="360" y="<?=$ry+1?>" width="80" height="13" rx="4" fill="<?=$cor?>" opacity=".15"/>
            <text x="364" y="<?=$ry+10?>" fill="<?=$cor?>" font-size="6.5" font-family="Inter,Arial"><?=$status?></text>
            <text x="510" y="<?=$ry+9?>" fill="#fff" font-size="7" font-weight="bold" font-family="Inter,Arial"><?=$val?></text>
            <?php endforeach;?>
            <!-- Agenda mini -->
            <rect x="452" y="72" width="114" height="110" rx="8" fill="#181b22"/>
            <text x="462" y="88" fill="#6b7280" font-size="7.5" font-family="Inter,Arial">Agenda hoje</text>
            <?php foreach([
              ['09:00','Retirada — João'],['11:30','Entrega — Maria'],['14:00','Coleta — Pedro'],['16:30','Vistoria — Ana'],
            ] as $i=>[$h,$ev]):
              $ay=100+$i*22;?>
            <rect x="462" y="<?=$ay?>" width="95" height="16" rx="4" fill="rgba(249,115,22,.1)"/>
            <text x="466" y="<?=$ay+10?>" fill="#f97316" font-size="6" font-family="Inter,Arial" font-weight="bold"><?=$h?></text>
            <text x="490" y="<?=$ay+10?>" fill="#9ca3af" font-size="6" font-family="Inter,Arial"><?=$ev?></text>
            <?php endforeach;?>
          </svg>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ═══ PRINCIPAIS RECURSOS (DESTAQUE) ═══ -->
<section style="padding:5rem 0 2rem;background:var(--bg)">
  <div class="container">
    <div class="text-center mb-5">
      <div class="sec-tag">Por que o FixaOS</div>
      <h2 class="sec-title">O essencial da sua oficina,<br>resolvido de ponta a ponta</h2>
      <p class="sec-sub" style="max-width:560px;margin:auto">Seis pilares que fazem a diferença no balcão todos os dias.</p>
    </div>
    <div class="d-flex flex-wrap justify-content-center gap-4">
      <?php foreach ([
        ['bi-clipboard2-check-fill','#60a5fa','Ordem de serviço completa','Abra, acompanhe e feche OS com status personalizáveis, peças, serviços, garantia e impressão de laudo em um clique. O cliente acompanha por um link, sem login.'],
        ['bi-phone-vibrate-fill','#22c55e','IMEI que preenche e protege','Digite o IMEI: o aparelho se cadastra sozinho — marca e modelo — e um clique na Anatel avisa se o celular foi dado como perdido, roubado ou furtado. Menos digitação e menos risco com aparelho de origem duvidosa.'],
        ['bi-camera-fill','#a855f7','Cadastro por foto com IA','Aponte o celular na etiqueta do aparelho — sem app, sem digitar. A IA lê marca, modelo e número de série e preenche a OS no computador na hora. TV, eletrodoméstico, notebook: acabou a digitação e o erro de modelo.'],
        ['bi-whatsapp','#25d366','Orçamento e comprovante no WhatsApp','Gere o PDF do orçamento, laudo ou comprovante e envie direto pelo WhatsApp — o documento de verdade, saindo do número da sua empresa.'],
        ['bi-cash-coin','#fbbf24','PDV e financeiro no caixa','Frente de caixa com baixa de estoque, cupom e a receita caindo no fluxo de caixa. Divida o pagamento em várias formas — parte no crédito parcelado, parte no débito ou PIX — com a taxa calculada na hora.'],
        ['bi-geo-alt-fill','#fb923c','Página no Google que traz cliente','Sua assistência ganha um perfil público no diretório, otimizado pra busca, com mapa e avaliações verificadas. Novos clientes te encontram sozinhos.'],
      ] as [$icon,$c,$t,$d]): ?>
      <div style="flex:1 1 240px;max-width:270px">
        <div class="feat-card h-100">
          <div class="feat-icon" style="background:<?= $c ?>22"><i class="bi <?= $icon ?>" style="color:<?= $c ?>"></i></div>
          <div class="feat-title"><?= $t ?></div>
          <div class="feat-desc"><?= $d ?></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <div class="text-center mt-4">
      <a href="<?= url('/cadastrar') ?>" class="btn-brand btn px-4 py-3 fw-bold"><i class="bi bi-rocket-takeoff-fill me-2"></i>Testar grátis por 15 dias</a>
    </div>
  </div>
</section>

<!-- ═══ FUNCIONALIDADES ═══ -->
<section id="funcionalidades" style="padding:6rem 0;background:var(--bg)">
  <div class="container">
    <div class="text-center mb-5">
      <div class="sec-tag">Funcionalidades</div>
      <h2 class="sec-title">Tudo que você precisa.<br>Nada que você não precisa.</h2>
      <p class="sec-sub" style="max-width:520px;margin:auto">Cada módulo foi pensado para o dia a dia real de uma assistência técnica. Sem complicação.</p>
    </div>
    <div class="feat-grid">
      <?php
      $feats=[
        ['bi-clipboard2-pulse','rgba(59,130,246,.15)','#60a5fa','Ordens de Serviço',
          'Abra, acompanhe e finalize OS com status personalizáveis, serviços, peças e impressão de laudo em um clique.'],
        ['bi-chat-dots-fill','rgba(37,211,102,.14)','#25d366','Chat interno da equipe',
          'Técnico, recepção e admin conversam dentro da própria OS, em tempo real, com alerta sonoro e sino de notificação. Toda a comunicação fica registrada no histórico do serviço.', 'Novo'],
        ['bi-list-columns-reverse','rgba(96,165,250,.14)','#60a5fa','Lista de OS com colapse',
          'Clique numa OS e ela expande ali mesmo — cliente, equipamento e valores sem sair da lista. Colunas enxutas: contato, WhatsApp, status e valor num relance.', 'Novo'],
        ['bi-people-fill','rgba(34,197,94,.12)','#4ade80','CRM de Clientes',
          'Histórico completo de cada cliente, equipamentos anteriores, pipeline de negócios e acompanhamento pós-venda.'],
        ['bi-box-seam','rgba(249,115,22,.12)','#fb923c','Controle de Estoque',
          'Peças, componentes e acessórios com alerta de estoque mínimo, entrada/saída automática nas OS e fornecedores.'],
        ['bi-graph-up-arrow','rgba(168,85,247,.12)','#c084fc','Financeiro Completo',
          'Contas a receber e pagar, fluxo de caixa, comissões por técnico, categorias e DRE simplificado por período.'],
        ['bi-calendar-week','rgba(236,72,153,.12)','#f472b6','Agenda',
          'Calendário de atendimentos, coletas e entregas. Programe e nunca mais esqueça um compromisso com cliente.'],
        ['bi-bar-chart-line-fill','rgba(20,184,166,.12)','#2dd4bf','Relatórios e Gráficos',
          'Dashboard visual com ticket médio, ranking de serviços, status por período e exportação para PDF e impressão.'],
        ['bi-shop','rgba(59,130,246,.12)','#60a5fa','Marketplace de Peças',
          'Compre e venda peças com outras assistências da plataforma. Encontre o que precisa sem sair do sistema.'],
        ['bi-chat-square-text','rgba(251,191,36,.12)','#fbbf24','Fórum Técnico',
          'Compartilhe dicas, soluções de defeitos e experiências com outros técnicos da comunidade FixaOS.'],
        ['bi-printer-fill','rgba(34,197,94,.12)','#4ade80','Impressão Profissional',
          'OS, orçamento, laudo, fechamento e garantia — templates prontos para imprimir ou enviar por WhatsApp.'],
        ['bi-whatsapp','rgba(37,211,102,.12)','#25d366','Acompanhamento pelo Cliente',
          'Cada OS gera um link único enviado pelo WhatsApp. O cliente acompanha status, laudo e valor em tempo real — sem precisar de login.'],
        ['bi-file-earmark-pdf-fill','rgba(37,211,102,.12)','#25d366','Orçamento e comprovante em PDF no WhatsApp',
          'Gere o PDF do orçamento, laudo ou comprovante e envie direto pelo WhatsApp do cliente — não é link, é o documento de verdade, saindo do número da sua empresa.'],
        ['bi-camera-fill','rgba(59,130,246,.15)','#60a5fa','Laudo fotográfico de entrada',
          'Fotografe arranhões, trincas e o estado do aparelho ao receber. As fotos vão por e-mail para você e para o cliente — prova que acaba com discussão. E sem ocupar espaço no sistema.'],
        ['bi-images','rgba(168,85,247,.12)','#c084fc','Fotos de produtos e peças',
          'Cadastre produtos e anúncios com foto tirada na hora pela câmera do celular. Estoque e marketplace muito mais profissionais.'],
        ['bi-envelope-paper-fill','rgba(236,72,153,.12)','#f472b6','E-mail automático',
          'Boas-vindas, notificações e comprovantes enviados automaticamente por e-mail, com o domínio da sua empresa autenticado para não cair no spam.'],
        ['bi-shield-check','rgba(249,115,22,.12)','#fb923c','Garantia e Retorno',
          'Controle completo de equipamentos em garantia com histórico, rastreabilidade e status dedicado.'],
        ['bi-person-gear','rgba(168,85,247,.12)','#c084fc','Usuários e Permissões',
          'Admin, gerente, recepcionista e técnico — cada perfil vê e faz exatamente o que deve. Sem acesso indevido.'],
        ['bi-cloud-upload','rgba(20,184,166,.12)','#2dd4bf','Backup e Segurança',
          'Seus dados sempre seguros. Backup por empresa, dados isolados e acesso protegido por e-mail e senha.'],
        ['bi-geo-alt-fill','rgba(249,115,22,.12)','#fb923c','Diretório Público',
          'Sua assistência tem uma página pública no diretório FixaOS — encontrável no Google com SEO otimizado.'],
        ['bi-search-heart','rgba(59,130,246,.15)','#60a5fa','Busca por Localização',
          'Clientes encontram você por CEP, cidade, estado ou raio de distância. Mapa interativo com pins das assistências.'],
        ['bi-star-fill','rgba(251,191,36,.12)','#fbbf24','Avaliações de Clientes',
          'Receba avaliações reais de clientes no seu perfil. Moderação pelo admin antes de publicar. Aumenta sua credibilidade.'],
        ['bi-megaphone-fill','rgba(34,197,94,.12)','#4ade80','Anúncios no Diretório',
          'Compre destaque no topo do diretório ou um slot de banner. Apareça primeiro e atraia mais clientes.'],
        ['bi-camera-fill','rgba(192,132,252,.15)','#c084fc','Cadastro por foto — a IA lê a etiqueta',
          'Aponte o celular na etiqueta do aparelho e a IA preenche marca, modelo e número de série sozinha no computador — sem app, sem login, só escanear o QR. Funciona com TV, eletrodoméstico, notebook e o que tiver etiqueta. Menos digitação, zero erro de modelo.', 'Novo'],
        ['bi-kanban-fill','rgba(168,85,247,.12)','#c084fc','Status do jeito da sua oficina',
          'Já vem com um fluxo pronto e testado: orçamento, em análise, aguardando peças, pronto e entregue. Não gostou? Crie, renomeie, escolha a cor e a ordem dos seus próprios status. Cada oficina trabalha do seu jeito.', 'Novo'],
        ['bi-phone-vibrate-fill','rgba(0,150,64,.14)','#22c55e','Consulta de IMEI + Anti-roubo',
          'Digite o IMEI e o aparelho se cadastra sozinho — marca e modelo preenchidos automaticamente. Um clique consulta a Anatel e mostra se o celular foi dado como perdido, roubado ou furtado. Você evita colocar a mão em aparelho de origem duvidosa.', 'Novo'],
        ['bi-person-vcard-fill','rgba(59,130,246,.14)','#60a5fa','Validação de CPF e CNPJ',
          'O sistema confere os dígitos verificadores na hora do cadastro e bloqueia documentos inválidos. Sua base de clientes nasce limpa, sem CPF ou CNPJ digitado errado.', 'Novo'],
        ['bi-shield-lock-fill','rgba(239,68,68,.14)','#f87171','Exclusão de OS protegida por senha',
          'Só o administrador exclui uma OS — e ainda precisa digitar a própria senha de login para confirmar. Cada exclusão fica registrada (quem e quando). Fim do apagão acidental.', 'Novo'],
        ['bi-cash-coin','rgba(34,197,94,.12)','#4ade80','Comissão de técnico',
          'Defina o percentual de cada técnico e lance a comissão puxando automático do valor da OS, ou na mão quando precisar. Marcou como paga? Já cai sozinho no Financeiro.', 'Novo'],
        ['bi-credit-card-2-front-fill','rgba(168,85,247,.12)','#c084fc','Pagamento dividido',
          'Fecha a OS ou vende no PDV misturando dinheiro, PIX, débito e crédito na mesma venda. A taxa de cada cartão é calculada e lançada sozinha no Financeiro.', 'Novo'],
        ['bi-search','rgba(99,102,241,.14)','#818cf8','Busca global',
          'Digite o número da OS, nome do cliente ou produto na barra do topo e ache na hora, de qualquer tela do sistema.', 'Novo'],
        ['bi-wifi-off','rgba(20,184,166,.12)','#2dd4bf','Modo offline',
          'Caiu a internet? Você ainda consulta as OS do dia e cria rascunhos, que sincronizam sozinhos assim que a conexão voltar.', 'Novo'],
        ['bi-upc-scan','rgba(20,184,166,.12)','#2dd4bf','Scanner de código de barras',
          'Aponte a câmera do celular para o código do produto e cadastre em segundos — sem digitar nada.', 'Em breve'],
        ['bi-phone-fill','rgba(249,115,22,.12)','#fb923c','Cadastro relâmpago no celular',
          'Fluxo otimizado para abrir OS e cadastrar produtos direto do balcão, pelo celular, em poucos toques.', 'Em breve'],
        ['bi-robot','rgba(59,130,246,.12)','#60a5fa','Automações de WhatsApp',
          'Avisos automáticos de "orçamento pronto", "equipamento pronto para retirada" e lembretes — direto no WhatsApp do cliente.', 'Em breve'],
      ];
      foreach($feats as $f):
        [$icon,$bg,$c,$title,$desc] = $f;
        $badge = $f[5] ?? null;?>
      <div class="feat-card" style="position:relative">
        <?php if($badge): $bc = ($badge==='Novo') ? ['rgba(34,197,94,.18)','#4ade80'] : ['rgba(249,115,22,.15)','#fb923c'];?>
        <span style="position:absolute;top:14px;right:14px;background:<?=$bc[0]?>;color:<?=$bc[1]?>;font-size:.6rem;font-weight:800;padding:.18rem .55rem;border-radius:20px;letter-spacing:.04em;text-transform:uppercase"><?=$badge?></span>
        <?php endif;?>
        <div class="feat-icon" style="background:<?=$bg?>">
          <i class="bi <?=$icon?>" style="color:<?=$c?>"></i>
        </div>
        <div class="feat-title"><?=$title?></div>
        <div class="feat-desc"><?=$desc?></div>
      </div>
      <?php endforeach;?>
    </div>
  </div>
</section>

<!-- ═══ CADASTRO POR FOTO — IA LÊ A ETIQUETA ═══ -->
<section style="padding:6rem 0;background:var(--bg);border-top:1px solid var(--border)">
  <div class="container">
    <div class="row align-items-center g-5">

      <!-- Texto -->
      <div class="col-lg-5">
        <span class="sec-tag" style="background:rgba(192,132,252,.14);border:1px solid rgba(192,132,252,.3);color:#c084fc;display:inline-block;padding:.25rem .7rem;border-radius:20px">✨ Novidade · cadastro por foto</span>
        <h2 class="sec-title" style="margin-top:.8rem">Cadastre o aparelho<br><em style="font-style:normal;color:#c084fc">tirando uma foto</em></h2>
        <p class="sec-sub">Aponte o celular na etiqueta — <strong style="color:#fff">sem app, sem login</strong>, só escanear o QR. A inteligência artificial lê <strong style="color:#fff">marca, modelo e número de série</strong> e preenche a OS no computador na hora. Adeus digitação, adeus modelo errado.</p>

        <div class="d-flex flex-column gap-3 mt-4">
          <?php foreach([
            ['bi-qr-code-scan','#c084fc','Sem app e sem login','Escaneia o QR na tela do PC e a câmera abre na hora — iPhone ou Android. Nada pra instalar, nada pra digitar.'],
            ['bi-stars','#fbbf24','A IA lê a etiqueta','Marca, modelo e número de série preenchidos sozinhos no computador. Lê até etiqueta pequena, apagada ou de lado.'],
            ['bi-tv','#60a5fa','TV, eletro, notebook…','Funciona com qualquer aparelho que tenha etiqueta. Pra celular, o IMEI faz o mesmo com um número.'],
            ['bi-lightning-charge-fill','#22c55e','Segundos, sem erro','Modelo certo é peça certa — você para de pedir a peça errada. Menos digitação, menos retrabalho.'],
          ] as[$icon,$ic,$t,$d]):?>
          <div class="d-flex gap-3 align-items-start">
            <div style="width:38px;height:38px;border-radius:10px;background:rgba(255,255,255,.05);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;flex-shrink:0">
              <i class="bi <?=$icon?>" style="color:<?=$ic?>"></i>
            </div>
            <div>
              <div style="color:#fff;font-weight:600;font-size:.9rem"><?=$t?></div>
              <div style="color:var(--muted);font-size:.83rem;margin-top:.2rem"><?=$d?></div>
            </div>
          </div>
          <?php endforeach;?>
        </div>

        <a href="<?= url('/cadastrar') ?>" class="btn-brand btn mt-4"><i class="bi bi-camera-fill me-2"></i>Testar grátis por 15 dias</a>
      </div>

      <!-- Mockup: celular fotografa a etiqueta -> preenche no PC -->
      <div class="col-lg-7">
        <div style="background:var(--bg2);border:1px solid var(--border);border-radius:20px;overflow:hidden;box-shadow:0 30px 60px rgba(0,0,0,.4)">
          <div style="background:#0b0d10;padding:.45rem 1rem;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:.5rem">
            <span style="background:rgba(192,132,252,.15);color:#c084fc;font-size:.62rem;font-weight:800;padding:.15rem .55rem;border-radius:20px;letter-spacing:.03em">EXEMPLO ILUSTRATIVO</span>
            <span style="color:#4b5563;font-size:.7rem">a foto da etiqueta preenchendo a OS no computador</span>
          </div>

          <div style="background:#fff;padding:1.3rem">
            <div style="display:flex;gap:1rem;flex-wrap:wrap;align-items:center;justify-content:center">

              <!-- Celular fotografando a etiqueta -->
              <div style="flex:0 0 auto;width:170px">
                <div style="position:relative;background:#0f172a;border-radius:22px;padding:8px;box-shadow:0 12px 30px rgba(0,0,0,.25)">
                  <div style="position:absolute;top:12px;left:50%;transform:translateX(-50%);width:44px;height:5px;background:#334155;border-radius:10px;z-index:2"></div>
                  <div style="background:#0b1220;border-radius:16px;overflow:hidden;aspect-ratio:9/16;display:flex;flex-direction:column">
                    <div style="flex:1;position:relative;display:flex;align-items:center;justify-content:center;background:radial-gradient(circle at 50% 42%, #1f2937, #0b1220)">
                      <div style="transform:rotate(-4deg);background:#f8fafc;border-radius:4px;padding:8px 9px;width:116px;box-shadow:0 6px 14px rgba(0,0,0,.45)">
                        <div style="font-weight:900;color:#a50034;font-size:.72rem;letter-spacing:.02em">LG</div>
                        <div style="color:#0f172a;font-size:.5rem;margin-top:3px;line-height:1.6">MODELO: <b>32LE5500</b><br>Nº SÉRIE:<br><b>011AZWSAT761</b></div>
                        <div style="display:flex;gap:1px;margin-top:5px">
                          <?php for($i=0;$i<25;$i++): ?><span style="width:<?= ($i%3)?'1px':'2px' ?>;height:12px;background:#0f172a;display:inline-block"></span><?php endfor; ?>
                        </div>
                      </div>
                      <span style="position:absolute;top:14px;left:14px;width:15px;height:15px;border-top:2px solid #c084fc;border-left:2px solid #c084fc"></span>
                      <span style="position:absolute;top:14px;right:14px;width:15px;height:15px;border-top:2px solid #c084fc;border-right:2px solid #c084fc"></span>
                      <span style="position:absolute;bottom:14px;left:14px;width:15px;height:15px;border-bottom:2px solid #c084fc;border-left:2px solid #c084fc"></span>
                      <span style="position:absolute;bottom:14px;right:14px;width:15px;height:15px;border-bottom:2px solid #c084fc;border-right:2px solid #c084fc"></span>
                    </div>
                    <div style="background:#0b1220;padding:8px;text-align:center">
                      <span style="display:inline-flex;align-items:center;gap:4px;background:rgba(34,197,94,.16);color:#4ade80;font-size:.58rem;font-weight:800;padding:.25rem .6rem;border-radius:20px"><i class="bi bi-check-circle-fill"></i> Etiqueta lida</span>
                    </div>
                  </div>
                </div>
                <div style="text-align:center;color:#94a3b8;font-size:.62rem;margin-top:.5rem"><i class="bi bi-qr-code-scan"></i> escaneou o QR · sem login</div>
              </div>

              <!-- Seta -->
              <div style="flex:0 0 auto;color:#c084fc;text-align:center" class="d-none d-md-block">
                <i class="bi bi-arrow-right" style="font-size:1.5rem"></i>
                <div style="font-size:.58rem;color:#94a3b8;font-weight:800;margin-top:2px;text-transform:uppercase;letter-spacing:.05em">IA lê</div>
              </div>

              <!-- Campos preenchendo no PC -->
              <div style="flex:1 1 240px;min-width:228px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:14px;padding:1rem">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:.7rem">
                  <span style="font-weight:800;color:#0f172a;font-size:.8rem"><i class="bi bi-pc-display" style="color:#64748b"></i> Novo equipamento</span>
                  <span style="background:rgba(192,132,252,.15);color:#7c3aed;font-size:.56rem;font-weight:800;padding:.15rem .5rem;border-radius:20px">✓ pela foto · 3s</span>
                </div>
                <?php foreach([
                  ['Tipo','TV de LED', false],
                  ['Marca','LG', true],
                  ['Modelo','32LE5500', true],
                  ['Nº de série','011AZWSAT761', true],
                ] as [$lab,$val,$auto]): ?>
                <div style="margin-bottom:.5rem">
                  <div style="font-size:.58rem;color:#94a3b8;font-weight:800;text-transform:uppercase;letter-spacing:.04em"><?=$lab?></div>
                  <div style="display:flex;align-items:center;gap:.4rem;background:<?= $auto?'#dcfce7':'#fff' ?>;border:1px solid <?= $auto?'#86efac':'#e2e8f0' ?>;border-radius:8px;padding:.4rem .6rem;margin-top:.2rem">
                    <span style="font-weight:700;color:#0f172a;font-size:.8rem;flex:1"><?=$val?></span>
                    <?php if($auto): ?><i class="bi bi-check-circle-fill" style="color:#16a34a;font-size:.8rem"></i><?php endif; ?>
                  </div>
                </div>
                <?php endforeach; ?>
              </div>

            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</section>
<!-- ═══ NOVIDADES — CHAT INTERNO + LISTA COM COLAPSE ═══ -->
<section style="padding:6rem 0;background:var(--bg2);border-top:1px solid var(--border);border-bottom:1px solid var(--border)">
  <div class="container">
    <div class="row align-items-center g-5">

      <!-- Texto -->
      <div class="col-lg-5">
        <div class="sec-tag" style="background:rgba(34,197,94,.14);border-color:rgba(34,197,94,.3);color:#4ade80">✨ Novidade · exclusivo do FixaOS</div>
        <h2 class="sec-title">Sua equipe conversa<br><em>dentro da própria OS</em></h2>
        <p class="sec-sub">Nenhum outro sistema de assistência tem isso. O técnico, a recepção e o admin trocam mensagens <strong style="color:#fff">amarradas a cada ordem de serviço</strong> — e a lista de OS abre com um clique, sem trocar de tela.</p>

        <div class="d-flex flex-column gap-3 mt-4">
          <?php foreach([
            ['bi-chat-dots-fill','#25d366','Chat da equipe por OS','Recepção pergunta, técnico responde — tudo registrado no histórico daquele serviço. Fim do "quem falou o quê" perdido no WhatsApp pessoal.'],
            ['bi-bell-fill','#fbbf24','Alerta sonoro e sino','Chegou mensagem nova? Sino no topo com contador e um bip discreto. Ninguém deixa o colega no vácuo.'],
            ['bi-list-columns-reverse','#60a5fa','Lista de OS com colapse','Clique na linha e ela expande ali mesmo: cliente, equipamento, marca/modelo e valores. Sem abrir página nova.'],
            ['bi-eye-fill','#c084fc','Colunas no ponto certo','OS, contato, WhatsApp, status e valor num relance. Valor zerado vira "Pendente". Ações de ver, imprimir e WhatsApp na mão.'],
          ] as[$icon,$c,$t,$d]):?>
          <div class="d-flex gap-3 align-items-start">
            <div style="width:38px;height:38px;border-radius:10px;background:rgba(255,255,255,.05);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;flex-shrink:0">
              <i class="bi <?=$icon?>" style="color:<?=$c?>"></i>
            </div>
            <div>
              <div style="color:#fff;font-weight:600;font-size:.9rem"><?=$t?></div>
              <div style="color:var(--muted);font-size:.83rem;margin-top:.2rem"><?=$d?></div>
            </div>
          </div>
          <?php endforeach;?>
        </div>
      </div>

      <!-- Mockup: lista com colapse + chat da equipe -->
      <div class="col-lg-7">
        <div style="background:var(--bg2);border:1px solid var(--border);border-radius:20px;overflow:hidden;box-shadow:0 30px 60px rgba(0,0,0,.4)">

          <!-- Selo -->
          <div style="background:#0b0d10;padding:.45rem 1rem;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:.5rem">
            <span style="background:rgba(34,197,94,.15);color:#4ade80;font-size:.62rem;font-weight:800;padding:.15rem .55rem;border-radius:20px;letter-spacing:.03em">EXEMPLO ILUSTRATIVO</span>
            <span style="color:#4b5563;font-size:.7rem">prévia da lista de OS com o chat da equipe aberto</span>
          </div>

          <div style="background:#fff;padding:1.1rem">

            <!-- Cabeçalho tabela -->
            <div style="display:flex;align-items:center;gap:.6rem;padding:.4rem .6rem;font-size:.66rem;font-weight:800;color:#94a3b8;text-transform:uppercase;letter-spacing:.03em">
              <span style="width:14px"></span>
              <span style="width:52px">OS</span>
              <span style="flex:1">Contato</span>
              <span style="width:96px" class="d-none d-sm-block">WhatsApp</span>
              <span style="width:96px">Status</span>
              <span style="width:64px;text-align:right">Valor</span>
            </div>

            <!-- Linha 1 (recolhida) -->
            <div style="display:flex;align-items:center;gap:.6rem;padding:.6rem;border-top:1px solid #eef2f6;color:#0f172a;font-size:.8rem">
              <i class="bi bi-chevron-right" style="color:#cbd5e1;font-size:.7rem"></i>
              <span style="width:52px;font-weight:800">5201</span>
              <span style="flex:1"><span style="font-weight:600">João Silva</span><br><span style="color:#94a3b8;font-size:.68rem">Samsung · A54</span></span>
              <span style="width:96px;color:#25d366" class="d-none d-sm-block"><i class="bi bi-whatsapp"></i> 11 9****-**21</span>
              <span style="width:96px"><span style="background:#dbeafe;color:#1d4ed8;font-size:.66rem;font-weight:700;padding:.12rem .5rem;border-radius:20px">Orçamento</span></span>
              <span style="width:64px;text-align:right;color:#f59e0b;font-weight:700;font-size:.72rem">Pendente</span>
            </div>

            <!-- Linha 2 (EXPANDIDA) -->
            <div style="border-top:1px solid #eef2f6;border-left:3px solid #16a34a;background:#f8fafc;border-radius:0 8px 8px 0">
              <div style="display:flex;align-items:center;gap:.6rem;padding:.6rem;color:#0f172a;font-size:.8rem">
                <i class="bi bi-chevron-down" style="color:#16a34a;font-size:.7rem"></i>
                <span style="width:52px;font-weight:800">5199</span>
                <span style="flex:1"><span style="font-weight:600">Maria Souza</span><br><span style="color:#94a3b8;font-size:.68rem">Apple · iPhone 12</span></span>
                <span style="width:96px;color:#25d366" class="d-none d-sm-block"><i class="bi bi-whatsapp"></i> 11 9****-**08</span>
                <span style="width:96px"><span style="background:#dcfce7;color:#15803d;font-size:.66rem;font-weight:700;padding:.12rem .5rem;border-radius:20px">Pronto p/ Retirada</span></span>
                <span style="width:64px;text-align:right;color:#16a34a;font-weight:800;font-size:.72rem">R$ 280</span>
              </div>

              <!-- Painel expandido -->
              <div style="padding:0 .8rem .9rem 1.6rem">
                <div style="display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:.7rem">
                  <span style="background:#eef2f6;color:#475569;font-size:.66rem;border-radius:6px;padding:.2rem .5rem"><i class="bi bi-phone"></i> iPhone 12 · 128GB</span>
                  <span style="background:#eef2f6;color:#475569;font-size:.66rem;border-radius:6px;padding:.2rem .5rem"><i class="bi bi-tools"></i> Troca de tela</span>
                  <span style="background:#eef2f6;color:#475569;font-size:.66rem;border-radius:6px;padding:.2rem .5rem"><i class="bi bi-shield-check"></i> Garantia 90 dias</span>
                </div>

                <!-- Chat da equipe -->
                <div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:.7rem">
                  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:.55rem">
                    <span style="font-weight:800;font-size:.72rem;color:#0f172a"><i class="bi bi-chat-dots-fill" style="color:#25d366"></i> Conversa da equipe</span>
                    <span style="display:inline-flex;align-items:center;gap:.25rem;color:#64748b;font-size:.64rem"><span style="position:relative"><i class="bi bi-bell-fill" style="color:#fbbf24"></i><span style="position:absolute;top:-5px;right:-6px;background:#ef4444;color:#fff;font-size:.5rem;font-weight:800;border-radius:20px;padding:0 .28rem">2</span></span>2 novas</span>
                  </div>

                  <!-- bolha recepção -->
                  <div style="display:flex;gap:.45rem;margin-bottom:.5rem">
                    <div style="width:24px;height:24px;border-radius:50%;background:#1e3a5f;color:#fff;font-size:.6rem;font-weight:800;display:flex;align-items:center;justify-content:center;flex-shrink:0">R</div>
                    <div>
                      <div style="font-size:.6rem;color:#94a3b8;margin-bottom:.1rem">Recepção · agora</div>
                      <div style="background:#f1f5f9;color:#334155;font-size:.72rem;border-radius:10px 10px 10px 2px;padding:.35rem .6rem;max-width:230px">Cliente ligou perguntando se já pode buscar. Ficou pronto?</div>
                    </div>
                  </div>

                  <!-- bolha técnico -->
                  <div style="display:flex;gap:.45rem;justify-content:flex-end">
                    <div style="text-align:right">
                      <div style="font-size:.6rem;color:#94a3b8;margin-bottom:.1rem">Você (Técnico) · agora</div>
                      <div style="background:#dcfce7;color:#166534;font-size:.72rem;border-radius:10px 10px 2px 10px;padding:.35rem .6rem;max-width:230px">Testado e aprovado ✅ Pode liberar pra retirada!</div>
                    </div>
                    <div style="width:24px;height:24px;border-radius:50%;background:#16a34a;color:#fff;font-size:.6rem;font-weight:800;display:flex;align-items:center;justify-content:center;flex-shrink:0">T</div>
                  </div>

                  <!-- campo digitar -->
                  <div style="display:flex;align-items:center;gap:.4rem;background:#f8fafc;border:1px solid #e2e8f0;border-radius:20px;padding:.3rem .7rem;margin-top:.55rem">
                    <span style="color:#94a3b8;font-size:.7rem;flex:1">Escreva pra equipe…</span>
                    <span style="width:22px;height:22px;border-radius:50%;background:#16a34a;color:#fff;display:flex;align-items:center;justify-content:center;font-size:.62rem"><i class="bi bi-send-fill"></i></span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Linha 3 (recolhida) -->
            <div style="display:flex;align-items:center;gap:.6rem;padding:.6rem;border-top:1px solid #eef2f6;color:#0f172a;font-size:.8rem">
              <i class="bi bi-chevron-right" style="color:#cbd5e1;font-size:.7rem"></i>
              <span style="width:52px;font-weight:800">5198</span>
              <span style="flex:1"><span style="font-weight:600">Carlos Lima</span><br><span style="color:#94a3b8;font-size:.68rem">Positivo · Notebook</span></span>
              <span style="width:96px;color:#25d366" class="d-none d-sm-block"><i class="bi bi-whatsapp"></i> 11 9****-**77</span>
              <span style="width:96px"><span style="background:#fef9c3;color:#a16207;font-size:.66rem;font-weight:700;padding:.12rem .5rem;border-radius:20px">Em Reparo</span></span>
              <span style="width:64px;text-align:right;color:#16a34a;font-weight:800;font-size:.72rem">R$ 150</span>
            </div>

          </div>
        </div>
      </div>

    </div>
  </div>
</section>

<!-- ═══ COMO FUNCIONA ═══ -->
<section id="como-funciona" style="padding:6rem 0;background:var(--bg2);border-top:1px solid var(--border);border-bottom:1px solid var(--border)">
  <div class="container">
    <div class="row align-items-center g-5">
      <div class="col-lg-5">
        <div class="sec-tag">Como funciona</div>
        <h2 class="sec-title">Pronto para usar<br>em menos de 5 minutos</h2>
        <p class="sec-sub">Sem instalação, sem técnico de TI, sem burocracia. Você cria a conta e já começa a abrir OS.</p>
      </div>
      <div class="col-lg-7">
        <div class="d-flex flex-column gap-4">
          <?php
          $steps=[
            ['1','Crie sua conta','Cadastre a assistência técnica gratuitamente. Pode ser com e-mail ou Google — leva 2 minutos.'],
            ['2','Configure do seu jeito','Adicione técnicos, personalize os status das OS, coloque o logo da sua empresa.'],
            ['3','Abra a primeira OS','Cadastre o cliente, o equipamento e o defeito. O sistema cuida do resto: workflow, impressão, histórico.'],
            ['4','Acompanhe tudo no dashboard','Veja em tempo real quantas OS estão abertas, concluídas, quanto entrou no caixa e muito mais.'],
            ['5','Cliente acompanha pelo WhatsApp','Envie um link único para o cliente acompanhar o status da OS em tempo real — sem login, sem complicação.'],
          ];
          foreach($steps as[$n,$t,$d]):?>
          <div class="step-wrap">
            <div class="step-num"><?=$n?></div>
            <div>
              <div style="color:#fff;font-weight:700;font-size:.98rem;margin-bottom:.3rem"><?=$t?></div>
              <div style="color:var(--muted);font-size:.87rem;line-height:1.6"><?=$d?></div>
            </div>
          </div>
          <?php endforeach;?>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ═══ DIRETÓRIO ═══ -->
<section style="padding:6rem 0;background:var(--bg)">
  <div class="container">
    <div class="row align-items-center g-5">

      <!-- Texto -->
      <div class="col-lg-5">
        <div class="sec-tag">Novo — Diretório de Assistências</div>
        <h2 class="sec-title">Seus clientes te<br>encontram no Google</h2>
        <p class="sec-sub">Cada assistência cadastrada no FixaOS ganha uma <strong style="color:#fff">página pública própria</strong> — com SEO otimizado, mapa, avaliações e botão de contato direto.</p>

        <div class="d-flex flex-column gap-3 mt-4">
          <?php foreach([
            ['bi-geo-alt-fill','#fb923c','Busca por localização','Clientes filtram por CEP, cidade e raio de distância até encontrar a assistência mais perto.'],
            ['bi-star-fill','#fbbf24','Avaliações verificadas','Clientes avaliam com nota e comentário. Você modera antes de publicar.'],
            ['bi-graph-up-arrow','#4ade80','SEO para o Google','Schema.org LocalBusiness, Open Graph e URL amigável. Apareça nas buscas orgânicas.'],
            ['bi-megaphone-fill','#60a5fa','Anúncios e destaques','Compre o topo do diretório ou um slot de banner para aparecer primeiro.'],
          ] as[$icon,$c,$t,$d]):?>
          <div class="d-flex gap-3 align-items-start">
            <div style="width:38px;height:38px;border-radius:10px;background:rgba(255,255,255,.05);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;flex-shrink:0">
              <i class="bi <?=$icon?>" style="color:<?=$c?>"></i>
            </div>
            <div>
              <div style="color:#fff;font-weight:600;font-size:.9rem"><?=$t?></div>
              <div style="color:var(--muted);font-size:.83rem;margin-top:.2rem"><?=$d?></div>
            </div>
          </div>
          <?php endforeach;?>
        </div>

        <div class="d-flex gap-3 mt-4 flex-wrap">
          <a href="<?= url('/assistencias') ?>" target="_blank" class="btn-brand btn px-4 py-2">
            <i class="bi bi-geo-alt-fill me-2"></i>Ver o diretório
          </a>
          <a href="<?= url('/assistencias') ?>" target="_blank" class="btn-ghost btn px-4 py-2">
            <i class="bi bi-search me-2"></i>Buscar por CEP
          </a>
        </div>
      </div>

      <!-- Mockup do diretório -->
      <div class="col-lg-7">
        <div style="background:var(--bg2);border:1px solid var(--border);border-radius:20px;overflow:hidden;box-shadow:0 30px 60px rgba(0,0,0,.4)">

          <!-- Selo de exemplo -->
          <div style="background:#0b0d10;padding:.45rem 1rem;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:.5rem">
            <span style="background:rgba(249,115,22,.15);color:#fb923c;font-size:.62rem;font-weight:800;padding:.15rem .55rem;border-radius:20px;letter-spacing:.03em">EXEMPLO ILUSTRATIVO</span>
            <span style="color:#4b5563;font-size:.7rem">prévia de como fica a tela do diretório</span>
          </div>

          <!-- Barra de busca mockup -->
          <div style="background:#111318;padding:1rem 1.5rem;border-bottom:1px solid var(--border)">
            <div style="display:flex;align-items:center;gap:.5rem;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);border-radius:10px;padding:.5rem 1rem;margin-bottom:.6rem">
              <i class="bi bi-search" style="color:#f97316"></i>
              <span style="color:#6b7280;font-size:.85rem">Buscar assistência por nome ou serviço…</span>
            </div>
            <div style="display:flex;gap:.4rem;flex-wrap:wrap">
              <?php foreach([['bi-geo-alt','CEP 01310-100'],['bi-building','São Paulo/SP'],['bi-tools','Celular'],['bi-circle','Raio 20km'],['bi-sort-down','Mais próximas']] as [$ic,$tx]): ?>
              <span style="display:inline-flex;align-items:center;gap:.35rem;background:rgba(249,115,22,.12);border:1px solid rgba(249,115,22,.25);color:#fb923c;border-radius:20px;font-size:.72rem;font-weight:600;padding:.25rem .7rem"><i class="bi <?=$ic?>"></i><?=$tx?></span>
              <?php endforeach; ?>
            </div>
          </div>

          <!-- Cards mockup -->
          <div style="padding:1.2rem">
            <?php
            $cards=[
              ['T','Timetec Assistência Técnica','Santana, São Paulo/SP','5.0','12','premium','Celular, Notebook, TV, Geladeira'],
              ['E','ElectroFix Centro','Centro, São Paulo/SP','4.8','8','destaque','Smartphone, Tablet, Notebook'],
              ['S','SmartRepair Vila Madalena','V. Madalena, São Paulo/SP','4.6','5','','Celular, Ar Condicionado'],
            ];
            foreach($cards as $i=>[$ini,$nome,$loc,$nota,$avals,$badge,$servs]):?>
            <div style="background:<?=$i===0?'#fff':'#f8fafc'?>;border:<?=$badge==='premium'?'1.5px solid #f59e0b':($badge==='destaque'?'1.5px solid #f97316':'1px solid #e2e8f0')?>;border-radius:12px;padding:1rem;display:flex;align-items:center;gap:1rem;margin-bottom:.8rem;<?=$i===0?'box-shadow:0 4px 16px rgba(0,0,0,.08)':''?>">
              <div style="width:48px;height:48px;border-radius:10px;background:#1e3a5f;display:flex;align-items:center;justify-content:center;font-size:1.3rem;font-weight:900;color:#fff;flex-shrink:0"><?=$ini?></div>
              <div style="flex:1;min-width:0">
                <div style="display:flex;align-items:center;gap:.5rem;flex-wrap:wrap">
                  <span style="font-weight:800;font-size:.92rem;color:#0f172a"><?=$nome?></span>
                  <?php if($badge==='premium'): ?><span style="background:linear-gradient(135deg,#f59e0b,#d97706);color:#fff;font-size:.62rem;font-weight:800;padding:.15rem .55rem;border-radius:20px">⭐ PREMIUM</span>
                  <?php elseif($badge==='destaque'): ?><span style="background:#f97316;color:#fff;font-size:.62rem;font-weight:800;padding:.15rem .55rem;border-radius:20px">🔥 DESTAQUE</span><?php endif;?>
                </div>
                <div style="color:#64748b;font-size:.75rem;margin:.15rem 0"><i class="bi bi-geo-alt-fill" style="color:#f97316"></i> <?=$loc?> · <i class="bi bi-arrow-up-right"></i> <?=$i===0?'0.8':($i===1?'2.3':'4.1')?> km</div>
                <div style="display:flex;flex-wrap:wrap;gap:.2rem;margin-top:.3rem">
                  <?php foreach(explode(',',$servs) as $s):?>
                  <span style="background:#f1f5f9;color:#475569;border-radius:4px;font-size:.65rem;padding:.1rem .4rem"><?=trim($s)?></span>
                  <?php endforeach;?>
                </div>
              </div>
              <div style="text-align:right;flex-shrink:0">
                <div style="color:#94a3b8;font-size:.72rem;line-height:1.2">Sem<br>avaliações</div>
              </div>
            </div>
            <?php endforeach;?>

            <!-- Mini mapa mockup -->
            <div style="background:#e8f0e0;border-radius:10px;height:80px;display:flex;align-items:center;justify-content:center;border:1px solid #d1e0c4;position:relative;overflow:hidden">
              <div style="position:absolute;inset:0;background:repeating-linear-gradient(0deg,transparent,transparent 18px,rgba(200,220,180,.4) 18px,rgba(200,220,180,.4) 19px),repeating-linear-gradient(90deg,transparent,transparent 22px,rgba(200,220,180,.4) 22px,rgba(200,220,180,.4) 23px)"></div>
              <?php foreach([['20%','40%','#f97316'],['50%','55%','#1e3a5f'],['72%','35%','#1e3a5f']] as[$l,$t,$c]):?>
              <div style="position:absolute;left:<?=$l?>;top:<?=$t?>;width:28px;height:28px;background:<?=$c?>;border-radius:50%;border:3px solid #fff;box-shadow:0 2px 8px rgba(0,0,0,.3);display:flex;align-items:center;justify-content:center">
                <i class="bi bi-tools" style="color:#fff;font-size:.6rem"></i>
              </div>
              <?php endforeach;?>
              <span style="background:#fff;padding:.3rem .8rem;border-radius:20px;font-size:.72rem;font-weight:700;color:#374151;box-shadow:0 2px 8px rgba(0,0,0,.15);position:relative;z-index:1"><i class="bi bi-map-fill me-1" style="color:#f97316"></i>Mapa interativo</span>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</section>

<!-- ═══ MARKETPLACE ═══ -->
<section style="padding:6rem 0;background:var(--bg2);border-top:1px solid var(--border);border-bottom:1px solid var(--border)">
  <div class="container">
    <div class="row align-items-center g-5">

      <!-- Mockup do marketplace -->
      <div class="col-lg-6 order-lg-1 order-2">
        <div style="background:var(--bg);border:1px solid var(--border);border-radius:20px;overflow:hidden;box-shadow:0 30px 60px rgba(0,0,0,.4)">

          <!-- Header -->
          <div style="background:#111318;padding:1rem 1.5rem;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between">
            <div style="display:flex;align-items:center;gap:.5rem">
              <i class="bi bi-shop" style="color:#f97316"></i>
              <span style="color:#fff;font-weight:700;font-size:.88rem">Marketplace de Peças</span>
            </div>
            <span style="background:rgba(34,197,94,.15);color:#4ade80;border-radius:20px;font-size:.7rem;font-weight:700;padding:.2rem .7rem">247 peças disponíveis</span>
          </div>

          <!-- Cards de peças -->
          <div style="padding:1.2rem;display:grid;grid-template-columns:1fr 1fr;gap:.8rem">
            <?php
            $pecas=[
              ['Display iPhone 14','Compatível com Pro e Pro Max','R$ 180,00','#60a5fa','bi-phone'],
              ['Placa Mãe Samsung A54','Original desmontada, testada','R$ 320,00','#4ade80','bi-cpu'],
              ['Compressor Geladeira','Embraco 1/5 HP, novo','R$ 490,00','#fb923c','bi-snow'],
              ['Bateria MacBook Air M2','Cycle count 12, original','R$ 650,00','#c084fc','bi-battery-full'],
            ];
            foreach($pecas as[$titulo,$desc,$preco,$c,$icon]):?>
            <div style="background:var(--bg2);border:1px solid var(--border);border-radius:12px;padding:.9rem;transition:.2s">
              <div style="width:36px;height:36px;border-radius:8px;background:<?=$c?>22;display:flex;align-items:center;justify-content:center;margin-bottom:.6rem">
                <i class="bi <?=$icon?>" style="color:<?=$c?>;font-size:1rem"></i>
              </div>
              <div style="color:#fff;font-weight:700;font-size:.82rem;margin-bottom:.2rem;display:-webkit-box;-webkit-line-clamp:1;-webkit-box-orient:vertical;overflow:hidden"><?=$titulo?></div>
              <div style="color:var(--muted);font-size:.72rem;margin-bottom:.5rem;display:-webkit-box;-webkit-line-clamp:1;-webkit-box-orient:vertical;overflow:hidden"><?=$desc?></div>
              <div style="display:flex;align-items:center;justify-content:space-between">
                <span style="color:#4ade80;font-weight:800;font-size:.88rem"><?=$preco?></span>
                <span style="background:rgba(249,115,22,.15);color:#fb923c;border-radius:6px;font-size:.65rem;font-weight:700;padding:.15rem .5rem">
                  <i class="bi bi-whatsapp me-1"></i>Chat
                </span>
              </div>
            </div>
            <?php endforeach;?>
          </div>

          <!-- Créditos -->
          <div style="padding:.8rem 1.2rem;border-top:1px solid var(--border);display:flex;align-items:center;justify-content:space-between">
            <div style="color:var(--muted);font-size:.78rem"><i class="bi bi-coin me-1" style="color:#fbbf24"></i>Seus créditos: <strong style="color:#fbbf24">10</strong> (bônus de boas-vindas)</div>
            <span style="color:#60a5fa;font-size:.75rem;font-weight:600">Anunciar peça →</span>
          </div>
        </div>
      </div>

      <!-- Texto -->
      <div class="col-lg-6 order-lg-2 order-1">
        <div class="sec-tag">Marketplace de Peças</div>
        <h2 class="sec-title">Compre e venda peças<br>com outras assistências</h2>
        <p class="sec-sub">Encontrou uma peça rara que precisa? Tem estoque parado? O marketplace FixaOS conecta assistências técnicas de todo o Brasil — direto pelo sistema, sem intermediários.</p>

        <div class="d-flex flex-column gap-3 mt-4">
          <?php foreach([
            ['bi-search','#60a5fa','Encontre qualquer peça','Celular, notebook, geladeira, TV — busque por equipamento, marca ou modelo. Contato direto com o vendedor pelo WhatsApp.'],
            ['bi-megaphone-fill','#fb923c','Anuncie seu estoque parado','Transforme peças encalhadas em dinheiro. Crie anúncio em 2 minutos com foto, preço e descrição.'],
            ['bi-coin','#fbbf24','10 créditos grátis ao cadastrar','Todo novo cadastro recebe créditos de boas-vindas para começar a anunciar sem gastar nada.'],
            ['bi-shield-check','#4ade80','Contato direto e seguro','Nenhum intermediário. Você fala direto com a assistência vendedora via WhatsApp.'],
          ] as[$icon,$c,$t,$d]):?>
          <div class="d-flex gap-3 align-items-start">
            <div style="width:38px;height:38px;border-radius:10px;background:rgba(255,255,255,.05);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;flex-shrink:0">
              <i class="bi <?=$icon?>" style="color:<?=$c?>"></i>
            </div>
            <div>
              <div style="color:#fff;font-weight:600;font-size:.9rem"><?=$t?></div>
              <div style="color:var(--muted);font-size:.83rem;margin-top:.2rem"><?=$d?></div>
            </div>
          </div>
          <?php endforeach;?>
        </div>

        <div class="d-flex gap-3 mt-4 flex-wrap">
          <a href="<?= url('/pecas') ?>" target="_blank" class="btn-brand btn px-4 py-2">
            <i class="bi bi-shop me-2"></i>Ver marketplace
          </a>
          <a href="<?= url('/cadastrar') ?>" class="btn-ghost btn px-4 py-2">
            <i class="bi bi-rocket-takeoff me-2"></i>Testar grátis
          </a>
        </div>
      </div>

    </div>
  </div>
</section>

<!-- ═══ PLANOS ═══ -->
<section id="planos" style="padding:6rem 0;background:var(--bg)">
  <div class="container">
    <div class="text-center mb-5">
      <div class="sec-tag">Planos</div>
      <h2 class="sec-title">Sistema completo em todos.<br>Muda só o tamanho.</h2>
      <p class="sec-sub" style="margin-bottom:2rem">Escolha pela sua equipe. Trimestral -15%, anual -20%.</p>

      <div class="price-toggle" id="pricingToggle">
        <button class="ptab active" data-ciclo="mensal">Mensal</button>
        <button class="ptab" data-ciclo="trimestral">Trimestral <span style="color:#4ade80">-15%</span></button>
        <button class="ptab" data-ciclo="anual">Anual <span style="color:#4ade80">-20%</span></button>
      </div>
    </div>

    <?php $__pl = require BASE_PATH . '/config/planos.php'; ?>
    <div class="row justify-content-center g-4">
      <?php foreach ($__pl['planos'] as $p): ?>
      <div class="col-md-4">
        <div class="price-card h-100 d-flex flex-column <?= !empty($p['destaque']) ? 'featured' : '' ?>">
          <?php if (!empty($p['destaque'])): ?><div class="price-badge">Mais popular</div><?php endif; ?>
          <div style="color:#fff;font-weight:800;font-size:1.15rem"><?= $p['nome'] ?></div>
          <div style="color:var(--muted);font-size:.8rem;margin-bottom:.6rem">
            <?= (int)$p['max_usuarios'] === 0 ? 'Usuários ilimitados' : 'Até '.(int)$p['max_usuarios'].' usuários' ?> ·
            <?= (int)$p['os_mes'] === 0 ? 'OS ilimitada' : (int)$p['os_mes'].' OS/mês' ?>
          </div>

          <?php foreach ($__pl['ciclos'] as $ck => $c): $tot = plano_preco_ciclo($p['preco_mensal'], $c); $pm = $tot / $c['meses']; ?>
          <div class="preco-land" data-ciclo="<?= $ck ?>" style="<?= $ck === 'mensal' ? '' : 'display:none' ?>">
            <div class="price-val">
              <span style="font-size:1rem;font-weight:600;color:#94a3b8;margin-top:.5rem;line-height:1">R$</span>
              <span style="font-size:2.6rem;font-weight:900;color:#fff;line-height:1"><?= number_format($pm/100, 2, ',', '.') ?></span>
              <small style="font-size:.85rem;color:var(--muted);margin-top:auto;padding-bottom:.2rem">/mês</small>
            </div>
            <?php if ($c['meses'] > 1): ?><div class="price-economy">R$ <?= number_format($tot/100, 2, ',', '.') ?> à vista · economize <?= $c['desconto'] ?>%</div><?php endif; ?>
          </div>
          <?php endforeach; ?>

          <?php if (!empty($p['preco_pos_intro'])): ?>
          <div class="price-economy" style="color:#fb923c">Preco fundador - depois de <?= (int)$p['intro_meses'] ?> meses, R$ <?= number_format($p['preco_pos_intro']/100, 2, ',', '.') ?>/mes</div>
          <?php endif; ?>

          <hr style="border-color:var(--border);margin:1.2rem 0">
          <div class="flex-grow-1">
            <?php foreach ($p['beneficios'] as $b): ?>
            <div class="price-item"><i class="bi bi-check-circle-fill"></i> <?= $b ?></div>
            <?php endforeach; ?>
          </div>
          <a href="<?= url('/cadastrar') ?>" class="btn-brand btn w-100 py-3 fw-bold mt-3">
            <i class="bi bi-rocket-takeoff-fill me-2"></i>Começar 15 dias grátis
          </a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <div class="text-center" style="color:var(--muted);font-size:.85rem;margin-top:1.5rem">
      Precisou de mais OS num mês? Compre crédito avulso (+<?= (int)$__pl['credito_os']['qtd'] ?> OS por R$ <?= number_format($__pl['credito_os']['preco']/100, 2, ',', '.') ?>). PIX ou cartão em até 12x · <strong style="color:#fb923c">comece com 15 dias grátis</strong>, sem cartão.
    </div>
  </div>
</section>

<script>
(function(){
  var toggle = document.getElementById('pricingToggle');
  if(!toggle) return;
  toggle.querySelectorAll('.ptab').forEach(function(b){
    b.addEventListener('click', function(){
      toggle.querySelectorAll('.ptab').forEach(function(x){ x.classList.remove('active'); });
      this.classList.add('active');
      var ck = this.dataset.ciclo;
      document.querySelectorAll('.preco-land').forEach(function(el){ el.style.display = el.dataset.ciclo === ck ? '' : 'none'; });
    });
  });
})();
</script>

<!-- ═══ MIGRAÇÃO ASSISTIDA ═══ -->
<section style="padding:6rem 0;background:var(--bg2);border-top:1px solid var(--border);border-bottom:1px solid var(--border)">
  <div class="container">
    <div class="text-center mb-5">
      <div class="sec-tag">Serviço opcional</div>
      <h2 class="sec-title">Vem de outro sistema?<br>Trazemos seus clientes.</h2>
      <p class="sec-sub" style="max-width:620px;margin:auto">Não importa qual sistema, planilha ou caderno você usa hoje: a gente traz o <strong style="color:#fff">cadastro dos seus clientes</strong> pra dentro do FixaOS, sem você redigitar nada. Um trabalho manual e cuidadoso, feito por quem conhece o sistema por dentro.</p>
    </div>

    <div class="row g-4 align-items-stretch justify-content-center">
      <!-- Como funciona -->
      <div class="col-md-6">
        <div class="price-card h-100">
          <div style="color:#fff;font-weight:700;font-size:1.1rem;margin-bottom:1.2rem">Como funciona</div>
          <?php foreach([
            ['bi-search','#60a5fa','Analisamos sua base','Olhamos de onde vêm seus dados e conferimos o que dá pra trazer com segurança.'],
            ['bi-people-fill','#4ade80','Trazemos seus clientes','Nome, contato, endereço e dados de cadastro — sem redigitar um por um.'],
            ['bi-shield-check','#c084fc','Você confere antes de valer','Importamos num ambiente de teste, você valida, e só então vai pro ar.'],
          ] as [$ic,$c,$t,$d]): ?>
          <div class="d-flex gap-3 mb-3 align-items-start">
            <div style="width:36px;height:36px;border-radius:10px;background:rgba(255,255,255,.05);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;flex-shrink:0"><i class="bi <?=$ic?>" style="color:<?=$c?>"></i></div>
            <div><div style="color:#fff;font-weight:600;font-size:.9rem"><?=$t?></div><div style="color:var(--muted);font-size:.83rem;margin-top:.2rem"><?=$d?></div></div>
          </div>
          <?php endforeach; ?>
          <div style="color:var(--muted);font-size:.8rem;margin-top:1rem;padding-top:1rem;border-top:1px solid var(--border)"><i class="bi bi-info-circle me-1"></i>Focamos no cadastro de clientes — o que dá pra entregar com qualidade. Seu histórico começa do zero no FixaOS, do jeitinho certo.</div>
        </div>
      </div>

      <!-- Card do serviço -->
      <div class="col-md-5">
        <div class="price-card h-100 d-flex flex-column justify-content-between">
          <div>
            <div style="color:#fb923c;font-weight:800;font-size:1.2rem;margin-bottom:.5rem">Migração de clientes</div>
            <p style="color:var(--muted);font-size:.9rem;line-height:1.6">Um trabalho manual e cuidadoso, feito pessoalmente. O valor é sob medida para o tamanho da sua base — <strong style="color:#fff">um preço justo pelo tempo e pela expertise</strong>, combinado antes, sem surpresa.</p>
            <div style="background:rgba(249,115,22,.08);border:1px solid rgba(249,115,22,.2);border-radius:12px;padding:1rem;margin-top:1rem">
              <div style="color:#fff;font-size:.85rem;line-height:1.6"><i class="bi bi-info-circle me-1" style="color:#fb923c"></i>Quer saber se dá pra trazer seus clientes? Manda uma mensagem — a avaliação é sem compromisso.</div>
            </div>
          </div>
          <a href="https://wa.me/5511979930702?text=Ol%C3%A1!%20Quero%20saber%20sobre%20a%20migra%C3%A7%C3%A3o%20dos%20meus%20clientes%20para%20o%20FixaOS." target="_blank" rel="noopener" class="btn-brand btn w-100 py-3 fw-bold mt-3"><i class="bi bi-whatsapp me-2"></i>Falar sobre migração</a>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ═══ FAQ ═══ -->
<section id="faq" style="padding:6rem 0;background:var(--bg)">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-7">
        <div class="text-center mb-5">
          <div class="sec-tag">FAQ</div>
          <h2 class="sec-title">Dúvidas frequentes</h2>
        </div>
        <?php
        $faqs=[
          ['Preciso instalar alguma coisa?',
            'Não. O FixaOS é 100% online — funciona em qualquer navegador, no computador, tablet ou celular. Sem instalação, sem servidor local.'],
          ['Quantos usuários posso cadastrar?',
            'Depende do plano: o Autônomo inclui 1 usuário, o Oficina inclui 3, e no Top Empresa é ilimitado. Cada pessoa da equipe tem o próprio login — você faz upgrade a qualquer momento se precisar de mais gente.'],
          ['O que acontece após os 15 dias grátis?',
            'Você escolhe continuar pagando ou cancela. Sem cobrança automática, sem pegadinha. Seus dados ficam disponíveis por mais 30 dias se você decidir sair.'],
          ['Consigo importar dados do meu sistema atual?',
            'Sim. Temos ferramenta de migração para os principais formatos e podemos ajudar manualmente se você usar outro sistema.'],
          ['Como é o suporte?',
            'Suporte por WhatsApp e e-mail, com atendimento humano. Sem bot de primeira linha, sem ticket sem resposta. Respondemos em até 4 horas úteis.'],
          ['Meus dados ficam seguros?',
            'Sim. Cada empresa tem dados completamente isolados. Backups automáticos diários. Acesso somente com autenticação.'],
          ['O que é o Diretório de Assistências?',
            'É uma página pública onde seus clientes podem encontrar sua assistência técnica pelo Google. Cada empresa cadastrada ganha uma URL própria com SEO, mapa, avaliações e botão de WhatsApp. É gratuito e automático — basta ativar nas configurações.'],
          ['Como funciona o destaque no diretório?',
            'Você pode contratar um plano de destaque (a partir de R$ 49,90/mês) para aparecer no topo do diretório com badge exclusivo, ou comprar um slot de banner para exibir sua marca na faixa de anúncios. Tudo gerenciado pelo próprio sistema.'],
        ];
        foreach($faqs as $i=>[$q,$a]):?>
        <div class="faq-item">
          <div class="faq-q" onclick="toggleFaq(<?=$i?>)">
            <?=$q?>
            <i class="bi bi-plus-lg" id="faq-icon-<?=$i?>" style="color:var(--brand);flex-shrink:0;margin-left:1rem"></i>
          </div>
          <div class="faq-a" id="faq-a-<?=$i?>"><?=$a?></div>
        </div>
        <?php endforeach;?>
      </div>
    </div>
  </div>
</section>

<!-- ═══ CTA FINAL ═══ -->
<section class="cta-sect">
  <div class="container">
    <h2 class="sec-title mb-3">Chega de OS no papel.<br>Comece hoje, de graça.</h2>
    <p style="color:#94a3b8;font-size:1rem;margin-bottom:2rem;max-width:480px;margin-left:auto;margin-right:auto">
      Crie sua conta e organize sua assistência técnica em minutos. 15 dias grátis, sem cartão de crédito, cancele quando quiser.
    </p>
    <div class="d-flex gap-3 justify-content-center flex-wrap">
      <a href="<?= url('/cadastrar') ?>" class="btn-brand btn px-5 py-3 fs-5 fw-bold">
        <i class="bi bi-rocket-takeoff-fill me-2"></i>Começar teste grátis de 15 dias
      </a>
      <a href="<?= url('/login') ?>" class="btn-ghost btn px-4 py-3 fw-semibold">
        <i class="bi bi-box-arrow-in-right me-2"></i>Já sou cliente
      </a>
      <a href="<?= url('/assistencias') ?>" class="btn-ghost btn px-4 py-3 fw-semibold">
        <i class="bi bi-geo-alt-fill me-2"></i>Ver o diretório
      </a>
    </div>
    <div style="color:var(--muted);font-size:.82rem;margin-top:1rem">
      Sem cartão · Sem contrato · Cancele quando quiser
    </div>
  </div>
</section>

<script>
// FAQ toggle
function toggleFaq(i){
  const a=document.getElementById('faq-a-'+i);
  const ico=document.getElementById('faq-icon-'+i);
  const open=a.classList.toggle('open');
  ico.className='bi '+(open?'bi-dash-lg':'bi-plus-lg');
  ico.style.color='var(--brand)';
}
</script>

<?php
// ═══ Dados estruturados (Schema.org / JSON-LD) para SEO — reaproveita $faqs e $__pl ═══
$__precos = array_map(fn($p) => $p['preco_mensal'] / 100, $__pl['planos']);
$__ld = [
  '@context' => 'https://schema.org',
  '@graph' => [
    [
      '@type' => 'Organization',
      '@id'   => 'https://fixaos.com.br/#organization',
      'name'  => 'FixaOS',
      'url'   => 'https://fixaos.com.br',
      'logo'  => 'https://fixaos.com.br/apple-touch-icon.png',
      'description' => 'Sistema de gestão para assistências técnicas.',
    ],
    [
      '@type' => 'WebSite',
      '@id'   => 'https://fixaos.com.br/#website',
      'url'   => 'https://fixaos.com.br',
      'name'  => 'FixaOS',
      'inLanguage' => 'pt-BR',
      'publisher'  => ['@id' => 'https://fixaos.com.br/#organization'],
    ],
    [
      '@type' => 'SoftwareApplication',
      'name'  => 'FixaOS',
      'applicationCategory' => 'BusinessApplication',
      'operatingSystem' => 'Web',
      'url' => 'https://fixaos.com.br',
      'description' => 'Sistema completo para assistência técnica: ordens de serviço, PDV, clientes, estoque, financeiro, agenda e página pública no Google. Teste grátis 15 dias.',
      'offers' => [
        '@type' => 'AggregateOffer',
        'priceCurrency' => 'BRL',
        'lowPrice'  => number_format(min($__precos), 2, '.', ''),
        'highPrice' => number_format(max($__precos), 2, '.', ''),
        'offerCount' => count($__pl['planos']),
      ],
    ],
    [
      '@type' => 'FAQPage',
      'mainEntity' => array_map(fn($f) => [
        '@type' => 'Question',
        'name'  => $f[0],
        'acceptedAnswer' => ['@type' => 'Answer', 'text' => strip_tags($f[1])],
      ], $faqs),
    ],
  ],
];
echo '<script type="application/ld+json">' . json_encode($__ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>';
?>
