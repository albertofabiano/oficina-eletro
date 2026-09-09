-- Campo "Modelo" (texto livre) pro cadastro de produto — Tipo/Marca já existiam como FK pra
-- tabelas de catálogo (tipos/marcas), mas nenhuma migration commitada criou essas colunas nem
-- essas tabelas (mesmo gap já documentado em CLAUDE.md pra os_pagamentos/cobrancas/lib/dompdf) —
-- só "modelo" estava faltando de verdade, os outros três (estado_id/tipo_id/marca_id) já
-- existem em produção. Sem FK (é texto livre, ex.: "Galaxy A54", "EAX64891"), então não corre o
-- risco de mismatch de tipo já visto antes em migrations com FOREIGN KEY.
ALTER TABLE `produtos`
  ADD COLUMN IF NOT EXISTS `modelo` VARCHAR(100) NULL AFTER `marca_id`;
