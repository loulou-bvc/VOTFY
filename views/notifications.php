<?php
session_start();

if (!isset($_SESSION['id_utilisateur'])) {
    echo "La session user_id n'est pas active, l'utilisateur n'est pas connecté.";
    exit;
}

$userId = $_SESSION['id_utilisateur'];
$apiBaseUrl = 'https://webdev.iut-orsay.fr/~nboulad/VOTFY/api/notifications'; // Remplacez par l'URL de base de votre API

function callApi($method, $endpoint, $data = null) {
    global $apiBaseUrl;

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

    $context = stream_context_create($options);
    $response = file_get_contents($url, false, $context);
    if ($response === false) {
        return ['status' => 'error', 'message' => 'Erreur lors de l\'appel à l\'API'];
    }
    return json_decode($response, true);
}

// Récupération des notifications de l'utilisateur
$result = callApi('GET', "/{$userId}");
$notifications = $result['status'] === 'success' ? $result['notifications'] : [];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications</title>
    <link rel="stylesheet" href="../css/header.css">
    <link rel="stylesheet" href="../css/footer.css">
    <link rel="stylesheet" href="../css/mesNotifications.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f9f9f9;
        }

        header {
            text-align: center;
            margin-bottom: 20px;
            background-color: #007bff;
            color: white;
            padding: 15px 0;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .groups-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            padding: 20px;
        }

        .cardg {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            padding: 15px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            overflow: hidden;
            cursor: pointer;
            position: relative;
        }

        .cardg:hover {
            transform: scale(1.05);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }

        .text-notif h3 {
            font-size: 1.2rem;
            margin: 0;
            padding-bottom: 5px;
        }

        .text-notif p {
            font-size: 0.9rem;
            color: #555;
            margin: 0;
        }

        /* Menu contextuel */
        .context-menu {
            position: absolute;
            background-color: white;
            border: 1px solid #ddd;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            border-radius: 5px;
            display: none;
            z-index: 1000;
            width: 150px;
        }

        .context-menu ul {
            list-style: none;
            margin: 0;
            padding: 5px 0;
        }

        .context-menu ul li {
            padding: 10px;
            cursor: pointer;
            text-align: center;
            transition: background-color 0.2s ease;
        }

        .context-menu ul li:hover {
            background-color: #f1f1f1;
        }
    </style>
</head>
<?php include_once 'includes/header.php'; ?>
<body>
<header>
    <h1>Mes Notifications</h1>
</header>
<main>
    <div class="groups-grid">
        <?php foreach ($notifications as $notification): ?>
            <div class="cardg" data-notification-id="<?= $notification['id_notification'] ?>">
                <div class="text-notif">
                    <h3><?= htmlspecialchars($notification['message'] ?? 'Message non défini') ?></h3>
                    <p><?= htmlspecialchars($notification['date_notification'] ?? '24H') ?></p>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <!-- Menu contextuel -->
    <div id="context-menu" class="context-menu">
        <ul>
            <li id="mark-read">Marquer comme lue</li>
            <li id="delete">Supprimer</li>
        </ul>
    </div>
</main>
<script>
    const apiUrl = "<?= $apiBaseUrl ?>";
    const contextMenu = document.getElementById('context-menu');
    let currentNotificationId = null;

    // Afficher le menu contextuel
    document.querySelectorAll('.cardg').forEach(card => {
        card.addEventListener('contextmenu', (event) => {
            event.preventDefault();
            currentNotificationId = card.dataset.notificationId;

            // Positionner le menu contextuel
            contextMenu.style.display = 'block';
            contextMenu.style.left = `${event.pageX}px`;
            contextMenu.style.top = `${event.pageY}px`;
        });
    });

    // Cacher le menu contextuel en cliquant ailleurs
    document.addEventListener('click', () => {
        contextMenu.style.display = 'none';
    });

    // Actions du menu contextuel
    document.getElementById('mark-read').addEventListener('click', () => {
        if (currentNotificationId) {
            fetch(`${apiUrl}/mark/${currentNotificationId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                alert(data.message || 'Notification marquée comme lue.');
                location.reload();
            });
        }
    });

    document.getElementById('delete').addEventListener('click', () => {
        if (currentNotificationId) {
            fetch(`${apiUrl}/delete/${currentNotificationId}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                alert(data.message || 'Notification supprimée.');
                location.reload();
            });
        }
    });
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>
<?php include_once 'includes/footer.php'; ?>
</html>
