# Vide Grenier en Ligne

Ce Readme.md est à destination des futurs repreneurs du site-web Vide Grenier en Ligne.

## Mise en place du projet back-end

1. Créez un VirtualHost pointant vers le dossier /public du site web (Apache)
2. Importez la base de données MySQL (sql/import.sql)
3. Connectez le projet et la base de données via les fichiers de configuration
4. Lancez la commande `composer install` pour les dépendances

## Mise en place du projet front-end
1. Lancez la commande `npm install` pour installer node-sass
2. Lancez la commande `npm run watch` pour compiler les fichiers SCSS

## Tests

Les tests unitaires (PHPUnit) se lancent depuis ce dossier (`src/`) :

```bash
vendor/bin/phpunit
```

Les modèles (`App/Models`) sont testés sans toucher à la vraie base MySQL : `Core\Model::setTestDB($pdo)` permet d'injecter une connexion PDO SQLite en mémoire à la place de la vraie connexion pendant les tests.

## Documentation de l'API

Les endpoints JSON exposés par [Api.php](App/Controllers/Api.php) sont documentés au format OpenAPI grâce à des attributs PHP (`#[OA\Get(...)]`) directement dans le contrôleur, via [zircote/swagger-php](https://github.com/zircote/swagger-php).

Le fichier `public/openapi.json` est **généré automatiquement** (jamais à modifier à la main) :
- à chaque `composer install` / `composer update` (hook `post-autoload-dump`)
- ou manuellement avec `composer openapi`

Une fois le projet lancé, la doc interactive (Swagger UI) est accessible sur `/api-docs/`.

Pour documenter un nouvel endpoint, ajoutez un attribut `#[OA\Get]`/`#[OA\Post]`/... au-dessus de l'action concernée dans un contrôleur, puis relancez `composer openapi`.

## Routing

Le [Router](Core/Router.php) traduit les URLs. 

Les routes sont ajoutées via la méthode `add`. 

En plus des **controllers** et **actions**, vous pouvez spécifier un paramètre comme pour la route suivante:

```php
$router->add('product/{id:\d+}', ['controller' => 'Product', 'action' => 'show']);
```


## Vues

Les vues sont rendues grâce à **Twig**. 
Vous les retrouverez dans le dossier `App/Views`. 

```php
View::renderTemplate('Home/index.html', [
    'name'    => 'Toto',
    'colours' => ['rouge', 'bleu', 'vert']
]);
```
## Models

Les modèles sont utilisés pour récupérer ou stocker des données dans l'application. Les modèles héritent de `Core
\Model
` et utilisent [PDO](http://php.net/manual/en/book.pdo.php) pour l'accès à la base de données. 

```php
$db = static::getDB();
```
