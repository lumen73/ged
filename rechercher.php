<?php
require "connexion.php";
exigerConnexion();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	verifierCsrf();
}

$erreur = '';
$message = $_SESSION['message'] ?? '';
unset($_SESSION['message']);
$valeurRecherche = $_POST['nom'] ?? $_POST['nom_recherche'] ?? '';
$n = is_string($valeurRecherche) ? trim($valeurRecherche) : '';
$afficherResultats = isset($_POST['rechercher']) || isset($_POST['modifierdoc']) || isset($_POST['supprimer']);
$documentEdit = null;

if (isset($_POST['modifierdoc'])) {
	$iddoc = filter_var($_POST['iddoc'] ?? null, FILTER_VALIDATE_INT);
	$date = is_string($_POST['date'] ?? null) ? trim($_POST['date']) : '';
	$nom = is_string($_POST['nomdoc'] ?? null) ? trim($_POST['nomdoc']) : '';
	$idcategorie = filter_var($_POST['categorie'] ?? null, FILTER_VALIDATE_INT);
	$iddepartement = filter_var($_POST['departement'] ?? null, FILTER_VALIDATE_INT);
	$dateValide = DateTime::createFromFormat('!Y-m-d', $date);

	if (!$iddoc || $nom === '' || strlen($nom) > 200 || !$dateValide || $dateValide->format('Y-m-d') !== $date || !$idcategorie || !$iddepartement) {
		$erreur = 'Vérifiez les informations du document.';
	} else {
		$q = $bd->prepare("UPDATE documents SET datesave = ?, nomdoc = ?, idcategorie = ?, iddepartement = ? WHERE iddocuments = ?");
		$q->execute([$date, $nom, $idcategorie, $iddepartement, $iddoc]);
		$message = 'Modification effectuée.';
		$afficherResultats = true;
	}
}

if (isset($_POST['supprimer'])) {
	$iddoc = filter_var($_POST['iddoc'] ?? null, FILTER_VALIDATE_INT);
	if (!$iddoc) {
		$erreur = 'Document invalide.';
	} else {
		$req = $bd->prepare("SELECT fichier FROM documents WHERE iddocuments = ?");
		$req->execute([$iddoc]);
		$nomFichier = $req->fetchColumn();
		$supprimer = $bd->prepare("DELETE FROM documents WHERE iddocuments = ?");
		$supprimer->execute([$iddoc]);
		if ($supprimer->rowCount() > 0 && $nomFichier) {
			supprimerFichierDocument($nomFichier);
		}
		$message = 'Suppression effectuée.';
		$afficherResultats = true;
	}
}

if (isset($_POST['edit'])) {
	$iddoc = filter_var($_POST['iddoc'] ?? null, FILTER_VALIDATE_INT);
	if ($iddoc) {
		$req = $bd->prepare("SELECT iddocuments, nomdoc, datesave, idcategorie, iddepartement FROM documents WHERE iddocuments = ?");
		$req->execute([$iddoc]);
		$documentEdit = $req->fetch();
	}
	if (!$documentEdit) {
		$erreur = 'Document introuvable.';
	}
	$afficherResultats = true;
}

$stm = null;
if ($afficherResultats) {
	$stm = $bd->prepare("SELECT documents.iddocuments, documents.nomdoc, documents.datesave, documents.fichier, categories.categorie, departements.departement FROM documents INNER JOIN departements ON departements.iddepartement = documents.iddepartement INNER JOIN categories ON categories.idcategorie = documents.idcategorie WHERE documents.nomdoc LIKE ? ORDER BY documents.iddocuments DESC");
	$stm->execute([$n . '%']);
}

$cat = $bd->query("SELECT idcategorie, categorie FROM categories ORDER BY categorie");
$dep = $bd->query("SELECT iddepartement, departement FROM departements ORDER BY departement");
?>
<!DOCTYPE html>
<html>

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="stylesheet" href="css/bootstrap.min.css">
	<title>Recherche document</title>
</head>

