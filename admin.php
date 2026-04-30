<?php
require 'config.php';

/* --- Sécurité : admin uniquement --- */
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: login.php");
    exit;
}

$message = '';
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

$upload_dir = __DIR__ . '/uploads';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

function uploadProductImage(array $file): ?string {
    global $upload_dir;

    if (empty($file['name']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    if ($file['size'] > 2 * 1024 * 1024) {
        return null;
    }

    $allowed_ext = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed_ext, true)) {
        return null;
    }

    $filename = uniqid('prod_', true) . '.' . $ext;
    $destination = $upload_dir . '/' . $filename;

    if (move_uploaded_file($file['tmp_name'], $destination)) {
        return 'uploads/' . $filename;
    }
    return null;
}


/* --- Ajouter un accessoire --- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'ajouter') {
    $name          = trim($_POST['name'] ?? '');
    $brand         = trim($_POST['brand'] ?? '');
    $cat_id        = (int)($_POST['category_id'] ?? 0);
    $price         = (float)($_POST['price'] ?? 0);
    $stock_quantity = max(0, (int)($_POST['stock_quantity'] ?? 0));
    $shelf_location = trim($_POST['shelf_location'] ?? '');
    $version       = trim($_POST['version'] ?? '');
    $capacity      = trim($_POST['capacity'] ?? '');
    $description   = trim($_POST['description'] ?? '');
    $image_url     = trim($_POST['image_url'] ?? '');

    if (!empty($_FILES['image_file']['name'])) {
        $upload_path = uploadProductImage($_FILES['image_file']);
        if ($upload_path) {
            $image_url = $upload_path;
        }
    }

    if (!$name || $price <= 0) {
        $message = ['type' => 'err', 'texte' => "Le nom et le prix sont obligatoires."];
    } else {
        $pdo->prepare("
            INSERT INTO accessories (name, brand, category_id, price, stock_quantity, shelf_location, version, capacity, description, image_url)
            VALUES (?,?,?,?,?,?,?,?,?,?)
        ")->execute([$name, $brand, $cat_id ?: null, $price, $stock_quantity, $shelf_location, $version, $capacity, $description, $image_url ?: null]);
        $message = ['type' => 'ok', 'texte' => "Accessoire \"$name\" ajouté avec succès."];
    }
}

/* --- Modifier un accessoire --- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'modifier') {
    $id             = (int)($_POST['id'] ?? 0);
    $name           = trim($_POST['name'] ?? '');
    $brand          = trim($_POST['brand'] ?? '');
    $cat_id         = (int)($_POST['category_id'] ?? 0);
    $price          = (float)($_POST['price'] ?? 0);
    $stock_quantity = max(0, (int)($_POST['stock_quantity'] ?? 0));
    $shelf_location = trim($_POST['shelf_location'] ?? '');
    $version        = trim($_POST['version'] ?? '');
    $capacity       = trim($_POST['capacity'] ?? '');
    $description    = trim($_POST['description'] ?? '');
    $image_url      = trim($_POST['image_url'] ?? '');

    if (!empty($_FILES['image_file']['name'])) {
        $upload_path = uploadProductImage($_FILES['image_file']);
        if ($upload_path) {
            $image_url = $upload_path;
        }
    }

    if (!$name || !$id || $price <= 0) {
        $message = ['type' => 'err', 'texte' => "Données incomplètes."];
    } else {
        $pdo->prepare("
            UPDATE accessories
            SET name=?, brand=?, category_id=?, price=?, stock_quantity=?, shelf_location=?, version=?, capacity=?, description=?, image_url=?
            WHERE id=?
        ")->execute([$name, $brand, $cat_id ?: null, $price, $stock_quantity, $shelf_location, $version, $capacity, $description, $image_url ?: null, $id]);
        $message = ['type' => 'ok', 'texte' => "Accessoire \"$name\" modifié."];
    }
}

/* --- Supprimer un accessoire --- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'supprimer') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id) {
        $pdo->prepare("DELETE FROM accessories WHERE id=?")->execute([$id]);
        $message = ['type' => 'ok', 'texte' => "Accessoire #$id supprimé."];
    }
}

/* --- Ajouter une catégorie --- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'ajouter_categorie') {
    $nom_cat = trim($_POST['nom_categorie'] ?? '');
    if ($nom_cat) {
        $pdo->prepare("INSERT INTO categories (name) VALUES (?)")->execute([$nom_cat]);
        $message = ['type' => 'ok', 'texte' => "Catégorie \"$nom_cat\" ajoutée."];
        $categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
    }
}

/* --- Supprimer une catégorie --- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'supprimer_categorie') {
    $id_cat = (int)($_POST['categorie_id'] ?? 0);
    if ($id_cat) {
        $pdo->prepare("UPDATE accessories SET category_id = NULL WHERE category_id = ?")->execute([$id_cat]);
        $pdo->prepare("DELETE FROM categories WHERE id = ?")->execute([$id_cat]);
        $message = ['type' => 'ok', 'texte' => "Catégorie supprimée."];
        $categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
    }
}

/* --- Changer le statut d'une commande --- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'changer_statut') {
    $cmd_id = (int)($_POST['commande_id'] ?? 0);
    $nouveau_statut = $_POST['statut'] ?? '';
    $statuts_valides = ['en_attente', 'payee', 'en_cours', 'livree', 'annulee'];
    if ($cmd_id && in_array($nouveau_statut, $statuts_valides)) {
        $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?")->execute([$nouveau_statut, $cmd_id]);
        $message = ['type' => 'ok', 'texte' => "Statut de la commande #$cmd_id mis à jour."];
    }
}

/* --- Bannir un utilisateur --- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'bannir') {
    $user_ban_id = (int)($_POST['user_id'] ?? 0);
    if ($user_ban_id && $user_ban_id != $_SESSION['user_id']) {
        $pdo->prepare("UPDATE users SET banned = 1 WHERE id = ? AND role != 'admin'")->execute([$user_ban_id]);
        $message = ['type' => 'ok', 'texte' => "Utilisateur #$user_ban_id banni."];
    }
}

/* --- Débannir un utilisateur --- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'debannir') {
    $user_ban_id = (int)($_POST['user_id'] ?? 0);
    if ($user_ban_id) {
        $pdo->prepare("UPDATE users SET banned = 0 WHERE id = ?")->execute([$user_ban_id]);
        $message = ['type' => 'ok', 'texte' => "Utilisateur #$user_ban_id débanni."];
    }
}



/* Accessoire à modifier si on vient de la page produit ou si ?modifier=X */
$produit_modif = null;
if (isset($_GET['modifier'])) {
    $idMod = (int)$_GET['modifier'];
    $stmtM = $pdo->prepare("SELECT * FROM accessories WHERE id = ?");
    $stmtM->execute([$idMod]);
    $produit_modif = $stmtM->fetch(PDO::FETCH_ASSOC);
}

