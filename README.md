# GED — Gestion électronique des documents

Application web pour centraliser les documents d’une organisation, les classer par catégorie et département, puis les retrouver et les consulter depuis un espace protégé par connexion.

## Fonctionnalités

- Connexion et gestion des comptes utilisateurs.
- Ajout de documents PDF avec nom, date, catégorie et département.
- Liste des documents, modification des informations, remplacement du PDF et suppression.
- Recherche par nom de document.
- Consultation des fichiers après authentification.
- Interface Bootstrap adaptée à une utilisation sur ordinateur et mobile.

## Technologies

- PHP natif et HTML5
- MySQL avec PDO
- Bootstrap CSS
- SQL pour le schéma et les données initiales
- Aucun outil d’IA ni framework JavaScript détecté

## Prérequis

- PHP avec les extensions PDO MySQL et Fileinfo activées
- MySQL ou MariaDB
- Apache ou un serveur PHP équivalent

## Installation locale

1. Placez le projet dans le répertoire servi par Apache, par exemple `C:\wamp64\www\ged`.
2. Démarrez Apache et MySQL.
3. Créez la base `ged` en `utf8mb4` dans phpMyAdmin, puis importez les fichiers SQL dans cet ordre :

   1. `categories.sql`
   2. `departements.sql`
   3. `documents.sql`
   4. `utilisateur.sql`

4. Pour une installation locale standard, la connexion utilise `localhost`, la base `ged` et l’utilisateur MySQL `root` sans mot de passe. Pour modifier ces paramètres, configurez les variables d’environnement ci-dessous.
5. Ouvrez `http://localhost/ged/index.php` et créez le premier compte depuis le lien proposé sur la page de connexion. Le mot de passe doit comporter au moins 12 caractères.

## Configuration

Les paramètres sont lus depuis l’environnement PHP :

| Variable | Rôle | Valeur locale par défaut |
| --- | --- | --- |
| `GED_ENV` | Environnement (`development` ou `production`) | `development` |
| `GED_DB_HOST` | Hôte MySQL | `localhost` |
| `GED_DB_NAME` | Nom de la base | `ged` |
| `GED_DB_USER` | Utilisateur MySQL | `root` en développement |
| `GED_DB_PASSWORD` | Mot de passe MySQL | vide en développement |
| `GED_UPLOAD_DIR` | Dossier de stockage des documents | `ged-private-files`, hors du répertoire public |
| `GED_COOKIE_SECURE` | Force le cookie de session en mode HTTPS | activé automatiquement si HTTPS est détecté |

En production, définissez un utilisateur MySQL dédié et son mot de passe, utilisez HTTPS et configurez `GED_UPLOAD_DIR` vers un dossier privé accessible en écriture par PHP.

## Utilisation

Après connexion, le menu permet d’ajouter un compte ou un document, d’afficher la liste, de rechercher un document et de se déconnecter. Les PDF téléversés sont limités à 10 Mo. La recherche par nom correspond au début du nom saisi.

## Sécurité

- Les pages de gestion et de consultation exigent une session authentifiée.
- Les écritures en base utilisent PDO et des requêtes préparées.
- Les formulaires POST vérifient un jeton CSRF.
- Les mots de passe sont enregistrés avec `password_hash()`.
- Les PDF sont contrôlés par extension, taille et type MIME avant stockage.
- Les fichiers sont servis par un point d’accès PHP après vérification de la session, plutôt que par une URL directe vers le document.

## Parcours de démonstration

En 30 secondes : connectez-vous, recherchez un document par son nom, ouvrez le PDF, puis modifiez sa catégorie et montrez le résultat actualisé dans la liste.
