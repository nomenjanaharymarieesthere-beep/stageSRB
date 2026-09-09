-- =========================================================
-- Base de données : drbf — Gestion des Courriers DRBF
-- A importer dans phpMyAdmin (XAMPP) : onglet "Importer"
-- =========================================================

CREATE DATABASE IF NOT EXISTS drbf CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE drbf;

-- ---------------------------------------------------------
-- Table : users
-- ---------------------------------------------------------
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS historique;
DROP TABLE IF EXISTS etapes;
DROP TABLE IF EXISTS courriers;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(30) NOT NULL,             -- secretaire, drbf, chef_srb, chef_srsp, srpe, division, coordonnateur
    service VARCHAR(10) DEFAULT NULL,      -- SRB, SRSP, SRPE
    division_code VARCHAR(30) DEFAULT NULL,
    actif TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------
-- Table : courriers
-- ---------------------------------------------------------
CREATE TABLE courriers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reference VARCHAR(50) UNIQUE NOT NULL,
    reference_depart VARCHAR(50) DEFAULT NULL,
    objet VARCHAR(255) NOT NULL,
    expediteur VARCHAR(255) NOT NULL,
    date_reception DATE NOT NULL,
    description TEXT,
    pdf_original VARCHAR(255) DEFAULT NULL,
    pdf_courant VARCHAR(255) DEFAULT NULL,
    statut VARCHAR(30) NOT NULL DEFAULT 'nouveau',
    service_cible VARCHAR(10) DEFAULT NULL,
    division_cible VARCHAR(30) DEFAULT NULL,
    chef_service_id INT DEFAULT NULL,
    division_id INT DEFAULT NULL,
    created_by INT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_courriers_created_by FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------
-- Table : etapes (journal des transmissions / workflow)
-- ---------------------------------------------------------
CREATE TABLE etapes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    courrier_id INT NOT NULL,
    from_user_id INT DEFAULT NULL,
    to_user_id INT DEFAULT NULL,
    from_role VARCHAR(30) DEFAULT NULL,
    to_role VARCHAR(30) DEFAULT NULL,
    action VARCHAR(50) NOT NULL,
    message TEXT,
    remarque TEXT,
    delai VARCHAR(100) DEFAULT NULL,
    pdf_file VARCHAR(255) DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_etapes_courrier FOREIGN KEY (courrier_id) REFERENCES courriers(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------
-- Table : notifications
-- ---------------------------------------------------------
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    courrier_id INT DEFAULT NULL,
    message VARCHAR(500) NOT NULL,
    lu TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------
-- Table : historique
-- ---------------------------------------------------------
CREATE TABLE historique (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    courrier_id INT DEFAULT NULL,
    action VARCHAR(50) NOT NULL,
    description VARCHAR(500) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_historique_user FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================================
-- Comptes de démonstration
-- =========================================================
INSERT INTO users (username, password,  role, service, division_code) VALUES
('SECRETAIRE', '$2y$10$VsPfTtRUaInG.9bumsiVYeABAuJiK5LxbbL5mjAGak6wv4u6joi1q', 'secretaire',    NULL,   NULL),
('DRBF',       '$2y$10$b2gVrMm5waDDCJKUY3bmfeBzSnMCY5qoXh9s89mFJmYK9hzr8xoqO', 'drbf',          NULL,   NULL),
('COORDO',     '$2y$10$7j9D9QJPtw7kHvr7dhiQmeF1eadm3lXbieewqqqEQk3w.f5AU26YK',  'coordonnateur', NULL,   NULL),

('SRB',        '$2y$10$moQDr9JNc42UgBBVWzBxSeWb/pGo1TsrybmlCIl6gb2PEBNu8joSa','chef_srb',      'SRB',  NULL),
('PE',         '$2y$10$F8wDAAJJPUnmUHkNdpI4puwskZxmFOz.wj6mHCK/ig31xHXO.sGIC', 'division',      'SRB',  'PE'),
('FLEPN',      '$2y$10$5H/UT06nUt3y0d74XHKB8OOWzJ9tHXFD4zAz7Pu8lc7QaQEHSb83O',   'division',      'SRB',  'FL-EPN'),
('ERFM',       '$2y$10$yd45wNd6iCXIS1mzDCvQh.CF4brhzJKHdN.6CdGdKTkhCtslZJiAK','division',      'SRB',  'Execution-RFM'),
('CIR',        '$2y$10$oRIUK/bETCyURyIgQA08iOTI0AP/CHd0O1fQ5LZFAiQjLyCulNap6',  'division',      'SRB',  'CIR'),

('SRSP',       '$2y$10$/Ys2aUCAI.Owd70nm.RFY.6zL4k/zX4YV.40mrAVHMZuQQflATsxC','chef_srsp',     'SRSP', NULL),
('BAG',        '$2y$10$Q19yxo/2Thfluq4k0Kjp9ug0vAy2b4IfTOX2mQ/XzgWvIlVatk/Qe', 'division',      'SRSP', 'BAG'),
('VISA',       '$2y$10$ZpbVxhfOCh4dSPkTtF6bouwijxhj3C0yiJNW7sn2qE6btWkC8ZE4C', 'division',      'SRSP', 'VISA'),
('SOLDE',      '$2y$10$5aJOy.At1LEsy5zubrh.DuM6XfMwKTRyd23Ss7woyongqJUrxX1QW',  'division',      'SRSP', 'SOLDE'),
('PENSION',    '$2y$10$HrXoQyjW76Yg6.Ubd5FfxOd7SkqvYc0WUNVuW3jjtS4jVae2SC0.q','division',      'SRSP', 'PENSION'),
('SECOUR',     '$2y$10$vCPie278bIUIlmjD7hqJFOG8HnmIGFWLVIW7Ay.Z69FskJbBwABFa', 'division',      'SRSP', 'SECOUR'),

('SRPE',       '$2y$10$CcxRjqGvRtgx7pcvOX/nM.LY3F13b5wyccW.JRaJW/lm334vzueaS', 'srpe',          'SRPE', NULL);
