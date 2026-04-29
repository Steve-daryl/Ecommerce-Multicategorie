<?php
/**
 * ShopMax — Page Contact
 */
$pageTitle = 'Contact';
require_once __DIR__ . '/includes/header.php';
$whatsappLink = 'https://wa.me/' . preg_replace('/[^0-9]/', '', $params['whatsapp'] ?? '');
?>

<div class="container">
    <div class="breadcrumb">
        <a href="<?= BASE_URL ?>/">Accueil</a>
        <span class="sep"><i class="fas fa-chevron-right"></i></span>
        <span>Contact</span>
    </div>

    <div class="section-header" style="padding-top:20px;">
        <span class="section-tag">Contactez-nous</span>
        <h2>Restons en Contact</h2>
        <p>Une question, une suggestion ? N'hésitez pas à nous écrire, nous répondons rapidement !</p>
    </div>

    <!-- Contact Info Cards -->
    <div class="contact-info-cards" style="margin-bottom:40px;">
        <div class="contact-info-card">
            <div class="icon"><i class="fas fa-map-marker-alt"></i></div>
            <h4>Adresse</h4>
            <p><?= e($params['contact_adresse'] ?? ($params['adresse_boutique'] ?? 'Bafoussam, Cameroun')) ?></p>
        </div>
        <div class="contact-info-card">
            <div class="icon"><i class="fab fa-whatsapp"></i></div>
            <h4>WhatsApp</h4>
            <p><?= e($params['whatsapp'] ?? '') ?></p>
        </div>
        <div class="contact-info-card">
            <div class="icon"><i class="fas fa-envelope"></i></div>
            <h4>Email</h4>
            <p><?= e($params['contact_email'] ?? ($params['email_boutique'] ?? '')) ?></p>
        </div>
        <div class="contact-info-card">
            <div class="icon"><i class="fas fa-clock"></i></div>
            <h4>Horaires</h4>
            <p><?= e($params['horaires'] ?? 'Lun-Sam : 8h - 20h') ?></p>
        </div>
    </div>

    <div class="contact-grid">
        <!-- Contact Action -->
        <div class="contact-form-card" style="display: flex; align-items: center; justify-content: center; text-align: center; padding: 60px 20px;">
            <div>
                <div class="icon" style="font-size: 3rem; color: #25D366; margin-bottom: 20px;"><i class="fab fa-whatsapp"></i></div>
                <h2 style="margin-bottom: 16px;">Contactez-nous en direct</h2>
                <p style="color:var(--text-muted); margin-bottom:30px; font-size: 1.1rem;">Pour toute demande, veuillez nous contacter directement via WhatsApp. Notre équipe vous répondra immédiatement !</p>
                <a href="<?= $whatsappLink ?>" target="_blank" class="btn btn-lg" style="background:#25D366;color:#fff;border-color:#25D366; font-size: 1.1rem; padding: 14px 30px; border-radius: 50px;">
                    <i class="fab fa-whatsapp" style="font-size: 1.3rem;"></i> Écrire sur WhatsApp
                </a>
            </div>
        </div>

        <!-- Map -->
        <div>
            <div class="contact-map">
                <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d63539.5!2d10.39!3d5.47!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x105f3c9b7a1b7b8f%3A0x8b8b8b8b8b8b8b8b!2sBafoussam%2C%20Cameroun!5e0!3m2!1sfr!2scm!4v1709913600000" allowfullscreen loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
            </div>
        </div>
    </div>
</div>



<?php require_once __DIR__ . '/includes/footer.php'; ?>
