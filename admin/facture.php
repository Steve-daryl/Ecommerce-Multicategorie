<?php
/**
 * ShopMax — Facture (A4, A5, A6)
 * Fixed: proper PDF export without content cutting, respects format
 */
session_start();
if (!isset($_SESSION['admin_id'])) {
    die("Non autorisé.");
}

require_once __DIR__ . '/../includes/config.php';
$pdo = getPDO();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$format = isset($_GET['format']) ? strtolower($_GET['format']) : 'a4';

$validFormats = ['a4', 'a5', 'a6'];
if (!in_array($format, $validFormats)) $format = 'a4';

// Récupérer la commande
$stmt = $pdo->prepare("SELECT * FROM commandes WHERE id = ?");
$stmt->execute([$id]);
$cmd = $stmt->fetch();

if (!$cmd) die("Commande introuvable.");

// Récupérer les articles
$stmtItems = $pdo->prepare("SELECT * FROM commande_items WHERE commande_id = ?");
$stmtItems->execute([$id]);
$items = $stmtItems->fetchAll();

// Récupérer paramètres boutique
$params = getAllParams();
$shopName = $params['nom_boutique'] ?? 'ShopMax';
$devise = $params['devise'] ?? 'FCFA';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Facture_<?= e($cmd['numero_cmd']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <!-- html2pdf.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        :root {
            --primary: #082F63;
            --text: #1E293B;
            --border: #E2E8F0;
        }
        * {
            box-sizing: border-box;
        }
        body {
            font-family: 'Inter', sans-serif;
            color: var(--text);
            margin: 0;
            padding: 0;
            background: #f1f5f9;
            display: flex;
            justify-content: center;
        }
        .page {
            background: white;
            margin: 20px auto;
            position: relative;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        /* Format A4 */
        .page[data-format="a4"] { width: 210mm; min-height: 297mm; padding: 20mm; font-size: 12pt; }
        /* Format A5 */
        .page[data-format="a5"] { width: 148mm; min-height: 210mm; padding: 15mm; font-size: 10pt; }
        /* Format A6 */
        .page[data-format="a6"] { width: 105mm; min-height: 148mm; padding: 10mm; font-size: 8pt; }

        .header { 
            display: flex; 
            justify-content: space-between; 
            align-items: flex-start;
            border-bottom: 3px solid var(--primary); 
            padding-bottom: 15px; 
            margin-bottom: 20px; 
            page-break-inside: avoid;
        }
        .logo { font-size: 1.8em; font-weight: bold; color: var(--primary); }
        .facture-title { text-align: right; }
        .facture-title h2 { color: var(--primary); margin: 0 0 5px 0; font-size: 1.4em; }
        .facture-title div { font-size: 0.9em; margin-bottom: 2px; }
        
        .details-grid { 
            display: flex; 
            gap: 15px; 
            margin-bottom: 25px; 
            page-break-inside: avoid;
        }
        .details-grid .box { flex: 1; }
        .box { 
            background: #f8fafc; 
            padding: 12px; 
            border-radius: 6px; 
            border: 1px solid var(--border); 
            page-break-inside: avoid;
        }
        .box h3 { margin-top: 0; font-size: 1em; color: var(--primary); margin-bottom: 8px; }
        .box p { margin: 3px 0; font-size: 0.9em; line-height: 1.4; }
        
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-bottom: 15px; 
            page-break-inside: auto;
        }
        thead { display: table-header-group; }
        tr { page-break-inside: avoid; page-break-after: auto; }
        th, td { padding: 8px 10px; border-bottom: 1px solid var(--border); text-align: left; font-size: 0.95em; }
        th { background: #f8fafc; font-weight: 600; color: var(--primary); }
        
        .totals { 
            margin-left: auto; 
            width: 55%; 
            page-break-inside: avoid;
        }
        .totals-row { display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid var(--border); font-size: 0.95em; }
        .totals-row.grand-total { 
            font-weight: bold; 
            font-size: 1.15em; 
            color: var(--primary); 
            border-bottom: none; 
            border-top: 3px solid var(--primary); 
            padding-top: 12px;
            margin-top: 5px;
        }

        .invoice-footer {
            margin-top: 30px; 
            text-align: center; 
            font-size: 0.85em; 
            color: #64748B;
            page-break-inside: avoid;
            border-top: 1px solid var(--border);
            padding-top: 15px;
        }

        /* Actions Toolbar (Hidden on print) */
        .toolbar {
            position: fixed;
            top: 20px;
            right: 20px;
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
            gap: 10px;
            z-index: 100;
        }
        .btn {
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-family: inherit;
            font-weight: 600;
            font-size: 0.9em;
        }
        .btn-print { background: var(--primary); color: white; }
        .btn-pdf { background: #10B981; color: white; }

        @media print {
            body { background: white; }
            .page { 
                margin: 0; 
                box-shadow: none; 
                border: none; 
                width: 100% !important;
                min-height: auto !important;
                padding: 15mm !important;
            }
            .toolbar { display: none !important; }
        }

        /* PDF-specific: prevent all page breaks inside key elements */
        .no-break { page-break-inside: avoid; }
    </style>
</head>
<body>

    <div class="toolbar">
        <h4 style="margin:0; text-align:center;">Actions</h4>
        <button class="btn btn-print" onclick="window.print()">🖨️ Imprimer</button>
        <button class="btn btn-pdf" onclick="generatePDF()">📄 Exporter PDF</button>
        <hr style="margin: 5px 0;">
        <select id="formatSelector" onchange="changeFormat()" style="padding:8px; border-radius:4px; border:1px solid #ccc;">
            <option value="a4" <?= $format === 'a4' ? 'selected' : '' ?>>Format A4</option>
            <option value="a5" <?= $format === 'a5' ? 'selected' : '' ?>>Format A5</option>
            <option value="a6" <?= $format === 'a6' ? 'selected' : '' ?>>Format A6</option>
        </select>
    </div>

    <!-- Le Conteneur de la Facture -->
    <div id="invoiceContent" class="page" data-format="<?= $format ?>">
        <div class="header no-break">
            <div class="logo">⚡ <?= e($shopName) ?></div>
            <div class="facture-title">
                <h2>FACTURE</h2>
                <div>Numéro : <strong><?= e($cmd['numero_cmd']) ?></strong></div>
                <div>Date : <?= date('d/m/Y', strtotime($cmd['date_commande'])) ?></div>
            </div>
        </div>

        <div class="details-grid no-break">
            <div class="box">
                <h3>Facturé à :</h3>
                <p><strong><?= e($cmd['nom_client']) ?></strong></p>
                <p>WhatsApp : <?= e($cmd['whatsapp']) ?></p>
                <p>Ville : <?= e($cmd['ville']) ?></p>
                <p>Adresse : <?= nl2br(e($cmd['adresse_livraison'])) ?></p>
            </div>
            <div class="box">
                <h3>Informations Boutique :</h3>
                <p><strong><?= e($shopName) ?></strong></p>
                <p><?= e($params['contact_adresse'] ?? ($params['adresse_boutique'] ?? '')) ?></p>
                <p>Contact : <?= e($params['whatsapp'] ?? '') ?></p>
                <p>Email : <?= e($params['contact_email'] ?? ($params['email_boutique'] ?? '')) ?></p>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Description</th>
                    <th style="text-align: center;">Qté</th>
                    <th style="text-align: right;">Prix Unit.</th>
                    <th style="text-align: right;">Montant</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($items as $it): ?>
                <tr>
                    <td><?= e($it['nom_produit']) ?></td>
                    <td style="text-align: center;"><?= $it['quantite'] ?></td>
                    <td style="text-align: right;"><?= formatPrix($it['prix_unitaire']) ?></td>
                    <td style="text-align: right;"><?= formatPrix($it['sous_total']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="totals no-break">
            <div class="totals-row">
                <span>Sous-total</span>
                <span><?= formatPrix($cmd['sous_total']) ?></span>
            </div>
            <div class="totals-row">
                <span>Frais de livraison</span>
                <span><?= $cmd['frais_livraison'] > 0 ? formatPrix($cmd['frais_livraison']) : 'Offerte' ?></span>
            </div>
            <div class="totals-row grand-total">
                <span>Total TTC</span>
                <span><?= formatPrix($cmd['total']) ?></span>
            </div>
        </div>
        
        <div class="invoice-footer no-break">
            Merci pour votre confiance ! <br>
            <?= e($params['slogan'] ?? '') ?>
        </div>
    </div>

    <script>
        function changeFormat() {
            const f = document.getElementById('formatSelector').value;
            window.location.href = `facture.php?id=<?= $id ?>&format=${f}`;
        }

        function generatePDF() {
            const element = document.getElementById('invoiceContent');
            const formatSelected = '<?= $format ?>'; // a4, a5, a6
            
            // Format dimensions in mm
            const formatDimensions = {
                'a4': [210, 297],
                'a5': [148, 210],
                'a6': [105, 148]
            };
            
            const dims = formatDimensions[formatSelected] || formatDimensions['a4'];
            
            // Configuration html2pdf - margins tuned for each format
            const margins = {
                'a4': [10, 10, 10, 10],
                'a5': [8, 8, 8, 8],
                'a6': [5, 5, 5, 5]
            };

            var opt = {
                margin:       margins[formatSelected] || margins['a4'],
                filename:     `Facture_<?= e($cmd['numero_cmd']) ?>.pdf`,
                image:        { type: 'jpeg', quality: 0.98 },
                html2canvas:  { 
                    scale: 2, 
                    useCORS: true,
                    letterRendering: true,
                    logging: false
                },
                jsPDF:        { 
                    unit: 'mm', 
                    format: dims, 
                    orientation: 'portrait' 
                },
                pagebreak:    { 
                    mode: ['avoid-all', 'css', 'legacy'],
                    before: '.page-break-before',
                    after: '.page-break-after',
                    avoid: ['.no-break', '.header', '.details-grid', '.totals', '.invoice-footer', 'tr']
                }
            };

            // Temporarily fix styles for PDF
            const originalBoxShadow = element.style.boxShadow;
            const originalMargin = element.style.margin;
            const originalWidth = element.style.width;
            
            element.style.boxShadow = 'none';
            element.style.margin = '0';
            
            html2pdf().set(opt).from(element).save().then(() => {
                // Restore styles
                element.style.boxShadow = originalBoxShadow || '0 4px 6px rgba(0,0,0,0.1)';
                element.style.margin = originalMargin || '20px auto';
            });
        }
    </script>
</body>
</html>
