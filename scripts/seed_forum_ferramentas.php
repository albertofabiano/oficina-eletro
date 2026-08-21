<?php
/**
 * Popula a categoria "Ferramentas e Equipamentos" do Fórum público (hoje vazia — só as
 * categorias importadas de outra fonte, "Dicas de Defeito"/"Firmware e Atualizações", tinham
 * conteúdo) com tópicos reais sobre as principais ferramentas de uma assistência técnica de
 * eletrônicos: estação de solda, ar quente, multímetro, fonte de bancada, kit de chaves,
 * lupa/microscópio, separadora de tela, lavadora ultrassônica, ESD, dessoldadora, testador de
 * bateria, retrabalho BGA, ferramentas de abertura e osciloscópio.
 *
 * Por padrão roda em modo SIMULAÇÃO (não grava nada, só mostra o que faria). Pra gravar:
 *   php scripts/seed_forum_ferramentas.php --aplicar
 *
 * Opções:
 *   --empresa=ID   força o id da empresa autora dos tópicos (padrão: busca por
 *                  nome_fantasia/razao_social LIKE '%FixaOS%', mesmo critério de
 *                  scripts/seed_dados_demo.php)
 *
 * Pra apagar depois (ajuste {IDS} pelos ids impressos no resumo final):
 *   DELETE FROM forum_topicos WHERE id IN ({IDS});
 */

define('BASE_PATH', dirname(__DIR__));
spl_autoload_register(function (string $class) {
    $path = BASE_PATH . '/' . str_replace(['App\\', '\\'], ['app/', '/'], $class) . '.php';
    if (file_exists($path)) require $path;
});

$aplicar = in_array('--aplicar', $argv, true);

$argOpt = function (string $nome, $default) use ($argv) {
    foreach ($argv as $a) {
        if (str_starts_with($a, "--{$nome}=")) return substr($a, strlen($nome) + 3);
    }
    return $default;
};
$empresaArg = $argOpt('empresa', null);

$db = App\Core\DB::pdo();

echo ($aplicar ? "MODO APLICAR — vai gravar de verdade no banco.\n" : "MODO SIMULAÇÃO — nada será gravado (rode com --aplicar pra gravar de verdade).\n");
echo str_repeat('-', 78) . "\n";

// ---------------------------------------------------------------------------------------
// Resolve empresa + autor (mesmo critério de scripts/seed_dados_demo.php)
// ---------------------------------------------------------------------------------------

if ($empresaArg !== null) {
    $stmt = $db->prepare("SELECT id, nome_fantasia, razao_social FROM empresas WHERE id = ?");
    $stmt->execute([(int) $empresaArg]);
    $empresa = $stmt->fetch();
    if (!$empresa) { fwrite(STDERR, "Empresa #{$empresaArg} não encontrada.\n"); exit(1); }
} else {
    $stmt = $db->query(
        "SELECT id, nome_fantasia, razao_social FROM empresas
         WHERE nome_fantasia LIKE '%FixaOS%' OR razao_social LIKE '%FixaOS%'"
    );
    $candidatos = $stmt->fetchAll();
    if (count($candidatos) === 0) {
        fwrite(STDERR, "Nenhuma empresa com nome contendo 'FixaOS' encontrada. Use --empresa=ID.\n");
        exit(1);
    }
    if (count($candidatos) > 1) {
        fwrite(STDERR, "Mais de uma empresa bateu com 'FixaOS' — escolha uma com --empresa=ID:\n");
        foreach ($candidatos as $c) fwrite(STDERR, "  #{$c['id']} — {$c['nome_fantasia']} ({$c['razao_social']})\n");
        exit(1);
    }
    $empresa = $candidatos[0];
}
$eid = (int) $empresa['id'];
echo "Empresa autora: #{$eid} — {$empresa['nome_fantasia']} ({$empresa['razao_social']})\n";

$stmtU = $db->prepare(
    "SELECT id, nome FROM usuarios WHERE empresa_id = ? AND ativo = 1
     ORDER BY (perfil IN ('tecnico','admin')) DESC, id LIMIT 1"
);
$stmtU->execute([$eid]);
$usuario = $stmtU->fetch();
if (!$usuario) { fwrite(STDERR, "Empresa #{$eid} não tem usuário ativo — impossível atribuir autoria.\n"); exit(1); }
$uid = (int) $usuario['id'];
echo "Autor dos tópicos: #{$uid} — {$usuario['nome']}\n";

