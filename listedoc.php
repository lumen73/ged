<?php
require "connexion.php";
exigerConnexion();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	verifierCsrf();
}

$erreur = '';
$message = $_SESSION['message'] ?? '';
unset($_SESSION['message']);

if (isset($_POST['ajoute'])) {
	$date = trim((string)($_POST['date'] ?? ''));
	$nom = trim((string)($_POST['nomdoc'] ?? ''));
	$idcategorie = filter_var($_POST['categorie'] ?? null, FILTER_VALIDATE_INT);
	$iddepartement = filter_var($_POST['departement'] ?? null, FILTER_VALIDATE_INT);
	$fichier = $_FILES['fichier'] ?? null;

	$dateValide = DateTime::createFromFormat('!Y-m-d', $date);
	if ($nom === '' || strlen($nom) > 200 || !$dateValide || $dateValide->format('Y-m-d') !== $date || !$idcategorie || !$iddepartement) {
		$erreur = 'Vérifiez le nom, la date, la catégorie et le département.';
	} elseif (!$fichier || $fichier['error'] !== UPLOAD_ERR_OK) {
		$erreur = 'Sélectionnez un fichier PDF valide.';
	} elseif (strtolower(pathinfo(str_replace('\\', '/', basename($fichier['name'])), PATHINFO_EXTENSION)) !== 'pdf' || $fichier['size'] > 10485760) {
		$erreur = 'Le fichier doit être un PDF de 10 Mo maximum.';
	} else {
		$finfo = new finfo(FILEINFO_MIME_TYPE);
		if ($finfo->file($fichier['tmp_name']) !== 'application/pdf') {
			$erreur = 'Le contenu du fichier n’est pas un PDF valide.';
		} else {
			$dossier = dossierDocuments();
			$destination = '';
			$fichierDeplace = false;

			try {
				if (!is_dir($dossier) && !mkdir($dossier, 0700, true) && !is_dir($dossier)) {
					throw new RuntimeException('Dossier de stockage indisponible.');
				}

				$bd->beginTransaction();
				$q = $bd->prepare("INSERT INTO documents (datesave, nomdoc, idcategorie, iddepartement) VALUES (?, ?, ?, ?)");
				$q->execute([$date, $nom, $idcategorie, $iddepartement]);
				$iddoc = (int)$bd->lastInsertId();

				$nom_fichier = pathinfo(str_replace('\\', '/', basename($fichier['name'])), PATHINFO_FILENAME);
				$nom_fichier = trim(substr(preg_replace('/[^A-Za-z0-9_-]+/', '_', $nom_fichier), 0, 80), '_-');
				if ($nom_fichier === '') {
					$nom_fichier = 'document';
				}
				$filename = $iddoc . '_' . $nom_fichier . '.pdf';
				$destination = $dossier . DIRECTORY_SEPARATOR . $filename;

				if (!move_uploaded_file($fichier['tmp_name'], $destination)) {
					throw new RuntimeException('Téléversement impossible.');
				}
				$fichierDeplace = true;

				$maj = $bd->prepare("UPDATE documents SET fichier = ? WHERE iddocuments = ?");
				$maj->execute([$filename, $iddoc]);
				$bd->commit();

				$_SESSION['message'] = 'Document ajouté.';
				header("Location: listedoc.php");
				exit;
			} catch (Throwable $e) {
				if ($bd->inTransaction()) {
					$bd->rollBack();
				}
				if ($fichierDeplace && is_file($destination)) {
					unlink($destination);
				}
				$erreur = 'Impossible d’enregistrer le document.';
			}
		}
	}
}

