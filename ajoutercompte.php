<?php
require "connexion.php";

$premierCompte = (int)$bd->query("SELECT COUNT(*) FROM utilisateur")->fetchColumn() === 0;
if (!$premierCompte) {
	exigerConnexion();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	verifierCsrf();
}

$erreur = '';
if (isset($_POST['ajouter'])) {
	$nom = trim((string)($_POST['nom'] ?? ''));
	$email = trim((string)($_POST['email'] ?? ''));
	$motdepasse = (string)($_POST['motdepasse'] ?? '');

	if ($nom === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($motdepasse) < 12) {
		$erreur = 'Renseignez un nom, un email valide et un mot de passe d’au moins 12 caractères.';
	} else {
		try {
			$verifier = $bd->prepare("SELECT idutilisateur FROM utilisateur WHERE email = ? LIMIT 1");
			$verifier->execute([$email]);
			if ($verifier->fetch()) {
				throw new RuntimeException('Email déjà utilisé.');
			}

			$q = $bd->prepare("INSERT INTO utilisateur (nom, email, motdepasse) VALUES (?, ?, ?)");
			$q->execute([$nom, $email, password_hash($motdepasse, PASSWORD_DEFAULT)]);

			if ($premierCompte) {
				session_regenerate_id(true);
				$_SESSION['idutilisateur'] = $bd->lastInsertId();
				$_SESSION['email'] = $email;
			}

			$_SESSION['message'] = 'Compte ajouté.';
			header($premierCompte ? "Location: listecompte.php" : "Location: index.php");
			exit;
		} catch (Throwable $e) {
			$erreur = 'Impossible de créer ce compte. Vérifiez que cet email n’est pas déjà utilisé.';
		}
	}
}
?>
<!DOCTYPE html>
<html>

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="stylesheet" type="text/css" href="css/bootstrap.min.css">
	<title>Ajouter un compte</title>
</head>

<body>
	<?php if (!$premierCompte) { include 'menu.php'; } ?>

	<div class="container">

		<fieldset>
			<legend><?php echo $premierCompte ? 'Créer le premier compte' : 'Ajouter un compte'; ?></legend>
			<?php if ($erreur !== '') { ?>
				<div class="alert alert-danger" role="alert"><?php echo h($erreur); ?></div>
			<?php } ?>
			<form method="POST" action="">
				<?php echo champCsrf(); ?>
				<label for="nom">Nom</label>
				<input id="nom" type="text" name="nom" class="form-control" maxlength="250" required>
				<label for="email">Email</label>
				<input id="email" type="email" name="email" class="form-control" maxlength="250" autocomplete="email" required>
				<label for="motdepasse">Mot de passe</label>
				<input id="motdepasse" type="password" name="motdepasse" class="form-control" minlength="12" autocomplete="new-password" required><br>
				<input type="submit" name="ajouter" value="AJOUTER" class="btn btn-success">
			</form>
		</fieldset>
	</div>
</body>

</html>
