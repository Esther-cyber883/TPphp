<?php
require 'config.php';
$page_titre = "EliteTech — Accueil";
require 'header.php';
?>

<!-- HERO -->
<section class="hero">
    <img class="hero-img"
         src="images/hero.webp"
         alt="Boutique EliteTech">
    <div class="hero-overlay"></div>
    <div class="hero-content">
        <h1>
            L'électronique de qualité,<br>
            <em>à Lomé</em>
        </h1>
        <p>Téléphones, coques, chargeurs et accessoires. Une boutique simple, des produits sélectionnés.</p>
        <div class="hero-actions">
            <a href="index.php" class="btn btn-or btn-lg">Voir la boutique</a>
            <?php if (!isset($_SESSION['user_id'])): ?>
                <a href="inscription.php" class="btn btn-lg btn-contour">
                    Créer un compte
                </a>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- AVANTAGES -->
<section class="section">
    <div class="container">
        <div class="section-titre">Pourquoi EliteTech ?</div>
        <p class="section-sous">Une boutique pensée pour aller droit au but.</p>

        <div class="feature-grid">
            <div class="feature-card">
                <div class="card-icon"></div>
                <h3>Stock visible</h3>
                <p>Chaque produit affiche son stock en temps réel. Vous savez toujours ce qui est disponible avant de vous déplacer.</p>
            </div>
            <div class="feature-card">
                <div class="card-icon"></div>
                <h3>Catégories claires</h3>
                <p>Coques, chargeurs, écouteurs, câbles — retrouvez ce que vous cherchez en un clic grâce aux filtres.</p>
            </div>
            <div class="feature-card">
                <div class="card-icon"></div>
                <h3>Boutique locale</h3>
                <p>Située à Lomé, notre équipe est disponible pour vous conseiller directement sur place ou par téléphone.</p>
            </div>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="section cta-section">
    <div class="container">
        <div class="section-titre">Commencez maintenant</div>
        <p class="section-sous">Explorez notre catalogue, filtrez par catégorie et consultez les détails de chaque produit.</p>
        <div class="inline-actions">
            <a href="index.php" class="btn btn-or">Parcourir la boutique</a>
            <a href="a-propos.php" class="btn btn-contour">En savoir plus</a>
        </div>
    </div>
</section>

<?php require 'footer.php'; ?>
