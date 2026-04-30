<?php

require 'config.php';

// Récupérer la commande
$order_id = (int)($_GET['commande'] ?? 0);
if (!$order_id) {
    header("Location: index.php");
    exit;
}

// Charger la commande
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
$stmt->execute([$order_id, $_SESSION['user_id']]);
$commande = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$commande) {
    header("Location: index.php");
    exit;
}

// Charger les articles de la commande
$stmt2 = $pdo->prepare("
    SELECT oi.*, a.name, a.image_url
    FROM order_items oi
    LEFT JOIN accessories a ON oi.accessory_id = a.id
    WHERE oi.order_id = ?
");
$stmt2->execute([$order_id]);
$articles = $stmt2->fetchAll(PDO::FETCH_ASSOC);

// Texte du statut
$statuts = [
    'en_attente' => 'En attente',
    'payee'      => 'Payée',
    'en_cours'   => 'En cours de livraison',
    'livree'     => 'Livrée',
    'annulee'    => 'Annulée'
];

$page_titre = "Commande confirmée — EliteTech";
require 'header.php';
?>

<div class="container section">

    <div class="confirmation-box">
        <div class="confirmation-icon">
            <i class="fa fa-check-circle"></i>
        </div>
        <h1>Commande confirmée !</h1>
        <p class="muted">Merci pour votre achat. Voici le résumé de votre commande.</p>
    </div>

    <div class="card-panel" style="max-width:700px; margin:0 auto;">

        <!-- Infos commande -->
        <table class="tableau">
            <tr>
                <td class="muted">Numéro de commande</td>
                <td><strong>#<?= $order_id ?></strong></td>
            </tr>
            <tr>
                <td class="muted">Date</td>
                <td><?= date('d/m/Y à H:i', strtotime($commande['created_at'])) ?></td>
            </tr>
            <tr>
                <td class="muted">Statut</td>
                <td>
                    <span class="badge badge-<?= $commande['status'] ?>">
                        <?= $statuts[$commande['status']] ?? $commande['status'] ?>
                    </span>
                </td>
            </tr>
            <tr>
                <td class="muted">Paiement</td>
                <td>
                    <?php
                    $modes = ['cash' => 'Espèces', 'mobile_money' => 'Mobile Money', 'card' => 'Carte bancaire'];
                    echo $modes[$commande['payment_method']] ?? $commande['payment_method'];
                    ?>
                </td>
            </tr>
            <tr>
                <td class="muted">Livraison</td>
                <td>
                    <?= htmlspecialchars($commande['address_name']) ?><br>
                    <?= htmlspecialchars($commande['address_phone']) ?><br>
                    <?= htmlspecialchars($commande['address_city']) ?> — <?= htmlspecialchars($commande['address_detail']) ?>
                </td>
            </tr>
        </table>

        <!-- Articles commandés -->
        <h3 style="margin:24px 0 12px; font-size:16px;">Articles commandés</h3>
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
                    <td><?= htmlspecialchars($art['name'] ?? 'Produit supprimé') ?></td>
                    <td><?= $art['quantity'] ?></td>
                    <td><?= number_format($art['unit_price'], 0, '', ' ') ?> F</td>
                    <td class="prix"><?= number_format($art['unit_price'] * $art['quantity'], 0, '', ' ') ?> F</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="recap-total" style="margin-top:16px;">
            <strong>Total</strong>
            <strong><?= number_format($commande['total'], 0, '', ' ') ?> F CFA</strong>
        </div>

        <div class="inline-actions" style="margin-top:24px; justify-content:center;">
            <a href="mes-commandes.php" class="btn btn-noir btn-sm">
                <i class="fa fa-list"></i> Mes commandes
            </a>
            <a href="index.php" class="btn btn-or btn-sm">
                <i class="fa fa-store"></i> Continuer les achats
            </a>
        </div>

    </div>

</div>

<?php require 'footer.php'; ?>
