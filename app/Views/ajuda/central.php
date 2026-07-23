<?php $titulo = 'Central de Ajuda'; ?>
<style>
.help-hero{background:var(--bg2);border-bottom:1px solid var(--border);padding:4rem 0 3rem;text-align:center}
.help-search{max-width:520px;margin:1.5rem auto 0}
.help-search input{background:rgba(255,255,255,.06);border:1px solid var(--border);color:#fff;border-radius:12px;padding:.8rem 1.2rem;font-size:1rem;width:100%}
.help-search input::placeholder{color:#4b5563}
.help-search input:focus{outline:none;border-color:rgba(249,115,22,.5);background:rgba(255,255,255,.08)}
.cat-card{background:var(--bg2);border:1px solid var(--border);border-radius:16px;padding:1.8rem;text-decoration:none;display:block;transition:.2s}
.cat-card:hover{border-color:rgba(249,115,22,.4);transform:translateY(-3px)}
.cat-icon{width:52px;height:52px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:1.4rem;margin-bottom:1rem}
.cat-title{color:#fff;font-weight:700;font-size:1rem;margin-bottom:.3rem}
.cat-sub{color:var(--muted);font-size:.82rem}
.art-item{display:flex;align-items:center;justify-content:space-between;padding:.9rem 0;border-bottom:1px solid var(--border);text-decoration:none;color:#cbd5e1;font-size:.9rem;transition:color .15s}
.art-item:hover{color:var(--brand)}
.art-item:last-child{border-bottom:none}
.badge-new{background:rgba(249,115,22,.15);color:#fb923c;font-size:.68rem;font-weight:700;padding:.15rem .5rem;border-radius:4px;margin-left:.5rem}
</style>

<!-- Hero -->
<div class="help-hero">
  <div class="sec-tag">Central de Ajuda</div>
  <h1 class="sec-title">Como podemos te ajudar?</h1>
  <p style="color:var(--muted);margin-top:.5rem">Documentação, tutoriais e respostas para as dúvidas mais comuns do FixaOS.</p>
  <div class="help-search">
    <input type="text" id="helpSearch" placeholder="Buscar artigos, tutoriais, dúvidas..." oninput="filtrarArtigos(this.value)">
  </div>
</div>

<div style="padding:4rem 0;background:var(--bg)">
<div class="container">

  <!-- Categorias -->
  <div class="row g-4 mb-5">
    <?php
    $cats=[
      ['bi-clipboard2-pulse','rgba(59,130,246,.15)','#60a5fa','Ordens de Serviço','Como abrir, editar, fechar e imprimir OS.',8,'#os'],
      ['bi-people-fill','rgba(34,197,94,.12)','#4ade80','Clientes e CRM','Cadastro, histórico e pipeline de vendas.',5,'#crm'],
      ['bi-box-seam','rgba(249,115,22,.12)','#fb923c','Estoque','Peças, fornecedores e movimentações.',4,'#estoque'],
      ['bi-graph-up-arrow','rgba(168,85,247,.12)','#c084fc','Financeiro','Lançamentos, fluxo de caixa e relatórios.',6,'#financeiro'],
      ['bi-calendar-week','rgba(236,72,153,.12)','#f472b6','Agenda','Agendamentos e compromissos.',3,'#agenda'],
      ['bi-person-gear','rgba(20,184,166,.12)','#2dd4bf','Usuários e Permissões','Perfis, acessos e configurações.',4,'#usuarios'],
      ['bi-shop','rgba(59,130,246,.12)','#60a5fa','Marketplace','Compra e venda de peças.',5,'#marketplace'],
      ['bi-gear','rgba(251,191,36,.12)','#fbbf24','Configurações','Empresa, impressão e personalizações.',6,'#config'],
    ];
    foreach($cats as[$icon,$bg,$c,$title,$sub,$n,$href]):?>
    <div class="col-6 col-md-3">
      <a href="<?=$href?>" class="cat-card">
        <div class="cat-icon" style="background:<?=$bg?>">
          <i class="bi <?=$icon?>" style="color:<?=$c?>"></i>
        </div>
        <div class="cat-title"><?=$title?></div>
        <div class="cat-sub"><?=$sub?></div>
        <div style="color:var(--muted);font-size:.75rem;margin-top:.6rem"><?=$n?> artigos</div>
      </a>
    </div>
    <?php endforeach;?>
  </div>

  <!-- Artigos por seção -->
  <div id="artigos-container">

    <!-- OS -->
    <div class="mb-5 art-section" id="os">
      <h3 style="color:#fff;font-weight:700;margin-bottom:1.2rem;font-size:1.1rem">
        <i class="bi bi-clipboard2-pulse me-2" style="color:#60a5fa"></i>Ordens de Serviço
      </h3>
      <div style="background:var(--bg2);border:1px solid var(--border);border-radius:14px;padding:0 1.5rem">
        <?php
        $arts=[
          ['Como abrir uma nova Ordem de Serviço','Passo a passo completo: cliente, equipamento, defeito e configurações.'],
          ['Entendendo os status de OS','O que cada status significa e como personalizar o workflow.'],
          ['Como adicionar serviços e peças em uma OS','Registre mão de obra e peças utilizadas diretamente na OS.'],
          ['Imprimir laudo, orçamento e garantia','Acesse os modelos de impressão e envie por WhatsApp.'],
          ['Fechar uma OS e registrar pagamento','Finalize o atendimento e gere o comprovante de fechamento.','new'],
          ['Garantia: como registrar retorno e abrir OS de garantia','Fluxo completo de retorno em garantia com rastreabilidade.'],
          ['Filtros e busca na lista de OS','Como usar os filtros por status, técnico, período e cliente.'],
          ['Exportar relatório de OS para PDF','Gere relatórios por status e período com um clique.'],
        ];
        foreach($arts as $art):?>
        <a href="#" class="art-item">
          <div>
            <?php $t=$art[0];$d=$art[1];$badge=$art[2]??null; ?><span class="art-text"><?=$t?><?php if($badge):?><span class="badge-new">NOVO</span><?php endif;?></span>
            <div style="color:var(--muted);font-size:.78rem;margin-top:.15rem"><?=$d?></div>
          </div>
          <i class="bi bi-chevron-right" style="color:var(--muted);flex-shrink:0;margin-left:1rem"></i>
        </a>
        <?php endforeach;?>
      </div>
    </div>

    <!-- CRM -->
    <div class="mb-5 art-section" id="crm">
      <h3 style="color:#fff;font-weight:700;margin-bottom:1.2rem;font-size:1.1rem">
        <i class="bi bi-people-fill me-2" style="color:#4ade80"></i>Clientes e CRM
      </h3>
      <div style="background:var(--bg2);border:1px solid var(--border);border-radius:14px;padding:0 1.5rem">
        <?php foreach([
          ['Cadastrar um novo cliente','PF ou PJ, com todos os dados de contato e endereço.'],
          ['Ver histórico de atendimentos do cliente','Acesse todas as OS anteriores de um cliente em um clique.'],
          ['Pipeline de vendas — como usar o CRM','Gerencie oportunidades e acompanhe negociações em andamento.'],
          ['Importar clientes de planilha ou sistema anterior','Migre sua base de clientes com facilidade.'],
          ['Busca rápida de clientes durante abertura de OS','Como funciona o autocomplete na tela de nova OS.'],
        ] as[$t,$d]):?>
        <a href="#" class="art-item">
          <div>
            <span class="art-text"><?=$t?></span>
            <div style="color:var(--muted);font-size:.78rem;margin-top:.15rem"><?=$d?></div>
          </div>
          <i class="bi bi-chevron-right" style="color:var(--muted);flex-shrink:0;margin-left:1rem"></i>
        </a>
        <?php endforeach;?>
      </div>
    </div>

    <!-- Estoque -->
    <div class="mb-5 art-section" id="estoque">
      <h3 style="color:#fff;font-weight:700;margin-bottom:1.2rem;font-size:1.1rem">
        <i class="bi bi-box-seam me-2" style="color:#fb923c"></i>Controle de Estoque
      </h3>
      <div style="background:var(--bg2);border:1px solid var(--border);border-radius:14px;padding:0 1.5rem">
        <?php foreach([
          ['Cadastrar produtos e peças no estoque','Nome, código, custo, preço e quantidade mínima.'],
          ['Dar entrada e saída de estoque manualmente','Registre movimentações fora das OS quando necessário.'],
          ['Como o estoque se atualiza automaticamente nas OS','Ao adicionar uma peça em uma OS, o estoque deduz automaticamente.'],
          ['Configurar alerta de estoque mínimo','Receba aviso quando uma peça estiver abaixo do limite configurado.'],
        ] as[$t,$d]):?>
        <a href="#" class="art-item">
          <div>
            <span class="art-text"><?=$t?></span>
            <div style="color:var(--muted);font-size:.78rem;margin-top:.15rem"><?=$d?></div>
          </div>
          <i class="bi bi-chevron-right" style="color:var(--muted);flex-shrink:0;margin-left:1rem"></i>
        </a>
        <?php endforeach;?>
      </div>
    </div>

    <!-- Financeiro -->
    <div class="mb-5 art-section" id="financeiro">
      <h3 style="color:#fff;font-weight:700;margin-bottom:1.2rem;font-size:1.1rem">
        <i class="bi bi-graph-up-arrow me-2" style="color:#c084fc"></i>Financeiro
      </h3>
      <div style="background:var(--bg2);border:1px solid var(--border);border-radius:14px;padding:0 1.5rem">
        <?php foreach([
          ['Como registrar um lançamento financeiro','Receitas, despesas, categorias e formas de pagamento.'],
          ['Fluxo de caixa: visão geral das entradas e saídas','Acompanhe o saldo do período com gráfico e tabela detalhada.'],
          ['Comissões de técnicos: como funciona o cálculo','Configure o percentual por técnico e veja o cálculo automático.'],
          ['Categorias financeiras: como criar e organizar','Organize seus lançamentos por categorias personalizadas.'],
          ['Relatório financeiro por período','Exporte um resumo financeiro com totais por categoria.'],
          ['Como integrar o fechamento de OS com o financeiro','OS fechadas geram lançamentos automáticos no financeiro.'],
        ] as[$t,$d]):?>
        <a href="#" class="art-item">
          <div>
            <span class="art-text"><?=$t?></span>
            <div style="color:var(--muted);font-size:.78rem;margin-top:.15rem"><?=$d?></div>
          </div>
          <i class="bi bi-chevron-right" style="color:var(--muted);flex-shrink:0;margin-left:1rem"></i>
        </a>
        <?php endforeach;?>
      </div>
    </div>

    <!-- Agenda -->
    <div class="mb-5 art-section" id="agenda">
      <h3 style="color:#fff;font-weight:700;margin-bottom:1.2rem;font-size:1.1rem">
        <i class="bi bi-calendar-week me-2" style="color:#f472b6"></i>Agenda
      </h3>
      <div style="background:var(--bg2);border:1px solid var(--border);border-radius:14px;padding:0 1.5rem">
        <?php foreach([
          ['Criar um agendamento','Vincule o compromisso a um cliente, OS e técnico responsável.'],
          ['Visualizar agenda do dia, semana ou mês','Alterne entre as visões para planejar melhor o atendimento.'],
          ['Editar ou cancelar um agendamento','Como reagendar ou remover um compromisso existente.'],
        ] as[$t,$d]):?>
        <a href="#" class="art-item">
          <div>
            <span class="art-text"><?=$t?></span>
            <div style="color:var(--muted);font-size:.78rem;margin-top:.15rem"><?=$d?></div>
          </div>
          <i class="bi bi-chevron-right" style="color:var(--muted);flex-shrink:0;margin-left:1rem"></i>
        </a>
        <?php endforeach;?>
      </div>
    </div>

    <!-- Usuários -->
    <div class="mb-5 art-section" id="usuarios">
      <h3 style="color:#fff;font-weight:700;margin-bottom:1.2rem;font-size:1.1rem">
        <i class="bi bi-person-gear me-2" style="color:#2dd4bf"></i>Usuários e Permissões
      </h3>
      <div style="background:var(--bg2);border:1px solid var(--border);border-radius:14px;padding:0 1.5rem">
        <?php foreach([
          ['Perfis de acesso: Admin, Gerente, Técnico e Recepcionista','O que cada perfil pode ver e fazer no sistema.'],
          ['Como convidar um novo usuário','Cadastre um técnico ou recepcionista e defina o perfil.'],
          ['Redefinir senha de um usuário','O admin pode redefinir a senha de qualquer membro da equipe.'],
          ['Desativar um usuário sem perder o histórico','Como remover o acesso sem apagar dados vinculados.'],
        ] as[$t,$d]):?>
        <a href="#" class="art-item">
          <div>
            <span class="art-text"><?=$t?></span>
            <div style="color:var(--muted);font-size:.78rem;margin-top:.15rem"><?=$d?></div>
          </div>
          <i class="bi bi-chevron-right" style="color:var(--muted);flex-shrink:0;margin-left:1rem"></i>
        </a>
        <?php endforeach;?>
      </div>
    </div>

    <!-- Marketplace -->
    <div class="mb-5 art-section" id="marketplace">
      <h3 style="color:#fff;font-weight:700;margin-bottom:1.2rem;font-size:1.1rem">
        <i class="bi bi-shop me-2" style="color:#60a5fa"></i>Marketplace de Peças
      </h3>
      <div style="background:var(--bg2);border:1px solid var(--border);border-radius:14px;padding:0 1.5rem">
        <?php foreach([
          ['Como criar um anúncio de peça','Título, descrição, preço, fotos e categorias do anúncio.'],
          ['Editar ou pausar um anúncio','Atualize informações ou pause temporariamente a peça.'],
          ['Sistema de créditos do Marketplace','Como funciona a compra de créditos para anunciar.'],
          ['Encontrar peças de outras assistências','Use a busca pública sem precisar fazer login.'],
          ['Entrar em contato com o vendedor','WhatsApp direto pelo anúncio — sem intermediários.'],
        ] as[$t,$d]):?>
        <a href="#" class="art-item">
          <div>
            <span class="art-text"><?=$t?></span>
            <div style="color:var(--muted);font-size:.78rem;margin-top:.15rem"><?=$d?></div>
          </div>
          <i class="bi bi-chevron-right" style="color:var(--muted);flex-shrink:0;margin-left:1rem"></i>
        </a>
        <?php endforeach;?>
      </div>
    </div>

    <!-- Config -->
    <div class="mb-5 art-section" id="config">
      <h3 style="color:#fff;font-weight:700;margin-bottom:1.2rem;font-size:1.1rem">
        <i class="bi bi-gear me-2" style="color:#fbbf24"></i>Configurações
      </h3>
      <div style="background:var(--bg2);border:1px solid var(--border);border-radius:14px;padding:0 1.5rem">
        <?php foreach([
          ['Dados da empresa: nome, logo, endereço e contato','Configure as informações que aparecem nos documentos impressos.'],
          ['Personalizar status das OS','Adicione, edite, reordene ou remova status do workflow.'],
          ['Configurar prefixo e formato do número da OS','Defina como os números das OS são gerados.'],
          ['Prazo de garantia padrão','Configure o tempo padrão de garantia para as OS da empresa.'],
          ['Backup e restauração de dados','Como fazer backup manual e restaurar em caso de necessidade.'],
          ['Integrar login com Google','Configure as credenciais OAuth para permitir login com Google.'],
        ] as[$t,$d]):?>
        <a href="#" class="art-item">
          <div>
            <span class="art-text"><?=$t?></span>
            <div style="color:var(--muted);font-size:.78rem;margin-top:.15rem"><?=$d?></div>
          </div>
          <i class="bi bi-chevron-right" style="color:var(--muted);flex-shrink:0;margin-left:1rem"></i>
        </a>
        <?php endforeach;?>
      </div>
    </div>

  </div><!-- /artigos-container -->

  <!-- Suporte -->
  <div style="background:var(--bg2);border:1px solid var(--border);border-radius:20px;padding:2.5rem;text-align:center;margin-top:2rem">
    <div class="sec-tag">Ainda com dúvida?</div>
    <h3 style="color:#fff;font-weight:700;margin:.5rem 0">Fale com o suporte</h3>
    <p style="color:var(--muted);font-size:.9rem;margin-bottom:1.5rem">Nossa equipe responde em até 4 horas úteis por WhatsApp ou e-mail.</p>
    <div class="d-flex gap-3 justify-content-center flex-wrap">
      <a href="https://wa.me/5500000000000" target="_blank" class="btn-brand btn px-4 py-2">
        <i class="bi bi-whatsapp me-2"></i>WhatsApp
      </a>
      <a href="mailto:suporte@fixaos.com.br" class="btn-ghost btn px-4 py-2">
        <i class="bi bi-envelope me-2"></i>E-mail
      </a>
    </div>
  </div>

</div>
</div>

<script>
function filtrarArtigos(q){
  q=q.toLowerCase().trim();
  document.querySelectorAll('.art-item').forEach(a=>{
    const txt=a.querySelector('.art-text')?.textContent.toLowerCase()||'';
    const desc=a.querySelector('[style*="color:var(--muted)"]')?.textContent.toLowerCase()||'';
    a.style.display=(q===''||txt.includes(q)||desc.includes(q))?'flex':'none';
  });
  document.querySelectorAll('.art-section').forEach(s=>{
    const visible=[...s.querySelectorAll('.art-item')].some(a=>a.style.display!=='none');
    s.style.display=(q===''||visible)?'block':'none';
  });
}
</script>
