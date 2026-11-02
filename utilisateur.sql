CREATE TABLE IF NOT EXISTS utilisateur (
  idutilisateur INT NOT NULL AUTO_INCREMENT,
  nom VARCHAR(250) NOT NULL,
  email VARCHAR(250) NOT NULL,
  motdepasse VARCHAR(255) NOT NULL,
  PRIMARY KEY (idutilisateur),
  UNIQUE KEY uq_utilisateur_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE utilisateur MODIFY motdepasse VARCHAR(255) NOT NULL;
