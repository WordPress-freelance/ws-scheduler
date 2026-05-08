# WS Scheduler

Système de prise de rendez-vous complet pour WordPress avec popup calendrier, formulaire et emails automatiques.

## Features

- Popup calendrier responsive
- Formulaire de réservation configuré
- Emails de confirmation et annulation automatiques
- Gestion des heures travaillées et jours
- Indisponibilités (période complète, créneau ponctuel, récurrence hebdomadaire)
- Durée de créneau personnalisable

## Installation

1. Activez le plugin via le tableau de bord WordPress
2. Allez dans Réglages → WS Scheduler
3. Configurez vos heures travaillées, durée de créneau, email admin
4. Insérez le shortcode `[ws_booking_button]` dans une page ou un article

## Développement

### Prérequis

- PHP 7.4+
- Composer

### Setup local

```bash
composer install
```

### Tests

```bash
# Tests sans coverage
composer test

# Tests avec coverage
composer test:coverage
```

### Build

Le plugin est automatiquement packagé à chaque push via GitHub Actions.

## Licence

GPL-2.0+
