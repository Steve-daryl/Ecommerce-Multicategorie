<?php
/**
 * ShopMax — Admin Produits (Avancé)
 * Fixed: slug uniqueness check, physical image deletion on product delete & image replace
 */
session_start();
$pageTitle = 'Produits';
require_once __DIR__ . '/includes/admin_header.php';

$pdo = getPDO();
$action = $_GET['action'] ?? 'list';
$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
if ($editId) $action = 'edit';
if (isset($_GET['add'])) $action = 'add';

$message = '';
$error = '';

/**
 * Helper: Delete physical image files for a product
 */
function deleteProductImages($pdo, $productId) {
    $stmtImg = $pdo->prepare("SELECT chemin FROM image_produit WHERE produit_id = ?");
    $stmtImg->execute([$productId]);
    $images = $stmtImg->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($images as $imgPath) {
        $fullPath = __DIR__ . '/../' . ltrim($imgPath, '/');
        if (file_exists($fullPath)) {
            @unlink($fullPath);
        }
    }
    
    // Delete DB records
    $pdo->prepare("DELETE FROM image_produit WHERE produit_id = ?")->execute([$productId]);
}

// Soft Delete Handle — also delete physical images
if (isset($_GET['delete'])) {
    $delId = (int)$_GET['delete'];
    
    // Delete physical images first
    deleteProductImages($pdo, $delId);
    
    // Soft delete the product
    $pdo->prepare("UPDATE produits SET supprime = 1, updated_at = NOW() WHERE id = ?")->execute([$delId]);
    header("Location: produits.php");
    exit;
}

// Traitement du formulaire Ajouter / Modifier
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_product'])) {
    $id = (int)($_POST['produit_id'] ?? 0);
    $nom = trim($_POST['nom']);
    $description = trim($_POST['description']);
    $caracteristiques = trim($_POST['caracteristiques'] ?? '');
    
    $prix = (float)$_POST['prix'];
    $prix_promo = !empty($_POST['prix_promo']) ? (float)$_POST['prix_promo'] : null;
    
    $categorie_id = (int)$_POST['categorie_id'];
    $marque_id = !empty($_POST['marque_id']) ? (int)$_POST['marque_id'] : null;
    $actif = isset($_POST['actif']) ? 1 : 0;
    $vedette = isset($_POST['vedette']) ? 1 : 0;
    
    // Variants parsing
    $variantValues = $_POST['variant_valeur'] ?? [];
    $variantPrices = $_POST['variant_prix'] ?? [];
    $variantStocks = $_POST['variant_stock'] ?? [];
    
    $hasVariants = count($variantValues) > 0 ? 1 : 0;
    
    // Si variants, le stock principal est la somme, sinon on prend le champs stock normal
    $stock = $hasVariants ? array_sum($variantStocks) : (int)($_POST['stock'] ?? 0);
    
    // Generate slug
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $nom)));
    
    try {
        // Check slug uniqueness BEFORE starting transaction
        // Exclude current product ID when editing
        if ($id) {
            $checkSlug = $pdo->prepare("SELECT id FROM produits WHERE slug = ? AND id != ?");
            $checkSlug->execute([$slug, $id]);
        } else {
            $checkSlug = $pdo->prepare("SELECT id FROM produits WHERE slug = ?");
            $checkSlug->execute([$slug]);
        }
        
        if ($checkSlug->fetch()) {
            // Slug already exists for another product — make it unique
            $slug = $slug . '-' . time();
        }
        
        $pdo->beginTransaction();
        
        if ($id) {
            $stmt = $pdo->prepare("UPDATE produits SET nom=?, slug=?, description=?, caracteristiques=?, prix=?, prix_promo=?, stock=?, categorie_id=?, marque_id=?, actif=?, vedette=?, a_variants=?, updated_at=NOW() WHERE id=?");
            $stmt->execute([$nom, $slug, $description, $caracteristiques, $prix, $prix_promo, $stock, $categorie_id, $marque_id, $actif, $vedette, $hasVariants, $id]);
            $message = "Produit mis à jour avec succès.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO produits (nom, slug, description, caracteristiques, prix, prix_promo, stock, categorie_id, marque_id, actif, vedette, a_variants) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$nom, $slug, $description, $caracteristiques, $prix, $prix_promo, $stock, $categorie_id, $marque_id, $actif, $vedette, $hasVariants]);
            $id = $pdo->lastInsertId();
            $message = "Produit ajouté avec succès.";
        }
        
        // Variants processing
        $pdo->prepare("DELETE FROM produit_variantes WHERE produit_id = ?")->execute([$id]);
        if ($hasVariants) {
            $stmtVar = $pdo->prepare("INSERT INTO produit_variantes (produit_id, valeur, prix, stock) VALUES (?, ?, ?, ?)");
            for ($i = 0; $i < count($variantValues); $i++) {
                if (trim($variantValues[$i]) !== '') {
                    $stmtVar->execute([$id, trim($variantValues[$i]), (float)$variantPrices[$i], (int)$variantStocks[$i]]);
                }
            }
        }
        
        // Images processing (Multiple Uploads)
        if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
            // Delete old physical image files before replacing
            deleteProductImages($pdo, $id);
            
            $fileCount = count($_FILES['images']['name']);
            $stmtImg = $pdo->prepare("INSERT INTO image_produit (produit_id, chemin, ordre) VALUES (?, ?, ?)");
            
            $uploadDir = __DIR__ . '/../assets/images/products/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            
            for ($i = 0; $i < $fileCount; $i++) {
                if ($_FILES['images']['error'][$i] === UPLOAD_ERR_OK) {
                    $tmp = $_FILES['images']['tmp_name'][$i];
                    $name = time() . '_' . $i . '_' . preg_replace('/[^A-Za-z0-9.-]/', '', $_FILES['images']['name'][$i]);
                    $destPath = $uploadDir . $name;
                    
                    if (move_uploaded_file($tmp, $destPath)) {
                        $dbPath = '/assets/images/products/' . $name;
                        $stmtImg->execute([$id, $dbPath, $i]);
                    }
                }
            }
        }
        
        $pdo->commit();
        $action = 'list';
    } catch (PDOException $e) {
        $pdo->rollBack();
        // Clear the success message on error
        $message = '';
        if ($e->getCode() == 23000) $error = "Le nom/slug existe déjà pour un autre produit.";
        else $error = "Erreur SQL : " . $e->getMessage();
    }
}

