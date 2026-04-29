<?php
/**
 * ShopMax — Footer partagé
 */
$params = $params ?? getAllParams();
$nomBoutique = $params['nom_boutique'] ?? 'ShopMax';
$whatsapp = $params['whatsapp'] ?? '';
$email = $params['email_boutique'] ?? ($params['contact_email'] ?? '');
$adresse = $params['adresse_boutique'] ?? ($params['contact_adresse'] ?? '');
$horaires = $params['horaires'] ?? 'Lun-Sam : 8h - 20h';
$whatsappLink = 'https://wa.me/' . preg_replace('/[^0-9]/', '', $whatsapp);
?>
</main>

<!-- Footer -->
<footer class="site-footer">
    <div class="footer-wave">
        <svg viewBox="0 0 1440 120" preserveAspectRatio="none">
            <path d="M0,60 C360,120 720,0 1080,60 C1260,90 1380,80 1440,60 L1440,120 L0,120 Z" fill="currentColor"/>
        </svg>
    </div>
    
    <div class="container">
        <div class="footer-grid">
            <!-- About -->
            <div class="footer-col">
                <a href="<?= BASE_URL ?>/" class="footer-logo">
                    <span class="logo-icon"><i class="fas fa-bolt"></i></span>
                    <span><?= e($nomBoutique) ?></span>
                </a>
                <p class="footer-desc">Votre boutique en ligne de confiance. Découvrez notre sélection de produits de qualité à des prix imbattables.</p>
                <div class="footer-socials">
                    <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                    <a href="#" aria-label="Twitter"><i class="fa-brands fa-x-twitter"></i></a>
                    <a href="<?= $whatsappLink ?>" target="_blank" aria-label="WhatsApp"><i class="fab fa-whatsapp"></i></a>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="footer-col">
                <h4>Liens Rapides</h4>
                <ul>
                    <li><a href="<?= BASE_URL ?>/">Accueil</a></li>
                    <li><a href="<?= BASE_URL ?>/boutique.php">Boutique</a></li>
                    <li><a href="<?= BASE_URL ?>/panier.php">Mon Panier</a></li>
                    <li><a href="<?= BASE_URL ?>/contact.php">Contact</a></li>
                </ul>
            </div>

            <!-- Categories -->
            <div class="footer-col">
                <h4>Catégories</h4>
                <ul>
                    <?php foreach ($categories as $cat): ?>
                    <li><a href="<?= BASE_URL ?>/boutique.php?categorie=<?= $cat['id'] ?>"><?= e($cat['nom']) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Contact -->
            <div class="footer-col">
                <h4>Contact</h4>
                <ul class="footer-contact">
                    <li><i class="fas fa-map-marker-alt"></i> <?= e($adresse) ?></li>
                    <li><i class="fab fa-whatsapp"></i> <?= e($whatsapp) ?></li>
                    <li><i class="fas fa-envelope"></i> <?= e($email) ?></li>
                    <li><i class="fas fa-clock"></i> <?= e($horaires) ?></li>
                </ul>
            </div>
        </div>

        <div class="footer-bottom">
            <p>&copy; <?= date('Y') ?> <?= e($nomBoutique) ?>. Tous droits réservés.</p>
            <div class="footer-payments">
                <i class="fas fa-money-bill-wave"></i>
                <i class="fab fa-whatsapp"></i>
                <i class="fas fa-truck"></i>
            </div>
        </div>
    </div>
</footer>

<!-- WhatsApp Float Button -->
<a href="<?= $whatsappLink ?>" target="_blank" class="whatsapp-float" aria-label="Contacter via WhatsApp">
    <i class="fab fa-whatsapp"></i>
</a>

<!-- Toast Container -->
<div class="toast-container" id="toastContainer"></div>

<!-- Scripts -->
<script>
    const BASE_URL = '<?= BASE_URL ?>';
    const WHATSAPP_NUMBER = '<?= preg_replace('/[^0-9]/', '', $whatsapp) ?>';
    const FRAIS_LIVRAISON = <?= (int)($params['frais_livraison'] ?? 2000) ?>;
    const SEUIL_LIVRAISON_GRATUITE = <?= (int)($params['seuil_livraison_gratuite'] ?? 50000) ?>;
    const DEVISE = '<?= $params['devise'] ?? 'FCFA' ?>';
</script>
<script src="<?= BASE_URL ?>/js/app.js?v=<?= time() ?>"></script>
</body>
</html>
