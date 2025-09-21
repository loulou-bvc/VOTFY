
# VOTFY

VOTFY est une plateforme web collaborative de vote/propositions/groupes, conçue pour des usages tels que les réunions associatives, les décisions d’équipe, ou les consultations citoyennes.
demo : http://webdev.iut-orsay.fr/~nboulad/VOTFY/views/login.php
---

## 🧰 Fonctionnalités principales

- Gestion des utilisateurs et des groupes  
- Soumission de propositions  
- Vote en ligne  
- Consultation des résultats / statistiques des votes  
- Interface modulable avec architecture MVC  
- Notifications possibles (selon cas d’usage)  

---

## 🛠️ Tech stack & prérequis

| Élément | Détail |
|---|---|
| Backend | PHP (avec PDO) |
| Frontend | HTML5, CSS3, JavaScript |
| Base de données | MySQL |
| Serveur requis | Apache (avec module `mod_rewrite`) |
| Version de PHP | 7.4 ou supérieure |
| Version de MySQL | 5.7 ou supérieure |

---

## 📚 Structure du projet

```
/VOTFY
├── api/                  # Endpoints / logique API
├── controllers/          # Contrôleurs : logique métier
├── config/               # Configuration de la base de données, constantes globales
├── css/                  # Styles CSS de l’interface
├── images/               # Logos, icônes, images statiques
├── js/                   # Scripts JavaScript client
├── modeles/              # Modèles accès données (BDD)
├── uploads/              # Fichiers uploadés
├── vendor/               # Librairies externes
├── views/                # Vues PHP/HTML
├── .htaccess             # Configuration serveur / redirections
├── db_informations.txt   # Informations de configuration de la base de données
├── index.php             # Point d’entrée de l’application
└── README.md             # Ce fichier
```

---

## ⚙️ Installation / configuration

1. Cloner le dépôt  
   ```bash
   git clone https://github.com/loulou-bvc/VOTFY.git
   cd VOTFY
   ```

2. Configurer la base de données MySQL  
   - Créer une base  
   - Importer le fichier `db_informations.txt` si nécessaire et adapter les paramètres (hôte, utilisateur, mot de passe, nom de la base)  

3. Assurer que PHP ≥ 7.4, MySQL ≥ 5.7, Apache avec `mod_rewrite` activé  

4. Mettre les fichiers du projet dans le dossier racine du serveur web (ex : `htdocs` ou `www`)  

5. Vérifier les permissions sur les dossiers d’uploads / vendor si besoin  

---

## 🚀 Usage

- Accéder à l’application via `index.php`  
- S’inscrire / se connecter  
- Créer / rejoindre des groupes  
- Soumettre des propositions  
- Voter, consulter les résultats  

---

## 📋 À améliorer / pistes futures

- Authentification + permissions plus fines (roles)  
- Notifications temps réel (email / WebSocket)  
- Amélioration de l’UI/UX responsive  
- Ajout de tests (unitaires / intégration)  
- Internationalisation  

---

## 🤝 Contribuer

Si tu veux aider :

- Fork ce dépôt  
- Crée une branche pour ta feature (`feature/nom-feature`)  
- Commit tes changements et push  
- Ouvre une Pull Request  

---

## 📜 Licence

MIT / à définir selon ce que tu veux.
