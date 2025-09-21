<?php
class Connexion {
    // Les attributs static caractéristiques de la connexion
    static private $hostname = 'projets.iut-orsay.fr';
    static private $database = 'saes3-nboulad';
    static private $login = 'saes3-nboulad';
    static private $password = 'iOavbOOVKaDrr17w';

    static private $tabUTF8 = array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8");

    // L'attribut static qui matérialisera la connexion
    static private $pdo;

    // Le getter public de cet attribut
    static public function pdo() {
        // Si la connexion n'est pas encore établie, on l'initialise
        if (!self::$pdo) {
            self::connect();
        }
        return self::$pdo;
    }

    // La fonction static de connexion qui initialise $pdo et lance la tentative de connexion
    static private function connect() {
        $h = self::$hostname;
        $d = self::$database;
        $l = self::$login;
        $p = self::$password;
        $t = self::$tabUTF8;
        try {
            self::$pdo = new PDO("mysql:host=$h;dbname=$d", $l, $p, $t);
            self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("Erreur de connexion à la base de données : " . $e->getMessage());
        }
    }
}
?>
