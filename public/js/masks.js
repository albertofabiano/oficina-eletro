/**
 * Máscaras e autopreenchimento de CEP — OficinaTech
 * Depende de IMask (carregado no layout)
 */

document.addEventListener('DOMContentLoaded', function () {

  // ─── CEP ──────────────────────────────────────────────────────────────────
  function aplicarMascaraCep(el) {
    IMask(el, { mask: '00000-000' });
    el.addEventListener('blur', buscarCep);
  }

  async function buscarCep() {
    const cep = this.value.replace(/\D/g, '');
    if (cep.length !== 8) return;

    // Localizar o formulário pai para preencher os campos corretos
    const form = this.closest('form') || document;

    const icone = this.parentElement.querySelector('.cep-spinner');
    if (icone) icone.style.display = 'inline-block';
    this.disabled = true;

    try {
      const r = await fetch(`https://viacep.com.br/ws/${cep}/json/`);
      const d = await r.json();
      if (d.erro) {
        mostrarAlertaCep(this, 'CEP não encontrado.');
        return;
      }
      preencherEndereco(form, d);
      mostrarAlertaCep(this, null);
    } catch (e) {
      mostrarAlertaCep(this, 'Erro ao buscar CEP. Preencha manualmente.');
    } finally {
      this.disabled = false;
      if (icone) icone.style.display = 'none';
    }
  }

  function preencherEndereco(form, d) {
    const campos = {
      logradouro : d.logradouro || '',
      bairro     : d.bairro     || '',
      cidade     : d.localidade || '',
      uf         : d.uf         || '',
      complemento: d.complemento|| '',
    };
    for (const [nome, valor] of Object.entries(campos)) {
      const el = form.querySelector(`[name="${nome}"]`);
      if (el && valor) {
        el.value = valor;
        // Foca no número após preencher
        if (nome === 'logradouro') {
          const numEl = form.querySelector('[name="numero"]');
          if (numEl) setTimeout(() => numEl.focus(), 50);
        }
        // Destaque visual
        el.classList.add('border-success');
        setTimeout(() => el.classList.remove('border-success'), 3000);
      }
    }
  }

  function mostrarAlertaCep(el, msg) {
    let aviso = el.parentElement.querySelector('.cep-aviso');
    if (!aviso) {
      aviso = document.createElement('div');
      aviso.className = 'cep-aviso form-text';
      el.parentElement.appendChild(aviso);
    }
    if (msg) {
      aviso.textContent = msg;
      aviso.className = 'cep-aviso form-text text-danger';
    } else {
      aviso.textContent = '✅ Endereço preenchido!';
      aviso.className = 'cep-aviso form-text text-success';
      setTimeout(() => { aviso.textContent = ''; }, 3000);
    }
  }

  // ─── TELEFONE ─────────────────────────────────────────────────────────────
  function aplicarMascaraTelefone(el) {
    const maskInstance = IMask(el, {
      mask: [
        { mask: '(00) 0000-0000' },
        { mask: '(00) 00000-0000' },
      ],
    });
    return maskInstance;
  }

  // ─── CPF / CNPJ dinâmico ─────────────────────────────────────────────────
  function aplicarMascaraCpfCnpj(el, tipoPessoaEl) {
    // Sem seletor de tipo: máscara única que alterna CPF/CNPJ pelo tamanho
    // (CPF até 11 dígitos, CNPJ a partir de 12). É o comportamento correto p/ campo "CPF ou CNPJ".
    if (!tipoPessoaEl) {
      IMask(el, {
        mask: [
          { mask: '000.000.000-00' },
          { mask: '00.000.000/0000-00' },
        ],
        dispatch: function (appended, dynamicMasked) {
          var digitos = (dynamicMasked.value + appended).replace(/\D/g, '');
          return dynamicMasked.compiledMasks[digitos.length > 11 ? 1 : 0];
        },
      });
      return;
    }
    // Com seletor PF/PJ: fixa a máscara conforme a escolha e troca ao mudar o tipo.
    let maskInstance;
    function atualizarMascara() {
      if (maskInstance) maskInstance.destroy();
      maskInstance = IMask(el, {
        mask: tipoPessoaEl.value === 'pj' ? '00.000.000/0000-00' : '000.000.000-00',
      });
    }
    atualizarMascara();
    tipoPessoaEl.addEventListener('change', atualizarMascara);
  }

  // ─── CNPJ fixo (somente PJ) ───────────────────────────────────────────────
  function aplicarMascaraCnpj(el) {
    IMask(el, { mask: '00.000.000/0000-00' });
  }

  // ─── DINHEIRO ─────────────────────────────────────────────────────────────
  function aplicarMascaraDinheiro(el) {
    IMask(el, {
      mask: Number,
      scale: 2,
      signed: false,
      thousandsSeparator: '.',
      padFractionalZeros: true,
      normalizeZeros: true,
      radix: ',',
      mapToRadix: ['.'],
      min: 0,
      max: 9999999,
    });
  }

  // ─── NÚMERO INTEIRO ───────────────────────────────────────────────────────
  function aplicarMascaraInteiro(el, min = 0, max = 9999) {
    IMask(el, { mask: Number, min, max, scale: 0 });
  }

  // ─── PLACA DE VEÍCULO (caso necessário) ───────────────────────────────────
  function aplicarMascaraPlaca(el) {
    IMask(el, {
      mask: [
        { mask: 'aaa-0000' },
        { mask: 'aaa-0a00' }, // Mercosul
      ],
      prepare: (str) => str.toUpperCase(),
    });
  }

  // ─── DATA BR ──────────────────────────────────────────────────────────────
  function aplicarMascaraData(el) {
    IMask(el, {
      mask: Date,
      pattern: 'd/`m/`Y',
      format: (date) => {
        const d = String(date.getDate()).padStart(2, '0');
        const m = String(date.getMonth() + 1).padStart(2, '0');
        return `${d}/${m}/${date.getFullYear()}`;
      },
      parse: (str) => {
        const [d, m, y] = str.split('/');
        return new Date(y, m - 1, d);
      },
      blocks: {
        d: { mask: IMask.MaskedRange, from: 1, to: 31, maxLength: 2 },
        m: { mask: IMask.MaskedRange, from: 1, to: 12, maxLength: 2 },
        Y: { mask: IMask.MaskedRange, from: 1900, to: 2099 },
      },
    });
  }

  // ─── NÚMERO DE SÉRIE / IMEI ───────────────────────────────────────────────
  function aplicarMascaraImei(el) {
    IMask(el, { mask: /^[\w\-\/]{0,30}$/ });
  }

  // ─── PORCENTAGEM ──────────────────────────────────────────────────────────
  function aplicarMascaraPercentual(el) {
    IMask(el, { mask: Number, min: 0, max: 100, scale: 2, radix: ',', mapToRadix: ['.'] });
  }

  // ═══════════════════════════════════════════════════════════════════════════
  // APLICAÇÃO AUTOMÁTICA — percorre todos os campos da página
  // ═══════════════════════════════════════════════════════════════════════════

  const tipoPessoaEl = document.getElementById('tipoPessoa');

  document.querySelectorAll('[name="cep"]').forEach(el => {
    // Adiciona spinner ao wrapper
    const wrap = document.createElement('div');
    wrap.className = 'position-relative';
    el.parentNode.insertBefore(wrap, el);
    wrap.appendChild(el);
    const sp = document.createElement('span');
    sp.className = 'cep-spinner spinner-border spinner-border-sm text-primary position-absolute';
    sp.style.cssText = 'right:10px;top:50%;transform:translateY(-50%);display:none';
    wrap.appendChild(sp);
    aplicarMascaraCep(el);
  });

  document.querySelectorAll('[name="telefone"],[name="whatsapp"],[name="celular"],[name="fone"]').forEach(el => {
    aplicarMascaraTelefone(el);
  });

  document.querySelectorAll('[name="cpf_cnpj"]').forEach(el => {
    aplicarMascaraCpfCnpj(el, tipoPessoaEl);
    if (window.validarDocumentoNoBlur) window.validarDocumentoNoBlur(el);
  });

  document.querySelectorAll('[name="cnpj"],[name="cnpj_cpf"]').forEach(el => {
    // Auto-detecta CPF ou CNPJ pelo nº de dígitos
    IMask(el, {
      mask: [
        { mask: '000.000.000-00' },
        { mask: '00.000.000/0000-00' },
      ],
      dispatch: function (appended, dynamicMasked) {
        var digitos = (dynamicMasked.value + appended).replace(/\D/g, '');
        return dynamicMasked.compiledMasks[digitos.length > 11 ? 1 : 0];
      },
    });
  });

  // Campos de dinheiro (valor_*, preco_*, custo_*, venda_*)
  document.querySelectorAll([
    '[name="valor"]',
    '[name="valor_total"]',
    '[name="valor_custo"]',
    '[name="valor_venda"]',
    '[name="valor_unitario"]',
    '[name="valor_diagnostico"]',
    '[name="desconto_valor"]',
    '[name="saldo_inicial"]',
    '[name="limite_credito"]',
    '[name="valor_estimado"]',
  ].join(',')).forEach(el => {
    if (el.type !== 'hidden') aplicarMascaraDinheiro(el);
  });

  // Percentuais
  document.querySelectorAll('[name="desconto_percentual"],[name="margem_lucro"],[name="probabilidade"],[name="percentual"]').forEach(el => {
    aplicarMascaraPercentual(el);
  });

  // IMEI / Número de série
  document.querySelectorAll('[name="imei"],[name="numero_serie"]').forEach(el => {
    aplicarMascaraImei(el);
  });

  // Quantidades e dias (inteiros)
  document.querySelectorAll('[name="garantia_dias"],[name="prazo_retirada_dias"],[name="max_usuarios"],[name="lembrete_minutos"]').forEach(el => {
    aplicarMascaraInteiro(el, 0, 9999);
  });

  // ─── Atualizar label CPF/CNPJ dinamicamente ─────────────────────────────
  if (tipoPessoaEl) {
    tipoPessoaEl.addEventListener('change', function () {
      const lblEl = document.getElementById('lblCpfCnpj');
      if (lblEl) lblEl.textContent = this.value === 'pj' ? 'CNPJ' : 'CPF';
    });
  }

  // ─── Formatação monetária em tempo real com R$ prefix nos inputs ─────────
  document.querySelectorAll('.input-money').forEach(el => {
    aplicarMascaraDinheiro(el);
  });

  // ─── Destaque de campo inválido no blur ───────────────────────────────────
  document.querySelectorAll('input[required], select[required], textarea[required]').forEach(el => {
    el.addEventListener('blur', function () {
      if (!this.value.trim()) {
        this.classList.add('is-invalid');
      } else {
        this.classList.remove('is-invalid');
        this.classList.add('is-valid');
      }
    });
    // Remove is-valid ao submeter (visual limpo)
    el.closest('form')?.addEventListener('submit', () => el.classList.remove('is-valid'));
  });

});

