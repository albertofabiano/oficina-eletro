<?php
/**
 * Popula a Base de Conhecimento (kb_artigos) com o conjunto completo de artigos
 * comerciais (landing) e funcionais (manual), pra o bot de suporte por WhatsApp
 * tirar dúvida de possíveis assinantes e de clientes já ativos.
 * Idempotente: pula artigos cujo slug já existe. Uso: php kb_seed_completo.php
 */
$dbCfg = require __DIR__ . '/../config/database.php';
$dbHost = $dbCfg['host'] ?? '127.0.0.1';
$dbName = $dbCfg['database'] ?? $dbCfg['dbname'] ?? 'fixaos';
$dbUser = $dbCfg['username'] ?? $dbCfg['user'] ?? 'fixaos';
$dbPass = $dbCfg['password'] ?? $dbCfg['pass'] ?? '';
$pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

$artigos = [
    // ── Comercial (landing) ──────────────────────────────────────────────
    [
        'slug' => 'funcionalidades-principais-do-fixaos',
        'categoria' => 'Comercial',
        'titulo' => 'Funcionalidades principais do FixaOS',
        'palavras_chave' => 'funcionalidades, recursos, o que tem, módulos',
        'conteudo' => "O FixaOS reúne: Ordens de Serviço com status personalizáveis, CRM de clientes, controle de "
            . "estoque, financeiro completo com comissão de técnico, agenda, relatórios com gráficos, marketplace de "
            . "peças entre assistências, fórum técnico, diretório público no Google, e cadastro de equipamento por "
            . "foto com IA. Tudo em um só sistema, 100% online, sem instalação.",
    ],
    [
        'slug' => 'cadastro-por-foto-com-ia',
        'categoria' => 'Comercial',
        'titulo' => 'Cadastro de equipamento por foto com IA',
        'palavras_chave' => 'foto, IA, etiqueta, scan, escanear, cadastro automático',
        'conteudo' => "Basta apontar a câmera do celular na etiqueta do aparelho (TV, eletrodoméstico, notebook) — "
            . "sem app, sem login, só escaneando um QR code na tela. A inteligência artificial lê marca, modelo e "
            . "número de série sozinha e preenche a OS no computador em segundos. Acaba a digitação e o erro de "
            . "modelo errado.",
    ],
    [
        'slug' => 'consulta-imei-anti-roubo',
        'categoria' => 'Comercial',
        'titulo' => 'Consulta de IMEI e verificação anti-roubo',
        'palavras_chave' => 'imei, roubado, furtado, anatel, celular',
        'conteudo' => "Ao digitar o IMEI de um celular, o aparelho se cadastra sozinho (marca e modelo preenchidos "
            . "automaticamente) e um clique consulta a Anatel pra mostrar se o celular foi dado como perdido, "
            . "roubado ou furtado — evitando que a assistência coloque a mão em aparelho de origem duvidosa.",
    ],
    [
        'slug' => 'chat-interno-da-equipe',
        'categoria' => 'Comercial',
        'titulo' => 'Chat interno da equipe dentro da OS',
        'palavras_chave' => 'chat, equipe, comunicação, conversa interna, diferencial',
        'conteudo' => "Diferencial exclusivo do FixaOS: técnico, recepção e admin conversam dentro da própria OS, em "
            . "tempo real, com alerta sonoro e sino de notificação. Toda a comunicação sobre aquele atendimento fica "
            . "registrada no histórico do serviço — acaba o \"quem falou o quê\" perdido no WhatsApp pessoal.",
    ],
    [
        'slug' => 'orcamento-comprovante-pdf-whatsapp',
        'categoria' => 'Comercial',
        'titulo' => 'Orçamento e comprovante em PDF pelo WhatsApp',
        'palavras_chave' => 'orçamento, comprovante, pdf, whatsapp, laudo',
        'conteudo' => "O sistema gera o PDF do orçamento, laudo técnico ou comprovante de fechamento e envia direto "
            . "pelo WhatsApp — documento de verdade, saindo do número da própria empresa. Cada OS também gera um "
            . "link de acompanhamento que o cliente usa pra ver o status em tempo real, sem precisar de login.",
    ],
    [
        'slug' => 'pagamento-dividido-e-pdv',
        'categoria' => 'Comercial',
        'titulo' => 'Pagamento dividido e PDV (frente de caixa)',
        'palavras_chave' => 'pdv, frente de caixa, pagamento dividido, cartão, pix, venda rápida',
        'conteudo' => "O PDV é uma tela de venda rápida pra peças e acessórios avulsos, sem precisar abrir uma OS "
            . "completa. Tanto no PDV quanto no fechamento de OS dá pra dividir o pagamento entre várias formas na "
            . "mesma venda (ex.: metade no Pix, metade no cartão), com a taxa da maquininha calculada e lançada "
            . "automaticamente no Financeiro.",
    ],
    [
        'slug' => 'modo-offline',
        'categoria' => 'Comercial',
        'titulo' => 'Modo offline — o trabalho não para se a internet cair',
        'palavras_chave' => 'offline, sem internet, conexão caiu, rascunho',
        'conteudo' => "Se a internet cair no meio do atendimento, o sistema continua no básico: dá pra consultar as "
            . "OS do dia e criar rascunhos de novas OS direto no navegador. Assim que a conexão volta, tudo "
            . "sincroniza sozinho, sem perder nada e sem retrabalho.",
    ],
    [
        'slug' => 'diretorio-publico-e-seo',
        'categoria' => 'Comercial',
        'titulo' => 'Diretório público — página no Google que traz cliente',
        'palavras_chave' => 'diretório, página pública, google, seo, divulgação',
        'conteudo' => "Toda assistência cadastrada no FixaOS ganha uma página pública própria, com SEO otimizado, "
            . "mapa, avaliações verificadas de clientes e botão de contato direto — encontrável no Google. Clientes "
            . "filtram por CEP, cidade ou raio de distância até achar a assistência mais perto. É gratuito e "
            . "automático, só precisa ativar nas configurações.",
    ],
    [
        'slug' => 'destaque-no-diretorio-preco',
        'categoria' => 'Comercial',
        'titulo' => 'Destaque e anúncios no diretório',
        'palavras_chave' => 'destaque, banner, anúncio no diretório, aparecer primeiro',
        'conteudo' => "É possível contratar um plano de destaque, a partir de R$49,90/mês, pra aparecer no topo do "
            . "diretório com um badge exclusivo, ou comprar um slot de banner pra exibir a marca na faixa de "
            . "anúncios. Tudo gerenciado pelo próprio sistema.",
    ],
    [
        'slug' => 'marketplace-de-pecas',
        'categoria' => 'Comercial',
        'titulo' => 'Marketplace de peças entre assistências',
        'palavras_chave' => 'marketplace, comprar peça, vender peça, estoque parado',
        'conteudo' => "O marketplace conecta assistências técnicas de todo o Brasil direto pelo sistema, sem "
            . "intermediários: encontre peças raras que outras assistências têm em estoque, ou anuncie peças "
            . "paradas suas em poucos minutos. Todo cadastro novo recebe 10 créditos grátis de boas-vindas pra "
            . "começar a anunciar sem gastar nada. O contato com o vendedor é direto pelo WhatsApp.",
    ],
    [
        'slug' => 'forum-tecnico',
        'categoria' => 'Comercial',
        'titulo' => 'Fórum técnico — comunidade de técnicos',
        'palavras_chave' => 'fórum, comunidade, dicas de reparo, defeitos',
        'conteudo' => "Espaço colaborativo onde técnicos de eletrônica compartilham defeitos, soluções e dicas de "
            . "reparo, organizado por categoria de equipamento. É público e indexado pelo Google, então as "
            . "contribuições ajudam outros técnicos a encontrar a solução.",
    ],
    [
        'slug' => 'como-comecar-passo-a-passo',
        'categoria' => 'Comercial',
        'titulo' => 'Como começar a usar o FixaOS',
        'palavras_chave' => 'como funciona, primeiros passos, começar, configurar',
        'conteudo' => "Pronto pra usar em menos de 5 minutos, sem instalação e sem técnico de TI: 1) crie a conta "
            . "grátis (e-mail ou Google), 2) adicione técnicos e personalize os status de OS, 3) abra a primeira "
            . "OS com cliente e equipamento, 4) acompanhe tudo no dashboard, 5) o cliente acompanha o andamento "
            . "pelo link enviado no WhatsApp, sem precisar de login.",
    ],
    [
        'slug' => 'migracao-de-outro-sistema',
        'categoria' => 'Comercial',
        'titulo' => 'Migração de dados de outro sistema',
        'palavras_chave' => 'migração, importar dados, trocar de sistema, planilha, outro sistema',
        'conteudo' => "O FixaOS tem uma ferramenta de migração de clientes vinda de outro sistema, planilha ou "
            . "caderno — a equipe analisa a base, traz o cadastro de clientes sem precisar redigitar, e você "
            . "confere tudo num ambiente de teste antes de valer. É um serviço opcional, com valor sob medida pro "
            . "tamanho da base, combinado antes sem surpresa. Quem tiver interesse deve ser encaminhado ao "
            . "atendimento humano pra avaliação sem compromisso.",
    ],
    [
        'slug' => 'seguranca-e-backup-dos-dados',
        'categoria' => 'Comercial',
        'titulo' => 'Segurança e backup dos dados',
        'palavras_chave' => 'segurança, backup, dados seguros, proteção',
        'conteudo' => "Cada empresa tem os dados completamente isolados dos de outras assistências. O sistema faz "
            . "backups automáticos diários, e o acesso exige autenticação por e-mail e senha, com sessão única por "
            . "login (a mesma conta não pode estar aberta em dois lugares ao mesmo tempo).",
    ],
    [
        'slug' => 'suporte-ao-cliente-canal-horario',
        'categoria' => 'Comercial',
        'titulo' => 'Como funciona o suporte do FixaOS',
        'palavras_chave' => 'suporte, atendimento, ajuda, contato humano',
        'conteudo' => "O suporte é por WhatsApp e e-mail, com atendimento humano — sem ticket sem resposta. O "
            . "compromisso é responder em até 4 horas úteis.",
    ],
    [
        'slug' => 'apos-teste-gratis-o-que-acontece',
        'categoria' => 'Comercial',
        'titulo' => 'O que acontece depois dos 15 dias de teste grátis',
        'palavras_chave' => 'depois do teste, acabou o trial, expirou, cobrança automática',
        'conteudo' => "Ao final dos 15 dias grátis, você escolhe continuar assinando um plano ou cancelar — não há "
            . "cobrança automática nem pegadinha. Se decidir sair, os dados cadastrados ficam disponíveis por mais "
            . "30 dias, caso queira retomar.",
    ],
    [
        'slug' => 'usuarios-por-plano',
        'categoria' => 'Comercial',
        'titulo' => 'Quantos usuários cada plano permite',
        'palavras_chave' => 'quantos usuários, limite de usuários, equipe, funcionários',
        'conteudo' => "O plano Autônomo inclui 1 usuário, o Oficina inclui 3, e o Top Empresa é ilimitado. Cada "
            . "pessoa da equipe tem o próprio login (não dá pra dividir uma conta entre várias pessoas ao mesmo "
            . "tempo). Dá pra fazer upgrade de plano a qualquer momento se precisar de mais gente.",
    ],

    // ── Suporte / funcional (manual) ──────────────────────────────────────
    [
        'slug' => 'abrir-e-acompanhar-uma-os',
        'categoria' => 'Suporte',
        'titulo' => 'Como abrir e acompanhar uma Ordem de Serviço',
        'palavras_chave' => 'abrir os, nova os, criar ordem de serviço',
        'conteudo' => "Em OS → Nova OS, o formulário tem 4 abas: Cliente (busca ou cadastra na hora), Equipamento "
            . "(tipo, marca, modelo, número de série, acessórios), Defeito relatado, e Configurações (técnico, "
            . "prioridade, valor estimado, prazo). Ao salvar, o número da OS é gerado automaticamente.",
    ],
    [
        'slug' => 'fechar-uma-os-pagamento',
        'categoria' => 'Suporte',
        'titulo' => 'Como fechar uma OS e registrar o pagamento',
        'palavras_chave' => 'fechar os, finalizar atendimento, pagamento da os',
        'conteudo' => "Mude o status da OS pra um do tipo Concluída, clique em \"Fechar OS\", confirme valor e forma "
            . "de pagamento (dinheiro, Pix, cartão, transferência ou boleto — dá pra dividir entre várias formas na "
            . "mesma OS). O sistema gera o lançamento financeiro automaticamente. Em pagamento com cartão, informe "
            . "a taxa da maquininha — ela é lançada sozinha como despesa já paga.",
    ],
    [
        'slug' => 'garantia-reabrir-os',
        'categoria' => 'Suporte',
        'titulo' => 'Garantia, retorno e reabertura de OS',
        'palavras_chave' => 'garantia, retorno, reabrir os, os de garantia',
        'conteudo' => "Se o equipamento retorna dentro do prazo de garantia, abra a OS original e clique em "
            . "\"Abrir OS de Garantia\" pra criar uma nova OS vinculada. Se uma OS foi fechada por engano, use o "
            . "botão \"Reabrir OS\" (na própria OS ou pela lista de OS, buscando por número/cliente/equipamento) — "
            . "ela volta pro status anterior ao fechamento.",
    ],
    [
        'slug' => 'busca-global-como-usar',
        'categoria' => 'Suporte',
        'titulo' => 'Como usar a busca global do sistema',
        'palavras_chave' => 'busca, pesquisar os, achar cliente, procurar produto',
        'conteudo' => "O campo de busca no topo do sistema procura em Ordens de Serviço, Clientes e Produtos ao "
            . "mesmo tempo, de qualquer tela. Digite pelo menos 2 letras (número da OS, nome/telefone/CPF do "
            . "cliente, ou nome/código do produto) e os resultados aparecem agrupados por categoria.",
    ],
    [
        'slug' => 'pdv-como-usar',
        'categoria' => 'Suporte',
        'titulo' => 'Como usar o PDV (frente de caixa)',
        'palavras_chave' => 'usar o pdv, vender peça avulsa, venda rápida',
        'conteudo' => "No botão \"PDV / Frente de Caixa\" da sidebar: adicione os itens da venda (produto do "
            . "estoque ou descrição livre), selecione o cliente se quiser, escolha a forma de pagamento (pode "
            . "dividir entre várias) e clique em Finalizar Venda — o estoque é baixado e o lançamento de receita "
            . "entra no Financeiro automaticamente.",
    ],
    [
        'slug' => 'comissao-tecnico-como-lancar',
        'categoria' => 'Suporte',
        'titulo' => 'Como lançar e configurar comissão de técnico',
        'palavras_chave' => 'comissão, técnico, percentual, quanto o técnico recebe',
        'conteudo' => "Em Financeiro → Comissões, clique em \"Nova Comissão\", escolha o técnico e, se quiser, "
            . "vincule a uma OS específica clicando em \"Puxar da OS\" pra somar os serviços automaticamente. O "
            . "percentual de cada técnico é configurado em Configurações do Sistema → Técnicos (ou usa o percentual "
            . "padrão da empresa). Ao marcar como paga, vira despesa no Financeiro sozinha.",
    ],
    [
        'slug' => 'limite-usuarios-sessao-unica',
        'categoria' => 'Suporte',
        'titulo' => 'Fui desconectado sozinho — sessão única por login',
        'palavras_chave' => 'desconectado, deslogado, sessão encerrada, login em outro lugar',
        'conteudo' => "Cada conta só pode estar logada em um dispositivo/navegador por vez — em qualquer plano. Se "
            . "a mesma conta logar em outro lugar, a sessão anterior é derrubada automaticamente com o aviso de "
            . "acesso em outro dispositivo. Se isso aconteceu sem motivo aparente, é provável que alguém (ou você "
            . "mesmo, em outro aparelho) tenha feito login com essa conta nesse meio tempo.",
    ],
    [
        'slug' => 'marketplace-anuncios-creditos-pedidos',
        'categoria' => 'Suporte',
        'titulo' => 'Como anunciar, usar créditos e pedir peças no marketplace',
        'palavras_chave' => 'anunciar peça, créditos do marketplace, pedido de peça',
        'conteudo' => "Em Marketplace → Meus Anúncios → Novo Anúncio, preencha título, equipamento, preço e "
            . "descrição, envie até 5 fotos e publique — cada cadastro novo já vem com 10 créditos grátis. Se não "
            . "achar a peça que precisa, publique um \"Pedido de Peça\" descrevendo o que procura, e outras "
            . "assistências podem responder com ofertas.",
    ],
    [
        'slug' => 'perfis-de-usuario-permissoes',
        'categoria' => 'Suporte',
        'titulo' => 'Perfis de usuário e permissões',
        'palavras_chave' => 'perfil, permissão, admin, gerente, técnico, recepcionista',
        'conteudo' => "Existem 4 perfis: Admin (acesso total, incluindo configurações e financeiro), Gerente (OS, "
            . "clientes, estoque e relatórios, sem configurações avançadas), Técnico (OS atribuídas a ele e "
            . "estoque) e Recepcionista (abertura de OS, cadastro de clientes e agenda). O número de usuários que "
            . "dá pra cadastrar depende do plano contratado.",
    ],
    [
        'slug' => 'personalizar-status-de-os',
        'categoria' => 'Suporte',
        'titulo' => 'Como personalizar os status de OS',
        'palavras_chave' => 'status de os, criar status, workflow personalizado',
        'conteudo' => "Em Configurações → Status de OS dá pra criar, editar, reordenar e excluir status conforme o "
            . "fluxo da sua oficina. Cada status tem um \"tipo\" que define seu comportamento (ex.: só status do "
            . "tipo Concluída mostram o botão Fechar OS). O status Garantia e qualquer status chamado Descartado "
            . "são protegidos contra edição/exclusão. Evite excluir status que já têm OS vinculadas.",
    ],
];

$check = $pdo->prepare("SELECT COUNT(*) FROM kb_artigos WHERE slug = ?");
$insert = $pdo->prepare("INSERT INTO kb_artigos (slug, categoria, titulo, palavras_chave, conteudo, ativo) VALUES (?,?,?,?,?,1)");

$criados = 0;
$pulados = 0;
foreach ($artigos as $a) {
    $check->execute([$a['slug']]);
    if ((int) $check->fetchColumn() > 0) { $pulados++; continue; }
    $insert->execute([$a['slug'], $a['categoria'], $a['titulo'], $a['palavras_chave'], $a['conteudo']]);
    $criados++;
}

echo "KB COMPLETO OK: {$criados} artigo(s) criado(s), {$pulados} já existiam (pulados).\n";
