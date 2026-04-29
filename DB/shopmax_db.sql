-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : jeu. 23 avr. 2026 à 13:39
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `shopmax_db`
--

-- --------------------------------------------------------

--
-- Structure de la table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('super_admin','admin') NOT NULL DEFAULT 'admin',
  `permissions` text DEFAULT NULL,
  `actif` tinyint(1) NOT NULL DEFAULT 1,
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `admins`
--

INSERT INTO `admins` (`id`, `username`, `email`, `password_hash`, `role`, `permissions`, `actif`, `last_login`, `created_at`) VALUES
(1, 'admin', 'admin@shopmax.com', '$2y$10$L8NqMt9r1DosDmD6Ij.PSe9M6Ry7aHtt3UGTCDcTaJGAWvRvmqG0u', 'super_admin', '[]', 1, '2026-04-21 04:10:54', '2026-04-21 11:09:58'),
(2, 'steve', 'steve@gmail.com', '$2y$10$L8NqMt9r1DosDmD6Ij.PSe9M6Ry7aHtt3UGTCDcTaJGAWvRvmqG0u', 'super_admin', '[]', 1, NULL, '2026-04-21 11:09:58');

-- --------------------------------------------------------

--
-- Structure de la table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `nom` varchar(150) NOT NULL,
  `slug` varchar(150) NOT NULL,
  `icone` varchar(100) DEFAULT '<i class="fas fa-box"></i>',
  `description` text DEFAULT NULL,
  `ordre_affichage` int(11) NOT NULL DEFAULT 1,
  `actif` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `categories`
--

INSERT INTO `categories` (`id`, `nom`, `slug`, `icone`, `description`, `ordre_affichage`, `actif`, `created_at`, `updated_at`) VALUES
(1, 'Smartphones', 'smartphones', '📱', 'Téléphones portables et accessoires', 1, 1, '2026-04-21 11:09:58', '2026-04-21 11:11:39'),
(2, 'Ordinateurs', 'ordinateurs', '<i class=\"fas fa-laptop\"></i>', 'Ordinateurs portables et de bureau', 2, 1, '2026-04-21 11:09:58', '2026-04-21 11:09:58'),
(3, 'Électronique', 'electronique', '<i class=\"fas fa-tv\"></i>', 'TV, audio, gadgets et accessoires', 3, 1, '2026-04-21 11:09:58', '2026-04-21 11:09:58'),
(4, 'Mode & Vêtements', 'mode-vetements', '<i class=\"fas fa-tshirt\"></i>', 'Vêtements, chaussures et accessoires', 4, 1, '2026-04-21 11:09:58', '2026-04-21 11:09:58'),
(5, 'Maison & Cuisine', 'maison-cuisine', '<i class=\"fas fa-home\"></i>', 'Électroménager et décoration', 5, 1, '2026-04-21 11:09:58', '2026-04-21 11:09:58'),
(6, 'Sport & Loisirs', 'sport-loisirs', '<i class=\"fas fa-futbol\"></i>', 'Équipements sportifs et loisirs', 6, 1, '2026-04-21 11:09:58', '2026-04-21 11:09:58');

-- --------------------------------------------------------

--
-- Structure de la table `clients`
--

