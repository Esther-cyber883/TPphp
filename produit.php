<?php
require 'config.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header("Location: index.php"); exit; }

$stmt = $pdo->prepare("
    SELECT a.*, c.name as category_name
    FROM accessories a
    LEFT JOIN categories c ON a.category_id = c.id
    WHERE a.id = ?
");
$stmt->execute([$id]);
$produit = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$produit) { header("Location: index.php"); exit; }

// Produits similaires
$stmtSim = $pdo->prepare("
    SELECT a.*, c.name as category_name
    FROM accessories a
    LEFT JOIN categories c ON a.category_id = c.id
    WHERE a.category_id = ? AND a.id != ?
    LIMIT 4
");
$stmtSim->execute([$produit['category_id'], $id]);
$similaires = $stmtSim->fetchAll(PDO::FETCH_ASSOC);

$message = null;

// Actions admin depuis la page produit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    $action = $_POST['action'] ?? '';

    if ($action === 'ajouter_stock') {
        $ajout = max(1, (int)($_POST['ajout'] ?? 1));
        $pdo->prepare("UPDATE accessories SET stock_quantity = stock_quantity + ? WHERE id = ?")
            ->execute([$ajout, $id]);
        $produit['stock_quantity'] += $ajout;
        $message = ['type' => 'ok', 'texte' => "$ajout unité(s) ajoutée(s). Stock : {$produit['stock_quantity']}."];
    }

    if ($action === 'retirer_stock') {
        $retrait = max(1, (int)($_POST['retrait'] ?? 1));
        $nouveau = max(0, $produit['stock_quantity'] - $retrait);
        $pdo->prepare("UPDATE accessories SET stock_quantity = ? WHERE id = ?")
            ->execute([$nouveau, $id]);
        $produit['stock_quantity'] = $nouveau;
        $message = ['type' => 'info', 'texte' => "$retrait unité(s) retirée(s). Stock : $nouveau."];
    }

    if ($action === 'modifier_stock') {
        $nouveau = max(0, (int)($_POST['stock_quantity'] ?? 0));
        $pdo->prepare("UPDATE accessories SET stock_quantity = ? WHERE id = ?")
            ->execute([$nouveau, $id]);
        $produit['stock_quantity'] = $nouveau;
        $message = ['type' => 'ok', 'texte' => "Stock défini à $nouveau unité(s)."];
    }

    if ($action === 'supprimer') {
        $pdo->prepare("DELETE FROM accessories WHERE id = ?")->execute([$id]);
        header("Location: index.php");
        exit;
    }
}

$page_titre = htmlspecialchars($produit['name']) . " — EliteTech";
require 'header.php';
?>

