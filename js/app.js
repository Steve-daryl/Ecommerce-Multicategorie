/**
 * ShopMax — Main Application JavaScript
 */
'use strict';

// ========== THEME MANAGEMENT ==========
const ThemeManager = {
    init() {
        const saved = localStorage.getItem('shopmax_theme') || 'light';
        this.set(saved, false);
        document.getElementById('themeToggle')?.addEventListener('click', () => this.toggle());
    },
    set(theme, save = true) {
        document.documentElement.setAttribute('data-theme', theme);
        const icon = document.getElementById('themeIcon');
        if (icon) { icon.className = theme === 'dark' ? 'fas fa-sun' : 'fas fa-moon'; }
        if (save) localStorage.setItem('shopmax_theme', theme);
    },
    toggle() {
        const current = document.documentElement.getAttribute('data-theme');
        this.set(current === 'dark' ? 'light' : 'dark');
    }
};

// ========== TOAST NOTIFICATIONS ==========
const Toast = {
    show(message, type = 'success', duration = 3000) {
        const container = document.getElementById('toastContainer');
        if (!container) return;
        const icons = { success: 'fa-check-circle', error: 'fa-exclamation-circle', info: 'fa-info-circle' };
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.innerHTML = `
            <i class="fas ${icons[type] || icons.info} toast-icon"></i>
            <span class="toast-message">${message}</span>
            <button class="toast-close" onclick="this.parentElement.classList.add('removing'); setTimeout(()=>this.parentElement.remove(),300)"><i class="fas fa-times"></i></button>
        `;
        container.appendChild(toast);
        setTimeout(() => { toast.classList.add('removing'); setTimeout(() => toast.remove(), 300); }, duration);
    }
};

// ========== CART MANAGEMENT ==========
const Cart = {
    async add(productId, quantity = 1, variantId = null) {
        try {
            const formData = new FormData();
            formData.append('action', 'add');
            formData.append('product_id', productId);
            formData.append('quantity', quantity);
            if (variantId) formData.append('variant_id', variantId);

            const resp = await fetch(`${BASE_URL}/php/cart_actions.php`, { method: 'POST', body: formData });
            const data = await resp.json();

            if (data.success) {
                Toast.show(data.message || 'Produit ajouté au panier !', 'success');
                this.updateBadge(data.cartCount);
                this.updateMiniCart(data.cartItems, data.cartTotal);
                this.showMiniCart();
            } else {
                Toast.show(data.message || 'Erreur lors de l\'ajout', 'error');
            }
        } catch (e) {
            Toast.show('Erreur de connexion', 'error');
        }
    },

    async update(cartKey, quantity) {
        try {
            const formData = new FormData();
            formData.append('action', 'update');
            formData.append('cart_key', cartKey);
            formData.append('quantity', quantity);
            const resp = await fetch(`${BASE_URL}/php/cart_actions.php`, { method: 'POST', body: formData });
            const data = await resp.json();
            if (data.success) {
                this.updateBadge(data.cartCount);
                if (typeof window.refreshCartPage === 'function') window.refreshCartPage(data);
            }
            return data;
        } catch (e) { Toast.show('Erreur de connexion', 'error'); }
    },

    async remove(cartKey) {
        try {
            const formData = new FormData();
            formData.append('action', 'remove');
            formData.append('cart_key', cartKey);
            const resp = await fetch(`${BASE_URL}/php/cart_actions.php`, { method: 'POST', body: formData });
            const data = await resp.json();
            if (data.success) {
                Toast.show('Produit retiré du panier', 'info');
                this.updateBadge(data.cartCount);
                if (typeof window.refreshCartPage === 'function') window.refreshCartPage(data);
            }
            return data;
        } catch (e) { Toast.show('Erreur de connexion', 'error'); }
    },

    updateBadge(count) {
        const badge = document.getElementById('cartBadge');
        if (badge) {
            badge.textContent = count;
            badge.style.display = count > 0 ? 'flex' : 'none';
            badge.classList.add('pulse');
            setTimeout(() => badge.classList.remove('pulse'), 300);
        }
    },

    updateMiniCart(items, total) {
        const container = document.getElementById('miniCartItems');
        const totalEl = document.getElementById('miniCartTotal');
        if (!container) return;

        if (!items || items.length === 0) {
            container.innerHTML = '<div class="mini-cart-empty"><i class="fas fa-shopping-bag"></i><p>Votre panier est vide</p></div>';
        } else {
            container.innerHTML = items.map(item => `
                <div class="mini-cart-item">
                    <img src="${BASE_URL}${item.image}" alt="${item.nom}" onerror="this.src='${BASE_URL}/assets/images/no-image.png'">
                    <div class="mini-cart-item-info">
                        <h5>${item.nom}</h5>
                        ${item.variante ? `<span class="qty">${item.variante}</span>` : ''}
                        <div class="price">${item.quantite} × ${formatPrice(item.prix)}</div>
                    </div>
                    <button class="mini-cart-item-remove" onclick="Cart.remove('${item.key}')"><i class="fas fa-trash-alt"></i></button>
                </div>
            `).join('');
        }
        if (totalEl) totalEl.textContent = formatPrice(total || 0);
    },

    showMiniCart() {
        document.getElementById('miniCart')?.classList.add('active');
        document.getElementById('miniCartOverlay')?.classList.add('active');
        document.body.style.overflow = 'hidden';
    },

    hideMiniCart() {
        document.getElementById('miniCart')?.classList.remove('active');
        document.getElementById('miniCartOverlay')?.classList.remove('active');
        document.body.style.overflow = '';
    }
};

