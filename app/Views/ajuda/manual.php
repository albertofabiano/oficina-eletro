<?php $titulo = 'Manual do Usuário'; $modoPdf = !empty($modoPdf); ?>
<style>
<?php if (!$modoPdf): ?>
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap');
<?php endif; ?>

/* Layout geral — fundo quente, editorial */
.manual-wrap{display:flex;gap:0;min-height:calc(100vh - 56px);background:#faf8f5;font-family:'Inter',sans-serif}

/* Nav lateral — carvão suave, não preto puro */
.manual-sidebar{
  width:256px;flex-shrink:0;
  background:#1c2029;
  border-right:1px solid rgba(255,255,255,.06);
  position:sticky;top:56px;
  height:calc(100vh - 56px);
  overflow-y:auto;padding:1.4rem 0;
}
.man-nav-title{
  font-size:.68rem;color:#7d8394;
  text-transform:uppercase;letter-spacing:.11em;
  padding:.5rem 1.3rem .3rem;font-weight:600;margin-top:1.1rem;
}
.man-nav-link{
  display:block;padding:.4rem 1.3rem;
  color:#a8adba;font-size:.84rem;
  text-decoration:none;
  border-left:2px solid transparent;
  transition:.15s;
}
.man-nav-link:hover{color:#fff;background:rgba(255,255,255,.035)}
.man-nav-link.active{color:#fff;border-left-color:#f97316;background:rgba(249,115,22,.09);font-weight:500}

/* Área de conteúdo */
.manual-content{
  flex:1;padding:3rem 3.5rem 5rem;
  max-width:860px;
  background:#fffefc;
  border-right:1px solid #ece7de;
}

/* Seções */
.man-section{margin-bottom:3.6rem;scroll-margin-top:72px}

/* Títulos — serif elegante pros H2, sans pro resto */
.man-h2{
  font-family:'Poppins',sans-serif;
  font-size:1.5rem;font-weight:600;
  color:#1a2332;
  margin-bottom:1.35rem;padding-bottom:.9rem;
  display:flex;align-items:center;gap:.65rem;
  letter-spacing:-.01em;
  border-bottom:1px solid #ece7de;
  position:relative;
}
.man-h2::after{content:'';position:absolute;left:0;bottom:-1px;width:38px;height:2px;background:#f97316}
.man-h2 i{color:#f97316;font-size:1.05rem}
.man-h3{
  font-family:'Poppins',sans-serif;
  font-size:.98rem;font-weight:600;
  color:#1e3a5f;
  margin:1.7rem 0 .7rem;
}

/* Parágrafos */
.man-p{color:#4b5261;font-size:.96rem;line-height:1.85;margin-bottom:1rem}

/* Passos */
.man-step{
  display:flex;gap:.95rem;align-items:flex-start;
  background:#fbf9f6;
  border:1px solid #ece7de;
  border-left:3px solid #f97316;
  border-radius:0 10px 10px 0;
  padding:.85rem 1.1rem;margin-bottom:.55rem;
}
.man-step-n{
  width:24px;height:24px;border-radius:50%;
  background:#f97316;color:#fff;
  font-weight:700;font-size:.76rem;
  display:flex;align-items:center;justify-content:center;
  flex-shrink:0;margin-top:2px;
}
.man-step-t{color:#333a47;font-size:.9rem;line-height:1.65}
.man-step-t strong{color:#1e3a5f;font-weight:600}

/* Avisos */
.man-tip{
  background:#fff8f0;border:1px solid #f6ddbc;
  border-left:3px solid #f97316;
  border-radius:0 10px 10px 0;
  padding:.8rem 1.05rem;margin:1rem 0;
  font-size:.87rem;color:#8a5a1f;line-height:1.6;
}
.man-tip i{color:#f97316;margin-right:.5rem}
.man-warn{
  background:#fdf4f3;border:1px solid #f3cdc9;
  border-left:3px solid #d94f45;
  border-radius:0 10px 10px 0;
  padding:.8rem 1.05rem;margin:1rem 0;
  font-size:.87rem;color:#943a32;line-height:1.6;
}
.man-warn i{color:#d94f45;margin-right:.5rem}

/* Tabelas — cabeçalho em texto normal, não gritado */
.man-table{width:100%;border-collapse:collapse;margin:1.1rem 0;font-size:.88rem;border:1px solid #ece7de;border-radius:10px;overflow:hidden}
.man-table th{
  background:#f6f3ee;color:#5b6270;
  padding:.6rem 1rem;text-align:left;
  font-size:.8rem;font-weight:600;
  border-bottom:1px solid #ece7de;
}
.man-table td{padding:.6rem 1rem;border-bottom:1px solid #f1ede5;color:#4b5261;vertical-align:top}
.man-table tr:last-child td{border-bottom:none}
.man-table td:first-child{color:#1e3a5f;font-weight:600}

/* Badge */
.man-badge{display:inline-block;border-radius:5px;padding:.15rem .55rem;font-size:.72rem;font-weight:600}
.kbd{background:#f6f3ee;border:1px solid #ddd6c7;border-radius:5px;padding:.15rem .5rem;font-size:.8rem;color:#333a47;font-family:monospace}

/* Breadcrumb topo */
.man-header-bar{
  background:#fffefc;border-bottom:1px solid #ece7de;
  padding:.7rem 3.5rem;
  font-size:.82rem;color:#8a8f9c;
  position:sticky;top:56px;z-index:10;
}
.man-header-bar span{color:#1e3a5f;font-weight:600}

@media(max-width:768px){
  .manual-sidebar{display:none}
  .manual-content{padding:1.3rem;border:none}
  .man-header-bar{padding:.7rem 1.3rem}
}

/* Versão PDF: uma coluna só, sem sidebar/sticky, com quebra de página sensata.
   Usa DejaVu Sans (fonte já embutida no Dompdf) em vez de Helvetica/Arial —
   a fonte core do PDF não tem glifo pra "→"/"—" e vira "?"; DejaVu cobre. */
.manual-wrap-pdf{display:block;background:#fff;min-height:0;font-family:'DejaVu Sans',sans-serif}
.manual-wrap-pdf .manual-content{max-width:100%;border-right:none;padding:0}
.manual-wrap-pdf .man-section{page-break-inside:auto}
.manual-wrap-pdf .man-step,.manual-wrap-pdf .man-tip,.manual-wrap-pdf .man-warn,.manual-wrap-pdf .man-table{page-break-inside:avoid}
/* Dompdf não carrega a webfont de ícones de forma confiável (mesmo padrão já
   usado nos outros PDFs do sistema, que também evitam ícone-fonte) — esconde. */
.manual-wrap-pdf .man-h2 i,.manual-wrap-pdf .man-tip i,.manual-wrap-pdf .man-warn i{display:none}
.manual-wrap-pdf .man-h2,.manual-wrap-pdf .man-h3{font-family:'DejaVu Sans',sans-serif}
/* Dompdf não centraliza texto com display:flex de forma confiável — troca a
   bolinha numerada do passo pra centralização por line-height, e usa azul
   (passos) e verde (dicas) em vez de laranja pra dar mais variedade de cor. */
.manual-wrap-pdf .man-step-n{display:block;text-align:center;line-height:24px;background:#2f6fb0}
.manual-wrap-pdf .man-step{border-left-color:#2f6fb0}
.manual-wrap-pdf .man-tip{background:#eef8f1;border-color:#bfe0c9;border-left-color:#1f9d5c;color:#1f6b45}
.manual-wrap-pdf .man-tip strong{color:#175236}
@page{margin:1.6cm;size:A4}
</style>
<div class="manual-wrap<?= $modoPdf ? ' manual-wrap-pdf' : '' ?>">

<?php if (!$modoPdf): ?>
<!-- Sidebar de navegação -->
<div class="manual-sidebar">
  <div style="padding:.5rem 1.2rem 1rem;border-bottom:1px solid #2d3139">
    <div style="color:#fff;font-weight:700;font-size:.9rem">Manual do Usuário</div>
    <div style="color:#4b5563;font-size:.75rem">FixaOS v1.1</div>
  </div>
  <?php
  // Fonte única em manual_secoes() (app/Helpers/functions.php) — também usada por
  // BuscaController::buscar() pra busca não ficar pra trás quando uma seção nova é adicionada.
  $sections = manual_secoes();
  foreach($sections as$group=>$links):?>
  <div class="man-nav-title"><?=$group?></div>
  <?php foreach($links as[$id,$label]):?>
  <a href="#<?=$id?>" class="man-nav-link"><?=$label?></a>
  <?php endforeach;endforeach;?>
</div>
<?php endif; ?>

<!-- Conteúdo -->
<div class="manual-content">

  <?php if ($modoPdf): ?>
  <!-- Capa (só na versão PDF) -->
  <div style="margin-bottom:2.5rem;padding-bottom:1.6rem;border-bottom:1px solid #ece7de">
    <div style="font-family:'Poppins',sans-serif;font-weight:700;font-size:.95rem;color:#f97316;letter-spacing:.02em">FixaOS</div>
    <div style="font-family:'Poppins',sans-serif;font-weight:600;font-size:2rem;color:#1a2332;margin-top:.3rem">Manual do Usuário</div>
    <div style="color:#8a8f9c;font-size:.85rem;margin-top:.4rem">Gerado em <?= date('d/m/Y') ?></div>
  </div>
  <?php else: ?>
  <!-- Busca no manual -->
  <div class="mb-4" style="background:linear-gradient(135deg,#1e3a5f,#2c5282);border-radius:16px;padding:1.75rem;box-shadow:0 10px 30px rgba(30,58,95,.25)">
    <div class="d-flex align-items-center gap-2 mb-2">
      <i class="bi bi-search text-white" style="font-size:1.5rem"></i>
      <span class="text-white fw-bold" style="font-size:1.25rem">Buscar no manual</span>
    </div>
    <div class="text-white-50 mb-3">Encontre rapidamente qualquer artigo deste manual</div>
    <input type="text" id="buscaInput" class="form-control form-control-lg" placeholder="Digite para buscar..." oninput="buscaGlobalInput()" autocomplete="off">
    <div id="buscaResultados" class="mt-2" style="display:none;background:#fff;border-radius:12px;overflow:hidden;max-height:440px;overflow-y:auto;box-shadow:0 12px 32px rgba(0,0,0,.25)"></div>
  </div>
  <div class="mb-4">
    <a href="<?= url('/manual/pdf') ?>" class="btn btn-outline-secondary btn-sm" style="border-radius:8px">
      <i class="bi bi-file-earmark-pdf me-1"></i>Baixar em PDF
    </a>
  </div>
  <?php endif; ?>

  <!-- Logo e Dados da Empresa -->
  <div class="man-section" id="cfg-empresa">
    <h2 class="man-h2"><i class="bi bi-building-fill"></i> Logo e Dados da Empresa</h2>
    <p class="man-p">Antes de usar o sistema no dia a dia, configure a identidade da sua empresa. Essas informações aparecem nos comprovantes impressos, no rodapé do sistema e no perfil público do diretório. Acesse pelo botão <strong>Configurações do Sistema</strong> na sidebar, ou em <strong>Configurações → Empresa</strong>.</p>

    <h3 class="man-h3">Logo da empresa</h3>
    <div class="man-step"><div class="man-step-n">1</div><div class="man-step-t">No topo da página, escolha um arquivo de imagem (PNG ou JPG). Um preview aparece na hora.</div></div>
    <div class="man-step"><div class="man-step-n">2</div><div class="man-step-t">Clique em <strong>Salvar</strong> para enviar. Para remover uma logo já enviada, use o botão de remover ao lado do preview.</div></div>
    <div class="man-tip"><i class="bi bi-lightbulb-fill"></i>Se você não enviar uma logo, o sistema usa automaticamente a marca padrão do FixaOS no lugar dela — nada fica em branco.</div>

    <h3 class="man-h3">Dados cadastrais</h3>
    <div class="man-step"><div class="man-step-n">1</div><div class="man-step-t">Preencha <strong>Razão Social</strong>, <strong>CNPJ</strong> e <strong>Nome Fantasia</strong> (o nome fantasia é o que aparece para os clientes).</div></div>
    <div class="man-step"><div class="man-step-n">2</div><div class="man-step-t">Informe <strong>E-mail</strong>, <strong>Telefone</strong> e <strong>WhatsApp</strong> de contato.</div></div>

    <h3 class="man-h3">Endereço</h3>
    <div class="man-step"><div class="man-step-n">1</div><div class="man-step-t">Preencha <strong>CEP</strong>, <strong>Logradouro</strong>, <strong>Número</strong> e <strong>Complemento</strong>.</div></div>
    <div class="man-step"><div class="man-step-n">2</div><div class="man-step-t">Clique em <strong>Salvar</strong> no final da página. As mudanças aparecem imediatamente na sidebar, nos comprovantes impressos e no diretório público.</div></div>

    <h3 class="man-h3">Taxas de cartão e Pix na maquininha</h3>
    <p class="man-p">Na aba <strong>Cartões</strong>, informe a taxa cobrada pela sua maquininha para <strong>Débito</strong>, <strong>Crédito</strong> (uma taxa por número de parcelas) e <strong>Pix (via maquininha)</strong>. Deixe em branco ou zero pra forma de pagamento que não tem taxa nenhuma.</p>
    <div class="man-tip"><i class="bi bi-info-circle-fill"></i>O campo "Pix (via maquininha)" é só pra quando o <strong>cliente final</strong> paga um Pix passando o cartão/QR na sua maquininha — diferente do Pix recebido direto na sua conta do banco, que continua sem taxa nenhuma. Nas telas de pagamento (Fechar OS, PDV, Receber OS), escolha "PIX (maquininha)" em vez de "PIX" simples quando for esse o caso.</div>
    <div class="man-tip"><i class="bi bi-lightbulb-fill"></i>Uma vez configuradas, essas taxas calculam sozinhas a despesa em qualquer lugar que recebe pagamento: fechamento de OS, PDV, atalho "Receber OS" no Fluxo de Caixa e Adiantamento de OS.</div>
  </div>

  <!-- Visão geral -->
  <div class="man-section" id="inicio">
    <h2 class="man-h2"><i class="bi bi-grid-1x2-fill"></i> Visão geral do FixaOS</h2>
    <p class="man-p">O FixaOS é um sistema de gestão completo para assistências técnicas de eletrônicos e eletrodomésticos. Ele centraliza tudo que você precisa: ordens de serviço, clientes, estoque, financeiro, agenda e muito mais.</p>
    <p class="man-p">O sistema é 100% web — funciona no navegador, sem instalação. Você acessa pelo computador, tablet ou celular.</p>
    <div class="man-tip"><i class="bi bi-lightbulb-fill"></i><strong>Dica:</strong> Salve o link do sistema como favorito no navegador para acessar com um clique.</div>

    <h3 class="man-h3">Módulos disponíveis</h3>
    <table class="man-table">
      <thead><tr><th>Módulo</th><th>O que faz</th></tr></thead>
      <tbody>
        <?php foreach([
          ['Dashboard','Visão geral com KPIs, gráficos e OS recentes.'],
          ['Ordens de Serviço','Abertura, acompanhamento e fechamento de atendimentos.'],
          ['Clientes / CRM','Cadastro e histórico de clientes, pipeline de vendas.'],
          ['Estoque','Peças, componentes, movimentações e fornecedores.'],
          ['Financeiro','Lançamentos, fluxo de caixa e comissões.'],
          ['Agenda','Calendário de atendimentos e compromissos.'],
          ['Relatórios','Gráficos e exportação de dados por período.'],
          ['Marketplace','Compra e venda de peças com outras assistências.'],
          ['Configurações','Empresa, usuários, status e personalizações.'],
        ] as[$m,$d]):?>
        <tr><td><strong><?=$m?></strong></td><td><?=$d?></td></tr>
        <?php endforeach;?>
      </tbody>
    </table>
  </div>

  <!-- Dashboard -->
  <div class="man-section" id="dashboard">
    <h2 class="man-h2"><i class="bi bi-speedometer2"></i> Dashboard</h2>
    <p class="man-p">O Dashboard é a tela inicial do sistema. Ele mostra um resumo em tempo real da operação da sua assistência.</p>
    <h3 class="man-h3">O que você vê no Dashboard</h3>
    <ul style="color:#4b5563;font-size:.9rem;line-height:2;padding-left:1.2rem">
      <li><strong style="color:#1e293b">KPIs:</strong> OS abertas, concluídas, faturamento do mês e ticket médio.</li>
      <li><strong style="color:#1e293b">Gráfico de faturamento:</strong> Evolução dos últimos 6 meses.</li>
      <li><strong style="color:#1e293b">Status das OS:</strong> Distribuição por status em gráfico de pizza.</li>
      <li><strong style="color:#1e293b">Últimas OS:</strong> Lista das ordens mais recentes com status e valor.</li>
      <li><strong style="color:#1e293b">Top serviços:</strong> Ranking dos serviços mais executados no mês.</li>
    </ul>
  </div>

  <!-- Navegação -->
  <div class="man-section" id="navegacao">
    <h2 class="man-h2"><i class="bi bi-compass"></i> Navegação e atalhos</h2>
    <p class="man-p">A sidebar esquerda agrupa os módulos em seções expansíveis (clique no nome do grupo para abrir/fechar — todas começam fechadas). Logo abaixo do Dashboard ficam botões coloridos de acesso rápido: <strong>Dashboard</strong>, <strong>PDV/Frente de Caixa</strong>, <strong>Abrir Nova OS</strong>, <strong>Produtos</strong>, <strong>Clientes</strong>, <strong>Financeiro</strong>, <strong>Relatórios</strong> e <strong>Configurações do Sistema</strong> — cada um com uma cor diferente para facilitar a identificação.</p>
    <div class="man-tip"><i class="bi bi-info-circle-fill"></i>Em dispositivos móveis, use o botão de menu (☰) no topo para abrir a sidebar.</div>
    <div class="man-tip"><i class="bi bi-lightbulb-fill"></i>Use a busca no topo da página do Manual para encontrar rapidamente um artigo de ajuda sem precisar navegar pelos menus (é uma busca separada da busca global do sistema, abaixo — essa aqui só procura neste Manual).</div>
  </div>

  <!-- Busca Global -->
  <div class="man-section" id="busca-global">
    <h2 class="man-h2"><i class="bi bi-search"></i> Busca global</h2>
    <p class="man-p">O campo de busca no topo do sistema procura em <strong>Ordens de Serviço</strong>, <strong>Clientes</strong> e <strong>Produtos</strong> ao mesmo tempo — de qualquer tela, sem precisar abrir o módulo primeiro.</p>
    <div class="man-step"><div class="man-step-n">1</div><div class="man-step-t">Digite pelo menos 2 letras: número da OS, nome/telefone/CPF do cliente, ou nome/código do produto.</div></div>
    <div class="man-step"><div class="man-step-n">2</div><div class="man-step-t">Os resultados aparecem agrupados por categoria (OS, Cliente, Produto) conforme você digita.</div></div>
    <div class="man-step"><div class="man-step-n">3</div><div class="man-step-t">Use as setas <strong>↑ ↓</strong> para navegar e <strong>Enter</strong> para abrir o item selecionado, ou clique direto no resultado.</div></div>
    <div class="man-tip"><i class="bi bi-lightbulb-fill"></i>No celular, toque no ícone de lupa ao lado do sino de notificações para abrir a busca.</div>
    <div class="man-tip"><i class="bi bi-info-circle-fill"></i>A busca só traz resultados da sua própria empresa — nunca mistura dados de outras assistências que usam o FixaOS.</div>
  </div>

  <!-- Abrir OS -->
  <div class="man-section" id="os-abrir">
    <h2 class="man-h2"><i class="bi bi-plus-circle-fill"></i> Abrir nova Ordem de Serviço</h2>
    <p class="man-p">Acesse <strong>OS → Nova OS</strong> ou clique no botão azul na sidebar. O formulário é dividido em 4 abas:</p>

    <h3 class="man-h3">Aba 1 — Cliente</h3>
    <div class="man-step"><div class="man-step-n">1</div><div class="man-step-t">Digite o nome do cliente no campo de busca. O sistema sugere clientes cadastrados em tempo real.</div></div>
    <div class="man-step"><div class="man-step-n">2</div><div class="man-step-t">Se o cliente não existir, clique em <strong>"Novo cliente"</strong> para cadastrar sem sair da OS.</div></div>

    <h3 class="man-h3">Aba 2 — Equipamento</h3>
    <div class="man-step"><div class="man-step-n">1</div><div class="man-step-t">Selecione o tipo, marca e informe o modelo, número de série e condição de entrada.</div></div>
    <div class="man-step"><div class="man-step-n">2</div><div class="man-step-t">Marque os acessórios entregues pelo cliente (carregador, capa, cabos etc.).</div></div>
    <div class="man-tip"><i class="bi bi-magic"></i><strong>Os campos se ajustam sozinhos conforme o Tipo escolhido.</strong> Ao selecionar Celular, Smartphone, Tablet, iPhone ou iPad, aparecem os campos <strong>Cor</strong>, <strong>IMEI</strong> (com botão "Buscar" para preencher marca/modelo automaticamente e checar bloqueio, e botão "Consulta Anatel" para validar o IMEI na base oficial) e <strong>Senha de desbloqueio</strong>. Ao selecionar Notebook, Computador, Desktop ou PC, aparecem os campos de <strong>Tipo de armazenamento (HD/SSD)</strong>, <strong>Memória RAM</strong>, <strong>Placa de vídeo</strong>, <strong>Placa-mãe</strong> e <strong>Processador</strong>. Para os demais tipos de equipamento, só os campos básicos aparecem.</div>

    <h3 class="man-h3">Aba 3 — Defeito</h3>
    <div class="man-step"><div class="man-step-n">1</div><div class="man-step-t">Descreva o defeito relatado pelo cliente. Seja específico: <em>"Não liga ao apertar o botão Power"</em>.</div></div>
    <div class="man-step"><div class="man-step-n">2</div><div class="man-step-t">Informe observações internas se houver (não aparecem na impressão do cliente).</div></div>

    <h3 class="man-h3">Aba 4 — Configurações</h3>
    <div class="man-step"><div class="man-step-n">1</div><div class="man-step-t">Defina técnico responsável, prioridade, valor estimado e prazo de previsão.</div></div>
    <div class="man-step"><div class="man-step-n">2</div><div class="man-step-t">Clique em <strong>"Salvar OS"</strong>. O número será gerado automaticamente.</div></div>

    <div class="man-tip"><i class="bi bi-lightbulb-fill"></i>O número da OS é gerado com o prefixo configurado em <strong>Configurações</strong>. Padrão: OS000001.</div>
  </div>

  <!-- Cadastro de equipamento por IA -->
  <div class="man-section" id="equip-scanner">
    <h2 class="man-h2"><i class="bi bi-phone-fill"></i> Cadastro de equipamento pela câmera (sem digitar)</h2>
    <p class="man-p">Na aba <strong>Equipamento</strong> do formulário de OS, clique em <strong>"Preencher pela câmera do celular"</strong> — o sistema lê a etiqueta do aparelho e preenche marca, modelo, número de série e tipo automaticamente, sem precisar digitar nada.</p>
    <div class="man-step"><div class="man-step-n">1</div><div class="man-step-t">Clique em <strong>"Preencher pela câmera do celular"</strong>. Aparece um QR Code e um código de 6 dígitos na tela do computador.</div></div>
    <div class="man-step"><div class="man-step-n">2</div><div class="man-step-t">No celular, aponte a câmera pro QR Code. Se a câmera não ler, acesse a página de pareamento e digite o código.</div></div>
    <div class="man-step"><div class="man-step-n">3</div><div class="man-step-t">Tire uma foto nítida da etiqueta do equipamento (geralmente na parte de trás ou embaixo do aparelho, com o modelo e o número de série).</div></div>
    <div class="man-step"><div class="man-step-n">4</div><div class="man-step-t">A IA lê a etiqueta e envia os dados pro computador — marca, modelo, número de série e tipo já aparecem preenchidos na tela.</div></div>
    <div class="man-step"><div class="man-step-n">5</div><div class="man-step-t">Confira os dados, ajuste se algo não ficou perfeito e continue o cadastro da OS normalmente.</div></div>
    <div class="man-tip"><i class="bi bi-lightbulb-fill"></i>Cada leitura de etiqueta por IA consome 1 crédito de scan de equipamento. Acompanhe o saldo e compre mais créditos em <strong>Configurações</strong>, se precisar.</div>
  </div>

  <!-- Fotos de entrada por WhatsApp -->
  <div class="man-section" id="os-fotos-whatsapp">
    <h2 class="man-h2"><i class="bi bi-camera-fill"></i> Fotos do estado de entrada <span class="man-badge" style="background:#dcfce7;color:#166534;margin-left:.4rem">⭐ Destaque</span></h2>
    <div class="man-tip" style="background:#f0fdf4;border-color:#86efac;border-left-color:#22c55e;color:#166534">
      <i class="bi bi-shield-check" style="color:#22c55e"></i><strong>Proteja sua assistência.</strong> Registre o estado do aparelho (riscos, trincas, tela quebrada) no ato da entrada, com foto. Evita reclamação depois do tipo "esse dano não tinha quando entreguei".
    </div>
    <p class="man-p">Na aba <strong>Equipamento</strong> do formulário de OS, use o card <strong>"Fotos do estado de entrada"</strong> — aceita até <strong>6 fotos</strong>, comprimidas em webp direto no aparelho antes de anexar.</p>
    <div class="man-step"><div class="man-step-n">1</div><div class="man-step-t">Clique em <strong>"Adicionar foto"</strong> para escolher fotos já tiradas (ou usar a câmera do próprio computador/celular que está preenchendo a OS), ou em <strong>"Tirar foto pelo celular"</strong> para fotografar de outro aparelho (mesmo pareamento por QR Code usado no cadastro de equipamento por IA).</div></div>
    <div class="man-step"><div class="man-step-n">2</div><div class="man-step-t">Registre arranhões, amassados, trincas, tela quebrada etc. — o contador no topo do card mostra quantas das 4 já foram adicionadas.</div></div>
    <div class="man-step"><div class="man-step-n">3</div><div class="man-step-t">Salve a OS normalmente. As fotos ficam anexadas a ela.</div></div>
    <div class="man-tip"><i class="bi bi-lightbulb-fill"></i>Essas fotos também aparecem na <strong>página pública de acompanhamento</strong> da OS (o link que o cliente recebe) — ele consegue ver o estado de entrada registrado sem precisar pedir.</div>

    <h3 class="man-h3">Adicionar fotos depois, direto na tela da OS</h3>
    <p class="man-p">Não precisa ter sido na abertura — o card <strong>"Fotos do estado de entrada"</strong> também aparece na própria tela da OS já criada, com os mesmos dois jeitos de adicionar: <strong>"Adicionar foto"</strong> (escolher arquivo) ou <strong>"Tirar foto pelo celular"</strong> (QR Code, mesmo pareamento). Útil quando um dano é percebido depois da entrada, ou a foto tirada na abertura não ficou boa.</p>
    <div class="man-warn"><i class="bi bi-exclamation-triangle-fill"></i>Excluir uma foto do estado de entrada é uma ação só do <strong>administrador</strong> — o botão de remover (✕) não aparece para os demais perfis, nem na tela da OS nem no formulário de edição.</div>
  </div>

  <!-- Status -->
  <div class="man-section" id="os-status">
    <h2 class="man-h2"><i class="bi bi-arrow-repeat"></i> Status e workflow de OS</h2>
    <p class="man-p">O FixaOS usa um sistema de status personalizável. Você define os status que fazem sentido para seu negócio em <strong>Configurações → Status de OS</strong>.</p>
    <div class="man-warn"><i class="bi bi-exclamation-triangle-fill"></i>O status <strong>Garantia</strong> (nativo do sistema) e qualquer status chamado <strong>Descartado</strong> ficam protegidos: não podem ser renomeados nem excluídos em Configurações → Status de OS. Uma OS marcada como Descartada também deixa de exibir selo e botão de garantia, já que o equipamento não foi reparado.</div>

    <h3 class="man-h3">Status padrão criados no cadastro</h3>
    <table class="man-table">
      <thead><tr><th>Status</th><th>Tipo</th><th>Significado</th></tr></thead>
      <tbody>
        <?php foreach([
          ['Aguardando Diagnóstico','Aberta','OS recém-chegada, aguardando avaliação.'],
          ['Em Diagnóstico','Em andamento','Técnico avaliando o equipamento.'],
          ['Aguardando Aprovação','Aguardando','Orçamento enviado, aguardando resposta do cliente.'],
          ['Aguardando Peças','Aguardando','Peça encomendada, aguardando chegada.'],
          ['Em Reparo','Em andamento','Reparo em execução.'],
          ['Pronto para Retirada','Concluída','Equipamento reparado e pronto.'],
          ['Entregue','Entregue','Cliente retirou o equipamento.'],
          ['Cancelado','Cancelada','OS encerrada sem reparo.'],
        ] as[$s,$t,$d]):?>
        <tr>
          <td><strong><?=$s?></strong></td>
          <td><span class="man-badge" style="background:rgba(59,130,246,.15);color:#60a5fa"><?=$t?></span></td>
          <td><?=$d?></td>
        </tr>
        <?php endforeach;?>
      </tbody>
    </table>
    <div class="man-warn"><i class="bi bi-exclamation-triangle-fill"></i>O botão <strong>"Fechar OS"</strong> aparece apenas nos status do tipo <em>Concluída</em> e <em>Sem Conserto</em>. Verifique o tipo ao criar novos status.</div>
  </div>

  <!-- Serviços e peças -->
  <div class="man-section" id="os-servicos">
    <h2 class="man-h2"><i class="bi bi-tools"></i> Adicionar serviços e peças</h2>
    <p class="man-p">Dentro de uma OS aberta, você pode registrar serviços realizados e peças utilizadas. O valor total da OS é calculado automaticamente.</p>
    <div class="man-step"><div class="man-step-n">1</div><div class="man-step-t">Na tela da OS, clique em <strong>"Adicionar Serviço"</strong>. Informe a descrição, quantidade e valor.</div></div>
    <div class="man-step"><div class="man-step-n">2</div><div class="man-step-t">Para peças, clique em <strong>"Adicionar Peça"</strong>. Se a peça está no estoque, o sistema deduz automaticamente.</div></div>
    <div class="man-step"><div class="man-step-n">3</div><div class="man-step-t">O valor total da OS é atualizado em tempo real conforme você adiciona itens.</div></div>
    <div class="man-tip"><i class="bi bi-lightbulb-fill"></i>Você pode adicionar quantos serviços e peças quiser. Itens podem ser removidos enquanto a OS não estiver fechada.</div>
    <div class="man-tip"><i class="bi bi-stars"></i>Ao digitar a <strong>Descrição</strong> do serviço, o campo sugere itens do seu <a href="#estoque-servicos">catálogo de Serviços cadastrados</a> — selecionar um já preenche o valor padrão. Continua aceitando texto livre para um serviço avulso, fora do catálogo.</div>
  </div>

  <!-- Chat interno da equipe -->
  <div class="man-section" id="os-chat">
    <h2 class="man-h2"><i class="bi bi-chat-dots-fill"></i> Chat interno da equipe (dentro da OS)</h2>
    <p class="man-p">Toda OS tem um chat interno — a <strong>"Conversa da equipe"</strong> — pra técnico, gerente, recepcionista e admin combinarem detalhes daquele atendimento sem sair da tela da OS.</p>
    <div class="man-step"><div class="man-step-n">1</div><div class="man-step-t">Abra a OS e localize o card <strong>"Conversa da equipe"</strong>.</div></div>
    <div class="man-step"><div class="man-step-n">2</div><div class="man-step-t">Digite a mensagem e envie. Qualquer usuário da empresa com acesso àquela OS pode ler e responder.</div></div>
    <div class="man-step"><div class="man-step-n">3</div><div class="man-step-t">Cada mensagem mostra quem escreveu e quando. Você pode editar ou apagar suas próprias mensagens; o administrador pode apagar qualquer mensagem.</div></div>
    <div class="man-tip"><i class="bi bi-lightbulb-fill"></i>O chat atualiza sozinho a cada poucos segundos, sem precisar recarregar a página, e mostra confirmação de leitura.</div>
    <div class="man-warn"><i class="bi bi-toggle-off"></i>O administrador pode desativar esse chat para toda a empresa em <strong>Configurações</strong>, se preferir não usar.</div>
  </div>

  <!-- Impressão -->
  <div class="man-section" id="os-imprimir">
    <h2 class="man-h2"><i class="bi bi-printer-fill"></i> Impressão e PDF</h2>
    <p class="man-p">O FixaOS oferece 5 modelos de documento para impressão ou envio por WhatsApp:</p>
    <table class="man-table">
      <thead><tr><th>Documento</th><th>Quando usar</th></tr></thead>
      <tbody>
        <?php foreach([
          ['Ordem de Serviço (OS)','Na entrada do equipamento — entregue ao cliente como recibo.'],
          ['Orçamento','Após o diagnóstico — lista serviços e valores para aprovação.'],
          ['Laudo Técnico','Diagnóstico detalhado com formatação (negrito, itálico, sublinhado, listas e cor), serviços/peças e valores. Só aparece no menu Imprimir quando o campo Laudo Técnico da OS está preenchido.'],
          ['Fechamento','Na entrega — comprovante de conclusão com valor pago.'],
          ['Garantia','Entregue junto com o equipamento ao finalizar.'],
        ] as[$d,$q]):?>
        <tr><td><strong><?=$d?></strong></td><td><?=$q?></td></tr>
        <?php endforeach;?>
      </tbody>
    </table>
    <p class="man-p">Para imprimir, abra a OS e clique no botão <strong>Imprimir</strong>. Escolha o documento desejado. Na tela de impressão, há também um botão para <strong>enviar por WhatsApp</strong> diretamente.</p>
    <div class="man-tip"><i class="bi bi-lightbulb-fill"></i>O campo <strong>Laudo Técnico</strong> fica na tela da OS, logo após "Peças Utilizadas", com um editor completo: negrito, itálico, sublinhado, lista com marcadores, lista numerada e cor do texto. Formate como quiser e salve — assim que estiver preenchido, a opção "Laudo técnico" aparece automaticamente no menu Imprimir.</div>
  </div>

  <!-- Fechar OS -->
  <div class="man-section" id="os-fechar">
    <h2 class="man-h2"><i class="bi bi-check-circle-fill"></i> Fechar uma OS</h2>
    <p class="man-p">O fechamento registra a entrega do equipamento e gera automaticamente um lançamento no financeiro.</p>
    <div class="man-step"><div class="man-step-n">1</div><div class="man-step-t">Mude o status da OS para <strong>"Pronto para Retirada"</strong> (ou equivalente do tipo <em>Concluída</em>).</div></div>
    <div class="man-step"><div class="man-step-n">2</div><div class="man-step-t">O botão <strong>"Fechar OS"</strong> ficará disponível. Clique nele.</div></div>
    <div class="man-step"><div class="man-step-n">3</div><div class="man-step-t">Confirme o valor e a forma de pagamento — dinheiro, Pix, cartão, transferência ou boleto. Se o cliente pagar parte em cada forma (ex.: metade no Pix, metade no cartão), clique em <strong>"Adicionar forma de pagamento"</strong> e monte o pagamento dividido.</div></div>
    <div class="man-step"><div class="man-step-n">4</div><div class="man-step-t">O sistema gera o lançamento financeiro (pago ou pendente, conforme o valor coberto) e registra a data de conclusão.</div></div>
    <div class="man-tip"><i class="bi bi-lightbulb-fill"></i>Após fechar, imprima o comprovante de fechamento e a garantia para entregar ao cliente.</div>
    <div class="man-tip"><i class="bi bi-info-circle-fill"></i>Em pagamento com cartão ou Pix pela maquininha, informe a taxa — o sistema calcula e lança automaticamente a despesa da taxa no Financeiro, já como paga (a maquininha captura na hora, mesmo que o restante do pagamento dividido ainda esteja pendente).</div>
  </div>

  <!-- Adiantamento de OS -->
  <div class="man-section" id="os-adiantamento">
    <h2 class="man-h2"><i class="bi bi-piggy-bank-fill"></i> Adiantamento (pagamento antecipado) <span class="man-badge" style="background:#dcfce7;color:#166534;margin-left:.4rem">⭐ Novo</span></h2>
    <p class="man-p">Peça cara, e você pede um sinal ao cliente antes de comprar ou consertar? O card <strong>"Adiantamento de Peças/Pagamento Adiantado"</strong>, na tela da OS, registra esse valor com as mesmas regras financeiras do fechamento — forma de pagamento, taxa de cartão (ou Pix na maquininha) e quem paga a taxa.</p>
    <div class="man-step"><div class="man-step-n">1</div><div class="man-step-t">Na tela da OS (ainda não fechada), clique em <strong>"+ Adicionar"</strong> no card de Adiantamento.</div></div>
    <div class="man-step"><div class="man-step-n">2</div><div class="man-step-t">Informe o valor recebido e a forma de pagamento. Se for cartão ou Pix pela maquininha, a taxa configurada aparece sozinha.</div></div>
    <div class="man-step"><div class="man-step-n">3</div><div class="man-step-t">Salve — o valor já entra <strong>na hora</strong> no Financeiro (é dinheiro que já entrou de verdade), sem esperar o fechamento da OS.</div></div>
    <div class="man-tip"><i class="bi bi-lightbulb-fill"></i>No momento de fechar a OS, um aviso mostra quanto já foi adiantado e quanto falta receber — você só precisa cobrar a diferença.</div>
    <div class="man-warn"><i class="bi bi-exclamation-triangle-fill"></i>Excluir um adiantamento estorna de verdade: apaga o lançamento (e a taxa, se houver) do Financeiro e desconta do valor pago da OS. Só é possível enquanto a OS ainda está aberta.</div>
  </div>

  <!-- Garantia -->
  <div class="man-section" id="os-garantia">
    <h2 class="man-h2"><i class="bi bi-shield-fill-check"></i> Garantia e retorno</h2>
    <p class="man-p">Quando um equipamento retorna dentro do prazo de garantia, o sistema permite abrir uma OS de garantia vinculada à original.</p>
    <div class="man-step"><div class="man-step-n">1</div><div class="man-step-t">Abra a OS original (a que foi fechada com garantia).</div></div>
    <div class="man-step"><div class="man-step-n">2</div><div class="man-step-t">Clique em <strong>"Abrir OS de Garantia"</strong>. O botão aparece se o prazo ainda está vigente.</div></div>
    <div class="man-step"><div class="man-step-n">3</div><div class="man-step-t">Uma nova OS é criada com vínculo à original, tipo <em>garantia</em> e o histórico do defeito anterior.</div></div>
    <div class="man-warn"><i class="bi bi-exclamation-triangle-fill"></i>OS de garantia aparecem em um filtro separado na lista de OS para fácil identificação.</div>
    <div class="man-warn"><i class="bi bi-exclamation-triangle-fill"></i>No passo de revisão do equipamento (Entrada de Garantia), selecionar pelo menos um acessório — ou marcar <strong>"Sem acessórios"</strong> — agora é obrigatório antes de criar a OS. Evita registrar o retorno sem revisar de verdade o que o cliente trouxe junto.</div>
  </div>

  <!-- Reabrir OS -->
  <div class="man-section" id="os-reabrir">
    <h2 class="man-h2"><i class="bi bi-arrow-counterclockwise"></i> Reabrir OS</h2>
    <p class="man-p">Se uma OS foi fechada (Concluída ou Entregue) por engano, ou o cliente precisa de um ajuste antes de retirar o equipamento, você pode reabri-la. Ao reabrir, a OS volta automaticamente para o status em que estava antes do fechamento, e as datas de conclusão/entrega são limpas.</p>

    <h3 class="man-h3">Direto na OS</h3>
    <div class="man-step"><div class="man-step-n">1</div><div class="man-step-t">Abra a OS fechada que deseja reabrir.</div></div>
    <div class="man-step"><div class="man-step-n">2</div><div class="man-step-t">Clique no botão <strong>"Reabrir OS"</strong>, ao lado dos botões de garantia/edição. Ele só aparece quando a OS está Concluída ou Entregue.</div></div>
    <div class="man-step"><div class="man-step-n">3</div><div class="man-step-t">Confirme a reabertura. A OS volta ao status anterior ao fechamento.</div></div>

    <h3 class="man-h3">Pela lista de OS (buscar sem abrir a OS)</h3>
    <div class="man-step"><div class="man-step-n">1</div><div class="man-step-t">Na lista de <strong>Ordens de Serviço</strong>, clique no botão laranja <strong>"Reabrir OS"</strong>.</div></div>
    <div class="man-step"><div class="man-step-n">2</div><div class="man-step-t">Digite o número da OS, nome do cliente, marca ou modelo do equipamento na busca.</div></div>
    <div class="man-step"><div class="man-step-n">3</div><div class="man-step-t">Clique na OS encontrada na lista de resultados e confirme a reabertura.</div></div>
    <div class="man-tip"><i class="bi bi-lightbulb-fill"></i>Esse segundo caminho é útil quando você não lembra em qual OS o problema aconteceu — a busca cobre todas as OS Concluídas/Entregues da empresa.</div>
  </div>

  <!-- Modo offline -->
  <div class="man-section" id="os-offline">
    <h2 class="man-h2"><i class="bi bi-wifi-off"></i> Modo offline</h2>
    <p class="man-p">Se a internet cair no meio do atendimento, o FixaOS continua funcionando no básico — o sistema guarda os dados no próprio navegador e sincroniza sozinho quando a conexão voltar.</p>
    <h3 class="man-h3">O que funciona sem internet</h3>
    <ul style="color:#4b5563;font-size:.9rem;line-height:2;padding-left:1.2rem">
      <li><strong style="color:#1e293b">Consultar OS do dia:</strong> as ordens já carregadas ficam disponíveis para consulta.</li>
      <li><strong style="color:#1e293b">Criar rascunho de OS:</strong> abra uma nova OS normalmente — ela fica salva como rascunho no navegador.</li>
    </ul>
    <div class="man-step"><div class="man-step-n">1</div><div class="man-step-t">Um aviso aparece no topo da tela quando o sistema perde a conexão.</div></div>
    <div class="man-step"><div class="man-step-n">2</div><div class="man-step-t">Continue trabalhando normalmente — o que der pra fazer offline é salvo localmente.</div></div>
    <div class="man-step"><div class="man-step-n">3</div><div class="man-step-t">Quando a internet voltar, os rascunhos sincronizam sozinhos e você recebe um aviso confirmando cada OS sincronizada.</div></div>
    <div class="man-warn"><i class="bi bi-exclamation-triangle-fill"></i>Se algum rascunho não conseguir sincronizar (ex.: cliente foi excluído nesse meio tempo), ele fica marcado como pendente em "OS offline" — dessa vez o ajuste é manual.</div>
  </div>

  <!-- Clientes -->
  <div class="man-section" id="clientes">
    <h2 class="man-h2"><i class="bi bi-people-fill"></i> Cadastro de Clientes</h2>
    <p class="man-p">Acesse <strong>Clientes → Novo Cliente</strong> para cadastrar. Campos principais:</p>
    <ul style="color:#4b5563;font-size:.9rem;line-height:2;padding-left:1.2rem">
      <li><strong style="color:#1e293b">Tipo:</strong> Pessoa Física (CPF) ou Jurídica (CNPJ).</li>
      <li><strong style="color:#1e293b">Contato:</strong> Telefone, WhatsApp e e-mail.</li>
      <li><strong style="color:#1e293b">Endereço:</strong> CEP com preenchimento automático via API.</li>
      <li><strong style="color:#1e293b">Observações:</strong> Notas internas sobre o cliente.</li>
    </ul>
    <div class="man-tip"><i class="bi bi-lightbulb-fill"></i>Você pode cadastrar clientes diretamente durante a abertura de uma OS sem precisar sair da tela.</div>
  </div>

  <!-- CRM -->
  <div class="man-section" id="crm">
    <h2 class="man-h2"><i class="bi bi-funnel-fill"></i> Pipeline de Vendas (CRM)</h2>
    <p class="man-p">O módulo CRM permite acompanhar oportunidades de negócio em estágios: Primeiro Contato → Orçamento → Negociação → Ganho/Perdido.</p>
    <p class="man-p">Acesse pelo menu <strong>Clientes → CRM</strong>. Arraste os cards entre os estágios conforme o negócio avança.</p>
  </div>

  <!-- Estoque -->
  <div class="man-section" id="estoque-produtos">
    <h2 class="man-h2"><i class="bi bi-box-seam-fill"></i> Cadastro de Produtos</h2>
    <p class="man-p">Acesse <strong>Estoque → Produtos</strong>. Para cada produto, informe: nome, código, categoria, custo, preço de venda e quantidade mínima para alerta.</p>
    <div class="man-warn"><i class="bi bi-exclamation-triangle-fill"></i>Produtos com quantidade abaixo do mínimo aparecem destacados na lista de estoque.</div>
  </div>

  <div class="man-section" id="estoque-mov">
    <h2 class="man-h2"><i class="bi bi-arrow-left-right"></i> Movimentações de Estoque</h2>
    <p class="man-p">O estoque é movimentado automaticamente quando peças são adicionadas a uma OS. Para movimentações manuais (compras, perdas), acesse o produto e clique em <strong>Movimentar</strong>.</p>
  </div>

  <!-- Catálogo de Serviços -->
  <div class="man-section" id="estoque-servicos">
    <h2 class="man-h2"><i class="bi bi-tools"></i> Catálogo de Serviços cadastrados <span class="man-badge" style="background:#dcfce7;color:#166534;margin-left:.4rem">⭐ Novo</span></h2>
    <p class="man-p">Assim como Produtos, os serviços que você mais presta também podem ter um cadastro padronizado — sem depender de digitar a descrição igual (e certa) toda vez que fecha uma OS. Acesse pelo link <strong>Serviços</strong> na sidebar.</p>
    <div class="man-step"><div class="man-step-n">1</div><div class="man-step-t">No card <strong>"Novo serviço"</strong>, informe a <strong>Descrição</strong> (ex.: "Troca de tela") e, se quiser, um <strong>Valor padrão</strong> sugerido.</div></div>
    <div class="man-step"><div class="man-step-n">2</div><div class="man-step-t">Clique em <strong>"Cadastrar serviço"</strong>. Ele passa a aparecer na lista, com botões de editar e excluir.</div></div>
    <div class="man-tip"><i class="bi bi-lightbulb-fill"></i>Esse catálogo alimenta o autocomplete do campo Descrição no modal <strong>"Novo Serviço"</strong> da OS — ver <a href="#os-servicos">Serviços e peças</a>. Ele é só um atalho de digitação: nada impede lançar um serviço avulso, fora do catálogo, direto na OS.</div>
  </div>

  <!-- PDV -->
  <div class="man-section" id="pdv">
    <h2 class="man-h2"><i class="bi bi-cash-stack"></i> PDV — Frente de Caixa</h2>
    <p class="man-p">O PDV é uma tela de venda rápida para quando você vende uma peça ou acessório avulso, sem precisar abrir uma Ordem de Serviço completa. Acesse pelo botão roxo <strong>"PDV / Frente de Caixa"</strong> na sidebar.</p>
    <div class="man-step"><div class="man-step-n">1</div><div class="man-step-t">Adicione os itens da venda: busque um produto do estoque (o sistema já traz o preço e verifica o saldo) ou digite uma descrição livre para itens fora do estoque.</div></div>
    <div class="man-step"><div class="man-step-n">2</div><div class="man-step-t">Selecione o cliente (opcional) e informe um desconto, se houver.</div></div>
    <div class="man-step"><div class="man-step-n">3</div><div class="man-step-t">Escolha a forma de pagamento: dinheiro, Pix, cartão de crédito, cartão de débito ou outro. No pagamento em dinheiro, informe o valor recebido e o troco é calculado automaticamente.</div></div>
    <div class="man-step"><div class="man-step-n">4</div><div class="man-step-t"><strong>Pagamento dividido:</strong> clique em <strong>"Adicionar forma de pagamento"</strong> para usar mais de uma forma na mesma venda (ex.: parte em dinheiro, parte no cartão). Some quanto quiser em cada linha até cobrir o total.</div></div>
    <div class="man-step"><div class="man-step-n">5</div><div class="man-step-t">Clique em <strong>Finalizar Venda</strong>. O sistema baixa o estoque dos produtos vendidos e gera automaticamente um lançamento de receita já paga no Financeiro.</div></div>
    <div class="man-warn"><i class="bi bi-exclamation-triangle-fill"></i>A venda é bloqueada se algum produto não tiver saldo suficiente em estoque.</div>
    <div class="man-tip"><i class="bi bi-lightbulb-fill"></i>Depois de finalizar, um comprovante de venda é gerado automaticamente e pode ser impresso ou enviado ao cliente.</div>
    <div class="man-tip"><i class="bi bi-info-circle-fill"></i>Se alguma linha do pagamento for no cartão ou Pix pela maquininha, informe a taxa — a despesa da taxa é lançada sozinha no Financeiro.</div>
  </div>

  <!-- Financeiro -->
  <div class="man-section" id="fin-lancamentos">
    <h2 class="man-h2"><i class="bi bi-currency-dollar"></i> Lançamentos Financeiros</h2>
    <p class="man-p">Acesse <strong>Financeiro → Lançamentos</strong>. Tipos de lançamento:</p>
    <table class="man-table">
      <thead><tr><th>Tipo</th><th>Quando usar</th></tr></thead>
      <tbody>
        <?php foreach([
          ['Receita','Dinheiro que entrou: pagamento de OS, venda de peça etc.'],
          ['Despesa','Dinheiro que saiu: aluguel, fornecedor, conta de luz etc.'],
        ] as[$t,$d]):?>
        <tr><td><strong><?=$t?></strong></td><td><?=$d?></td></tr>
        <?php endforeach;?>
      </tbody>
    </table>
    <div class="man-tip"><i class="bi bi-lightbulb-fill"></i>OS fechadas geram lançamentos automaticamente. Você pode criar categorias personalizadas em <strong>Financeiro → Categorias</strong>.</div>
  </div>

  <div class="man-section" id="fin-fluxo">
    <h2 class="man-h2"><i class="bi bi-graph-up-arrow"></i> Fluxo de Caixa</h2>
    <p class="man-p">O fluxo de caixa mostra todas as entradas e saídas do período com saldo acumulado, incluindo um gráfico de barras (receita x despesa) sobreposto a uma linha de saldo acumulado. Filtre por data e categoria. Acesse em <strong>Financeiro → Fluxo de Caixa</strong>.</p>
    <div class="man-tip"><i class="bi bi-info-circle-fill"></i>Por padrão, o período exibido começa no 1º dia do mês corrente e vai até hoje. Ajuste as datas acima do gráfico para consultar outros períodos.</div>
  </div>

  <div class="man-section" id="fin-relatorios">
    <h2 class="man-h2"><i class="bi bi-bar-chart-fill"></i> Relatórios Financeiros</h2>
    <p class="man-p">Acesse <strong>Relatórios</strong> para gerar análises por período, status e técnico com gráficos interativos. Exporte para PDF ou imprima diretamente.</p>
  </div>

  <!-- Comissão de técnico -->
  <div class="man-section" id="fin-comissoes">
    <h2 class="man-h2"><i class="bi bi-cash-coin"></i> Comissão de Técnico</h2>
    <p class="man-p">Acesse <strong>Financeiro → Comissões</strong> (ou o atalho na barra de ações do Fluxo de Caixa) para lançar e controlar quanto cada técnico tem a receber.</p>
    <h3 class="man-h3">Lançar uma comissão</h3>
    <div class="man-step"><div class="man-step-n">1</div><div class="man-step-t">Clique em <strong>"Nova Comissão"</strong> e escolha o técnico.</div></div>
    <div class="man-step"><div class="man-step-n">2</div><div class="man-step-t">Opcionalmente, busque e vincule a uma OS específica.</div></div>
    <div class="man-step"><div class="man-step-n">3</div><div class="man-step-t">Informe o valor base à mão, ou clique em <strong>"Puxar da OS"</strong> para somar automaticamente os serviços que esse técnico realizou na OS vinculada.</div></div>
    <div class="man-step"><div class="man-step-n">4</div><div class="man-step-t">O percentual já vem preenchido — com o % próprio do técnico (se configurado em <a href="#cfg-tecnicos">Técnicos</a>) ou o percentual padrão da empresa. Ajuste se precisar.</div></div>
    <div class="man-step"><div class="man-step-n">5</div><div class="man-step-t">Salve. Quando for pagar, clique em <strong>"Marcar como paga"</strong> na lista — isso gera automaticamente uma despesa no Financeiro.</div></div>
    <div class="man-tip"><i class="bi bi-lightbulb-fill"></i>Filtre por técnico e período na lista de Comissões para conferir quanto cada um já recebeu e quanto ainda está pendente.</div>
  </div>

  <!-- Agenda: visões e navegação -->
  <div class="man-section" id="agenda">
    <h2 class="man-h2"><i class="bi bi-calendar-week-fill"></i> Agenda — Visões e navegação</h2>
    <p class="man-p">Acesse pela <strong>Agenda</strong> na sidebar. É o calendário de compromissos da empresa — coletas, entregas, visitas, cobranças, e qualquer evento que precise de data marcada — com 4 formas de visualizar, filtros, arrastar para reagendar, repetição automática e lembretes.</p>

    <h3 class="man-h3">As 4 visões</h3>
    <table class="man-table">
      <thead><tr><th>Visão</th><th>O que mostra</th></tr></thead>
      <tbody>
        <?php foreach([
          ['Mês','Grade mensal tradicional. Cada dia mostra até 2 eventos; havendo mais, um "+N mais" abre a lista completa daquele dia.'],
          ['Semana','7 colunas (uma por dia) com eixo de horas — os eventos aparecem posicionados e dimensionados pelo horário de início e duração. Uma linha vermelha marca a hora atual.'],
          ['Dia','Mesma ideia da Semana, só que uma coluna única — mais espaço pra ver título, cliente e horário no próprio bloco.'],
          ['Técnicos','Uma linha (swimlane) por técnico ativo, eixo de horas do dia selecionado. Ao lado do nome, uma barra mostra a ocupação dele (horas agendadas sobre a jornada de trabalho), ficando vermelha se passar de 100% — é a visão pra responder rápido "quem tem espaço hoje".'],
        ] as[$v,$d]):?>
        <tr><td><strong><?=$v?></strong></td><td><?=$d?></td></tr>
        <?php endforeach;?>
      </tbody>
    </table>
    <p class="man-p">Troque de visão pelos botões no topo da Agenda (ou pelas teclas <span class="kbd">1</span>–<span class="kbd">4</span>) — a data que você estava olhando não se perde ao trocar. Use as setas ao lado do título pra navegar (período anterior/seguinte) e o botão <strong>Hoje</strong> pra voltar à data atual.</p>

    <h3 class="man-h3">Filtros</h3>
    <p class="man-p">Logo abaixo do topo da Agenda:</p>
    <ul style="color:#4b5563;font-size:.9rem;line-height:2;padding-left:1.2rem">
      <li><strong style="color:#1e293b">Chips de tipo:</strong> Ordem de Serviço, Coleta, Entrega, Financeiro, Garantia, Pessoal e Outro — clique pra ligar/desligar cada um (todos ativos por padrão). Cada tipo tem uma cor própria, usada em todo evento dele.</li>
      <li><strong style="color:#1e293b">Técnico:</strong> dropdown com busca, filtra só os eventos daquele responsável.</li>
      <li><strong style="color:#1e293b">Status:</strong> Agendado, Confirmado, Em andamento, Concluído, Cancelado ou Atrasado.</li>
    </ul>
    <p class="man-p">Um botão <strong>"Limpar filtros"</strong> aparece assim que algum filtro estiver ativo. O estado dos filtros fica salvo no link da página — dá pra compartilhar ou favoritar uma visão já filtrada (ex.: "só as coletas do João") e ela volta do mesmo jeito.</p>

    <h3 class="man-h3">Atalhos de teclado</h3>
    <table class="man-table">
      <thead><tr><th>Tecla</th><th>Ação</th></tr></thead>
      <tbody>
        <tr><td><span class="kbd">N</span></td><td>Novo evento</td></tr>
        <tr><td><span class="kbd">T</span></td><td>Ir para hoje</td></tr>
        <tr><td><span class="kbd">1</span>–<span class="kbd">4</span></td><td>Trocar de visão (Mês / Semana / Dia / Técnicos)</td></tr>
        <tr><td><span class="kbd">←</span> <span class="kbd">→</span></td><td>Navegar (período anterior/seguinte)</td></tr>
        <tr><td><span class="kbd">/</span></td><td>Buscar evento</td></tr>
        <tr><td><span class="kbd">?</span></td><td>Abrir a lista de atalhos</td></tr>
      </tbody>
    </table>
    <div class="man-tip"><i class="bi bi-lightbulb-fill"></i>Os atalhos ficam desativados automaticamente com o foco num campo de texto — pode digitar sem se preocupar em disparar algum sem querer.</div>
    <div class="man-tip"><i class="bi bi-phone"></i><strong>No celular</strong>, a Agenda abre direto na visão Dia, com uma faixa de dias deslizável no topo — arraste o dedo pros lados pra trocar de dia, ou toque num dia da faixa. Toque no telefone do cliente pra ligar, ou no endereço pra abrir no mapa.</div>
  </div>

  <!-- Agenda: criar e editar eventos -->
  <div class="man-section" id="agenda-eventos">
    <h2 class="man-h2"><i class="bi bi-calendar-plus-fill"></i> Agenda — Criar e editar eventos</h2>

    <h3 class="man-h3">Criação rápida</h3>
    <p class="man-p">Clique numa área vazia do calendário (um dia, um horário, a linha de um técnico) — abre um popover compacto já com a data preenchida.</p>
    <div class="man-step"><div class="man-step-n">1</div><div class="man-step-t">Preencha <strong>Título</strong>, <strong>Hora</strong>, <strong>Tipo</strong> e <strong>Responsável</strong>.</div></div>
    <div class="man-step"><div class="man-step-n">2</div><div class="man-step-t">Clique em <strong>Salvar</strong> — o evento aparece na hora no calendário, sem recarregar a página. Se precisar vincular Cliente/OS, definir recorrência ou cor personalizada, clique em <strong>"Mais opções"</strong> pra abrir o formulário completo com o que já foi digitado.</div></div>

    <h3 class="man-h3">Formulário completo</h3>
    <p class="man-p">Abra pelo botão <strong>"+ Novo evento"</strong>, pela tecla <span class="kbd">N</span>, ou clicando num evento existente pra editar.</p>
    <div class="man-step"><div class="man-step-n">1</div><div class="man-step-t"><strong>Ordem de Serviço</strong> e <strong>Cliente</strong> são opcionais — busque e vincule. Ao selecionar uma OS, o sistema já preenche cliente, técnico responsável e sugere um título sozinho.</div></div>
    <div class="man-step"><div class="man-step-n">2</div><div class="man-step-t">Escolha o <strong>Tipo</strong> — cada um tem uma cor fixa (veja a tabela abaixo), usada em todas as visões e nos filtros.</div></div>
    <div class="man-step"><div class="man-step-n">3</div><div class="man-step-t">Defina <strong>Responsável</strong>, <strong>Início</strong> (obrigatório) e <strong>Fim</strong> (opcional). Se o técnico escolhido já tiver outro compromisso nesse horário, um aviso aparece — não bloqueia o salvamento, só avisa.</div></div>
    <div class="man-step"><div class="man-step-n">4</div><div class="man-step-t">Configure <strong>Repetir</strong>, <strong>Cor personalizada</strong> e <strong>Lembretes</strong> se precisar (ver seções abaixo).</div></div>
    <div class="man-step"><div class="man-step-n">5</div><div class="man-step-t">Clique em <strong>Salvar</strong> (ou <strong>Atualizar</strong>, se estiver editando).</div></div>

    <h3 class="man-h3">Cor por tipo de evento</h3>
    <table class="man-table">
      <thead><tr><th>Tipo</th><th>Cor</th></tr></thead>
      <tbody>
        <?php foreach([
          ['Ordem de Serviço','#4f46e5'],['Coleta','#f59e0b'],['Entrega','#16a34a'],
          ['Financeiro','#0d9488'],['Garantia','#7c3aed'],['Pessoal','#db2777'],['Outro','#0ea5e9'],
        ] as[$t,$c]):?>
        <tr><td><strong><?=$t?></strong></td><td><span style="display:inline-block;width:14px;height:14px;border-radius:50%;background:<?=$c?>;vertical-align:middle;margin-right:.4rem"></span><?=$c?></td></tr>
        <?php endforeach;?>
      </tbody>
    </table>
    <div class="man-tip"><i class="bi bi-palette-fill"></i>Quer destacar um evento específico com outra cor (sem mudar o Tipo dele)? Ligue <strong>"Cor personalizada"</strong> no formulário e escolha a cor — ela substitui a cor do Tipo só naquele evento. Deixando desligado, a cor volta a seguir o Tipo automaticamente.</div>
    <div class="man-warn"><i class="bi bi-exclamation-triangle-fill"></i>Evento com status <strong>Atrasado</strong> sempre aparece em vermelho, mesmo com Tipo ou cor personalizada definidos — é o único aviso que tem prioridade sobre qualquer escolha de cor.</div>

    <h3 class="man-h3">Excluir um evento</h3>
    <p class="man-p">Abra o evento e clique em <strong>Excluir</strong> (no rodapé do formulário). Eventos avulsos pedem só uma confirmação; eventos de uma série pedem também o escopo — veja <a href="#agenda-recorrencia">Repetir compromissos</a>.</p>
  </div>

  <!-- Agenda: recorrência -->
  <div class="man-section" id="agenda-recorrencia">
    <h2 class="man-h2"><i class="bi bi-arrow-repeat"></i> Agenda — Repetir compromissos</h2>
    <p class="man-p">Pra compromissos que se repetem (aluguel, parcela, manutenção mensal), não é preciso criar um evento por vez — a Agenda gera as ocorrências automaticamente a partir de uma regra.</p>

    <h3 class="man-h3">Criar uma série</h3>
    <div class="man-step"><div class="man-step-n">1</div><div class="man-step-t">No formulário de novo evento, ligue <strong>Repetir</strong>.</div></div>
    <div class="man-step"><div class="man-step-n">2</div><div class="man-step-t">Escolha a <strong>Frequência</strong>: diariamente, semanalmente, mensalmente (no dia do mês, ex. "todo dia 10") ou mensalmente por posição (ex. "toda 2ª segunda-feira do mês" — útil pra reuniões recorrentes que não caem sempre no mesmo dia do mês), ou anualmente.</div></div>
    <div class="man-step"><div class="man-step-n">3</div><div class="man-step-t">Defina <strong>"A cada"</strong> (ex.: a cada 2 semanas) e o <strong>Término</strong>: nunca, após um número de ocorrências, ou até uma data.</div></div>
    <div class="man-step"><div class="man-step-n">4</div><div class="man-step-t">Salve — as ocorrências futuras já aparecem no calendário, marcadas com o ícone de repetição (<i class="bi bi-arrow-repeat"></i>), e o texto da regra (ex.: "Todo dia 10, mensalmente") aparece ao passar o mouse ou abrir o evento.</div></div>
    <div class="man-tip"><i class="bi bi-lightbulb-fill"></i>Um evento que já existe e ainda não é recorrente também pode virar série depois — abra ele, ligue Repetir, configure e salve.</div>

    <h3 class="man-h3">Editar ou excluir uma ocorrência</h3>
    <p class="man-p">Ao editar ou excluir um evento que faz parte de uma série, o sistema pergunta o alcance da mudança:</p>
    <table class="man-table">
      <thead><tr><th>Escopo</th><th>Efeito</th></tr></thead>
      <tbody>
        <tr><td><strong>Somente este evento</strong></td><td>Muda só essa data — vira uma exceção da série, o resto continua igual.</td></tr>
        <tr><td><strong>Este e os seguintes</strong></td><td>A série antiga termina na véspera; uma nova série começa dali com as mudanças, mantendo a mesma frequência.</td></tr>
        <tr><td><strong>Toda a série</strong></td><td>Aplica a mudança em todas as ocorrências, passadas e futuras, sem alterar a data/padrão de repetição.</td></tr>
      </tbody>
    </table>
    <div class="man-tip"><i class="bi bi-info-circle-fill"></i>Ao <strong>excluir</strong>, esses mesmos 3 escopos valem: só aquele dia, dali pra frente, ou a série inteira.</div>
  </div>

  <!-- Agenda: arrastar/redimensionar/teclado -->
  <div class="man-section" id="agenda-mover">
    <h2 class="man-h2"><i class="bi bi-arrows-move"></i> Agenda — Arrastar, redimensionar e mover por teclado</h2>
    <p class="man-p">Reagendar não precisa abrir o formulário — arraste o evento direto no calendário, em qualquer uma das 4 visões.</p>
    <ul style="color:#4b5563;font-size:.9rem;line-height:2;padding-left:1.2rem">
      <li><strong style="color:#1e293b">Mês:</strong> arraste o evento pra outro dia (o horário continua o mesmo).</li>
      <li><strong style="color:#1e293b">Semana / Dia:</strong> arraste pra outro dia/horário; puxe a borda inferior do bloco pra mudar a duração.</li>
      <li><strong style="color:#1e293b">Técnicos:</strong> arraste na horizontal pra mudar o horário, ou entre linhas pra trocar o técnico responsável; puxe a borda direita pra mudar a duração.</li>
    </ul>
    <p class="man-p">Depois de soltar, um aviso de sucesso aparece com o botão <strong>"Desfazer"</strong>, válido por alguns segundos — clique se arrastou errado.</p>
    <div class="man-warn"><i class="bi bi-exclamation-triangle-fill"></i>Arrastar um evento de uma série recorrente também pergunta o escopo (somente este / este e os seguintes / toda a série) antes de aplicar.</div>

    <h3 class="man-h3">Sem mouse: mover por teclado</h3>
    <div class="man-step"><div class="man-step-n">1</div><div class="man-step-t">Dê foco no evento (Tab) e pressione <span class="kbd">M</span> pra entrar no modo de movimentação.</div></div>
    <div class="man-step"><div class="man-step-n">2</div><div class="man-step-t">Use as <strong>setas do teclado</strong> pra mover — na visão Técnicos, ↑/↓ trocam de técnico.</div></div>
    <div class="man-step"><div class="man-step-n">3</div><div class="man-step-t">Pressione <span class="kbd">Enter</span> pra confirmar, ou <span class="kbd">Esc</span> pra cancelar.</div></div>
  </div>

  <!-- Agenda: lembretes -->
  <div class="man-section" id="agenda-lembretes">
    <h2 class="man-h2"><i class="bi bi-bell-fill"></i> Agenda — Lembretes <span class="man-badge" style="background:#dcfce7;color:#166534;margin-left:.4rem">⭐ Destaque</span></h2>
    <p class="man-p">Cada evento pode disparar dois tipos de lembrete: um <strong>interno</strong>, pro técnico responsável (notificação dentro do sistema), e um <strong>opcional pro cliente</strong>, por WhatsApp ou e-mail.</p>

    <h3 class="man-h3">Lembrete interno (técnico)</h3>
    <p class="man-p">No formulário do evento, marque quando avisar — pode marcar mais de um: <strong>na hora</strong>, <strong>15 minutos antes</strong>, <strong>1 hora antes</strong> e/ou <strong>1 dia antes</strong>. O técnico responsável recebe uma notificação no sininho do sistema em cada horário marcado.</p>

    <h3 class="man-h3"><i class="bi bi-volume-up-fill"></i> Alerta sonoro no vencimento</h3>
    <p class="man-p">Além dos lembretes acima, cada evento tem um toggle próprio — <strong>"🔊 Alerta sonoro no vencimento"</strong> — que toca um beep no exato instante em que o compromisso começa, mesmo que nenhum lembrete "na hora" esteja marcado. O evento com o alerta ligado mostra um ícone de alto-falante no calendário.</p>
    <p class="man-p">O beep chega junto com a notificação do sininho, então funciona em <strong>qualquer tela do sistema</strong> — não precisa estar com a Agenda aberta, só logado no FixaOS em alguma aba do navegador.</p>
    <div class="man-tip"><i class="bi bi-info-circle-fill"></i>Navegadores só permitem som automático depois de alguma interação na página (um clique, por exemplo) — se a aba ficar parada sem nenhuma interação, o primeiro beep pode não tocar; os próximos tocam normalmente.</div>

    <h3 class="man-h3">Lembrete pro cliente</h3>
    <div class="man-step"><div class="man-step-n">1</div><div class="man-step-t">Ligue <strong>"Lembrete para o cliente"</strong> no formulário do evento.</div></div>
    <div class="man-step"><div class="man-step-n">2</div><div class="man-step-t">Escolha o <strong>canal</strong> (WhatsApp ou e-mail) e <strong>quando enviar</strong> (na hora, 15 min, 1h ou 1 dia antes).</div></div>
    <div class="man-step"><div class="man-step-n">3</div><div class="man-step-t">Edite a <strong>mensagem</strong> — use as variáveis <code style="background:#f6f3ee;padding:.1rem .35rem;border-radius:4px">{{cliente}}</code>, <code style="background:#f6f3ee;padding:.1rem .35rem;border-radius:4px">{{data}}</code>, <code style="background:#f6f3ee;padding:.1rem .35rem;border-radius:4px">{{hora}}</code>, <code style="background:#f6f3ee;padding:.1rem .35rem;border-radius:4px">{{os}}</code> e <code style="background:#f6f3ee;padding:.1rem .35rem;border-radius:4px">{{endereco}}</code>, que são preenchidas automaticamente antes do envio.</div></div>
    <div class="man-tip"><i class="bi bi-whatsapp" style="color:#25d366"></i>O envio por WhatsApp usa o número da própria empresa (o mesmo conectado em Configurações → WhatsApp da Empresa) — sem ele conectado, o lembrete não sai.</div>
    <div class="man-warn"><i class="bi bi-exclamation-triangle-fill"></i>Evento cancelado nunca dispara lembrete — mesmo que já estivesse agendado antes do cancelamento.</div>
    <div class="man-tip"><i class="bi bi-info-circle-fill"></i>Em compromissos de uma série recorrente, o lembrete se repete em cada ocorrência futura automaticamente — não precisa configurar de novo a cada uma.</div>
  </div>

  <!-- Agenda: indicadores e proximos 7 dias -->
  <div class="man-section" id="agenda-indicadores">
    <h2 class="man-h2"><i class="bi bi-graph-up"></i> Agenda — Painel Hoje, indicadores e Próximos 7 dias</h2>

    <h3 class="man-h3"><i class="bi bi-speedometer2"></i> Painel Hoje</h3>
    <p class="man-p">O primeiro bloco da tela, acima da barra de navegação do calendário — um resumo operacional do dia, pensado pra você ver de cara, sem precisar navegar por nenhuma visão específica:</p>
    <ul style="color:#4b5563;font-size:.9rem;line-height:2;padding-left:1.2rem">
      <li><strong style="color:#1e293b">Aguardando atendimento:</strong> OS com status do tipo "Aberta" — equipamento recebido, ainda não iniciado.</li>
      <li><strong style="color:#1e293b">Entregas previstas:</strong> eventos do tipo Entrega agendados para hoje.</li>
      <li><strong style="color:#1e293b">Orçamentos aguardando aprovação:</strong> OS com status do tipo "Aguardando".</li>
      <li><strong style="color:#1e293b">Serviços atrasados:</strong> OS com previsão de entrega vencida e ainda não concluída/entregue/cancelada (fica em vermelho quando maior que zero).</li>
      <li><strong style="color:#1e293b">Livres hoje:</strong> soma das horas livres de todos os técnicos ativos, considerando a jornada configurada pela empresa.</li>
    </ul>
    <p class="man-p">Abaixo dos números, uma barra de ocupação por técnico ativo — mesma lógica da visão Técnicos (horas agendadas hoje sobre a jornada), ficando vermelha se passar de 100%.</p>
    <div class="man-tip"><i class="bi bi-info-circle-fill"></i>O painel é sempre sobre <strong>hoje</strong>, independente de qual dia/mês/visão você está navegando no calendário abaixo dele.</div>

    <h3 class="man-h3">Indicadores</h3>
    <p class="man-p">Logo abaixo da barra de filtros, 4 cartões resumem o mês atual e respeitam os filtros ativos — clique em qualquer um pra aplicar o filtro correspondente:</p>
    <ul style="color:#4b5563;font-size:.9rem;line-height:2;padding-left:1.2rem">
      <li><strong style="color:#1e293b">Eventos no mês:</strong> total de compromissos.</li>
      <li><strong style="color:#1e293b">OS agendadas:</strong> quantos eventos são do tipo Ordem de Serviço.</li>
      <li><strong style="color:#1e293b">Em atraso:</strong> eventos com status Atrasado (fica em vermelho quando maior que zero).</li>
      <li><strong style="color:#1e293b">A receber no mês:</strong> valor pendente das OS vinculadas aos eventos do mês.</li>
    </ul>

    <h3 class="man-h3">Próximos 7 dias</h3>
    <p class="man-p">Abaixo do calendário, uma lista agrupada por dia ("Hoje", "Amanhã", depois pelo nome do dia) com tudo que vem pela frente — <strong>sem os filtros da grade</strong>, é sempre o panorama completo. Em cada item, um menu de ações rápidas permite:</p>
    <ul style="color:#4b5563;font-size:.9rem;line-height:2;padding-left:1.2rem">
      <li><strong style="color:#1e293b">Concluir</strong> ou <strong>Cancelar</strong> o compromisso sem abrir o formulário.</li>
      <li><strong style="color:#1e293b">Reagendar</strong> — abre o formulário já naquele evento.</li>
      <li><strong style="color:#1e293b">Abrir OS</strong>, se o evento estiver vinculado a uma.</li>
    </ul>
    <div class="man-tip"><i class="bi bi-lightbulb-fill"></i>Toda ação rápida também mostra o aviso com "Desfazer", igual ao arrastar — dá pra reverter um "Concluir"/"Cancelar" feito sem querer.</div>
  </div>

  <!-- Agenda: gera lançamento no financeiro -->
  <div class="man-section" id="agenda-financeiro">
    <h2 class="man-h2"><i class="bi bi-cash-coin"></i> Agenda gera lançamento no Financeiro <span class="man-badge" style="background:#dcfce7;color:#166534;margin-left:.4rem">⭐ Novo</span></h2>
    <p class="man-p">Compromissos financeiros recorrentes (aluguel, parcela de equipamento, mensalidade) podem virar um lançamento de verdade no Fluxo de Caixa sem precisar abrir o módulo Financeiro à parte — sem duplicar cadastro.</p>
    <div class="man-step"><div class="man-step-n">1</div><div class="man-step-t">No formulário do evento, escolha o tipo <strong>Financeiro</strong>. Aparece um bloco extra com <strong>Tipo</strong> (receita/despesa), <strong>Valor</strong>, <strong>Categoria</strong> e <strong>Conta</strong>.</div></div>
    <div class="man-step"><div class="man-step-n">2</div><div class="man-step-t">Preencha esse "molde" e salve — vale pra série inteira, se o evento for recorrente.</div></div>
    <div class="man-step"><div class="man-step-n">3</div><div class="man-step-t">Quando o compromisso vencer, use a ação rápida <strong>"Marcar como pago/recebido"</strong> em <a href="#agenda-indicadores">Próximos 7 dias</a> — isso cria o lançamento no Financeiro (já pago, com data de hoje) e marca aquele compromisso como concluído.</div></div>
    <div class="man-warn"><i class="bi bi-exclamation-triangle-fill"></i>O sistema nunca lança sozinho quando a data chega — sempre precisa do clique em "Marcar como pago". O valor pode variar (aluguel reajustado) ou o pagamento atrasar, então a confirmação é sempre manual.</div>
    <div class="man-tip"><i class="bi bi-info-circle-fill"></i>Numa série recorrente, marcar uma ocorrência como paga afeta só aquele dia — as outras continuam pendentes, cada uma com seu próprio lançamento quando for paga.</div>
  </div>

  <!-- Agenda: envia dados ao técnico -->
  <div class="man-section" id="agenda-tecnico">
    <h2 class="man-h2"><i class="bi bi-whatsapp" style="color:#25d366"></i> Agenda envia dados do atendimento ao técnico <span class="man-badge" style="background:#dcfce7;color:#166534;margin-left:.4rem">⭐ Novo</span></h2>
    <p class="man-p">Pra um evento de agenda com <strong>Técnico</strong> e <strong>Ordem de Serviço</strong> vinculados (visita, coleta ou entrega), o FixaOS manda pro WhatsApp de quem vai atender os dados de quem ele vai visitar — sem precisar abrir o sistema no celular.</p>
    <div class="man-step"><div class="man-step-n">1</div><div class="man-step-t">Em <a href="#agenda-indicadores">Próximos 7 dias</a>, abra o menu de ações rápidas do evento (só aparece quando tem OS e técnico vinculados) e clique em <strong>"Enviar dados ao técnico"</strong>.</div></div>
    <div class="man-step"><div class="man-step-n">2</div><div class="man-step-t">O técnico recebe, no WhatsApp cadastrado dele, uma mensagem com cliente, telefone, endereço completo, aparelho e defeito relatado — seguida do <strong>PDF da OS</strong>.</div></div>
    <div class="man-tip"><i class="bi bi-lightbulb-fill"></i>Usa o mesmo campo de telefone que já aparece como WhatsApp do técnico em <a href="#cfg-tecnicos">Técnicos</a>, e exige o WhatsApp da empresa conectado — igual qualquer outro envio ao cliente.</div>

    <h3 class="man-h3">Atendimento rápido</h3>
    <p class="man-p">Pra não precisar abrir o formulário completo (~15 campos) só pra agendar uma visita de uma OS que já existe, use o botão <strong>"Atendimento rápido"</strong> na toolbar da Agenda.</p>
    <div class="man-step"><div class="man-step-n">1</div><div class="man-step-t">Busque a OS pelo número, cliente ou aparelho — o cliente e um resumo aparecem sozinhos, e o técnico responsável da OS já vem sugerido.</div></div>
    <div class="man-step"><div class="man-step-n">2</div><div class="man-step-t">Escolha o <strong>Tipo</strong> (Ordem de Serviço, Coleta ou Entrega), confirme <strong>Técnico</strong> e <strong>Data/hora</strong>, e salve.</div></div>
    <div class="man-tip"><i class="bi bi-lightbulb-fill"></i>Um único clique cria o evento <strong>e</strong> já dispara "Enviar dados ao técnico" em seguida — se o envio falhar (técnico sem telefone, WhatsApp desconectado), o evento continua criado; só o aviso não sai, e o sistema avisa isso na hora.</div>
  </div>

  <!-- Marketplace -->
  <div class="man-section" id="mkt-anuncios">
    <h2 class="man-h2"><i class="bi bi-shop"></i> Criar Anúncio no Marketplace</h2>
    <div class="man-step"><div class="man-step-n">1</div><div class="man-step-t">Acesse <strong>Marketplace → Meus Anúncios → Novo Anúncio</strong>.</div></div>
    <div class="man-step"><div class="man-step-n">2</div><div class="man-step-t">Preencha: título, tipo de equipamento, marca, modelo, código, preço e descrição.</div></div>
    <div class="man-step"><div class="man-step-n">3</div><div class="man-step-t">Faça upload de até 5 fotos da peça. A primeira será a imagem principal.</div></div>
    <div class="man-step"><div class="man-step-n">4</div><div class="man-step-t">Clique em <strong>Publicar</strong>. O anúncio fica visível publicamente em <code style="background:#1f2937;padding:.1rem .4rem;border-radius:4px;color:#fb923c">/pecas</code>.</div></div>
  </div>

  <div class="man-section" id="mkt-creditos">
    <h2 class="man-h2"><i class="bi bi-coin"></i> Créditos do Marketplace</h2>
    <p class="man-p">Cada empresa recebe <strong>10 créditos gratuitos</strong> ao se cadastrar. Créditos são consumidos ao publicar anúncios. O saldo aparece no painel do Marketplace.</p>
  </div>

  <!-- Vitrine / Marketplace Publico -->
  <div class="man-section" id="mkt-vitrine">
    <h2 class="man-h2"><i class="bi bi-shop-window"></i> Vitrine e Marketplace Público</h2>
    <p class="man-p">A Vitrine reúne os anúncios de peças de <strong>todas as assistências técnicas</strong> que usam o FixaOS, permitindo comprar peças com outras empresas da rede. Acesse em <strong>Marketplace → Vitrine</strong>.</p>
    <p class="man-p">O <strong>Marketplace Público</strong> (link em "Divulgação" na sidebar) é essa mesma vitrine, só que aberta ao público em geral — qualquer pessoa pode navegar e ver os anúncios em <code style="background:#1f2937;padding:.1rem .4rem;border-radius:4px;color:#fb923c">/pecas</code>, mesmo sem estar logada, inclusive pelo Google. É uma forma de divulgar as peças à venda da sua empresa além da rede FixaOS.</p>
    <p class="man-p">Ao encontrar uma peça de interesse, o contato com o vendedor é feito diretamente pelo WhatsApp ou telefone informado no anúncio.</p>
  </div>

  <!-- Pedidos de Pecas -->
  <div class="man-section" id="mkt-pedidos">
    <h2 class="man-h2"><i class="bi bi-megaphone-fill"></i> Pedidos de Peças</h2>
    <p class="man-p">Não achou a peça que precisa? Publique um <strong>pedido</strong> descrevendo o que procura (tipo, marca, modelo) e a comunidade de assistências poderá responder com ofertas.</p>
    <div class="man-step"><div class="man-step-n">1</div><div class="man-step-t">Acesse <strong>Marketplace → Pedidos de Peças → Novo Pedido</strong> e descreva o que precisa, com nível de urgência e um WhatsApp de contato.</div></div>
    <div class="man-step"><div class="man-step-n">2</div><div class="man-step-t">Outras empresas podem responder seu pedido com uma mensagem de oferta.</div></div>
    <div class="man-step"><div class="man-step-n">3</div><div class="man-step-t">Ao resolver, marque o pedido como <strong>Atendido</strong> (ou <strong>Cancelar</strong>, se desistir).</div></div>
    <div class="man-tip"><i class="bi bi-lightbulb-fill"></i>Pedidos também aparecem na página pública, ampliando as chances de alguém fora da rede te ajudar.</div>
  </div>

  <!-- Forum Tecnico -->
  <div class="man-section" id="forum-usar">
    <h2 class="man-h2"><i class="bi bi-chat-dots-fill"></i> Fórum Técnico</h2>
    <p class="man-p">O Fórum é um espaço colaborativo entre técnicos de eletrônica para compartilhar defeitos, soluções e dicas de reparo — organizado por categorias de equipamento.</p>
    <h3 class="man-h3">Criar um tópico</h3>
    <div class="man-step"><div class="man-step-n">1</div><div class="man-step-t">Acesse <strong>Fórum Técnico → Novo Tópico</strong> e escolha a categoria do equipamento.</div></div>
    <div class="man-step"><div class="man-step-n">2</div><div class="man-step-t">Informe título, marca, modelo e versão do firmware (se aplicável).</div></div>
    <div class="man-step"><div class="man-step-n">3</div><div class="man-step-t">Descreva o defeito e a solução no campo de conteúdo. Você pode anexar arquivos (esquemas, firmwares) e um link externo.</div></div>
    <h3 class="man-h3">Interagir</h3>
    <p class="man-p">Nos tópicos, você pode curtir uma resposta útil e o autor do tópico pode marcar a <strong>melhor resposta</strong>. O Fórum é público e indexado pelo Google — suas contribuições ajudam outros técnicos a encontrar a solução.</p>
    <div class="man-tip"><i class="bi bi-lightbulb-fill"></i>Use a busca do Fórum para checar se o defeito que você está enfrentando já foi resolvido por outro técnico antes de abrir um novo tópico.</div>
  </div>

  <!-- Editor de Imagens -->
  <div class="man-section" id="editor-imagens">
    <h2 class="man-h2"><i class="bi bi-image"></i> Editor de Imagens</h2>
    <p class="man-p">Ferramenta rápida para ajustar fotos antes de usar no sistema (fotos de produtos, anúncios do Marketplace, logo da empresa etc.), sem precisar de outro programa.</p>
    <ul style="color:#4b5563;font-size:.9rem;line-height:2;padding-left:1.2rem">
      <li><strong style="color:#1e293b">Cortar (Crop):</strong> recorte livre ou com proporções predefinidas.</li>
      <li><strong style="color:#1e293b">Redimensionar:</strong> tamanhos predefinidos ou dimensões personalizadas.</li>
      <li><strong style="color:#1e293b">Qualidade/compressão:</strong> ajuste o tamanho do arquivo final.</li>
      <li><strong style="color:#1e293b">Desfazer:</strong> volte a uma edição anterior a qualquer momento.</li>
    </ul>
    <p class="man-p">Acesse em <strong>Configurações → Editor de Imagens</strong>. Envie a imagem (upload ou arraste e solte), edite, e clique em <strong>Baixar</strong> para salvar no computador ou <strong>Salvar no sistema</strong> para guardar direto no FixaOS.</p>
  </div>

  <!-- Config -->
  <div class="man-section" id="cfg-usuarios">
    <h2 class="man-h2"><i class="bi bi-person-gear"></i> Gerenciar Usuários</h2>
    <p class="man-p">Acesse <strong>Configurações → Usuários</strong>. Perfis disponíveis:</p>
    <table class="man-table">
      <thead><tr><th>Perfil</th><th>Acesso</th></tr></thead>
      <tbody>
        <?php foreach([
          ['Admin','Acesso total ao sistema incluindo configurações e financeiro.'],
          ['Gerente','Acesso a OS, clientes, estoque e relatórios. Sem acesso a configurações avançadas.'],
          ['Técnico','Acesso às OS atribuídas a ele e ao estoque.'],
          ['Recepcionista','Abertura de OS, cadastro de clientes e agenda.'],
        ] as[$p,$a]):?>
        <tr><td><strong><?=$p?></strong></td><td><?=$a?></td></tr>
        <?php endforeach;?>
      </tbody>
    </table>
    <div class="man-tip"><i class="bi bi-info-circle-fill"></i>O número de usuários que você pode cadastrar depende do seu plano. Veja em <a href="#cfg-limites">Limite de usuários e sessão</a>.</div>
  </div>

  <!-- Técnicos e comissão -->
  <div class="man-section" id="cfg-tecnicos">
    <h2 class="man-h2"><i class="bi bi-tools"></i> Técnicos e % de comissão</h2>
    <p class="man-p">Acesse <strong>Configurações do Sistema → Técnicos</strong>. Essa tela lista quem atende OS (perfil Técnico ou qualquer usuário marcado como "atende OS") e permite configurar o percentual de comissão individual de cada um.</p>
    <div class="man-step"><div class="man-step-n">1</div><div class="man-step-t">Clique em editar o técnico desejado.</div></div>
    <div class="man-step"><div class="man-step-n">2</div><div class="man-step-t">Informe o <strong>percentual de comissão</strong> próprio dele. Deixe em branco para usar o percentual padrão da empresa.</div></div>
    <div class="man-tip"><i class="bi bi-lightbulb-fill"></i>O percentual padrão da empresa (usado quando o técnico não tem um % próprio) fica em <strong>Configurações → Comissão padrão</strong>.</div>
  </div>

  <div class="man-section" id="cfg-status">
    <h2 class="man-h2"><i class="bi bi-tags-fill"></i> Personalizar Status de OS</h2>
    <p class="man-p">Acesse <strong>Configurações → Status de OS</strong>. Você pode criar, editar, reordenar e excluir status. Cada status tem um <em>tipo</em> que define seu comportamento no sistema.</p>
    <div class="man-warn"><i class="bi bi-exclamation-triangle-fill"></i>Não exclua status que já possuem OS vinculadas — isso pode causar erros no sistema.</div>
  </div>

  <!-- Ligar/desligar funções do sistema -->
  <div class="man-section" id="cfg-ferramentas">
    <h2 class="man-h2"><i class="bi bi-toggles"></i> Ligar e desligar funções do sistema</h2>
    <p class="man-p">O administrador pode ligar ou desligar, para <strong>toda a empresa</strong>, alguns recursos do sistema. Tudo fica em <strong>Configurações do Sistema</strong>, na sidebar:</p>
    <table class="man-table">
      <thead><tr><th>Função</th><th>Onde ligar/desligar</th><th>O que faz</th></tr></thead>
      <tbody>
        <?php foreach([
          ['Chat da equipe','Configurações → Chat da Equipe','Liga/desliga o chat interno (sino de conversas + caixa de mensagens nas OS). Tem também avisos sonoros e repetição do aviso a cada 10s, configuráveis separadamente. As mensagens já enviadas não são apagadas ao desligar.'],
          ['Calculadora','Configurações → Calculadora e Mentor','Mostra ou esconde o botão flutuante da calculadora na tela de todo mundo na empresa.'],
          ['Mentor (assistente IA)','Configurações → Calculadora e Mentor','Mostra ou esconde o botão flutuante do Mentor IA na tela de todo mundo na empresa.'],
          ['Previsão de entrega','Configurações → Previsão de Entrega','Mostra ou esconde o campo de previsão de entrega nas telas de OS.'],
          ['Exibição do texto','Configurações → Exibição do Texto','Escolhe se nomes e textos do sistema aparecem em formatação normal ou em MAIÚSCULO. Só muda a exibição — o que foi digitado continua salvo do jeito original.'],
        ] as[$f,$l,$q]):?>
        <tr><td><strong><?=$f?></strong></td><td><?=$l?></td><td><?=$q?></td></tr>
        <?php endforeach;?>
      </tbody>
    </table>
    <div class="man-tip"><i class="bi bi-lightbulb-fill"></i>Essas opções só aparecem no menu para usuários com o papel de <strong>administrador</strong>.</div>
  </div>

  <!-- Limite de usuários e sessão -->
  <div class="man-section" id="cfg-limites">
    <h2 class="man-h2"><i class="bi bi-shield-lock-fill"></i> Limite de usuários e sessão única</h2>
    <p class="man-p">Cada plano do FixaOS inclui um número de usuários:</p>
    <table class="man-table">
      <thead><tr><th>Plano</th><th>Usuários incluídos</th></tr></thead>
      <tbody>
        <?php foreach([
          ['Autônomo','2 usuários'],
          ['Oficina','5 usuários'],
          ['Top Empresa','Ilimitado'],
        ] as[$p,$a]):?>
        <tr><td><strong><?=$p?></strong></td><td><?=$a?></td></tr>
        <?php endforeach;?>
      </tbody>
    </table>
    <p class="man-p">Ao atingir o limite, a tela de <strong>Usuários</strong> bloqueia a criação (ou reativação) de um novo usuário e sugere fazer upgrade de plano.</p>
    <h3 class="man-h3">Sessão única por login</h3>
    <p class="man-p">Cada conta só pode estar logada em <strong>um dispositivo/navegador por vez</strong>. Se a mesma conta logar em outro lugar, a sessão anterior é derrubada automaticamente, com o aviso "Sua sessão foi encerrada porque esta conta foi acessada em outro dispositivo ou navegador."</p>
    <div class="man-tip"><i class="bi bi-info-circle-fill"></i>Isso vale para qualquer plano — mesmo no Top Empresa, cada pessoa da equipe precisa do próprio login. Não é possível várias pessoas usarem a mesma conta ao mesmo tempo.</div>
    <div class="man-tip"><i class="bi bi-lightbulb-fill"></i>Se você foi desconectado sem motivo aparente, é provável que alguém (ou você mesmo, em outro aparelho) tenha feito login com essa conta nesse meio tempo.</div>
  </div>

</div><!-- /manual-content -->
</div><!-- /manual-wrap -->

<?php if (!$modoPdf): ?>
<script>
// Highlight nav link on scroll
const sections=document.querySelectorAll('.man-section');
const links=document.querySelectorAll('.man-nav-link');
const observer=new IntersectionObserver(entries=>{
  entries.forEach(e=>{
    if(e.isIntersecting){
      links.forEach(l=>l.classList.remove('active'));
      const active=document.querySelector('.man-nav-link[href="#'+e.target.id+'"]');
      if(active)active.classList.add('active');
    }
  });
},{threshold:.3,rootMargin:'-60px 0px -60% 0px'});
sections.forEach(s=>observer.observe(s));

// Smooth scroll
links.forEach(l=>{
  l.addEventListener('click',e=>{
    e.preventDefault();
    const t=document.querySelector(l.getAttribute('href'));
    if(t)t.scrollIntoView({behavior:'smooth',block:'start'});
  });
});

// Busca no sistema
const BUSCA_URL = '<?= url('/api/busca') ?>';
let buscaTimeout = null;

function buscaGlobalInput() {
  clearTimeout(buscaTimeout);
  const termo = document.getElementById('buscaInput').value.trim();
  const resultDiv = document.getElementById('buscaResultados');
  if (termo.length < 2) { resultDiv.style.display = 'none'; return; }
  buscaTimeout = setTimeout(async () => {
    try {
      const r = await fetch(`${BUSCA_URL}?q=${encodeURIComponent(termo)}`);
      const dados = await r.json();
      renderBuscaResultados(dados);
    } catch(e) {}
  }, 300);
}

function escBusca(s) {
  return String(s ?? '').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
}

function renderBuscaResultados(dados) {
  const resultDiv = document.getElementById('buscaResultados');
  let html = '';

  if (dados.manual && dados.manual.length) {
    dados.manual.forEach(m => {
      html += `<a href="${m.url}" class="d-block px-3 py-2 text-decoration-none text-dark border-bottom"><i class="bi bi-question-circle me-1"></i>${escBusca(m.label)}</a>`;
    });
  }

  if (!html) html = '<div class="text-center text-muted py-3 small">Nenhum resultado encontrado.</div>';

  resultDiv.innerHTML = html;
  resultDiv.style.display = 'block';
}

document.addEventListener('click', function(e) {
  const resultDiv = document.getElementById('buscaResultados');
  const input = document.getElementById('buscaInput');
  if (resultDiv && resultDiv.style.display !== 'none' && !resultDiv.contains(e.target) && e.target !== input) {
    resultDiv.style.display = 'none';
  }
});
</script>
<?php endif; ?>
