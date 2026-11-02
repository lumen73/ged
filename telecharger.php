<?php
require "connexion.php";
exigerConnexion();

$iddoc = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$iddoc) {
	http_response_code(404);
	exit('Document introuvable.');
}

$q = $bd->prepare("SELECT fichier FROM documents WHERE iddocuments = ?");
$q->execute([$iddoc]);
$nom = $q->fetchColumn();
$chemin = $nom ? cheminDocument($nom) : null;

if ($chemin === null) {
	http_response_code(404);
	exit('Document introuvable.');
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$type = $finfo->file($chemin);
$typesAutorises = ['application/pdf', 'image/jpeg', 'image/png'];
if (!in_array($type, $typesAutorises, true)) {
	http_response_code(404);
	exit('Document introuvable.');
}

$nomTelecharge = basename($chemin);
header('Content-Type: ' . $type);
header('Content-Length: ' . filesize($chemin));
header('Content-Disposition: inline; filename="' . rawurlencode($nomTelecharge) . '"');
header('X-Content-Type-Options: nosniff');
readfile($chemin);
exit;
?>