/* Onglet actif */
$onglet = $_GET['onglet'] ?? 'accessories';

/* Liste des accessoires avec filtre stock bas si demandé */
$filtre_bas = isset($_GET['bas_stock']);
$sqlProd = "SELECT a.*, c.name as category_name
            FROM accessories a
            LEFT JOIN categories c ON a.category_id = c.id";
if ($filtre_bas) $sqlProd .= " WHERE a.stock_quantity < 5";
$sqlProd .= " ORDER BY a.stock_quantity ASC, a.name ASC";
$produits = $pdo->query($sqlProd)->fetchAll(PDO::FETCH_ASSOC);

/* Stats */
$total_produits  = (int)$pdo->query("SELECT COUNT(*) FROM accessories")->fetchColumn();
$bas_stock_nb    = (int)$pdo->query("SELECT COUNT(*) FROM accessories WHERE stock_quantity < 5")->fetchColumn();
$valeur_stock    = (float)$pdo->query("SELECT COALESCE(SUM(price * stock_quantity), 0) FROM accessories")->fetchColumn();
$total_users     = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$total_commandes = (int)$pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$ca_total        = (float)$pdo->query("SELECT COALESCE(SUM(total), 0) FROM orders WHERE status != 'annulee'")->fetchColumn();

$page_titre = "Administration — EliteTech";
require 'header.php';
?>

