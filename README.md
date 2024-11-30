# **SyncSpace API** 🚀

Bienvenue dans le dépôt de l'API serveur de **SyncSpace**, une application modulaire 🧩 conçue pour centraliser la gestion de vos outils personnels et professionnels. Organisez, collaborez et gagnez en productivité avec un seul espace de travail intelligent !

---

## **Table des Matières** 📚

1. [À propos](#à-propos)  
2. [Fonctionnalités](#fonctionnalités)  
3. [Prérequis](#prérequis)  
4. [Installation](#installation)  
5. [Configuration](#configuration)  
6. [Lancer le serveur](#lancer-le-serveur)  
7. [Documentation de l'API](#documentation-de-lapi)  
8. [Structure des dossiers](#structure-des-dossiers)  
9. [Contribuer](#contribuer)  
10. [Licence](#licence)

---

## **À propos** 🤖

Cette API, construite avec **Symfony 7.2**, est le backend de **SyncSpace**. Elle gère les fonctionnalités suivantes :

- **Authentification sécurisée** via **JWT** 🔒.
- **Gestion des utilisateurs** et des sessions 👤.
- **Module de chat** pour la communication en temps réel 💬.
- **Gestion des tâches**, du calendrier 📅 et du coffre-fort des mots de passe 🔑.
- **Drive personnel** pour l’upload et la gestion de fichiers 📂.

Le serveur est conçu pour être extensible, sécurisé, et conforme au RGPD ⚖️.

---

## **Fonctionnalités** 🎯

### **Authentification**
- Inscription et connexion d’utilisateurs 🔑.
- Génération de jetons JWT pour un accès sécurisé 🔐.
- Protection des endpoints via des middlewares 🛡️.

### **Modules disponibles** 🧩
1. **Gestion des utilisateurs** : CRUD des profils 👥.
2. **Module chat** : Envoi et réception de messages 💬.
3. **Calendrier** : Gestion d’événements avec rappels ⏰.
4. **Drive** : Upload, organisation, et partage de fichiers 📤.
5. **Gestionnaire de mots de passe** : Stockage sécurisé de mots de passe 🔐.

### **Modularité**
- API conçue pour activer ou désactiver facilement les modules selon les besoins de l’application cliente ⚙️.

---

## **Prérequis** 🛠️

Avant de commencer, assurez-vous que votre environnement dispose des éléments suivants :

- **PHP 8.2** ou supérieur 🔧.
- **Composer** installé globalement 📦.
- **PostgreSQL 13+** 📊.
- **Docker** (pour une installation conteneurisée) 🐳.
- **Node.js 18+** et **npm** (optionnel, pour gérer certains scripts d’outillage) 🌐.

---

## **Installation** ⚙️

1. **Cloner le dépôt** :  
   
bash
   git clone https://github.com/votre-utilisateur/syncspace-server.git
   cd syncspace-server

2. **Installer les dépendances Symfony** :
bash
   composer install

3. **Configurer la base de données** :  
   Ouvrez le fichier .env situé à la racine du projet et modifiez la variable DATABASE_URL avec vos informations de base de données (utilisateur, mot de passe, etc.).
   Pour un exemple concret dupliquez le fichier .env.example

4. **Créer la base de données** :  
   
bash
   php bin/console doctrine:database:create

5. **Appliquer les migrations** :
bash
   php bin/console doctrine:migrations:migrate

6. **Générer les clés JWT** :  
   Créez les clés nécessaires pour l'authentification JWT en exécutant les commandes suivantes dans votre terminal. Ces clés seront utilisées pour sécuriser les échanges d’informations entre le frontend et le backend.
   ### Ajouter

---

## **Lancer le serveur** 🚀

Pour démarrer le serveur localement :

1. Si vous utilisez **PHP intégré** :
   
bash
   php bin/console server:run

2. Si vous utilisez **Docker** :
bash
   docker compose up --build

L’API sera accessible à l’adresse suivante : http://localhost:8000

---

## **Documentation de l'API** 📄

L’API est documentée sur une page accessible gratuitement. Une interface interactive est disponible à :
/docs (par exemple : [http://localhost:8000/api/docs](http://localhost:8000/api/docs)).

### **Exemples d’endpoints principaux** 🔑
- POST /register : Inscription d’un utilisateur.  
- POST /login : Authentification et récupération d’un JWT.  
- GET /chat/conversations : Liste des conversations.  
- POST /chat/send : Envoi d’un message.  
- GET /calendar/events : Liste des événements dans le calendrier.

---

## **Structure des dossiers** 📂

- **src/** : Contient le code principal de l’application.
  - **Controller/** : Endpoints de l’API.
  - **Entity/** : Définition des modèles de données.
  - **Repository/** : Requêtes avancées pour les entités.
  - **Service/** : Logique métier réutilisable.
- **config/** : Configuration des services, routes, et autres paramètres.  
- **migrations/** : Scripts de migration de la base de données.  
- **tests/** : Tests unitaires et fonctionnels.  

---

## **Contribuer** 💡

1. Forkez le projet.  
2. Créez une branche pour vos modifications :
   
bash
   git checkout -b [module]:feature/nouvelle-fonctionnalite
3. Soumettez une pull request après validation de vos changements.

---
## Licence 📜

Ce projet est sous licence MIT. Vous êtes libre de l’utiliser, de le modifier et de le distribuer tant que vous respectez les termes de la licence.

---
🌟 Merci de contribuer à SyncSpace! 🌟
