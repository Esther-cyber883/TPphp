# EliteTech — Plateforme e-commerce PHP

Application e-commerce simple développée en PHP pur et MySQL.

## Fonctionnalités

- Inscription / connexion sécurisée (hash bcrypt)
- Gestion des sessions utilisateur
- Catalogue produit avec filtres par catégorie
- Pages produit détaillées
- Panier avec modification des quantités et suppression
- Validation de commande avec informations de livraison
- Historique des commandes côté client
- Espace administrateur pour gérer produits, catégories, commandes et utilisateurs
- Upload d'images de produit via le back-office

## Installation

1. Copier le dossier dans `c:\wamp64\www\site3`.
2. Démarrer Apache et MySQL via WAMP.
3. Ouvrir le navigateur : `http://localhost/site3/accueil.php`.
4. La base de données est créée automatiquement par `config.php`.

## Fichiers importants

- `config.php` : connexion PDO + création des tables + gestion des pages publiques
- `login.php` / `inscription.php` : authentification client
- `index.php` : catalogue produit
- `panier.php` : panier d'achat
- `commander.php` : validation de commande
- `mes-commandes.php` : historique client
- `admin.php` : administration du catalogue et des commandes
- `logout.php` : déconnexion

## Points de conformité

- Utilisation de PDO avec requêtes préparées
- Mots de passe hashés via `password_hash`
- Gestion des rôles `client` / `admin`
- Page publique `a-propos.php` ajoutée
- `.gitignore` présent pour exclure `config.php` et `uploads/`

## Notes

- Le dossier `uploads/` est utilisé pour les images produits uploadées depuis l'administration.
- Les commandes sont simulées ; le paiement est fictif mais le statut est enregistré.


## Createurs et
## 👥 Collaborateurs

- KPEGLO Esther
- TCHABO Emmanuel