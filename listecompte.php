<?php
require "connexion.php";
exigerConnexion();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	verifierCsrf();
}

$erreur = '';
$message = $_SESSION['message'] ?? '';
unset($_SESSION['message']);

if (isset($_POST['appliquermodifier'])) {
	$idutilisateur = filter_var($_POST['idutilisateur'] ?? null, FILTER_VALIDATE_INT);
	$nom = trim((string)($_POST['nom'] ?? ''));
	$email = trim((string)($_POST['email'] ?? ''));
	$motdepasse = (string)($_POST['motdepasse'] ?? '');

	if (!$idutilisateur || $nom === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
		$erreur = 'Vérifiez le nom et l’email.';
	} elseif ($motdepasse !== '' && strlen($motdepasse) < 12) {
		$erreur = 'Le nouveau mot de passe doit contenir au moins 12 caractères.';
	} else {
		try {
			$verifier = $bd->prepare("SELECT idutilisateur FROM utilisateur WHERE email = ? AND idutilisateur <> ? LIMIT 1");
			$verifier->execute([$email, $idutilisateur]);
			if ($verifier->fetch()) {
				throw new RuntimeException('Email déjà utilisé.');
			}

			if ($motdepasse !== '') {
				$q = $bd->prepare("UPDATE utilisateur SET nom = ?, email = ?, motdepasse = ? WHERE idutilisateur = ?");
				$q->execute([$nom, $email, password_hash($motdepasse, PASSWORD_DEFAULT), $idutilisateur]);
			} else {
				$q = $bd->prepare("UPDATE utilisateur SET nom = ?, email = ? WHERE idutilisateur = ?");
				$q->execute([$nom, $email, $idutilisateur]);
			}

			if ((int)$idutilisateur === (int)$_SESSION['idutilisateur']) {
				$_SESSION['email'] = $email;
			}
			$_SESSION['message'] = 'Modification effectuée.';
			header("Location: listecompte.php");
			exit;
		} catch (Throwable $e) {
			$erreur = 'Impossible d’enregistrer ces informations. Vérifiez que cet email n’est pas déjà utilisé.';
		}
	}
}

if (isset($_POST['supprimer'])) {
	$idutilisateur = filter_var($_POST['idutilisateur'] ?? null, FILTER_VALIDATE_INT);
	if (!$idutilisateur) {
		$erreur = 'Compte invalide.';
	} elseif ((int)$idutilisateur === (int)$_SESSION['idutilisateur']) {
		$erreur = 'Vous ne pouvez pas supprimer le compte connecté.';
	} else {
		$q = $bd->prepare("DELETE FROM utilisateur WHERE idutilisateur = ?");
		$q->execute([$idutilisateur]);
		$_SESSION['message'] = 'Suppression effectuée.';
		header("Location: listecompte.php");
		exit;
	}
}

$user = null;
if (isset($_POST['form-modifier'])) {
	$idutilisateur = filter_var($_POST['idutilisateur'] ?? null, FILTER_VALIDATE_INT);
	if ($idutilisateur) {
		$req = $bd->prepare("SELECT idutilisateur, nom, email FROM utilisateur WHERE idutilisateur = ?");
		$req->execute([$idutilisateur]);
		$user = $req->fetch();
	}
	if (!$user) {
		$erreur = 'Compte introuvable.';
	}
}

$stm = $bd->query("SELECT idutilisateur, nom, email FROM utilisateur ORDER BY nom ASC");
?>

<!DOCTYPE html>
<html>

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="stylesheet" type="text/css" href="css/bootstrap.min.css">
	<title>Liste des comptes</title>
</head>

<body>
	<?php include 'menu.php'; ?>

	<div class="container">
		<h1>Liste des comptes</h1>
		<?php if ($message !== '') { ?>
			<div class="alert alert-success" role="status"><?php echo h($message); ?></div>
		<?php } ?>
		<?php if ($erreur !== '') { ?>
			<div class="alert alert-danger" role="alert"><?php echo h($erreur); ?></div>
		<?php } ?>

		<?php if ($user) { ?>
			<h3>Modifier utilisateur</h3>
			<form method="POST">
				<?php echo champCsrf(); ?>
				<input type="hidden" name="idutilisateur" value="<?php echo h($user['idutilisateur']); ?>">

				<label for="nom">Nom</label>
				<input id="nom" type="text" name="nom" value="<?php echo h($user['nom']); ?>" class="form-control" maxlength="250" required>

				<label for="email">Email</label>
				<input id="email" type="email" name="email" value="<?php echo h($user['email']); ?>" class="form-control" maxlength="250" required>

				<label for="motdepasse">Nouveau mot de passe</label>
				<input id="motdepasse" type="password" name="motdepasse" class="form-control" minlength="12" autocomplete="new-password">

				<br>
				<input type="submit" name="appliquermodifier" value="Modifier" class="btn btn-warning">
			</form>
			<hr>
		<?php } ?>

		<div class="table-responsive">
		<table class="table">
			<tr>
				<th>Nom</th>
				<th>Email</th>
				<th>Modifier</th>
				<th>Supprimer</th>
			</tr>

			<tbody>
				<?php while ($d = $stm->fetch()) { ?>
					<tr>
						<td><?php echo h($d['nom']); ?></td>
						<td><?php echo h($d['email']); ?></td>
						<td>
							<form method="POST">
								<?php echo champCsrf(); ?>
								<input type="hidden" name="idutilisateur" value="<?php echo h($d['idutilisateur']); ?>">
								<input type="submit" value="MODIFIER" name="form-modifier" class="btn btn-warning">
							</form>
						</td>
						<td>
							<form method="POST" onsubmit="return confirm('Confirmer la suppression ?');">
								<?php echo champCsrf(); ?>
								<input type="hidden" name="idutilisateur" value="<?php echo h($d['idutilisateur']); ?>">
								<input type="submit" value="SUPPRIMER" name="supprimer" class="btn btn-danger">
							</form>
						</td>
					</tr>
				<?php } ?>
			</tbody>
		</table>
		</div>
	</div>
</body>

</html>
