<?php
/**
 * ShopMax — AJAX Cart Actions
 * Actions: add, update, remove, get
 */
require_once __DIR__ . '/../includes/config.php';
header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'add':
        addToCart();
        break;
    case 'update':
        updateCart();
        break;
    case 'remove':
        removeFromCart();
        break;
    case 'get':
        getCart();
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Action invalide']);
}

function addToCart() {
    $pdo = getPDO();
    $productId = (int)($_POST['product_id'] ?? 0);
    $quantity = max(1, (int)($_POST['quantity'] ?? 1));
    $variantId = !empty($_POST['variant_id']) ? (int)$_POST['variant_id'] : null;

    // Vérifier le produit
    $stmt = $pdo->prepare("SELECT p.*, m.nom AS marque_nom FROM produits p LEFT JOIN marques m ON m.id = p.marque_id WHERE p.id = :id AND p.actif = 1 AND p.supprime = 0");
    $stmt->execute(['id' => $productId]);
    $product = $stmt->fetch();
    if (!$product) { echo json_encode(['success' => false, 'message' => 'Produit introuvable']); return; }

    // Déterminer prix et stock
    $prix = (float)$product['prix'];
    $stock = (int)$product['stock'];
    $nomProduit = $product['nom'];
    $varianteLabel = null;

    if ($product['prix_promo']) $prix = (float)$product['prix_promo'];

    if ($variantId && $product['a_variants']) {
        $stmtV = $pdo->prepare("SELECT * FROM produit_variantes WHERE id = :id AND produit_id = :pid AND actif = 1");
        $stmtV->execute(['id' => $variantId, 'pid' => $productId]);
        $variant = $stmtV->fetch();
        if (!$variant) { echo json_encode(['success' => false, 'message' => 'Variante introuvable']); return; }
        $prix = (float)$variant['prix'];
        $stock = (int)$variant['stock'];
        $varianteLabel = $variant['valeur'];
        $nomProduit .= ' — ' . $variant['valeur'];
    }

    // Vérifier stock
    if ($stock < $quantity) { echo json_encode(['success' => false, 'message' => 'Stock insuffisant (disponible: ' . $stock . ')']); return; }

    // Clé unique du panier
    $cartKey = $productId . '_' . ($variantId ?? '0');

    if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

    if (isset($_SESSION['cart'][$cartKey])) {
        $newQty = $_SESSION['cart'][$cartKey]['quantite'] + $quantity;
        if ($newQty > $stock) { echo json_encode(['success' => false, 'message' => 'Quantité max atteinte (stock: ' . $stock . ')']); return; }
        $_SESSION['cart'][$cartKey]['quantite'] = $newQty;
    } else {
        $image = getImagePrincipale($productId);
        $_SESSION['cart'][$cartKey] = [
            'product_id' => $productId,
            'variant_id' => $variantId,
            'nom' => $product['nom'],
            'variante' => $varianteLabel,
            'marque' => $product['marque_nom'],
            'prix' => $prix,
            'quantite' => $quantity,
            'image' => $image,
            'stock' => $stock
        ];
    }

    echo json_encode([
        'success' => true,
        'message' => e($product['nom']) . ' ajouté au panier !',
        'cartCount' => getCartCount(),
        'cartTotal' => getCartTotal(),
        'cartItems' => getCartItemsArray()
    ]);
}

function updateCart() {
    $cartKey = $_POST['cart_key'] ?? '';
    $quantity = max(0, (int)($_POST['quantity'] ?? 0));

    if (!isset($_SESSION['cart'][$cartKey])) {
        echo json_encode(['success' => false, 'message' => 'Article introuvable']);
        return;
    }

    if ($quantity <= 0) {
        unset($_SESSION['cart'][$cartKey]);
    } else {
        $stock = $_SESSION['cart'][$cartKey]['stock'] ?? 999;
        $_SESSION['cart'][$cartKey]['quantite'] = min($quantity, $stock);
    }

    echo json_encode([
        'success' => true,
        'cartCount' => getCartCount(),
        'cartTotal' => getCartTotal(),
        'cartItems' => getCartItemsArray()
    ]);
}

function removeFromCart() {
    $cartKey = $_POST['cart_key'] ?? '';
    if (isset($_SESSION['cart'][$cartKey])) {
        unset($_SESSION['cart'][$cartKey]);
    }
    echo json_encode([
        'success' => true,
        'cartCount' => getCartCount(),
        'cartTotal' => getCartTotal(),
        'cartItems' => getCartItemsArray()
    ]);
}

function getCart() {
    echo json_encode([
        'success' => true,
        'cartCount' => getCartCount(),
        'cartTotal' => getCartTotal(),
        'cartItems' => getCartItemsArray()
    ]);
}

function getCartItemsArray(): array {
    if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) return [];
    $items = [];
    foreach ($_SESSION['cart'] as $key => $item) {
        $items[] = array_merge($item, ['key' => $key]);
    }
    return $items;
}
