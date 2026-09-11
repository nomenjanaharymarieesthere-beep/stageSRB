-- Migration : Ajout de la colonne reference_origine
-- Référence attribuée par l'expéditeur (organisme/personne émettrice du courrier),
-- distincte de la référence d'arrivée (reference) attribuée par la secrétaire.
ALTER TABLE courriers ADD COLUMN reference_origine VARCHAR(255) DEFAULT NULL AFTER `reference`;