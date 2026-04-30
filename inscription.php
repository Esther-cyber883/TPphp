<?php
require 'config.php';

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username  = strtolower(trim($_POST['username'] ?? ''));
    $name      = trim($_POST['name'] ?? '');
    $email     = strtolower(trim($_POST['email'] ?? ''));
    $password  = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';

    if (!$username || !$name || !$email || !$password || !$password2) {
        $erreur = "Tous les champs sont obligatoires.";
    } elseif (strlen($username) < 3) {
        $erreur = "Le nom d'utilisateur doit faire au moins 3 caractères.";
    } elseif (strlen($name) < 3) {
        $erreur = "Le nom complet doit faire au moins 3 caractères.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erreur = "Veuillez saisir une adresse email valide.";
    } elseif (strlen($password) < 6) {
        $erreur = "Le mot de passe doit faire au moins 6 caractères.";
    } elseif ($password !== $password2) {
        $erreur = "Les deux mots de passe ne correspondent pas.";
    } else {
        $check = $pdo->prepare("SELECT id, username, email FROM users WHERE email = ? OR username = ? LIMIT 1");
        $check->execute([$email, $username]);
        $existing = $check->fetch(PDO::FETCH_ASSOC);
        if ($existing) {
            if ($existing['email'] === $email) {
                $erreur = "Cet email est déjà utilisé. Connectez-vous ou choisissez un autre email.";
            } else {
                $erreur = "Ce nom d'utilisateur est déjà pris. Choisissez-en un autre.";
            }
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $pdo->prepare("INSERT INTO users (username, name, email, password, role) VALUES (?, ?, ?, ?, 'client')")
                ->execute([$username, $name, $email, $hash]);
            header("Location: login.php?inscrit=1");
            exit;
        }
    }
}

$page_titre = "Créer un compte — EliteTech";
require 'header.php';
?>

<div class="auth-page-wrapper">

    <div class="auth-box">

        <h1 class="auth-titre">Créer un compte</h1>
        <p class="auth-sous">Rejoignez la boutique EliteTech gratuitement</p>

        <div class="auth-carte">

            <?php if ($erreur): ?>
                <div class="msg msg-err">
                     <?= htmlspecialchars($erreur) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">

                <div class="champ">
                    <label for="username">Nom d'utilisateur</label>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                        placeholder="Ex: admin, esther123"
                        required
                        autocomplete="username"
                    >
                </div>

                <div class="champ">
                    <label for="name">Nom complet</label>
                    <input
                        type="text"
                        id="name"
                        name="name"
                        value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
                        placeholder="Ex: Abdou Akim Gbadamassi"
                        required
                        autocomplete="name"
                    >
                </div>

                <div class="champ">
                    <label for="email">Email</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                        placeholder="votre@email.com"
                        required
                        autocomplete="email"
                    >
                </div>

                <div class="champ">
                    <label for="password">Mot de passe</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Minimum 6 caractères"
                        required
                        autocomplete="new-password"
                    >
                </div>

                <div class="champ">
                    <label for="password2">Confirmer le mot de passe</label>
                    <input
                        type="password"
                        id="password2"
                        name="password2"
                        placeholder="Répétez votre mot de passe"
                        required
                        autocomplete="new-password"
                    >
                </div>

                 <button type="submit" class="btn btn-or btn-full">
                     Créer mon compte
                 </button>

            </form>

            <div class="auth-sep">
                Déjà un compte ? <a href="login.php">Se connecter</a>
            </div>

        </div>

        <p class="auth-footer-note">
            <a href="accueil.php" class="muted-link">Retour à l'accueil</a>
        </p>

    </div>
</div>

<?php require 'footer.php'; ?>
