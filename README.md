# 🚀 JADCoreEngine - OpenCore

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![PHP Version](https://img.shields.io/badge/PHP-8.2%2B-blue.svg)](https://php.net)

**JADCoreEngine** est un micro-framework PHP haute performance, conçu pour être ultra-léger, sans dépendances externes (`0 vendor` au cœur). Cette version **OpenCore** contient les fondations architecturales du moteur.

> [!IMPORTANT]
> Ce dépôt contient uniquement le **Cœur (Core)** du framework. Pour obtenir l'arborescence complète, le système de templates, le Starter Kit UI (Tailwind/TS) et le support Docker, rendez-vous sur [jadeveloppement.fr](https://jadeveloppement.fr).

---

## 🏗️ Ce que contient cet OpenCore

Le dossier `config/` regroupe les composants essentiels pour bâtir une application MVC moderne :

* **Service Container** : Gestion de l'injection de dépendances et des singletons.
* **Custom Router** : Gestion des routes avec support des Middlewares.
* **Facades (Auth, Role, Log, etc.)** : Accès simplifié aux services globaux via des interfaces statiques.
* **Collection Engine** : Manipulation avancée de tableaux (Map, Filter, SortBy, Pluck) inspirée de Laravel.
* **Validator** : Système de validation de données robuste.

---

## 🛠️ Aperçu du Code

### Utilisation des Collections
```php
use Config\Facades\Support\Collection;

$users = new Collection([
    ['id' => 1, 'name' => 'Jalal', 'role' => 'admin'],
    ['id' => 2, 'name' => 'Alex', 'role' => 'user']
]);

$admins = $users->filter(fn($u) => $u['role'] === 'admin')->pluck('name');
```

### Utilisation des Routes
```php
Route::get('/profile', [ProfileController::class, 'index'], [AuthMiddleware::class]);
Route::post('/update', [PostController::class, 'updatePost'], [AuthMiddleware::class]);

Route::middlewares([AuthMiddleware::class], function() {
  Route::post('/update', [PostController::class, 'updatePost']);

  Route::controllers(PostController::class, function() {
    Route::post('/update', 'updatePost');
  });
});
```

## 🎯 Pourquoi choisir JADCoreEngine ?
* **Performance brute :** Aucune surcharge de librairies tierces.
* **Maîtrise totale :** Comprenez exactement comment votre code interagit avec le serveur.
* **Légèreté :** Idéal pour les micro-services, les API rapides ou les MVP qui doivent charger en un clin d'œil.

## 🚀 Passer à la vitesse supérieure
Vous voulez déployer un projet complet en moins de 5 minutes ? Découvrez nos versions Premium :
| Feature | OpenCore | StudentCore | Starter Kit |
| :--- | :---: | :---: | :---: |
| **Core Facades** | ✅ | ✅ | ✅ |
| **Full MVC Directory** | ❌ | ✅ | ✅ |
| **Docker Compose** | ❌ | ✅ | ✅ |
| **Auth UI (Tailwind)** | ❌ | ❌ | ✅ |
| **Dashboard & Profile** | ❌ | ❌ | ✅ |

## 📄 Licence
Ce projet est sous licence MIT. Vous pouvez l'utiliser, le modifier et le distribuer librement pour vos besoins personnels ou commerciaux.

## Développé avec ❤️ par Jalal AISSAOUI (JADeveloppement)
