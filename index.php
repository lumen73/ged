<?php
require "connexion.php";

$erreur = '';
$premierCompte = (int)$bd->query("SELECT COUNT(*) FROM utilisateur")->fetchColumn() === 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	verifierCsrf();
}

if (isset($_POST['connecter'])) {
	$email = trim((string)($_POST['email'] ?? ''));
	$motdepasse = (string)($_POST['motdepasse'] ?? '');
	$q = $bd->prepare("SELECT idutilisateur, nom, email, motdepasse FROM utilisateur WHERE email = ? LIMIT 1");
	$q->execute([$email]);
	$d = $q->fetch();
	$valide = false;

	if ($d) {
		$hash = (string)$d['motdepasse'];
		$valide = $motdepasse !== '' && password_verify($motdepasse, $hash);

		if (!$valide && $motdepasse !== '' && (password_get_info($hash)['algo'] ?? null) === null) {
			$ancienHash = password_hash($hash, PASSWORD_DEFAULT);
			if (password_verify($motdepasse, $ancienHash)) {
				$maj = $bd->prepare("UPDATE utilisateur SET motdepasse = ? WHERE idutilisateur = ?");
				$maj->execute([$ancienHash, $d['idutilisateur']]);
				$valide = true;
			}
		}
	}

	if ($valide) {
		session_regenerate_id(true);
		$_SESSION['idutilisateur'] = $d['idutilisateur'];
		$_SESSION['email'] = $d['email'];
		header("Location: listecompte.php");
		exit;
	}

	$erreur = 'Email ou mot de passe incorrect.';
}

$message = $_SESSION['message'] ?? '';
unset($_SESSION['message']);
?>
<!DOCTYPE html>
<html>

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="stylesheet" type="text/css" href="css/bootstrap.min.css">
	<title>Connexion</title>
</head>

<body>

	<div class="container">
		<img src="img/photo.jpeg" alt="Logo" style="width: 25%; margin: auto; display: block;">
		<h2 class="text-center">Gestion Electronique des Documents</h2>
		<div style="border: 0.5px solid black; border-radius: 10px; padding: 10px;">
			<?php if ($message !== '') { ?>
				<div class="alert alert-success" role="status"><?php echo h($message); ?></div>
			<?php } ?>
			<?php if ($erreur !== '') { ?>
				<div class="alert alert-danger" role="alert"><?php echo h($erreur); ?></div>
			<?php } ?>
			<form method="POST" action="">
				<?php echo champCsrf(); ?>
				<label for="email" class="fw-bold">Email</label>
				<input id="email" type="email" name="email" class="form-control" placeholder="Saisissez l'email" autocomplete="username" required> <br>
				<label for="motdepasse" class="fw-bold">Mot de passe</label>
				<input id="motdepasse" type="password" name="motdepasse" class="form-control" placeholder="Saisissez le mot de passe" autocomplete="current-password" required>
				<br>
				<input type="submit" name="connecter" value="Se Connecter" class="btn btn-success">
			</form>
			<?php if ($premierCompte) { ?>
				<p class="mt-3"><a href="ajoutercompte.php">Créer le premier compte</a></p>
			<?php } ?>
		</div>
	</div>
</body>

</html>
