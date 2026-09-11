-- Migration : Ajout de la colonne pdf_complementaire
-- PDF complémentaire ajouté par le Chef de Service après validation du Coordonnateur,
-- transmis avec le dossier au DRBF puis à la Secrétaire.
ALTER TABLE courriers ADD COLUMN pdf_complementaire VARCHAR(255) DEFAULT NULL AFTER pdf_courant;