// Helpers combo
$cats = $pdo->query("SELECT id, nom FROM categories ORDER BY nom")->fetchAll();
$marques = $pdo->query("SELECT id, nom FROM marques ORDER BY nom")->fetchAll();
?>

<div class="page-header">
    <div class="page-title">
        <h1>Produits</h1>
        <p>Gérez votre catalogue, variantes, caractéristiques et images multiples.</p>
    </div>
    <div>
        <?php if ($action === 'list'): ?>
        <a href="?add=1" class="btn btn-primary"><i class="fas fa-plus"></i> Ajouter un Produit</a>
        <?php else: ?>
        <a href="produits.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Retour</a>
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
    $filter_cat = $_GET['categorie_id'] ?? '';
    $filter_actif = $_GET['actif'] ?? '';
    $date_debut = trim($_GET['date_debut'] ?? '');
    $date_fin = trim($_GET['date_fin'] ?? '');
    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = (int)($_GET['limit'] ?? 10);
    if (!in_array($limit, [5, 10, 15, 20, 25, 30, 50, 100])) $limit = 10;
    $offset = ($page - 1) * $limit;

    $where = "WHERE p.supprime = 0";
    $params = [];

    if ($search !== '') {
        $where .= " AND p.nom LIKE ?";
        $params[] = "%$search%";
    }
    if ($filter_cat !== '') {
        $where .= " AND p.categorie_id = ?";
        $params[] = $filter_cat;
    }
    if ($filter_actif !== '') {
        $where .= " AND p.actif = ?";
        $params[] = $filter_actif;
    }
    if ($date_debut !== '') {
        $where .= " AND DATE(p.created_at) >= ?";
        $params[] = $date_debut;
    }
    if ($date_fin !== '') {
        $where .= " AND DATE(p.created_at) <= ?";
        $params[] = $date_fin;
    }

    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM produits p $where");
    $stmtCount->execute($params);
    $total = $stmtCount->fetchColumn();
    $totalPages = max(1, ceil($total / $limit));

    $stmt = $pdo->prepare("
        SELECT p.*, c.nom AS cat_nom, m.nom AS marque_nom,
        (SELECT chemin FROM image_produit WHERE produit_id = p.id ORDER BY ordre ASC LIMIT 1) AS image
        FROM produits p
        LEFT JOIN categories c ON p.categorie_id = c.id
        LEFT JOIN marques m ON p.marque_id = m.id
        $where
        ORDER BY p.id DESC
        LIMIT $limit OFFSET $offset
    ");
    $stmt->execute($params);
    $produits = $stmt->fetchAll();
    
    // Fetch categories for the filter dropdown
    $catsFilter = $pdo->query("SELECT id, nom FROM categories ORDER BY nom")->fetchAll();
    ?>
    <div class="card">
        <div class="card-header"><div class="card-title"><i class="fas fa-box-open"></i> Catalogue Actif (<?= $total ?>)</div></div>
        <div class="card-body" style="display:flex; flex-direction:column; padding:0;">
            <div style="padding:20px;">
                <form method="GET" class="filter-bar">
                    <input type="text" name="search" class="form-control search-input" placeholder="Rechercher un produit..." value="<?= e($search) ?>">
                    
                    <input type="date" name="date_debut" class="form-control" value="<?= e($date_debut) ?>" title="Date début">
                    <input type="date" name="date_fin" class="form-control" value="<?= e($date_fin) ?>" title="Date fin">

                    <select name="categorie_id" class="form-control">
                        <option value="">Toutes les catégories</option>
                        <?php foreach($catsFilter as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= $filter_cat == $c['id'] ? 'selected' : '' ?>><?= e($c['nom']) ?></option>
                        <?php endforeach; ?>
                    </select>

                <select name="actif" class="form-control">
                    <option value="">Tous les statuts</option>
                    <option value="1" <?= $filter_actif === '1' ? 'selected' : '' ?>>Actifs</option>
                    <option value="0" <?= $filter_actif === '0' ? 'selected' : '' ?>>Inactifs</option>
                </select>

                <select name="limit" class="form-control" style="min-width: 100px;">
                    <?php foreach([5,10,15,20,25,30,50,100] as $l): ?>
                    <option value="<?= $l ?>" <?= $limit === $l ? 'selected' : '' ?>><?= $l ?> par page</option>
                    <?php endforeach; ?>
                </select>

                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filtrer</button>
                <a href="produits.php" class="btn btn-secondary" title="Réinitialiser"><i class="fas fa-sync-alt"></i></a>
            </form>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Nom</th>
                            <th>Prix (FCFA)</th>
                            <th>Stock</th>
                            <th>Cat. / Marque</th>
                            <th>Variantes</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($produits)): ?>
                        <tr><td colspan="8" style="text-align: center;">Aucun produit trouvé.</td></tr>
                        <?php else: ?>
                        <?php foreach($produits as $p): ?>
                        <tr>
                            <td>
                                <?php $imgSrc = $p['image'] ? BASE_URL . $p['image'] : BASE_URL . '/assets/images/no-image.png'; ?>
                                <img src="<?= e($imgSrc) ?>" alt="Produit" width="40" height="40" style="object-fit:cover; border-radius:4px;">
                            </td>
                            <td><strong><?= e($p['nom']) ?></strong></td>
                            <td><?= formatPrix($p['prix_promo'] ?: $p['prix']) ?></td>
                            <td>
                                <?php if($p['stock'] <= $p['stock_alerte']): ?>
                                <span style="color:var(--danger); font-weight:bold;"><?= $p['stock'] ?></span>
                                <?php else: ?>
                                <?= $p['stock'] ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge" style="background:#E8F0FB; color:#082F63;"><?= e($p['cat_nom']) ?></span>
                                <span style="font-size:0.8em; color:var(--text-muted);"><?= e($p['marque_nom']) ?></span>
                            </td>
                            <td><?= $p['a_variants'] ? '<i class="fas fa-check" style="color:var(--success)"></i> Oui' : '-' ?></td>
                            <td><?= $p['actif'] ? '<span class="badge livree">Actif</span>' : '<span class="badge annulee">Inactif</span>' ?></td>
                            <td class="actions">
                                <a href="?edit=<?= $p['id'] ?>" class="btn-icon" title="Modifier"><i class="fas fa-edit"></i></a>
                                <a href="?delete=<?= $p['id'] ?>" class="btn-icon" title="Supprimer" onclick="return confirm('Sûr de vouloir supprimer ce produit ? Les images associées seront aussi supprimées.');" style="color:var(--danger);"><i class="fas fa-trash"></i></a>
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
                    $qsParams = "search=".urlencode($search)."&categorie_id=".urlencode($filter_cat)."&actif=".urlencode($filter_actif)."&date_debut=".urlencode($date_debut)."&date_fin=".urlencode($date_fin)."&limit=".$limit;
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
    $p = [];
    $variants = [];
    $images = [];
    if ($action === 'edit') {
        $stmt = $pdo->prepare("SELECT * FROM produits WHERE id = ?");
        $stmt->execute([$editId]);
        $p = $stmt->fetch() ?: [];
        
        $stmtV = $pdo->prepare("SELECT * FROM produit_variantes WHERE produit_id = ?");
        $stmtV->execute([$editId]);
        $variants = $stmtV->fetchAll();
        
        $stmtI = $pdo->prepare("SELECT chemin FROM image_produit WHERE produit_id = ? ORDER BY ordre ASC");
        $stmtI->execute([$editId]);
        $images = $stmtI->fetchAll(PDO::FETCH_COLUMN);
    }
    
    $nom = $_POST['nom'] ?? $p['nom'] ?? '';
    $desc = $_POST['description'] ?? $p['description'] ?? '';
    $carac = $_POST['caracteristiques'] ?? $p['caracteristiques'] ?? '';
    $prix = $_POST['prix'] ?? $p['prix'] ?? '';
    $promo = $_POST['prix_promo'] ?? $p['prix_promo'] ?? '';
    $stock = $_POST['stock'] ?? $p['stock'] ?? '0';
    $cat_id = $_POST['categorie_id'] ?? $p['categorie_id'] ?? '';
    $marq_id = $_POST['marque_id'] ?? $p['marque_id'] ?? '';
    $actif = isset($_POST['save_product']) ? (isset($_POST['actif']) ? 1 : 0) : ($p['actif'] ?? 1);
    $vedette = isset($_POST['save_product']) ? (isset($_POST['vedette']) ? 1 : 0) : ($p['vedette'] ?? 0);
    ?>
    <div class="card">
        <div class="card-header"><div class="card-title"><?= $action === 'edit' ? 'Modifier le Produit' : 'Ajouter un Produit' ?></div></div>
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data">
                <?php if ($action === 'edit'): ?>
                <input type="hidden" name="produit_id" value="<?= $p['id'] ?>">
                <?php endif; ?>
                
                <div style="display:grid; grid-template-columns: 2fr 1fr; gap: 30px;">
                    <!-- LEFT COLUMN -->
                    <div>
                        <div class="form-group">
                            <label class="form-label">Nom du produit <span style="color:var(--danger)">*</span></label>
                            <input type="text" name="nom" class="form-control" value="<?= e($nom) ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Description Courte</label>
                            <textarea name="description" class="form-control" rows="3"><?= e($desc) ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Caractéristiques (<small>Texte long ou HTML</small>)</label>
                            <textarea name="caracteristiques" class="form-control" rows="6"><?= e($carac) ?></textarea>
                        </div>
                        
                        <!-- VARIANTS BUILDER -->
                        <div style="background:var(--bg-main); padding: 15px; border-radius: 6px; border:1px solid var(--border); margin-top: 20px;">
                            <h4 style="margin-top:0; color:var(--primary); display:flex; justify-content:space-between; align-items:center;">
                                Variantes du Produit (Ex: Taille, Couleur, Stockage)
                                <button type="button" class="btn btn-secondary btn-sm" onclick="addVariantRow()">+ Ajouter Variante</button>
                            </h4>
                            <p style="font-size:0.85rem; color:var(--text-muted); margin-bottom:10px;">Si le produit a des variantes, le stock global sera la somme des stocks des variantes.</p>
                            
                            <table class="table" style="background:white; border:1px solid var(--border);">
                                <thead style="font-size:0.8rem;">
                                    <tr>
                                        <th>Label (ex: Rouge, 128Go)</th>
                                        <th>Prix Rallonge (+ FCFA)</th>
                                        <th>Stock Spécifique</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody id="variantsContainer">
                                    <?php foreach($variants as $v): ?>
                                    <tr>
                                        <td><input type="text" name="variant_valeur[]" value="<?= e($v['valeur']) ?>" class="form-control" required></td>
                                        <td><input type="number" name="variant_prix[]" value="<?= e($v['prix_supplementaire']) ?>" class="form-control" step="0.01"></td>
                                        <td><input type="number" name="variant_stock[]" value="<?= e($v['stock']) ?>" class="form-control" required min="0"></td>
                                        <td><button type="button" class="btn-icon" onclick="this.closest('tr').remove()"><i class="fas fa-trash" style="color:var(--danger)"></i></button></td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <!-- Si vide, générer une ligne invisible template au clic en JS -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- RIGHT COLUMN -->
                    <div>
                        <div style="background:#f8fafc; padding:15px; border-radius:6px; border:1px solid var(--border); margin-bottom:20px;">
                            <div style="display:flex; gap:15px; margin-bottom:15px;">
                                <div class="form-group" style="flex:1; margin:0;">
                                    <label class="form-label">Prix Base <span style="color:var(--danger)">*</span></label>
                                    <input type="number" name="prix" class="form-control" value="<?= e($prix) ?>" required min="0">
                                </div>
                                <div class="form-group" style="flex:1; margin:0;">
                                    <label class="form-label">Prix Promo</label>
                                    <input type="number" name="prix_promo" class="form-control" value="<?= e($promo) ?>" min="0">
                                </div>
                            </div>
                            <!-- Stock is disabled/overridden if variants exist but we keep the main box -->
                            <div class="form-group" style="margin:0;">
                                <label class="form-label">Stock Global</label>
                                <input type="number" name="stock" id="stockGlobal" class="form-control" value="<?= e($stock) ?>" min="0">
                                <p style="font-size:0.8rem; color:var(--text-muted); margin-top:5px;">Ignoré si des variantes sont définies.</p>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Catégorie <span style="color:var(--danger)">*</span></label>
                            <select name="categorie_id" class="form-control" required>
                                <option value="">Choisir...</option>
                                <?php foreach($cats as $c): ?>
                                <option value="<?= $c['id'] ?>" <?= $cat_id == $c['id'] ? 'selected' : '' ?>><?= e($c['nom']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Marque</label>
                            <select name="marque_id" class="form-control">
                                <option value="">Aucune</option>
                                <?php foreach($marques as $m): ?>
                                <option value="<?= $m['id'] ?>" <?= $marq_id == $m['id'] ? 'selected' : '' ?>><?= e($m['nom']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Galerie d'images</label>
                            <input type="file" name="images[]" class="form-control" accept="image/*" multiple>
                            <p style="font-size:0.8rem; color:var(--text-muted); margin-top:5px;">Sélectionnez plusieurs fichiers à la fois. Si vous uploadez de nouvelles images, les anciennes seront remplacées.</p>
                            
                            <?php if(!empty($images)): ?>
                            <div style="display:flex; gap:10px; margin-top:10px; flex-wrap:wrap;">
                                <?php foreach($images as $img): ?>
                                <img src="<?= BASE_URL . e($img) ?>" style="width:60px; height:60px; object-fit:cover; border-radius:4px; border:1px solid #ccc;">
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group" style="margin-top: 30px;">
                            <label style="display:flex; align-items:center; gap:10px; cursor:pointer; margin-bottom:15px;">
                                <input type="checkbox" name="actif" value="1" <?= $actif ? 'checked' : '' ?> style="width:18px; height:18px;">
                                <span style="font-weight:600;">Produit Actif / Visible</span>
                            </label>
                            <label style="display:flex; align-items:center; gap:10px; cursor:pointer;">
                                <input type="checkbox" name="vedette" value="1" <?= $vedette ? 'checked' : '' ?> style="width:18px; height:18px;">
                                <span style="font-weight:600;">⭐ Mettre en Vedette (Accueil)</span>
                            </label>
                        </div>
                    </div>
                </div>
                
                <hr style="border:0; border-top:1px solid var(--border); margin:20px 0;">
                <button type="submit" name="save_product" class="btn btn-primary btn-lg">
                    <i class="fas fa-save"></i> Enregistrer
                </button>
            </form>
        </div>
    </div>
    
    <script>
    function addVariantRow() {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td><input type="text" name="variant_valeur[]" placeholder="Ex: 256 Go Gris" class="form-control" required></td>
            <td><input type="number" name="variant_prix[]" placeholder="0" class="form-control" step="0.01"></td>
            <td><input type="number" name="variant_stock[]" placeholder="10" class="form-control" required min="0"></td>
            <td><button type="button" class="btn-icon" onclick="this.closest('tr').remove()"><i class="fas fa-trash" style="color:var(--danger)"></i></button></td>
        `;
        document.getElementById('variantsContainer').appendChild(tr);
    }
    </script>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
