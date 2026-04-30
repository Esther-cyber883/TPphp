<?php

require 'config.php';

// Vérifier que le panier n'est pas vide
$panier = $_SESSION['panier'] ?? [];
if (empty($panier)) {
    header("Location: panier.php");
    exit;
}

// Calculer le total
$total = 0;
foreach ($panier as $item) {
    $total += $item['prix'] * $item['quantite'];
}

$erreur = '';

/* --- TRAITEMENT DU FORMULAIRE --- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer les données du formulaire
    $nom      = trim($_POST['nom'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $ville    = trim($_POST['ville'] ?? '');
    $adresse  = trim($_POST['adresse'] ?? '');
    $paiement = $_POST['paiement'] ?? 'cash';

    // Vérification simple
    if (!$nom || !$telephone || !$ville || !$adresse) {
        $erreur = "Veuillez remplir tous les champs.";
    } else {
        // 1. Créer la commande dans la base de données
        $stmt = $pdo->prepare("
            INSERT INTO orders (user_id, total, address_name, address_phone, address_city, address_detail, payment_method, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'en_attente')
        ");
        $stmt->execute([
            $_SESSION['user_id'],
            $total,
            $nom,
            $telephone,
            $ville,
            $adresse,
            $paiement
        ]);

        // Récupérer l'ID de la commande qu'on vient de créer
        $order_id = $pdo->lastInsertId();

        // 2. Ajouter chaque produit du panier dans order_items
        foreach ($panier as $id_produit => $item) {
            $stmt2 = $pdo->prepare("
                INSERT INTO order_items (order_id, accessory_id, quantity, unit_price)
                VALUES (?, ?, ?, ?)
            ");
            $stmt2->execute([
                $order_id,
                $id_produit,
                $item['quantite'],
                $item['prix']
            ]);

            // 3. Diminuer le stock du produit
            $stmt3 = $pdo->prepare("
                UPDATE accessories
                SET stock_quantity = stock_quantity - ?
                WHERE id = ? AND stock_quantity >= ?
            ");
            $stmt3->execute([
                $item['quantite'],
                $id_produit,
                $item['quantite']
            ]);
        }

        // 4. Vider le panier
        $_SESSION['panier'] = [];

        // 5. Rediriger vers le paiement ou la confirmation
        if ($paiement === 'cash') {
            // Paiement en espèces = pas de page paiement, directement la confirmation
            header("Location: confirmation.php?commande=" . $order_id);
            exit;
        } else {
            // Paiement Mobile Money ou Carte = page de paiement fictif
            header("Location: paiement.php?commande=" . $order_id);
            exit;
        }
    }
}

$page_titre = "Commander — EliteTech";
require 'header.php';
?>

<div class="container section">

    <h1 class="section-titre">Finaliser la commande</h1>
    <p class="section-sous">Remplissez vos informations de livraison et choisissez votre mode de paiement.</p>

    <!-- Message d'erreur -->
    <?php if ($erreur): ?>
        <div class="alerte alerte-err"><?= htmlspecialchars($erreur) ?></div>
    <?php endif; ?>

    <div class="commande-grid">

        <!-- FORMULAIRE (colonne gauche) -->
        <div class="card-panel">
            <h2 class="h2-md">Informations de livraison</h2>

            <form method="POST" action="commander.php">

                <div class="champ">
                    <label for="nom">Nom complet *</label>
                    <input type="text" id="nom" name="nom"
                           value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>"
                           placeholder="Ex: Kofi Mensah" required>
                </div>

                <div class="champ">
                    <label for="telephone">Numéro de téléphone *</label>
                    <input type="text" id="telephone" name="telephone"
                           value="<?= htmlspecialchars($_POST['telephone'] ?? '') ?>"
                           placeholder="Ex: +228 90 12 34 56" required>
                </div>

                <div class="champ">
                    <label for="ville">Ville *</label>
                    <input type="text" id="ville" name="ville"
                           value="<?= htmlspecialchars($_POST['ville'] ?? 'Lomé') ?>"
                           placeholder="Ex: Lomé" required>
                </div>

                <div class="champ">
                    <label for="adresse">Adresse détaillée *</label>
                    <textarea id="adresse" name="adresse" placeholder="Quartier, rue, repère..."
                              required><?= htmlspecialchars($_POST['adresse'] ?? '') ?></textarea>
                </div>

                <h2 class="h2-md" style="margin-top:24px;">Mode de paiement</h2>

                <div class="paiement-options">
                    <label class="paiement-option">
                        <input type="radio" name="paiement" value="cash" checked>
                        <span class="paiement-label">
                            <i class="fa fa-money-bill-wave"></i>
                            <strong>Espèces</strong>
                            <small>Payer à la livraison</small>
                        </span>
                    </label>

                    <label class="paiement-option">
                        <input type="radio" name="paiement" value="mobile_money">
                        <span class="paiement-label">
                            <i class="fa fa-mobile-alt"></i>
                            <strong>Mobile Money</strong>
                            <small>TMoney / Flooz</small>
                        </span>
                    </label>

                    <label class="paiement-option">
                        <input type="radio" name="paiement" value="card">
                        <span class="paiement-label">
                            <i class="fa fa-credit-card"></i>
                            <strong>Carte bancaire</strong>
                            <small>Visa / Mastercard</small>
                        </span>
                    </label>
                </div>

                <button type="submit" class="btn btn-or btn-full" style="margin-top:24px;">
                    <i class="fa fa-check"></i> Valider la commande
                </button>
            </form>
        </div>

        <!-- RÉCAPITULATIF (colonne droite) -->
        <div class="card-panel">
            <h2 class="h2-md">Récapitulatif</h2>

            <?php foreach ($panier as $id_produit => $item): ?>
            <div class="recap-ligne">
                <div class="recap-info">
                    <strong><?= htmlspecialchars($item['nom']) ?></strong>
                    <span class="muted">x <?= $item['quantite'] ?></span>
                </div>
                <div class="recap-prix">
                    <?= number_format($item['prix'] * $item['quantite'], 0, '', ' ') ?> F
                </div>
            </div>
            <?php endforeach; ?>

            <div class="recap-total">
                <strong>Total à payer</strong>
                <strong><?= number_format($total, 0, '', ' ') ?> F CFA</strong>
            </div>

            <a href="panier.php" class="btn btn-contour btn-sm btn-full" style="margin-top:16px;">
                <i class="fa fa-arrow-left"></i> Modifier le panier
            </a>
        </div>

    </div>

</div>

<?php require 'footer.php'; ?>