<body>
	<?php include 'menu.php'; ?>

	<div class="container">
		<h1>Rechercher un document</h1>
		<?php if ($message !== '') { ?>
			<div class="alert alert-success" role="status"><?php echo h($message); ?></div>
		<?php } ?>
		<?php if ($erreur !== '') { ?>
			<div class="alert alert-danger" role="alert"><?php echo h($erreur); ?></div>
		<?php } ?>

		<form method="POST">
			<?php echo champCsrf(); ?>
			<label for="nom" class="fw-bold">Nom du document</label>
			<input id="nom" type="text" name="nom" value="<?php echo h($n); ?>" class="form-control" maxlength="200">
			<br>
			<input type="submit" name="rechercher" value="Rechercher" class="btn btn-success">
		</form>

		<?php if ($afficherResultats) { ?>
			<h4>Résultats : <?php echo h($n); ?></h4>
			<div class="table-responsive">
				<table class="table mt-3">
					<thead>
						<tr>
							<th>Nom</th>
							<th>Catégorie</th>
							<th>Département</th>
							<th>Date</th>
							<th>Voir</th>
							<th>Actions</th>
						</tr>
					</thead>
					<tbody>
						<?php $found = false; ?>
						<?php while ($r = $stm->fetch()) { $found = true; ?>
							<tr>
								<td><?php echo h($r['nomdoc']); ?></td>
								<td><?php echo h($r['categorie']); ?></td>
								<td><?php echo h($r['departement']); ?></td>
								<td><?php echo h($r['datesave']); ?></td>
								<td><?php if ($r['fichier'] !== null && $r['fichier'] !== '') { ?><a class="btn btn-success" target="_blank" rel="noopener" href="telecharger.php?id=<?php echo h($r['iddocuments']); ?>">Voir</a><?php } else { ?>Indisponible<?php } ?></td>
								<td>
									<form method="POST" class="d-inline">
										<?php echo champCsrf(); ?>
										<input type="hidden" name="iddoc" value="<?php echo h($r['iddocuments']); ?>">
										<input type="hidden" name="nom_recherche" value="<?php echo h($n); ?>">
										<input type="submit" name="edit" value="Modifier" class="btn btn-warning">
									</form>
									<form method="POST" class="d-inline" onsubmit="return confirm('Confirmer ?');">
										<?php echo champCsrf(); ?>
										<input type="hidden" name="iddoc" value="<?php echo h($r['iddocuments']); ?>">
										<input type="hidden" name="nom_recherche" value="<?php echo h($n); ?>">
										<input type="submit" name="supprimer" value="Supprimer" class="btn btn-danger">
									</form>
								</td>
							</tr>
						<?php } ?>
						<?php if (!$found) { ?>
							<tr><td colspan="6">Aucun document trouvé.</td></tr>
						<?php } ?>
					</tbody>
				</table>
			</div>
		<?php } ?>

		<?php if ($documentEdit) { ?>
			<h3>Modifier document</h3>
			<form method="POST">
				<?php echo champCsrf(); ?>
				<input type="hidden" name="iddoc" value="<?php echo h($documentEdit['iddocuments']); ?>">
				<input type="hidden" name="nom_recherche" value="<?php echo h($n); ?>">

				<label for="nomdoc">Nom du document</label>
				<input id="nomdoc" type="text" name="nomdoc" value="<?php echo h($documentEdit['nomdoc']); ?>" class="form-control" maxlength="200" required>
				<label for="date">Date</label>
				<input id="date" type="date" name="date" value="<?php echo h($documentEdit['datesave']); ?>" class="form-control mt-2" required>

				<label for="categorie">Catégorie</label>
				<select id="categorie" name="categorie" class="form-control mt-2" required>
					<?php while ($c = $cat->fetch()) { ?>
						<option value="<?php echo h($c['idcategorie']); ?>" <?php echo (int)$c['idcategorie'] === (int)$documentEdit['idcategorie'] ? 'selected' : ''; ?>><?php echo h($c['categorie']); ?></option>
					<?php } ?>
				</select>

				<label for="departement">Département</label>
				<select id="departement" name="departement" class="form-control mt-2" required>
					<?php while ($d = $dep->fetch()) { ?>
						<option value="<?php echo h($d['iddepartement']); ?>" <?php echo (int)$d['iddepartement'] === (int)$documentEdit['iddepartement'] ? 'selected' : ''; ?>><?php echo h($d['departement']); ?></option>
					<?php } ?>
				</select>

				<br>
				<input type="submit" name="modifierdoc" value="Modifier" class="btn btn-warning">
			</form>
		<?php } ?>
	</div>
</body>

</html>