if (isset($_POST['modifierdoc'])) {
	$iddoc = filter_var($_POST['iddoc'] ?? null, FILTER_VALIDATE_INT);
	$date = trim((string)($_POST['date'] ?? ''));
	$nom = trim((string)($_POST['nomdoc'] ?? ''));
	$idcategorie = filter_var($_POST['categorie'] ?? null, FILTER_VALIDATE_INT);
	$iddepartement = filter_var($_POST['departement'] ?? null, FILTER_VALIDATE_INT);
	$fichier = $_FILES['fichier'] ?? null;
	$dateValide = DateTime::createFromFormat('!Y-m-d', $date);

	if (!$iddoc || $nom === '' || strlen($nom) > 200 || !$dateValide || $dateValide->format('Y-m-d') !== $date || !$idcategorie || !$iddepartement) {
		$erreur = 'Informations du document invalides.';
	} else {
		$req = $bd->prepare("SELECT fichier FROM documents WHERE iddocuments = ?");
		$req->execute([$iddoc]);
		$ancienFichier = $req->fetchColumn();

		if ($ancienFichier === false) {
			$erreur = 'Document introuvable.';
		} elseif ($fichier && $fichier['error'] !== UPLOAD_ERR_NO_FILE && $fichier['error'] !== UPLOAD_ERR_OK) {
			$erreur = 'Le téléversement du fichier a échoué.';
		} elseif ($fichier && $fichier['error'] === UPLOAD_ERR_OK && (strtolower(pathinfo(str_replace('\\', '/', basename($fichier['name'])), PATHINFO_EXTENSION)) !== 'pdf' || $fichier['size'] > 10485760)) {
			$erreur = 'Le fichier doit être un PDF de 10 Mo maximum.';
		} elseif ($fichier && $fichier['error'] === UPLOAD_ERR_OK) {
			$finfo = new finfo(FILEINFO_MIME_TYPE);
			if ($finfo->file($fichier['tmp_name']) !== 'application/pdf') {
				$erreur = 'Le contenu du fichier est invalide.';
			} else {
				$dossier = dossierDocuments();
				$destination = '';
				$fichierDeplace = false;

				try {
					if (!is_dir($dossier) && !mkdir($dossier, 0700, true) && !is_dir($dossier)) {
						throw new RuntimeException('Dossier de stockage indisponible.');
					}

					$nom_fichier = pathinfo(str_replace('\\', '/', basename($fichier['name'])), PATHINFO_FILENAME);
					$nom_fichier = trim(substr(preg_replace('/[^A-Za-z0-9_-]+/', '_', $nom_fichier), 0, 80), '_-');
					if ($nom_fichier === '') {
						$nom_fichier = 'document';
					}
					$filename = $iddoc . '_' . $nom_fichier . '_' . bin2hex(random_bytes(4)) . '.pdf';
					$destination = $dossier . DIRECTORY_SEPARATOR . $filename;

					$bd->beginTransaction();
					if (!move_uploaded_file($fichier['tmp_name'], $destination)) {
						throw new RuntimeException('Téléversement impossible.');
					}
					$fichierDeplace = true;

					$maj = $bd->prepare("UPDATE documents SET datesave = ?, nomdoc = ?, idcategorie = ?, iddepartement = ?, fichier = ? WHERE iddocuments = ?");
					$maj->execute([$date, $nom, $idcategorie, $iddepartement, $filename, $iddoc]);
					$bd->commit();

					if ($ancienFichier) {
						supprimerFichierDocument($ancienFichier);
					}
				} catch (Throwable $e) {
					if ($bd->inTransaction()) {
						$bd->rollBack();
					}
					if ($fichierDeplace && is_file($destination)) {
						unlink($destination);
					}
					$erreur = 'Enregistrement impossible.';
				}
			}
		} else {
			$maj = $bd->prepare("UPDATE documents SET datesave = ?, nomdoc = ?, idcategorie = ?, iddepartement = ? WHERE iddocuments = ?");
			$maj->execute([$date, $nom, $idcategorie, $iddepartement, $iddoc]);
		}

		if ($erreur === '') {
			$_SESSION['message'] = 'Modification effectuée.';
			header("Location: listedoc.php");
			exit;
		}
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
		$_SESSION['message'] = 'Suppression effectuée.';
		header("Location: listedoc.php");
		exit;
	}
}

