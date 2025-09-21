<?php
session_start();

if (!isset($_SESSION['id_utilisateur'])) {
    header('Location: login.php');
    exit;
}

if (!isset($_GET['id_groupe']) || !filter_var($_GET['id_groupe'], FILTER_VALIDATE_INT)) {
    header('Location: mesgroupes.php');
    exit;
}

$idGroupe = (int)$_GET['id_groupe'];
$apiBaseUrl = 'https://webdev.iut-orsay.fr/~nboulad/VOTFY/api'; // Remplacez par l'URL de base de votre API
$logFile = __DIR__ . '/logs/debug.log';

function callApi($method, $endpoint, $data = null) {
    global $apiBaseUrl, $logFile;

    $url = $apiBaseUrl . $endpoint;
    $options = [
        'http' => [
            'method' => $method,
            'header' => "Content-Type: application/json\r\nAccept: application/json",
        ]
    ];

    if ($data) {
        $options['http']['content'] = json_encode($data);
    }

    file_put_contents($logFile, "Appel API : $method $url\nDonnées : " . json_encode($data) . "\n", FILE_APPEND);
    $context = stream_context_create($options);
    $response = @file_get_contents($url, false, $context);

    if ($response === false) {
        $error = error_get_last();
        file_put_contents($logFile, "Erreur API : " . $error['message'] . "\n", FILE_APPEND);
        return ['status' => 'error', 'message' => $error['message']];
    }

    file_put_contents($logFile, "Réponse API : $response\n", FILE_APPEND);
    return json_decode($response, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = htmlspecialchars(trim($_POST['message'] ?? ''));
    if (!empty($message)) {
        $data = [
            'id_utilisateur' => $_SESSION['id_utilisateur'],
            'message' => $message
        ];
        $response = callApi('POST', "/messages/$idGroupe", $data);
        if ($response['status'] !== 'success') {
            file_put_contents($logFile, "Erreur lors de l'envoi du message : " . json_encode($response) . "\n", FILE_APPEND);
            echo json_encode(['error' => $response['message']]);
        }
    } else {
        file_put_contents($logFile, "Erreur : message vide\n", FILE_APPEND);
    }
    exit;
}

// Pas de traitement direct GET dans ce fichier pour renvoyer JSON
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat - Groupe</title>
    <link rel="stylesheet" href="../css/chat.css">
    <link rel="stylesheet" href="../css/footer.css">
    <link rel="stylesheet" href="../css/header.css">
    <link rel="stylesheet" href="../css/inviter.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
</head>
<body>
<?php include_once 'includes/header.php'; ?>
<div class="body-container content">
    <h2 class="text-center"> <strong>Chat du Groupe </strong></h2>
    <div class="chat-container info-container">
        <div id="messages-container" class="messages-container"></div>
        <div id="typing-status"></div>
        <div class="chat-input">
            <form id="chat-form">
                <textarea name="message" placeholder="Écrivez votre message..." required></textarea>
                <button type="submit">Envoyer</button>
            </form>
        </div>
    </div>
</div>

<script>
    const idGroupe = <?= json_encode($idGroupe) ?>;

    function loadMessages() {
        fetch(`<?= $apiBaseUrl ?>/messages/${idGroupe}`)
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success' && data.messages) {
                    const container = document.getElementById('messages-container');
                    container.innerHTML = '';
                    data.messages.forEach(message => {
                        const div = document.createElement('div');
                        div.className = 'message';
                        div.innerHTML = `
                            <strong>${message.utilisateur}</strong> : 
                            <p>${message.message}</p> 
                            <small>(${message.date_envoi})</small>
                        `;
                        container.appendChild(div);
                    });
                    container.scrollTop = container.scrollHeight; // Scrolle en bas
                } else {
                    console.error('Erreur dans la structure des données API', data);
                }
            })
            .catch(error => console.error('Erreur lors du chargement des messages :', error));
    }

    document.getElementById('chat-form').addEventListener('submit', function (event) {
        event.preventDefault();
        const formData = new FormData(this);

        fetch('', {
            method: 'POST',
            body: formData
        })
        .then(() => {
            this.reset();
            loadMessages();
        })
        .catch(error => console.error('Erreur lors de l\'envoi du message :', error));
    });

    setInterval(loadMessages, 3000);
    loadMessages();
</script>
</body>
<?php include_once 'includes/footer.html'; ?>
</html>
