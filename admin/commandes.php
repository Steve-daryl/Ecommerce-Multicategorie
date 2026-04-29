<?php
/**
 * ShopMax — Admin Commandes
 */
session_start();
$pageTitle = 'Commandes';
require_once __DIR__ . '/includes/admin_header.php';

$pdo = getPDO();
$action = $_GET['action'] ?? 'list';
$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
if ($editId) $action = 'edit';

// Handle Status Update
$message = '';
$error = '';

// Status transition rules: unidirectional flow
// en_attente -> confirmee -> en_livraison -> livree
// annulee can be set from any state except livree
function getAllowedTransitions($currentStatus) {
    return match($currentStatus) {
        'en_attente'    => ['en_attente', 'confirmee', 'annulee'],
        'confirmee'     => ['confirmee', 'en_livraison', 'annulee'],
        'en_livraison'  => ['en_livraison', 'livree', 'annulee'],
        'livree'        => ['livree'],       // No change allowed
        'annulee'       => ['annulee'],       // No change allowed
        default         => [$currentStatus]
    };
}

function getStatusLabel($statut) {
    return match($statut) {
        'en_attente'   => 'En attente',
        'confirmee'    => 'Confirmée',
        'en_livraison' => 'En livraison',
        'livree'       => 'Livrée',
        'annulee'      => 'Annulée',
        default        => $statut
    };
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $cmdId = (int)$_POST['commande_id'];
    $newStatus = $_POST['nouveau_statut'];
    
    try {
        $pdo->beginTransaction();
        
        $stmtCmd = $pdo->prepare("SELECT statut FROM commandes WHERE id = ?");
        $stmtCmd->execute([$cmdId]);
        $oldStatus = $stmtCmd->fetchColumn();
        
        if ($oldStatus && $oldStatus !== $newStatus) {
            // Validate the transition
            $allowed = getAllowedTransitions($oldStatus);
            if (!in_array($newStatus, $allowed)) {
                $error = "Transition de statut non autorisée : " . getStatusLabel($oldStatus) . " → " . getStatusLabel($newStatus);
            } else {
                $stmtUp = $pdo->prepare("UPDATE commandes SET statut = ?, date_maj = NOW() WHERE id = ?");
                $stmtUp->execute([$newStatus, $cmdId]);
                $message = "Le statut de la commande a été mis à jour avec succès.";
            }
        }
        $pdo->commit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = "Erreur de mise à jour : " . $e->getMessage();
    }
}

// Helpers
function getStatusBadgeList($statut) {
    return match($statut) {
        'en_attente' => '<span class="badge en_attente">En attente</span>',
        'confirmee' => '<span class="badge confirmee">Confirmée</span>',
        'en_livraison' => '<span class="badge en_livraison">En livraison</span>',
        'livree' => '<span class="badge livree">Livrée</span>',
        'annulee' => '<span class="badge annulee">Annulée</span>',
        default => '<span class="badge">' . e($statut) . '</span>'
    };
}
?>

<div class="page-header">
    <div class="page-title">
        <h1>Commandes</h1>
        <p>Gérez les commandes, mettez à jour les statuts et visualisez les reçus.</p>
    </div>
    <?php if ($action === 'edit'): ?>
    <div>
        <a href="commandes.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Retour à la liste</a>
    </div>
    <?php endif; ?>
</div>

<?php if ($message): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= e($message) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> <?= e($error) ?></div>
<?php endif; ?>

