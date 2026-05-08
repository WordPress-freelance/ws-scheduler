=== WS Scheduler ===
Contributors: webstrategy
Tags: booking, appointments, calendar, scheduling, email
Requires at least: 6.5
Requires PHP: 7.4
Tested up to: 6.5
Stable tag: 4.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Système complet de prise de rendez-vous avec popup calendrier, formulaire et emails automatiques.

== Description ==

WS Scheduler offre une solution simple et complète pour gérer les rendez-vous sur votre site WordPress. Installez le plugin, configurez vos paramètres, et laissez vos clients réserver via un calendrier élégant et responsive.

**Fonctionnalités principales :**
- Popup calendrier responsive et élégante
- Formulaire de réservation configuré
- Emails de confirmation et annulation automatiques
- Gestion flexible des heures travaillées et jours
- Indisponibilités (période complète, créneau ponctuel, ou récurrence hebdomadaire)
- Durée de créneau personnalisable
- Tableau de bord avec aperçu des rendez-vous
- Support multilingue (Français, Allemand, Espagnol)

== Installation ==

1. **Depuis WordPress.org:**
   - Allez dans Plugins → Ajouter
   - Cherchez "WS Scheduler"
   - Cliquez sur "Installer maintenant"
   - Activez le plugin

2. **Manuel (via ZIP):**
   - Téléchargez le plugin
   - Décompressez-le dans `/wp-content/plugins/`
   - Allez dans l'admin WordPress
   - Activez "WS Scheduler"

**Configuration initiale :**
1. Allez dans Réglages → WS Scheduler
2. Configurez vos heures travaillées (par défaut : 09:00-18:00)
3. Définissez la durée de créneau (par défaut : 30 minutes)
4. Entrez votre email admin
5. Donnez un nom à votre activité/entreprise
6. Insérez le shortcode `[ws_booking_button]` dans une page ou un article
7. (Optionnel) Gérez les indisponibilités via Réglages → WS Scheduler → Indisponibilités

== Usage ==

**Shortcode :**
```
[ws_booking_button]
```

Vous pouvez ajouter ce shortcode dans :
- Pages et articles (éditeur classique)
- Widgets texte
- Constructeurs visuels (Avada, Elementor, etc.)

**Tableau de bord :**
- Consultez tous vos rendez-vous en attente, confirmés ou annulés
- Modifiez ou annulez directement depuis l'admin
- Les clients reçoivent automatiquement une notification par email

== Screenshots ==

1. **Popup calendrier** - Interface popup responsive pour la sélection de date
2. **Formulaire de réservation** - Formulaire simple avec champs configurés
3. **Tableau de bord** - Liste de tous les rendez-vous avec statuts
4. **Paramètres** - Page de configuration des heures et durée de créneau
5. **Indisponibilités** - Gestion des jours/plages fermés

== Frequently Asked Questions ==

= Puis-je personnaliser les emails ? =
Actuellement, les emails utilisent un template standard. Une future version Pro offrira la personnalisation complète des templates et couleurs.

= Comment annuler un rendez-vous ? =
Les clients peuvent demander l'annulation via le lien fourni dans l'email de confirmation. Les administrateurs peuvent annuler directement depuis le tableau de bord. L'annulation envoie automatiquement une notification au client.

= Puis-je définir plusieurs calendriers ? =
Pour le moment, WS Scheduler gère un seul calendrier par site. Pour plusieurs activités, vous pouvez utiliser plusieurs sites ou une future version Pro.

= Les clients doivent-ils se connecter ? =
Non. WS Scheduler permet les réservations sans inscription. Les clients reçoivent une confirmation par email.

= Quel fuseau horaire est utilisé ? =
WS Scheduler utilise le fuseau horaire configuré dans les réglages généraux de WordPress (Réglages → Général).

= Comment importer les rendez-vous existants ? =
Une future version Pro inclura un export/import CSV. Pour l'instant, les rendez-vous doivent être ajoutés manuellement via l'admin.

= Puis-je synchroniser avec Google Calendar ? =
La synchronisation Google Calendar est une fonctionnalité de la version Pro (en développement).

= Comment modifier les textes affichés ? =
Tous les textes sont internationalisables (i18n) et peuvent être traduits ou modifiés via des plugins comme Loco Translate. Vous pouvez aussi modifier le language du site dans Réglages → Général → Langue du site.

== Changelog ==

= 4.0.0 =
- **Major refactoring** : Plugin autonome, suppression de la dépendance Hub Connector
- Suppression de la page Licence Pro
- Ajout de tests PHPUnit avec WP_Mock
- Ajout de GitHub Actions CI pour les tests automatiques
- Amélioration générale de l'architecture
- Conformité stricte avec les directives WordPress.org

= 3.9.10 =
- JS inliné pour bypass des plugins de cache agressifs (LiteSpeed, WP Rocket)

= 3.9.5 =
- Fix : Suppression du nonce sur les endpoints de lecture publique (get_month_slots, get_available_slots)
- Fix : Nonce conservé sur l'endpoint book_appointment (mutation)

= 3.9.0 =
- Migration vers WS Connector standalone

= 3.8.0 =
- Refonte UX des indisponibilités
- 3 modes de saisie : période, créneau ponctuel, récurrence hebdomadaire
- Fix : Récurrence hebdomadaire ne fonctionnait pas correctement
- Extension du schéma BDD : ajout colonnes time_start et time_end

== Support ==

Pour toute question ou problème :
- Forum WordPress.org : https://wordpress.org/support/plugin/ws-scheduler/
- Documentation : https://wordpress-freelance.com/plugins/ws-scheduler/

== License ==

WS Scheduler est distribué sous la license GNU General Public License v2.0 ou ultérieur.

== Credits ==

Développé par **WebStrategy** — Expertise WordPress, SEO et GEO depuis 2005.
https://wordpress-freelance.com

Traductions contribuées par la communauté WordPress.
