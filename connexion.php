<?php
$secure = (getenv('GED_COOKIE_SECURE') === '1') || (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
session_set_cookie_params([
	'lifetime' => 0,
	'path' => '/',
	'secure' => $secure,
	'httponly' => true,
	'samesite' => 'Lax'
]);
session_start();

if (empty($_SESSION['csrf_token'])) {
	$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function h($value)
{
	return htmlspecialchars((string)($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function champCsrf()
{
	return '<input type="hidden" name="csrf_token" value="' . h($_SESSION['csrf_token']) . '">';
}

function verifierCsrf()
{
	$token = $_POST['csrf_token'] ?? '';
	if (!is_string($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
		http_response_code(403);
		exit('Requête invalide.');
	}
}

function exigerConnexion()
{
	if (empty($_SESSION['idutilisateur'])) {
		header('Location: index.php');
		exit;
	}
}

function dossierDocuments()
{
	$dossier = getenv('GED_UPLOAD_DIR');
	if ($dossier === false || $dossier === '') {
		$dossier = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'ged-private-files';
	}
	return rtrim($dossier, "\\/");
}

function cheminDocument($nom)
{
	$nom = (string)$nom;
	if ($nom === '' || basename($nom) !== $nom || !preg_match('/^[A-Za-z0-9_-]+(?:\.(?:pdf|png|jpe?g))?$/i', $nom)) {
		return null;
	}

	$racines = [dossierDocuments(), __DIR__ . DIRECTORY_SEPARATOR . 'fichiers'];
	$noms = [$nom];
	if (ctype_digit($nom)) {
		$noms = [$nom . '.pdf', $nom . '.jpg', $nom . '.jpeg', $nom . '.png', $nom];
	}

	foreach ($racines as $racine) {
		foreach ($noms as $nomFichier) {
			$chemin = $racine . DIRECTORY_SEPARATOR . $nomFichier;
			if (is_file($chemin)) {
				return $chemin;
			}
		}
	}

	return null;
}

function supprimerFichierDocument($nom)
{
	$chemin = cheminDocument($nom);
	if ($chemin !== null) {
		unlink($chemin);
	}
}

$environnement = strtolower(getenv('GED_ENV') ?: 'development');
$hote = getenv('GED_DB_HOST') ?: 'localhost';
$base = getenv('GED_DB_NAME') ?: 'ged';
$utilisateur = getenv('GED_DB_USER');
$motdepasse = getenv('GED_DB_PASSWORD');

if ($utilisateur === false || $utilisateur === '') {
	$utilisateur = $environnement === 'production' ? '' : 'root';
}
if ($motdepasse === false) {
	$motdepasse = '';
}

if ($environnement === 'production' && ($utilisateur === '' || strtolower($utilisateur) === 'root' || $motdepasse === '')) {
	http_response_code(500);
	die('Configuration de base de données manquante.');
}

try {
	$bd = new PDO(
		'mysql:host=' . $hote . ';dbname=' . $base . ';charset=utf8mb4',
		$utilisateur,
		$motdepasse,
		[
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
			PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
			PDO::ATTR_EMULATE_PREPARES => false
		]
	);
} catch (PDOException $e) {
	http_response_code(500);
	die('Erreur de connexion');
}
?>
