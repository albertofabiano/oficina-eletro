<?php $titulo = ''; ?>

<!-- HERO -->
<section class="hero">
  <div class="container position-relative py-5">
    <div class="row align-items-center g-5">
      <div class="col-lg-6">
        <div class="hero-badge"><i class="bi bi-stars me-1"></i> 30 dias de trial gratuito • Sem cartão</div>
        <h1>Gestão completa para sua <span>assistência técnica</span></h1>
        <p class="lead mt-3 mb-4">OS, CRM, estoque, financeiro e muito mais — tudo em um sistema simples, rápido e sem mensalidade no início.</p>
        <div class="d-flex flex-wrap gap-3">
          <a href="<?= url('/cadastrar') ?>" class="btn btn-primary btn-hero">
            <i class="bi bi-rocket-takeoff me-2"></i>Começar grátis agora
          </a>
          <a href="#funcionalidades" class="btn btn-outline-light btn-hero">
            Ver funcionalidades
          </a>
        </div>
        <div class="d-flex flex-wrap gap-4 mt-4">
          <?php foreach (['✅ Sem instalação','✅ 30 dias grátis','✅ Suporte incluído'] as $item): ?>
          <span class="text-muted small"><?= $item ?></span>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="col-lg-6 d-none d-lg-block">
        <div class="hero-img">
          <!-- Dashboard preview SVG -->
          <svg viewBox="0 0 560 340" xmlns="http://www.w3.org/2000/svg" style="width:100%;border-radius:8px">
            <rect width="560" height="340" fill="#1a1d23"/>
            <!-- Sidebar -->
            <rect width="130" height="340" fill="#0f1117"/>
            <rect x="12" y="20" width="106" height="28" rx="6" fill="#0d6efd22"/>
            <circle cx="26" cy="34" r="8" fill="#0d6efd"/>
            <rect x="40" y="29" width="60" height="6" rx="3" fill="#fff" opacity=".8"/>
            <rect x="40" y="38" width="40" height="4" rx="2" fill="#6c757d" opacity=".5"/>
            <rect x="12" y="64" width="40" height="4" rx="2" fill="#6c757d" opacity=".3"/>
            <?php foreach ([80,100,120,140,168,188,208,236,256] as $y): ?>
            <rect x="12" y="<?= $y ?>" width="106" height="18" rx="5" fill="<?= $y===80?'#0d6efd22':'transparent' ?>"/>
            <rect x="30" y="<?= $y+5 ?>" width="70" height="6" rx="3" fill="#adb5bd" opacity=".4"/>
            <?php endforeach; ?>
            <!-- Content area -->
            <!-- Cards -->
            <?php
            $cards = [
              [145,20,90,54,'#0d6efd','OS Abertas','12'],
              [243,20,90,54,'#198754','Concluídas','47'],
              [341,20,90,54,'#dc3545','Atrasadas','3'],
              [439,20,90,54,'#0dcaf0','Faturamento','R$8.4k'],
            ];
            foreach ($cards as [$x,$y,$w,$h,$c,$label,$val]):
            ?>
            <rect x="<?= $x ?>" y="<?= $y ?>" width="<?= $w ?>" height="<?= $h ?>" rx="8" fill="<?= $c ?>" opacity=".9"/>
            <text x="<?= $x+8 ?>" y="<?= $y+18 ?>" fill="rgba(255,255,255,.8)" font-size="8" font-family="Arial">
              <?= $label ?>
            </text>
            <text x="<?= $x+8 ?>" y="<?= $y+40 ?>" fill="#fff" font-size="18" font-weight="bold" font-family="Arial">
              <?= $val ?>
            </text>
            <?php endforeach; ?>
            <!-- Chart -->
            <rect x="145" y="84" width="192" height="120" rx="8" fill="#212529"/>
            <text x="155" y="100" fill="#adb5bd" font-size="8" font-family="Arial">Faturamento mensal</text>
            <?php
            $bars = [[155,28],[180,45],[205,35],[230,60],[255,52],[280,70]];
            foreach ($bars as [$bx,$bh]):
            ?>
            <rect x="<?= $bx ?>" y="<?= 190-$bh ?>" width="18" height="<?= $bh ?>" rx="3" fill="#0d6efd" opacity=".7"/>
            <?php endforeach; ?>
            <!-- OS Table -->
            <rect x="145" y="214" width="400" height="116" rx="8" fill="#212529"/>
            <text x="155" y="230" fill="#adb5bd" font-size="8" font-family="Arial">Últimas OS</text>
            <rect x="145" y="235" width="400" height="1" fill="#333"/>
            <?php
            $rows = [
              ['#000142','Samsung TV 55"','Em Reparo','#0d6efd','R$ 280'],
              ['#000141','iPhone 14 Pro','Pronto p/ Retirada','#198754','R$ 450'],
              ['#000140','Geladeira Brastemp','Aguard. Peças','#fd7e14','R$ 320'],
              ['#000139','Notebook Dell','Em Diagnóstico','#0dcaf0','R$ 150'],
            ];
            foreach ($rows as $i => [$num,$equip,$status,$cor,$val]):
              $ry = 245 + $i*22;
            ?>
            <text x="155" y="<?= $ry+8 ?>" fill="#fff" font-size="7.5" font-family="Arial"><?= $num ?></text>
            <text x="210" y="<?= $ry+8 ?>" fill="#adb5bd" font-size="7.5" font-family="Arial"><?= $equip ?></text>
            <rect x="340" y="<?= $ry ?>" width="75" height="13" rx="4" fill="<?= $cor ?>" opacity=".2"/>
            <text x="345" y="<?= $ry+9 ?>" fill="<?= $cor ?>" font-size="6.5" font-family="Arial"><?= $status ?></text>
            <text x="470" y="<?= $ry+8 ?>" fill="#fff" font-size="7.5" font-family="Arial"><?= $val ?></text>
            <?php endforeach; ?>
            <!-- Top 5 serviços -->
            <rect x="345" y="84" width="200" height="120" rx="8" fill="#212529"/>
            <text x="355" y="100" fill="#adb5bd" font-size="8" font-family="Arial">Top Serviços</text>
            <?php
            $servicos = ['Troca de Tela','Troca de Bateria','Placa Mãe','Limpeza Geral','Conector Carga'];
            foreach ($servicos as $si => $sv):
              $sy = 112 + $si*18;
              $bw = [140,110,90,70,50][$si];
            ?>
            <text x="355" y="<?= $sy+8 ?>" fill="#fff" font-size="7" font-family="Arial"><?= $sv ?></text>
            <rect x="355" y="<?= $sy+10 ?>" width="<?= $bw ?>" height="5" rx="2" fill="#0d6efd" opacity=".6"/>
            <?php endforeach; ?>
          </svg>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- STATS -->