// ========== UTILITY ==========
function formatPrice(price) {
    return new Intl.NumberFormat('fr-FR').format(price) + ' ' + (typeof DEVISE !== 'undefined' ? DEVISE : 'FCFA');
}

// ========== HEADER ==========
const Header = {
    init() {
        // Scroll effect
        window.addEventListener('scroll', () => {
            document.getElementById('siteHeader')?.classList.toggle('scrolled', window.scrollY > 50);
        });
        // Mobile menu
        document.getElementById('mobileToggle')?.addEventListener('click', () => {
            document.getElementById('mainNav')?.classList.toggle('active');
            document.getElementById('mobileOverlay')?.classList.toggle('active');
        });
        document.getElementById('mobileOverlay')?.addEventListener('click', () => {
            document.getElementById('mainNav')?.classList.remove('active');
            document.getElementById('mobileOverlay')?.classList.remove('active');
        });
        // Search
        document.getElementById('searchToggle')?.addEventListener('click', () => {
            document.getElementById('searchOverlay')?.classList.toggle('active');
            document.getElementById('searchInput')?.focus();
        });
        document.getElementById('searchClose')?.addEventListener('click', () => {
            document.getElementById('searchOverlay')?.classList.remove('active');
        });
        // Mini cart
        document.getElementById('cartBtn')?.addEventListener('click', (e) => {
            if (window.innerWidth > 768) { e.preventDefault(); Cart.showMiniCart(); }
        });
        document.getElementById('miniCartClose')?.addEventListener('click', Cart.hideMiniCart);
        document.getElementById('miniCartOverlay')?.addEventListener('click', Cart.hideMiniCart);
        // Dropdown on mobile
        document.querySelectorAll('.dropdown-toggle').forEach(el => {
            el.addEventListener('click', (e) => {
                if (window.innerWidth <= 768) { e.preventDefault(); el.closest('.nav-dropdown')?.classList.toggle('open'); }
            });
        });
    }
};

