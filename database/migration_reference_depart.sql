-- =========================================================
-- Migration : Ajout de la colonne reference_depart
-- Exécuter dans phpMyAdmin ou en ligne de commande
-- =========================================================

ALTER TABLE courriers ADD COLUMN reference_depart VARCHAR(50) DEFAULT NULL AFTER reference;
