<?php
require 'config.php';

// Récupérer la commande
$order_id = (int)($_GET['commande'] ?? 0);
if (!$order_id) {
    header("Location: index.php");
    exit;
}

// Charger la commande depuis la BDD
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
$stmt->execute([$order_id, $_SESSION['user_id']]);
$commande = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$commande) {
    header("Location: index.php");
    exit;
}

$erreur = '';
$methode = $commande['payment_method'];

/* --- TRAITEMENT DU PAIEMENT FICTIF --- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {


    $stmt2 = $pdo->prepare("UPDATE orders SET status = 'payee' WHERE id = ?");
    $stmt2->execute([$order_id]);

    // Rediriger vers la confirmation
    header("Location: confirmation.php?commande=" . $order_id);
    exit;
}

$page_titre = "Paiement — EliteTech";
require 'header.php';
?>

<div class="container section">

    <h1 class="section-titre">Paiement</h1>
    <p class="section-sous">Commande #<?= $order_id ?> — Total : <strong><?= number_format($commande['total'], 0, '', ' ') ?> F CFA</strong></p>

    <div class="paiement-wrapper">
        <div class="card-panel" style="max-width:500px; margin:0 auto;">

            <?php if ($methode === 'mobile_money'): ?>
            <!-- PAIEMENT MOBILE MONEY -->
            <h2 class="h2-md">
                <i class="fa fa-mobile-alt" style="color:var(--or)"></i>
                Paiement Mobile Money
            </h2>
            <p class="muted" style="margin-bottom:20px;">
                Entrez votre numéro TMoney ou Flooz pour simuler le paiement.
            </p>

            <form method="POST">
                <div class="champ">
                    <label>Numéro de téléphone Mobile Money</label>
                    <input type="text" name="numero_mm" placeholder="Ex: 90 12 34 56" required>
                </div>
                <div class="champ">
                    <label>Code de confirmation</label>
                    <input type="text" name="code_mm" placeholder="Ex: 1234" required>
                    <small>Entrez n'importe quel code (paiement fictif)</small>
                </div>
                <button type="submit" class="btn btn-or btn-full">
                    <i class="fa fa-check"></i> Confirmer le paiement — <?= number_format($commande['total'], 0, '', ' ') ?> F
                </button>
            </form>

            <?php elseif ($methode === 'card'): ?>
            <!-- PAIEMENT CARTE BANCAIRE -->
            <h2 class="h2-md">
                <i class="fa fa-credit-card" style="color:var(--or)"></i>
                Paiement par Carte
            </h2>
            <p class="muted" style="margin-bottom:20px;">
                Entrez vos informations de carte pour simuler le paiement.
            </p>

            <form method="POST">
                <div class="champ">
                    <label>Numéro de carte</label>
                    <input type="text" name="numero_carte" placeholder="1234 5678 9012 3456"
                           maxlength="19" required>
                </div>
                <div class="grid-2">
                    <div class="champ">
                        <label>Date d'expiration</label>
                        <input type="text" name="expiration" placeholder="MM/AA" maxlength="5" required>
                    </div>
                    <div class="champ">
                        <label>CVV</label>
                        <input type="text" name="cvv" placeholder="123" maxlength="3" required>
                    </div>
                </div>
                <div class="champ">
                    <label>Nom sur la carte</label>
                    <input type="text" name="nom_carte" placeholder="KOFI MENSAH" required>
                </div>
                <button type="submit" class="btn btn-or btn-full">
                    <i class="fa fa-lock"></i> Payer <?= number_format($commande['total'], 0, '', ' ') ?> F CFA
                </button>
            </form>

            <?php else: ?>
                <p>Mode de paiement inconnu.</p>
                <a href="index.php" class="btn btn-or">Retour</a>
            <?php endif; ?>

            <p class="muted" style="text-align:center; margin-top:16px; font-size:12px;">
                <i class="fa fa-lock"></i> Paiement sécurisé (simulation)
            </p>
        </div>
    </div>

</div>

<?php require 'footer.php'; ?>
