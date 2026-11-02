CREATE TABLE IF NOT EXISTS documents (
  iddocuments INT NOT NULL AUTO_INCREMENT,
  nomdoc VARCHAR(200) NOT NULL,
  fichier VARCHAR(255) DEFAULT NULL,
  datesave DATE NOT NULL,
  idcategorie INT NOT NULL,
  iddepartement INT NOT NULL,
  PRIMARY KEY (iddocuments),
  KEY fk_idcategorie (idcategorie),
  KEY fk_iddepartement (iddepartement),
  CONSTRAINT fk_documents_categorie FOREIGN KEY (idcategorie) REFERENCES categories (idcategorie),
  CONSTRAINT fk_documents_departement FOREIGN KEY (iddepartement) REFERENCES departements (iddepartement)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE documents MODIFY fichier VARCHAR(255) DEFAULT NULL;