$stmtC = $db->prepare("SELECT id FROM forum_categorias WHERE nome = 'Ferramentas e Equipamentos' AND ativo = 1");
$stmtC->execute();
$catId = (int) $stmtC->fetchColumn();
if (!$catId) { fwrite(STDERR, "Categoria 'Ferramentas e Equipamentos' não encontrada ou inativa.\n"); exit(1); }
echo "Categoria: #{$catId} — Ferramentas e Equipamentos\n";
echo str_repeat('-', 78) . "\n";

// ---------------------------------------------------------------------------------------
// Tópicos
// ---------------------------------------------------------------------------------------

$topicos = [
    [
        'titulo' => 'Estação de solda: o que considerar antes de comprar',
        'conteudo' => "Ferro de solda avulso resolve no início, mas quem já queimou ponta demais ou perdeu tempo esperando a temperatura estabilizar sabe que a estação de solda de bancada compensa rápido.\n\nO que olhar de verdade:\n- Controle de temperatura real (com display), não só um botão de potência — solda em componente SMD pequeno exige precisão, senão descola trilha.\n- Tempo de aquecimento e recuperação de temperatura (volta a subir rápido depois de encostar num ponto grande de solda, tipo terra de placa de fonte).\n- Disponibilidade de pontas — de nada adianta uma estação boa se a ponta que você precisa não é fácil de achar reposição.\n- Aterramento da ponta (ESD) — sem isso, risco real de fritar componente sensível.\n\nFaixa de entrada (estações tipo 936/852D+ genéricas) já resolve 90% do dia a dia. Quem trabalha com placa de nível mais fino (notebook, board level) sente diferença migrando pra estações com controle por estação de solda dedicada tipo T12/T210, que aquecem muito mais rápido.",
    ],
    [
        'titulo' => 'Ar quente (hot air): guia rápido de temperatura e vazão por tipo de componente',
        'conteudo' => "Temperatura errada no ar quente é a causa mais comum de placa queimada/empenada por erro de operador, não de defeito real. Um ponto de partida que funciona bem na prática (ajustar conforme a estação e a distância do bico):\n\n- Componentes pequenos (resistor, capacitor SMD, transistor): ~300-330°C, vazão baixa.\n- CI comum (flash, PMIC pequeno): ~340-360°C, vazão média.\n- Componentes grandes / com muito contato térmico com a placa (blindagem, conector USB-C reforçado): 370-400°C, vazão média-alta, com pré-aquecimento por baixo se possível.\n- BGA (chip de banda base, memória): depende muito do tamanho — geralmente 380-420°C, vazão média, sempre com bico direcionador pra não jogar ar quente em componente vizinho.\n\nDica que evita boa parte dos problemas: nunca mirar o ar quente direto e fixo num ponto só — fazer movimento circular/vai-e-vem, e usar fita kapton ou protetor térmico nos componentes vizinhos quando for retrabalhar algo perto de plástico (conector, flex).",
    ],
    [
        'titulo' => 'Multímetro: qual comprar e o que não pode faltar',
        'conteudo' => "Multímetro é a ferramenta que mais rápido paga o próprio preço — praticamente todo diagnóstico começa com uma medição de continuidade, tensão ou resistência.\n\nO que realmente importa na hora de escolher:\n- Modo de continuidade com beep rápido (não dá pra ficar olhando o display toda hora enquanto testa trilha por trilha).\n- Boa resolução em baixa resistência (curto de placa costuma aparecer como poucos ohms — multímetro ruim mostra tudo como '0' e não ajuda a diferenciar).\n- Fusível de proteção interno de verdade (multímetro sem fusível bom vira sucata no primeiro engano de escala).\n- Pontas de prova finas o bastante pra medir em SMD sem encostar em dois pontos ao mesmo tempo.\n\nPra quem faz diagnóstico de placa com frequência, vale considerar também um multímetro com modo mA de baixa faixa e boa velocidade de amostragem — ajuda bastante quando o teste é medir consumo de corrente da placa ligada (indicador clássico de curto ativo).",
    ],
    [
        'titulo' => 'Fonte de bancada ajustável: por que é item obrigatório na oficina',
        'conteudo' => "Se tem uma ferramenta que separa quem só troca peça de quem realmente diagnostica placa, é a fonte de bancada. Ligar o aparelho direto na tomada/bateria sem saber quanto ele está puxando é trabalhar no escuro.\n\nPra que serve na prática:\n- Ligar a placa sem bateria e ver o consumo em tempo real — corrente que sobe muito rápido e trava geralmente indica curto; consumo zero pode indicar proteção atuando ou trilha rompida.\n- Substituir a bateria temporariamente pra testar sem risco (bateria estufada perto de retrabalho a quente é combinação perigosa).\n- Alimentar a placa com tensão controlada e ir subindo aos poucos, observando em que ponto o curto 'acorda' — técnica clássica pra achar componente em curto por aquecimento.\n\nProcure uma com proteção de corrente ajustável de verdade (não só um limite fixo) e boa resolução no display — 2 casas decimais de corrente já ajuda muito a perceber diferença entre 'consumo normal de standby' e 'início de curto'.",
    ],
    [
        'titulo' => 'Kit de chaves de precisão para celular: o que realmente vale a pena',
        'conteudo' => "Kit de chave barato de camelô costuma arredondar o parafuso Pentalobe/Tri-point na segunda ou terceira abertura — e parafuso arredondado em aparelho de cliente é dor de cabeça que ninguém quer.\n\nO que olhar:\n- Aço de qualidade nas pontas (as boas mantêm o fio da chave por muito mais tempo).\n- Conjunto com as bitolas específicas de celular: Pentalobe P2/P5, Tri-point Y000, Phillips #000/#00, e as chavetas de plástico/spudger pra não arranhar carcaça.\n- Ventosa boa (não a de brinde) — puxar tela sem ventosa de sucção real é caminho certo pra rachar o vidro.\n- Cartão/palheta plástica fina pra soltar adesivo sem forçar a carcaça.\n\nInvestir uma vez num kit de marca conhecida (iFixit, Jakemy, Xiaomi/Qianli linha profissional) sai mais barato no fim das contas do que trocar chave arredondada toda semana — e evita o prejuízo bem maior de estragar o aparelho do cliente.",
    ],
    [
        'titulo' => 'Lupa/microscópio digital: quando compensa investir',
        'conteudo' => "Quem só troca tela e bateria sobrevive sem microscópio. Quem entra em nível de placa (retrabalho de CI, ponte de solda invisível a olho nu, trilha rompida) não vai longe sem um.\n\nPontos pra decidir:\n- Lupa de bancada com LED (aro de luz) já ajuda bastante e é a opção mais barata — boa pra inspeção geral e solda de componente médio.\n- Microscópio digital USB (conectado no PC/monitor) é o meio-termo: dá aumento maior, permite gravar/tirar print pra documentar o defeito, e não cansa a vista como lupa óptica em uso prolongado.\n- Microscópio estereoscópio óptico tradicional continua sendo o padrão-ouro pra quem faz microssolda BGA fina — profundidade de campo e ausência de delay (diferente do digital, que sempre tem uma latência mínima de imagem) fazem diferença na hora de posicionar componente minúsculo.\n\nPra quem está começando: um microscópio digital USB de faixa intermediária, com zoom óptico real (não digital) e boa iluminação regulável, já resolve praticamente todo caso do dia a dia sem o investimento alto de um estereoscópio profissional.",
    ],
    [
        'titulo' => 'Máquina separadora de tela (LCD/touch): comprar ou fazer na unha?',
        'conteudo' => "Separar touch de LCD manualmente (fio de nylon + calor de soprador/chapa) funciona, mas é fácil rachar o vidro ou deixar bolha na recolagem se a técnica não for boa — e cada tela rachada por erro de abertura é prejuízo direto.\n\nA separadora a vácuo com aquecimento (tipo as de bancada com placa quente + sucção) resolve dois problemas de uma vez: amolece o adesivo de forma uniforme (sem ponto de calor concentrado que mancha o LCD) e puxa o vidro com sucção controlada, sem o risco de rachar que o método manual tem.\n\nVale o investimento pra quem faz troca de vidro/touch com regularidade — o retrabalho de tela rachada durante a separação manual (ou o tempo perdido fazendo com calor e fio) geralmente paga a máquina em poucos meses. Pra quem faz esporadicamente, o kit manual (chapa de aquecimento + fio + ventosas) ainda é uma opção razoável, só exige mais prática.",
    ],
    [
        'titulo' => 'Lavadora ultrassônica: útil ou só modismo?',
        'conteudo' => "Não é modismo — pra quem mexe com placa que já teve contato com líquido (o clássico 'caiu na água'), a lavadora ultrassônica com álcool isopropílico é praticamente indispensável. A cavitação do ultrassom remove resíduo/oxidação de dentro de conector e sob componente BGA de um jeito que escovinha manual não alcança.\n\nCuidados que fazem diferença:\n- Usar álcool isopropílico de alta pureza (99%+), nunca água nem álcool comum — resíduo de álcool ruim deixa mancha e não evapora limpo.\n- Não colocar a placa com a bateria conectada.\n- Secar bem (ar comprimido + estufa/calor baixo) antes de religar — umidade residual em componente é curto na certa.\n- Tempo de ciclo curto (poucos minutos) é suficiente — exagerar no tempo não limpa mais, só desgasta.\n\nModelo de bancada pequeno (algo em torno de 2-3 litros) já atende bem o volume de uma assistência técnica de porte médio.",
    ],
    [
        'titulo' => 'Pulseira e tapete antiestático (ESD): protegendo a placa de dano invisível',
        'conteudo' => "Descarga eletrostática (ESD) é o tipo de dano que não aparece na hora — o componente sensível (CI, memória, controlador de carga) pode ser degradado por uma descarga que você nem percebeu, e falhar semanas depois, o que gera reclamação de 'voltou com defeito' sem ninguém entender o porquê.\n\nO básico que resolve a maior parte do risco:\n- Pulseira antiestática aterrada de verdade (fio até um ponto de terra real, não só decorativa).\n- Tapete de bancada condutivo, também aterrado, principalmente na hora de manusear placa fora do aparelho.\n- Evitar bancada com carpete/tecido sintético embaixo em dia seco — ambiente de baixa umidade aumenta muito o risco de estática.\n\nInvestimento baixo (um kit de pulseira + tapete custa pouco) pra evitar um problema que é praticamente impossível de diagnosticar depois que já aconteceu — decidir se um retorno de cliente foi ESD ou outra causa é quase adivinhação.",
    ],
    [
        'titulo' => 'Dessoldadora a vácuo x trança de dessolda: quando usar cada uma',
        'conteudo' => "As duas fazem o mesmo trabalho básico (tirar solda de um ponto), mas em situações diferentes:\n\n- Trança de dessolda: ótima pra limpar excesso de solda de uma trilha, preparar um pad antes de soldar componente novo, ou remover solda de poucos pontos. Barata, não exige equipamento extra além do ferro de solda.\n- Dessoldadora a vácuo (manual tipo 'sugador' ou elétrica de bancada): essencial pra remover componente com múltiplos pinos de furo passante (conector, botão, plugue de carga em placas mais antigas) sem forçar e sem estressar a trilha — a sucção limpa o furo de forma muito mais completa que a trança.\n\nNa prática, as duas se complementam: trança no dia a dia de retoque, dessoldadora a vácuo (de preferência elétrica, que mantém a temperatura e a sucção constantes) pra remoção de componente com pino em furo passante. Quem faz retrabalho de conector de carga com frequência sente MUITA diferença tendo uma dessoldadora elétrica boa.",
    ],
    [
        'titulo' => 'Testador de bateria de celular: identificando inchaço e degradação',
        'conteudo' => "Bateria estufada é risco de segurança, não só reclamação de 'não segura carga' — e nem sempre o inchaço é visível a olho nu antes de abrir o aparelho.\n\nO que ajuda no diagnóstico:\n- Testador de capacidade/impedância interna (mede mAh real e a resistência interna da bateria) — bateria degradada mostra impedância bem mais alta que o normal, mesmo sem inchaço visível ainda.\n- Paquímetro pra medir espessura da bateria contra a especificação original — inchaço inicial às vezes só aparece nessa comparação.\n- Multímetro pra conferir tensão de repouso — bateria muito abaixo da tensão mínima esperada (geralmente perto de 3V ou menos em Li-ion) pode já ter sofrido descarga profunda e virou risco.\n\nRegra prática: qualquer bateria com inchaço visível, cheiro estranho, ou aquecimento anormal durante carga deve ser substituída na hora, mesmo que o cliente não tenha reclamado — inclusive vale documentar isso no laudo pra deixar registrado que a troca foi por segurança.",
    ],
    [
        'titulo' => 'Estação de retrabalho BGA: vale a pena pra quem faz nível de placa',
        'conteudo' => "Reballing de chip (repor as esferas de solda de um BGA — memória, controlador de carga, banda base) é outro patamar de trabalho, e ar quente comum não dá conta com a mesma consistência de uma estação de retrabalho BGA dedicada.\n\nO que uma estação dedicada agrega:\n- Pré-aquecimento por baixo da placa (infravermelho ou resistivo), que reduz o choque térmico e evita empenar a placa — problema comum quando se tenta fazer reballing só com ar quente de cima.\n- Perfil de temperatura programável (rampa de subida, patamar, resfriamento controlado) — repetibilidade que ferramenta manual não oferece.\n- Bico/nozzle proporcional ao tamanho exato do chip, concentrando o calor só onde precisa.\n\nPra quem faz reparo simples (troca de tela, bateria, conector) isso é investimento desnecessário. Mas pra quem quer atender defeito de placa que a maioria da concorrência recusa (chip queimado, bola de solda rompida por queda) é praticamente o que define se você consegue prestar esse serviço ou não.",
    ],
    [
        'titulo' => 'Organização de bancada: ferramentas de abertura que evitam quebrar plástico',
        'conteudo' => "Boa parte da quebra de encaixe/clip plástico durante a abertura de um aparelho não é falta de cuidado — é usar a ferramenta errada pro ponto errado.\n\nO conjunto básico que evita a maioria dos acidentes:\n- Spudger de plástico (não metálico) pra desconectar flex e soltar clip sem risco de curto acidental.\n- Palheta/cartão fino pra deslizar por baixo de tela colada, aplicando pressão uniforme em vez de fazer alavanca num ponto só.\n- Pinça de ponta fina (reta e curva) pra manusear parafuso pequeno e flex sem amassar.\n- Organizador magnético/com compartimentos pra parafuso — perder ou trocar parafuso de posição errada é causa clássica de aparelho que 'não fecha direito' depois do reparo.\n\nInvestir num kit de ferramentas de abertura decente é barato comparado ao custo de reposição de uma carcaça ou tela trincada por um clipe forçado com chave de fenda comum.",
    ],
    [
        'titulo' => 'Osciloscópio na assistência técnica: luxo ou necessidade?',
        'conteudo' => "Pra reparo de nível componente (fonte chaveada, sinal de clock, comunicação I2C/SPI travada) o osciloscópio deixa de ser luxo e vira ferramenta de diagnóstico real — multímetro mostra tensão média, mas não mostra ruído, oscilação ou um sinal que devia estar chaveando e está travado.\n\nPra quem está decidindo se compensa:\n- Se o trabalho é majoritariamente troca de peça (tela, bateria, conector, câmera), osciloscópio não faz falta no dia a dia.\n- Se envolve diagnóstico de placa-mãe (fonte não liga, sinal de clock ausente, barramento de comunicação sem resposta), um osciloscópio USB ou de bancada de faixa de entrada (2 canais, banda de 50-100MHz já atende a maioria dos casos de eletrônica de consumo) já abre um leque grande de diagnóstico que hoje é feito 'no chute'.\n\nNão precisa ser o modelo mais caro — osciloscópios USB conectados no notebook, de faixa de entrada, já são suficientes pra maioria dos sinais digitais e de fonte chaveada que aparecem numa assistência técnica comum. O ganho não é só técnico: reduz e muito o tempo gasto tentando diagnosticar 'no escuro'.",
    ],
];

echo count($topicos) . " tópico(s) preparado(s) pra 'Ferramentas e Equipamentos'.\n";
echo str_repeat('-', 78) . "\n";

if (!$aplicar) {
    foreach ($topicos as $t) echo "  - {$t['titulo']}\n";
    echo "\nRode com --aplicar pra gravar de verdade.\n";
    exit(0);
}

$stmtIns = $db->prepare(
    "INSERT INTO forum_topicos (forum_categoria_id, empresa_id, usuario_id, titulo, conteudo, marca, modelo, versao_firmware, url_externa)
     VALUES (?, ?, ?, ?, ?, '', '', NULL, NULL)"
);

$ids = [];
foreach ($topicos as $t) {
    $stmtIns->execute([$catId, $eid, $uid, $t['titulo'], $t['conteudo']]);
    $ids[] = (int) $db->lastInsertId();
    echo "Criado ##{$ids[array_key_last($ids)]} — {$t['titulo']}\n";
}

echo str_repeat('-', 78) . "\n";
echo count($ids) . " tópico(s) gravado(s) com sucesso.\n";
echo "Pra desfazer: DELETE FROM forum_topicos WHERE id IN (" . implode(',', $ids) . ");\n";