<div class="stats-bar">
  <div class="container">
    <div class="row">
      <?php foreach ([
        ['500+','Assistências ativas'],
        ['50 mil+','OS processadas'],
        ['30 dias','Trial gratuito'],
        ['99,9%','Uptime garantido'],
      ] as [$num,$label]): ?>
      <div class="col-6 col-md-3 stat-item">
        <div class="stat-num"><?= $num ?></div>
        <div class="stat-label"><?= $label ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<!-- FUNCIONALIDADES -->
<section id="funcionalidades" class="py-6" style="padding:5rem 0; background:#f8f9fa">
  <div class="container">
    <div class="text-center mb-5">
      <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 mb-3">Funcionalidades</span>
      <h2 class="fw-bold fs-1">Tudo que sua assistência precisa</h2>
      <p class="text-muted fs-5">Módulos completos, integrados e fáceis de usar.</p>
    </div>
    <div class="row g-4">
      <?php
      $features = [
        ['bi-clipboard2-pulse','bg-primary bg-opacity-10 text-primary','Ordens de Serviço',
          'Abra, acompanhe e finalize OS com workflow de status, serviços e peças em tempo real.'],
        ['bi-people','bg-success bg-opacity-10 text-success','CRM de Clientes',
          'Cadastro completo PF/PJ, histórico de atendimentos, pipeline de vendas e contatos.'],
        ['bi-box-seam','bg-warning bg-opacity-10 text-warning','Controle de Estoque',
          'Gerencie peças e componentes com alerta de mínimo, entradas/saídas e fornecedores.'],
        ['bi-currency-dollar','bg-info bg-opacity-10 text-info','Financeiro',
          'Contas a receber/pagar, fluxo de caixa, comissões de técnicos e DRE simplificado.'],
        ['bi-calendar3','bg-danger bg-opacity-10 text-danger','Agenda',
          'Calendário de atendimentos, coletas e entregas. Nunca mais perca um compromisso.'],
        ['bi-bar-chart-line','bg-secondary bg-opacity-10 text-secondary','Relatórios',
          'Dashboard com gráficos, ranking de serviços, ticket médio e exportação de dados.'],
        ['bi-building','bg-primary bg-opacity-10 text-primary','Multiempresas',
          'Gerencie várias filiais com dados completamente isolados por empresa.'],
        ['bi-person-gear','bg-success bg-opacity-10 text-success','Usuários e Perfis',
          'Admin, gerente, recepcionista e técnico — cada um com permissões específicas.'],
      ];
      foreach ($features as [$icon,$iconClass,$title,$desc]): ?>
      <div class="col-md-6 col-lg-3">
        <div class="feature-card">
          <div class="feature-icon <?= $iconClass ?>">
            <i class="bi <?= $icon ?>"></i>
          </div>
          <h5 class="fw-bold mb-2"><?= $title ?></h5>
          <p class="text-muted small mb-0"><?= $desc ?></p>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- COMO FUNCIONA -->
