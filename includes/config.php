<?php
/**
 * ShopMax — Configuration globale
 * Session, connexion BDD, fonctions utilitaires
 */

// Démarrer la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Charger la connexion BDD
require_once __DIR__ . '/../DB/db.php';

/**
 * Récupérer la connexion PDO
 */
function getPDO(): PDO {
    return Database::getInstance()->getConnection();
}

/**
 * Récupérer un paramètre du site
 */
function getParam(string $cle, string $default = ''): string {
    static $cache = [];
    if (isset($cache[$cle])) return $cache[$cle];
    
    $pdo = getPDO();
    $stmt = $pdo->prepare("SELECT valeur FROM parametres WHERE cle = :cle LIMIT 1");
    $stmt->execute(['cle' => $cle]);
    $val = $stmt->fetchColumn();
    $cache[$cle] = $val !== false ? $val : $default;
    return $cache[$cle];
}

/**
 * Récupérer tous les paramètres
 */
function getAllParams(): array {
    static $all = null;
    if ($all !== null) return $all;
    
    $pdo = getPDO();
    $stmt = $pdo->query("SELECT cle, valeur FROM parametres");
    $all = [];
    while ($row = $stmt->fetch()) {
        $all[$row['cle']] = $row['valeur'];
    }
    return $all;
}

/**
 * Échapper pour HTML
 */
function e(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

/**
 * Formater le prix en FCFA
 */
function formatPrix($prix): string {
    $devise = getParam('devise', 'FCFA');
    return number_format((float)$prix, 0, ',', ' ') . ' ' . $devise;
}

/**
 * Obtenir le nombre d'articles dans le panier
 */
function getCartCount(): int {
    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) return 0;
    $count = 0;
    foreach ($_SESSION['cart'] as $item) {
        $count += (int)($item['quantite'] ?? 0);
    }
    return $count;
}

/**
 * Obtenir le total du panier
 */
function getCartTotal(): float {
    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) return 0;
    $total = 0;
    foreach ($_SESSION['cart'] as $item) {
        $prix = (float)($item['prix'] ?? 0);
        $qte = (int)($item['quantite'] ?? 0);
        $total += $prix * $qte;
    }
    return $total;
}

/**
 * Récupérer les catégories actives
 */
function getCategories(): array {
    $pdo = getPDO();
    $stmt = $pdo->query("SELECT * FROM categories WHERE actif = 1 ORDER BY ordre_affichage ASC");
    return $stmt->fetchAll();
}

/**
 * Récupérer les marques actives
 */
function getMarques(): array {
    $pdo = getPDO();
    $stmt = $pdo->query("SELECT * FROM marques WHERE actif = 1 ORDER BY nom ASC");
    return $stmt->fetchAll();
}

/**
 * Récupérer l'image principale d'un produit
 */
function getImagePrincipale(int $produitId): string {
    $pdo = getPDO();
    $stmt = $pdo->prepare("SELECT chemin FROM image_produit WHERE produit_id = :id ORDER BY ordre ASC LIMIT 1");
    $stmt->execute(['id' => $produitId]);
    $chemin = $stmt->fetchColumn();
    return $chemin ?: '/assets/images/no-image.png';
}

/**
 * Calculer le pourcentage de réduction
 */
function calcRemise(float $prix, float $prixPromo): int {
    if ($prix <= 0) return 0;
    return round((($prix - $prixPromo) / $prix) * 100);
}

/**
 * Vérifier le stock effectif d'un produit
 */
function getStockEffectif(array $produit): int {
    if ($produit['a_variants']) {
        $pdo = getPDO();
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(stock), 0) FROM produit_variantes WHERE produit_id = :id AND actif = 1");
        $stmt->execute(['id' => $produit['id']]);
        return (int)$stmt->fetchColumn();
    }
    return (int)$produit['stock'];
}

// Chemin de base
define('BASE_URL', '/ecommerceMbeppa/version2');
