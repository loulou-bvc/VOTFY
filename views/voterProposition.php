<?php
// Classe callApi intégrée pour simplifier
class callApi {
    public function callApi($endpoint, $method = 'GET', $data = null) {
        $url = "https://webdev.iut-orsay.fr/~nboulad/VOTFY/api/$endpoint";
        $options = [
            'http' => [
                'header' => "Content-Type: application/json\r\n",
                'method' => $method,
            ],
        ];

        if ($data) {
            $options['http']['content'] = json_encode($data);
        }

        $context = stream_context_create($options);
        $response = @file_get_contents($url, false, $context);

        if ($response === false) {
            $error = error_get_last();
            error_log("Erreur API : " . print_r($error, true));
            return ['status' => 'error', 'message' => 'Erreur lors de l\'appel API'];
        }

        $decodedResponse = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['status' => 'error', 'message' => 'Réponse JSON invalide'];
        }

        return $decodedResponse;
    }
}

// Démarrage de session si nécessaire
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérifiez si l'utilisateur est connecté
if (!isset($_SESSION['id_utilisateur'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['id_utilisateur'];
$propositionId = isset($_GET['id_proposition']) ? (int)$_GET['id_proposition'] : null;

if (!$propositionId) {
    header('Location: mesPropositions.php');
    exit;
}

// Instanciez le client API
$apiClient = new callApi();

// Récupérer les détails de la proposition via l'API
$propositionResponse = $apiClient->callApi("propositions/$propositionId", 'GET');
if ($propositionResponse['status'] !== 'success') {
    header('Location: mesPropositions.php');
    exit;
}
$proposition = $propositionResponse['proposition'];

// Vérifiez si l'utilisateur a déjà voté via l'API
$voteCheckResponse = $apiClient->callApi("votes/hasVoted/$userId/$propositionId", 'GET');
$alreadyVoted = ($voteCheckResponse['status'] === 'success' && $voteCheckResponse['hasVoted']);

// Traitement du formulaire de vote
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['vote'])) {
    if ($alreadyVoted) {
        echo "<script>alert('Vous avez déjà voté pour cette proposition.');</script>";
    } else {
        $vote = $_POST['vote'];
        if (in_array($vote, ['pour', 'contre', 'abstention'])) {
            $data = [
                'proposition_id' => $propositionId,
                'user_id' => $userId,
                'choix' => $vote,
            ];
            $voteResponse = $apiClient->callApi('votes', 'POST', $data);
            if ($voteResponse['status'] === 'success') {
                echo "<script>alert('Votre vote a été enregistré avec succès !'); window.location.href='voterProposition.php?id_proposition=$propositionId';</script>";
                exit;
            } else {
                echo "<script>alert('Erreur lors de l\'enregistrement de votre vote : {$voteResponse['message']}');</script>";
            }
        } else {
            echo "<script>alert('Vote invalide.');</script>";
        }
    }
}

// Récupérer les statistiques des votes via l'API
$voteStatsResponse = $apiClient->callApi("votes/statistics/$propositionId", 'GET');
$voteStats = ($voteStatsResponse['status'] === 'success') ? $voteStatsResponse['statistics'] : null;
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vote pour la Proposition</title>
    <link rel="stylesheet" href="../css/voterProposition.css">
    <link rel="stylesheet" href="../css/global.css">
    <link rel="stylesheet" href="../css/nouveaugroupe.css">
    <link rel="stylesheet" href="../css/accueil.css"> <!-- CSS spécifique à l'accueil -->
    <link rel="stylesheet" href="../css/footer.css">
    <link rel="stylesheet" href="../css/header.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.5/font/bootstrap-icons.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<header class="header-container">
    <a href="javascript:history.back()" class="back-arrow">
        <i class="bi bi-chevron-left"></i>
    </a>
    <h1 class="header-title">Vote pour la Proposition</h1>
</header>
<main class="mt-4">
    <div class="container-all1">
        <h2 style="margin-bottom: 35px;">Proposition <strong><?= htmlspecialchars($proposition['titre']) ?></strong></h2>
        <p><strong>Description :</strong> <?= htmlspecialchars($proposition['description']) ?></p>
        <p><strong>Date :</strong> <?= htmlspecialchars($proposition['date_proposition']) ?></p>
        <p><strong>Durée :</strong> <?= htmlspecialchars($proposition['duree']) ?></p>
        <p style="font-size: 3em"><strong>Budget :</strong> <?= htmlspecialchars($proposition['budget']) ?>€</p>

        <?php if ($voteStats): ?>
            <div class="mt-4">
                <h3>Statistiques des votes :</h3>
                <p style="font-size: 1.5em;margin-top: 10px;"><strong>Total des votes :</strong> <?= $voteStats['totalVotes'] ?></p>
                <div class="chart-container">
                    <canvas id="voteChart"></canvas>
                </div>
                <div class="list-result">
                    <p>Pour : <?= $voteStats['voteCounts']['pour'] ?> (<?= $voteStats['percentages']['pour'] ?>%)</p>
                    <p>Contre : <?= $voteStats['voteCounts']['contre'] ?> (<?= $voteStats['percentages']['contre'] ?>%)</p>
                    <p>Abstentions : <?= $voteStats['voteCounts']['abstention'] ?> (<?= $voteStats['percentages']['abstention'] ?>%)</p>
                </div>
            </div>
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    const ctx = document.getElementById('voteChart').getContext('2d');
                    const voteChart = new Chart(ctx, {
                        type: 'doughnut',
                        data: {
                            labels: ['Pour', 'Contre', 'Abstentions'],
                            datasets: [{
                                data: [
                                    <?= $voteStats['percentages']['pour'] ?>,
                                    <?= $voteStats['percentages']['contre'] ?>,
                                    <?= $voteStats['percentages']['abstention'] ?>
                                ],
                                backgroundColor: ['#4caf50', '#f44336', '#2196f3'],
                                borderColor: ['#fff', '#fff', '#fff'],
                                borderWidth: 2
                            }]
                        },
                        options: {
                            responsive: true,
                            plugins: {
                                legend: {
                                    display: true,
                                    position: 'top',
                                },
                            }
                        }
                    });
                });
            </script>
        <?php endif; ?>

        <?php if ($alreadyVoted): ?>
            <p class="alert alert-info">Vous avez déjà voté pour cette proposition.</p>
        <?php else: ?>
            <form method="POST" class="form-vote">
                <div class="form-check1">
                    <button type="submit" name="vote" value="pour" class="btn3 btn-success" id="votePour"><img src="../images/vote_pour.png" alt="Vote Pour" class="img-vote img-pour"></button>
                </div>
                <div class="form-check1">
                    <button type="submit" name="vote" value="abstention" class="btn3 btn-secondary" id="voteAbstention"><img src="../images/vote_blanc.png" alt="Vote Abstention" class="img-vote img-blanc"></button>
                </div>
                <div class="form-check1">
                    <button type="submit" name="vote" value="contre" class="btn3 btn-danger" id="voteContre"><img src="../images/vote_contre.png" alt="Vote Contre" class="img-vote img-contre"></button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</main>
</body>
<?php include_once 'includes/footer.html'; ?>
</html>
