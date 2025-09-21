// Gère la soumission du formulaire d'inscription
function handleRegistrationSubmit(event) {
    event.preventDefault(); // Empêche l'envoi réel du formulaire
    showPopup(); // Affiche le pop-up de vérification
    startResendTimer(); // Active le timer pour le bouton de renvoi
}

// Gère la soumission du code de vérification
function submitVerificationCode() {
    const verificationCode = document.getElementById('verification-code').value;
    if (verificationCode) {
        alert(`Code soumis : ${verificationCode}`); // Exemple : traitement côté client
        closePopup(); // Ferme le pop-up après soumission
    } else {
        alert("Veuillez entrer le code de vérification.");
    }
}

// Timer pour activer le bouton de renvoi
let resendTimer = 90;
let resendInterval;

function startResendTimer() {
    const resendButton = document.getElementById('resend-email');
    const resendInfo = document.getElementById('resend-info');

    resendButton.disabled = true; // Désactive le bouton
    resendInfo.textContent = `Vous pourrez renvoyer l'email dans ${resendTimer} secondes.`;

    resendInterval = setInterval(() => {
        resendTimer--;
        resendInfo.textContent = `Vous pourrez renvoyer l'email dans ${resendTimer} secondes.`;

        if (resendTimer <= 0) {
            clearInterval(resendInterval);
            resendTimer = 90;
            resendButton.disabled = false; // Active le bouton
            resendInfo.textContent = "Vous pouvez renvoyer l'email.";
        }
    }, 1000);
}

// Fonction pour renvoyer l'email
function resendEmail() {
    alert("Email de vérification renvoyé."); // Exemple : traitement côté client
    startResendTimer(); // Redémarre le timer
}