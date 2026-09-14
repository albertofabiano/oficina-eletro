-- E-mail opcional por linha da lista manual de convite (ver 060) — cada empresa colada pode
-- ter WhatsApp e/ou e-mail; o disparo tenta os dois canais quando ambos existirem.
ALTER TABLE `diretorio_convites_manuais`
  ADD COLUMN IF NOT EXISTS `email` VARCHAR(150) NULL AFTER `whatsapp`;
