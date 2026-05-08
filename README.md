# WS Scheduler

[![Tests](https://github.com/WordPress-freelance/ws-scheduler/actions/workflows/tests.yml/badge.svg)](https://github.com/WordPress-freelance/ws-scheduler/actions/workflows/tests.yml)
[![PHP 7.4 → 8.2](https://img.shields.io/badge/PHP-7.4_%E2%86%92_8.2-7C5CBF)](https://github.com/WordPress-freelance/ws-scheduler/actions)
[![License: GPL-2.0+](https://img.shields.io/badge/license-GPL--2.0%2B-7C5CBF)](https://www.gnu.org/licenses/gpl-2.0.html)

Système de prise de rendez-vous pour WordPress, sans abonnement, sans dépendance externe.
Popup calendrier avec disponibilités en temps réel, formulaire de réservation, emails HTML automatiques au client et à l'administrateur.

> Compatible Avada, Elementor, Divi, et tous les principaux page builders. Compatible LiteSpeed Cache, WP Rocket, W3 Total Cache, WP Super Cache.

---

## Fonctionnalités (version Free)

- Popup booking calendrier — disponibilités en temps réel
- Horaires d'ouverture, durée des créneaux configurables (15 à 120 min)
- Trois modes d'indisponibilité : période complète, créneau ponctuel, récurrence hebdomadaire
- Email HTML de confirmation au client + notification admin
- Tableau de bord admin — gestion du statut (confirmé / en attente / annulé)
- Shortcode `[ws_booking_button]` et classe CSS `.ws-book-btn` pour les builders
- Multilingue (FR/EN/ES/DE), entièrement traduisible
- Hooks RGPD natifs : `wp_privacy_personal_data_exporters` et `wp_privacy_personal_data_erasers` branchés

## Version Pro

[WS Scheduler Pro](https://plugin.wordpress-freelance.com) ajoute :

- Synchronisation Google Calendar
- Génération de liens Google Meet par rendez-vous
- Templates email personnalisables (couleurs, logo, textes)
- White-label : retrait du crédit "Powered by WebStrategy"

## Installation

```
1. Télécharger le ZIP depuis WordPress.org ou la page Releases.
2. Plugins → Ajouter → Téléverser → Activer.
3. WS Scheduler → Réglages → configurer horaires, durée, email admin.
4. Placer [ws_booking_button] sur une page, OU appliquer .ws-book-btn à un bouton dans le builder.
```

## Architecture

WPPB strict : orchestrateur → loader → admin/public/ajax/db/slots/email/privacy.

```
ws-scheduler/
├── ws-scheduler.php              # entry point + activation hooks
├── uninstall.php                 # cleanup tables + options
├── includes/
│   ├── class-ws-scheduler.php           # orchestrateur
│   ├── class-ws-scheduler-loader.php    # routing actions/filters/shortcodes
│   ├── class-ws-scheduler-activator.php
│   ├── class-ws-scheduler-deactivator.php
│   ├── class-ws-scheduler-i18n.php
│   ├── class-ws-scheduler-db.php        # appointments + unavailabilities
│   ├── class-ws-scheduler-slots.php     # moteur de slots
│   ├── class-ws-scheduler-email.php     # templates HTML
│   ├── class-ws-scheduler-ajax.php      # endpoints AJAX
│   └── class-ws-scheduler-privacy.php   # exporter / eraser RGPD
├── admin/
│   ├── class-ws-scheduler-admin.php
│   └── partials/                # dashboard, settings, unavail, licence
├── public/
│   ├── class-ws-scheduler-public.php
│   └── partials/                # button + popup
└── tests/                       # 129 tests PHPUnit + WP_Mock
```

## Hooks PHP

### Filters

| Filter | Default | Description |
|---|---|---|
| `ws_scheduler_load_google_fonts` | `true` | Désactiver le chargement de Lora + Inter depuis Google Fonts (RGPD strict). |
| `ws_scheduler_after_booking` | `''` | Permet d'injecter un lien Meet ou autre après création du RDV. Reçoit `($meet_link, $appointment)`. |

### Actions natives WP utilisées

- `wp_privacy_personal_data_exporters` — Export RGPD (Outils → Données personnelles)
- `wp_privacy_personal_data_erasers` — Effacement RGPD
- `admin_init` → `wp_add_privacy_policy_content` — Texte suggéré pour la politique de confidentialité

## Conformité WordPress.org

Audit sur les [18 guidelines du Plugin Directory](https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/) :

- GPL-2.0+ (guideline 1)
- Pas de trialware — version Free entièrement fonctionnelle (5)
- Pas de tracking sans consentement (7)
- Pas d'exécution de code tiers ; CDN uniquement pour les fonts, désactivable (8)
- "Powered by" optionnel et OFF par défaut (10)
- Pas de hijacking dashboard, pas de masquage de notices système (11)
- Hooks RGPD branchés
- jQuery via dépendance déclarée — pas de copie embarquée (13)

## Tests

```bash
composer install
composer test                # phpunit --no-coverage
composer test:coverage       # avec rapport Clover + HTML
```

Matrix CI : PHP 7.4 / 8.0 / 8.1 / 8.2 — 129 tests, 264 assertions.

## Licence

GPL-2.0-or-later — © WebStrategy
