<?php
/**
 * ShopMax — Admin Utilisateurs
 */
session_start();
$pageTitle = 'Utilisateurs & Droits';
require_once __DIR__ . '/includes/admin_header.php';

$pdo = getPDO();
$action = $_GET['action'] ?? 'list';
$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
if ($editId) $action = 'edit';
if (isset($_GET['add'])) $action = 'add';

$message = '';
$error = '';

$available_modules = [
    'dashboard' => 'Tableau de bord',
    'commandes' => 'Commandes & Factures',
    'produits' => 'Produits',
    'categories' => 'Catégories',
    'marques' => 'Marques',
    'parametres' => 'Paramètres',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_user'])) {
    $id = $_POST['id'] ?? 0;
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];
    $actif = isset($_POST['actif']) ? 1 : 0;
    
    // Parse permissions (only for 'admin' role, super_admin gets everything implicitly)
    $perms = [];
    if ($role === 'admin' && isset($_POST['permissions'])) {
        $perms = $_POST['permissions'];
    }
    $permissions_json = json_encode($perms);

    try {
        if ($id) {
            // Update
            $query = "UPDATE admins SET username=?, email=?, role=?, permissions=?, actif=? ";
            $params = [$username, $email, $role, $permissions_json, $actif];
            
            if (!empty($_POST['password'])) {
                $query .= ", password_hash=? ";
                $params[] = password_hash($_POST['password'], PASSWORD_DEFAULT);
            }
            
            $query .= " WHERE id=?";
            $params[] = $id;
            
            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            $message = "Utilisateur mis à jour.";
        } else {
            // Insert
            if (empty($_POST['password'])) {
                throw new Exception("Le mot de passe est obligatoire pour un nouvel utilisateur.");
            }
            $hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("INSERT INTO admins (username, email, password_hash, role, permissions, actif) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$username, $email, $hash, $role, $permissions_json, $actif]);
            $message = "Utilisateur ajouté.";
        }
        $action = 'list';
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) $error = "Le nom d'utilisateur ou l'email existe déjà.";
        else $error = "Erreur SQL : " . $e->getMessage();
    } catch (Exception $e) {
        $error = "Erreur : " . $e->getMessage();
    }
}
?>

