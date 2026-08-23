-- Migrácia pre podporu QR platieb (FIO Bank overenie)
-- Spusti raz pred nasadením nových súborov

-- Pridanie stĺpca qr_platba do tabuľky ekasa_doklady
ALTER TABLE `ekasa_doklady`
  ADD COLUMN `qr_platba` DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER `platobna_karta`;
