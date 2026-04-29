<?php
/**
 * ShopMax — Admin Catégories
 */
session_start();
$pageTitle = 'Catégories';
require_once __DIR__ . '/includes/admin_header.php';

$pdo = getPDO();
$action = $_GET['action'] ?? 'list';
$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
if ($editId) $action = 'edit';
if (isset($_GET['add'])) $action = 'add';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_category'])) {
    $id = $_POST['id'] ?? 0;
    $nom = trim($_POST['nom']);
    $icone = trim($_POST['icone']);
    $description = trim($_POST['description']);
    $ordre = (int)$_POST['ordre_affichage'];
    $actif = isset($_POST['actif']) ? 1 : 0;
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $nom)));
    
    try {
        if ($id) {
            $stmt = $pdo->prepare("UPDATE categories SET nom=?, slug=?, icone=?, description=?, ordre_affichage=?, actif=?, updated_at=NOW() WHERE id=?");
            $stmt->execute([$nom, $slug, $icone, $description, $ordre, $actif, $id]);
            $message = "Catégorie mise à jour.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO categories (nom, slug, icone, description, ordre_affichage, actif) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$nom, $slug, $icone, $description, $ordre, $actif]);
            $message = "Catégorie ajoutée.";
        }
        $action = 'list';
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) $error = "Le nom/slug existe déjà.";
        else $error = "Erreur : " . $e->getMessage();
    }
}

// Suppression d'une catégorie
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_category'])) {
    $deleteId = (int)$_POST['delete_id'];
    try {
        $pdo->beginTransaction();

        // Compter les produits liés
        $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM produits WHERE categorie_id = ? AND supprime = 0");
        $stmtCount->execute([$deleteId]);
        $nbProduits = (int)$stmtCount->fetchColumn();

        // Détacher explicitement les produits pour éviter l'erreur de contrainte de clé étrangère
        $stmtUpdate = $pdo->prepare("UPDATE produits SET categorie_id = NULL WHERE categorie_id = ?");
        $stmtUpdate->execute([$deleteId]);

        $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->execute([$deleteId]);
        
        $pdo->commit();

        if ($nbProduits > 0) {
            $message = "Catégorie supprimée. $nbProduits produit(s) ont été dissocié(s).";
        } else {
            $message = "Catégorie supprimée avec succès.";
        }
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = "Erreur lors de la suppression : " . $e->getMessage();
    }
    $action = 'list';
}

?>

