/**
 * Testes do módulo public/js/equip-modal-abas.js (lógica pura do modal "Equipamento" de duas
 * abas, ver os/form.php) — trava da aba "Acessórios e estado", texto dinâmico do que falta,
 * regra do "Sem acessórios" e deduplicação por nome (minúsculo + sem acento).
 *
 * Mesmo espírito dos tests/*_test.php do projeto: script simples, sem framework, assert()
 * próprio, exit 1 na primeira falha. Rodar com: node tests/equip_modal_abas_test.js
 */
const path = require('path');
const M = require(path.join(__dirname, '..', 'public', 'js', 'equip-modal-abas.js'));

let falhas = 0;

function assertIgual(atual, esperado, msg) {
  const a = JSON.stringify(atual);
  const e = JSON.stringify(esperado);
  if (a !== e) {
    falhas++;
    console.error(`FALHA: ${msg}\n  esperado: ${e}\n  obtido:   ${a}`);
  } else {
    console.log(`ok: ${msg}`);
  }
}

function assertVerdadeiro(cond, msg) {
  if (!cond) {
    falhas++;
    console.error(`FALHA: ${msg} (esperava verdadeiro)`);
  } else {
    console.log(`ok: ${msg}`);
  }
}

// ── Trava da aba 2 + texto dinâmico do que falta ──────────────────────────────

assertIgual(
  M.calcularGateAba1({}).completo,
  false,
  'sem nada preenchido, aba 2 continua travada'
);

assertIgual(
  M.calcularGateAba1({}).textoFaltando,
  'Preencha o tipo, a marca e o modelo para liberar a próxima aba',
  'texto dinâmico: falta tudo'
);

assertIgual(
  M.calcularGateAba1({ tipo: 'TV' }).textoFaltando,
  'Preencha a marca e o modelo para liberar a próxima aba',
  'texto dinâmico: falta marca e modelo'
);

assertIgual(
  M.calcularGateAba1({ tipo: 'TV', marca: 'LG' }).textoFaltando,
  'Preencha o modelo para liberar a próxima aba',
  'texto dinâmico: falta só o modelo'
);

assertIgual(
  M.calcularGateAba1({ tipo: 'TV', marca: 'LG', modelo: '  ' }).completo,
  false,
  'modelo só com espaço em branco não conta como preenchido'
);

assertIgual(
  M.calcularGateAba1({ tipo: 'TV', marca: 'LG', modelo: 'OLED55' }).completo,
  true,
  'tipo + marca + modelo preenchidos libera a aba 2'
);

assertIgual(
  M.calcularGateAba1({ tipo: 'TV', marca: 'LG', modelo: 'OLED55' }).textoFaltando,
  null,
  'sem nada faltando, não há texto de aviso'
);

assertIgual(
  M.calcularGateAba1({ marca: 'LG', modelo: 'OLED55' }).textoFaltando,
  'Preencha o tipo para liberar a próxima aba',
  'texto dinâmico: falta só o tipo (ordem sempre tipo/marca/modelo, não a ordem de preenchimento)'
);

// ── Regra do "Sem acessórios" ──────────────────────────────────────────────────

assertIgual(
  M.ehSemAcessorios('Sem acessórios'),
  true,
  'ehSemAcessorios reconhece a grafia padrão'
);
assertIgual(
  M.ehSemAcessorios('SEM ACESSORIOS'),
  true,
  'ehSemAcessorios reconhece maiúsculo sem acento (causa real do bug de duplicata)'
);
assertIgual(
  M.ehSemAcessorios('  Sem acessórios  '),
  true,
  'ehSemAcessorios ignora espaço nas pontas'
);
assertIgual(
  M.ehSemAcessorios('Cabo de força'),
  false,
  'ehSemAcessorios não confunde outro nome'
);

{
  let estado = { selecionados: [], semAtivo: false };
  estado = M.alternarAcessorio(estado, 1);
  assertIgual(estado, { selecionados: [1], semAtivo: false }, 'marcar um acessório real seleciona ele');

  estado = M.alternarSemAcessorios(estado);
  assertIgual(estado, { selecionados: [], semAtivo: true }, 'marcar "Sem acessórios" zera os reais selecionados');

  estado = M.alternarAcessorio(estado, 2);
  assertIgual(estado, { selecionados: [2], semAtivo: false }, 'marcar outro acessório real desliga "Sem acessórios"');

  estado = M.alternarAcessorio(estado, 2);
  assertIgual(estado, { selecionados: [], semAtivo: false }, 'desmarcar o último acessório real não reativa "Sem acessórios" sozinho');

  estado = M.alternarSemAcessorios(estado);
  estado = M.alternarSemAcessorios(estado);
  assertIgual(estado, { selecionados: [], semAtivo: false }, 'desmarcar "Sem acessórios" não inventa uma seleção anterior');
}

// ── Deduplicação (tipo customizado x chip fixo, acessório duplicado no catálogo) ──────────

assertIgual(
  M.filtrarTiposCustomizados(
    [{ id: 1, nome: 'TV' }, { id: 2, nome: 'Fonte de bancada' }, { id: 3, nome: 'notebook' }],
    ['TV', 'Celular', 'Notebook', 'Linha branca']
  ),
  [{ id: 2, nome: 'Fonte de bancada' }],
  'tipo customizado com o mesmo nome de um chip fixo (ignorando caixa) não aparece de novo'
);

assertIgual(
  M.filtrarTiposCustomizados(
    [{ id: 1, nome: 'Fonte de bancada' }, { id: 2, nome: 'fonte de bancada' }],
    ['TV', 'Celular', 'Notebook', 'Linha branca']
  ),
  [{ id: 1, nome: 'Fonte de bancada' }],
  'dois tipos customizados com o mesmo nome (caixa diferente) só aparecem uma vez'
);

assertIgual(
  M.deduplicarPorNome([
    { id: 1, nome: 'CABO DE FORÇA' },
    { id: 2, nome: 'cabo de forca' },
    { id: 3, nome: 'Fone de ouvido' }
  ]),
  [{ id: 1, nome: 'CABO DE FORÇA' }, { id: 3, nome: 'Fone de ouvido' }],
  '"CABO DE FORÇA" e "cabo de forca" (sem acento, caixa diferente) deduplicam pra 1 chip'
);

assertVerdadeiro(
  M.normalizar('AÇÃO') === M.normalizar('acao'),
  'normalizar() ignora acento e caixa'
);

if (falhas) {
  console.error(`\n${falhas} teste(s) falharam.`);
  process.exit(1);
}
console.log('\nTodos os testes passaram.');
