<?php
/**
 * ShopMax — Contact Form Handler
 * Saves message to DB AND sends a real email to the shop owner
 */
require_once __DIR__ . '/../includes/config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

$nom = trim($_POST['nom'] ?? '');
$email = trim($_POST['email'] ?? '');
$objet = trim($_POST['objet'] ?? '');
$message = trim($_POST['message'] ?? '');

$errors = [];
if (empty($nom)) $errors[] = 'Le nom est requis';
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email invalide';
if (empty($message)) $errors[] = 'Le message est requis';

if (!empty($errors)) {
    echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
    exit;
}

try {
    $pdo = getPDO();
    
    // 1. Save to database
    $stmt = $pdo->prepare("INSERT INTO messages_contact (nom, email, objet, message, ip_address) VALUES (:nom, :email, :objet, :msg, :ip)");
    $stmt->execute([
        'nom' => $nom,
        'email' => $email,
        'objet' => $objet,
        'msg' => $message,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? ''
    ]);
    
    // 2. Send real email to shop owner
    $params = getAllParams();
    $shopEmail = $params['contact_email'] ?? ($params['email_boutique'] ?? '');
    $shopName = $params['nom_boutique'] ?? 'ShopMax';
    
    if (!empty($shopEmail) && filter_var($shopEmail, FILTER_VALIDATE_EMAIL)) {
        $sujetEmail = !empty($objet) ? "[{$shopName}] Contact: {$objet}" : "[{$shopName}] Nouveau message de contact";
        
        // Build HTML email body
        $htmlBody = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; color: #1E293B; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #082F63; color: white; padding: 20px; border-radius: 8px 8px 0 0; text-align: center; }
                .header h2 { margin: 0; }
                .body { background: #f8fafc; padding: 20px; border: 1px solid #E2E8F0; }
                .field { margin-bottom: 15px; }
                .field-label { font-weight: bold; color: #082F63; font-size: 14px; margin-bottom: 4px; }
                .field-value { background: white; padding: 10px; border: 1px solid #E2E8F0; border-radius: 4px; }
                .footer { background: #f1f5f9; padding: 15px; text-align: center; font-size: 12px; color: #64748B; border-radius: 0 0 8px 8px; border: 1px solid #E2E8F0; border-top: 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>📩 Nouveau message de contact</h2>
                </div>
                <div class='body'>
                    <div class='field'>
                        <div class='field-label'>👤 Nom :</div>
                        <div class='field-value'>" . htmlspecialchars($nom) . "</div>
                    </div>
                    <div class='field'>
                        <div class='field-label'>📧 Email :</div>
                        <div class='field-value'>" . htmlspecialchars($email) . "</div>
                    </div>
                    <div class='field'>
                        <div class='field-label'>📋 Objet :</div>
                        <div class='field-value'>" . htmlspecialchars($objet ?: '(aucun objet)') . "</div>
                    </div>
                    <div class='field'>
                        <div class='field-label'>💬 Message :</div>
                        <div class='field-value'>" . nl2br(htmlspecialchars($message)) . "</div>
                    </div>
                    <div class='field'>
                        <div class='field-label'>🌐 IP :</div>
                        <div class='field-value'>" . htmlspecialchars($_SERVER['REMOTE_ADDR'] ?? 'N/A') . "</div>
                    </div>
                </div>
                <div class='footer'>
                    Ce message a été envoyé depuis le formulaire de contact de {$shopName}.<br>
                    Vous pouvez répondre directement à " . htmlspecialchars($email) . "
                </div>
            </div>
        </body>
        </html>";
        
        // Email headers
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "From: {$shopName} <noreply@" . ($_SERVER['SERVER_NAME'] ?? 'localhost') . ">\r\n";
        $headers .= "Reply-To: " . $email . "\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion();
        
        // Send the email (suppress errors, non-blocking)
        @mail($shopEmail, $sujetEmail, $htmlBody, $headers);
    }
    
    echo json_encode(['success' => true, 'message' => 'Message envoyé avec succès ! Nous vous répondrons rapidement.']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'envoi du message.']);
}
