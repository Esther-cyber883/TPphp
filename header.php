<?php if (session_status() === PHP_SESSION_NONE) session_start(); ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_titre ?? 'EliteTech — Boutique Électronique' ?></title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<header class="site-header">
    <div class="header-main container">
        <a href="accueil.php" class="logo">Elite<span>Tech</span></a>

        <nav class="nav-links">
            <a href="accueil.php"  class="<?= basename($_SERVER['PHP_SELF']) === 'accueil.php'  ? 'actif' : '' ?>">Accueil</a>
            <a href="index.php"    class="<?= basename($_SERVER['PHP_SELF']) === 'index.php'    ? 'actif' : '' ?>">Boutique</a>
            <a href="a-propos.php" class="<?= basename($_SERVER['PHP_SELF']) === 'a-propos.php' ? 'actif' : '' ?>">À propos</a>
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                <a href="admin.php" class="<?= basename($_SERVER['PHP_SELF']) === 'admin.php' ? 'actif' : '' ?>">Admin</a>
            <?php endif; ?>
        </nav>

        <div class="nav-user">
            <?php if (isset($_SESSION['user_id'])): ?>
                <?php
                $nb_panier = 0;
                if (isset($_SESSION['panier'])) {
                    foreach ($_SESSION['panier'] as $item) {
                        $nb_panier += $item['quantite'];
                    }
                }
                ?>
                <a href="panier.php" class="btn-panier" title="Mon panier">
                    <i class="fa fa-shopping-cart"></i>
                    <?php if ($nb_panier > 0): ?>
                        <span class="panier-badge"><?= $nb_panier ?></span>
                    <?php endif; ?>
                </a>
                <span class="nav-username"><?= htmlspecialchars($_SESSION['user_name'] ?? $_SESSION['username'] ?? '') ?></span>
                <a href="logout.php" class="btn-nav-deco">Déconnexion</a>
            <?php else: ?>
                <a href="login.php" class="btn-nav-co">Connexion</a>
            <?php endif; ?>
        </div>
    </div>
</header>

<main>
