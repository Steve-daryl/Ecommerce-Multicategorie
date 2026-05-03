<?php
/**
 * ShopMax — Header partagé
 */
require_once __DIR__ . '/config.php';

$params = getAllParams();
$nomBoutique = $params['nom_boutique'] ?? 'ShopMax';
$slogan = $params['slogan'] ?? '';
$cartCount = getCartCount();
$categories = getCategories();

// Page courante pour le menu actif
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="fr" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= e($slogan) ?> — <?= e($nomBoutique) ?>">
    <title><?= isset($pageTitle) ? e($pageTitle) . ' — ' : '' ?><?= e($nomBoutique) ?></title>
    
    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Stylesheets -->
    <?php $v = time(); ?>
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/variables.css?v=<?= $v ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/base.css?v=<?= $v ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/components.css?v=<?= $v ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/pages.css?v=<?= $v ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/footer-responsive.css?v=<?= $v ?>">
</head>
<body>

<!-- Announcement Bar -->
<div class="announcement-bar">
    <div class="container">
        <p><i class="fas fa-truck"></i> Livraison gratuite à partir de <?= formatPrix($params['seuil_livraison_gratuite'] ?? 50000) ?> &nbsp;|&nbsp; <i class="fas fa-shield-halved"></i> Paiement sécurisé via WhatsApp</p>
    </div>
</div>

<!-- Header -->
<header class="site-header" id="siteHeader">
    <div class="container header-inner">
        <!-- Logo -->
        <a href="<?= BASE_URL ?>/" class="logo">
            <span class="logo-icon"><i class="fas fa-bolt"></i></span>
            <span class="logo-text"><?= e($nomBoutique) ?></span>
        </a>

        <!-- Navigation -->
        <nav class="main-nav" id="mainNav">
            <a href="<?= BASE_URL ?>/" class="nav-link <?= $currentPage === 'index' ? 'active' : '' ?>">
                <i class="fas fa-home"></i> Accueil
            </a>
            <a href="<?= BASE_URL ?>/boutique" class="nav-link <?= $currentPage === 'boutique' ? 'active' : '' ?>">
                <i class="fas fa-store"></i> Boutique
            </a>
            <div class="nav-dropdown">
                <a href="#" class="nav-link dropdown-toggle">
                    <i class="fas fa-layer-group"></i> Catégories <i class="fas fa-chevron-down"></i>
                </a>
                <div class="dropdown-menu">
                    <?php foreach ($categories as $cat): ?>
                    <a href="<?= BASE_URL ?>/boutique?categorie=<?= $cat['id'] ?>" class="dropdown-item">
                        <span class="cat-icon"><?= $cat['icone'] ?></span>
                        <?= e($cat['nom']) ?>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <a href="<?= BASE_URL ?>/panier" class="nav-link <?= $currentPage === 'panier' ? 'active' : '' ?>">
                <i class="fas fa-shopping-bag"></i> Panier
            </a>
            <a href="<?= BASE_URL ?>/contact" class="nav-link <?= $currentPage === 'contact' ? 'active' : '' ?>">
                <i class="fas fa-envelope"></i> Contact
            </a>
        </nav>

        <!-- Header Actions -->
        <div class="header-actions">
            <!-- Search Toggle -->
            <button class="header-btn" id="searchToggle" aria-label="Rechercher">
                <i class="fas fa-search"></i>
            </button>

            <!-- Theme Toggle -->
            <button class="header-btn" id="themeToggle" aria-label="Changer de thème">
                <i class="fas fa-moon" id="themeIcon"></i>
            </button>

            <!-- Cart -->
            <a href="<?= BASE_URL ?>/panier" class="header-btn cart-btn" id="cartBtn">
                <i class="fas fa-shopping-bag"></i>
                <span class="cart-badge" id="cartBadge" <?= $cartCount === 0 ? 'style="display:none"' : '' ?>><?= $cartCount ?></span>
            </a>

            <!-- Mobile Menu Toggle -->
            <button class="header-btn mobile-toggle" id="mobileToggle" aria-label="Menu">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </div>

    <!-- Search Overlay -->
    <div class="search-overlay" id="searchOverlay">
        <div class="container">
            <form action="<?= BASE_URL ?>/boutique" method="GET" class="search-form">
                <input type="text" name="q" placeholder="Rechercher un produit..." class="search-input" id="searchInput" autocomplete="off">
                <button type="submit" class="search-submit"><i class="fas fa-search"></i></button>
                <button type="button" class="search-close" id="searchClose"><i class="fas fa-times"></i></button>
            </form>
        </div>
    </div>
</header>

<!-- Mini Cart Dropdown -->
<div class="mini-cart" id="miniCart">
    <div class="mini-cart-header">
        <h4>Mon Panier</h4>
        <button class="mini-cart-close" id="miniCartClose"><i class="fas fa-times"></i></button>
    </div>
    <div class="mini-cart-items" id="miniCartItems">
        <?php $cartItems = $_SESSION['cart'] ?? []; ?>
        <?php if (empty($cartItems)): ?>
            <div class="mini-cart-empty"><i class="fas fa-shopping-bag"></i><p>Votre panier est vide</p></div>
        <?php else: ?>
            <?php foreach ($cartItems as $key => $item): ?>
                <?php $imgSrc = !empty($item['image']) ? BASE_URL . $item['image'] : BASE_URL . '/assets/images/no-image.png'; ?>
                <div class="mini-cart-item">
                    <img src="<?= e($imgSrc) ?>" alt="<?= e($item['nom']) ?>" onerror="this.src='<?= BASE_URL ?>/assets/images/no-image.png'">
                    <div class="mini-cart-item-info">
                        <h5><?= e($item['nom']) ?></h5>
                        <?php if (!empty($item['variante'])): ?><span class="qty"><?= e($item['variante']) ?></span><?php endif; ?>
                        <div class="price"><?= $item['quantite'] ?> × <?= formatPrix($item['prix']) ?></div>
                    </div>
                    <button class="mini-cart-item-remove" onclick="Cart.remove('<?= e($key) ?>')"><i class="fas fa-trash-alt"></i></button>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <div class="mini-cart-footer">
        <div class="mini-cart-total">
            <span>Total</span>
            <strong id="miniCartTotal"><?= formatPrix(getCartTotal()) ?></strong>
        </div>
        <a href="<?= BASE_URL ?>/panier" class="btn btn-primary btn-block">
            Voir le panier <i class="fas fa-arrow-right"></i>
        </a>
    </div>
</div>
<div class="mini-cart-overlay" id="miniCartOverlay"></div>

<!-- Mobile Menu Overlay -->
<div class="mobile-overlay" id="mobileOverlay"></div>

<main class="main-content">
