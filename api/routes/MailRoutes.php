<?php 

require_once __DIR__.'/../controller/MailController.php';

function handleMailRoutes($method,$uri){
    $logFile = __DIR__ . '/../debug.log';
    file_put_contents($logFile, "Méthode : $method, URI : " . json_encode($uri) . "\n", FILE_APPEND);

    switch ($method) {
        case "POST":
            $data = json_decode(file_get_contents('php://input'), true);
            if(isset($uri[4]) && $uri[4] === "inviter"){
                $to = $data["email"];
                $invitationLinkAccept = $data["invitationLinkAccept"];
                $invitationLinkReject = $data["invitationLinkDecline"];
                echo json_encode(MailController::sendInvitationEmail($to, $invitationLinkAccept,$invitationLinkReject));
                return;
            }
            else{
                if(isset($uri[4]) && $uri[4] === "change"&&is_numeric($uri[5])){
                    $to = $data["email"];
                    $reset_link = $data["reset_link"];
                    echo json_encode(MailController::sendPasswordResetEmail($to, $reset_link));
                    return;
                }
            }
            break;

        default :
            http_response_code(405);
            echo json_encode(['message' => 'Méthode non autorisée']);
            break;
    }
}