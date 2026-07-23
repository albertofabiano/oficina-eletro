<?php $titulo = 'Manual do Usuário'; ?>
<style>
/* Layout geral */
.manual-wrap{display:flex;gap:0;min-height:calc(100vh - 56px);background:#f1f5f9}

/* Nav lateral — dark */
.manual-sidebar{
  width:250px;flex-shrink:0;
  background:#1a1d23;
  border-right:1px solid #2d3139;
  position:sticky;top:56px;
  height:calc(100vh - 56px);
  overflow-y:auto;padding:1rem 0;
}
.man-nav-title{
  font-size:.65rem;color:#6b7280;
  text-transform:uppercase;letter-spacing:.09em;
  padding:.5rem 1.1rem .2rem;font-weight:700;margin-top:.8rem;
}
.man-nav-link{
  display:block;padding:.42rem 1.1rem;
  color:#9ca3af;font-size:.84rem;
  text-decoration:none;
  border-left:3px solid transparent;
  transition:.15s;
}
.man-nav-link:hover{color:#fff;background:rgba(255,255,255,.04)}
.man-nav-link.active{color:#fff;border-left-color:#f97316;background:rgba(249,115,22,.1);font-weight:600}

/* Área de conteúdo — fundo claro */
.manual-content{
  flex:1;padding:2.5rem 3rem;
  max-width:820px;
  background:#fff;
  border-right:1px solid #e2e8f0;
}

/* Seções */
.man-section{margin-bottom:3rem;scroll-margin-top:72px}

/* Títulos */
.man-h2{
  font-size:1.45rem;font-weight:800;
  color:#0f172a;
  margin-bottom:1.2rem;
  padding-bottom:.7rem;
  border-bottom:2px solid #f97316;
  display:flex;align-items:center;gap:.6rem;
}
.man-h2 i{color:#f97316;font-size:1.2rem}
.man-h3{
  font-size:.98rem;font-weight:700;
  color:#1e3a5f;
  margin:1.6rem 0 .6rem;
}

/* Parágrafos */
.man-p{color:#374151;font-size:.93rem;line-height:1.85;margin-bottom:1rem}

/* Passos */
.man-step{
  display:flex;gap:.9rem;align-items:flex-start;
  background:#f8fafc;
  border:1px solid #e2e8f0;
  border-left:4px solid #f97316;
  border-radius:0 10px 10px 0;
  padding:.9rem 1.1rem;margin-bottom:.6rem;
}
.man-step-n{
  width:26px;height:26px;border-radius:50%;
  background:#f97316;color:#fff;
  font-weight:800;font-size:.8rem;
  display:flex;align-items:center;justify-content:center;
  flex-shrink:0;margin-top:1px;
}
.man-step-t{color:#1f2937;font-size:.9rem;line-height:1.65}
.man-step-t strong{color:#1e3a5f}

/* Avisos */
.man-tip{
  background:#fff7ed;border:1px solid #fed7aa;
  border-left:4px solid #f97316;
  border-radius:0 10px 10px 0;
  padding:.8rem 1rem;margin:1rem 0;
  font-size:.87rem;color:#92400e;
}
.man-tip i{color:#f97316;margin-right:.5rem}
.man-warn{
  background:#fef2f2;border:1px solid #fecaca;
  border-left:4px solid #ef4444;
  border-radius:0 10px 10px 0;
  padding:.8rem 1rem;margin:1rem 0;
  font-size:.87rem;color:#991b1b;
}
.man-warn i{color:#ef4444;margin-right:.5rem}

/* Tabelas */
.man-table{width:100%;border-collapse:collapse;margin:1rem 0;font-size:.87rem;border:1px solid #e2e8f0;border-radius:10px;overflow:hidden}
.man-table th{
  background:#1e3a5f;color:#fff;
  padding:.65rem 1rem;text-align:left;
  font-size:.78rem;text-transform:uppercase;letter-spacing:.04em;font-weight:700;
}
.man-table td{padding:.65rem 1rem;border-bottom:1px solid #f1f5f9;color:#374151;vertical-align:top}
.man-table tr:nth-child(even) td{background:#f8fafc}
.man-table tr:last-child td{border-bottom:none}
.man-table td:first-child{color:#1e3a5f;font-weight:600}

/* Badge */
.man-badge{display:inline-block;border-radius:5px;padding:.15rem .55rem;font-size:.72rem;font-weight:700}
.kbd{background:#f1f5f9;border:1px solid #cbd5e1;border-radius:5px;padding:.15rem .5rem;font-size:.8rem;color:#1e293b;font-family:monospace}

/* Breadcrumb topo */
.man-header-bar{
  background:#fff;border-bottom:1px solid #e2e8f0;
  padding:.7rem 3rem;
  font-size:.82rem;color:#64748b;
  position:sticky;top:56px;z-index:10;
}
.man-header-bar span{color:#1e3a5f;font-weight:600}

@media(max-width:768px){
  .manual-sidebar{display:none}
  .manual-content{padding:1.2rem;border:none}
  .man-header-bar{padding:.7rem 1rem}
}
</style>

<div class="manual-wrap">

<!-- Sidebar de navegação -->
<div class="manual-sidebar">
  <div style="padding:.5rem 1.2rem 1rem;border-bottom:1px solid #2d3139">
    <div style="color:#fff;font-weight:700;font-size:.9rem">Manual do Usuário</div>
    <div style="color:#4b5563;font-size:.75rem">FixaOS v1.0</div>
  </div>
  <?php
  $sections=[
    'Primeiros Passos'=>[
      ['inicio','Visão geral do sistema'],
      ['dashboard','Dashboard'],
      ['navegacao','Navegação e atalhos'],
    ],
    'Ordens de Serviço'=>[
      ['os-abrir','Abrir nova OS'],
      ['os-status','Status e workflow'],
      ['os-servicos','Serviços e peças'],
      ['os-imprimir','Impressão e PDF'],
      ['os-fechar','Fechar OS'],
      ['os-garantia','Garantia e retorno'],
      ['os-reabrir','Reabrir OS'],
    ],
    'Clientes e CRM'=>[
      ['clientes','Cadastro de clientes'],
      ['crm','Pipeline de vendas'],
    ],
    'Estoque'=>[
      ['estoque-produtos','Cadastro de produtos'],
      ['estoque-mov','Movimentações'],
    ],
    'Frente de Caixa'=>[
      ['pdv','PDV — Vendas rápidas'],
    ],
    'Financeiro'=>[
      ['fin-lancamentos','Lançamentos'],
      ['fin-fluxo','Fluxo de caixa'],
      ['fin-relatorios','Relatórios'],
    ],
    'Agenda'=>[
      ['agenda','Agendamentos'],
    ],
    'Marketplace'=>[
      ['mkt-anuncios','Criar anúncio'],
      ['mkt-creditos','Créditos'],
      ['mkt-vitrine','Vitrine e Marketplace Público'],
      ['mkt-pedidos','Pedidos de Peças'],
    ],
    'Fórum Técnico'=>[
      ['forum-usar','Como usar o Fórum'],
    ],
    'Ferramentas'=>[
      ['editor-imagens','Editor de Imagens'],
    ],
    'Configurações'=>[
      ['cfg-empresa','Dados da empresa'],
      ['cfg-usuarios','Usuários'],
      ['cfg-status','Status de OS'],
    ],
  ];
  foreach($sections as$group=>$links):?>
  <div class="man-nav-title"><?=$group?></div>
  <?php foreach($links as[$id,$label]):?>
  <a href="#<?=$id?>" class="man-nav-link"><?=$label?></a>
  <?php endforeach;endforeach;?>
</div>

<!-- Conteúdo -->
<div class="manual-content">

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
    <div class="man-tip"><i class="bi bi-lightbulb-fill"></i>Use a busca no topo da página do Manual para encontrar rapidamente OS, clientes, produtos ou artigos de ajuda, sem precisar navegar pelos menus.</div>
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

    <h3 class="man-h3">Aba 3 — Defeito</h3>
    <div class="man-step"><div class="man-step-n">1</div><div class="man-step-t">Descreva o defeito relatado pelo cliente. Seja específico: <em>"Não liga ao apertar o botão Power"</em>.</div></div>
    <div class="man-step"><div class="man-step-n">2</div><div class="man-step-t">Informe observações internas se houver (não aparecem na impressão do cliente).</div></div>

    <h3 class="man-h3">Aba 4 — Configurações</h3>
    <div class="man-step"><div class="man-step-n">1</div><div class="man-step-t">Defina técnico responsável, prioridade, valor estimado e prazo de previsão.</div></div>
    <div class="man-step"><div class="man-step-n">2</div><div class="man-step-t">Clique em <strong>"Salvar OS"</strong>. O número será gerado automaticamente.</div></div>

    <div class="man-tip"><i class="bi bi-lightbulb-fill"></i>O número da OS é gerado com o prefixo configurado em <strong>Configurações</strong>. Padrão: OS000001.</div>
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
  </div>

  <!-- Impressão -->
  <div class="man-section" id="os-imprimir">
    <h2 class="man-h2"><i class="bi bi-printer-fill"></i> Impressão e PDF</h2>
    <p class="man-p">O FixaOS oferece 4 modelos de documento para impressão ou envio por WhatsApp:</p>
    <table class="man-table">
      <thead><tr><th>Documento</th><th>Quando usar</th></tr></thead>
      <tbody>
        <?php foreach([
          ['Ordem de Serviço (OS)','Na entrada do equipamento — entregue ao cliente como recibo.'],
          ['Orçamento','Após o diagnóstico — lista serviços e valores para aprovação.'],
          ['Laudo Técnico','Documento técnico com diagnóstico detalhado.'],
          ['Fechamento','Na entrega — comprovante de conclusão com valor pago.'],
          ['Garantia','Entregue junto com o equipamento ao finalizar.'],
        ] as[$d,$q]):?>
        <tr><td><strong><?=$d?></strong></td><td><?=$q?></td></tr>
        <?php endforeach;?>
      </tbody>
    </table>
    <p class="man-p">Para imprimir, abra a OS e clique no botão <strong>Imprimir</strong>. Escolha o documento desejado. Na tela de impressão, há também um botão para <strong>enviar por WhatsApp</strong> diretamente.</p>
  </div>

  <!-- Fechar OS -->
  <div class="man-section" id="os-fechar">
    <h2 class="man-h2"><i class="bi bi-check-circle-fill"></i> Fechar uma OS</h2>
    <p class="man-p">O fechamento registra a entrega do equipamento e gera automaticamente um lançamento no financeiro.</p>
    <div class="man-step"><div class="man-step-n">1</div><div class="man-step-t">Mude o status da OS para <strong>"Pronto para Retirada"</strong> (ou equivalente do tipo <em>Concluída</em>).</div></div>
    <div class="man-step"><div class="man-step-n">2</div><div class="man-step-t">O botão <strong>"Fechar OS"</strong> ficará disponível. Clique nele.</div></div>
    <div class="man-step"><div class="man-step-n">3</div><div class="man-step-t">Confirme o valor, forma de pagamento e situação (pago ou pendente).</div></div>
    <div class="man-step"><div class="man-step-n">4</div><div class="man-step-t">O sistema gera o lançamento financeiro e registra a data de conclusão.</div></div>
    <div class="man-tip"><i class="bi bi-lightbulb-fill"></i>Após fechar, imprima o comprovante de fechamento e a garantia para entregar ao cliente.</div>
  </div>

  <!-- Garantia -->
  <div class="man-section" id="os-garantia">
    <h2 class="man-h2"><i class="bi bi-shield-fill-check"></i> Garantia e retorno</h2>
    <p class="man-p">Quando um equipamento retorna dentro do prazo de garantia, o sistema permite abrir uma OS de garantia vinculada à original.</p>
    <div class="man-step"><div class="man-step-n">1</div><div class="man-step-t">Abra a OS original (a que foi fechada com garantia).</div></div>
    <div class="man-step"><div class="man-step-n">2</div><div class="man-step-t">Clique em <strong>"Abrir OS de Garantia"</strong>. O botão aparece se o prazo ainda está vigente.</div></div>
    <div class="man-step"><div class="man-step-n">3</div><div class="man-step-t">Uma nova OS é criada com vínculo à original, tipo <em>garantia</em> e o histórico do defeito anterior.</div></div>
    <div class="man-warn"><i class="bi bi-exclamation-triangle-fill"></i>OS de garantia aparecem em um filtro separado na lista de OS para fácil identificação.</div>
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

  <!-- PDV -->
  <div class="man-section" id="pdv">
    <h2 class="man-h2"><i class="bi bi-cash-stack"></i> PDV — Frente de Caixa</h2>
    <p class="man-p">O PDV é uma tela de venda rápida para quando você vende uma peça ou acessório avulso, sem precisar abrir uma Ordem de Serviço completa. Acesse pelo botão roxo <strong>"PDV / Frente de Caixa"</strong> na sidebar.</p>
    <div class="man-step"><div class="man-step-n">1</div><div class="man-step-t">Adicione os itens da venda: busque um produto do estoque (o sistema já traz o preço e verifica o saldo) ou digite uma descrição livre para itens fora do estoque.</div></div>
    <div class="man-step"><div class="man-step-n">2</div><div class="man-step-t">Selecione o cliente (opcional) e informe um desconto, se houver.</div></div>
    <div class="man-step"><div class="man-step-n">3</div><div class="man-step-t">Escolha a forma de pagamento: dinheiro, Pix, cartão de crédito, cartão de débito ou outro. No pagamento em dinheiro, informe o valor recebido e o troco é calculado automaticamente.</div></div>
    <div class="man-step"><div class="man-step-n">4</div><div class="man-step-t">Clique em <strong>Finalizar Venda</strong>. O sistema baixa o estoque dos produtos vendidos e gera automaticamente um lançamento de receita já paga no Financeiro.</div></div>
    <div class="man-warn"><i class="bi bi-exclamation-triangle-fill"></i>A venda é bloqueada se algum produto não tiver saldo suficiente em estoque.</div>
    <div class="man-tip"><i class="bi bi-lightbulb-fill"></i>Depois de finalizar, um comprovante de venda é gerado automaticamente e pode ser impresso ou enviado ao cliente.</div>
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

  <!-- Agenda -->
  <div class="man-section" id="agenda">
    <h2 class="man-h2"><i class="bi bi-calendar-week-fill"></i> Agenda</h2>
    <p class="man-p">Gerencie compromissos no calendário em <strong>Agenda</strong>. Vincule cada evento a um cliente, técnico e OS. Visualize por dia, semana ou mês.</p>
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
  </div>

  <div class="man-section" id="cfg-status">
    <h2 class="man-h2"><i class="bi bi-tags-fill"></i> Personalizar Status de OS</h2>
    <p class="man-p">Acesse <strong>Configurações → Status de OS</strong>. Você pode criar, editar, reordenar e excluir status. Cada status tem um <em>tipo</em> que define seu comportamento no sistema.</p>
    <div class="man-warn"><i class="bi bi-exclamation-triangle-fill"></i>Não exclua status que já possuem OS vinculadas — isso pode causar erros no sistema.</div>
  </div>

</div><!-- /manual-content -->
</div><!-- /manual-wrap -->

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
