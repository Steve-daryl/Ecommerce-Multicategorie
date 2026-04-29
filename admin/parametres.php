<?php
/**
 * ShopMax — Admin Paramètres
 * Fixed: added missing fields (horaires, adresse_boutique, email_boutique)
 * so all params used by client interface can be configured from admin
 */
session_start();
$pageTitle = 'Paramètres';
require_once __DIR__ . '/includes/admin_header.php';

$pdo = getPDO();
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_params'])) {
    $params = [
        'nom_boutique', 'slogan', 'devise', 'whatsapp',
        'frais_livraison', 'seuil_livraison_gratuite', 
        'contact_email', 'contact_adresse',
        'horaires', 'seuil_alerte_global'
    ];
    
    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("INSERT INTO parametres (cle, valeur) VALUES (?, ?) ON DUPLICATE KEY UPDATE valeur = VALUES(valeur)");
        
        foreach ($params as $cle) {
            if (isset($_POST[$cle])) {
                $stmt->execute([$cle, trim($_POST[$cle])]);
            }
        }
        
        // Sync alias keys so client-side code works seamlessly
        // adresse_boutique = contact_adresse, email_boutique = contact_email
        if (isset($_POST['contact_adresse'])) {
            $stmt->execute(['adresse_boutique', trim($_POST['contact_adresse'])]);
        }
        if (isset($_POST['contact_email'])) {
            $stmt->execute(['email_boutique', trim($_POST['contact_email'])]);
        }
        
        $pdo->commit();
        $message = "Paramètres mis à jour avec succès.";
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Erreur : " . $e->getMessage();
    }
}

// Récupérer les paramètres actuels
$currentParams = getAllParams();
?>

<div class="page-header">
    <div class="page-title">
        <h1>Paramètres du site</h1>
        <p>Configurez les informations générales de la boutique et la livraison. Ces paramètres s'appliquent sur l'interface client.</p>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= e($message) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> <?= e($error) ?></div>
<?php endif; ?>

<div class="card" style="max-width: 800px;">
    <div class="card-header"><div class="card-title"><i class="fas fa-cog"></i> Configuration Générale</div></div>
    <div class="card-body">
        <form method="POST">
            <h4 style="margin-bottom: 15px; color: var(--primary); border-bottom: 1px solid var(--border); padding-bottom: 5px;">Identité de la Boutique</h4>
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="form-group">
                    <label class="form-label">Nom de la boutique</label>
                    <input type="text" name="nom_boutique" class="form-control" value="<?= e($currentParams['nom_boutique'] ?? 'ShopMax') ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Slogan</label>
                    <input type="text" name="slogan" class="form-control" value="<?= e($currentParams['slogan'] ?? 'Votre boutique en ligne premium') ?>">
                </div>
            </div>

            <h4 style="margin-bottom: 15px; margin-top: 10px; color: var(--primary); border-bottom: 1px solid var(--border); padding-bottom: 5px;">Contact & Réception des Commandes</h4>
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="form-group">
                    <label class="form-label">Numéro WhatsApp (Réception des commandes)</label>
                    <input type="tel" name="whatsapp" class="form-control" value="<?= e($currentParams['whatsapp'] ?? '+237600000000') ?>" required>
                    <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 4px;">Ex: +237 6XX XXX XXX (Utilisez le format international)</p>
                </div>
                <div class="form-group">
                    <label class="form-label">Email de contact</label>
                    <input type="email" name="contact_email" class="form-control" value="<?= e($currentParams['contact_email'] ?? ($currentParams['email_boutique'] ?? 'contact@shopmax.com')) ?>">
                    <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 4px;">Cet email recevra les messages du formulaire de contact et s'affichera sur le site.</p>
                </div>
                <div class="form-group">
                    <label class="form-label">Adresse de la boutique</label>
                    <input type="text" name="contact_adresse" class="form-control" value="<?= e($currentParams['contact_adresse'] ?? ($currentParams['adresse_boutique'] ?? 'Douala, Cameroun')) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Horaires d'ouverture</label>
                    <input type="text" name="horaires" class="form-control" value="<?= e($currentParams['horaires'] ?? 'Lun-Sam : 8h - 20h') ?>">
                    <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 4px;">Affiché sur la page de contact.</p>
                </div>
            </div>

            <h4 style="margin-bottom: 15px; margin-top: 10px; color: var(--primary); border-bottom: 1px solid var(--border); padding-bottom: 5px;">Ventes & Livraison</h4>
            <div style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px;">
                <div class="form-group">
                    <label class="form-label">Devise</label>
                    <input type="text" name="devise" class="form-control" value="<?= e($currentParams['devise'] ?? 'FCFA') ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Frais de livraison standard</label>
                    <input type="number" name="frais_livraison" class="form-control" value="<?= e($currentParams['frais_livraison'] ?? '2000') ?>" required min="0">
                </div>
                <div class="form-group">
                    <label class="form-label">Seuil livraison gratuite</label>
                    <input type="number" name="seuil_livraison_gratuite" class="form-control" value="<?= e($currentParams['seuil_livraison_gratuite'] ?? '50000') ?>" required min="0">
                </div>
                <div class="form-group">
                    <label class="form-label">Seuil d'alerte de stock (Global)</label>
                    <input type="number" name="seuil_alerte_global" class="form-control" value="<?= e($currentParams['seuil_alerte_global'] ?? '5') ?>" required min="0">
                    <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 4px;">Utilisé par défaut pour identifier les produits en rupture.</p>
                </div>
            </div>

            <hr style="border:0; border-top:1px solid var(--border); margin:20px 0;">
            <button type="submit" name="save_params" class="btn btn-primary btn-lg">
                <i class="fas fa-save"></i> Enregistrer les paramètres
            </button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
