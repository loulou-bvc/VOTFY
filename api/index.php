<?php

// Chargement des dépendances
require_once __DIR__ . '/Connexion.php';
require_once __DIR__ . '/routes/UtilisateurRoutes.php';
require_once __DIR__ . '/routes/GroupeRoutes.php';
require_once __DIR__ . '/routes/PropositionRoutes.php';
require_once __DIR__ . '/routes/CommentRoutes.php';
require_once __DIR__ . '/routes/VoteRoutes.php';
require_once __DIR__ . '/routes/NotificationRoutes.php';
require_once __DIR__ . '/routes/MessageRoutes.php';
require_once __DIR__ . '/routes/MailRoutes.php';
require_once __DIR__ . '/routes/SignalementRoutes.php';

// Récupération de la méthode HTTP
$requestMethod = $_SERVER['REQUEST_METHOD'];

// Surcharge de la méthode HTTP si `_method` est présent
if ($requestMethod === 'POST' && isset($_POST['_method'])) {
    $requestMethod = strtoupper($_POST['_method']); // Convertir en majuscule pour standardisation
}

// Récupération de l'URI
$requestUri = explode('/', trim($_SERVER['REQUEST_URI'], '/'));

// Base pour les routes `/~nboulad/VOTFY/api`
$baseUri = '~nboulad/VOTFY/api';

// Logging pour debug
$logFile = __DIR__ . '/../debug.log';
file_put_contents($logFile, "Méthode : $requestMethod, URI : " . json_encode($requestUri) . PHP_EOL, FILE_APPEND);

// Validation de l'URI
if (isset($requestUri[2]) && implode('/', array_slice($requestUri, 0, 3)) === $baseUri) {
    $ressource = $requestUri[3] ?? null; // La ressource ciblée (utilisateurs, groupes, etc.)
    
    if (!$ressource) {
        http_response_code(400);
        echo json_encode(['message' => 'Aucune ressource spécifiée']);
        exit;
    }

    // Gestion des ressources
    switch ($ressource) {
        case 'utilisateurs':
            handleUserRoutes($requestMethod, $requestUri);
            break;
        case 'groupes':
            handleGroupRoutes($requestMethod, $requestUri);
            break;
        case 'propositions':
            handlePropositionRoutes($requestMethod, $requestUri);
            break;
        case 'commentaires':
            handleCommentRoutes($requestMethod, $requestUri);
            break;
        case 'votes':
            handleVoteRoutes($requestMethod, $requestUri);
            break;
        case 'notifications':
            handleNotificationRoutes($requestMethod, $requestUri);
            break;
        case 'messages':
            handleMessageRoutes($requestMethod, $requestUri);
            break;
        case 'login':
            handleUserLogin($requestMethod, $requestUri);
            break;
        case 'mail':
            handleMailRoutes($requestMethod, $requestUri);  
            break;
        case 'logout':
            handleUserLogout($requestMethod, $requestUri);
            break;
        case 'signalement':
            handleSignalementRoutes($requestMethod, $requestUri);
            break;
        default:
            // Ressource non trouvée
            http_response_code(404);
            echo json_encode(['message' => 'Ressource non trouvée']);
            break;
    }
} else {
    // URI incorrecte
    http_response_code(400);
    echo json_encode(['message' => 'URI incorrecte ou non autorisée']);
}
?>
