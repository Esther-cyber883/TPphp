<?php
require 'config.php';

// Récupérer toutes les commandes du client connecté
$stmt = $pdo->prepare("
    SELECT o.*,
           (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as nb_articles
    FROM orders o
    WHERE o.user_id = ?
    ORDER BY o.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$commandes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Si on veut voir le détail d'une commande
$detail = null;
$articles = [];
if (isset($_GET['detail'])) {
    $detail_id = (int)$_GET['detail'];
    $stmt2 = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
    $stmt2->execute([$detail_id, $_SESSION['user_id']]);
    $detail = $stmt2->fetch(PDO::FETCH_ASSOC);

    if ($detail) {
        $stmt3 = $pdo->prepare("
            SELECT oi.*, a.name, a.image_url
            FROM order_items oi
            LEFT JOIN accessories a ON oi.accessory_id = a.id
            WHERE oi.order_id = ?
        ");
        $stmt3->execute([$detail_id]);
        $articles = $stmt3->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Textes des statuts
$statuts = [
    'en_attente' => 'En attente',
    'payee'      => 'Payée',
    'en_cours'   => 'En cours',
    'livree'     => 'Livrée',
    'annulee'    => 'Annulée'
];

// Textes des modes de paiement
$modes_paiement = [
    'cash'         => 'Espèces',
    'mobile_money' => 'Mobile Money',
    'card'         => 'Carte bancaire'
];

$page_titre = "Mes commandes — EliteTech";
require 'header.php';
?>

<div class="container section">

    <h1 class="section-titre">Mes commandes</h1>
    <p class="section-sous">Retrouvez ici l'historique de toutes vos commandes.</p>

    <?php if ($detail): ?>
    <!--  DÉTAIL D'UNE COMMANDE  -->
    <div class="card-panel" style="max-width:800px;">

        <div class="flex-between align-center mb-20">
            <h2 class="h2-md">Commande #<?= $detail['id'] ?></h2>
            <a href="mes-commandes.php" class="btn btn-contour btn-sm">
                <i class="fa fa-arrow-left"></i> Retour
            </a>
        </div>

        <table class="tableau">
            <tr>
                <td class="muted">Date</td>
                <td><?= date('d/m/Y à H:i', strtotime($detail['created_at'])) ?></td>
            </tr>
            <tr>
                <td class="muted">Statut</td>
                <td>
                    <span class="badge badge-<?= $detail['status'] ?>">
                        <?= $statuts[$detail['status']] ?? $detail['status'] ?>
                    </span>
                </td>
            </tr>
            <tr>
                <td class="muted">Paiement</td>
                <td><?= $modes_paiement[$detail['payment_method']] ?? $detail['payment_method'] ?></td>
            </tr>
            <tr>
                <td class="muted">Adresse</td>
                <td>
                    <?= htmlspecialchars($detail['address_name']) ?><br>
                    <?= htmlspecialchars($detail['address_phone']) ?><br>
                    <?= htmlspecialchars($detail['address_city']) ?> — <?= htmlspecialchars($detail['address_detail']) ?>
                </td>
            </tr>
        </table>

        <h3 style="margin:24px 0 12px; font-size:16px;">Articles</h3>
        <table class="tableau">
            <thead>
                <tr>
                    <th>Produit</th>
                    <th>Qté</th>
                    <th>Prix unitaire</th>
                    <th>Sous-total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($articles as $art): ?>
                <tr>
                    <td>
                        <?php if ($art['name']): ?>
                            <a href="produit.php?id=<?= $art['accessory_id'] ?>" class="table-link">
                                <?= htmlspecialchars($art['name']) ?>
                            </a>
                        <?php else: ?>
                            <span class="muted">Produit supprimé</span>
                        <?php endif; ?>
                    </td>
                    <td><?= $art['quantity'] ?></td>
                    <td><?= number_format($art['unit_price'], 0, '', ' ') ?> F</td>
                    <td class="prix"><?= number_format($art['unit_price'] * $art['quantity'], 0, '', ' ') ?> F</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="recap-total" style="margin-top:12px;">
            <strong>Total</strong>
            <strong><?= number_format($detail['total'], 0, '', ' ') ?> F CFA</strong>
        </div>
    </div>

    <?php elseif (empty($commandes)): ?>
    <!-- Aucune commande -->
    <div class="vide">
        <i class="fa fa-box-open"></i>
        <p class="vide-titre">Aucune commande</p>
        <p class="vide-texte">Vous n'avez pas encore passé de commande.</p>
        <a href="index.php" class="btn btn-or">Voir la boutique</a>
    </div>

    <?php else: ?>
    <!--  LISTE DES COMMANDES  -->
    <div class="overflow-auto">
        <table class="tableau">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Date</th>
                    <th>Articles</th>
                    <th>Total</th>
                    <th>Paiement</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($commandes as $cmd): ?>
                <tr>
                    <td><?= $cmd['id'] ?></td>
                    <td><?= date('d/m/Y', strtotime($cmd['created_at'])) ?></td>
                    <td><?= $cmd['nb_articles'] ?> article(s)</td>
                    <td class="prix"><?= number_format($cmd['total'], 0, '', ' ') ?> F</td>
                    <td><?= $modes_paiement[$cmd['payment_method']] ?? $cmd['payment_method'] ?></td>
                    <td>
                        <span class="badge badge-<?= $cmd['status'] ?>">
                            <?= $statuts[$cmd['status']] ?? $cmd['status'] ?>
                        </span>
                    </td>
                    <td>
                        <a href="mes-commandes.php?detail=<?= $cmd['id'] ?>" class="btn btn-noir btn-sm">
                            <i class="fa fa-eye"></i> Voir
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

</div>

<?php require 'footer.php'; ?>
