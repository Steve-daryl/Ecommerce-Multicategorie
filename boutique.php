<?php
/**
 * ShopMax — Page Boutique
 */
$pageTitle = 'Boutique';
require_once __DIR__ . '/includes/header.php';

$pdo = getPDO();

// Filtres
$catFilter = isset($_GET['categorie']) ? (int)$_GET['categorie'] : 0;
$marqueFilter = isset($_GET['marque']) ? (int)$_GET['marque'] : 0;
$prixMin = isset($_GET['prix_min']) ? (int)$_GET['prix_min'] : 0;
$prixMax = isset($_GET['prix_max']) ? (int)$_GET['prix_max'] : 0;
$search = trim($_GET['q'] ?? '');
$sort = $_GET['tri'] ?? 'recent';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = (int)getParam('produits_par_page', '12');

// Build query
$where = ["p.actif = 1", "p.supprime = 0"];
$params_q = [];

if ($catFilter > 0) { $where[] = "p.categorie_id = :cat"; $params_q['cat'] = $catFilter; }
if ($marqueFilter > 0) { $where[] = "p.marque_id = :marque"; $params_q['marque'] = $marqueFilter; }
if ($prixMin > 0) { $where[] = "COALESCE(p.prix_promo, p.prix) >= :pmin"; $params_q['pmin'] = $prixMin; }
if ($prixMax > 0) { $where[] = "COALESCE(p.prix_promo, p.prix) <= :pmax"; $params_q['pmax'] = $prixMax; }
if (!empty($search)) { $where[] = "(p.nom LIKE :q OR p.description LIKE :q2)"; $params_q['q'] = "%$search%"; $params_q['q2'] = "%$search%"; }

$whereClause = implode(' AND ', $where);

$orderBy = match($sort) {
    'prix_asc' => 'COALESCE(p.prix_promo, p.prix) ASC',
    'prix_desc' => 'COALESCE(p.prix_promo, p.prix) DESC',
    'nom' => 'p.nom ASC',
    'ancien' => 'p.created_at ASC',
    default => 'p.created_at DESC'
};

