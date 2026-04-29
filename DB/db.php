<?php

/**
 * Classe de connexion à la base de données (Pattern Singleton)
 * Projet : ShopMax
 */
class Database {
    // Instance unique de la classe
    private static $instance = null;
    // Objet de connexion PDO
    private $pdo;

    // Paramètres de configuration (à adapter selon ton environnement)
    private $host     = 'localhost';
    private $db_name  = 'shopmax_db';
    private $username = 'root';
    private $password = ''; // Par défaut vide sur XAMPP/WAMP
    private $charset  = 'utf8mb4';

    /**
     * Le constructeur est privé pour empêcher l'instanciation directe via 'new'
     */
    private function __construct() {
        $dsn = "mysql:host={$this->host};dbname={$this->db_name};charset={$this->charset}";
        
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Lance des exceptions en cas d'erreur SQL
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Retourne les résultats sous forme de tableaux associatifs
            PDO::ATTR_EMULATE_PREPARES   => false,                  // Utilise les vraies requêtes préparées de MySQL
        ];

        try {
            $this->pdo = new PDO($dsn, $this->username, $this->password, $options);
        } catch (PDOException $e) {
            // En production, ne jamais afficher $e->getMessage() directement (risque de sécurité)
            die("Erreur de connexion à la base de données : " . $e->getMessage());
        }
    }

    /**
     * Empêche le clonage de l'instance
     */
    private function __clone() {}

    /**
     * Empêche la désérialisation de l'instance
     */
    public function __wakeup() {
        throw new Exception("Impossible de désérialiser un singleton.");
    }

    /**
     * Méthode statique pour récupérer l'instance unique
     * * @return Database
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Récupère l'objet PDO pour effectuer des requêtes
     * * @return PDO
     */
    public function getConnection() {
        return $this->pdo;
    }
}