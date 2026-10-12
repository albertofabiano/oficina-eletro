/**
 * Lógica pura (sem DOM) do modal "Equipamento" de duas abas (os/form.php) — trava da aba
 * "Acessórios e estado", texto dinâmico do que falta, exclusividade do "Sem acessórios" e
 * deduplicação de tipo/acessório por nome (minúsculo + sem acento). Extraído pra um módulo
 * próprio só porque precisa ser testável via Node sem precisar simular DOM — o resto do
 * cabeamento (eventos, render) continua inline em os/form.php, como todo o resto da tela.
 *
 * Funciona nos dois mundos: no navegador vira `window.EquipModalAbas`; no Node (testes),
 * `module.exports`.
 */
(function (root, factory) {
  if (typeof module !== 'undefined' && module.exports) {
    module.exports = factory();
  } else {
    root.EquipModalAbas = factory();
  }
})(typeof self !== 'undefined' ? self : this, function () {
  'use strict';

  var MAPA_ACENTOS = {
    'á': 'a', 'à': 'a', 'ã': 'a', 'â': 'a', 'ä': 'a',
    'é': 'e', 'è': 'e', 'ê': 'e', 'ë': 'e',
    'í': 'i', 'ì': 'i', 'î': 'i', 'ï': 'i',
    'ó': 'o', 'ò': 'o', 'ô': 'o', 'õ': 'o', 'ö': 'o',
    'ú': 'u', 'ù': 'u', 'û': 'u', 'ü': 'u',
    'ç': 'c', 'ñ': 'n'
  };
  var REGEX_ACENTOS = /[áàãâäéèêëíìîïóòôõöúùûüçñ]/g;

  /** Mesmo mapa de remover_acentos() (app/Helpers/functions.php), só que já assumindo
   *  minúsculo — normalizar() sempre faz toLowerCase() antes de tirar o acento. */
  function removerAcentos(s) {
    return String(s || '').replace(REGEX_ACENTOS, function (c) { return MAPA_ACENTOS[c] || c; });
  }

  /** Chave de comparação: sem espaço nas pontas, minúsculo, sem acento. */
  function normalizar(s) {
    return removerAcentos(String(s || '').trim().toLowerCase());
  }

  var ORDEM_CAMPOS = ['tipo', 'marca', 'modelo'];
  var ROTULOS_CAMPOS = { tipo: 'o tipo', marca: 'a marca', modelo: 'o modelo' };

  /**
   * Monta "Preencha {lista} para liberar a próxima aba" em português natural a partir de uma
   * lista de chaves faltando (subconjunto de ORDEM_CAMPOS, nessa ordem).
   * 1 item: "Preencha o modelo..."; 2: "...a marca e o modelo..."; 3: "...o tipo, a marca e o
   * modelo...".
   */
  function montarTextoFaltando(faltando) {
    if (!faltando || !faltando.length) return null;
    var partes = faltando.map(function (chave) { return ROTULOS_CAMPOS[chave] || chave; });
    var lista;
    if (partes.length === 1) {
      lista = partes[0];
    } else {
      lista = partes.slice(0, -1).join(', ') + ' e ' + partes[partes.length - 1];
    }
    return 'Preencha ' + lista + ' para liberar a próxima aba';
  }

  /**
   * Estado da aba 1 (Equipamento) a partir dos 3 campos obrigatórios — nunca lê DOM, recebe os
   * valores já extraídos pelo chamador. `modelo` conta depois de trim() (espaço em branco não
   * preenche).
   */
  function calcularGateAba1(valores) {
    valores = valores || {};
    var val = {
      tipo: String(valores.tipo || '').trim(),
      marca: String(valores.marca || '').trim(),
      modelo: String(valores.modelo || '').trim()
    };
    var faltando = ORDEM_CAMPOS.filter(function (chave) { return !val[chave]; });
    return {
      completo: faltando.length === 0,
      faltando: faltando,
      textoFaltando: montarTextoFaltando(faltando)
    };
  }

  /** "Sem acessórios" é o chip nativo — nunca um item de catálogo — comparado sem
   *  diferenciar maiúsculas/minúsculas/acento (cobre "SEM ACESSORIOS" sem acento também). */
  function ehSemAcessorios(nome) {
    return normalizar(nome) === 'sem acessorios';
  }

  /**
   * Marcar/desmarcar um acessório real. Mesma regra de sempre: selecionar um item desliga
   * "Sem acessórios" (são mutuamente exclusivos); desmarcar não reativa "Sem acessórios"
   * sozinho (só o botão próprio liga/desliga essa flag).
   */
  function alternarAcessorio(estado, id) {
    estado = estado || { selecionados: [], semAtivo: false };
    var selecionados = (estado.selecionados || []).slice();
    var idx = selecionados.indexOf(id);
    var semAtivo = !!estado.semAtivo;
    if (idx === -1) {
      selecionados.push(id);
      semAtivo = false;
    } else {
      selecionados.splice(idx, 1);
    }
    return { selecionados: selecionados, semAtivo: semAtivo };
  }

  /** Marcar "Sem acessórios" zera a seleção real; desmarcar só desliga a flag (não reinventa
   *  uma seleção anterior). */
  function alternarSemAcessorios(estado) {
    estado = estado || { selecionados: [], semAtivo: false };
    var semAtivo = !estado.semAtivo;
    var selecionados = semAtivo ? [] : (estado.selecionados || []).slice();
    return { selecionados: selecionados, semAtivo: semAtivo };
  }

  /**
   * Primeira ocorrência de cada nome normalizado (minúsculo, sem acento) — nunca apaga nada do
   * servidor, só decide o que a tela desenha. `obterNome` default assume item.nome.
   */
  function deduplicarPorNome(lista, obterNome) {
    obterNome = obterNome || function (item) { return item && item.nome; };
    var vistos = {};
    var resultado = [];
    (lista || []).forEach(function (item) {
      var chave = normalizar(obterNome(item));
      if (chave === '' || vistos[chave]) return;
      vistos[chave] = true;
      resultado.push(item);
    });
    return resultado;
  }

  /**
   * Tipos customizados (catálogo da empresa) que ainda não têm um chip fixo equivalente —
   * cobre o bug de "TV"/"Notebook" aparecendo duas vezes quando o catálogo da empresa já tem
   * uma linha com o mesmo nome (gravada por garantirTipoEquip() no servidor toda vez que o
   * chip fixo é usado). Também deduplica entre si os próprios itens do catálogo.
   */
  function filtrarTiposCustomizados(tiposCustom, rotulosFixos, limite) {
    var fixosNorm = (rotulosFixos || []).map(normalizar);
    var vistos = {};
    var resultado = [];
    (tiposCustom || []).forEach(function (t) {
      var chave = normalizar(t && t.nome);
      if (chave === '' || vistos[chave] || fixosNorm.indexOf(chave) !== -1) return;
      vistos[chave] = true;
      resultado.push(t);
    });
    return typeof limite === 'number' ? resultado.slice(0, limite) : resultado;
  }

  return {
    removerAcentos: removerAcentos,
    normalizar: normalizar,
    montarTextoFaltando: montarTextoFaltando,
    calcularGateAba1: calcularGateAba1,
    ehSemAcessorios: ehSemAcessorios,
    alternarAcessorio: alternarAcessorio,
    alternarSemAcessorios: alternarSemAcessorios,
    deduplicarPorNome: deduplicarPorNome,
    filtrarTiposCustomizados: filtrarTiposCustomizados
  };
});
