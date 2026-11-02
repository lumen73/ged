<?php
if (PHP_SAPI !== 'cli') {
	http_response_code(404);
	exit;
}

require "connexion.php";

try {
	$bd->beginTransaction();
	$stm = $bd->query("SELECT idutilisateur, motdepasse FROM utilisateur FOR UPDATE");
	$maj = $bd->prepare("UPDATE utilisateur SET motdepasse = ? WHERE idutilisateur = ?");
	$nombre = 0;

	while ($d = $stm->fetch()) {
		$motdepasse = (string)$d['motdepasse'];
		if ((password_get_info($motdepasse)['algo'] ?? null) !== null) {
			continue;
		}

		$maj->execute([password_hash($motdepasse, PASSWORD_DEFAULT), $d['idutilisateur']]);
		$nombre++;
	}

	$bd->commit();
	echo $nombre . PHP_EOL;
} catch (Throwable $e) {
	if ($bd->inTransaction()) {
		$bd->rollBack();
	}
	fwrite(STDERR, "Migration impossible.\n");
	exit(1);
}
?>
