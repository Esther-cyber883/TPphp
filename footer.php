</main>

<footer class="site-footer">
    <div class="footer-inner container">
        <div class="footer-brand">
            <span class="footer-logo-txt">Elite<span>Tech</span></span>
            <p class="footer-tagline">
                Vente de téléphones et accessoires électroniques à Lomé.
                Des produits de qualité, un service sérieux.
            </p>
        </div>

        <div>
            <span class="footer-col-titre">Navigation</span>
            <nav class="footer-nav">
                <a href="accueil.php">Accueil</a>
                <a href="index.php">Boutique</a>
                <a href="a-propos.php">À propos</a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="panier.php">Mon panier</a>
                    <a href="mes-commandes.php">Mes commandes</a>
                <?php else: ?>
                    <a href="login.php">Connexion</a>
                    <a href="inscription.php">Créer un compte</a>
                <?php endif; ?>
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                    <a href="admin.php">Administration</a>
                <?php endif; ?>
            </nav>
        </div>

        <div>
            <span class="footer-col-titre">Contact</span>
            <ul class="footer-contact">
                <li><i class="fas fa-map-marker-alt"></i> Lomé, Togo</li>
                <li><i class="fas fa-phone"></i> +228 92 96 05 63</li>
                <li><i class="fas fa-envelope"></i> contact@elitetech.tg</li>
                <li><i class="fas fa-clock"></i> Lun – Sam, 8h à 18h</li>
            </ul>
        </div>
    </div>

    <div class="footer-bas">
        <p>&copy; <?= date('Y') ?> EliteTech &mdash; Tous droits réservés. Lomé, Togo.</p>
    </div>

</footer>

</body>
</html>
