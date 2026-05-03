<?php
/**
 * ShopMax — Page d'Accueil
 */
$pageTitle = 'Accueil';
require_once __DIR__ . '/includes/header.php';

$pdo = getPDO();

// Obtenir les IDs des top 7 produits les plus vendus (commandes livrées)
$stmtTop = $pdo->query("
    SELECT ci.produit_id
    FROM commande_items ci
    JOIN commandes cmd ON cmd.id = ci.commande_id
    WHERE cmd.statut = 'livree'
    GROUP BY ci.produit_id
    ORDER BY SUM(ci.quantite) DESC
    LIMIT 7
");
$topIds = $stmtTop->fetchAll(PDO::FETCH_COLUMN);
$topIdsList = empty($topIds) ? '0' : implode(',', $topIds);

// Produits vedettes (manuels + top 7 des ventes)
$stmtVedettes = $pdo->query("
    SELECT p.*, m.nom AS marque_nom, c.nom AS categorie_nom,
    (SELECT chemin FROM image_produit WHERE produit_id = p.id ORDER BY ordre ASC LIMIT 1) AS image
    FROM produits p
    LEFT JOIN marques m ON m.id = p.marque_id
    LEFT JOIN categories c ON c.id = p.categorie_id
    WHERE p.actif = 1 AND p.supprime = 0 
    AND (p.vedette = 1 OR p.id IN ($topIdsList))
    ORDER BY p.vedette DESC, p.created_at DESC 
    LIMIT 8
");
$vedettes = $stmtVedettes->fetchAll();

// Produits en promo
$stmtPromos = $pdo->query("
    SELECT p.*, m.nom AS marque_nom,
    (SELECT chemin FROM image_produit WHERE produit_id = p.id ORDER BY ordre ASC LIMIT 1) AS image
    FROM produits p
    LEFT JOIN marques m ON m.id = p.marque_id
    WHERE p.actif = 1 AND p.supprime = 0 AND p.prix_promo IS NOT NULL
    ORDER BY (p.prix - p.prix_promo) DESC LIMIT 4
");
$promos = $stmtPromos->fetchAll();
?>

<!-- Hero Section -->
<section class="hero">
    <div class="hero-shapes">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
        <div class="shape shape-3"></div>
    </div>
    <div class="container">
        <div class="hero-grid">
            <div class="hero-content">
                <div class="hero-badge">
                    <i class="fas fa-sparkles"></i> Bienvenue chez <?= e($nomBoutique) ?>
                </div>
                <h1>Découvrez les <span>Meilleurs Produits</span> au Meilleur Prix</h1>
                <p>Explorez notre sélection exclusive de produits de qualité. Smartphones, ordinateurs, vêtements, accessoires et bien plus encore !</p>
                <div class="hero-btns">
                    <a href="<?= BASE_URL ?>/boutique" class="btn btn-primary btn-lg">
                        <i class="fas fa-store"></i> Découvrir la Boutique
                    </a>
                    <a href="<?= BASE_URL ?>/contact" class="btn btn-secondary btn-lg">
                        <i class="fab fa-whatsapp"></i> Nous Contacter
                    </a>
                </div>
                <div class="hero-stats">
                    <div class="hero-stat"><div class="num">500+</div><div class="label">Produits</div></div>
                    <div class="hero-stat"><div class="num">2K+</div><div class="label">Clients</div></div>
                    <div class="hero-stat"><div class="num">4.9★</div><div class="label">Satisfaction</div></div>
                </div>
            </div>
            <div class="hero-visual"></div>
        </div>
    </div>
</section>

<!-- Categories Section -->
<section class="section" style="background: var(--bg-secondary);">
    <div class="container">
        <div class="section-header">
            <h2>Explorez Nos Catégories</h2>
            <p>Trouvez rapidement ce que vous cherchez parmi nos différentes collections</p>
        </div>
        <div class="categories-grid">
            <?php foreach ($categories as $cat): ?>
            <a href="<?= BASE_URL ?>/boutique?categorie=<?= $cat['id'] ?>" class="category-card">
                <span class="icon"><?= $cat['icone'] ?></span>
                <h3><?= e($cat['nom']) ?></h3>
                <p><?= e($cat['description'] ?? '') ?></p>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Featured Products -->
<section class="section">
    <div class="container">
        <div class="section-header">
            <h2>Produits Vedettes</h2>
            <p>Les produits les plus populaires choisis pour vous</p>
        </div>
        <div class="products-grid">
            <?php foreach ($vedettes as $p): ?>
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
                        <?php if ($p['vedette']): ?>
                        <span class="badge badge-vedette"><i class="fas fa-star"></i> Vedette</span>
                        <?php elseif (in_array($p['id'], $topIds ?? [])): ?>
                        <span class="badge badge-vedette" style="background: var(--danger);"><i class="fas fa-fire"></i> Top Vente</span>
                        <?php endif; ?>
                        <?php if ($stock <= 0): ?>
                        <span class="badge badge-sold-out">Épuisé</span>
                        <?php endif; ?>
                    </div>
                    <div class="product-card-actions">
                        <a href="<?= BASE_URL ?>/produit?id=<?= $p['id'] ?>" class="btn-icon" title="Voir détails">
                            <i class="fas fa-eye"></i>
                        </a>
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
                    <a href="<?= BASE_URL ?>/produit?id=<?= $p['id'] ?>" class="btn btn-secondary btn-sm">
                        <i class="fas fa-list"></i> Choisir option
                    </a>
                    <?php else: ?>
                    <button class="btn btn-sm" disabled>Épuisé</button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center" style="margin-top:40px;">
            <a href="<?= BASE_URL ?>/boutique" class="btn btn-primary btn-lg">
                Voir tous les produits <i class="fas fa-arrow-right"></i>
            </a>
        </div>
    </div>
</section>

<?php if (!empty($promos)): ?>
<!-- Promotions -->
<section class="section" style="background: var(--bg-secondary);">
    <div class="container">
        <div class="section-header">
            <span class="section-tag">🔥 Offres</span>
            <h2>Promotions en Cours</h2>
            <p>Profitez de nos meilleures offres avant qu'il ne soit trop tard</p>
        </div>
        <div class="products-grid">
            <?php foreach ($promos as $p): ?>
            <?php $img = $p['image'] ?: '/assets/images/no-image.png'; ?>
            <div class="product-card">
                <div class="product-card-img">
                    <img src="<?= BASE_URL . e($img) ?>" alt="<?= e($p['nom']) ?>" loading="lazy"
                         onerror="this.src='https://placehold.co/400x300/E8F0FB/082F63?text=<?= urlencode($p['nom']) ?>'">
                    <div class="product-badges">
                        <span class="badge badge-promo">-<?= calcRemise($p['prix'], $p['prix_promo']) ?>%</span>
                    </div>
                </div>
                <div class="product-card-body">
                    <div class="product-card-brand"><?= e($p['marque_nom'] ?? '') ?></div>
                    <h3><a href="<?= BASE_URL ?>/produit.php?id=<?= $p['id'] ?>"><?= e($p['nom']) ?></a></h3>
                    <div class="product-price">
                        <span class="current"><?= formatPrix($p['prix_promo']) ?></span>
                        <span class="old"><?= formatPrix($p['prix']) ?></span>
                    </div>
                </div>
                <div class="product-card-footer">
                    <a href="<?= BASE_URL ?>/produit?id=<?= $p['id'] ?>" class="btn btn-accent btn-sm btn-block">
                        <i class="fas fa-fire"></i> Voir l'offre
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Promo Banner -->
<section class="section">
    <div class="container">
        <div class="promo-banner">
            <div>
                <h2 style="color: white;">Livraison Gratuite dès <?= formatPrix($params['seuil_livraison_gratuite'] ?? 50000) ?></h2>
                <p>Commandez maintenant et profitez de la livraison offerte sur tous vos achats</p>
            </div>
            <a href="<?= BASE_URL ?>/boutique" class="btn btn-lg">
                Commander maintenant <i class="fas fa-arrow-right"></i>
            </a>
        </div>
    </div>
</section>

<!-- Why Choose Us -->
<section class="section" style="background: var(--bg-secondary);">
    <div class="container">
        <div class="section-header">
            <span class="section-tag">Avantages</span>
            <h2>Pourquoi Nous Choisir ?</h2>
            <p>Des avantages exclusifs pour une expérience d'achat exceptionnelle</p>
        </div>
        <div class="why-grid">
            <div class="why-card">
                <div class="icon"><i class="fas fa-truck-fast"></i></div>
                <h3>Livraison Rapide</h3>
                <p>Recevez vos commandes en 24-48h dans tout le Cameroun</p>
            </div>
            <div class="why-card">
                <div class="icon"><i class="fas fa-shield-halved"></i></div>
                <h3>Produits Garantis</h3>
                <p>Garantie sur tous nos produits avec SAV réactif</p>
            </div>
            <div class="why-card">
                <div class="icon"><i class="fas fa-headset"></i></div>
                <h3>Support 24/7</h3>
                <p>Assistance via WhatsApp disponible à tout moment</p>
            </div>
            <div class="why-card">
                <div class="icon"><i class="fas fa-tags"></i></div>
                <h3>Meilleurs Prix</h3>
                <p>Prix compétitifs et promotions régulières sur tous les produits</p>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