<div class="container section">

    <!-- En-tête admin -->
    <div class="flex-between mb-32">
        <div>
            <h1 class="admin-title">
                Espace Administration
            </h1>
            <p class="admin-subtitle">
                Connecté en tant que <strong class="table-link"><?= htmlspecialchars($_SESSION['user_name'] ?? $_SESSION['username'] ?? '') ?></strong>
            </p>
        </div>
        <div class="inline-actions">
            <a href="index.php" class="btn btn-contour btn-sm">
                <i class="fa fa-store"></i> Voir la boutique
            </a>
            <a href="logout.php" class="btn btn-rouge btn-sm">
                <i class="fa fa-sign-out-alt"></i> Déconnexion
            </a>
        </div>
    </div>

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-num"><?= $total_produits ?></div>
            <div class="stat-label"><i class="fa fa-box"></i> Produits</div>
        </div>
        <?php $stat_class = $bas_stock_nb > 0 ? 'stat-alert' : 'stat-ok'; ?>
        <div class="stat-card <?= $stat_class ?>">
            <div class="stat-num <?= $stat_class ?>"><?= $bas_stock_nb ?></div>
            <div class="stat-label"><i class="fa fa-exclamation-triangle"></i> Stock critique</div>
        </div>
        <div class="stat-card">
            <div class="stat-num"><?= $total_commandes ?></div>
            <div class="stat-label"><i class="fa fa-shopping-bag"></i> Commandes</div>
        </div>
        <div class="stat-card">
            <div class="stat-num"><?= number_format($ca_total, 0, '', ' ') ?></div>
            <div class="stat-label"><i class="fa fa-coins"></i> CA Total (F CFA)</div>
        </div>
        <div class="stat-card">
            <div class="stat-num"><?= $total_users ?></div>
            <div class="stat-label"><i class="fa fa-users"></i> Utilisateurs</div>
        </div>
    </div>

    <!-- Message flash -->
    <?php if ($message): ?>
        <div class="alerte alerte-<?= $message['type'] === 'ok' ? 'ok' : 'err' ?>">
            <?= htmlspecialchars($message['texte']) ?>
        </div>
    <?php endif; ?>

    <!--  SI MODIFICATION D'UN PRODUIT  -->
    <?php if ($produit_modif): ?>
    <div class="card-panel card-panel--accent">
        <h2 class="h2-lg">
            <i class="fa fa-edit icon-or"></i>
            Modifier : <?= htmlspecialchars($produit_modif['name']) ?>
        </h2>

        <form method="POST" action="admin.php" enctype="multipart/form-data">
            <input type="hidden" name="action" value="modifier">
            <input type="hidden" name="id" value="<?= $produit_modif['id'] ?>">

            <div class="grid-2">
                <div class="champ">
                    <label>Nom du produit *</label>
                    <input type="text" name="name" value="<?= htmlspecialchars($produit_modif['name']) ?>" required>
                </div>
                <div class="champ">
                    <label>Marque</label>
                    <input type="text" name="brand" value="<?= htmlspecialchars($produit_modif['brand'] ?? '') ?>">
                </div>
                <div class="champ">
                    <label>Catégorie</label>
                    <select name="category_id">
                        <option value="">-- Aucune --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= $cat['id'] == $produit_modif['category_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="champ">
                    <label>Prix (F CFA) *</label>
                    <input type="number" name="price" value="<?= $produit_modif['price'] ?>" min="0" step="100" required>
                </div>
                <div class="champ">
                    <label>Stock (quantité)</label>
                    <input type="number" name="stock_quantity" value="<?= $produit_modif['stock_quantity'] ?>" min="0">
                </div>
                <div class="champ">
                    <label>Emplacement en magasin</label>
                    <input type="text" name="shelf_location" value="<?= htmlspecialchars($produit_modif['shelf_location'] ?? '') ?>" placeholder="Ex: Rayon A3">
                </div>
            </div>

            <div class="champ">
                <label>URL de l'image</label>
                <input type="text" name="image_url" value="<?= htmlspecialchars($produit_modif['image_url'] ?? '') ?>"
                       placeholder="https://... ou images/monfichier.jpg">
                <small class="note-small">
                    Mettez vos images dans le dossier <strong>images/</strong> ou utilisez l'upload ci-dessous.
                </small>
            </div>

            <div class="champ">
                <label>Uploader une image</label>
                <input type="file" name="image_file" accept="image/png,image/jpeg,image/webp,image/gif">
                <small class="note-small">Fichier accepté : JPG, PNG, WEBP, GIF (taille maximum 2 Mo).</small>
            </div>

            <div class="grid-2">
                <div class="champ">
                    <label>Version</label>
                    <input type="text" name="version" value="<?= htmlspecialchars($produit_modif['version'] ?? '') ?>" placeholder="Ex: 2024, Pro, Max">
                </div>
                <div class="champ">
                    <label>Capacité</label>
                    <input type="text" name="capacity" value="<?= htmlspecialchars($produit_modif['capacity'] ?? '') ?>" placeholder="Ex: 128GB, 5000mAh">
                </div>
            </div>

            <div class="champ">
                <label>Description du produit</label>
                <textarea name="description" placeholder="Décrivez le produit, ses caractéristiques..."><?= htmlspecialchars($produit_modif['description'] ?? '') ?></textarea>
            </div>

            <div class="inline-actions">
                <button type="submit" class="btn btn-or">
                    <i class="fa fa-save"></i> Enregistrer les modifications
                </button>
                <a href="admin.php" class="btn btn-contour">Annuler</a>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <!-- Onglets -->
    <div class="onglets">
        <a href="admin.php?onglet=accessories" class="onglet <?= $onglet === 'accessories' ? 'actif' : '' ?>">
            <i class="fa fa-box"></i> Accessoires
        </a>
        <a href="admin.php?onglet=ajouter" class="onglet <?= $onglet === 'ajouter' ? 'actif' : '' ?>">
            <i class="fa fa-plus"></i> Ajouter
        </a>
        <a href="admin.php?onglet=categories" class="onglet <?= $onglet === 'categories' ? 'actif' : '' ?>">
            <i class="fa fa-tags"></i> Catégories
        </a>
        <a href="admin.php?onglet=commandes" class="onglet <?= $onglet === 'commandes' ? 'actif' : '' ?>">
            <i class="fa fa-shopping-bag"></i> Commandes
        </a>
        <a href="admin.php?onglet=utilisateurs" class="onglet <?= $onglet === 'utilisateurs' ? 'actif' : '' ?>">
            <i class="fa fa-users"></i> Utilisateurs
        </a>
    </div>

    <!-- ===== ONGLET : LISTE DES ACCESSOIRES ===== -->
    <?php if ($onglet === 'accessories'): ?>

    <div class="flex-between align-center mb-20">
        <h2 class="h2-md">Liste des accessoires</h2>
        <div class="inline-actions">
            <a href="admin.php?onglet=accessories" class="btn btn-contour btn-sm <?= !$filtre_bas ? 'btn-noir' : '' ?>">
                Tous
            </a>
            <a href="admin.php?onglet=accessories&bas_stock=1" class="btn btn-sm btn-alert">
                <i class="fa fa-exclamation-triangle"></i> Stock critique (<?= $bas_stock_nb ?>)
            </a>
        </div>
    </div>

    <?php if (empty($produits)): ?>
        <div class="vide">
            <div class="vide-icone"><i class="fa fa-box-open"></i></div>
            <p class="vide-titre">Aucun accessoire</p>
            <p class="vide-texte">Ajoutez votre premier accessoire dans l'onglet "Ajouter".</p>
        </div>
    <?php else: ?>
    <div class="overflow-auto">
        <table class="tableau">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Image</th>
                    <th>Nom</th>
                    <th>Marque</th>
                    <th>Catégorie</th>
                    <th>Prix</th>
                    <th>Stock</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($produits as $p): ?>
                <tr>
                    <td class="muted"><?= $p['id'] ?></td>
                    <td>
                            <?php if ($p['image_url']): ?>
                            <img src="<?= htmlspecialchars($p['image_url']) ?>" class="thumb-48">
                        <?php else: ?>
                            <div class="thumb-placeholder">
                                <i class="fa fa-box muted"></i>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="produit.php?id=<?= $p['id'] ?>" class="table-link">
                            <?= htmlspecialchars($p['name']) ?>
                        </a>
                    </td>
                    <td><?= htmlspecialchars($p['brand'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($p['category_name'] ?? '—') ?></td>
                    <td class="prix"><?= number_format($p['price'], 0, '', ' ') ?> F</td>
                    <td>
                        <?php if ($p['stock_quantity'] <= 0): ?>
                            <span class="badge badge-zero">0</span>
                        <?php elseif ($p['stock_quantity'] < 5): ?>
                            <span class="badge badge-bas"><?= $p['stock_quantity'] ?></span>
                        <?php else: ?>
                            <span class="badge badge-stock"><?= $p['stock_quantity'] ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="inline-actions">
                            <a href="admin.php?modifier=<?= $p['id'] ?>" class="btn btn-contour btn-sm" title="Modifier">
                                <i class="fa fa-edit"></i> Modifier
                            </a>
                            <a href="produit.php?id=<?= $p['id'] ?>" class="btn btn-noir btn-sm" title="Voir">
                                <i class="fa fa-eye"></i> Voir
                            </a>
                                <form method="POST" class="form-inline"
                                    onsubmit="return confirm('Supprimer cet accessoire ?');">
                                <input type="hidden" name="action" value="supprimer">
                                <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                <button type="submit" class="btn btn-rouge btn-sm" title="Supprimer">
                                    <i class="fa fa-trash"></i> Supprimer
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <!-- ===== ONGLET : AJOUTER UN ACCESSOIRE ===== -->
    <?php elseif ($onglet === 'ajouter'): ?>

    <h2 class="h2-lg">Ajouter un nouvel accessoire</h2>

    <div class="grid-2-lg">
        <div class="card-panel max-w-760">
            <form method="POST" action="admin.php?onglet=ajouter" enctype="multipart/form-data">
                <input type="hidden" name="action" value="ajouter">

                <div class="grid-2">
                    <div class="champ">
                        <label>Nom du produit *</label>
                        <input type="text" name="name" placeholder="Ex: iPhone 15 Pro Case" required>
                    </div>
                    <div class="champ">
                        <label>Marque</label>
                        <input type="text" name="brand" placeholder="Ex: Apple">
                    </div>
                    <div class="champ">
                        <label>Catégorie</label>
                        <select name="category_id">
                            <option value="">-- Aucune catégorie --</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="champ">
                        <label>Prix (F CFA) *</label>
                        <input type="number" name="price" placeholder="0" min="0" step="100" required>
                    </div>
                    <div class="champ">
                        <label>Quantité en stock</label>
                        <input type="number" name="stock_quantity" placeholder="0" min="0" value="0">
                    </div>
                    <div class="champ">
                        <label>Emplacement en magasin</label>
                        <input type="text" name="shelf_location" placeholder="Ex: Rayon B2, Vitrine 3">
                    </div>
                    <div class="champ">
                        <label>Version</label>
                        <input type="text" name="version" placeholder="Ex: 2024, Pro, Max">
                    </div>
                    <div class="champ">
                        <label>Capacité</label>
                        <input type="text" name="capacity" placeholder="Ex: 128GB, 5000mAh">
                    </div>
                </div>

                <div class="champ">
                    <label>URL de l'image du produit</label>
                    <input type="text" name="image_url"
                           placeholder="images/iphone15.jpg  OU  https://example.com/photo.jpg">
                    <small class="note-small">
                        <i class="fa fa-info-circle"></i>
                        Pour utiliser une image locale, copiez-la dans le dossier <strong>images/</strong>
                        et écrivez son chemin ici (ex : images/monimage.jpg).
                    </small>
                </div>

                <div class="champ">
                    <label>Uploader une image</label>
                    <input type="file" name="image_file" accept="image/png,image/jpeg,image/webp,image/gif">
                    <small class="note-small">Fichier accepté : JPG, PNG, WEBP, GIF (taille maximum 2 Mo).</small>
                </div>

                <div class="champ">
                    <label>Description du produit</label>
                    <textarea name="description" placeholder="Décrivez le produit, ses caractéristiques..."></textarea>
                </div>

                <div class="inline-actions">
                    <button type="submit" class="btn btn-or btn-large">
                        <i class="fa fa-plus"></i> Ajouter le produit
                    </button>
                    <a href="admin.php" class="btn btn-contour">Annuler</a>
                </div>
            </form>
        </div>

        <!-- Guide ajout produit (colonne droite) -->
        <aside class="content-card">
            <h3 class="h3-accent"><i class="fa fa-book"></i> Guide : ajouter un produit</h3>

            <div class="lead-body">
                <section>
                    <h4>Option 1 — Image depuis Internet (rapide)</h4>
                    <ol class="lead-ol">
                        <li>Ouvrez le site contenant l'image.</li>
                        <li>Clique droit → "Copier l'adresse de l'image".</li>
                        <li>Collez l'URL dans le champ "URL de l'image" du formulaire.</li>
                        <li>Exemple : <code class="code-inline">https://exemple.com/produit.jpg</code></li>
                    </ol>
                </section>

                <section>
                    <h4>Option 2 — Image locale (recommandé)</h4>
                    <ol class="lead-ol">
                        <li>Renommez l'image (ex: <code class="code-inline">iphone15.jpg</code>).</li>
                        <li>Copiez le fichier dans le dossier <strong>images/</strong> du site.</li>
                        <li>Indiquez le chemin dans le formulaire : <code class="code-inline">images/iphone15.jpg</code></li>
                    </ol>
                </section>

                <section>
                    <h4>Où trouver le dossier <strong>images/</strong> ?</h4>
                    <p>Le dossier <strong>images/</strong> est dans le même répertoire que vos fichiers PHP. Exemple local :</p>
                    <pre class="code-inline">/var/www/mon-site/images/</pre>
                </section>

                <p class="info-box">
                    <i class="fa fa-lightbulb icon-or"></i>
                    <strong>Conseil :</strong> utilisez des images carrées de ~600x600px en JPG/PNG/WebP pour un rendu optimal.
                </p>
            </div>
        </aside>
    </div>

    <!-- ===== ONGLET : CATÉGORIES ===== -->
    <?php elseif ($onglet === 'categories'): ?>

    <h2 class="h2-md">Gestion des catégories</h2>

    <div class="grid-2-lg">

        <!-- Liste des catégories -->
                <div class="card-panel">
            <h3 class="h3-md">Catégories existantes</h3>

            <?php if (empty($categories)): ?>
                <p class="muted">Aucune catégorie.</p>
            <?php else: ?>
                <?php foreach ($categories as $cat): ?>
                <div class="category-row">
                    <span class="cat-name">
                        <?= htmlspecialchars($cat['name']) ?>
                    </span>
                    <form method="POST" onsubmit="return confirm('Supprimer cette catégorie ? Les produits ne seront pas supprimés.');">
                        <input type="hidden" name="action" value="supprimer_categorie">
                        <input type="hidden" name="categorie_id" value="<?= $cat['id'] ?>">
                        <button type="submit" class="btn btn-rouge btn-sm">
                            <i class="fa fa-trash"></i> Supprimer
                        </button>
                    </form>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Ajouter catégorie -->
        <div class="card-panel">
            <h3 class="h3-md">Ajouter une catégorie</h3>

            <form method="POST" action="admin.php?onglet=categories">
                <input type="hidden" name="action" value="ajouter_categorie">
                <div class="champ">
                    <label>Nom de la catégorie</label>
                    <input type="text" name="nom_categorie" placeholder="Ex: Coques, Chargeurs..." required>
                </div>
                <button type="submit" class="btn btn-or">
                    <i class="fa fa-plus"></i> Ajouter
                </button>
            </form>
        </div>

    </div>

    <!-- ===== ONGLET : COMMANDES ===== -->
    <?php elseif ($onglet === 'commandes'): ?>

    <h2 class="h2-md">Gestion des commandes</h2>

    <?php
    // Charger toutes les commandes
    $all_commandes = $pdo->query("
        SELECT o.*, u.username
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id
        ORDER BY o.created_at DESC
    ")->fetchAll(PDO::FETCH_ASSOC);

    $statuts_texte = [
        'en_attente' => 'En attente',
        'payee'      => 'Payée',
        'en_cours'   => 'En cours',
        'livree'     => 'Livrée',
        'annulee'    => 'Annulée'
    ];
    ?>

    <?php if (empty($all_commandes)): ?>
        <div class="vide">
            <i class="fa fa-shopping-bag"></i>
            <p class="vide-titre">Aucune commande</p>
        </div>
    <?php else: ?>

    <?php
    // Détail d'une commande si demandé
    $cmd_detail = null;
    $cmd_articles = [];
    if (isset($_GET['voir_cmd'])) {
        $cmd_detail_id = (int)$_GET['voir_cmd'];
        $stmtCmd = $pdo->prepare("SELECT o.*, u.username FROM orders o LEFT JOIN users u ON o.user_id = u.id WHERE o.id = ?");
        $stmtCmd->execute([$cmd_detail_id]);
        $cmd_detail = $stmtCmd->fetch(PDO::FETCH_ASSOC);
        if ($cmd_detail) {
            $stmtArt = $pdo->prepare("SELECT oi.*, a.name FROM order_items oi LEFT JOIN accessories a ON oi.accessory_id = a.id WHERE oi.order_id = ?");
            $stmtArt->execute([$cmd_detail_id]);
            $cmd_articles = $stmtArt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
    ?>

    <?php if ($cmd_detail): ?>
    <!-- Détail d'une commande -->
    <div class="card-panel">
        <div class="flex-between align-center mb-20">
            <h3 class="h3-md">Commande #<?= $cmd_detail['id'] ?> — <?= htmlspecialchars($cmd_detail['username']) ?></h3>
            <a href="admin.php?onglet=commandes" class="btn btn-contour btn-sm"><i class="fa fa-arrow-left"></i> Retour</a>
        </div>
        <table class="tableau">
            <tr><td class="muted">Date</td><td><?= date('d/m/Y H:i', strtotime($cmd_detail['created_at'])) ?></td></tr>
            <tr><td class="muted">Client</td><td><?= htmlspecialchars($cmd_detail['address_name']) ?> — <?= htmlspecialchars($cmd_detail['address_phone']) ?></td></tr>
            <tr><td class="muted">Adresse</td><td><?= htmlspecialchars($cmd_detail['address_city']) ?> — <?= htmlspecialchars($cmd_detail['address_detail']) ?></td></tr>
            <tr><td class="muted">Paiement</td><td><?= htmlspecialchars($cmd_detail['payment_method']) ?></td></tr>
            <tr><td class="muted">Total</td><td class="prix"><?= number_format($cmd_detail['total'], 0, '', ' ') ?> F CFA</td></tr>
        </table>
        <h4 style="margin:16px 0 8px;">Articles</h4>
        <table class="tableau">
            <thead><tr><th>Produit</th><th>Qté</th><th>Prix</th><th>Sous-total</th></tr></thead>
            <tbody>
            <?php foreach ($cmd_articles as $ca): ?>
            <tr>
                <td><?= htmlspecialchars($ca['name'] ?? 'Supprimé') ?></td>
                <td><?= $ca['quantity'] ?></td>
                <td><?= number_format($ca['unit_price'], 0, '', ' ') ?> F</td>
                <td><?= number_format($ca['unit_price'] * $ca['quantity'], 0, '', ' ') ?> F</td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <!-- Liste des commandes -->
    <div class="overflow-auto">
        <table class="tableau">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Client</th>
                    <th>Date</th>
                    <th>Total</th>
                    <th>Paiement</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($all_commandes as $cmd): ?>
                <tr>
                    <td><?= $cmd['id'] ?></td>
                    <td><?= htmlspecialchars($cmd['username'] ?? 'Inconnu') ?></td>
                    <td><?= date('d/m/Y', strtotime($cmd['created_at'])) ?></td>
                    <td class="prix"><?= number_format($cmd['total'], 0, '', ' ') ?> F</td>
                    <td><?= htmlspecialchars($cmd['payment_method']) ?></td>
                    <td>
                        <form method="POST" class="inline-form">
                            <input type="hidden" name="action" value="changer_statut">
                            <input type="hidden" name="commande_id" value="<?= $cmd['id'] ?>">
                            <select name="statut" onchange="this.form.submit()">
                                <?php foreach ($statuts_texte as $val => $txt): ?>
                                    <option value="<?= $val ?>" <?= $cmd['status'] === $val ? 'selected' : '' ?>>
                                        <?= $txt ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </td>
                    <td>
                        <a href="admin.php?onglet=commandes&voir_cmd=<?= $cmd['id'] ?>" class="btn btn-noir btn-sm">
                            <i class="fa fa-eye"></i> Détail
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <!-- ===== ONGLET : UTILISATEURS ===== -->
    <?php elseif ($onglet === 'utilisateurs'): ?>

    <h2 class="h2-md">Gestion des utilisateurs</h2>

    <?php
    $all_users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
    ?>

    <div class="overflow-auto">
        <table class="tableau">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nom</th>
                    <th>Rôle</th>
                    <th>Inscrit le</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($all_users as $u): ?>
                <tr>
                    <td><?= $u['id'] ?></td>
                    <td><?= htmlspecialchars($u['username']) ?></td>
                    <td>
                        <span class="badge <?= $u['role'] === 'admin' ? 'badge-or' : 'badge-gris' ?>">
                            <?= $u['role'] ?>
                        </span>
                    </td>
                    <td><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
                    <td>
                        <?php if ($u['banned'] ?? 0): ?>
                            <span class="badge badge-zero">Banni</span>
                        <?php else: ?>
                            <span class="badge badge-stock">Actif</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($u['role'] !== 'admin'): ?>
                            <?php if ($u['banned'] ?? 0): ?>
                                <form method="POST" class="inline-form">
                                    <input type="hidden" name="action" value="debannir">
                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                    <button type="submit" class="btn btn-or btn-sm">
                                        <i class="fa fa-check"></i> Débannir
                                    </button>
                                </form>
                            <?php else: ?>
                                <form method="POST" class="inline-form"
                                      onsubmit="return confirm('Bannir cet utilisateur ?');">
                                    <input type="hidden" name="action" value="bannir">
                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                    <button type="submit" class="btn btn-rouge btn-sm">
                                        <i class="fa fa-ban"></i> Bannir
                                    </button>
                                </form>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="muted">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php endif; ?>

</div>

<?php require 'footer.php'; ?>