<section id="como-funciona" class="section-dark py-5">
  <div class="container py-4">
    <div class="text-center mb-5">
      <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 mb-3">Como funciona</span>
      <h2 class="fw-bold fs-1 text-white">Em 3 passos você já está operando</h2>
    </div>
    <div class="row g-4 text-center">
      <?php foreach ([
        ['1','Crie sua conta','Cadastre sua assistência técnica gratuitamente. Sem cartão de crédito, sem burocracia.'],
        ['2','Configure em minutos','Adicione seus técnicos, categorias de equipamento e personalize os status das OS.'],
        ['3','Comece a usar','Abra ordens de serviço, cadastre clientes e controle tudo no dashboard.'],
      ] as [$n,$t,$d]): ?>
      <div class="col-md-4">
        <div class="step-num"><?= $n ?></div>
        <h5 class="text-white fw-bold"><?= $t ?></h5>
        <p class="text-muted"><?= $d ?></p>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- PLANOS -->
<section id="planos" class="py-5" style="padding:5rem 0">
  <div class="container py-4">
    <div class="text-center mb-5">
      <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 mb-3">Planos</span>
      <h2 class="fw-bold fs-1">Preço simples e transparente</h2>
      <p class="text-muted fs-5">Comece grátis por 30 dias. Sem surpresas.</p>
    </div>
    <div class="row g-4 justify-content-center">
      <!-- Básico -->
      <div class="col-md-4">
        <div class="price-card">
          <div class="text-muted small fw-semibold mb-1">BÁSICO</div>
          <div class="price-valor"><sup>R$</sup>97<small>/mês</small></div>
          <p class="text-muted mt-2 mb-4">Para assistências pequenas iniciando no digital.</p>
          <ul class="list-unstyled d-flex flex-column gap-2 mb-4">
            <?php foreach (['1 usuário','50 OS/mês','Clientes e Equipamentos','Financeiro básico'] as $f): ?>
            <li class="small"><i class="bi bi-check-circle-fill text-success me-2"></i><?= $f ?></li>
            <?php endforeach; ?>
          </ul>
          <a href="<?= url('/cadastrar') ?>" class="btn btn-outline-primary w-100">Começar grátis</a>
        </div>
      </div>
      <!-- Profissional -->
      <div class="col-md-4">
        <div class="price-card destaque" style="border-color:#0d6efd">
          <div class="text-muted small fw-semibold mb-1">PROFISSIONAL</div>
          <div class="price-valor"><sup>R$</sup>197<small>/mês</small></div>
          <p class="text-muted mt-2 mb-4">Para quem quer crescer com controle total.</p>
          <ul class="list-unstyled d-flex flex-column gap-2 mb-4">
            <?php foreach (['Até 5 usuários','OS ilimitadas','CRM + Pipeline','Estoque completo','Financeiro + Comissões','Relatórios avançados','Agenda'] as $f): ?>
            <li class="small"><i class="bi bi-check-circle-fill text-success me-2"></i><?= $f ?></li>
            <?php endforeach; ?>
          </ul>
          <a href="<?= url('/cadastrar') ?>" class="btn btn-primary w-100 fw-semibold">Começar grátis</a>
        </div>
      </div>
      <!-- Enterprise -->
      <div class="col-md-4">
        <div class="price-card">
          <div class="text-muted small fw-semibold mb-1">ENTERPRISE</div>
          <div class="price-valor"><sup>R$</sup>397<small>/mês</small></div>
          <p class="text-muted mt-2 mb-4">Para redes e franquias com múltiplas unidades.</p>
          <ul class="list-unstyled d-flex flex-column gap-2 mb-4">
            <?php foreach (['Usuários ilimitados','Multiempresas/Filiais','Tudo do Profissional','API de integração','Suporte prioritário','Treinamento incluso'] as $f): ?>
            <li class="small"><i class="bi bi-check-circle-fill text-success me-2"></i><?= $f ?></li>
            <?php endforeach; ?>
          </ul>
          <a href="<?= url('/cadastrar') ?>" class="btn btn-outline-primary w-100">Falar com vendas</a>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- CTA FINAL -->
<div class="cta-section">
  <div class="container">
    <h2 class="fw-bold fs-1 mb-3">Pronto para organizar sua assistência?</h2>
    <p class="fs-5 mb-4 opacity-90">Crie sua conta agora e experimente 30 dias completamente grátis.</p>
    <a href="<?= url('/cadastrar') ?>" class="btn btn-light btn-hero text-primary fw-bold">
      <i class="bi bi-rocket-takeoff me-2"></i>Criar conta gratuita
    </a>
    <div class="mt-3 small opacity-75">Sem cartão de crédito • Sem contrato • Cancele quando quiser</div>
  </div>
</div>