<?php if ($action === 'list'): ?>
    <?php
    $search = trim($_GET['search'] ?? '');
    $filter_statut = $_GET['statut'] ?? '';
    $date_debut = trim($_GET['date_debut'] ?? '');
    $date_fin = trim($_GET['date_fin'] ?? '');
    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = (int)($_GET['limit'] ?? 15);
    if (!in_array($limit, [5, 10, 15, 20, 25, 30, 50, 100])) $limit = 15;
    $offset = ($page - 1) * $limit;

    $where = "WHERE 1=1";
    $params = [];

    if ($search !== '') {
        $where .= " AND (numero_cmd LIKE ? OR nom_client LIKE ? OR whatsapp LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    if ($filter_statut !== '') {
        $where .= " AND statut = ?";
        $params[] = $filter_statut;
    }

    if ($date_debut !== '') {
        $where .= " AND DATE(date_commande) >= ?";
        $params[] = $date_debut;
    }

    if ($date_fin !== '') {
        $where .= " AND DATE(date_commande) <= ?";
        $params[] = $date_fin;
    }

    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM commandes $where");
    $stmtCount->execute($params);
    $total = $stmtCount->fetchColumn();
    $totalPages = max(1, ceil($total / $limit));

    $stmt = $pdo->prepare("SELECT * FROM commandes $where ORDER BY date_commande DESC LIMIT $limit OFFSET $offset");
    $stmt->execute($params);
    $commandes = $stmt->fetchAll();
    ?>
    <div class="card">
        <div class="card-header">
            <div class="card-title">Commandes (<?= $total ?>)</div>
        </div>
        <div class="card-body" style="display:flex; flex-direction:column; padding:0;">
            <div style="padding:20px;">
                <form method="GET" class="filter-bar">
                    <input type="text" name="search" class="form-control search-input" placeholder="Recherche Num, Client, WhatsApp..." value="<?= e($search) ?>">
                    
                    <input type="date" name="date_debut" class="form-control" value="<?= e($date_debut) ?>" title="Date début">
                    <input type="date" name="date_fin" class="form-control" value="<?= e($date_fin) ?>" title="Date fin">

                    <select name="statut" class="form-control">
                    <option value="">Tous les statuts</option>
                    <option value="en_attente" <?= $filter_statut === 'en_attente' ? 'selected' : '' ?>>En attente</option>
                    <option value="confirmee" <?= $filter_statut === 'confirmee' ? 'selected' : '' ?>>Confirmée</option>
                    <option value="en_livraison" <?= $filter_statut === 'en_livraison' ? 'selected' : '' ?>>En livraison</option>
                    <option value="livree" <?= $filter_statut === 'livree' ? 'selected' : '' ?>>Livrée</option>
                    <option value="annulee" <?= $filter_statut === 'annulee' ? 'selected' : '' ?>>Annulée</option>
                </select>

                <select name="limit" class="form-control" style="min-width: 100px;">
                    <?php foreach([5,10,15,20,25,30,50,100] as $l): ?>
                    <option value="<?= $l ?>" <?= $limit === $l ? 'selected' : '' ?>><?= $l ?> par page</option>
                    <?php endforeach; ?>
                </select>

                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filtrer</button>
                <a href="commandes.php" class="btn btn-secondary" title="Réinitialiser"><i class="fas fa-sync-alt"></i></a>
            </form>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Numéro</th>
                            <th>Client / WhatsApp</th>
                            <th>Date</th>
                            <th>Articles</th>
                            <th>Total</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($commandes)): ?>
                        <tr><td colspan="7" style="text-align: center;">Aucune commande trouvée.</td></tr>
                        <?php else: ?>
                        <?php foreach($commandes as $cmd): 
                            // Get items count
                            $stmtC = $pdo->prepare("SELECT SUM(quantite) FROM commande_items WHERE commande_id = ?");
                            $stmtC->execute([$cmd['id']]);
                            $nbItems = $stmtC->fetchColumn() ?: 0;
                        ?>
                        <tr>
                            <td><strong><?= e($cmd['numero_cmd']) ?></strong></td>
                            <td>
                                <div><?= e($cmd['nom_client']) ?></div>
                                <div style="font-size: 0.8rem; color: var(--text-muted);"><i class="fab fa-whatsapp"></i> <?= e($cmd['whatsapp']) ?></div>
                            </td>
                            <td><?= date('d/m/Y H:i', strtotime($cmd['date_commande'])) ?></td>
                            <td><?= $nbItems ?> article(s)</td>
                            <td><strong><?= formatPrix($cmd['total']) ?></strong></td>
                            <td><?= getStatusBadgeList($cmd['statut']) ?></td>
                            <td>
                                <a href="commandes.php?edit=<?= $cmd['id'] ?>" class="btn-icon" title="Voir/Modifier"><i class="fas fa-eye"></i></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($totalPages > 1): ?>
            <div class="pagination-container">
                <ul class="pagination">
                    <?php 
                    $qsParams = "search=".urlencode($search)."&statut=".urlencode($filter_statut)."&date_debut=".urlencode($date_debut)."&date_fin=".urlencode($date_fin)."&limit=".$limit;
                    ?>
                    <?php if ($page > 1): ?>
                    <li><a href="?<?= $qsParams ?>&page=<?= $page-1 ?>">&laquo; Préc.</a></li>
                    <?php endif; ?>
                    
                    <?php
                    // Pagination logic to avoid too many pages showing
                    $start = max(1, $page - 2);
                    $end = min($totalPages, $page + 2);
                    
                    if ($start > 1) {
                        echo '<li><a href="?'.$qsParams.'&page=1">1</a></li>';
                        if ($start > 2) echo '<li class="disabled"><span>...</span></li>';
                    }
                    
                    for($i=$start; $i<=$end; $i++): ?>
                    <li class="<?= $i === $page ? 'active' : '' ?>">
                        <?php if ($i === $page): ?>
                        <span><?= $i ?></span>
                        <?php else: ?>
                        <a href="?<?= $qsParams ?>&page=<?= $i ?>"><?= $i ?></a>
                        <?php endif; ?>
                    </li>
                    <?php endfor; 
                    
                    if ($end < $totalPages) {
                        if ($end < $totalPages - 1) echo '<li class="disabled"><span>...</span></li>';
                        echo '<li><a href="?'.$qsParams.'&page='.$totalPages.'">'.$totalPages.'</a></li>';
                    }
                    ?>

                    <?php if ($page < $totalPages): ?>
                    <li><a href="?<?= $qsParams ?>&page=<?= $page+1 ?>">Suiv. &raquo;</a></li>
                    <?php endif; ?>
                </ul>
            </div>
            <?php endif; ?>
        </div>
    </div>

