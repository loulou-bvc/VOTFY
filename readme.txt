
VOTIFY
Description du projet
demo : http://webdev.iut-orsay.fr/~nboulad/VOTFY/views/login.php
VOTIFY est une plateforme web collaborative permettant la gestion de groupes, la soumission de propositions, le vote en ligne, et la consultation des résultats. L'application est conçue pour être utilisée dans des contextes variés tels que les réunions associatives, les décisions d'équipe, ou encore les consultations citoyennes.

Le projet respecte une architecture MVC (Modèle-Vue-Contrôleur) avec une organisation modulaire, intégrant des fonctionnalités telles que :

Gestion des groupes et des utilisateurs.
Soumission et vote de propositions.
Notifications en temps réel.
Accès à des statistiques et historiques des votes.
Structure du projet
bash
Copier le code
/VOTIFY
├── /api                 # Points d'accès API REST.
	├── /controllers         # Logique métier pour gérer les fonctionnalités.
	├── /Routes         # Routes de l'api
├── /config              # Configuration de la base de données et constantes globales.
├── /css                 # Feuilles de style pour l'interface utilisateur.
├── /images              # Ressources graphiques (logo, icônes, etc.).
├── /js                  # Scripts JavaScript pour l'interactivité.
├── /modeles             # Modèles d'accès aux données (interactions avec la BDD).
├── /views               # Fichiers PHP/HTML pour l'interface utilisateur.
├── .htaccess            # Fichier Apache pour le routage et la sécurité.
├── index.php            # Point d'entrée principal de l'application.
└── README.md            # Documentation du projet.
Prérequis
Technologies utilisées
Frontend : HTML5, CSS3, JavaScript.
Backend : PHP (avec PDO pour la base de données).
Base de données : MySQL.
Serveur : Apache.
Frameworks et outils :
Visual Studio Code (ou tout autre IDE).
FileZilla (pour la gestion du serveur).
phpMyAdmin (pour la gestion de la base de données).
Environnement requis
PHP version 7.4 ou supérieure.
Serveur Apache avec module mod_rewrite activé.
MySQL version 5.7 ou supérieure.
Extensions PHP : PDO, mysqli.
Accès à un serveur pour héberger les fichiers (ex. : serveur de l'école ou local via XAMPP).

Fonctionnalités principales
1. Pages statiques
Connexion : Permet à l'utilisateur de se connecter à la plateforme.
Inscription : Création d'un compte utilisateur.
Accueil : Affiche les groupes auxquels appartient l'utilisateur.
2. Pages dynamiques (basées sur SQL)
Page des groupes : Gestion des groupes, affichage des membres.
Page de propositions : Soumission et vote sur des propositions.
Page des résultats : Affichage des résultats et statistiques des votes.
3. Notifications
Notifications pour les nouvelles propositions, votes, et résultats.
Gestion des préférences utilisateur (notifications email et mobile).
4. API REST
Endpoints pour :
Récupérer la liste des groupes, propositions, et votes.
Soumettre un vote ou une proposition.
Mettre à jour les paramètres utilisateur.

Charte Graphique - VOTIFY
Introduction
La charte graphique de VOTIFY définit l'identité visuelle de la plateforme. Elle vise à garantir une expérience utilisateur cohérente et moderne, tout en reflétant les valeurs de simplicité, de collaboration et de transparence qui caractérisent l'application.

1. Couleurs
Les couleurs principales ont été choisies pour symboliser la clarté et la confiance dans le processus de vote.

Couleurs principales
Couleur	Hexadecimal	Utilisation
Bleu pastel	#A8DADC	Boutons, liens, et éléments interactifs.
Rose clair	#FFE3E3	Fonds de sections importantes ou modales.
Blanc	#FFFFFF	Fond principal pour assurer une bonne lisibilité.
Noir	#1D3557	Texte principal.
Gris clair	#F1FAEE	Arrière-plans secondaires et bordures.
2. Typographie
La typographie doit être simple et lisible pour tous les types d'utilisateurs.

Polices utilisées
Roboto : Utilisée pour les titres et sous-titres.
Open Sans : Utilisée pour le corps du texte.
Hiérarchie typographique
Niveau	Style	Taille	Utilisation
Titre principal	Roboto, Bold	24px	Titres des pages.
Sous-titre	Roboto, SemiBold	20px	Sections et sous-sections.
Texte courant	Open Sans, Normal	16px	Corps du texte principal.
Légende	Open Sans, Light	12px	Informations secondaires.
3. Icônes
Icônes principales
Des icônes simples et modernes sont utilisées pour représenter les différentes sections de l'application. Ces icônes proviennent d'une bibliothèque open-source comme FontAwesome ou Material Icons.

Icône	Utilisation
👥	Gestion des groupes.
📩	Notifications.
🗳️	Propositions et votes.
⚙️	Paramètres et configurations.
4. Images
Logos et illustrations
Logo principal : Une icône représentant une enveloppe stylisée, qui évoque le vote et la simplicité.
Illustrations : Utilisées pour dynamiser les pages d'accueil et de connexion, avec un style plat et moderne.
Format des images
Format : SVG pour des images vectorielles et adaptables.
Résolution : Minimum 300 DPI pour une qualité optimale sur tous les écrans.
5. Mise en page
Disposition
Grille à 12 colonnes : Pour assurer une cohérence dans l'espacement et l'alignement des éléments.
Marges et espaces : Une marge de 20px est appliquée autour des contenus.
Comportement responsive
Les pages sont optimisées pour s'adapter aux écrans suivants :
Mobile : Affichage en une seule colonne.
Tablette : Affichage en deux colonnes.
Desktop : Affichage complet avec un maximum de trois colonnes.
6. Boutons
Style des boutons
État	Couleur de fond	Couleur du texte	Bordure
Normal	Bleu pastel	Blanc	Aucune
Survolé	Rose clair	Noir	Aucune
Désactivé	Gris clair	Gris foncé	Aucune
Formes
Les boutons ont des coins légèrement arrondis avec un rayon de 5px pour une apparence moderne.

7. Exemples d’utilisation
Page d'accueil
Fond blanc avec une barre de navigation bleue pastel.
Boutons interactifs rose clair.
Typographie Roboto pour les titres et Open Sans pour les paragraphes.
Page de vote
En-tête avec le titre de la proposition (24px, Roboto).
Sections séparées avec un fond gris clair.
Boutons "Pour", "Contre" et "Blanc" codés en bleu, rouge et gris respectivement.
Pour le bon fonctionnement de ce application web veuillez a l'utiliser sur https://webdev.iut-orsay.fr/~nboulad