// ========== PRODUCT DETAIL ==========
const ProductDetail = {
    init() {
        // Gallery thumbnails
        document.querySelectorAll('.gallery-thumb').forEach(thumb => {
            thumb.addEventListener('click', () => {
                document.querySelectorAll('.gallery-thumb').forEach(t => t.classList.remove('active'));
                thumb.classList.add('active');
                const mainImg = document.querySelector('.gallery-main img');
                if (mainImg) mainImg.src = thumb.querySelector('img').src;
            });
        });
        // Quantity controls
        document.querySelectorAll('.qty-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const input = btn.closest('.qty-controls').querySelector('.qty-input');
                const max = parseInt(input.max) || 999;
                let val = parseInt(input.value) || 1;
                if (btn.classList.contains('qty-minus')) val = Math.max(1, val - 1);
                else val = Math.min(max, val + 1);
                input.value = val;
                input.dispatchEvent(new Event('change'));
            });
        });
        // Tabs
        document.querySelectorAll('.tab-header').forEach(header => {
            header.addEventListener('click', () => {
                const target = header.dataset.tab;
                document.querySelectorAll('.tab-header').forEach(h => h.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
                header.classList.add('active');
                document.getElementById(target)?.classList.add('active');
            });
        });
        // Variant selector
        document.querySelectorAll('.variant-option:not(.disabled)').forEach(opt => {
            opt.addEventListener('click', () => {
                document.querySelectorAll('.variant-option').forEach(o => o.classList.remove('active'));
                opt.classList.add('active');
                
                const rawPrice = opt.dataset.prix;
                let price = parseFloat(rawPrice);
                const stock = parseInt(opt.dataset.stock) || 0;
                const varId = opt.dataset.id;
                
                // Toujours utiliser le prix de la variante défini, même si 0
                const priceEl = document.getElementById('productPrice') || document.querySelector('.price-current');
                if (priceEl && !isNaN(price)) {
                    priceEl.textContent = formatPrice(price);
                    
                    // Hide old price/badge when variant is selected
                    document.querySelectorAll('.price-old, .price-badge').forEach(el => el.style.display = 'none');
                }
                
                const stockInfo = document.querySelector('.stock-info');
                if (stockInfo) {
                    if (stock > 0) {
                        stockInfo.className = 'stock-info in-stock';
                        stockInfo.innerHTML = `<i class="fas fa-check-circle"></i> En stock (${stock} disponible${stock > 1 ? 's' : ''})`;
                    } else {
                        stockInfo.className = 'stock-info out-of-stock';
                        stockInfo.innerHTML = '<i class="fas fa-times-circle"></i> Rupture de stock';
                    }
                }
                
                const varInput = document.getElementById('selectedVariantId');
                if (varInput) varInput.value = varId;
                
                const qtyInput = document.getElementById('qtyInput');
                if (qtyInput) {
                    qtyInput.max = stock;
                    if (parseInt(qtyInput.value) > stock) qtyInput.value = stock;
                }
            });
        });
    }
};

// ========== SHOP FILTERS ==========
const ShopFilters = {
    init() {
        // Filter group toggle
        document.querySelectorAll('.filter-group h4').forEach(h => {
            h.addEventListener('click', () => h.closest('.filter-group')?.classList.toggle('collapsed'));
        });
        // Mobile filter button
        document.querySelector('.mobile-filter-btn')?.addEventListener('click', () => {
            document.querySelector('.filters-sidebar')?.classList.toggle('active');
            document.getElementById('mobileOverlay')?.classList.toggle('active');
        });
        // Reset filters
        document.getElementById('resetFilters')?.addEventListener('click', () => {
            window.location.href = `${BASE_URL}/boutique.php`;
        });
        
        // Desktop filter toggle
        const toggleBtn = document.getElementById('toggleFiltersBtn');
        const shopLayout = document.querySelector('.shop-layout');
        if (toggleBtn && shopLayout) {
            // Load state
            if (localStorage.getItem('shopFiltersHidden') === 'true') {
                shopLayout.classList.add('filters-hidden');
                toggleBtn.innerHTML = '<i class="fas fa-eye"></i> <span class="text">Afficher Filtres</span>';
            }
            
            toggleBtn.addEventListener('click', () => {
                shopLayout.classList.toggle('filters-hidden');
                const isHidden = shopLayout.classList.contains('filters-hidden');
                localStorage.setItem('shopFiltersHidden', isHidden);
                
                if (isHidden) {
                    toggleBtn.innerHTML = '<i class="fas fa-eye"></i> <span class="text">Afficher Filtres</span>';
                } else {
                    toggleBtn.innerHTML = '<i class="fas fa-eye-slash"></i> <span class="text">Masquer Filtres</span>';
                }
            });
        }
    }
};

// ========== INIT ==========
document.addEventListener('DOMContentLoaded', () => {
    ThemeManager.init();
    Header.init();
    ProductDetail.init();
    ShopFilters.init();
    // Scroll reveal
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(e => { if (e.isIntersecting) { e.target.classList.add('revealed'); observer.unobserve(e.target); } });
    }, { threshold: 0.1 });
    document.querySelectorAll('.section').forEach(s => { s.classList.add('reveal'); observer.observe(s); });
});
