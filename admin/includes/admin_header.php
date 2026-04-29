<?php
/**
 * ShopMax — Admin Header
 */
require_once __DIR__ . '/../../includes/config.php';

// Verification de connexion
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$publicPages = ['login'];

if (!in_array($currentPage, $publicPages)) {
    if (!isset($_SESSION['admin_id'])) {
        header("Location: login.php");
        exit;
    }
}

// Helper: Check Permission
function hasPermission($module) {
    if (($_SESSION['admin_role'] ?? '') === 'super_admin') return true;
    $perms = $_SESSION['admin_permissions'] ?? [];
    return in_array($module, $perms);
}

// Redirect if un-authorized
$moduleAccess = [
    'index' => 'dashboard',
    'commandes' => 'commandes',
    'facture' => 'commandes', // Inherits from commandes
    'produits' => 'produits',
    'categories' => 'categories',
    'marques' => 'marques',
    'parametres' => 'parametres',
    'utilisateurs' => 'utilisateurs'
];

if (!in_array($currentPage, $publicPages) && isset($moduleAccess[$currentPage])) {
    $reqMod = $moduleAccess[$currentPage];
    if (!hasPermission($reqMod) && $currentPage !== 'index') {
        // Fallback or deny
        die("Accès Refusé. Vous n'avez pas la permission d'accéder à ce module.");
    }
}

$adminName = $_SESSION['admin_username'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? e($pageTitle) . ' — ' : '' ?>ShopMax Admin</title>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Admin Stylesheet -->
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>

<?php if (!in_array($currentPage, $publicPages)): ?>
<div class="admin-layout">
    <!-- Sidebar -->
    <aside class="admin-sidebar" id="adminSidebar">
        <div class="sidebar-header">
            <span class="logo-icon"><i class="fas fa-bolt"></i></span>
            <span class="logo-text">ShopMax Panel</span>
        </div>
        
        <nav class="sidebar-nav">
            <?php if(hasPermission('dashboard')): ?>
            <div class="nav-title">Menu Principal</div>
            <a href="index.php" class="nav-item <?= $currentPage === 'index' ? 'active' : '' ?>">
                <i class="fas fa-chart-pie"></i> Tableau de bord
            </a>
            <?php endif; ?>
            
            <?php if(hasPermission('commandes')): ?>
            <a href="commandes.php" class="nav-item <?= $currentPage === 'commandes' ? 'active' : '' ?>">
                <i class="fas fa-shopping-cart"></i> Commandes
            </a>
            <?php endif; ?>
            
            <?php if(hasPermission('produits')): ?>
            <a href="produits.php" class="nav-item <?= $currentPage === 'produits' ? 'active' : '' ?>">
                <i class="fas fa-box-open"></i> Produits
            </a>
            <?php endif; ?>
            
            <?php if(hasPermission('categories') || hasPermission('marques')): ?>
            <div class="nav-title">Catalogue</div>
            <?php if(hasPermission('categories')): ?>
            <a href="categories.php" class="nav-item <?= $currentPage === 'categories' ? 'active' : '' ?>">
                <i class="fas fa-layer-group"></i> Catégories
            </a>
            <?php endif; ?>
            <?php if(hasPermission('marques')): ?>
            <a href="marques.php" class="nav-item <?= $currentPage === 'marques' ? 'active' : '' ?>">
                <i class="fas fa-tags"></i> Marques
            </a>
            <?php endif; ?>
            <?php endif; ?>
            
            <?php if(hasPermission('parametres') || hasPermission('utilisateurs')): ?>
            <div class="nav-title">Système</div>
            <?php if(hasPermission('parametres')): ?>
            <a href="parametres.php" class="nav-item <?= $currentPage === 'parametres' ? 'active' : '' ?>">
                <i class="fas fa-cog"></i> Paramètres
            </a>
            <?php endif; ?>
            <?php if(hasPermission('utilisateurs')): ?>
            <a href="utilisateurs.php" class="nav-item <?= $currentPage === 'utilisateurs' ? 'active' : '' ?>">
                <i class="fas fa-users"></i> Utilisateurs
            </a>
            <?php endif; ?>
            <?php endif; ?>
        </nav>
        
        <div class="sidebar-footer">
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="admin-main">
        <header class="admin-header">
            <div class="header-left">
                <button class="btn-toggle-sidebar" id="toggleSidebar">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
            <div class="header-right">
                <a href="../" target="_blank" class="btn btn-secondary btn-sm" title="Voir la boutique">
                    <i class="fas fa-external-link-alt"></i> Boutique
                </a>
                <div class="user-profile">
                    <div class="user-avatar"><?= strtoupper(substr($adminName, 0, 1)) ?></div>
                    <span><?= e($adminName) ?></span>
                </div>
            </div>
        </header>
        
        <div class="admin-content">
<?php endif; ?>
