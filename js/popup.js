// Fonction pour afficher le pop-up
function showPopup() {
    const popup = document.getElementById('popup');
    popup.style.display = 'flex'; // Affiche le pop-up
}

// Fonction pour fermer le pop-up
function closePopup() {
    const popup = document.getElementById('popup');
    popup.style.display = 'none'; // Cache le pop-up
}

// Fonction pour gérer la soumission du formulaire
function handleSubmit(event) {
    event.preventDefault(); // Empêche l'envoi réel du formulaire
    showPopup(); // Affiche le pop-up
}
