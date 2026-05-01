<?php
/**
 * ShopMax — Page Panier & Commande
 */
$pageTitle = 'Mon Panier';
require_once __DIR__ . '/includes/header.php';

$cart = $_SESSION['cart'] ?? [];
$sousTotal = getCartTotal();
$fraisLivraison = (int)($params['frais_livraison'] ?? 2000);
$seuilGratuit = (int)($params['seuil_livraison_gratuite'] ?? 50000);
$frais = ($sousTotal >= $seuilGratuit) ? 0 : $fraisLivraison;
$total = $sousTotal + $frais;

// Check if we just placed an order
$confirmation = $_SESSION['order_confirmation'] ?? null;
if ($confirmation) { unset($_SESSION['order_confirmation']); }
?>

<div class="container">
    <div class="breadcrumb">
        <a href="<?= BASE_URL ?>/">Accueil</a>
        <span class="sep"><i class="fas fa-chevron-right"></i></span>
        <span>Mon Panier</span>
    </div>

    <?php if ($confirmation): ?>
    <!-- Confirmation -->
    <div class="confirmation-page">
        <div class="confirmation-icon"><i class="fas fa-check"></i></div>
        <h1>Commande Enregistrée !</h1>
        <p>Votre commande <strong><?= e($confirmation['numero_cmd']) ?></strong> a été enregistrée. Envoyez la facture sur WhatsApp pour confirmer.</p>
        <a href="<?= e($confirmation['whatsapp_url']) ?>" target="_blank" class="btn btn-primary btn-lg" style="margin-bottom:16px;background:#25D366;border-color:#25D366;">
            <i class="fab fa-whatsapp"></i> Envoyer sur WhatsApp
        </a>
        <br>
        <a href="<?= BASE_URL ?>/boutique" class="btn btn-secondary">Continuer mes achats</a>
    </div>

    <?php elseif (empty($cart)): ?>
    <!-- Empty Cart -->
    <div class="cart-empty" style="padding:80px 20px;">
        <i class="fas fa-shopping-bag"></i>
        <h3>Votre panier est vide</h3>
        <p>Explorez notre boutique pour trouver des produits qui vous plaisent !</p>
        <a href="<?= BASE_URL ?>/boutique" class="btn btn-primary btn-lg">
            <i class="fas fa-store"></i> Aller à la boutique
        </a>
    </div>

    <?php else: ?>
    <div class="cart-layout" id="cartLayout">
        <!-- Cart Table -->
        <div>
            <div class="cart-table">
                <table>
                    <thead>
                        <tr>
                            <th>Produit</th>
                            <th>Prix</th>
                            <th>Quantité</th>
                            <th>Sous-total</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="cartTableBody">
                        <?php foreach ($cart as $key => $item): ?>
                        <tr id="row-<?= e($key) ?>">
                            <td data-label="Produit">
                                <div class="cart-product">
                                    <?php $imgSrc = !empty($item['image']) ? BASE_URL . $item['image'] : BASE_URL . '/assets/images/no-image.png'; ?>
                                    <img src="<?= e($imgSrc) ?>" alt="<?= e($item['nom']) ?>"
                                         onerror="this.src='<?= BASE_URL ?>/assets/images/no-image.png'">
                                    <div class="cart-product-info">
                                        <h4><?= e($item['nom']) ?></h4>
                                        <?php if ($item['variante']): ?>
                                        <p><?= e($item['variante']) ?></p>
                                        <?php endif; ?>
                                        <p><?= e($item['marque'] ?? '') ?></p>
                                    </div>
                                </div>
                            </td>
                            <td data-label="Prix"><strong><?= formatPrix($item['prix']) ?></strong></td>
                            <td data-label="Quantité">
                                <div class="qty-controls" style="display:inline-flex;">
                                    <button class="qty-btn qty-minus" onclick="updateCartQty('<?= e($key) ?>', -1)"><i class="fas fa-minus"></i></button>
                                    <input type="number" class="qty-input" value="<?= $item['quantite'] ?>" min="1" max="<?= $item['stock'] ?>" id="qty-<?= e($key) ?>" onchange="setCartQty('<?= e($key) ?>', this.value)">
                                    <button class="qty-btn qty-plus" onclick="updateCartQty('<?= e($key) ?>', 1)"><i class="fas fa-plus"></i></button>
                                </div>
                            </td>
                            <td data-label="Sous-total"><strong class="item-subtotal"><?= formatPrix($item['prix'] * $item['quantite']) ?></strong></td>
                            <td class="td-action"><button class="cart-remove" onclick="removeCartItem('<?= e($key) ?>')" aria-label="Supprimer"><i class="fas fa-trash-alt"></i></button></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div style="margin-top:16px;">
                <a href="<?= BASE_URL ?>/boutique" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Continuer mes achats</a>
            </div>
        </div>

        <!-- Summary + Order Form -->
        <div class="cart-summary">
            <div class="cart-summary-card">
                <h3><i class="fas fa-receipt"></i> Récapitulatif</h3>
                <div class="summary-row"><span>Sous-total</span><span id="summSousTotal"><?= formatPrix($sousTotal) ?></span></div>
                <div class="summary-row"><span>Livraison</span><span id="summLivraison"><?= $frais == 0 ? '<span style="color:var(--success)">Gratuite</span>' : formatPrix($frais) ?></span></div>
                <div class="summary-row total"><span>Total TTC</span><span id="summTotal"><?= formatPrix($total) ?></span></div>
                <?php if ($sousTotal < $seuilGratuit): ?>
                <p style="font-size:0.82rem;color:var(--text-muted);margin-top:12px;"><i class="fas fa-info-circle"></i> Plus que <strong><?= formatPrix($seuilGratuit - $sousTotal) ?></strong> pour la livraison gratuite !</p>
                <?php endif; ?>
            </div>

            <div class="order-form">
                <h3><i class="fas fa-truck"></i> Informations de livraison</h3>
                <form id="orderForm" onsubmit="submitOrder(event)">
                    <div class="form-group">
                        <label>Nom et prénom <span class="required">*</span></label>
                        <input type="text" name="nom_client" class="form-control" required placeholder="Ex: Jean Dupont">
                    </div>
                    <div class="form-group">
                        <label>Numéro WhatsApp <span class="required">*</span></label>
                        <input type="tel" name="whatsapp" class="form-control" required placeholder="Ex: +237 6XX XXX XXX">
                    </div>
                    <div class="form-group">
                        <label>Adresse de livraison <span class="required">*</span></label>
                        <input type="text" name="adresse" class="form-control" required placeholder="Quartier, rue, repère...">
                    </div>
                    <div class="form-group">
                        <label>Ville <span class="required">*</span></label>
                        <input type="text" name="ville" class="form-control" required placeholder="Ex: Yaoundé">
                    </div>
                    <div class="form-group">
                        <label>Note / Message (optionnel)</label>
                        <textarea name="note" class="form-control" placeholder="Instructions particulières..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block btn-lg" id="orderSubmitBtn">
                        <i class="fab fa-whatsapp"></i> Passer la Commande
                    </button>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