// Count
$stmtCount = $pdo->prepare("SELECT COUNT(*) FROM produits p WHERE $whereClause");
$stmtCount->execute($params_q);
$totalProducts = (int)$stmtCount->fetchColumn();
$totalPages = max(1, ceil($totalProducts / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

// Fetch products
$stmtProd = $pdo->prepare("
    SELECT p.*, m.nom AS marque_nom, c.nom AS categorie_nom,
    (SELECT chemin FROM image_produit WHERE produit_id = p.id ORDER BY ordre ASC LIMIT 1) AS image
    FROM produits p
    LEFT JOIN marques m ON m.id = p.marque_id
    LEFT JOIN categories c ON c.id = p.categorie_id
    WHERE $whereClause
    ORDER BY $orderBy
    LIMIT $perPage OFFSET $offset
");
$stmtProd->execute($params_q);
$produits = $stmtProd->fetchAll();

// Marques pour filtre
$marques = getMarques();

// Category counts
$stmtCatCount = $pdo->query("SELECT c.id, c.nom, c.icone, COUNT(p.id) as total FROM categories c LEFT JOIN produits p ON p.categorie_id = c.id AND p.actif = 1 AND p.supprime = 0 WHERE c.actif = 1 GROUP BY c.id ORDER BY c.ordre_affichage");
$catCounts = $stmtCatCount->fetchAll();

// Build current query string for pagination
function buildQueryString($overrides = []) {
    $params = array_merge($_GET, $overrides);
    unset($params['page']);
    if (isset($overrides['page'])) $params['page'] = $overrides['page'];
    return http_build_query($params);
}
?>

<div class="container">
    <!-- Breadcrumb -->
    <div class="breadcrumb">
        <a href="<?= BASE_URL ?>/">Accueil</a>
        <span class="sep"><i class="fas fa-chevron-right"></i></span>
        <span>Boutique</span>
        <?php if ($catFilter): ?>
        <?php $catName = ''; foreach($catCounts as $c) if($c['id']==$catFilter) $catName=$c['nom']; ?>
        <span class="sep"><i class="fas fa-chevron-right"></i></span>
        <span><?= e($catName) ?></span>
        <?php endif; ?>
    </div>

    <div class="shop-layout">
        <!-- Filters Sidebar -->
        <aside class="filters-sidebar" id="filtersSidebar">
            <div class="filter-group">
                <h4>Catégories <i class="fas fa-chevron-down"></i></h4>
                <div class="filter-body">
                    <label>
                        <input type="radio" name="categorie" value="" <?= $catFilter == 0 ? 'checked' : '' ?>
                            onchange="window.location='<?= BASE_URL ?>/boutique.php?<?= buildQueryString(['categorie' => '', 'page' => 1]) ?>'">
                        <span><i class="fas fa-border-all"></i> Toutes les catégories</span>
                    </label>
                    <?php foreach ($catCounts as $c): ?>
                    <label>
                        <input type="radio" name="categorie" value="<?= $c['id'] ?>" <?= $catFilter == $c['id'] ? 'checked' : '' ?>
                            onchange="window.location='<?= BASE_URL ?>/boutique.php?<?= buildQueryString(['categorie' => $c['id'], 'page' => 1]) ?>'">
                        <span><?= $c['icone'] ?> <?= e($c['nom']) ?></span>
                        <span class="count"><?= $c['total'] ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="filter-group">
                <h4>Marques <i class="fas fa-chevron-down"></i></h4>
                <div class="filter-body">
                    <?php foreach ($marques as $m): ?>
                    <label>
                        <input type="checkbox" name="marque" value="<?= $m['id'] ?>" <?= $marqueFilter == $m['id'] ? 'checked' : '' ?>
                            onchange="window.location='<?= BASE_URL ?>/boutique.php?<?= buildQueryString(['marque' => $m['id'], 'page' => 1]) ?>'">
                        <?= e($m['nom']) ?>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="filter-group">
                <h4>Fourchette de Prix <i class="fas fa-chevron-down"></i></h4>
                <div class="filter-body">
                    <form method="GET" action="<?= BASE_URL ?>/boutique.php" class="price-range">
                        <?php if ($catFilter): ?><input type="hidden" name="categorie" value="<?= $catFilter ?>"><?php endif; ?>
                        <?php if ($marqueFilter): ?><input type="hidden" name="marque" value="<?= $marqueFilter ?>"><?php endif; ?>
                        <?php if ($sort !== 'recent'): ?><input type="hidden" name="tri" value="<?= e($sort) ?>"><?php endif; ?>
                        <div class="price-inputs">
                            <input type="number" name="prix_min" placeholder="Min" value="<?= $prixMin ?: '' ?>">
                            <span>—</span>
                            <input type="number" name="prix_max" placeholder="Max" value="<?= $prixMax ?: '' ?>">
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm btn-block">Appliquer</button>
                    </form>
                </div>
            </div>

            <button class="btn btn-secondary btn-sm btn-block filter-reset" id="resetFilters">
                <i class="fas fa-rotate-left"></i> Réinitialiser
            </button>
        </aside>

        <!-- Products Area -->
        <div class="shop-products">
            <!-- Toolbar -->
            <div class="shop-toolbar">
                <div class="shop-toolbar-left" style="display:flex; gap:10px;">
                    <button class="btn btn-sm btn-secondary mobile-filter-btn">
                        <i class="fas fa-sliders"></i> Filtres
                    </button>
                    <button class="btn btn-sm btn-outline desktop-filter-btn" id="toggleFiltersBtn">
                        <i class="fas fa-eye-slash"></i> <span class="text">Masquer Filtres</span>
                    </button>
                </div>
                <div class="shop-result-count">
                    <strong><?= $totalProducts ?></strong> produit<?= $totalProducts > 1 ? 's' : '' ?> trouvé<?= $totalProducts > 1 ? 's' : '' ?>
                    <?php if (!empty($search)): ?> pour "<?= e($search) ?>"<?php endif; ?>
                </div>
                <div class="shop-controls">
                    <div class="shop-sort">
                        <select onchange="window.location='<?= BASE_URL ?>/boutique.php?'+new URLSearchParams({...Object.fromEntries(new URLSearchParams(window.location.search)), tri: this.value, page: 1}).toString()">
                            <option value="recent" <?= $sort==='recent' ? 'selected' : '' ?>>Plus récents</option>
                            <option value="prix_asc" <?= $sort==='prix_asc' ? 'selected' : '' ?>>Prix croissant</option>
                            <option value="prix_desc" <?= $sort==='prix_desc' ? 'selected' : '' ?>>Prix décroissant</option>
                            <option value="nom" <?= $sort==='nom' ? 'selected' : '' ?>>Nom A-Z</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Products Grid -->
            <?php if (empty($produits)): ?>
            <div class="cart-empty">
                <i class="fas fa-search"></i>
                <h3>Aucun produit trouvé</h3>
                <p>Essayez de modifier vos filtres ou votre recherche.</p>
                <a href="<?= BASE_URL ?>/boutique.php" class="btn btn-primary">Voir tous les produits</a>
            </div>
            <?php else: ?>
            <div class="products-grid">
                <?php foreach ($produits as $p): ?>
                <?php
                    $img = $p['image'] ?: '/assets/images/no-image.png';
                    $stock = getStockEffectif($p);
                    $hasPromo = !empty($p['prix_promo']);
                    $prixAffiche = $hasPromo ? $p['prix_promo'] : $p['prix'];
                ?>
                <div class="product-card <?= $stock <= 0 ? 'out-of-stock' : '' ?>">
                    <div class="product-card-img">
                        <img src="<?= BASE_URL . e($img) ?>" alt="<?= e($p['nom']) ?>" loading="lazy"
                             onerror="this.src='https://placehold.co/400x300/E8F0FB/082F63?text=<?= urlencode($p['nom']) ?>'">
                        <div class="product-badges">
                            <?php if ($hasPromo): ?>
                            <span class="badge badge-promo">-<?= calcRemise($p['prix'], $p['prix_promo']) ?>%</span>
                            <?php endif; ?>
                            <?php if ($stock <= 0): ?>
                            <span class="badge badge-sold-out">Épuisé</span>
                            <?php endif; ?>
                        </div>
                        <div class="product-card-actions">
                            <a href="<?= BASE_URL ?>/produit.php?id=<?= $p['id'] ?>" class="btn-icon" title="Voir"><i class="fas fa-eye"></i></a>
                        </div>
                    </div>
                    <div class="product-card-body">
                        <div class="product-card-brand"><?= e($p['marque_nom'] ?? '') ?></div>
                        <h3><a href="<?= BASE_URL ?>/produit.php?id=<?= $p['id'] ?>"><?= e($p['nom']) ?></a></h3>
                        <div class="product-price">
                            <span class="current"><?= formatPrix($prixAffiche) ?></span>
                            <?php if ($hasPromo): ?>
                            <span class="old"><?= formatPrix($p['prix']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="product-card-footer">
                        <?php if ($stock > 0 && !$p['a_variants']): ?>
                        <button class="btn btn-primary btn-sm" onclick="Cart.add(<?= $p['id'] ?>)">
                            <i class="fas fa-cart-plus"></i> Ajouter
                        </button>
                        <?php elseif ($p['a_variants']): ?>
                        <a href="<?= BASE_URL ?>/produit.php?id=<?= $p['id'] ?>" class="btn btn-secondary btn-sm">
                            <i class="fas fa-list"></i> Options
                        </a>
                        <?php else: ?>
                        <button class="btn btn-sm" disabled>Épuisé</button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                <a href="<?= BASE_URL ?>/boutique.php?<?= buildQueryString(['page' => $page - 1]) ?>" class="page-btn"><i class="fas fa-chevron-left"></i></a>
                <?php endif; ?>
                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                <a href="<?= BASE_URL ?>/boutique.php?<?= buildQueryString(['page' => $i]) ?>" class="page-btn <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>
                <?php if ($page < $totalPages): ?>
                <a href="<?= BASE_URL ?>/boutique.php?<?= buildQueryString(['page' => $page + 1]) ?>" class="page-btn"><i class="fas fa-chevron-right"></i></a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
