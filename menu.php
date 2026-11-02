<?php
if (!function_exists('champCsrf')) {
	require "connexion.php";
}
exigerConnexion();
?>
<nav class="navbar bg-success">
	<img src="img/photo.jpeg" alt="Logo" class="navbar-brand text-white" style="width:5%; padding: auto;">
	<ul class="nav">
		<li class="nav-item"><a href="listecompte.php" class="nav-link text-white">Liste des Comptes</a></li>
		<li class="nav-item"><a href="ajoutercompte.php" class="nav-link text-white">Ajouter Compte</a></li>
		<li class="nav-item"><a href="ajouterdoc.php" class="nav-link text-white">Ajouter Document</a></li>
		<li class="nav-item"><a href="listedoc.php" class="nav-link text-white">Liste des Documents</a></li>
		<li class="nav-item"><a href="rechercher.php" class="nav-link text-white">Rechercher un Document</a></li>
		<li class="nav-item">
			<form method="POST" action="deconnexion.php">
				<?php echo champCsrf(); ?>
				<button type="submit" class="nav-link text-white btn btn-danger">Deconnexion</button>
			</form>
		</li>
	</ul>
</nav>
