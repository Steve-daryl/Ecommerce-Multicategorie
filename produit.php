<?php
/**
 * ShopMax — Page Détail Produit
 */
require_once __DIR__ . '/includes/config.php';
$pdo = getPDO();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { header('Location: ' . BASE_URL . '/boutique'); exit; }

// Fetch product
$stmt = $pdo->prepare("
    SELECT p.*, m.nom AS marque_nom, m.slug AS marque_slug, c.nom AS categorie_nom, c.id AS categorie_id
    FROM produits p
    LEFT JOIN marques m ON m.id = p.marque_id
    LEFT JOIN categories c ON c.id = p.categorie_id
    WHERE p.id = :id AND p.actif = 1 AND p.supprime = 0
");
$stmt->execute(['id' => $id]);
$prod = $stmt->fetch();
if (!$prod) { header('Location: ' . BASE_URL . '/boutique'); exit; }

// Images
$stmtImg = $pdo->prepare("SELECT * FROM image_produit WHERE produit_id = :id ORDER BY ordre ASC");
$stmtImg->execute(['id' => $id]);
$images = $stmtImg->fetchAll();
if (empty($images)) $images = [['chemin' => '/assets/images/no-image.png', 'alt_text' => $prod['nom']]];

// Variants
$variantes = [];
if ($prod['a_variants']) {
    $stmtV = $pdo->prepare("SELECT * FROM produit_variantes WHERE produit_id = :id AND actif = 1 ORDER BY prix ASC");
    $stmtV->execute(['id' => $id]);
    $variantes = $stmtV->fetchAll();
}

// Related products
$stmtR = $pdo->prepare("
    SELECT p.*, m.nom AS marque_nom,
    (SELECT chemin FROM image_produit WHERE produit_id = p.id ORDER BY ordre ASC LIMIT 1) AS image
    FROM produits p LEFT JOIN marques m ON m.id = p.marque_id
    WHERE p.categorie_id = :cat AND p.id != :id AND p.actif = 1 AND p.supprime = 0
    ORDER BY RAND() LIMIT 4
");
$stmtR->execute(['cat' => $prod['categorie_id'], 'id' => $id]);
$related = $stmtR->fetchAll();

$stock = getStockEffectif($prod);
$hasPromo = !empty($prod['prix_promo']);
$prixAffiche = $hasPromo ? $prod['prix_promo'] : $prod['prix'];

$pageTitle = $prod['nom'];
require_once __DIR__ . '/includes/header.php';
?>

<div class="container">
    <!-- Breadcrumb -->
    <div class="breadcrumb">
        <a href="<?= BASE_URL ?>/">Accueil</a>
        <span class="sep"><i class="fas fa-chevron-right"></i></span>
        <a href="<?= BASE_URL ?>/boutique">Boutique</a>
        <span class="sep"><i class="fas fa-chevron-right"></i></span>
        <a href="<?= BASE_URL ?>/boutique?categorie=<?= $prod['categorie_id'] ?>"><?= e($prod['categorie_nom']) ?></a>
        <span class="sep"><i class="fas fa-chevron-right"></i></span>
        <span><?= e($prod['nom']) ?></span>
    </div>

    <div class="product-detail">
        <!-- Gallery -->
        <div class="gallery">
            <div class="gallery-main">
                <img src="<?= BASE_URL . e($images[0]['chemin']) ?>" alt="<?= e($images[0]['alt_text'] ?? $prod['nom']) ?>" id="mainImage"
                     onerror="this.src='https://placehold.co/600x600/E8F0FB/082F63?text=<?= urlencode($prod['nom']) ?>'">
            </div>
            <?php if (count($images) > 1): ?>
            <div class="gallery-thumbs">
                <?php foreach ($images as $i => $img): ?>
                <div class="gallery-thumb <?= $i === 0 ? 'active' : '' ?>">
                    <img src="<?= BASE_URL . e($img['chemin']) ?>" alt="<?= e($img['alt_text'] ?? '') ?>"
                         onerror="this.src='https://placehold.co/100x100/E8F0FB/082F63?text=<?= $i+1 ?>'">
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Product Info -->
        <div class="product-info" data-base-price="<?= $prixAffiche ?>">
            <?php if ($prod['marque_nom']): ?>
            <div class="brand"><?= e($prod['marque_nom']) ?></div>
            <?php endif; ?>

            <h1><?= e($prod['nom']) ?></h1>

            <div class="price-block">
                <span class="price-current" id="productPrice"><?= formatPrix($prixAffiche) ?></span>
                <?php if ($hasPromo): ?>
                <span class="price-old"><?= formatPrix($prod['prix']) ?></span>
                <span class="price-badge">-<?= calcRemise($prod['prix'], $prod['prix_promo']) ?>%</span>
                <?php endif; ?>
            </div>

            <?php if ($prod['description']): ?>
            <p class="short-desc"><?= e($prod['description']) ?></p>
            <?php endif; ?>

            <div class="stock-info <?= $stock > 0 ? 'in-stock' : 'out-of-stock' ?>">
                <i class="fas fa-<?= $stock > 0 ? 'check' : 'times' ?>-circle"></i>
                <?= $stock > 0 ? "En stock ($stock disponible" . ($stock > 1 ? 's' : '') . ")" : 'Rupture de stock' ?>
            </div>

            <?php if (!empty($variantes)): ?>
            <div class="variant-selector">
                <label>Choisir une option :</label>
                <div class="variant-options">
                    <!-- Produit Principal (Option par défaut) -->
                    <div class="variant-option active <?= $prod['stock'] <= 0 ? 'disabled' : '' ?>" data-id="" data-prix="<?= $prixAffiche ?>" data-stock="<?= $prod['stock'] ?>">
                        Standard
                        <small>(<?= formatPrix($prixAffiche) ?>)</small>
                    </div>

                    <?php foreach ($variantes as $v): ?>
                    <div class="variant-option <?= $v['stock'] <= 0 ? 'disabled' : '' ?>"
                         data-id="<?= $v['id'] ?>" data-prix="<?= $v['prix'] ?>" data-stock="<?= $v['stock'] ?>">
                        <?= e($v['valeur']) ?>
                        <small>(<?= formatPrix($v['prix']) ?>)</small>
                    </div>
                    <?php endforeach; ?>
                </div>
                <input type="hidden" id="selectedVariantId" value="">
            </div>
            <?php endif; ?>

            <div class="qty-selector">
                <label>Quantité :</label>
                <div class="qty-controls">
                    <button class="qty-btn qty-minus" type="button"><i class="fas fa-minus"></i></button>
                    <input type="number" class="qty-input" id="qtyInput" value="1" min="1" max="<?= $stock ?>">
                    <button class="qty-btn qty-plus" type="button"><i class="fas fa-plus"></i></button>
                </div>
            </div>

            <button class="btn btn-primary add-to-cart-btn" id="addToCartBtn" <?= $stock <= 0 ? 'disabled' : '' ?>
                onclick="handleAddToCart()">
                <i class="fas fa-cart-plus"></i>
                <?= $stock > 0 ? 'Ajouter au panier' : 'Produit épuisé' ?>
            </button>
        </div>
    </div>

    <!-- Tabs -->
    <div class="product-tabs">
        <div class="tab-headers">
            <div class="tab-header active" data-tab="tab-desc">Description</div>
            <div class="tab-header" data-tab="tab-specs">Caractéristiques</div>
        </div>
        <div class="tab-content active" id="tab-desc">
            <?= nl2br(e($prod['description_long'] ?? $prod['description'] ?? 'Aucune description détaillée disponible.')) ?>
        </div>
        <div class="tab-content" id="tab-specs">
            <?php if ($prod['caracteristiques']): ?>
            <table class="specs-table">
                <?php foreach (explode("\n", $prod['caracteristiques']) as $line):
                    $parts = explode(':', $line, 2);
                    if (count($parts) === 2): ?>
                <tr>
                    <td><?= e(trim($parts[0])) ?></td>
                    <td><?= e(trim($parts[1])) ?></td>
                </tr>
                <?php endif; endforeach; ?>
            </table>
            <?php else: ?>
            <p>Aucune caractéristique renseignée.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if (!empty($related)): ?>
<!-- Related Products -->
<section class="related-section">
    <div class="container">
        <div class="section-header">
            <h2>Produits Similaires</h2>
            <p>Vous pourriez aussi aimer</p>
        </div>
        <div class="products-grid">
            <?php foreach ($related as $r): ?>
            <?php
                $rImg = $r['image'] ?: '/assets/images/no-image.png';
                $rHasPromo = !empty($r['prix_promo']);
                $rPrix = $rHasPromo ? $r['prix_promo'] : $r['prix'];
            ?>
            <div class="product-card">
                <div class="product-card-img">
                    <img src="<?= BASE_URL . e($rImg) ?>" alt="<?= e($r['nom']) ?>" loading="lazy"
                         onerror="this.src='https://placehold.co/400x300/E8F0FB/082F63?text=<?= urlencode($r['nom']) ?>'">
                    <?php if ($rHasPromo): ?>
                    <div class="product-badges"><span class="badge badge-promo">-<?= calcRemise($r['prix'], $r['prix_promo']) ?>%</span></div>
                    <?php endif; ?>
                </div>
                <div class="product-card-body">
                    <div class="product-card-brand"><?= e($r['marque_nom'] ?? '') ?></div>
                    <h3><a href="<?= BASE_URL ?>/produit?id=<?= $r['id'] ?>"><?= e($r['nom']) ?></a></h3>
                    <div class="product-price">
                        <span class="current"><?= formatPrix($rPrix) ?></span>
                        <?php if ($rHasPromo): ?><span class="old"><?= formatPrix($r['prix']) ?></span><?php endif; ?>
                    </div>
                </div>
                <div class="product-card-footer">
                    <a href="<?= BASE_URL ?>/produit?id=<?= $r['id'] ?>" class="btn btn-secondary btn-sm btn-block">Voir détails</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<script>
function handleAddToCart() {
    const qty = parseInt(document.getElementById('qtyInput').value) || 1;
    const variantId = document.getElementById('selectedVariantId')?.value || null;
    <?php if ($prod['a_variants']): ?>
    // On vérifie qu'une option est bien active (le Standard par défaut ou une variante)
    if (variantId === null && !document.querySelector('.variant-option.active')) { 
        Toast.show('Veuillez choisir une option', 'error'); 
        return; 
    }
    <?php endif; ?>
    Cart.add(<?= $prod['id'] ?>, qty, variantId);
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
