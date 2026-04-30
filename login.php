<?php
require 'config.php';

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login    = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    if (!$login || !$password) {
        $erreur = "Veuillez remplir tous les champs.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? OR username = ? LIMIT 1");
        $stmt->execute([$login, $login]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            if ($user['banned'] == 1) {
                $erreur = "Votre compte a été suspendu. Contactez l'administrateur.";
            } else {
                $_SESSION['user_id']   = $user['id'];
                $_SESSION['user_name'] = $user['name'] ?? $user['username'] ?? '';
                $_SESSION['role']      = $user['role'];
                header("Location: " . ($user['role'] === 'admin' ? 'admin.php' : 'index.php'));
                exit;
            }
        } else {
            $erreur = "Email ou mot de passe incorrect.";
        }
    }
}

$page_titre = "Connexion — EliteTech";
require 'header.php';
?>

<div class="auth-page-wrapper">

    <div class="auth-box">

        <h1 class="auth-titre">Connexion</h1>
        <p class="auth-sous">Entrez vos informations pour accéder à la boutique</p>

        <div class="auth-carte">

            <?php if ($erreur): ?>
                <div class="msg msg-err">
                     <?= htmlspecialchars($erreur) ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['inscrit'])): ?>
                <div class="msg msg-ok">
                     Compte créé ! Connectez-vous maintenant.
                </div>
            <?php endif; ?>

            <form method="POST" action="">

                <div class="champ">
                    <label for="email">Email ou nom d'utilisateur</label>
                    <input
                        type="text"
                        id="email"
                        name="email"
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                        placeholder="Email ou nom d'utilisateur"
                        required
                        autocomplete="username"
                    >
                </div>

                <div class="champ">
                    <label for="password">Mot de passe</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Votre mot de passe"
                        required
                        autocomplete="current-password"
                    >
                </div>

                 <button type="submit" class="btn btn-or btn-full">
                     Se connecter
                 </button>

            </form>

            <div class="auth-sep">
                Pas encore de compte ? <a href="inscription.php">Créer un compte</a>
            </div>

        </div>

        <p class="auth-footer-note">
            <a href="accueil.php" class="link-reset">Retour à l'accueil</a>
        </p>

    </div>
</div>

<?php require 'footer.php'; ?>
