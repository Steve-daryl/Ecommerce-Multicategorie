<?php
/**
 * ShopMax — Order Handler
 * Validates order, saves to DB, generates WhatsApp invoice
 */
require_once __DIR__ . '/../includes/config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

$cart = $_SESSION['cart'] ?? [];
if (empty($cart)) {
    echo json_encode(['success' => false, 'message' => 'Votre panier est vide']);
    exit;
}

// Validate fields
$nom = trim($_POST['nom_client'] ?? '');
$whatsapp = trim($_POST['whatsapp'] ?? '');
$adresse = trim($_POST['adresse'] ?? '');
$ville = trim($_POST['ville'] ?? '');
$note = trim($_POST['note'] ?? '');

$errors = [];
if (empty($nom)) $errors[] = 'Le nom est requis';
if (empty($whatsapp)) $errors[] = 'Le numéro WhatsApp est requis';
if (empty($adresse)) $errors[] = 'L\'adresse est requise';
if (empty($ville)) $errors[] = 'La ville est requise';

if (!empty($errors)) {
    echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
    exit;
}

$pdo = getPDO();
$params = getAllParams();
$fraisLivraison = (int)($params['frais_livraison'] ?? 2000);
$seuilGratuit = (int)($params['seuil_livraison_gratuite'] ?? 50000);
$devise = $params['devise'] ?? 'FCFA';
$nomBoutique = $params['nom_boutique'] ?? 'ShopMax';

// Calculate totals
$sousTotal = 0;
foreach ($cart as $item) {
    $sousTotal += $item['prix'] * $item['quantite'];
}
$frais = ($sousTotal >= $seuilGratuit) ? 0 : $fraisLivraison;
$total = $sousTotal + $frais;

// Generate order number
$stmt = $pdo->query("SELECT COUNT(*) FROM commandes");
$count = (int)$stmt->fetchColumn() + 1;
$numeroCMD = 'CMD-' . date('Y') . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);

try {
    $pdo->beginTransaction();

    // Insert order
    $stmt = $pdo->prepare("INSERT INTO commandes (numero_cmd, nom_client, whatsapp, adresse_livraison, ville, note_client, sous_total, frais_livraison, total, statut, date_commande)
        VALUES (:num, :nom, :wa, :adr, :ville, :note, :st, :fl, :total, 'en_attente', NOW())");
    $stmt->execute([
        'num' => $numeroCMD, 'nom' => $nom, 'wa' => $whatsapp,
        'adr' => $adresse, 'ville' => $ville, 'note' => $note,
        'st' => $sousTotal, 'fl' => $frais, 'total' => $total
    ]);
    $commandeId = $pdo->lastInsertId();

    // Insert items
    $stmtItem = $pdo->prepare("INSERT INTO commande_items (commande_id, produit_id, variante_id, nom_produit, prix_unitaire, quantite) VALUES (:cid, :pid, :vid, :nom, :prix, :qte)");
    foreach ($cart as $item) {
        $stmtItem->execute([
            'cid' => $commandeId,
            'pid' => $item['product_id'],
            'vid' => $item['variant_id'],
            'nom' => $item['nom'] . ($item['variante'] ? ' — ' . $item['variante'] : ''),
            'prix' => $item['prix'],
            'qte' => $item['quantite']
        ]);
    }

    // Generate WhatsApp invoice text
    $facture = "🧾 *FACTURE DE COMMANDE*\n";
    $facture .= "━━━━━━━━━━━━━━━━━━━━━━━━\n";
    $facture .= "📌 N° Commande : {$numeroCMD}\n";
    $facture .= "📅 Date : " . date('d/m/Y à H\hi') . "\n";
    $facture .= "👤 Client : {$nom}\n";
    $facture .= "📞 WhatsApp : {$whatsapp}\n";
    $facture .= "📍 Adresse : {$adresse}, {$ville}\n";
    if ($note) $facture .= "📝 Note : {$note}\n";
    $facture .= "━━━━━━━━━━━━━━━━━━━━━━━━\n";
    $facture .= "🛒 *DÉTAIL DES PRODUITS :*\n\n";

    foreach ($cart as $item) {
        $nomP = $item['nom'] . ($item['variante'] ? ' (' . $item['variante'] . ')' : '');
        $sTotal = number_format($item['prix'] * $item['quantite'], 0, ',', ' ');
        $facture .= "• {$nomP} x{$item['quantite']} → {$sTotal} {$devise}\n";
    }

    $facture .= "\n━━━━━━━━━━━━━━━━━━━━━━━━\n";
    $facture .= "💰 Sous-total  : " . number_format($sousTotal, 0, ',', ' ') . " {$devise}\n";
    $facture .= "🚚 Livraison   : " . ($frais == 0 ? 'GRATUITE' : number_format($frais, 0, ',', ' ') . ' ' . $devise) . "\n";
    $facture .= "✅ *TOTAL TTC  : " . number_format($total, 0, ',', ' ') . " {$devise}*\n\n";
    $facture .= "Merci pour votre commande sur {$nomBoutique} ! 🙏";

    // Save invoice text
    $stmtF = $pdo->prepare("UPDATE commandes SET facture_texte = :f WHERE id = :id");
    $stmtF->execute(['f' => $facture, 'id' => $commandeId]);

    $pdo->commit();

    // Clear cart
    $_SESSION['cart'] = [];

    // Build WhatsApp URL
    $waNumber = preg_replace('/[^0-9]/', '', $params['whatsapp'] ?? '');
    $waUrl = 'https://wa.me/' . $waNumber . '?text=' . rawurlencode($facture);

    echo json_encode([
        'success' => true,
        'message' => 'Commande enregistrée !',
        'numero_cmd' => $numeroCMD,
        'total' => $total,
        'whatsapp_url' => $waUrl,
        'facture' => $facture
    ]);

} catch (Exception $ex) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'enregistrement: ' . $ex->getMessage()]);
}