// ─── Validação de CPF/CNPJ (compartilhado) ───────────────────────────────
function _cpfValido(n){ if(/^(\d)\1{10}$/.test(n)) return false; for(let t=9;t<11;t++){let d=0;for(let i=0;i<t;i++)d+=(+n[i])*((t+1)-i);d=((10*d)%11)%10;if(+n[t]!==d)return false;} return true; }
function _cnpjValido(n){ if(/^(\d)\1{13}$/.test(n)) return false; for(let t=12;t<14;t++){let d=0,m=t-7;for(let i=0;i<t;i++){d+=(+n[i])*m;m=(m===2)?9:m-1;}d=((10*d)%11)%10;if(+n[t]!==d)return false;} return true; }
window.docValido = function(v){ const n=(v||'').replace(/\D/g,''); if(n==='')return true; if(n.length===11)return _cpfValido(n); if(n.length===14)return _cnpjValido(n); return false; };
window.validarDocumentoNoBlur = function(el){
  const fb = () => {
    const anchor = el.closest('.input-group') || el;   // não jogar a msg DENTRO do input-group (flex encolhe o campo)
    let m = anchor.parentNode.querySelector('.doc-feedback');
    if (!m) { m = document.createElement('div'); m.className = 'doc-feedback small text-danger mt-1 w-100'; anchor.insertAdjacentElement('afterend', m); }
    return m;
  };
  const check = () => { const ok=window.docValido(el.value); const m=fb();
    if(!ok){ el.classList.add('is-invalid'); el.classList.remove('is-valid'); m.textContent='CPF/CNPJ inválido — confira os dígitos.'; m.style.display=''; }
    else { el.classList.remove('is-invalid'); m.style.display='none'; if(el.value.trim()) el.classList.add('is-valid'); }
    return ok; };
  el.addEventListener('blur', check);
  el.closest('form')?.addEventListener('submit', function(ev){ if(!check()){ ev.preventDefault(); el.focus(); } });
};