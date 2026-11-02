<?php
require "connexion.php";
exigerConnexion();

$stm_departement = $bd->query("SELECT iddepartement, departement FROM departements ORDER BY departement");
$stm_categorie = $bd->query("SELECT idcategorie, categorie FROM categories ORDER BY categorie");
?>
<!DOCTYPE html>
<html>

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="stylesheet" type="text/css" href="css/bootstrap.min.css">
	<title>Ajouter un document</title>
</head>

<body>
	<?php include 'menu.php'; ?>

	<div class="container">
		<fieldset>
			<legend>Ajouter un document</legend>
			<form method="POST" action="listedoc.php" enctype="multipart/form-data">
				<?php echo champCsrf(); ?>
				<label for="date">Date</label>
				<input id="date" type="date" name="date" class="form-control" required>
				<label for="nomdoc">Nom du document</label>
				<input id="nomdoc" type="text" name="nomdoc" class="form-control" maxlength="200" required>
				<label for="categorie">Catégorie</label>
				<select id="categorie" class="form-control" name="categorie" required>
					<option value="">-Choisir-</option>
					<?php while ($categorie = $stm_categorie->fetch()) { ?>
						<option value="<?php echo h($categorie['idcategorie']); ?>"><?php echo h($categorie['categorie']); ?></option>
					<?php } ?>
				</select>
				<label for="departement">Département</label>
				<select id="departement" class="form-control" name="departement" required>
					<option value="">-Choisir-</option>
					<?php while ($departement = $stm_departement->fetch()) { ?>
						<option value="<?php echo h($departement['iddepartement']); ?>"><?php echo h($departement['departement']); ?></option>
					<?php } ?>
				</select>
				<label for="fichier">Charger le document (PDF, 10 Mo maximum)</label>
				<input id="fichier" type="file" name="fichier" class="form-control" accept="application/pdf,.pdf" required><br>
				<input type="submit" name="ajoute" value="AJOUTER" class="btn btn-success">
				<a href="listedoc.php" class="btn btn-danger float-end">ANNULER</a>
			</form>
		</fieldset>
	</div>
</body>

</html>