<?php elseif ($action === 'edit'): ?>
    <?php
    $stmt = $pdo->prepare("SELECT * FROM commandes WHERE id = ?");
    $stmt->execute([$editId]);
    $cmd = $stmt->fetch();
    
    if (!$cmd):
        echo "<div class='alert alert-danger'>Commande introuvable.</div>";
    else:
        $stmtItems = $pdo->prepare("SELECT * FROM commande_items WHERE commande_id = ?");
        $stmtItems->execute([$editId]);
        $items = $stmtItems->fetchAll();
        
        $currentStatus = $cmd['statut'];
        $allowedStatuses = getAllowedTransitions($currentStatus);
        $isFinal = in_array($currentStatus, ['livree', 'annulee']);
    ?>
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">
        <div class="card">
            <div class="card-header">
                <div class="card-title">Détails de la Commande <?= e($cmd['numero_cmd']) ?></div>
                <?= getStatusBadgeList($cmd['statut']) ?>
            </div>
            <div class="card-body">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                    <div>
                        <h4 style="margin-bottom: 10px; color: var(--primary);">Client</h4>
                        <p><strong>Nom:</strong> <?= e($cmd['nom_client']) ?></p>
                        <p><strong>WhatsApp:</strong> <?= e($cmd['whatsapp']) ?></p>
                        <p><strong>Ville:</strong> <?= e($cmd['ville']) ?></p>
                        <p><strong>Adresse:</strong> <br><?= nl2br(e($cmd['adresse_livraison'])) ?></p>
                    </div>
                    <div>
                        <h4 style="margin-bottom: 10px; color: var(--primary);">Commande</h4>
                        <p><strong>Date:</strong> <?= date('d/m/Y H:i', strtotime($cmd['date_commande'])) ?></p>
                        <p><strong>Sous-total:</strong> <?= formatPrix($cmd['sous_total']) ?></p>
                        <p><strong>Livraison:</strong> <?= $cmd['frais_livraison'] > 0 ? formatPrix($cmd['frais_livraison']) : 'Gratuite' ?></p>
                        <p><strong>Total TTC:</strong> <strong style="font-size: 1.1em; color: var(--success);"><?= formatPrix($cmd['total']) ?></strong></p>
                    </div>
                </div>
                
                <?php if ($cmd['note_client']): ?>
                <div style="padding: 15px; background: var(--bg-main); border-radius: 6px; border-left: 4px solid var(--accent); margin-bottom: 20px;">
                    <strong>Note du client:</strong><br>
                    <?= nl2br(e($cmd['note_client'])) ?>
                </div>
                <?php endif; ?>

                <h4 style="margin-bottom: 10px; padding-top: 10px; border-top: 1px solid var(--border);">Produits Commandés</h4>
                <table class="table" style="border: 1px solid var(--border); border-radius: 6px; overflow: hidden;">
                    <thead>
                        <tr style="background: var(--bg-main);">
                            <th>Produit</th>
                            <th>Prix Unit.</th>
                            <th>Qte</th>
                            <th>Sous-Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($items as $it): ?>
                        <tr>
                            <td><?= e($it['nom_produit']) ?></td>
                            <td><?= formatPrix($it['prix_unitaire']) ?></td>
                            <td>x<?= $it['quantite'] ?></td>
                            <td><?= formatPrix($it['sous_total']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div>
            <!-- Update Status Card -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-edit"></i> Modifier le Statut</div>
                </div>
                <div class="card-body">
                    <?php if ($isFinal): ?>
                    <div style="text-align: center; padding: 20px; color: var(--text-muted);">
                        <i class="fas fa-lock" style="font-size: 2rem; margin-bottom: 10px; display: block; color: <?= $currentStatus === 'livree' ? 'var(--success)' : 'var(--danger)' ?>;"></i>
                        <p style="font-weight: 600;">Cette commande est <strong><?= getStatusLabel($currentStatus) ?></strong>.</p>
                        <p style="font-size: 0.85rem;">Aucune modification de statut n'est possible.</p>
                    </div>
                    <?php else: ?>
                    <form method="POST">
                        <input type="hidden" name="commande_id" value="<?= $cmd['id'] ?>">
                        <div class="form-group">
                            <label class="form-label">Statut de la commande</label>
                            <select name="nouveau_statut" class="form-control">
                                <?php foreach($allowedStatuses as $st): ?>
                                <option value="<?= $st ?>" <?= $currentStatus === $st ? 'selected' : '' ?>><?= getStatusLabel($st) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 8px;">
                                <i class="fas fa-info-circle"></i> 
                                Flux : En attente → Confirmée → En livraison → Livrée.<br>
                                Vous pouvez annuler à tout moment (sauf si déjà livrée).
                            </p>
                        </div>
                        <button type="submit" name="update_status" class="btn btn-primary btn-block" style="width:100%;">
                            Enregistrer le statut
                        </button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- WhatsApp Text / Facture -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fab fa-whatsapp"></i> Facture Originale & PDF</div>
                </div>
                <div class="card-body">
                    <div style="margin-bottom: 15px; display:flex; gap:10px;">
                        <a href="facture.php?id=<?= $cmd['id'] ?>&format=a4" target="_blank" class="btn btn-secondary btn-sm"><i class="fas fa-file-invoice"></i> Configurer et Imprimer / Exporter la Facture</a>
                    </div>
                    <textarea class="form-control" rows="15" readonly style="font-family: monospace; font-size: 0.8rem; background: var(--bg-main);"><?= e($cmd['facture_texte']) ?></textarea>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
