<?php
/**
 * ShopMax — Statistiques & Requêtes Dashboard
 * Ce fichier remplace les vues MySQL pour assurer la compatibilité 
 * avec tous les types d'hébergement.
 * Optimisé : Traduction des jours/mois en français et labels plus propres.
 */

/**
 * Récupère le CA par jour sur les 7 derniers jours (ou plus pour le test)
 */
function getStatsVentesSemaine($pdo) {
    try {
        // On passe en français pour les noms de jours si possible
        $pdo->exec("SET lc_time_names = 'fr_FR'");
        
        return $pdo->query("
            SELECT 
                DATE_FORMAT(date_commande, '%W') AS jour, 
                CAST(date_commande AS DATE) AS date_jour, 
                COALESCE(SUM(total), 0) AS ca_total,
                COUNT(*) AS nb_commandes
            FROM commandes 
            WHERE statut IN ('livree','confirmee','en_livraison') 
            AND date_commande >= CURDATE() - INTERVAL 30 DAY 
            GROUP BY CAST(date_commande AS DATE) 
            ORDER BY CAST(date_commande AS DATE) ASC
        ")->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Fallback si SET lc_time_names échoue
        return $pdo->query("
            SELECT 
                DATE_FORMAT(date_commande, '%d/%m') AS jour, 
                CAST(date_commande AS DATE) AS date_jour, 
                COALESCE(SUM(total), 0) AS ca_total
            FROM commandes 
            WHERE statut IN ('livree','confirmee','en_livraison') 
            AND date_commande >= CURDATE() - INTERVAL 30 DAY 
            GROUP BY CAST(date_commande AS DATE) 
            ORDER BY CAST(date_commande AS DATE) ASC
        ")->fetchAll(PDO::FETCH_ASSOC);
    }
}

/**
 * Récupère le CA par mois pour l'année en cours
 */
function getStatsVentesMois($pdo) {
    try {
        $pdo->exec("SET lc_time_names = 'fr_FR'");
        
        return $pdo->query("
            SELECT 
                DATE_FORMAT(date_commande, '%b') AS mois, 
                COALESCE(SUM(total), 0) AS ca_total 
            FROM commandes 
            WHERE statut IN ('livree','confirmee','en_livraison') 
            AND YEAR(date_commande) = YEAR(CURDATE()) 
            GROUP BY MONTH(date_commande) 
            ORDER BY MONTH(date_commande) ASC
        ")->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return $pdo->query("
            SELECT 
                DATE_FORMAT(date_commande, '%m/%Y') AS mois, 
                COALESCE(SUM(total), 0) AS ca_total 
            FROM commandes 
            WHERE statut IN ('livree','confirmee','en_livraison') 
            AND YEAR(date_commande) = YEAR(CURDATE()) 
            GROUP BY MONTH(date_commande) 
            ORDER BY MONTH(date_commande) ASC
        ")->fetchAll(PDO::FETCH_ASSOC);
    }
}

/**
 * Récupère la répartition des ventes par catégorie
 */
function getStatsParCategorie($pdo) {
    try {
        return $pdo->query("
            SELECT 
                COALESCE(c.nom, 'Sans catégorie') AS categorie, 
                COALESCE(SUM(ci.quantite), 0) AS quantite_totale,
                COALESCE(SUM(ci.prix_unitaire * ci.quantite), 0) AS ca_total
            FROM commande_items ci
            JOIN commandes cmd ON cmd.id = ci.commande_id
            LEFT JOIN produits p ON p.id = ci.produit_id
            LEFT JOIN categories c ON c.id = p.categorie_id
            WHERE cmd.statut IN ('livree','confirmee','en_livraison')
            GROUP BY c.id, c.nom
            ORDER BY quantite_totale DESC
            LIMIT 6
        ")->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
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
                ci.nom_produit AS produit, 
                SUM(ci.quantite) AS quantite_totale,
                SUM(ci.prix_unitaire * ci.quantite) AS ca_total
            FROM commande_items ci
            JOIN commandes cmd ON cmd.id = ci.commande_id
            WHERE cmd.statut IN ('livree','confirmee','en_livraison')
            GROUP BY ci.nom_produit
            ORDER BY quantite_totale DESC
            LIMIT 7
        ")->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}
