=== WS Scheduler ===
Contributors: webstrategy
Tags: appointment, booking, calendar, scheduler, reservation
Requires at least: 6.5
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 4.0.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Self-hosted appointment booking with a popup calendar, real-time slot availability and automated emails. No SaaS, no subscription.

== Description ==

WS Scheduler is a complete, self-hosted appointment booking system for WordPress. Visitors click a button, pick a date and a time slot in a popup, fill in the form, and receive an automatic HTML confirmation email. The site administrator gets a notification email at the same time.

The plugin runs entirely on your own server. No third-party SaaS, no subscription, no external API call from the free version. All booking data is stored in two custom tables in your WordPress database.

The free version is fully functional and not time-limited. There is an optional [WS Scheduler Pro](https://plugin.wordpress-freelance.com) upgrade for Google Calendar / Meet sync and email branding, but the free version covers the full booking workflow on its own.

**How it works**

1. Configure your working hours, slot duration, and notification email in the plugin settings.
2. Add the booking button to your site — either via the shortcode `[ws_booking_button]` or by applying the CSS class `ws-book-btn` to any element in your theme or page builder.
3. Visitors click the button, a popup opens with a calendar showing real-time availability.
4. They select a date, a time slot, fill in their details, and submit. The appointment is saved in the database and confirmation emails are sent automatically.

**Features**

* Popup booking calendar with real-time slot availability — generated server-side from working hours, booked appointments, and configured unavailabilities
* Configurable working days, opening hours (e.g. 09:00–18:00), and slot duration (15 to 120 minutes)
* Three unavailability modes: full period (vacations), punctual slot (one specific day with start/end times), weekly recurring (e.g. every Monday from 12:00 to 14:00)
* Configurable booking window — limit how far in advance visitors can book
* Automatic HTML confirmation email to the client and notification email to the admin
* Admin dashboard with status filters (confirmed, pending, cancelled) and per-row actions
* Shortcode `[ws_booking_button label="..." class="..."]` and universal CSS class `.ws-book-btn` for any builder
* Native WordPress personal data exporter and eraser for full GDPR compliance
* Multilingual: French, English, Spanish, German bundled — fully translatable
* Compatible with all major page builders (Avada, Elementor, Divi, Bricks, Oxygen)
* Compatible with major caching plugins (LiteSpeed Cache, WP Rocket, W3 Total Cache, WP Super Cache, WP Fastest Cache) — automatic cache purge on activation, inline JavaScript to bypass aggressive script combiners
* Two filter hooks for developers: `ws_scheduler_load_google_fonts` and `ws_scheduler_after_booking`

**Pro version (optional)**

[WS Scheduler Pro](https://plugin.wordpress-freelance.com) extends the free version with two-way Google Calendar sync, automatic Google Meet link generation per appointment, customizable email templates (colors, logo, greeting and footer text), and a white-label option. The Pro upgrade is only required for these specific features — the free version is not crippled in any way.

== Installation ==

1. Install and activate the plugin from the WordPress admin (Plugins → Add New → Search "WS Scheduler"), or upload the ZIP via Plugins → Add New → Upload Plugin.
2. Go to **WS Scheduler → Settings** and configure your working days, opening hours, slot duration, and the email address that should receive admin notifications.
3. Optionally, go to **WS Scheduler → Unavailabilities** to block dates or recurring time ranges (e.g. lunch break).
4. Add the booking button to your site:
   * Drop `[ws_booking_button]` into any post, page, or widget. Override the label with `[ws_booking_button label="Book a discovery call"]`.
   * Or apply the CSS class `ws-book-btn` to any existing button or link in your theme or page builder. The popup is wired up automatically.

**Minimum requirements**

* WordPress 6.5 or later
* PHP 7.4 or later
* MySQL 5.7+ / MariaDB 10.3+

== Frequently Asked Questions ==

= How do I add the booking button? =

Two ways. Use the shortcode `[ws_booking_button]` in any post, page, or widget. Or apply the CSS class `ws-book-btn` to any element in your theme or page builder (Avada, Elementor, Divi, etc.) — the popup will open when that element is clicked.

= Can I customize the button label? =

Yes. The default label is set in **WS Scheduler → Settings → Front-office button**. You can also override it per shortcode: `[ws_booking_button label="Book a call"]`. If you use the `.ws-book-btn` class on your own element, the inner text of that element becomes the label.

= Where is appointment data stored? =

Locally in your WordPress database, in two custom tables: `wp_ws_appointments` (one row per booking) and `wp_ws_unavailabilities` (configuration data only). No external API call is made by the free version.

= Does it work with caching plugins? =

Yes. The plugin is designed for LiteSpeed Cache, WP Rocket, W3 Total Cache, WP Super Cache, and WP Fastest Cache. The booking JavaScript is inlined in the page to prevent cache plugins from breaking the popup, and all known HTML caches are purged automatically on plugin activation.

= Can I block recurring time slots, like a lunch break? =

Yes. Go to **WS Scheduler → Unavailabilities** and choose **Weekly recurring**. Select the days and a time range. The plugin will exclude those slots from availability every week.

= How do I export or delete a visitor's booking data on request? =

Go to **Tools → Personal Data → Export Personal Data** (or **Erase Personal Data**), enter the visitor's email address, and confirm. The plugin registers WordPress's native exporter and eraser, so all matching booking records are returned or deleted.

= What happens to the data when I uninstall? =

The `uninstall.php` script drops both database tables and deletes all plugin options. All appointment data is permanently removed. Deactivating the plugin without uninstalling leaves all data intact.

= Is Google Calendar integration available? =

Two-way Google Calendar sync and automatic Google Meet link generation are available in [WS Scheduler Pro](https://plugin.wordpress-freelance.com). The free version intentionally does not call any external API.

= How do I remove the "Powered by WebStrategy" credit? =

The credit is **off by default**. It only appears if you enable it in **WS Scheduler → Settings → Credits**. The toggle is provided for users who want to support the project; nothing is shown to your visitors unless you turn it on.

= Can I call the booking system programmatically? =

Yes. The static methods on `WS_Scheduler_DB` (`insert_appointment`, `get_appointment`, `update_appointment`, `delete_appointment`) and `WS_Scheduler_Slots::get_slots_for_date( $date )` can be called directly from your own PHP code. Two filter hooks are also exposed: `ws_scheduler_load_google_fonts` and `ws_scheduler_after_booking`.

== Privacy Policy ==

This plugin stores personal data submitted by visitors when they book an appointment. The data is stored **locally in your WordPress database**. The free version does not transmit any data to external servers.

**What data is stored:** first name, last name, email address, and optionally company name, phone number and message — only the fields filled in by the visitor. The selected appointment slot, status (pending, confirmed, cancelled), and a creation timestamp are also stored.

**Where the data is stored:** in two custom tables created on plugin activation — `wp_ws_appointments` (one row per booking) and `wp_ws_unavailabilities` (configuration only, no PII).

**When data is sent externally:** only when emails are sent. The plugin uses WordPress's native `wp_mail()` function, routed through whatever transport your site has configured (PHP mail, SMTP plugin, transactional service). The plugin itself does not call any third-party API in the free version.

**How long the data is retained:** indefinitely, until you manually delete an appointment from the admin dashboard or run the WordPress personal data eraser.

**Right to access and erase:** the plugin registers WordPress's native `wp_privacy_personal_data_exporters` and `wp_privacy_personal_data_erasers` filters. Visitors can request the export or deletion of their booking data via the standard WordPress data request flow at **Tools → Personal Data**. A suggested privacy policy paragraph is also registered via `wp_add_privacy_policy_content` and is available at **Settings → Privacy → Policy Guide**.

**Your responsibilities:** as the site owner, you are responsible for informing visitors that their booking data will be stored, and for obtaining any consent required by applicable law (GDPR, CCPA, etc.). We recommend updating your site's privacy policy accordingly.

**Pro version note:** if you install [WS Scheduler Pro](https://plugin.wordpress-freelance.com) and enable the Google Calendar / Meet integration, booking data is also transmitted to Google's APIs under the OAuth2 flow you configure. Google's privacy policy applies to that data: https://policies.google.com/privacy. The free version does not transmit anything to Google.

== Screenshots ==

1. Admin dashboard — appointment list with status filters and counters (upcoming, confirmed, pending, cancelled), and the in-dashboard installation help card.
2. Unavailabilities manager — three input modes (full period, punctual slot, weekly recurring) and the list of active rules.
3. Settings page — working days, opening hours, slot duration, notification email, button label, and the optional "Powered by" credit toggle (off by default).
4. Front-end popup, step 1 — calendar with available, fully-booked, and closed dates clearly distinguished, plus the time slot picker for the selected day.
5. Front-end popup, step 2 — booking form with a recap bar showing the chosen date and time.

== Changelog ==

= 4.0.2 =
* Removed the dedicated "Licence Pro" admin sub-page entirely (Plugin Directory guideline 11 — keep upsell prompts contextual, not as a top-level admin page).
* Added "Settings | Pro version" links on the plugin list page via `plugin_action_links_<basename>` (the discreet, conventional location for upsell links).
* Added an in-dashboard "How to install the booking button" help card with the shortcode and CSS class methods, side-by-side, copy-paste-ready. Open by default while there are no appointments yet, collapsible afterwards.
* Fixed: parse error in the Licence Pro partial (unescaped apostrophes in single-quoted French translation strings) — moot since the page is removed, but identified by `php -l` audit.

= 4.0.1 =
* Added: WordPress personal data exporter and eraser hooks (Tools → Personal Data) — full GDPR compliance.
* Added: Suggested privacy policy text registered via `wp_add_privacy_policy_content`.
* Added: Five 1200x900 screenshots in `assets/` for the WordPress.org plugin page.
* Added: Comprehensive unit test suite — over 200 tests covering activator, admin, ajax, db, email, loader, privacy, public, slots, and orchestrator.
* Fixed: Removed `display: none !important` rule on `.notice` and `.updated` inside the plugin's admin pages — was hiding system-critical notices from other plugins and WP core (Plugin Directory guideline 11 compliance).
* Tested up to WordPress 6.8.

= 4.0.0 =
* Removed dependency on `ws-connector` — the free version is now fully standalone with no external API call.
* Added opt-in display toggle for the "Powered by WebStrategy" credit in Settings (off by default — guideline 10 compliance).
* Registered Licence Pro page with feature overview and pricing.
* Added `ws_scheduler_load_google_fonts` filter to disable the Google Fonts CDN if needed.

= 3.9.10 =
* Inlined booking JavaScript to bypass aggressive cache plugin interference (LiteSpeed Cache, WP Rocket).

= 3.9.9 =
* Automatic HTML cache purge (LiteSpeed, WP Rocket, W3TC, WP Super Cache, WP Fastest Cache) on plugin activation.

= 3.9.5 =
* Removed nonce check on public read endpoints (`get_month_slots`, `get_available_slots`) to prevent false 403 errors when pages are served from cache with an expired nonce.

= 3.8.0 =
* Unavailability UX redesign: three distinct modes (full period, punctual slot, weekly recurring).
* Fixed recurring unavailability logic.
* Extended database schema: `time_start` and `time_end` TIME columns added; idempotent migration on activation.

= 3.7.0 =
* Security and ecosystem alignment audit.

== Upgrade Notice ==

= 4.0.2 =
Removes the dedicated Licence Pro admin sub-page (replaced by a discreet "Pro version" link on the plugin list page) and adds an in-dashboard installation help card. Fixes a parse error in the now-removed partial. No database changes.

= 4.0.1 =
Adds GDPR personal data exporter/eraser, removes an admin-side notice-hiding rule that violated WordPress.org guideline 11, and ships full screenshots. No database changes.

= 4.0.0 =
Removes the `ws-connector` dependency. The plugin is now fully standalone. No data migration required.