<div class="container section">

    <!-- Breadcrumb -->
    <div class="breadcrumb">
        <a href="index.php"> Boutique</a>
        <?php if ($produit['category_name']): ?>
            <span>&rsaquo;</span>
            <a href="index.php?categorie=<?= $produit['category_id'] ?>">
                <?= htmlspecialchars($produit['category_name']) ?>
            </a>
        <?php endif; ?>
        <span>&rsaquo;</span>
        <span><?= htmlspecialchars($produit['name']) ?></span>
    </div>

    <!-- Message flash -->
    <?php if ($message): ?>
        <div class="alerte alerte-<?= $message['type'] === 'ok' ? 'ok' : ($message['type'] === 'err' ? 'err' : 'info') ?>">
            
            <?= htmlspecialchars($message['texte']) ?>
        </div>
    <?php endif; ?>

    <!-- Détail produit -->
    <div class="detail-grid">

        <!-- Image -->
        <div>
            <?php if ($produit['image_url']): ?>
                <img class="detail-img"
                     src="<?= htmlspecialchars($produit['image_url']) ?>"
                     alt="<?= htmlspecialchars($produit['name']) ?>">
            <?php else: ?>
                <div class="detail-img-placeholder"><i class="fa fa-box"></i></div>
            <?php endif; ?>
        </div>

        <!-- Infos -->
        <div class="detail-corps">

            <?php if ($produit['brand']): ?>
                <p class="detail-marque"><?= htmlspecialchars($produit['brand']) ?></p>
            <?php endif; ?>

            <h1 class="detail-nom"><?= htmlspecialchars($produit['name']) ?></h1>

            <div class="detail-prix">
                <?= number_format($produit['price'], 0, '', ' ') ?>
                <small>F CFA</small>
            </div>

            <div class="detail-info">
                <div class="info-item">
                    <label>Disponibilité</label>
                    <span>
                        <?php if ($produit['stock_quantity'] <= 0): ?>
                            <span class="badge badge-zero">Épuisé</span>
                        <?php elseif ($produit['stock_quantity'] <= 5): ?>
                            <span class="badge badge-bas"><?= $produit['stock_quantity'] ?> restant(s)</span>
                        <?php else: ?>
                            <span class="badge badge-stock"><?= $produit['stock_quantity'] ?> en stock</span>
                        <?php endif; ?>
                    </span>
                </div>
                <?php if ($produit['category_name']): ?>
                <div class="info-item">
                    <label>Catégorie</label>
                    <span><?= htmlspecialchars($produit['category_name']) ?></span>
                </div>
                <?php endif; ?>
                <?php if ($produit['shelf_location']): ?>
                <div class="info-item">
                    <label>Emplacement</label>
                    <span><?= htmlspecialchars($produit['shelf_location']) ?></span>
                </div>
                <?php endif; ?>
                <?php if ($produit['version']): ?>
                <div class="info-item">
                    <label>Version</label>
                    <span><?= htmlspecialchars($produit['version']) ?></span>
                </div>
                <?php endif; ?>
                <?php if ($produit['capacity']): ?>
                <div class="info-item">
                    <label>Capacité</label>
                    <span><?= htmlspecialchars($produit['capacity']) ?></span>
                </div>
                <?php endif; ?>
            </div>

            <!-- Description -->
            <?php if ($produit['description']): ?>
            <div class="produit-description">
                <h3>Description</h3>
                <p><?= nl2br(htmlspecialchars($produit['description'])) ?></p>
            </div>
            <?php endif; ?>

            <!-- Bouton ajouter au panier (pour les clients connectés) -->
            <?php if (isset($_SESSION['user_id'])): ?>
                <?php if ($produit['stock_quantity'] > 0): ?>
                    <form method="POST" action="panier.php" class="panier-ajout-form">
                        <input type="hidden" name="action" value="ajouter">
                        <input type="hidden" name="id_produit" value="<?= $produit['id'] ?>">
                        <div class="inline-actions">
                            <input type="number" name="quantite" value="1" min="1"
                                   max="<?= $produit['stock_quantity'] ?>"
                                   style="width:70px; padding:8px; border:1px solid var(--gris2);">
                            <button type="submit" class="btn btn-or">
                                <i class="fa fa-cart-plus"></i> Ajouter au panier
                            </button>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="alerte alerte-err">
                        <i class="fa fa-ban"></i> Rupture de stock — Ce produit n'est plus disponible.
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <p class="muted" style="margin-top:12px;">
                    <a href="login.php" class="link-or">Connectez-vous</a> pour ajouter au panier.
                </p>
            <?php endif; ?>

            <!-- Bloc admin -->
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
            <div class="admin-produit-bloc">
                <p class="admin-produit-titre"> Gestion du stock</p>

                <!-- Ajouter -->
                <div class="admin-stock-ligne">
                    <form method="POST" class="inline-form">
                        <input type="hidden" name="action" value="ajouter_stock">
                        <input type="number" name="ajout" value="1" min="1">
                        <button type="submit" class="btn btn-or btn-sm">
                             Ajouter
                        </button>
                    </form>
                    <form method="POST" class="inline-form">
                        <input type="hidden" name="action" value="retirer_stock">
                        <input type="number" name="retrait" value="1" min="1">
                        <button type="submit" class="btn btn-contour btn-sm">
                             Retirer
                        </button>
                    </form>
                </div>

                <!-- Définir -->
                <form method="POST" class="admin-stock-ligne mb-24">
                    <input type="hidden" name="action" value="modifier_stock">
                    <input type="number" name="stock_quantity" value="<?= (int)$produit['stock_quantity'] ?>" min="0" class="inline-input">
                    <button type="submit" class="btn btn-noir btn-sm">
                         Définir le stock
                    </button>
                </form>

                 <div class="inline-actions">
                    <a href="admin.php?modifier=<?= $id ?>" class="btn btn-contour btn-sm">
                         Modifier ce produit
                    </a>
                    <form method="POST" onsubmit="return confirm('Supprimer ce produit définitivement ?');">
                        <input type="hidden" name="action" value="supprimer">
                        <button type="submit" class="btn btn-rouge btn-sm">
                             Supprimer
                        </button>
                    </form>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </div>

    <!-- Produits similaires -->
    <?php if (!empty($similaires)): ?>
    <div class="mt-24">
        <div class="section-titre">Produits similaires</div>
        <p class="section-sous">Dans la même catégorie</p>
        <div class="grille">
            <?php foreach ($similaires as $p): ?>
            <div class="carte">
                <a href="produit.php?id=<?= $p['id'] ?>">
                    <?php if ($p['image_url']): ?>
                        <img class="carte-img"
                             src="<?= htmlspecialchars($p['image_url']) ?>"
                             alt="<?= htmlspecialchars($p['name']) ?>">
                    <?php else: ?>
                        <div class="carte-img-placeholder"><i class="fa fa-box"></i></div>
                    <?php endif; ?>
                </a>
                <div class="carte-corps">
                    <?php if ($p['brand']): ?>
                        <p class="carte-marque"><?= htmlspecialchars($p['brand']) ?></p>
                    <?php endif; ?>
                    <h3 class="carte-nom">
                        <a href="produit.php?id=<?= $p['id'] ?>" class="link-reset">
                            <?= htmlspecialchars($p['name']) ?>
                        </a>
                    </h3>
                    <div class="carte-pied">
                        <span class="carte-prix"><?= number_format($p['price'], 0, '', ' ') ?> F</span>
                    </div>
                    <a href="produit.php?id=<?= $p['id'] ?>" class="btn btn-noir btn-sm btn-full mt-12">Voir</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

</div>

<?php require 'footer.php'; ?>
