=== WS Scheduler ===
Contributors: webstrategy
Tags: appointment, booking, calendar, scheduler, reservation
Requires at least: 6.5
Tested up to: 6.8
Stable tag: 4.0.1
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Complete appointment booking system — popup calendar, real-time slots, automated emails. No subscription, no external service required.

== Description ==

WS Scheduler lets visitors book appointments directly on your site. A popup calendar shows real-time availability; both parties receive an automatic confirmation email.

**Everything runs locally — no subscription, no SaaS, no data sent to external servers.**

= Free features =

* Popup booking calendar with real-time slot availability
* Configurable working hours and slot duration (15 to 120 min)
* Three unavailability modes: full period, specific slot, weekly recurring
* Automatic HTML confirmation email to the client
* Automatic notification email to the administrator
* Cancellation emails with customizable content (via Pro)
* Appointment dashboard: status management (confirmed, pending, cancelled)
* Shortcode `[ws_booking_button]` and CSS class `.ws-book-btn` for theme builders
* Multilingual (French, English, Spanish, German) — fully translatable
* Compatible with Avada, Elementor, Divi, and all major page builders
* LiteSpeed Cache, WP Rocket, W3 Total Cache, WP Super Cache compatible

= Pro version =

**WS Scheduler Pro** adds:

* **Google Calendar sync** — each booking creates a Google Calendar event automatically
* **Google Meet links** — a unique Meet link generated and included in every confirmation email
* **Custom email templates** — colors, logo, greeting and footer text configurable from the admin
* **White-label** — remove the "Powered by WebStrategy" credit from the popup and emails

[Get WS Scheduler Pro →](https://plugin.wordpress-freelance.com)

= How it works =

1. Install and activate the plugin
2. Configure your working hours in **WS Scheduler → Settings**
3. Add `[ws_booking_button]` to any page, or apply the class `.ws-book-btn` to any element in your theme builder
4. Visitors click the button → choose a date → fill in their details → receive a confirmation email

= Privacy =

All appointment data is stored in your WordPress database (tables `wp_ws_appointments` and `wp_ws_unavailabilities`). No data is transmitted to external servers. The booking JavaScript relies only on your site's own AJAX endpoint (`admin-ajax.php`).

The plugin registers WordPress's native personal data exporter and eraser (Tools → Personal Data → Export / Erase). Visitors can request the export or deletion of their booking data using their email address — WS Scheduler will return matching records. A suggested privacy policy paragraph is also added to **Settings → Privacy → Policy Guide**.

If you use the Google Calendar / Meet integration (Pro), data is transmitted to Google's APIs under the OAuth2 flow you configure.

== Installation ==

1. Upload the `ws-scheduler` folder to `/wp-content/plugins/`
2. Activate the plugin in **Plugins → Installed Plugins**
3. Go to **WS Scheduler → Settings** to configure working hours, slot duration, and notification email
4. Add the booking button with `[ws_booking_button]` or the class `.ws-book-btn`

= Minimum requirements =

* WordPress 6.5+
* PHP 7.4+
* MySQL 5.7+ / MariaDB 10.3+

== Frequently Asked Questions ==

= How do I add the booking button? =

Use the shortcode `[ws_booking_button]` in any page, post, or widget area. You can also apply the CSS class `ws-book-btn` to any existing `<button>` or `<a>` element in Avada, Elementor, Divi, or your theme's header builder.

= Can I customize the button label? =

Yes — go to **WS Scheduler → Settings → Front-office button**. You can also override per shortcode: `[ws_booking_button label="Book a call"]`.

= Does it work with caching plugins? =

Yes. The plugin is specifically designed for LiteSpeed Cache, WP Rocket, W3 Total Cache, and WP Super Cache. The booking JavaScript is injected inline to prevent cache interference with script loading order.

= Where is appointment data stored? =

Locally in your WordPress database: tables `wp_ws_appointments` and `wp_ws_unavailabilities`. No external API calls are made by the Free version.

= What does "uninstall" do? =

Uninstalling removes both database tables and all plugin options. All appointment data is permanently deleted.

= Can I block specific recurring time slots? =

Yes. Go to **WS Scheduler → Unavailabilities** and choose "Weekly recurring". Select the days and hours — for example, every Monday and Wednesday from 12:00 to 14:00.

= Is Google Calendar integration available? =

Google Calendar and Google Meet sync are available in [WS Scheduler Pro](https://plugin.wordpress-freelance.com).

= The "Powered by WebStrategy" credit appears in emails — how do I remove it? =

You can enable or disable it in **WS Scheduler → Settings → Credits**. It is off by default. Permanent removal (also from the popup) is available in WS Scheduler Pro.

== Screenshots ==

1. Admin dashboard — appointment list with status filters and counters
2. Unavailabilities manager — three input modes (full period, punctual slot, weekly recurring)
3. Settings page — working hours, slot duration, notification email, button label
4. Licence Pro page — feature overview and pricing
5. Front-end popup — Step 1: calendar with available and unavailable dates
6. Front-end popup — Step 2: booking form with recap bar
7. Confirmation screen after successful booking

== Changelog ==

= 4.0.1 =
* Added: WordPress personal data exporter and eraser hooks (Tools → Personal Data) — RGPD compliance for stored appointment PII (name, email, phone, message).
* Added: Suggested privacy policy text registered via `wp_add_privacy_policy_content` (Settings → Privacy → Policy Guide).
* Fixed: Removed `display:none` rule on `.notice` and `.updated` in admin pages — was hiding system-critical notices from other plugins / WP core (Plugin Directory guideline 11 compliance).
* Tested up to WordPress 6.8.

= 4.0.0 =
* Removed dependency on ws-connector — the Free version is now fully standalone with no external API calls
* Added opt-in display toggle for WebStrategy credit in Settings (off by default — guideline 10 compliance)
* Registered Licence Pro page with feature overview and pricing
* Fixed: `enqueue_notice_throttle` method reference removed (PHP warning)
* Added `ws_scheduler_load_google_fonts` filter to disable Google Fonts CDN if needed

= 3.9.10 =
* Inline JS to bypass aggressive cache plugin interference (LiteSpeed Cache, WP Rocket)

= 3.9.9 =
* Automatic HTML cache purge (LiteSpeed, WP Rocket, W3TC, WP Super Cache, WP Fastest Cache) on plugin activation

= 3.9.5 =
* Removed nonce check on public read endpoints (`get_month_slots`, `get_available_slots`) to prevent false 403s from cached pages with expired nonces

= 3.8.0 =
* Unavailability UX redesign: three distinct modes (full period, punctual slot, weekly recurring)
* Fixed recurring unavailability logic (was using creation datetimes instead of reconstructing for the evaluated day)
* Extended DB schema: `time_start` and `time_end` TIME columns added; idempotent migration on activation
* Day-of-week pill selector UI

= 3.7.0 =
* Security and ecosystem alignment audit

== Upgrade Notice ==

= 4.0.1 =
Adds RGPD/privacy hooks and removes an admin notice-hiding rule that violated WordPress.org guideline 11. No database changes.

= 4.0.0 =
This version removes the ws-connector/hub_connector dependency. The plugin is now fully standalone. No data migration required — upgrade safely.
