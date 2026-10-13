<style>
/* HERO */
.fxl-hero{padding:5.5rem 0 4rem;position:relative;overflow:hidden}
.fxl-hero::before{content:'';position:absolute;top:-180px;right:-180px;width:520px;height:520px;border-radius:50%;background:radial-gradient(circle,rgba(140,124,255,.25),transparent 70%);pointer-events:none}
.fxl-hero::after{content:'';position:absolute;bottom:-220px;left:-160px;width:480px;height:480px;border-radius:50%;background:radial-gradient(circle,rgba(60,201,192,.16),transparent 70%);pointer-events:none}
.fxl-badge{display:inline-flex;align-items:center;gap:.45rem;background:rgba(34,197,94,.12);border:1px solid rgba(34,197,94,.35);color:#4ade80;font-size:.82rem;font-weight:700;padding:.45rem 1rem;border-radius:999px;margin-bottom:1.5rem}
.fxl-hero h1{font-size:2.9rem;font-weight:800;line-height:1.12;letter-spacing:-.02em;color:#fff;max-width:680px}
.fxl-hero h1 .hl{color:#3CC9C0}
.fxl-hero p.lead{font-size:1.1rem;color:#c7c5d9;max-width:560px;margin:1.3rem 0 2rem;line-height:1.6}
.fxl-hero-ctas{display:flex;gap:.9rem;flex-wrap:wrap;margin-bottom:.9rem}
.fxl-btn-hero-primary{background:#8C7CFF;color:#fff;font-weight:700;border:none;border-radius:12px;padding:.9rem 1.7rem;font-size:1rem}
.fxl-btn-hero-primary:hover{background:#7563f0;color:#fff}
.fxl-btn-hero-ghost{background:rgba(255,255,255,.06);color:#fff;font-weight:600;border:1px solid rgba(255,255,255,.16);border-radius:12px;padding:.9rem 1.7rem;font-size:1rem}
.fxl-btn-hero-ghost:hover{background:rgba(255,255,255,.1);color:#fff}
.fxl-hero-sub{font-size:.8rem;color:#8b8aa3}

.fxl-shot-frame{border-radius:16px;overflow:hidden;border:1px solid rgba(255,255,255,.1);box-shadow:0 30px 70px -20px rgba(0,0,0,.55),0 0 0 1px rgba(255,255,255,.03);background:#0f0a17}
.fxl-shot-frame img{display:block;width:100%;height:auto}
.fxl-shot-bar{display:flex;align-items:center;gap:6px;padding:.6rem .8rem;background:#17101f;border-bottom:1px solid rgba(255,255,255,.07)}
.fxl-shot-dot{width:9px;height:9px;border-radius:50%}

/* SEÇÃO — título/rótulo padrão */
.fxl-section{padding:4.5rem 0}
.fxl-eyebrow{font-size:.78rem;font-weight:800;letter-spacing:.08em;text-transform:uppercase;color:#8C7CFF;margin-bottom:.6rem}
.fxl-section h2{font-size:1.9rem;font-weight:800;color:#fff;letter-spacing:-.01em;max-width:520px}
.fxl-section p.fxl-desc{color:#a7a9c4;font-size:1rem;line-height:1.65;max-width:460px}
.fxl-check{display:flex;gap:.6rem;align-items:flex-start;margin-bottom:.8rem;font-size:.92rem;color:#d7d6e6}
.fxl-check i{color:#3CC9C0;font-size:1.05rem;margin-top:.15rem;flex-shrink:0}

/* NO CELULAR */
.fxl-mobile-strip{display:flex;gap:1.6rem;justify-content:center;flex-wrap:wrap;padding:0 20px}
.fxl-mobile-strip img{width:220px;height:auto;filter:drop-shadow(0 25px 45px rgba(0,0,0,.5));transition:transform .25s}
.fxl-mobile-strip img:hover{transform:translateY(-6px)}

/* COMO FUNCIONA */
.fxl-step{background:rgba(255,255,255,.035);border:1px solid rgba(255,255,255,.08);border-radius:16px;padding:1.6rem;height:100%}
.fxl-step-n{width:36px;height:36px;border-radius:10px;background:rgba(140,124,255,.16);color:#8C7CFF;font-weight:800;font-family:'Poppins',sans-serif;display:flex;align-items:center;justify-content:center;margin-bottom:1rem;font-size:1.05rem}
.fxl-step h3{font-size:1.05rem;font-weight:700;color:#fff;margin-bottom:.45rem}
.fxl-step p{font-size:.88rem;color:#a7a9c4;line-height:1.55;margin:0}

/* PROMO BAND */
.fxl-promo{background:linear-gradient(135deg,rgba(34,197,94,.14),rgba(140,124,255,.1));border:1px solid rgba(74,222,128,.25);border-radius:20px;padding:2.6rem;text-align:center}
.fxl-promo h2{font-size:1.6rem;margin:0 auto .6rem;max-width:620px}
.fxl-promo p{color:#c7c5d9;font-size:.95rem;max-width:520px;margin:0 auto 1.6rem}
</style>

<!-- HERO -->
<section class="fxl-hero">
  <div class="fxl-container">
    <div class="row align-items-center g-5">
      <div class="col-lg-6">
        <div class="fxl-badge"><i class="bi bi-stars"></i> Grátis por 1 ano — primeiros 100 clientes, sem cartão</div>
        <h1>Sua vida financeira, <span class="hl">organizada</span> de verdade</h1>
        <p class="lead">Lançamentos, leitura de conta por foto, contas recorrentes, agenda de vencimentos e caixinhas de reserva — tudo num só app, sem precisar de CNPJ de assistência técnica nenhum.</p>
        <div class="fxl-hero-ctas">
          <a href="<?= url('/carteira-fixa/cadastrar') ?>" class="fxl-btn-hero-primary btn">Criar conta grátis</a>
          <a href="<?= url('/carteira-fixa/login') ?>" class="fxl-btn-hero-ghost btn">Já tenho conta</a>
        </div>
        <div class="fxl-hero-sub">Sem cartão de crédito. A conta já nasce liberada por 1 ano inteiro.</div>
      </div>
      <div class="col-lg-6">
        <div class="fxl-shot-frame">
          <div class="fxl-shot-bar">
            <span class="fxl-shot-dot" style="background:#ff5f57"></span>
            <span class="fxl-shot-dot" style="background:#febc2e"></span>
            <span class="fxl-shot-dot" style="background:#28c840"></span>
          </div>
          <img src="/img/carteira-fixa/resumo.webp" alt="Tela de Resumo do Carteira Fixa, com gastos, recebidos, saldo do mês, saldo atual e guardado em caixinhas" loading="eager">
        </div>
      </div>
    </div>
  </div>
</section>

<!-- FUNCIONALIDADES -->
<section id="funcionalidades" class="fxl-section">
  <div class="fxl-container">

    <!-- Lançamentos -->
    <div class="row align-items-center g-5 mb-5 pb-4">
      <div class="col-lg-6 order-lg-1">
        <div class="fxl-eyebrow">Lançamentos</div>
        <h2 class="mb-3">Gasto ou entrada, sem complicação</h2>
        <p class="fxl-desc mb-4">Um toggle simples (Gasto/Entrada), categorias em chip colorido, forma de pagamento opcional e a possibilidade de escanear a conta pela câmera em vez de digitar tudo na mão.</p>
        <div class="fxl-check"><i class="bi bi-check-circle-fill"></i> Escaneie uma conta ou boleto pela câmera do celular e deixe o app ler os dados</div>
        <div class="fxl-check"><i class="bi bi-check-circle-fill"></i> Forma de pagamento (Pix, débito, dinheiro, cartão parcelado) calcula sozinha a data certa</div>
        <div class="fxl-check"><i class="bi bi-check-circle-fill"></i> Confirmação antes de salvar — evita lançar gasto como entrada (ou o contrário) sem perceber</div>
      </div>
      <div class="col-lg-6 order-lg-2">
        <div class="fxl-shot-frame">
          <img src="/img/carteira-fixa/lancamentos.webp" alt="Tela de Lançamentos do Carteira Fixa, com gasto no mês, recebido no mês, saldo do mês e a lista de lançamentos" loading="lazy">
        </div>
      </div>
    </div>

    <!-- Resumo -->
    <div class="row align-items-center g-5 mb-5 pb-4">
      <div class="col-lg-6 order-lg-2">
        <div class="fxl-eyebrow">Resumo</div>
        <h2 class="mb-3">Seu mês inteiro, num relance só</h2>
        <p class="fxl-desc mb-4">Gasto, recebido, saldo do mês, saldo atual e o total guardado em caixinhas — tudo nos cartões do topo, com um gráfico dia a dia e o ranking de categorias logo abaixo.</p>
        <div class="fxl-check"><i class="bi bi-check-circle-fill"></i> Gráfico diário separando pago/em aberto, gasto e entrada</div>
        <div class="fxl-check"><i class="bi bi-check-circle-fill"></i> Ranking "por categoria" pra saber onde o dinheiro está indo</div>
        <div class="fxl-check"><i class="bi bi-check-circle-fill"></i> Próximos vencimentos e últimos lançamentos, direto na primeira tela</div>
      </div>
      <div class="col-lg-6 order-lg-1">
        <div class="fxl-shot-frame">
          <img src="/img/carteira-fixa/resumo.webp" alt="Tela de Resumo do Carteira Fixa com gráfico diário, categorias, próximos vencimentos e últimos lançamentos" loading="lazy">
        </div>
      </div>
    </div>

    <!-- Agenda -->
    <div class="row align-items-center g-5 mb-5 pb-4">
      <div class="col-lg-6 order-lg-1">
        <div class="fxl-eyebrow">Agenda</div>
        <h2 class="mb-3">Nunca mais esqueça uma conta</h2>
        <p class="fxl-desc mb-4">Todo lançamento com vencimento e toda conta recorrente (aluguel, financiamento, assinatura) já aparece sozinho na Agenda — sem cadastrar o mesmo compromisso duas vezes.</p>
        <div class="fxl-check"><i class="bi bi-check-circle-fill"></i> Calendário mensal com o que vence e o que você vai receber</div>
        <div class="fxl-check"><i class="bi bi-check-circle-fill"></i> Contas recorrentes com ou sem data de fim (aluguel x financiamento de 48x)</div>
        <div class="fxl-check"><i class="bi bi-check-circle-fill"></i> Editar ou excluir um compromisso direto da lista do dia</div>
      </div>
      <div class="col-lg-6 order-lg-2">
        <div class="fxl-shot-frame">
          <img src="/img/carteira-fixa/agenda-eventos.webp" alt="Tela de Agenda do Carteira Fixa mostrando o calendário do mês e a lista de compromissos do dia selecionado" loading="lazy">
        </div>
      </div>
    </div>

    <!-- Caixinhas -->
    <div class="row align-items-center g-5">
      <div class="col-lg-6 order-lg-2">
        <div class="fxl-eyebrow">Caixinhas</div>
        <h2 class="mb-3">Separe dinheiro de verdade</h2>
        <p class="fxl-desc mb-4">Crie uma caixinha com nome e meta (ex.: "Férias", "Emergência"), guarde um valor sempre que puder e o app lembra: dinheiro guardado não é dinheiro disponível.</p>
        <div class="fxl-check"><i class="bi bi-check-circle-fill"></i> Meta com valor e data, barra de progresso acompanhando quanto falta</div>
        <div class="fxl-check"><i class="bi bi-check-circle-fill"></i> Aviso "falta transferir" até você confirmar que já mandou pro banco/investimento</div>
        <div class="fxl-check"><i class="bi bi-check-circle-fill"></i> O valor guardado já desconta do saldo da conta e do Resumo, pra não contar duas vezes</div>
      </div>
      <div class="col-lg-6 order-lg-1">
        <div class="fxl-shot-frame">
          <img src="/img/carteira-fixa/caixinhas.webp" alt="Tela de Caixinhas do Carteira Fixa, com a caixinha Férias, meta de R$10.000 e aviso de falta transferir" loading="lazy">
        </div>
      </div>
    </div>

  </div>
</section>

<!-- NO CELULAR -->
<section class="fxl-section" style="padding-top:1.5rem">
  <div class="fxl-container text-center mb-5">
    <div class="fxl-eyebrow" style="text-align:center">No seu celular</div>
    <h2 class="mx-auto" style="max-width:540px">Funciona liso direto do navegador, sem baixar nada de loja nenhuma</h2>
    <p class="fxl-desc mx-auto" style="max-width:480px">Adicione à tela inicial e use como se fosse um app instalado de verdade — a mesma tela de sempre, só que a um toque de distância. Tema claro ou escuro, você escolhe (ou deixa no automático, seguindo o do seu celular).</p>
  </div>
  <div class="fxl-mobile-strip">
    <img src="/img/carteira-fixa/mobile-resumo.webp" alt="Tela de Resumo do Carteira Fixa no celular" loading="lazy">
    <img src="/img/carteira-fixa/mobile-lancamentos.webp" alt="Tela de Lançamentos do Carteira Fixa no celular" loading="lazy">
    <img src="/img/carteira-fixa/mobile-acoes.webp" alt="Botões de Escanear conta e Adicionar lançamento no celular" loading="lazy">
    <img src="/img/carteira-fixa/mobile-agenda.webp" alt="Tela de Agenda do Carteira Fixa no celular" loading="lazy">
    <img src="/img/carteira-fixa/mobile-agenda-eventos.webp" alt="Lista de compromissos da Agenda, com o aviso de parcelas restantes de uma conta recorrente" loading="lazy">
    <img src="/img/carteira-fixa/mobile-contas.webp" alt="Tela de Contas do Carteira Fixa no celular" loading="lazy">
    <img src="/img/carteira-fixa/mobile-categorias.webp" alt="Tela de Categorias do Carteira Fixa no celular" loading="lazy">
    <img src="/img/carteira-fixa/mobile-config.webp" alt="Tela de Configurações, com foto de perfil e perfis do Carteira Fixa no celular" loading="lazy">
  </div>
</section>

<!-- COMO FUNCIONA -->
<section id="como-funciona" class="fxl-section" style="background:rgba(255,255,255,.02);border-top:1px solid rgba(255,255,255,.06);border-bottom:1px solid rgba(255,255,255,.06)">
  <div class="fxl-container">
    <div class="text-center mb-5">
      <div class="fxl-eyebrow" style="text-align:center">Como funciona</div>
      <h2 class="mx-auto" style="max-width:480px">Três passos, sem curva de aprendizado</h2>
    </div>
    <div class="row g-4">
      <div class="col-md-4">
        <div class="fxl-step">
          <div class="fxl-step-n">1</div>
          <h3>Crie sua conta grátis</h3>
          <p>Nome, e-mail e senha — ou entre direto com o Google. Sem cartão, sem forma de pagamento pra escolher.</p>
        </div>
      </div>
      <div class="col-md-4">
        <div class="fxl-step">
          <div class="fxl-step-n">2</div>
          <h3>Lance ou escaneie</h3>
          <p>Digite o gasto/entrada na hora, ou tire uma foto da conta/boleto e deixe o app preencher os dados por você.</p>
        </div>
      </div>
      <div class="col-md-4">
        <div class="fxl-step">
          <div class="fxl-step-n">3</div>
          <h3>Acompanhe e guarde</h3>
          <p>O Resumo e a Agenda se atualizam sozinhos; o que sobrar, guarde numa Caixinha com meta.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- PROMO FINAL -->
<section class="fxl-section">
  <div class="fxl-container">
    <div class="fxl-promo">
      <div class="fxl-badge" style="margin-bottom:1rem"><i class="bi bi-stars"></i> Primeiros 100 clientes</div>
      <h2>Grátis por 1 ano inteiro, sem pegadinha</h2>
      <p>A conta já nasce liberada por 12 meses, sem cartão nem cobrança nenhuma. Depois dos primeiros 100, continua entrando de graça do mesmo jeito — só não carrega esse selo específico.</p>
      <a href="<?= url('/carteira-fixa/cadastrar') ?>" class="fxl-btn-hero-primary btn">Criar minha conta grátis</a>
    </div>
  </div>
</section>