async function updateCartQty(key, delta) {
    const input = document.getElementById('qty-' + key);
    let val = parseInt(input.value) + delta;
    val = Math.max(1, Math.min(val, parseInt(input.max) || 999));
    input.value = val;
    const data = await Cart.update(key, val);
    if (data?.success) location.reload();
}
async function setCartQty(key, val) {
    val = Math.max(1, parseInt(val) || 1);
    const data = await Cart.update(key, val);
    if (data?.success) location.reload();
}
async function removeCartItem(key) {
    const data = await Cart.remove(key);
    if (data?.success) location.reload();
}
async function submitOrder(e) {
    e.preventDefault();
    const btn = document.getElementById('orderSubmitBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Traitement...';
    try {
        const formData = new FormData(document.getElementById('orderForm'));
        const resp = await fetch(`${BASE_URL}/php/order_handler.php`, { method: 'POST', body: formData });
        const data = await resp.json();
        if (data.success) {
            Toast.show('Commande enregistrée ! Redirection vers WhatsApp...', 'success');
            setTimeout(() => { window.open(data.whatsapp_url, '_blank'); location.reload(); }, 1500);
        } else {
            Toast.show(data.message || 'Erreur', 'error');
            btn.disabled = false;
            btn.innerHTML = '<i class="fab fa-whatsapp"></i> Passer la Commande';
        }
    } catch (err) {
        Toast.show('Erreur de connexion', 'error');
        btn.disabled = false;
        btn.innerHTML = '<i class="fab fa-whatsapp"></i> Passer la Commande';
    }
}
window.refreshCartPage = function(data) { location.reload(); };
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
