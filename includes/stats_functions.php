<?php
/**
 * ShopMax — Statistiques & Requêtes Dashboard
 * Ce fichier remplace les vues MySQL pour assurer la compatibilité 
 * avec tous les types d'hébergement.
 */

/**
 * Récupère le CA par jour sur les 7 derniers jours
 */
function getStatsVentesSemaine($pdo) {
    try {
        return $pdo->query("
            SELECT 
                date_format(date_commande,'%W') AS jour, 
                CAST(date_commande AS DATE) AS date_jour, 
                COALESCE(SUM(total),0) AS ca_total,
                COUNT(*) AS nb_commandes
            FROM commandes 
            WHERE statut IN ('livree','confirmee','en_livraison') 
            AND date_commande >= CURDATE() - INTERVAL 7 DAY 
            GROUP BY CAST(date_commande AS DATE) 
            ORDER BY CAST(date_commande AS DATE) ASC
        ")->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Stats Error (Semaine): " . $e->getMessage());
        return [];
    }
}

/**
 * Récupère le CA par mois pour l'année en cours
 */
function getStatsVentesMois($pdo) {
    try {
        return $pdo->query("
            SELECT 
                date_format(date_commande,'%b') AS mois, 
                COALESCE(SUM(total),0) AS ca_total 
            FROM commandes 
            WHERE statut IN ('livree','confirmee','en_livraison') 
            AND YEAR(date_commande) = YEAR(CURDATE()) 
            GROUP BY MONTH(date_commande) 
            ORDER BY MONTH(date_commande) ASC
        ")->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Stats Error (Mois): " . $e->getMessage());
        return [];
    }
}

/**
 * Récupère la répartition des ventes par catégorie
 */
function getStatsParCategorie($pdo) {
    try {
        return $pdo->query("
            SELECT 
                c.nom AS categorie, 
                COUNT(lc.produit_id) AS quantite_totale 
            FROM categories c 
            JOIN produits p ON p.categorie_id = c.id 
            JOIN ligne_commande lc ON lc.produit_id = p.id 
            GROUP BY c.id
        ")->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Stats Error (Catégories): " . $e->getMessage());
        return [];
    }
}

/**
 * Récupère le Top 7 des produits les plus vendus
 */
function getStatsTopProduits($pdo) {
    try {
        return $pdo->query("
            SELECT 
                p.nom AS produit, 
                SUM(lc.quantite) AS quantite_totale 
            FROM produits p 
            JOIN ligne_commande lc ON lc.produit_id = p.id 
            GROUP BY p.id 
            ORDER BY quantite_totale DESC 
            LIMIT 7
        ")->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Stats Error (Top Produits): " . $e->getMessage());
        return [];
    }
}
