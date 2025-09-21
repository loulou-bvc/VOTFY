<?php

require_once __DIR__ . '/../Connexion.php';

class UtilisateurController {
    /**
     * Crée un nouvel utilisateur avec les données fournies.
     *
     * @param array $data Les données de l'utilisateur.
     * @return array Le statut de la création de l'utilisateur.
     */
    public static function create($data) {
        $pdo = Connexion::pdo();
        try {
            // Validation des données
            if (!isset($data['nom'], $data['prenom'], $data['email'], $data['mot_de_passe'], $data['adresse_postale'])) {
                return ['status' => 'error', 'message' => 'Données manquantes ou invalides'];
            }
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                return ['status' => 'error', 'message' => 'Email invalide'];
            }

            // Génération du token de vérification
            $verificationToken = bin2hex(random_bytes(16));
            $expiryDate = date('Y-m-d H:i:s', strtotime('+24 hours'));

            // Appel de la procédure stockée
            $sql = "CALL add_user(:nom, :prenom, :email, :motDePasse, :adressePostale, :verificationToken, :verificationExpiry)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':nom' => $data['nom'],
                ':prenom' => $data['prenom'],
                ':email' => $data['email'],
                ':motDePasse' => password_hash($data['mot_de_passe'], PASSWORD_BCRYPT),
                ':adressePostale' => $data['adresse_postale'],
                ':verificationToken' => $verificationToken,
                ':verificationExpiry' => $expiryDate
            ]);

            return ['status' => 'success', 'message' => 'Utilisateur créé avec succès', 'verificationToken' => $verificationToken];
        } catch (PDOException $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * Supprime un utilisateur par son ID.
     *
     * @param int $id L'ID de l'utilisateur.
     * @return array Le statut de la suppression.
     */
    public static function deleteAccount($id) {
        $pdo = Connexion::pdo();
        try {
            $sql = "CALL delete_user(:id)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':id' => $id]);

            return ['status' => 'success', 'message' => 'Utilisateur supprimé avec succès'];
        } catch (PDOException $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * Récupère un utilisateur par son ID.
     *
     * @param int $id L'ID de l'utilisateur.
     * @return array Les informations utilisateur ou un message d'erreur.
     */
    public static function getById($id) {
        if (!is_numeric($id) || (int)$id <= 0) {
            return ['status' => 'error', 'message' => 'ID utilisateur invalide'];
        }

        $pdo = Connexion::pdo();
        try {
            $stmt = $pdo->prepare("SELECT * FROM Utilisateur WHERE id_utilisateur = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                return ['status' => 'error', 'message' => 'Utilisateur non trouvé'];
            }

            return ['status' => 'success', 'data' => $user];
        } catch (PDOException $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * Met à jour les informations d'un utilisateur.
     *
     * @param int $id L'ID de l'utilisateur.
     * @param array $data Les données à mettre à jour.
     * @return array Le statut de la mise à jour.
     */
    public static function updateUserInfo($id, $data) {
        $pdo = Connexion::pdo();
        try {
            if (!isset($data['nom'], $data['prenom'], $data['email'], $data['adresse_postale'])) {
                return ['status' => 'error', 'message' => 'Données manquantes ou invalides'];
            }

            $sql = "CALL update_user(:id, :nom, :prenom, :email, :adressePostale)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':id' => $id,
                ':nom' => $data['nom'],
                ':prenom' => $data['prenom'],
                ':email' => $data['email'],
                ':adressePostale' => $data['adresse_postale']
            ]);

            NotificationController::create($id, "Vos informations ont été mises à jour.");
            return ['status' => 'success', 'message' => 'Informations mises à jour avec succès'];
        } catch (PDOException $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * Gère la connexion d'un utilisateur.
     *
     * @param array $data Les informations de connexion (email, mot_de_passe).
     * @return array Le statut de la connexion et les informations utilisateur.
     */
    public static function login($data) {
        $pdo = Connexion::pdo();
        try {
            $sql = "SELECT id_utilisateur, prenom, mot_de_passe, email_verified FROM Utilisateur WHERE email = :email";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':email' => $data['email']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($data['mot_de_passe'], $user['mot_de_passe'])) {
                return [
                    'success' => true,
                    'status' => 'success',
                    'message' => 'Connexion réussie',
                    'user' => [
                        'id_utilisateur' => $user['id_utilisateur'],
                        'prenom' => $user['prenom'],
                        'email_verified' => $user['email_verified']
                    ]
                ];
            }
            return [
                'success' => false,
                'status' => 'error',
                'message' => 'Email ou mot de passe incorrect',
                'user' => null
            ];
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return [
                'success' => false,
                'status' => 'error',
                'message' => 'Une erreur interne est survenue.',
                'user' => null
            ];
        }
    }
}


?>
