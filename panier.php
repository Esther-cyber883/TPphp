<?php

require 'config.php';

// Le panier est stocké dans $_SESSION['panier']
// C'est un tableau : $_SESSION['panier'][id_produit] = ['quantite' => X, 'nom' => '...', 'prix' => Y]

$message = '';

/* --- ACTIONS DU PANIER --- */

// Ajouter un produit au panier
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'ajouter') {
    $id_produit = (int)($_POST['id_produit'] ?? 0);
    $quantite   = max(1, (int)($_POST['quantite'] ?? 1));

    if ($id_produit > 0) {
        // Chercher le produit dans la base de données
        $stmt = $pdo->prepare("SELECT id, name, price, stock_quantity, image_url FROM accessories WHERE id = ?");
        $stmt->execute([$id_produit]);
        $produit = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($produit) {
            // Vérifier le stock
            if ($produit['stock_quantity'] <= 0) {
                $message = "Ce produit est en rupture de stock.";
            } else {
                // Initialiser le panier si besoin
                if (!isset($_SESSION['panier'])) {
                    $_SESSION['panier'] = [];
                }

                // Si le produit est déjà dans le panier, augmenter la quantité
                if (isset($_SESSION['panier'][$id_produit])) {
                    $nouvelle_qte = $_SESSION['panier'][$id_produit]['quantite'] + $quantite;
                    // Ne pas dépasser le stock disponible
                    if ($nouvelle_qte > $produit['stock_quantity']) {
                        $nouvelle_qte = $produit['stock_quantity'];
                    }
                    $_SESSION['panier'][$id_produit]['quantite'] = $nouvelle_qte;
                } else {
                    // Ajouter le produit au panier
                    if ($quantite > $produit['stock_quantity']) {
                        $quantite = $produit['stock_quantity'];
                    }
                    $_SESSION['panier'][$id_produit] = [
                        'quantite'  => $quantite,
                        'nom'       => $produit['name'],
                        'prix'      => $produit['price'],
                        'image_url' => $produit['image_url']
                    ];
                }
                $message = "Produit ajouté au panier !";
            }
        }
    }
}

// Supprimer un produit du panier
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'supprimer') {
    $id_produit = (int)($_POST['id_produit'] ?? 0);
    if (isset($_SESSION['panier'][$id_produit])) {
        unset($_SESSION['panier'][$id_produit]);
        $message = "Produit retiré du panier.";
    }
}

// Modifier la quantité
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'modifier_quantite') {
    $id_produit = (int)($_POST['id_produit'] ?? 0);
    $quantite   = max(1, (int)($_POST['quantite'] ?? 1));

    if (isset($_SESSION['panier'][$id_produit])) {
        // Vérifier le stock
        $stmt = $pdo->prepare("SELECT stock_quantity FROM accessories WHERE id = ?");
        $stmt->execute([$id_produit]);
        $stock = $stmt->fetchColumn();

        if ($quantite > $stock) {
            $quantite = $stock;
        }
        $_SESSION['panier'][$id_produit]['quantite'] = $quantite;
    }
}

// Vider le panier
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'vider') {
    $_SESSION['panier'] = [];
    $message = "Le panier a été vidé.";
}

/* --- CALCUL DU TOTAL --- */
$total = 0;
$panier = $_SESSION['panier'] ?? [];
foreach ($panier as $item) {
    $total += $item['prix'] * $item['quantite'];
}

$page_titre = "Mon panier — EliteTech";
require 'header.php';
?>

<div class="container section">

    <h1 class="section-titre">Mon panier</h1>
    <p class="section-sous">
        <?php if (count($panier) > 0): ?>
            Vous avez <?= count($panier) ?> produit(s) dans votre panier.
        <?php else: ?>
            Votre panier est vide.
        <?php endif; ?>
    </p>

    <!-- Message flash -->
    <?php if ($message): ?>
        <div class="alerte alerte-ok"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <?php if (count($panier) > 0): ?>

    <!-- Tableau du panier -->
    <div class="overflow-auto">
        <table class="tableau">
            <thead>
                <tr>
                    <th>Image</th>
                    <th>Produit</th>
                    <th>Prix unitaire</th>
                    <th>Quantité</th>
                    <th>Sous-total</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($panier as $id_produit => $item): ?>
                <tr>
                    <!-- Image -->
                    <td>
                        <?php if ($item['image_url']): ?>
                            <img src="<?= htmlspecialchars($item['image_url']) ?>" class="thumb-48">
                        <?php else: ?>
                            <div class="thumb-placeholder"><i class="fa fa-box"></i></div>
                        <?php endif; ?>
                    </td>

                    <!-- Nom du produit -->
                    <td>
                        <a href="produit.php?id=<?= $id_produit ?>" class="table-link">
                            <?= htmlspecialchars($item['nom']) ?>
                        </a>
                    </td>

                    <!-- Prix unitaire -->
                    <td class="prix"><?= number_format($item['prix'], 0, '', ' ') ?> F</td>

                    <!-- Quantité (modifiable) -->
                    <td>
                        <form method="POST" class="inline-form">
                            <input type="hidden" name="action" value="modifier_quantite">
                            <input type="hidden" name="id_produit" value="<?= $id_produit ?>">
                            <input type="number" name="quantite" value="<?= $item['quantite'] ?>"
                                   min="1" style="width:60px;" onchange="this.form.submit()">
                        </form>
                    </td>

                    <!-- Sous-total -->
                    <td class="prix">
                        <?= number_format($item['prix'] * $item['quantite'], 0, '', ' ') ?> F
                    </td>

                    <!-- Supprimer -->
                    <td>
                        <form method="POST">
                            <input type="hidden" name="action" value="supprimer">
                            <input type="hidden" name="id_produit" value="<?= $id_produit ?>">
                            <button type="submit" class="btn btn-rouge btn-sm">
                                <i class="fa fa-trash"></i> Retirer
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Total et actions -->
    <div class="panier-footer">
        <div class="panier-total">
            Total : <strong><?= number_format($total, 0, '', ' ') ?> F CFA</strong>
        </div>
        <div class="inline-actions">
            <form method="POST" style="display:inline;">
                <input type="hidden" name="action" value="vider">
                <button type="submit" class="btn btn-contour btn-sm"
                        onclick="return confirm('Vider tout le panier ?');">
                    <i class="fa fa-trash"></i> Vider le panier
                </button>
            </form>
            <a href="index.php" class="btn btn-noir btn-sm">
                <i class="fa fa-arrow-left"></i> Continuer les achats
            </a>
            <a href="commander.php" class="btn btn-or">
                <i class="fa fa-check"></i> Commander
            </a>
        </div>
    </div>

    <?php else: ?>
        <!-- Panier vide -->
        <div class="vide">
            <i class="fa fa-shopping-cart"></i>
            <p class="vide-titre">Votre panier est vide</p>
            <p class="vide-texte">Parcourez la boutique pour ajouter des produits.</p>
            <a href="index.php" class="btn btn-or">Voir la boutique</a>
        </div>
    <?php endif; ?>

</div>

<?php require 'footer.php'; ?>
