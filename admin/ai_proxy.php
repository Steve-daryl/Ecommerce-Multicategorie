<?php
/**
 * ShopMax AI Assistant - Backend Proxy
 * Handles communication between Admin Panel and Gemini API
 */
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/stats_functions.php';

// Access Control
if (!isset($_SESSION['admin_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Accès non autorisé']);
    exit;
}

// Configuration
if (file_exists(__DIR__ . '/ai_config.php')) {
    require_once __DIR__ . '/ai_config.php';
    $apiKey = $ai_api_key;
} else {
    // Fallback or error
    $apiKey = ""; 
}

$model = "gemini-2.5-flash-lite";
$apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

// Handle POST request
$input = json_decode(file_get_contents('php://input'), true);
$userMessage = $input['message'] ?? '';
$history = $input['history'] ?? []; // Array of {role: 'user'|'model', parts: [{text: '...'}]}

if (empty($userMessage)) {
    echo json_encode(['error' => 'Message vide']);
    exit;
}

$pdo = getPDO();

// 1. Fetch Store Context
$caTotal = $pdo->query("SELECT COALESCE(SUM(total), 0) FROM commandes WHERE statut = 'livree'")->fetchColumn();
$cmdAttente = $pdo->query("SELECT COUNT(*) FROM commandes WHERE statut = 'en_attente'")->fetchColumn();
$totalClients = $pdo->query("SELECT COUNT(*) FROM clients")->fetchColumn();

// Stock Alerts
$params = getAllParams();
$seuilGlobal = (int)($params['seuil_alerte_global'] ?? 5);
$stmt = $pdo->prepare("SELECT COUNT(*) FROM produits WHERE a_variants = 0 AND stock <= GREATEST(COALESCE(stock_alerte, 0), :seuil) AND actif = 1 AND supprime = 0");
$stmt->execute(['seuil' => $seuilGlobal]);
$alerteProduit = $stmt->fetchColumn();
$stmt = $pdo->prepare("SELECT COUNT(*) FROM produit_variantes pv JOIN produits p ON p.id = pv.produit_id WHERE pv.stock <= GREATEST(COALESCE(pv.stock_alerte, 0), :seuil) AND pv.actif = 1 AND p.actif = 1 AND p.supprime = 0");
$stmt->execute(['seuil' => $seuilGlobal]);
$alerteVariante = $stmt->fetchColumn();
$rupturesAlertes = $alerteProduit + $alerteVariante;

// Detailed stats
$statsCategories = getStatsParCategorie($pdo);
$topProduits = getStatsTopProduits($pdo);
$statsSemaine = getStatsVentesSemaine($pdo);

// 2. Build System Prompt / Context
$systemInstruction = "Tu es l'Assistant IA Intelligent de la plateforme E-commerce 'ShopMax'. 
Ton rôle est polyvalent :
1. ANALYSTE EXPERT : Tu aides l'administrateur à comprendre ses données de vente, faire des prévisions et élaborer des stratégies commerciales.
2. ASSISTANT PERSONNEL : Tu réponds à toutes les questions, qu'elles soient liées au projet ou d'ordre général (culture générale, conseils, rédaction, etc.).
3. TONALITÉ : Professionnelle, amicale, concise et intelligente.

DONNÉES TEMPS RÉEL DE LA BOUTIQUE (pour tes analyses) :
- CA Total (Livré) : {$caTotal} FCFA
- Commandes en attente : {$cmdAttente}
- Alertes Stock : {$rupturesAlertes}
- Clients : {$totalClients}
- Top Catégories : " . json_encode($statsCategories) . "
- Top Produits : " . json_encode($topProduits) . "
- Ventes récentes : " . json_encode($statsSemaine) . "

Si on te pose une question sur la boutique, utilise ces chiffres. Sinon, réponds librement selon tes vastes connaissances.";

// 3. Prepare Gemini Request with History
$contents = [];
foreach ($history as $msg) {
    $contents[] = [
        "role" => $msg['role'] === 'user' ? 'user' : 'model',
        "parts" => [["text" => $msg['text']]]
    ];
}

// Add current user message
$contents[] = [
    "role" => "user",
    "parts" => [["text" => $userMessage]]
];

$data = [
    "system_instruction" => [
        "parts" => [["text" => $systemInstruction]]
    ],
    "contents" => $contents,
    "generationConfig" => [
        "temperature" => 0.8,
        "topK" => 40,
        "topP" => 0.95,
        "maxOutputTokens" => 2048,
    ]
];

// 4. Call Gemini API via CURL
$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // useful for local environments like XAMPP
curl_setopt($ch, CURLOPT_TIMEOUT, 30); // 30 seconds timeout
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10); // 10 seconds to connect

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

header('Content-Type: application/json');
if ($httpCode === 200) {
    $result = json_decode($response, true);
    $aiText = $result['candidates'][0]['content']['parts'][0]['text'] ?? "Désolé, je n'ai pas pu générer de réponse.";
    echo json_encode(['reply' => $aiText]);
} else {
    $errorMsg = 'Désolé, une erreur est survenue';
    
    if ($httpCode === 429) {
        $errorMsg = 'Limite de messages atteinte pour aujourd\'hui ou trop de requêtes rapides. Veuillez patienter quelques minutes avant de réessayer.';
    } elseif ($httpCode === 403) {
        $errorMsg = 'Accès refusé. Votre clé d\'API semble invalide ou bloquée.';
    } elseif ($httpCode === 400) {
        $errorMsg = 'Requête invalide. Le modèle est peut-être indisponible ou surchargé.';
    }
    
    if ($curlError) {
        $errorMsg .= " (Erreur réseau : $curlError)";
    }
    
    echo json_encode([
        'error' => $errorMsg, 
        'http_code' => $httpCode,
        // Toujours garder les détails en console pour le debug si besoin
        'details' => json_decode($response, true) ?? $response
    ]);
}