$iddoc = filter_var($_POST['iddoc'] ?? null, FILTER_VALIDATE_INT);
$d = null;
if ((isset($_POST['edit']) || isset($_POST['modifierdoc'])) && $iddoc) {
	$req = $bd->prepare("SELECT iddocuments, nomdoc, datesave, idcategorie, iddepartement FROM documents WHERE iddocuments = ?");
	$req->execute([$iddoc]);
	$d = $req->fetch();
	if (!$d) {
		$erreur = 'Document introuvable.';
	}
}

$stm = $bd->query("SELECT documents.iddocuments, documents.nomdoc, documents.datesave, documents.fichier, categories.categorie, departements.departement FROM documents INNER JOIN departements ON departements.iddepartement = documents.iddepartement INNER JOIN categories ON categories.idcategorie = documents.idcategorie ORDER BY documents.iddocuments DESC");
$cat = $bd->query("SELECT idcategorie, categorie FROM categories ORDER BY categorie");
$dep = $bd->query("SELECT iddepartement, departement FROM departements ORDER BY departement");
?>
<!DOCTYPE html>
<html>

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="stylesheet" type="text/css" href="css/bootstrap.min.css">
	<title>Liste des documents</title>
</head>

<body>
	<?php include 'menu.php'; ?>

	<div class="container">
		<h1>Liste des documents</h1>
		<?php if ($message !== '') { ?>
			<div class="alert alert-success" role="status"><?php echo h($message); ?></div>
		<?php } ?>
		<?php if ($erreur !== '') { ?>
			<div class="alert alert-danger" role="alert"><?php echo h($erreur); ?></div>
		<?php } ?>

		<?php if ($d) { ?>
			<h3>Modifier document</h3>
			<form method="POST" enctype="multipart/form-data">
				<?php echo champCsrf(); ?>
				<input type="hidden" name="iddoc" value="<?php echo h($d['iddocuments']); ?>">

				<label for="date">Date</label>
				<input id="date" type="date" name="date" value="<?php echo h($d['datesave']); ?>" class="form-control" required>

				<label for="nomdoc">Nom document</label>
				<input id="nomdoc" type="text" name="nomdoc" value="<?php echo h($d['nomdoc']); ?>" class="form-control" maxlength="200" required>

				<label for="categorie">Catégorie</label>
				<select id="categorie" name="categorie" class="form-control" required>
					<?php while ($c = $cat->fetch()) { ?>
						<option value="<?php echo h($c['idcategorie']); ?>" <?php echo (int)$c['idcategorie'] === (int)$d['idcategorie'] ? 'selected' : ''; ?>><?php echo h($c['categorie']); ?></option>
					<?php } ?>
				</select>

				<label for="departement">Département</label>
				<select id="departement" name="departement" class="form-control" required>
					<?php while ($p = $dep->fetch()) { ?>
						<option value="<?php echo h($p['iddepartement']); ?>" <?php echo (int)$p['iddepartement'] === (int)$d['iddepartement'] ? 'selected' : ''; ?>><?php echo h($p['departement']); ?></option>
					<?php } ?>
				</select>

				<label for="fichier">Remplacer le fichier (PDF, 10 Mo maximum)</label>
				<input id="fichier" type="file" name="fichier" class="form-control" accept="application/pdf,.pdf">

				<br>
				<input type="submit" name="modifierdoc" value="Modifier" class="btn btn-warning">
			</form>
			<hr>
		<?php } ?>

		<div class="table-responsive">
			<table class="table">
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
					<?php while ($r = $stm->fetch()) { ?>
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
									<input type="submit" name="edit" value="Modifier" class="btn btn-warning">
								</form>
								<form method="POST" class="d-inline" onsubmit="return confirm('Confirmer la suppression ?');">
									<?php echo champCsrf(); ?>
									<input type="hidden" name="iddoc" value="<?php echo h($r['iddocuments']); ?>">
									<input type="submit" name="supprimer" value="Supprimer" class="btn btn-danger">
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
