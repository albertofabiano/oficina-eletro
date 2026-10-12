<?php $modoPdf = !empty($modoPdf); ?>
<style>
<?php if (!$modoPdf): ?>
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap');
<?php endif; ?>

.mde-wrap{background:#faf8f5;min-height:calc(100vh - 56px);font-family:'Inter',sans-serif;padding:2.4rem 1.5rem 5rem}
.mde-content{max-width:820px;margin:0 auto;background:#fffefc;border:1px solid #ece7de;border-radius:16px;padding:2.6rem 3rem}
@media(max-width:768px){.mde-content{padding:1.6rem 1.3rem}}

.mde-back{display:inline-flex;align-items:center;gap:.4rem;color:#8a8f9c;font-size:.85rem;text-decoration:none;margin-bottom:1.4rem}
.mde-back:hover{color:#f97316}
.mde-pdf-link{display:inline-flex;align-items:center;gap:.4rem;background:#fff;border:1px solid #ece7de;color:#4b5261;font-size:.82rem;font-weight:600;padding:.45rem .9rem;border-radius:8px;text-decoration:none;float:right}
.mde-pdf-link:hover{border-color:#f97316;color:#f97316}

.mde-eyebrow{color:#f97316;font-weight:700;font-size:.78rem;text-transform:uppercase;letter-spacing:.08em;margin-bottom:.4rem}
.mde-title{font-family:'Poppins',sans-serif;font-size:1.9rem;font-weight:700;color:#1a2332;margin-bottom:.6rem;letter-spacing:-.01em}
.mde-lead{color:#4b5261;font-size:1rem;line-height:1.8;margin-bottom:2.2rem}
.mde-lead strong{color:#1e3a5f}

.man-section{margin-bottom:2.6rem}
.man-h2{font-family:'Poppins',sans-serif;font-size:1.25rem;font-weight:600;color:#1a2332;margin-bottom:1.1rem;padding-bottom:.75rem;display:flex;align-items:center;gap:.6rem;border-bottom:1px solid #ece7de;position:relative}
.man-h2::after{content:'';position:absolute;left:0;bottom:-1px;width:32px;height:2px;background:#f97316}
.man-h2 i{color:#f97316;font-size:1rem}
.man-p{color:#4b5261;font-size:.93rem;line-height:1.8;margin-bottom:.9rem}
.man-step{display:flex;gap:.9rem;align-items:flex-start;background:#fbf9f6;border:1px solid #ece7de;border-left:3px solid #f97316;border-radius:0 10px 10px 0;padding:.8rem 1.05rem;margin-bottom:.5rem}
.man-step-n{width:22px;height:22px;border-radius:50%;background:#f97316;color:#fff;font-weight:700;font-size:.72rem;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px}
.man-step-t{color:#333a47;font-size:.88rem;line-height:1.6}
.man-step-t strong{color:#1e3a5f;font-weight:600}
.man-tip{background:#fff8f0;border:1px solid #f6ddbc;border-left:3px solid #f97316;border-radius:0 10px 10px 0;padding:.75rem 1rem;margin:.9rem 0;font-size:.85rem;color:#8a5a1f;line-height:1.6}
.man-tip i{color:#f97316;margin-right:.45rem}
.man-warn{background:#fdf4f3;border:1px solid #f3cdc9;border-left:3px solid #d94f45;border-radius:0 10px 10px 0;padding:.75rem 1rem;margin:.9rem 0;font-size:.85rem;color:#943a32;line-height:1.6}
.man-warn i{color:#d94f45;margin-right:.45rem}

.mde-mock{background:#fff;border:1px solid #e3e6eb;border-radius:12px;padding:1rem 1.2rem;margin:.9rem 0;font-size:.86rem;color:#374151;line-height:1.75}
.mde-mock-label{color:#94a3b8;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.2rem}
.mde-chip{display:inline-block;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:999px;padding:.15rem .6rem;font-size:.78rem;color:#334155;margin:.15rem .2rem .15rem 0}

/* Recortes reais da ficha pública da Eletroli */
.mde-shot{margin:1.1rem 0}
.mde-shot img{max-width:100%;height:auto;display:block;border:1px solid #ece7de;border-radius:10px;box-shadow:0 4px 16px rgba(26,35,50,.08)}
.mde-shot-cap{display:flex;align-items:center;gap:.4rem;color:#94a3b8;font-size:.74rem;font-weight:600;margin-top:.5rem}
.mde-shot-cap i{color:#f97316}

.mde-final{background:linear-gradient(135deg,#1e3a5f,#2c5282);border-radius:16px;padding:1.6rem 1.8rem;margin-top:2.4rem;color:#fff}
.mde-final h3{font-family:'Poppins',sans-serif;font-weight:600;font-size:1.05rem;margin-bottom:.5rem}
.mde-final p{color:#cbd5e1;font-size:.88rem;line-height:1.7;margin-bottom:.9rem}
.mde-final a{display:inline-flex;align-items:center;gap:.4rem;background:#f97316;color:#fff;font-weight:700;font-size:.85rem;padding:.6rem 1.1rem;border-radius:9px;text-decoration:none}

<?php if ($modoPdf): ?>
/* Dompdf não lida bem com display:flex pra centralizar texto — mesma correção já usada em
   ajuda/manual.php: bolinha do passo centralizada por line-height, não flex. */
.mde-pdf-link{display:none}
.man-step{display:block}
.man-step-n{display:inline-block;text-align:center;line-height:22px;float:left;margin-right:.7rem}
.man-step-t{display:block;overflow:hidden}
.man-h2,.man-tip i,.man-warn i{font-family:'DejaVu Sans',sans-serif}
.man-h2 i,.mde-shot-cap i{display:none}
@page{margin:1.6cm;size:A4}
<?php endif; ?>
</style>

<div class="mde-wrap">
  <div class="mde-content">

    <?php if (!$modoPdf): ?>
    <a href="<?= url('/empresa/perfil-publico/manual/pdf') ?>" class="mde-pdf-link"><i class="bi bi-file-earmark-pdf"></i> Baixar em PDF</a>
    <a href="<?= url('/empresa/perfil-publico') ?>" class="mde-back"><i class="bi bi-arrow-left"></i> Voltar pro Editar Diretório</a>
    <?php endif; ?>

    <div class="mde-eyebrow">Manual · Diretório</div>
    <h1 class="mde-title">Como editar o perfil do seu Diretório</h1>
    <p class="mde-lead">Passo a passo de cada campo da tela <strong>Editar Diretório</strong> (Empresa → Perfil Público) e o que ele muda na sua ficha pública — <code>fixaos.com.br/assistencias/{sua-empresa}</code>. Os exemplos abaixo usam a <strong>Eletroli Assistência Técnica</strong>, uma empresa real do FixaOS, com autorização pra usar o perfil dela como referência — cada recorte mostra o resultado de verdade na página pública.</p>

    <div class="man-section">
      <h2 class="man-h2"><i class="bi bi-shop-window"></i> Identificação da empresa</h2>
      <p class="man-p">O primeiro card da tela. É o nome, WhatsApp e endereço que aparecem no topo da sua ficha pública e no mapa de localização.</p>
      <div class="man-step"><div class="man-step-n">1</div><div class="man-step-t"><strong>Nome da empresa</strong> — vira o título da página e a URL (<em>slug</em>). A Eletroli preencheu <strong>"Eletroli Assistencia Técnica"</strong>.</div></div>
      <div class="man-step"><div class="man-step-n">2</div><div class="man-step-t"><strong>WhatsApp público</strong> — é o número do botão verde "Chamar no WhatsApp" que qualquer visitante vê. A Eletroli usa um número próprio da loja, diferente do WhatsApp interno de atendimento de OS.</div></div>
      <div class="man-step"><div class="man-step-n">3</div><div class="man-step-t"><strong>Endereço</strong> (CEP, Logradouro, Número, Complemento, Bairro, Cidade, UF) — alimenta o mapa da ficha pública. Preenchido certo, o sistema geocodifica sozinho e já posiciona o pino no mapa.</div></div>
      <div class="mde-shot">
        <img src="<?= url('/img/manual/eletroli-banner.png') ?>" alt="Banner da ficha pública da Eletroli, com nome e cidade">
        <div class="mde-shot-cap"><i class="bi bi-camera-fill"></i> Resultado: Nome + Endereço (cidade) formam o banner do topo.</div>
      </div>
      <div class="mde-shot">
        <img src="<?= url('/img/manual/eletroli-localizacao.jpg') ?>" alt="Mapa de localização da Eletroli na ficha pública">
        <div class="mde-shot-cap"><i class="bi bi-camera-fill"></i> Resultado: Endereço completo vira o card "Localização" com mapa.</div>
      </div>
    </div>

    <div class="man-section">
      <h2 class="man-h2"><i class="bi bi-globe2"></i> Site e redes sociais, Especialidades e Horário</h2>
      <p class="man-p">Três blocos diferentes da tela de edição, mas que aparecem juntos no mesmo cartão <strong>"Entre em contato"</strong> da ficha pública:</p>
      <div class="man-step"><div class="man-step-n">1</div><div class="man-step-t"><strong>Site e redes sociais</strong> — preencha <strong>Site</strong>, <strong>E-mail público</strong>, <strong>Instagram</strong>, <strong>Facebook</strong>, <strong>YouTube</strong> e/ou <strong>TikTok</strong>; todos opcionais, um usuário (sem "@") já basta.</div></div>
      <div class="man-step"><div class="man-step-n">2</div><div class="man-step-t"><strong>Especialidades</strong> — tags curtas (marca, tipo de aparelho, bairro atendido); digite e aperte <strong>Enter</strong> pra adicionar. Viram "pílulas" coloridas.</div></div>
      <div class="man-step"><div class="man-step-n">3</div><div class="man-step-t"><strong>Horário de funcionamento</strong> — ligue/desligue cada dia e defina o horário de abre/fecha; a ficha destaca o dia de hoje sozinha.</div></div>
      <div class="mde-shot">
        <img src="<?= url('/img/manual/eletroli-contato.png') ?>" alt="Card Entre em contato, Especialidades e Horário de funcionamento da ficha pública da Eletroli">
        <div class="mde-shot-cap"><i class="bi bi-camera-fill"></i> Resultado: os três blocos empilhados na sidebar da ficha pública.</div>
      </div>
    </div>

    <div class="man-section">
      <h2 class="man-h2"><i class="bi bi-card-text"></i> Descrição pública</h2>
      <p class="man-p">Texto livre (com negrito, se quiser) que vira o card <strong>"Sobre a empresa"</strong> da sua ficha — o espaço pra contar o que você conserta, seus diferenciais e sua experiência, em suas próprias palavras.</p>
      <div class="man-step"><div class="man-step-n">1</div><div class="man-step-t">Digite direto no campo, ou clique em <strong>"Preencher com IA"</strong> e informe o que você conserta, anos de experiência e diferenciais — o sistema já busca o resto (nome/cidade) da sua empresa sozinho e gera um texto pronto pra revisar.</div></div>
      <div class="man-tip"><i class="bi bi-lightbulb-fill"></i>A Eletroli usou esse campo pra listar os tipos de defeito que conserta (tela escura, não liga, prato parado do micro-ondas etc.) e os diferenciais (diagnóstico sem taxa escondida, garantia de 90 dias) — isso ajuda tanto o cliente quanto o Google a entenderem o que a loja faz.</div>
      <div class="mde-shot">
        <img src="<?= url('/img/manual/eletroli-sobre.png') ?>" alt="Card Sobre a empresa da ficha pública da Eletroli">
        <div class="mde-shot-cap"><i class="bi bi-camera-fill"></i> Resultado: card "Sobre a empresa" na ficha pública.</div>
      </div>
    </div>

    <div class="man-section">
      <h2 class="man-h2"><i class="bi bi-scissors"></i> Serviços oferecidos</h2>
      <p class="man-p">Diferente de Especialidades (tags soltas), aqui cada linha é um serviço de verdade — categoria + nome — e ganha um ícone próprio na ficha pública.</p>
      <div class="man-step"><div class="man-step-n">1</div><div class="man-step-t">Clique em <strong>"+ Adicionar"</strong>, escolha uma categoria (ex.: TV) e dê um nome ao serviço. A Eletroli cadastrou <strong>TV</strong> e <strong>Micro-ondas</strong>.</div></div>
    </div>

    <div class="man-section">
      <h2 class="man-h2"><i class="bi bi-image-fill"></i> Logo e fotos da empresa</h2>
      <p class="man-p">Fica na coluna lateral da tela de edição, com dois blocos:</p>
      <div class="man-step"><div class="man-step-n">1</div><div class="man-step-t"><strong>Logo</strong> — aparece ao lado do nome, no topo da ficha pública e no cartão de busca do Diretório.</div></div>
      <div class="man-step"><div class="man-step-n">2</div><div class="man-step-t"><strong>Fotos da empresa</strong> — até 4 fotos: a 1ª enviada vira a <strong>capa</strong> (foto grande em destaque), as outras 3 formam a <strong>galeria</strong> (carrossel, clicável). Dá pra trocar qual é a capa a qualquer momento.</div></div>
      <div class="man-tip"><i class="bi bi-info-circle-fill"></i>A Eletroli usou as 4 fotos: a bancada com peças de TV como capa, mais 2 fotos de reparo e um banner de divulgação na galeria.</div>
      <div class="mde-shot">
        <img src="<?= url('/img/manual/eletroli-fotos.jpg') ?>" alt="Carrossel de fotos da Eletroli na ficha pública">
        <div class="mde-shot-cap"><i class="bi bi-camera-fill"></i> Resultado: card "Fotos" com a capa grande + miniaturas da galeria.</div>
      </div>
    </div>

    <div class="man-section">
      <h2 class="man-h2"><i class="bi bi-star-fill"></i> Exibir avaliações de clientes</h2>
      <p class="man-p">Um interruptor dentro do card de Identificação. Ligado (padrão), a ficha pública mostra a nota média, os comentários recebidos e um formulário pra novos clientes avaliarem. Desligado, a seção some da página — as avaliações já recebidas continuam guardadas, só não aparecem.</p>
      <div class="mde-shot">
        <img src="<?= url('/img/manual/eletroli-resumo.png') ?>" alt="Nome, logo e nota de avaliações da Eletroli na ficha pública">
        <div class="mde-shot-cap"><i class="bi bi-camera-fill"></i> Resultado: nota média (★★★★★ 5.0, 2 avaliações) no cartão de resumo.</div>
      </div>
    </div>

    <div class="man-section">
      <h2 class="man-h2"><i class="bi bi-save-fill"></i> Salvando</h2>
      <div class="man-step"><div class="man-step-n">1</div><div class="man-step-t">Identificação, endereço, site/redes, descrição, especialidades, serviços, logo e horário salvam todos juntos no botão <strong>"Salvar perfil público"</strong>, no fim da página.</div></div>
      <div class="man-step"><div class="man-step-n">2</div><div class="man-step-t">Fotos da empresa (capa/galeria) têm botão próprio por foto (<strong>enviar</strong>/<strong>remover</strong>/<strong>tornar capa</strong>) — a mudança já vale na hora, sem precisar clicar em "Salvar perfil público".</div></div>
      <div class="man-warn"><i class="bi bi-exclamation-triangle-fill"></i>Só empresas <strong>reivindicadas</strong> (alguém já logou e confirmou ser dona do perfil) têm acesso a esta tela — e só <strong>nome da empresa e endereço</strong> são obrigatórios pra publicar a ficha.</div>
    </div>

    <div class="man-section">
      <h2 class="man-h2"><i class="bi bi-eye-fill"></i> Visualizações e destaque <span style="font-weight:400;color:#8a8f9c;font-size:.78rem">(plano pago)</span></h2>
      <p class="man-p">Exclusivo de quem assina um plano pago do FixaOS ou contrata destaque: a contagem real de visualizações do perfil e o selo "Empresa cliente FixaOS" na sidebar — sem plano ativo, esse número fica borrado.</p>
      <div class="mde-shot">
        <img src="<?= url('/img/manual/eletroli-destaque.png') ?>" alt="Contador de visualizações e selo Empresa cliente FixaOS na ficha da Eletroli">
        <div class="mde-shot-cap"><i class="bi bi-camera-fill"></i> Resultado: visualizações + selo, visíveis porque a Eletroli tem plano ativo.</div>
      </div>
    </div>

    <div class="mde-final">
      <h3>Veja o resultado completo</h3>
      <p>Todos esses blocos juntos formam a ficha pública da Eletroli, de verdade, no ar agora.</p>
      <a href="<?= url('/assistencias') ?>" target="_blank"><i class="bi bi-box-arrow-up-right"></i> Abrir o Diretório público</a>
    </div>

  </div>
</div>
