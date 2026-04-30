<?php
/**
 * ShopMax — Admin Dashboard (index)
 */
session_start();
$pageTitle = 'Tableau de bord';
require_once __DIR__ . '/includes/admin_header.php';

$pdo = getPDO();

// --- KPIs ---
// 1. Total Chiffre d'Affaires (Commandes livrées)
$stmt = $pdo->query("SELECT COALESCE(SUM(total), 0) FROM commandes WHERE statut = 'livree'");
$caTotal = $stmt->fetchColumn();

// 2. Commandes En Attente
$stmt = $pdo->query("SELECT COUNT(*) FROM commandes WHERE statut = 'en_attente'");
$cmdAttente = $stmt->fetchColumn();

$params = getAllParams();
$seuilGlobal = (int)($params['seuil_alerte_global'] ?? 5);

// 3. Produits en Alerte / Rupture (Sans Variante)
$stmt = $pdo->prepare("SELECT COUNT(*) FROM produits WHERE a_variants = 0 AND stock <= GREATEST(COALESCE(stock_alerte, 0), :seuil) AND actif = 1 AND supprime = 0");
$stmt->execute(['seuil' => $seuilGlobal]);
$alerteProduit = $stmt->fetchColumn();

// 4. Variantes en Alerte / Rupture
$stmt = $pdo->prepare("SELECT COUNT(*) FROM produit_variantes pv JOIN produits p ON p.id = pv.produit_id WHERE pv.stock <= GREATEST(COALESCE(pv.stock_alerte, 0), :seuil) AND pv.actif = 1 AND p.actif = 1 AND p.supprime = 0");
$stmt->execute(['seuil' => $seuilGlobal]);
$alerteVariante = $stmt->fetchColumn();
$rupturesAlertes = $alerteProduit + $alerteVariante;

