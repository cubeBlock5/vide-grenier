# Vide Grenier en Ligne

Application web qui permet à n'importe quel résident de France métropolitaine de donner gratuitement les objets qui traînent dans son grenier ou son garage : jeux de société, livres, puzzles, etc. Pas de contrepartie, pas de vente, juste du don entre particuliers.

Ce dépôt reprend le code d'un ancien prestataire (en cessation d'activité) pour le compte du client VideGrenierEnLigne. Le travail consiste à remettre le projet d'aplomb : environnement de dev propre, correction des bugs remontés par les utilisateurs, puis mise en place d'un environnement de prod.

## Bugs à corriger

Remontés directement par le client :

- [ ] Message d'erreur affiché quand aucune photo n'est jointe à une annonce (le champ photo est marqué comme requis alors qu'il ne devrait pas l'être)
- [ ] Un utilisateur qui vient de s'inscrire n'est pas connecté automatiquement après son inscription
- [ ] La case "se souvenir de moi" au login ne fait rien
- [ ] Le formulaire de contact sur la fiche produit ouvre le client mail au lieu d'afficher un vrai formulaire

## Stack

- PHP 7 — mini-framework MVC maison (pas de Symfony/Laravel), routing + controllers + models dans `Core/` et `App/`
- Twig pour les vues
- MySQL via PDO
- SCSS compilé avec node-sass
- Docker / Docker Compose pour les environnements
- PHPUnit pour les tests
- Swagger pour la doc de l'API

## Structure

```
App/
  Controllers/   Home, Product, User, Api
  Models/        Articles, Cities, User
  Views/         templates Twig
  Utility/       Hash, Upload
Core/            le "framework" : Router, Controller, Model, View
public/          point d'entrée (index.php) + assets front
sql/             dump de la base
style/           sources SCSS
```

## Environnement de dev

Prérequis : Docker + Docker Compose.

```bash
./dev.sh
```

Le script monte deux containers (serveur web + MySQL), importe `sql/import.sql` et sert le site sur `http://localhost:8080`. Les identifiants de connexion à la base sont dans `.env.dev`, à copier depuis `.env.dev.example`.

Pour le front (compilation SCSS) :

```bash
npm install
npm run watch
```

## Tests

```bash
docker compose exec web vendor/bin/phpunit
```

## Workflow Git

GitFlow : les branches de correctif/feature partent de `dev`, chaque bug ou tâche a son issue. Le passage vers `main` se fait via merge request une fois relu. `main` correspond toujours à ce qui tourne (ou doit tourner) en prod.

## Prod

Deux containers séparés : un pour MySQL avec volume persistant, un pour le serveur web avec le code de `main` déjà intégré dans l'image (pas de bind mount du code source en prod).

```bash
./prod.sh
```

Les deux environnements (dev et prod) tournent en parallèle sur la machine, chacun avec son propre `.env`.

## Doc API

Générée avec Swagger, dispo sur `/api/doc` une fois l'environnement lancé.
