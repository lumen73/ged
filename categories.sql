CREATE TABLE IF NOT EXISTS categories (
  idcategorie INT NOT NULL AUTO_INCREMENT,
  categorie VARCHAR(250) NOT NULL,
  PRIMARY KEY (idcategorie),
  UNIQUE KEY uq_categories_categorie (categorie)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO categories (categorie)
SELECT 'Privé' WHERE NOT EXISTS (SELECT 1 FROM categories WHERE categorie = 'Privé');

INSERT INTO categories (categorie)
SELECT 'Public' WHERE NOT EXISTS (SELECT 1 FROM categories WHERE categorie = 'Public');