CREATE TABLE `clients` (
  `id` int(11) NOT NULL,
  `nom` varchar(200) NOT NULL,
  `whatsapp` varchar(30) DEFAULT NULL,
  `email` varchar(200) DEFAULT NULL,
  `ville` varchar(100) DEFAULT NULL,
  `adresse` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `clients`
--

INSERT INTO `clients` (`id`, `nom`, `whatsapp`, `email`, `ville`, `adresse`, `created_at`) VALUES
(1, 'Jean Dupont', '+237690000001', 'jean@email.com', 'Douala', NULL, '2026-04-21 11:09:58'),
(2, 'Marie Kamga', '+237690000002', 'marie@email.com', 'Yaoundé', NULL, '2026-04-21 11:09:58'),
(3, 'Paul Tchoumi', '+237690000003', 'paul@email.com', 'Bafoussam', NULL, '2026-04-21 11:09:58');

-- --------------------------------------------------------

--
-- Structure de la table `commandes`
--

CREATE TABLE `commandes` (
  `id` int(11) NOT NULL,
  `numero_cmd` varchar(50) NOT NULL,
  `nom_client` varchar(200) NOT NULL,
  `whatsapp` varchar(30) NOT NULL,
  `adresse_livraison` text NOT NULL,
  `ville` varchar(100) NOT NULL DEFAULT '',
  `note_client` text DEFAULT NULL,
  `sous_total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `frais_livraison` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `statut` enum('en_attente','confirmee','en_livraison','livree','annulee') NOT NULL DEFAULT 'en_attente',
  `facture_texte` text DEFAULT NULL,
  `date_commande` datetime NOT NULL DEFAULT current_timestamp(),
  `date_maj` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `commandes`
--

INSERT INTO `commandes` (`id`, `numero_cmd`, `nom_client`, `whatsapp`, `adresse_livraison`, `ville`, `note_client`, `sous_total`, `frais_livraison`, `total`, `statut`, `facture_texte`, `date_commande`, `date_maj`) VALUES
(1, 'CMD-2026-0001', 'Jean Dupont', '+237690000001', 'Quartier Bonamoussadi, Rue 123', 'Douala', 'Livraison le matin SVP', 699000.00, 0.00, 699000.00, 'livree', '🧾 Facture CMD-2026-0001', '2026-04-01 04:09:58', NULL),
(2, 'CMD-2026-0002', 'Marie Kamga', '+237690000002', 'Quartier Bastos, Avenue Kennedy', 'Yaoundé', NULL, 155000.00, 2000.00, 157000.00, 'livree', '🧾 Facture CMD-2026-0002', '2026-04-06 04:09:58', NULL),
(3, 'CMD-2026-0003', 'Paul Tchoumi', '+237690000003', 'Centre Commercial, étage 2', 'Bafoussam', 'Emballage cadeau', 799000.00, 0.00, 799000.00, 'livree', '🧾 Facture CMD-2026-0003', '2026-04-09 04:09:58', NULL),
(4, 'CMD-2026-0004', 'Jean Dupont', '+237690000001', 'Quartier Bonamoussadi', 'Douala', NULL, 72000.00, 2000.00, 74000.00, 'en_livraison', '🧾 Facture CMD-2026-0004', '2026-04-16 04:09:58', NULL),
(5, 'CMD-2026-0005', 'Amina Bello', '+237690000004', 'Quartier Makepe, Douala', 'Douala', 'Appeler avant livraison', 399000.00, 0.00, 399000.00, 'confirmee', '🧾 Facture CMD-2026-0005', '2026-04-18 04:09:58', NULL),
(6, 'CMD-2026-0006', 'Eric Ngwa', '+237690000005', 'Cité SIC, Bâtiment 5', 'Yaoundé', NULL, 250000.00, 0.00, 250000.00, 'en_attente', '🧾 Facture CMD-2026-0006', '2026-04-20 04:09:58', NULL),
(7, 'CMD-2026-0007', 'Carine Fomba', '+237690000006', 'Rue du marché central', 'Bafoussam', 'Produit urgent', 350000.00, 0.00, 350000.00, 'en_attente', '🧾 Facture CMD-2026-0007', '2026-04-21 04:09:58', NULL),
(8, 'CMD-2026-0008', 'Marie Kamga', '+237690000002', 'Quartier Bastos', 'Yaoundé', NULL, 85000.00, 2000.00, 87000.00, 'livree', '🧾 Facture CMD-2026-0008', '2026-03-27 04:09:58', NULL),
(9, 'CMD-2026-0009', 'Patrick Ndonko', '+237690000007', 'Akwa, Boulevard de la Liberté', 'Douala', NULL, 420000.00, 0.00, 420000.00, 'livree', '🧾 Facture CMD-2026-0009', '2026-03-22 04:09:58', NULL),
(10, 'CMD-2026-0010', 'Sophie Talla', '+237690000008', 'Quartier Mvog-Ada', 'Yaoundé', 'Contre remboursement', 180000.00, 0.00, 180000.00, 'annulee', '🧾 Facture CMD-2026-0010', '2026-04-13 04:09:58', NULL),
(11, 'CMD-2026-0011', 'daryl', '+237 699168894', 'Rue de la paix', 'Bafoussam', 'FRAGILE', 155000.00, 2000.00, 157000.00, 'en_attente', '🧾 *FACTURE DE COMMANDE*\n━━━━━━━━━━━━━━━━━━━━━━━━\n📌 N° Commande : CMD-2026-0011\n📅 Date : 22/04/2026 à 06h41\n👤 Client : daryl\n📞 WhatsApp : +237 699168894\n📍 Adresse : Rue de la paix, Bafoussam\n📝 Note : FRAGILE\n━━━━━━━━━━━━━━━━━━━━━━━━\n🛒 *DÉTAIL DES PRODUITS :*\n\n• Xiaomi Redmi Note 13 Pro x1 → 155 000 FCFA\n\n━━━━━━━━━━━━━━━━━━━━━━━━\n💰 Sous-total  : 155 000 FCFA\n🚚 Livraison   : 2 000 FCFA\n✅ *TOTAL TTC  : 157 000 FCFA*\n\nMerci pour votre commande sur ShopMax ! 🙏', '2026-04-21 21:41:34', '2026-04-21 21:41:34'),
(12, 'CMD-2026-0012', 'daryl', '+1 699168894', 'eveche', 'Bafoussam', '', 50.00, 2000.00, 2050.00, 'livree', '🧾 *FACTURE DE COMMANDE*\n━━━━━━━━━━━━━━━━━━━━━━━━\n📌 N° Commande : CMD-2026-0012\n📅 Date : 22/04/2026 à 12h17\n👤 Client : daryl\n📞 WhatsApp : +1 699168894\n📍 Adresse : eveche, Bafoussam\n━━━━━━━━━━━━━━━━━━━━━━━━\n🛒 *DÉTAIL DES PRODUITS :*\n\n• DARYL (256 Go - Gris Titane) x1 → 50 FCFA\n\n━━━━━━━━━━━━━━━━━━━━━━━━\n💰 Sous-total  : 50 FCFA\n🚚 Livraison   : 2 000 FCFA\n✅ *TOTAL TTC  : 2 050 FCFA*\n\nMerci pour votre commande sur ShopMax ! 🙏', '2026-04-22 03:17:31', '2026-04-22 11:10:02'),
(13, 'CMD-2026-0013', 'Doloremque tempor fu', '+237600000000', 'eveche', 'Bafoussam', '', 3196000.00, 0.00, 3196000.00, 'livree', '🧾 *FACTURE DE COMMANDE*\n━━━━━━━━━━━━━━━━━━━━━━━━\n📌 N° Commande : CMD-2026-0013\n📅 Date : 22/04/2026 à 20h08\n👤 Client : Doloremque tempor fu\n📞 WhatsApp : +237600000000\n📍 Adresse : eveche, Bafoussam\n━━━━━━━━━━━━━━━━━━━━━━━━\n🛒 *DÉTAIL DES PRODUITS :*\n\n• MacBook Air M2 x4 → 3 196 000 FCFA\n\n━━━━━━━━━━━━━━━━━━━━━━━━\n💰 Sous-total  : 3 196 000 FCFA\n🚚 Livraison   : GRATUITE\n✅ *TOTAL TTC  : 3 196 000 FCFA*\n\nMerci pour votre commande sur ShopMax ! 🙏', '2026-04-22 11:08:53', '2026-04-22 11:09:48');

-- --------------------------------------------------------

--
-- Structure de la table `commande_items`
--

CREATE TABLE `commande_items` (
  `id` int(11) NOT NULL,
  `commande_id` int(11) NOT NULL,
  `produit_id` int(11) DEFAULT NULL,
  `variante_id` int(11) DEFAULT NULL,
  `nom_produit` varchar(255) NOT NULL,
  `prix_unitaire` decimal(12,2) NOT NULL,
  `quantite` int(11) NOT NULL DEFAULT 1,
  `sous_total` decimal(12,2) GENERATED ALWAYS AS (`prix_unitaire` * `quantite`) STORED
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `commande_items`
--

INSERT INTO `commande_items` (`id`, `commande_id`, `produit_id`, `variante_id`, `nom_produit`, `prix_unitaire`, `quantite`) VALUES
(1, 1, 1, 1, 'Samsung Galaxy S24 Ultra — 256 Go Noir', 699000.00, 1),
(2, 2, 3, NULL, 'Xiaomi Redmi Note 13 Pro', 155000.00, 1),
(3, 3, 4, NULL, 'MacBook Air M2', 799000.00, 1),
(4, 4, 9, 9, 'Nike Air Max 90 — Taille 40 Blanc/Noir', 72000.00, 1),
(5, 5, 7, NULL, 'Samsung Smart TV 55\" QLED', 399000.00, 1),
(6, 6, 8, NULL, 'Sony WH-1000XM5', 250000.00, 1),
(7, 7, 6, NULL, 'Lenovo IdeaPad Slim 3', 350000.00, 1),
(8, 8, 9, 10, 'Nike Air Max 90 — Taille 41 Blanc/Noir', 72000.00, 1),
(9, 8, 3, NULL, 'Xiaomi Redmi Note 13 Pro', 155000.00, 1),
(10, 9, 5, NULL, 'HP Pavilion 15', 420000.00, 1),
(11, 10, 3, NULL, 'Xiaomi Redmi Note 13 Pro', 180000.00, 1),
(12, 11, 3, NULL, 'Xiaomi Redmi Note 13 Pro', 155000.00, 1),
(13, 12, 1, 31, 'DARYL — 256 Go - Gris Titane', 50.00, 1),
(14, 13, 4, NULL, 'MacBook Air M2', 799000.00, 4);

-- --------------------------------------------------------

--
-- Structure de la table `image_produit`
--

CREATE TABLE `image_produit` (
  `id` int(11) NOT NULL,
  `produit_id` int(11) NOT NULL,
  `chemin` varchar(500) NOT NULL,
  `alt_text` varchar(255) DEFAULT NULL,
  `ordre` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `image_produit`
--

INSERT INTO `image_produit` (`id`, `produit_id`, `chemin`, `alt_text`, `ordre`, `created_at`) VALUES
(2, 1, '/assets/images/products/1776833906_0_CulturejaponaisesamouraMangacinqafficheimpressionHDT-shirtmural.jpg', NULL, 0, '2026-04-22 04:58:26'),
(3, 1, '/assets/images/products/1776833906_1_JujutsukaisenNeonaiartworkbyARTIFLUX.jpg', NULL, 1, '2026-04-22 04:58:26'),
(4, 1, '/assets/images/products/1776833906_2_download1.jpg', NULL, 2, '2026-04-22 04:58:26'),
(5, 1, '/assets/images/products/1776833906_3_download2.jpg', NULL, 3, '2026-04-22 04:58:26');

-- --------------------------------------------------------

--
-- Structure de la table `marques`
--

CREATE TABLE `marques` (
  `id` int(11) NOT NULL,
  `nom` varchar(150) NOT NULL,
  `slug` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `actif` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `marques`
--

INSERT INTO `marques` (`id`, `nom`, `slug`, `description`, `actif`, `created_at`, `updated_at`) VALUES
(1, 'Samsung', 'samsung', 'Électronique et smartphones Samsung', 1, '2026-04-21 11:09:58', '2026-04-21 11:09:58'),
(2, 'Apple', 'apple', 'Produits Apple - iPhone, MacBook, iPad', 1, '2026-04-21 11:09:58', '2026-04-21 11:09:58'),
(3, 'Xiaomi', 'xiaomi', 'Smartphones et gadgets Xiaomi', 1, '2026-04-21 11:09:58', '2026-04-21 11:09:58'),
(4, 'HP', 'hp', 'Ordinateurs et imprimantes HP', 1, '2026-04-21 11:09:58', '2026-04-21 11:09:58'),
(5, 'Nike', 'nike', 'Vêtements et chaussures de sport Nike', 1, '2026-04-21 11:09:58', '2026-04-21 11:09:58'),
(6, 'LG', 'lg', 'Électronique et électroménager LG', 1, '2026-04-21 11:09:58', '2026-04-21 11:09:58'),
(7, 'Sony', 'sony', 'Audio, TV et consoles Sony', 1, '2026-04-21 11:09:58', '2026-04-21 11:09:58'),
(8, 'Lenovo', 'lenovo', 'Ordinateurs portables Lenovo', 1, '2026-04-21 11:09:58', '2026-04-21 11:09:58');

-- --------------------------------------------------------

--
-- Structure de la table `messages_contact`
--

CREATE TABLE `messages_contact` (
  `id` int(11) NOT NULL,
  `nom` varchar(200) NOT NULL,
  `email` varchar(200) NOT NULL,
  `objet` varchar(255) DEFAULT NULL,
  `message` text NOT NULL,
  `ip_address` varchar(50) DEFAULT NULL,
  `lu` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `parametres`
--

CREATE TABLE `parametres` (
  `id` int(11) NOT NULL,
  `cle` varchar(100) NOT NULL,
  `valeur` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `parametres`
--

INSERT INTO `parametres` (`id`, `cle`, `valeur`) VALUES
(1, 'nom_boutique', 'ShopMax'),
(2, 'slogan', 'Votre boutique en ligne premium'),
(3, 'devise', 'FCFA'),
(4, 'whatsapp', '+237699168894'),
(5, 'frais_livraison', '2000'),
(6, 'seuil_livraison_gratuite', '500000'),
(7, 'contact_email', 'contact@shopmax.com'),
(8, 'contact_adresse', 'Bafoussam, Cameroun'),
(9, 'adresse_boutique', 'Douala, Cameroun'),
(10, 'email_boutique', 'contact@shopmax.com'),
(11, 'horaires', 'Lun-Sam : 8h - 20h'),
(12, 'produits_par_page', '12');

-- --------------------------------------------------------

--
-- Structure de la table `produits`
--

CREATE TABLE `produits` (
  `id` int(11) NOT NULL,
  `nom` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `caracteristiques` text DEFAULT NULL,
  `prix` decimal(12,2) NOT NULL DEFAULT 0.00,
  `prix_promo` decimal(12,2) DEFAULT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `stock_alerte` int(11) NOT NULL DEFAULT 5,
  `categorie_id` int(11) DEFAULT NULL,
  `marque_id` int(11) DEFAULT NULL,
  `actif` tinyint(1) NOT NULL DEFAULT 1,
  `vedette` tinyint(1) NOT NULL DEFAULT 0,
  `a_variants` tinyint(1) NOT NULL DEFAULT 0,
  `supprime` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `produits`
--

INSERT INTO `produits` (`id`, `nom`, `slug`, `description`, `caracteristiques`, `prix`, `prix_promo`, `stock`, `stock_alerte`, `categorie_id`, `marque_id`, `actif`, `vedette`, `a_variants`, `supprime`, `created_at`, `updated_at`) VALUES
(1, 'DARYL', 'daryl', 'Tres bon produit de qualite !', 'Nom: DARYL\r\nPrenom: STEVE\r\nAGE: 20', 100.00, 25.00, 25, 5, 1, 1, 1, 1, 1, 0, '2026-04-21 11:09:58', '2026-04-22 18:12:08'),
(2, 'iPhone 15 Pro Max', 'iphone-15-pro-max', 'Le meilleur iPhone jamais créé, avec puce A17 Pro', 'Écran: 6.7\" Super Retina XDR OLED\nProcesseur: A17 Pro\nRAM: 8 Go\nStockage: 256 Go\nBatterie: 4422 mAh\nAppareil photo: 48MP + 12MP + 12MP', 950000.00, NULL, 15, 5, 1, 2, 1, 1, 1, 0, '2026-04-21 11:09:58', '2026-04-21 11:09:58'),
(3, 'Xiaomi Redmi Note 13 Pro', 'xiaomi-redmi-note-13-pro', 'Excellent rapport qualité-prix avec appareil photo 200MP', 'Écran: 6.67\" AMOLED 120Hz\nProcesseur: MediaTek Helio G99\nRAM: 8 Go\nStockage: 256 Go\nBatterie: 5100 mAh\nAppareil photo: 200MP + 8MP + 2MP', 180000.00, 155000.00, 40, 5, 1, 3, 1, 1, 0, 0, '2026-04-21 11:09:58', '2026-04-21 11:09:58'),
(4, 'MacBook Air M2', 'macbook-air-m2', 'Ultra-fin, ultra-puissant. Le MacBook Air avec la puce M2', 'Écran: 13.6\" Liquid Retina\nProcesseur: Apple M2\nRAM: 8 Go\nStockage: 256 Go SSD\nAutonomie: 18 heures\nPoids: 1.24 kg', 850000.00, 799000.00, 10, 3, 2, 2, 1, 1, 0, 0, '2026-04-21 11:09:58', '2026-04-21 11:09:58'),
(5, 'HP Pavilion 15', 'hp-pavilion-15', 'Ordinateur portable polyvalent pour le travail et les études', 'Écran: 15.6\" Full HD IPS\nProcesseur: Intel Core i5-1235U\nRAM: 8 Go DDR4\nStockage: 512 Go SSD\nOS: Windows 11\nPoids: 1.75 kg', 420000.00, NULL, 20, 5, 2, 4, 1, 0, 0, 0, '2026-04-21 11:09:58', '2026-04-21 11:09:58'),
(6, 'Lenovo IdeaPad Slim 3', 'lenovo-ideapad-slim-3', 'PC portable léger et abordable pour un usage quotidien', 'Écran: 15.6\" Full HD\nProcesseur: AMD Ryzen 5 7520U\nRAM: 8 Go\nStockage: 512 Go SSD\nOS: Windows 11\nPoids: 1.63 kg', 350000.00, 320000.00, 18, 5, 2, 8, 1, 0, 0, 0, '2026-04-21 11:09:58', '2026-04-21 11:09:58'),
(7, 'Samsung Smart TV 55\" QLED', 'samsung-smart-tv-55-qled', 'Téléviseur intelligent 4K avec technologie QLED', 'Écran: 55\" 4K QLED\nHDR: HDR10+\nSmart TV: Tizen OS\nConnectivité: Wi-Fi, Bluetooth, HDMI x3\nAudio: 20W Dolby Digital Plus', 450000.00, 399000.00, 8, 3, 3, 1, 1, 1, 0, 0, '2026-04-21 11:09:58', '2026-04-21 11:09:58'),
(8, 'Sony WH-1000XM5', 'sony-wh-1000xm5', 'Casque sans fil à réduction de bruit leader du marché', 'Type: Over-ear\nRéduction de bruit: Active (ANC)\nAutonomie: 30 heures\nCodec: LDAC, AAC, SBC\nPoids: 250g\nConnexion: Bluetooth 5.2', 250000.00, NULL, 12, 5, 3, 7, 1, 1, 0, 0, '2026-04-21 11:09:58', '2026-04-21 11:09:58'),
(9, 'Nike Air Max 90', 'nike-air-max-90', 'Baskets iconiques Nike Air Max 90 - confort et style', 'Matériau: Cuir et textile\nSemelle: Air Max\nFermeture: Lacets\nStyle: Lifestyle', 85000.00, 72000.00, 30, 5, 4, 5, 1, 0, 1, 0, '2026-04-21 11:09:58', '2026-04-21 11:09:58'),
(10, 'LG Réfrigérateur Double Porte', 'lg-r-frig-rateur-double-porte', 'Réfrigérateur spacieux avec technologie Linear Cooling', 'Capacité: 423 litres\r\nType: Double porte\r\nClasse énergie: A+\r\nTechnologie: Linear Cooling\r\nCompresseur: Smart Inverter\r\nDimensions: 70.5 x 178 x 73.5 cm', 380000.00, 350000.00, 2, 2, 5, 6, 1, 0, 0, 0, '2026-04-21 11:09:58', '2026-04-22 18:48:52');

-- --------------------------------------------------------

--
-- Structure de la table `produit_variantes`
--

CREATE TABLE `produit_variantes` (
  `id` int(11) NOT NULL,
  `produit_id` int(11) NOT NULL,
  `valeur` varchar(150) NOT NULL,
  `prix_supplementaire` decimal(10,2) NOT NULL DEFAULT 0.00,
  `prix` decimal(12,2) NOT NULL DEFAULT 0.00,
  `stock` int(11) NOT NULL DEFAULT 0,
  `stock_alerte` int(11) NOT NULL DEFAULT 3,
  `actif` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `produit_variantes`
--

INSERT INTO `produit_variantes` (`id`, `produit_id`, `valeur`, `prix_supplementaire`, `prix`, `stock`, `stock_alerte`, `actif`, `created_at`) VALUES
(5, 2, '256 Go - Titanium Naturel', 0.00, 950000.00, 5, 3, 1, '2026-04-21 11:09:58'),
(6, 2, '256 Go - Titanium Bleu', 0.00, 950000.00, 4, 3, 1, '2026-04-21 11:09:58'),
(7, 2, '512 Go - Titanium Noir', 100000.00, 1050000.00, 3, 3, 1, '2026-04-21 11:09:58'),
(8, 2, '1 To - Titanium Blanc', 250000.00, 1200000.00, 3, 3, 1, '2026-04-21 11:09:58'),
(9, 9, 'Taille 40 - Blanc/Noir', 0.00, 72000.00, 8, 3, 1, '2026-04-21 11:09:58'),
(10, 9, 'Taille 41 - Blanc/Noir', 0.00, 72000.00, 6, 3, 1, '2026-04-21 11:09:58'),
(11, 9, 'Taille 42 - Rouge/Noir', 0.00, 72000.00, 5, 3, 1, '2026-04-21 11:09:58'),
(12, 9, 'Taille 43 - Blanc/Noir', 0.00, 72000.00, 6, 3, 1, '2026-04-21 11:09:58'),
(13, 9, 'Taille 44 - Gris', 0.00, 72000.00, 5, 3, 1, '2026-04-21 11:09:58'),
(38, 1, '256 Go - Noir', 0.00, 25.00, 8, 3, 1, '2026-04-22 18:12:08'),
(39, 1, '256 Go - Gris Titane', 0.00, 25.00, 7, 3, 1, '2026-04-22 18:12:08'),
(40, 1, '512 Go - Noir', 50000.00, 50025.00, 5, 3, 1, '2026-04-22 18:12:08'),
(41, 1, '512 Go - Violet', 50000.00, 50025.00, 5, 3, 1, '2026-04-22 18:12:08');

-- --------------------------------------------------------

--
-- Doublure de structure pour la vue `v_graph_categories`
-- (Voir ci-dessous la vue réelle)
--
CREATE TABLE `v_graph_categories` (
`categorie` varchar(150)
,`quantite_totale` decimal(32,0)
,`ca_total` decimal(44,2)
);

-- --------------------------------------------------------

--
-- Doublure de structure pour la vue `v_graph_mois`
-- (Voir ci-dessous la vue réelle)
--
CREATE TABLE `v_graph_mois` (
`mois` varchar(7)
,`mois_nom` varchar(9)
,`ca_total` decimal(34,2)
,`nb_commandes` bigint(21)
);

-- --------------------------------------------------------

--
-- Doublure de structure pour la vue `v_graph_semaine`
-- (Voir ci-dessous la vue réelle)
--
CREATE TABLE `v_graph_semaine` (
`jour` varchar(64)
,`date_jour` date
,`ca_total` decimal(34,2)
,`nb_commandes` bigint(21)
);

-- --------------------------------------------------------

--
-- Doublure de structure pour la vue `v_graph_top7_produits`
-- (Voir ci-dessous la vue réelle)
--
CREATE TABLE `v_graph_top7_produits` (
`produit` varchar(255)
,`quantite_totale` decimal(32,0)
,`ca_total` decimal(44,2)
);

-- --------------------------------------------------------

--
-- Structure de la vue `v_graph_categories`
--
DROP TABLE IF EXISTS `v_graph_categories`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_graph_categories`  AS SELECT coalesce(`c`.`nom`,'Sans catégorie') AS `categorie`, coalesce(sum(`ci`.`quantite`),0) AS `quantite_totale`, coalesce(sum(`ci`.`prix_unitaire` * `ci`.`quantite`),0) AS `ca_total` FROM (((`commande_items` `ci` join `commandes` `cmd` on(`cmd`.`id` = `ci`.`commande_id`)) left join `produits` `p` on(`p`.`id` = `ci`.`produit_id`)) left join `categories` `c` on(`c`.`id` = `p`.`categorie_id`)) WHERE `cmd`.`statut` in ('livree','confirmee','en_livraison') GROUP BY `c`.`id`, `c`.`nom` ORDER BY coalesce(sum(`ci`.`quantite`),0) DESC LIMIT 0, 6 ;

-- --------------------------------------------------------

--
-- Structure de la vue `v_graph_mois`
--
DROP TABLE IF EXISTS `v_graph_mois`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_graph_mois`  AS SELECT date_format(`commandes`.`date_commande`,'%Y-%m') AS `mois`, monthname(`commandes`.`date_commande`) AS `mois_nom`, coalesce(sum(`commandes`.`total`),0) AS `ca_total`, count(0) AS `nb_commandes` FROM `commandes` WHERE `commandes`.`statut` in ('livree','confirmee','en_livraison') AND year(`commandes`.`date_commande`) = year(curdate()) GROUP BY date_format(`commandes`.`date_commande`,'%Y-%m') ORDER BY date_format(`commandes`.`date_commande`,'%Y-%m') ASC ;

-- --------------------------------------------------------

--
-- Structure de la vue `v_graph_semaine`
--
DROP TABLE IF EXISTS `v_graph_semaine`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_graph_semaine`  AS SELECT date_format(`commandes`.`date_commande`,'%W') AS `jour`, cast(`commandes`.`date_commande` as date) AS `date_jour`, coalesce(sum(`commandes`.`total`),0) AS `ca_total`, count(0) AS `nb_commandes` FROM `commandes` WHERE `commandes`.`statut` in ('livree','confirmee','en_livraison') AND `commandes`.`date_commande` >= curdate() - interval 7 day GROUP BY cast(`commandes`.`date_commande` as date) ORDER BY cast(`commandes`.`date_commande` as date) ASC ;

-- --------------------------------------------------------

--
-- Structure de la vue `v_graph_top7_produits`
--
DROP TABLE IF EXISTS `v_graph_top7_produits`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_graph_top7_produits`  AS SELECT `ci`.`nom_produit` AS `produit`, sum(`ci`.`quantite`) AS `quantite_totale`, sum(`ci`.`prix_unitaire` * `ci`.`quantite`) AS `ca_total` FROM (`commande_items` `ci` join `commandes` `cmd` on(`cmd`.`id` = `ci`.`commande_id`)) WHERE `cmd`.`statut` in ('livree','confirmee','en_livraison') GROUP BY `ci`.`nom_produit` ORDER BY sum(`ci`.`quantite`) DESC LIMIT 0, 7 ;

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Index pour la table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Index pour la table `clients`
--
ALTER TABLE `clients`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `commandes`
--
ALTER TABLE `commandes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `numero_cmd` (`numero_cmd`);

--
-- Index pour la table `commande_items`
--
ALTER TABLE `commande_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `commande_id` (`commande_id`);

--
-- Index pour la table `image_produit`
--
ALTER TABLE `image_produit`
  ADD PRIMARY KEY (`id`),
  ADD KEY `produit_id` (`produit_id`);

--
-- Index pour la table `marques`
--
ALTER TABLE `marques`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Index pour la table `messages_contact`
--
ALTER TABLE `messages_contact`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `parametres`
--
ALTER TABLE `parametres`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cle` (`cle`);

--
-- Index pour la table `produits`
--
ALTER TABLE `produits`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `categorie_id` (`categorie_id`),
  ADD KEY `marque_id` (`marque_id`);

--
-- Index pour la table `produit_variantes`
--
ALTER TABLE `produit_variantes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `produit_id` (`produit_id`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT pour la table `clients`
--
ALTER TABLE `clients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `commandes`
--
ALTER TABLE `commandes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT pour la table `commande_items`
--
ALTER TABLE `commande_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT pour la table `image_produit`
--
ALTER TABLE `image_produit`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT pour la table `marques`
--
ALTER TABLE `marques`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT pour la table `messages_contact`
--
ALTER TABLE `messages_contact`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `parametres`
--
ALTER TABLE `parametres`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;

--
-- AUTO_INCREMENT pour la table `produits`
--
ALTER TABLE `produits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT pour la table `produit_variantes`
--
ALTER TABLE `produit_variantes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `commande_items`
--
ALTER TABLE `commande_items`
  ADD CONSTRAINT `fk_items_commande` FOREIGN KEY (`commande_id`) REFERENCES `commandes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `image_produit`
--
ALTER TABLE `image_produit`
  ADD CONSTRAINT `fk_images_produit` FOREIGN KEY (`produit_id`) REFERENCES `produits` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `produits`
--
ALTER TABLE `produits`
  ADD CONSTRAINT `fk_produits_categorie` FOREIGN KEY (`categorie_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_produits_marque` FOREIGN KEY (`marque_id`) REFERENCES `marques` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Contraintes pour la table `produit_variantes`
--
ALTER TABLE `produit_variantes`
  ADD CONSTRAINT `fk_variantes_produit` FOREIGN KEY (`produit_id`) REFERENCES `produits` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
