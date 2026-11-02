CREATE TABLE IF NOT EXISTS departements (
  iddepartement INT NOT NULL AUTO_INCREMENT,
  departement VARCHAR(250) NOT NULL,
  PRIMARY KEY (iddepartement),
  UNIQUE KEY uq_departements_departement (departement)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO departements (departement)
SELECT 'Technique' WHERE NOT EXISTS (SELECT 1 FROM departements WHERE departement = 'Technique');

INSERT INTO departements (departement)
SELECT 'Marketing' WHERE NOT EXISTS (SELECT 1 FROM departements WHERE departement = 'Marketing');

INSERT INTO departements (departement)
SELECT 'Ressource Humaines' WHERE NOT EXISTS (SELECT 1 FROM departements WHERE departement = 'Ressource Humaines');