<div class="page-header">
    <div class="page-title">
        <h1>Utilisateurs</h1>
        <p>Gérez les accès et les permissions (RBAC) des administrateurs du panel.</p>
    </div>
    <div>
        <?php if ($action === 'list'): ?>
        <a href="?add=1" class="btn btn-primary"><i class="fas fa-user-plus"></i> Ajouter un Utilisateur</a>
        <?php else: ?>
        <a href="utilisateurs.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Retour à la liste</a>
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
    $filter_role = $_GET['role'] ?? '';
    $date_debut = trim($_GET['date_debut'] ?? '');
    $date_fin = trim($_GET['date_fin'] ?? '');
    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = (int)($_GET['limit'] ?? 10);
    if (!in_array($limit, [5, 10, 15, 20, 25, 30, 50, 100])) $limit = 10;
    $offset = ($page - 1) * $limit;

    $where = "WHERE 1=1";
    $params = [];
    if ($search !== '') {
        $where .= " AND (username LIKE ? OR email LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    if ($filter_role !== '') {
        $where .= " AND role = ?";
        $params[] = $filter_role;
    }
    if ($date_debut !== '') {
        $where .= " AND DATE(created_at) >= ?";
        $params[] = $date_debut;
    }
    if ($date_fin !== '') {
        $where .= " AND DATE(created_at) <= ?";
        $params[] = $date_fin;
    }

    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM admins $where");
    $stmtCount->execute($params);
    $total = $stmtCount->fetchColumn();
    $totalPages = max(1, ceil($total / $limit));

    $stmt = $pdo->prepare("SELECT id, username, email, role, actif, last_login FROM admins $where ORDER BY id ASC LIMIT $limit OFFSET $offset");
    $stmt->execute($params);
    $users = $stmt->fetchAll();
    ?>
    <div class="card">
        <div class="card-header"><div class="card-title">Tous les Administrateurs (<?= $total ?>)</div></div>
        <div class="card-body" style="display:flex; flex-direction:column; padding:0;">
            <div style="padding:20px;">
                <form method="GET" class="filter-bar">
                    <input type="text" name="search" class="form-control search-input" placeholder="Rechercher nom, email..." value="<?= e($search) ?>">
                    
                    <input type="date" name="date_debut" class="form-control" value="<?= e($date_debut) ?>" title="Date début">
                    <input type="date" name="date_fin" class="form-control" value="<?= e($date_fin) ?>" title="Date fin">

                    <select name="role" class="form-control">
                    <option value="">Tous les rôles</option>
                    <option value="super_admin" <?= $filter_role === 'super_admin' ? 'selected' : '' ?>>Super Admin</option>
                    <option value="admin" <?= $filter_role === 'admin' ? 'selected' : '' ?>>Admin</option>
                </select>

                <select name="limit" class="form-control" style="min-width: 100px;">
                    <?php foreach([5,10,15,20,25,30,50,100] as $l): ?>
                    <option value="<?= $l ?>" <?= $limit === $l ? 'selected' : '' ?>><?= $l ?> par page</option>
                    <?php endforeach; ?>
                </select>

                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filtrer</button>
                <a href="utilisateurs.php" class="btn btn-secondary" title="Réinitialiser"><i class="fas fa-sync-alt"></i></a>
            </form>

            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>Utilisateur</th><th>Email</th><th>Rôle</th><th>Dernière Connexion</th><th>Statut</th><th>Action</th></tr></thead>
                    <tbody>
                        <?php if(empty($users)): ?>
                        <tr><td colspan="6" style="text-align: center;">Aucun utilisateur trouvé.</td></tr>
                        <?php else: ?>
                        <?php foreach($users as $u): ?>
                        <tr>
                            <td><strong><?= e($u['username']) ?></strong></td>
                            <td><?= e($u['email']) ?></td>
                            <td>
                                <?= $u['role'] === 'super_admin' ? '<span class="badge" style="background:#082F63; color:white;">Super Admin</span>' : '<span class="badge" style="background:#F1F5F9; color:#64748B;">Admin</span>' ?>
                            </td>
                            <td><?= $u['last_login'] ? date('d/m/Y H:i', strtotime($u['last_login'])) : 'Jamais' ?></td>
                            <td><?= $u['actif'] ? '<span class="badge livree">Actif</span>' : '<span class="badge annulee">Inactif</span>' ?></td>
                            <td>
                                <a href="?edit=<?= $u['id'] ?>" class="btn-icon"><i class="fas fa-edit"></i></a>
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
                    $qsParams = "search=".urlencode($search)."&role=".urlencode($filter_role)."&date_debut=".urlencode($date_debut)."&date_fin=".urlencode($date_fin)."&limit=".$limit;
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
    $u = [];
    $userPerms = [];
    if ($action === 'edit') {
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE id = ?");
        $stmt->execute([$editId]);
        $u = $stmt->fetch() ?: [];
        $userPerms = $u['permissions'] ? json_decode($u['permissions'], true) : [];
    }
    
    // Prevent locking out the main admin accidentally
    $isSelf = ($u['id'] ?? 0) == $_SESSION['admin_id'];
    ?>
    <div class="card" style="max-width: 800px;">
        <div class="card-header"><div class="card-title"><?= $action === 'edit' ? 'Modifier Utilisateur' : 'Créer un Utilisateur' ?></div></div>
        <div class="card-body">
            <form method="POST">
                <?php if ($action === 'edit'): ?>
                <input type="hidden" name="id" value="<?= $u['id'] ?>">
                <?php endif; ?>
                
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px;">
                    <div>
                        <div class="form-group">
                            <label class="form-label">Nom d'utilisateur <span style="color:var(--danger)">*</span></label>
                            <input type="text" name="username" class="form-control" value="<?= e($_POST['username'] ?? $u['username'] ?? '') ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Mot de passe</label>
                            <input type="password" name="password" class="form-control" placeholder="<?= $action === 'edit' ? 'Laisser vide pour ne pas modifier' : 'Obligatoire' ?>" <?= $action === 'add' ? 'required' : '' ?>>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" value="<?= e($_POST['email'] ?? $u['email'] ?? '') ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Rôle <span style="color:var(--danger)">*</span></label>
                            <select name="role" class="form-control" id="roleSelector" onchange="togglePermissions()" <?= $isSelf ? 'disabled' : '' ?>>
                                <option value="super_admin" <?= ($u['role'] ?? '') === 'super_admin' ? 'selected' : '' ?>>Super Administrateur (Accès Total)</option>
                                <option value="admin" <?= ($u['role'] ?? 'admin') === 'admin' ? 'selected' : '' ?>>Administrateur Restreint</option>
                            </select>
                            <?php if ($isSelf): ?>
                            <input type="hidden" name="role" value="<?= $u['role'] ?>">
                            <small style="color:var(--warning)">Vous ne pouvez pas modifier votre propre rôle.</small>
                            <?php endif; ?>
                        </div>

                        <div class="form-group" style="margin-top:20px;">
                            <label style="display:flex; align-items:center; gap:10px;">
                                <input type="checkbox" name="actif" value="1" <?= isset($_POST['save_user']) ? (isset($_POST['actif']) ? 'checked' : '') : (($u['actif'] ?? 1) ? 'checked' : '') ?> <?= $isSelf ? 'disabled' : '' ?>>
                                <span style="font-weight:600;">Actif (Autorisé à se connecter)</span>
                            </label>
                            <?php if ($isSelf): ?><input type="hidden" name="actif" value="1"><?php endif; ?>
                        </div>
                    </div>
                    
                    <div id="permissionsBox" style="background:var(--bg-main); padding: 20px; border-radius:8px; border:1px solid var(--border);">
                        <h4 style="margin-top:0; color:var(--primary); margin-bottom:15px;">Permissions (RBAC)</h4>
                        <p style="font-size:0.85rem; color:var(--text-muted); margin-bottom:15px;">
                            Cochez les modules auxquels cet utilisateur peut accéder. (Ignoré pour les Super Admins).
                        </p>
                        
                        <?php foreach($available_modules as $key => $label): ?>
                        <label style="display:flex; align-items:center; gap:10px; margin-bottom:10px; cursor:pointer;">
                            <input type="checkbox" name="permissions[]" value="<?= $key ?>" <?= in_array($key, $userPerms) ? 'checked' : '' ?>>
                            <span><?= e($label) ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <hr style="border:0; border-top:1px solid var(--border); margin:20px 0;">
                <button type="submit" name="save_user" class="btn btn-primary btn-lg"><i class="fas fa-save"></i> Enregistrer l'utilisateur</button>
            </form>
        </div>
    </div>
    
    <script>
    function togglePermissions() {
        var role = document.getElementById('roleSelector').value;
        var box = document.getElementById('permissionsBox');
        if (role === 'super_admin') {
            box.style.opacity = '0.5';
            box.style.pointerEvents = 'none';
        } else {
            box.style.opacity = '1';
            box.style.pointerEvents = 'auto';
        }
    }
    togglePermissions();
    </script>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
