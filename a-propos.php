<?php
require 'config.php';
$page_titre = "À propos — EliteTech";
require 'header.php';
?>

<div class="container section">
    <h1 class="section-titre">À propos de EliteTech</h1>
    <p class="section-sous">Une boutique locale pour l'électronique à Lomé.</p>

    <div class="content-card">
        <h2>Notre mission</h2>
        <p>EliteTech propose une sélection d'accessoires et de smartphones pratiques pour les clients togolais. Le site permet de consulter le catalogue, ajouter des produits au panier, valider une commande et suivre son historique.</p>
    </div>

    <div class="content-card mt-24">
        <h2>Fonctionnalités</h2>
        <ul class="lead-ul">
            <li>Inscription et connexion sécurisées</li>
            <li>Catalogue produit filtrable par catégorie</li>
            <li>Ajout au panier, modification et suppression d'articles</li>
            <li>Validation de commande avec informations de livraison</li>
            <li>Historique des commandes côté client</li>
            <li>Espace administrateur pour gérer produits, catégories et commandes</li>
        </ul>
    </div>

    <div class="content-card mt-24">
        <h2>Tech stack</h2>
        <p>PHP pur, MySQL via PDO, HTML5/CSS3, JavaScript léger.</p>
        <p>Le projet est pensé pour être déployé localement sous WAMP/XAMPP.</p>
    </div>

    <div class="content-card mt-24">
        <h2>Contact</h2>
        <p>Pour toute question ou support, utilisez les informations dans le pied de page.</p>
    </div>
</div>

<?php require 'footer.php'; ?>