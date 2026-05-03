# 🛒 ShopMax — Plateforme E-commerce Multi-catégorie

**ShopMax** est une solution e-commerce complète, moderne et performante, conçue pour gérer une boutique en ligne multi-catégorie avec une expérience utilisateur premium et un assistant intelligent intégré.

![Banner](https://images.unsplash.com/photo-1557821552-17105176677c?ixlib=rb-4.0.3&auto=format&fit=crop&w=1200&q=80)

## 🚀 Fonctionnalités Clés

### 💻 Interface Client
- **Boutique Intuitive** : Navigation par catégories et marques avec filtres dynamiques.
- **Gestion de Panier** : Expérience d'achat fluide et rapide.
- **Recherche Avancée** : Trouvez facilement vos produits.
- **Responsive Design** : Optimisé pour mobiles, tablettes et ordinateurs.

### 📊 Dashboard Administrateur
- **Gestion Complète** : Contrôle total sur les produits, variantes, stocks, catégories et marques.
- **Statistiques en Temps Réel** : Tableaux de bord interactifs avec **Chart.js** pour suivre vos ventes et performances.
- **Mode Sombre (Dark Mode)** : Interface moderne et confortable, avec persistance des préférences.
- **Sécurité** : Accès restreint et protection des données sensibles.

### 🤖 Assistant IA (Gemini Integration)
- **Analyse Commerciale** : L'IA a accès aux données de votre boutique (CA, stocks, commandes) pour vous conseiller.
- **Polyvalence** : Répond à vos questions d'ordre général ou spécifiques au projet.
- **Interface Glassmorphism** : Un chat flottant élégant de style "WhatsApp".
- **Résilience** : Système de secours automatique et gestion intelligente des quotas d'API.

## 🛠️ Stack Technique
- **Backend** : PHP 8.x (Compatible XAMPP/WAMP)
- **Base de données** : MySQL
- **Frontend** : Vanilla HTML5, CSS3 (Modern UI), JavaScript (ES6+)
- **Bibliothèques** : FontAwesome 6, Google Fonts (Inter), Chart.js
- **IA** : Google Gemini API (v1beta)

## ⚙️ Installation

1. **Cloner le dépôt** :
   ```bash
   git clone https://github.com/Steve-daryl/Ecommerce-Multicategorie.git
   ```

2. **Configuration de la Base de Données** :
   - Importez le fichier `.sql` (si présent) dans votre gestionnaire PHPMyAdmin.
   - Configurez vos accès dans `includes/config.php`.

3. **Configuration de l'IA (Important)** :
   - Créez un fichier `admin/ai_config.php` (ce fichier est ignoré par Git pour votre sécurité).
   - Ajoutez-y votre clé d'API Gemini :
     ```php
     <?php
     $ai_api_key = "VOTRE_CLE_API_ICI";
     ```

4. **Lancer le projet** :
   - Placez le dossier dans `htdocs` (XAMPP) ou `www` (WAMP).
   - Accédez au site via `http://localhost/Ecommerce-Multicategorie/`.

## 🔒 Sécurité & Confidentialité
Le projet inclut une protection automatique des clés d'API via `.gitignore`. Veillez à ne jamais uploader votre fichier `ai_config.php` sur un dépôt public.

---
*Développé avec ❤️ pour une expérience e-commerce moderne.*
