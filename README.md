# DRBF — Application de Gestion des Courriers

Application web PHP + **MySQL** pour la Direction Régionale du Budget et des Finances.
Prête pour **XAMPP** (Apache + MySQL + PHP).

## Installation avec XAMPP

1. **Copiez le dossier `DRBF/`** dans :
   ```
   D:\xampp\htdocs\DRBF
   ```
2. **Démarrez XAMPP Control Panel** → lancez **Apache** et **MySQL** (les deux doivent être verts).
3. **Importez la base de données** :
   - Ouvrez `http://localhost/phpmyadmin`
   - Cliquez sur l'onglet **Importer**
   - Choisissez le fichier `D:\xampp\htdocs\DRBF\database\drbf.sql`
   - Cliquez sur **Exécuter**
   - Cela crée automatiquement la base **`drbf`**, les 5 tables, et les 15 comptes de démonstration.
4. **Vérifiez la connexion** dans `DRBF/config/database.php` (valeurs par défaut de XAMPP, normalement à ne pas modifier) :
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'drbf');
   define('DB_USER', 'root');
   define('DB_PASS', '');   // XAMPP n'a pas de mot de passe par défaut
   ```
5. Ouvrez **`http://localhost/DRBF/`** dans votre navigateur.

Assurez-vous aussi que le dossier `DRBF/uploads/courriers/` est accessible en écriture
(sous Windows/XAMPP c'est le cas par défaut).

## Comptes de démonstration

Mot de passe pour tous les comptes : **password123**

| Rôle | Identifiant |
|---|---|
| Secrétaire | `SECRETAIRE` |
| Directeur DRBF | `DRBF` |
| Coordonnateur | `COORDO` |
| Chef SRB | `SRB` |
| Chef SRSP | `SRSP` |
| Chef SRPE | `SRPE` |
| Division PE (SRB) | `PE` |
| Division FL-EPN (SRB) | `FLEPN` |
| Division Exécution-RFM (SRB) | `ERFM` |
| Division CIR (SRB) | `CIR` |
| Division BAG (SRSP) | `BAG` |
| Division VISA (SRSP) | `VISA` |
| Division SOLDE (SRSP) | `SOLDE` |
| Division PENSION (SRSP) | `PENSION` |
| Division SECOUR (SRSP) | `SECOUR` |

## Circuit du courrier (résumé)

Secrétaire → Directeur DRBF → (SRB / SRSP / SRPE) → Division concernée (ou directement le Chef SRPE)
→ Coordonnateur (vérification) → boucle de correction si nécessaire → validation
→ Secrétaire (signature) → archivage.

Chaque rôle ne peut effectuer que les actions et transmissions décrites dans le cahier des charges
(le contrôle est appliqué à la fois dans l'interface — seuls les liens autorisés apparaissent —
et côté serveur via `require_role()` dans chaque page).

## Structure du projet

```
DRBF/
├── database/drbf.sql        Fichier SQL à importer dans phpMyAdmin (base "drbf")
├── config/database.php      Connexion MySQL (PDO) — host/nom base/utilisateur/mot de passe
├── includes/                 Fonctions communes, en-tête (sidebar/thème) et pied de page
├── uploads/courriers/        Fichiers PDF téléversés
├── secretaire/ drbf/ chef_srb/ chef_srsp/ srpe/ division/ coordonnateur/
│                              Pages spécifiques à chaque rôle (dashboard, actions, historique)
├── index.php, login.php, logout.php
```

## Notes techniques

- Base de données **MySQL/MariaDB** nommée `drbf`, compatible XAMPP/phpMyAdmin.
- Mots de passe hachés avec `password_hash()` / vérifiés avec `password_verify()`.
- Chaque transmission de courrier (`transmettre_courrier()` dans `includes/fonctions.php`) journalise
  automatiquement l'étape, met à jour le statut, crée une notification pour le destinataire et
  alimente l'historique des deux parties concernées — garantissant que chaque utilisateur ne voit que
  les courriers et actions qui le concernent.
- Thème visuel moderne : sidebar dégradée bleu marine/or, cartes statistiques avec effet de survol,
  badges de statut colorés, boutons avec ombres douces.