// --- Listes pour le tableau d'alerte (Limit 10) ---
$stmt = $pdo->prepare("
    SELECT id, nom, stock, GREATEST(COALESCE(stock_alerte, 0), $seuilGlobal) as seuil_effectif, 'produit' as type 
    FROM produits 
    WHERE a_variants = 0 AND stock <= GREATEST(COALESCE(stock_alerte, 0), $seuilGlobal) AND actif = 1 AND supprime = 0 
    UNION ALL 
    SELECT pv.produit_id as id, CONCAT(p.nom, ' (', pv.valeur, ')') as nom, pv.stock, GREATEST(COALESCE(pv.stock_alerte, 0), $seuilGlobal) as seuil_effectif, 'variante' as type 
    FROM produit_variantes pv JOIN produits p ON p.id = pv.produit_id 
    WHERE pv.stock <= GREATEST(COALESCE(pv.stock_alerte, 0), $seuilGlobal) AND pv.actif = 1 AND p.actif = 1 AND p.supprime = 0
    ORDER BY stock ASC LIMIT 10
");
$stmt->execute();
$listeAlertes = $stmt->fetchAll();

// 5. Total Clients
$stmt = $pdo->query("SELECT COUNT(*) FROM clients");
$totalClients = $stmt->fetchColumn();

// --- Graphiques Rapides : 5 Dernières Commandes ---
$stmt = $pdo->query("SELECT * FROM commandes ORDER BY date_commande DESC LIMIT 5");
$dernieresCmd = $stmt->fetchAll();

require_once __DIR__ . '/../includes/stats_functions.php';

// --- DATA POUR CHART.JS (Optimisé via PHP Service) ---
$dataSemaine    = getStatsVentesSemaine($pdo);
$dataMois       = getStatsVentesMois($pdo);
$dataCategories = getStatsParCategorie($pdo);
$dataTopProduits = getStatsTopProduits($pdo);

// Helper CSS status
function getStatusBadge($statut) {
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
        <h1>Tableau de bord</h1>
        <p>Bienvenue, <?= e($_SESSION['admin_username']) ?>. Voici un aperçu de l'activité.</p>
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-info">
            <h3>Chiffre d'affaires</h3>
            <div class="stat-value"><?= formatPrix($caTotal) ?></div>
        </div>
        <div class="stat-icon success"><i class="fas fa-wallet"></i></div>
    </div>
    
    <div class="stat-card">
        <div class="stat-info">
            <h3>Cmd en attente</h3>
            <div class="stat-value"><?= $cmdAttente ?></div>
        </div>
        <div class="stat-icon warning"><i class="fas fa-clock"></i></div>
    </div>
    
    <div class="stat-card">
        <div class="stat-info">
            <h3>Alertes Stock</h3>
            <div class="stat-value"><?= $rupturesAlertes ?></div>
        </div>
        <div class="stat-icon danger"><i class="fas fa-exclamation-triangle"></i></div>
    </div>
    
    <div class="stat-card">
        <div class="stat-info">
            <h3>Clients Inscrits</h3>
            <div class="stat-value"><?= $totalClients ?></div>
        </div>
        <div class="stat-icon primary"><i class="fas fa-users"></i></div>
    </div>
</div>

<div class="stats-grid" style="margin-bottom: 24px;">
    <!-- Chart: Ventes par semaine -->
    <div class="card" style="margin-bottom: 0;">
        <div class="card-header"><div class="card-title">Ventes de la Semaine (FCFA)</div></div>
        <div class="card-body"><canvas id="chartSemaine" height="200"></canvas></div>
    </div>
    
    <!-- Chart: Ventes par mois -->
    <div class="card" style="margin-bottom: 0;">
        <div class="card-header"><div class="card-title">Ventes Annuelles par Mois (FCFA)</div></div>
        <div class="card-body"><canvas id="chartMois" height="200"></canvas></div>
    </div>
</div>

<div class="stats-grid" style="margin-bottom: 24px;">
    <!-- Chart: Top Categories -->
    <div class="card" style="margin-bottom: 0;">
        <div class="card-header"><div class="card-title">Top Catégories</div></div>
        <div class="card-body" style="display:flex; justify-content:center;"><canvas id="chartCategories" height="220"></canvas></div>
    </div>
    
    <!-- Chart: Top 7 Produits -->
    <div class="card" style="margin-bottom: 0;">
        <div class="card-header"><div class="card-title">Top 7 Produits Vendus</div></div>
        <div class="card-body"><canvas id="chartTopProduits" height="220"></canvas></div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div class="card-title"><i class="fas fa-receipt"></i> Dernières commandes</div>
        <a href="commandes.php" class="btn btn-secondary btn-sm">Voir tout</a>
    </div>
    <div class="card-body" style="padding: 0;">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Numéro</th>
                        <th>Client</th>
                        <th>Date</th>
                        <th>Total</th>
                        <th>Statut</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($dernieresCmd)): ?>
                    <tr><td colspan="6" style="text-align: center;">Aucune commande pour le moment.</td></tr>
                    <?php else: ?>
                    <?php foreach ($dernieresCmd as $cmd): ?>
                    <tr>
                        <td><strong><?= e($cmd['numero_cmd']) ?></strong></td>
                        <td>
                            <div><?= e($cmd['nom_client']) ?></div>
                            <div style="font-size: 0.8rem; color: var(--text-muted);"><i class="fab fa-whatsapp"></i> <?= e($cmd['whatsapp']) ?></div>
                        </td>
                        <td><?= date('d/m/Y H:i', strtotime($cmd['date_commande'])) ?></td>
                        <td><strong><?= formatPrix($cmd['total']) ?></strong></td>
                        <td><?= getStatusBadge($cmd['statut']) ?></td>
                        <td>
                            <a href="commandes.php?edit=<?= $cmd['id'] ?>" class="btn-icon" title="Gérer"><i class="fas fa-cog"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Table: Alertes de Stock -->
<div class="card" style="margin-top: 24px;">
    <div class="card-header">
        <div class="card-title"><i class="fas fa-exclamation-triangle" style="color: var(--danger);"></i> Alertes de Stock</div>
        <a href="produits.php" class="btn btn-secondary btn-sm">Gérer les produits</a>
    </div>
    <div class="card-body" style="padding: 0;">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Produit</th>
                        <th>Type</th>
                        <th>Stock Actuel</th>
                        <th>Seuil d'Alerte</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($listeAlertes)): ?>
                    <tr><td colspan="5" style="text-align: center;">Aucun produit en alerte de stock.</td></tr>
                    <?php else: ?>
                    <?php foreach ($listeAlertes as $alerte): ?>
                    <tr>
                        <td><strong><?= e($alerte['nom']) ?></strong></td>
                        <td><span class="badge" style="background: var(--bg-main); color: var(--text-muted);"><?= ucfirst($alerte['type']) ?></span></td>
                        <td>
                            <span class="badge <?= $alerte['stock'] <= 0 ? 'annulee' : 'en_attente' ?>" style="font-size: 0.9rem;">
                                <?= $alerte['stock'] ?>
                            </span>
                        </td>
                        <td><?= $alerte['seuil_effectif'] ?></td>
                        <td>
                            <a href="produits.php?edit=<?= $alerte['id'] ?>" class="btn-icon" title="Mettre à jour"><i class="fas fa-edit"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Chart.JS Integration -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Parser les données depuis PHP
    const dataSemaine = <?= json_encode($dataSemaine) ?>;
    const dataMois = <?= json_encode($dataMois) ?>;
    const dataCat = <?= json_encode($dataCategories) ?>;
    const dataTopProd = <?= json_encode($dataTopProduits) ?>;

    // 1. Chart Semaine (Bar)
    if (document.getElementById('chartSemaine')) {
        new Chart(document.getElementById('chartSemaine'), {
            type: 'bar',
            data: {
                labels: dataSemaine.map(d => d.jour),
                datasets: [{
                    label: 'Chiffre d\'Affaires (FCFA)',
                    data: dataSemaine.map(d => d.ca_total),
                    backgroundColor: '#10B981',
                    borderRadius: 4
                }]
            },
            options: { responsive: true, maintainAspectRatio: false }
        });
    }

    // 2. Chart Mois (Line)
    if (document.getElementById('chartMois')) {
        new Chart(document.getElementById('chartMois'), {
            type: 'line',
            data: {
                labels: dataMois.map(d => d.mois),
                datasets: [{
                    label: 'Chiffre d\'Affaires (FCFA)',
                    data: dataMois.map(d => d.ca_total),
                    borderColor: '#3B82F6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: { responsive: true, maintainAspectRatio: false }
        });
    }

    // 3. Chart Categories (Doughnut)
    if (document.getElementById('chartCategories')) {
        new Chart(document.getElementById('chartCategories'), {
            type: 'doughnut',
            data: {
                labels: dataCat.map(d => d.categorie),
                datasets: [{
                    data: dataCat.map(d => d.quantite_totale),
                    backgroundColor: ['#082F63', '#FFB020', '#10B981', '#3B82F6', '#F59E0B', '#EF4444']
                }]
            },
            options: { responsive: true, maintainAspectRatio: false }
        });
    }

    // 4. Chart Top Produits (Horizontal Bar)
    if (document.getElementById('chartTopProduits')) {
        new Chart(document.getElementById('chartTopProduits'), {
            type: 'bar',
            data: {
                labels: dataTopProd.map(d => d.produit),
                datasets: [{
                    label: 'Quantité Vendue',
                    data: dataTopProd.map(d => d.quantite_totale),
                    backgroundColor: '#FFB020',
                    borderRadius: 4
                }]
            },
            options: { 
                indexAxis: 'y', 
                responsive: true, 
                maintainAspectRatio: false 
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