<div class="page-header">
    <div class="page-title">
        <h1>Catégories</h1>
        <p>Gérez la classification de vos produits.</p>
    </div>
    <div>
        <?php if ($action === 'list'): ?>
        <a href="?add=1" class="btn btn-primary"><i class="fas fa-plus"></i> Ajouter une Catégorie</a>
        <?php else: ?>
        <a href="categories.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Retour à la liste</a>
        <?php endif; ?>
    </div>
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
    $date_debut = trim($_GET['date_debut'] ?? '');
    $date_fin = trim($_GET['date_fin'] ?? '');
    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = (int)($_GET['limit'] ?? 10);
    if (!in_array($limit, [5, 10, 15, 20, 25, 30, 50, 100])) $limit = 10;
    $offset = ($page - 1) * $limit;

    $where = "WHERE 1=1";
    $params = [];
    if ($search !== '') {
        $where .= " AND nom LIKE ?";
        $params[] = "%$search%";
    }
    if ($date_debut !== '') {
        $where .= " AND DATE(created_at) >= ?";
        $params[] = $date_debut;
    }
    if ($date_fin !== '') {
        $where .= " AND DATE(created_at) <= ?";
        $params[] = $date_fin;
    }

    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM categories $where");
    $stmtCount->execute($params);
    $total = $stmtCount->fetchColumn();
    $totalPages = max(1, ceil($total / $limit));

    $stmt = $pdo->prepare("SELECT * FROM categories $where ORDER BY ordre_affichage ASC LIMIT $limit OFFSET $offset");
    $stmt->execute($params);
    $cats = $stmt->fetchAll();
    ?>
    <div class="card">
        <div class="card-header"><div class="card-title">Toutes les catégories (<?= $total ?>)</div></div>
        <div class="card-body" style="display:flex; flex-direction:column; padding:0;">
            <div style="padding:20px;">
                <form method="GET" class="filter-bar">
                    <input type="text" name="search" class="form-control search-input" placeholder="Rechercher une catégorie..." value="<?= e($search) ?>">
                    
                    <input type="date" name="date_debut" class="form-control" value="<?= e($date_debut) ?>" title="Date début">
                    <input type="date" name="date_fin" class="form-control" value="<?= e($date_fin) ?>" title="Date fin">

                    <select name="limit" class="form-control" style="min-width: 100px;">
                    <?php foreach([5,10,15,20,25,30,50,100] as $l): ?>
                    <option value="<?= $l ?>" <?= $limit === $l ? 'selected' : '' ?>><?= $l ?> par page</option>
                    <?php endforeach; ?>
                </select>

                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filtrer</button>
                <a href="categories.php" class="btn btn-secondary" title="Réinitialiser"><i class="fas fa-sync-alt"></i></a>
            </form>

            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>Icône</th><th>Nom</th><th>Ordre</th><th>Statut</th><th>Produits</th><th>Action</th></tr></thead>
                    <tbody>
                        <?php if(empty($cats)): ?>
                        <tr><td colspan="6" style="text-align: center;">Aucune catégorie trouvée.</td></tr>
                        <?php else: ?>
                        <?php foreach($cats as $c):
                            $stmtNb = $pdo->prepare("SELECT COUNT(*) FROM produits WHERE categorie_id = ? AND supprime = 0");
                            $stmtNb->execute([$c['id']]);
                            $nbProd = (int)$stmtNb->fetchColumn();
                        ?>
                        <tr>
                            <td><?= $c['icone'] ?></td>
                            <td><strong><?= e($c['nom']) ?></strong></td>
                            <td><?= $c['ordre_affichage'] ?></td>
                            <td><?= $c['actif'] ? '<span class="badge livree">Actif</span>' : '<span class="badge annulee">Inactif</span>' ?></td>
                            <td><span class="badge <?= $nbProd > 0 ? 'confirmee' : 'en_attente' ?>"><?= $nbProd ?></span></td>
                            <td>
                                <div class="actions">
                                    <a href="?edit=<?= $c['id'] ?>" class="btn-icon" title="Modifier"><i class="fas fa-edit"></i></a>
                                    <button type="button" class="btn-icon btn-icon-danger" title="Supprimer"
                                        onclick="openDeleteModal(<?= $c['id'] ?>, '<?= e(addslashes($c['nom'])) ?>', <?= $nbProd ?>)">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </div>
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
                    $qsParams = "search=".urlencode($search)."&date_debut=".urlencode($date_debut)."&date_fin=".urlencode($date_fin)."&limit=".$limit;
                    ?>
                    <?php if ($page > 1): ?>
                    <li><a href="?<?= $qsParams ?>&page=<?= $page-1 ?>">&laquo; Préc.</a></li>
                    <?php endif; ?>
                    
                    <?php
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

