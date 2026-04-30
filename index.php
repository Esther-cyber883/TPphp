<?php
require 'config.php';

$recherche = trim($_GET['q'] ?? '');
$categorie = (int)($_GET['categorie'] ?? 0);
$page = max(1, (int)($_GET['page'] ?? 1));
$par_page = 12;
$offset = ($page - 1) * $par_page;

// Build WHERE clause
$where = "WHERE 1=1";
$params = [];

if ($recherche) {
    $where     .= " AND (a.name LIKE ? OR a.brand LIKE ? OR c.name LIKE ? OR a.description LIKE ?)";
    $params[] = "%$recherche%";
    $params[] = "%$recherche%";
    $params[] = "%$recherche%";
    $params[] = "%$recherche%";
}
if ($categorie) {
    $where     .= " AND a.category_id = ?";
    $params[] = $categorie;
}

// Count total
$count_sql = "SELECT COUNT(*) as total FROM accessories a LEFT JOIN categories c ON a.category_id = c.id $where";
$stmt = $pdo->prepare($count_sql);
$stmt->execute($params);
$total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
$pages_total = ceil($total / $par_page);

// Get products
$sql = "SELECT a.*, c.name as category_name
        FROM accessories a
        LEFT JOIN categories c ON a.category_id = c.id
        $where
        ORDER BY a.id DESC LIMIT " . intval($par_page) . " OFFSET " . intval($offset);

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$produits = $stmt->fetchAll(PDO::FETCH_ASSOC);

$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

$page_titre = "Boutique — EliteTech";
require 'header.php';
?>

<!-- Hero boutique -->
<section class="hero hero-boutique">
    <img class="hero-img"
         src="images/hero2.webp"
         alt="Boutique">
    <div class="hero-overlay"></div>
    <div class="hero-content">
        <h1>Notre boutique</h1>
        <p>Trouvez vos accessoires par catégorie ou par recherche.</p>
        <form method="GET" action="index.php" class="search-bar">
            <input type="text" name="q"
                   value="<?= htmlspecialchars($recherche) ?>"
                   placeholder="Rechercher un produit...">
            <button type="submit"><i class="fa fa-search"></i></button>
        </form>
    </div>
</section>

<div class="container section">

    <!-- Filtres -->
    <?php if (!empty($categories)): ?>
    <div class="filtres">
        <a href="index.php" class="filtre-btn <?= !$categorie && !$recherche ? 'actif' : '' ?>">Tout voir</a>
        <?php foreach ($categories as $cat): ?>
            <a href="index.php?categorie=<?= $cat['id'] ?>"
               class="filtre-btn <?= $categorie == $cat['id'] ? 'actif' : '' ?>">
                <?= htmlspecialchars($cat['name']) ?>
            </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Titre -->
    <div class="section-titre">
        <?php if ($recherche): ?>
            Résultats : "<?= htmlspecialchars($recherche) ?>"
        <?php elseif ($categorie): ?>
            <?php
            $ac = array_values(array_filter($categories, fn($c) => $c['id'] == $categorie));
            echo htmlspecialchars($ac[0]['name'] ?? 'Catégorie');
            ?>
        <?php else: ?>
            Tous les produits
        <?php endif; ?>
    </div>

    <!-- Grille -->
    <?php if (empty($produits)): ?>
        <div class="vide">
            <i class="fa fa-search"></i>
            <p class="vide-titre">Aucun produit trouvé</p>
              <p class="vide-texte">Essayez un autre terme ou <a href="index.php" class="link-or">voir tout</a>.</p>
        </div>
    <?php else: ?>
        <div class="grille">
            <?php foreach ($produits as $p): ?>
            <div class="carte">
                <a href="produit.php?id=<?= $p['id'] ?>">
                    <?php if ($p['image_url']): ?>
                        <img class="carte-img"
                             src="<?= htmlspecialchars($p['image_url']) ?>"
                             alt="<?= htmlspecialchars($p['name']) ?>">
                    <?php else: ?>
                        <div class="carte-img-placeholder">
                            <i class="fa fa-box"></i>
                        </div>
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
                        <?php if ($p['stock_quantity'] <= 0): ?>
                            <span class="badge badge-zero">Épuisé</span>
                        <?php elseif ($p['stock_quantity'] <= 5): ?>
                            <span class="badge badge-bas">Reste <?= $p['stock_quantity'] ?></span>
                        <?php else: ?>
                            <span class="badge badge-stock">En stock</span>
                        <?php endif; ?>
                    </div>

                    <a href="produit.php?id=<?= $p['id'] ?>"
                       class="btn btn-noir btn-sm btn-full mt-12">
                        Voir le produit
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($pages_total > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="index.php?page=1<?= $categorie ? '&categorie=' . $categorie : '' ?><?= $recherche ? '&q=' . urlencode($recherche) : '' ?>"
                   class="btn btn-contour btn-sm" title="Première page">
                    <i class="fa fa-chevron-left"></i><i class="fa fa-chevron-left"></i>
                </a>
                <a href="index.php?page=<?= $page - 1 ?><?= $categorie ? '&categorie=' . $categorie : '' ?><?= $recherche ? '&q=' . urlencode($recherche) : '' ?>"
                   class="btn btn-contour btn-sm" title="Page précédente">
                    <i class="fa fa-chevron-left"></i>
                </a>
            <?php endif; ?>

            <span class="pagination-info">
                <?= $page ?> / <?= $pages_total ?>
            </span>

            <?php if ($page < $pages_total): ?>
                <a href="index.php?page=<?= $page + 1 ?><?= $categorie ? '&categorie=' . $categorie : '' ?><?= $recherche ? '&q=' . urlencode($recherche) : '' ?>"
                   class="btn btn-contour btn-sm" title="Page suivante">
                    <i class="fa fa-chevron-right"></i>
                </a>
                <a href="index.php?page=<?= $pages_total ?><?= $categorie ? '&categorie=' . $categorie : '' ?><?= $recherche ? '&q=' . urlencode($recherche) : '' ?>"
                   class="btn btn-contour btn-sm" title="Dernière page">
                    <i class="fa fa-chevron-right"></i><i class="fa fa-chevron-right"></i>
                </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    <?php endif; ?>

</div>

<?php require 'footer.php'; ?>