<?php elseif ($action === 'add' || $action === 'edit'): ?>
    <?php
    $c = [];
    if ($action === 'edit') {
        $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
        $stmt->execute([$editId]);
        $c = $stmt->fetch() ?: [];
    }
    ?>
    <div class="card" style="max-width: 600px;">
        <div class="card-header"><div class="card-title"><?= $action === 'edit' ? 'Modifier' : 'Ajouter' ?></div></div>
        <div class="card-body">
            <form method="POST">
                <?php if ($action === 'edit'): ?><input type="hidden" name="id" value="<?= $c['id'] ?>"><?php endif; ?>
                <div class="form-group">
                    <label class="form-label">Nom <span style="color:var(--danger)">*</span></label>
                    <input type="text" name="nom" class="form-control" value="<?= e($_POST['nom'] ?? $c['nom'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Icône (HTML ex: <code>&lt;i class="fas fa-laptop"&gt;&lt;/i&gt;</code>) <span style="color:var(--danger)">*</span></label>
                    <input type="text" name="icone" class="form-control" value="<?= e($_POST['icone'] ?? $c['icone'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="3"><?= e($_POST['description'] ?? $c['description'] ?? '') ?></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Ordre d'affichage (Tri)</label>
                    <input type="number" name="ordre_affichage" class="form-control" value="<?= e($_POST['ordre_affichage'] ?? $c['ordre_affichage'] ?? '1') ?>">
                </div>
                <div class="form-group">
                    <label style="display:flex; align-items:center; gap:10px;">
                        <input type="checkbox" name="actif" value="1" <?= isset($_POST['save_category']) ? (isset($_POST['actif']) ? 'checked' : '') : (($c['actif'] ?? 1) ? 'checked' : '') ?>>
                        <span style="font-weight:600;">Catégorie Active</span>
                    </label>
                </div>
                <button type="submit" name="save_category" class="btn btn-primary btn-block"><i class="fas fa-save"></i> Enregistrer</button>
            </form>
        </div>
    </div>
<?php endif; ?>

<!-- Modal de confirmation de suppression -->
<div id="deleteModal" style="display:none; position:fixed; inset:0; z-index:9999; background:rgba(0,0,0,0.5); align-items:center; justify-content:center;">
    <div style="background:var(--bg-card); border-radius:12px; padding:30px; max-width:440px; width:90%; box-shadow:0 20px 60px rgba(0,0,0,0.3); text-align:center;">
        <div style="width:56px; height:56px; border-radius:50%; background:rgba(239,68,68,0.1); display:flex; align-items:center; justify-content:center; margin:0 auto 16px;">
            <i class="fas fa-exclamation-triangle" style="font-size:1.5rem; color:var(--danger);"></i>
        </div>
        <h3 style="margin-bottom:8px; font-size:1.2rem;">Supprimer la catégorie</h3>
        <p id="deleteModalText" style="color:var(--text-muted); margin-bottom:6px;"></p>
        <p id="deleteModalWarning" style="color:var(--danger); font-weight:600; font-size:0.85rem; margin-bottom:20px;"></p>
        <form method="POST" id="deleteForm" style="display:flex; gap:12px; justify-content:center;">
            <input type="hidden" name="delete_id" id="deleteId">
            <button type="button" onclick="closeDeleteModal()" class="btn btn-secondary">Annuler</button>
            <button type="submit" name="delete_category" class="btn btn-danger"><i class="fas fa-trash-alt"></i> Supprimer</button>
        </form>
    </div>
</div>

<style>
.btn-icon-danger { color: var(--text-muted); background: var(--bg-main); padding: 6px; border-radius: 4px; border: none; cursor: pointer; transition: all 0.2s; font-size: inherit; }
.btn-icon-danger:hover { color: var(--danger); background: rgba(239, 68, 68, 0.1); }
</style>

<script>
function openDeleteModal(id, nom, nbProduits) {
    document.getElementById('deleteId').value = id;
    document.getElementById('deleteModalText').textContent = 'Voulez-vous vraiment supprimer la catégorie "' + nom + '" ?';
    if (nbProduits > 0) {
        document.getElementById('deleteModalWarning').textContent = '⚠ ' + nbProduits + ' produit(s) seront dissocié(s) de cette catégorie.';
    } else {
        document.getElementById('deleteModalWarning').textContent = '';
    }
    document.getElementById('deleteModal').style.display = 'flex';
}
function closeDeleteModal() {
    document.getElementById('deleteModal').style.display = 'none';
}
document.getElementById('deleteModal').addEventListener('click', function(e) {
    if (e.target === this) closeDeleteModal();
});
</script>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
