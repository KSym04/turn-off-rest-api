=== Turn Off REST API ===
Contributors: ksym04
Tags: disable rest api, rest api, security, json, wp-json
Requires at least: 4.7
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.1.1
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Disable the WordPress REST API for logged out visitors and lock down your /wp-json endpoints, with a per route allow list so you stay in control.

== Description ==

**Turn Off REST API** is a lightweight WordPress security plugin that disables the WordPress REST API for visitors who are not logged in. Anonymous requests to your `/wp-json` endpoints receive an authentication error instead of your site data, while logged in users, your theme, and your plugins keep working normally.

By default WordPress exposes a large amount of information through the REST API, including your list of user accounts and usernames, published content, and details about your site. For most sites that open, unauthenticated access is unnecessary and only widens the attack surface for user enumeration and content scraping. Turn Off REST API closes the WordPress REST API to the public in one click, then gives you a clear settings screen to reopen only the specific REST API routes you actually need.

= Why turn off the WordPress REST API? =

* Stop anonymous user enumeration through `/wp-json/wp/v2/users`.
* Reduce your attack surface against REST API based exploits and bots.
* Keep your content and site data from being scraped through the public API.
* Stay in control with a per route allow list instead of an all or nothing switch.

= What it does =

* Returns an authentication error for unauthenticated REST API requests.
* Optionally removes the REST API discovery links and headers from your page source.
* Lets you build an allow list of routes that should stay public (for example a contact form or a specific integration).
* Adds a Site Health check so the restriction is clearly explained and never mistaken for a fault.
* Keeps the admin area, the block editor, and logged in functionality fully working.

= Built for control, not breakage =

Some security plugins disable the REST API completely and break the block editor or third party integrations in the process. Turn Off REST API only blocks unauthenticated access, and the per route allow list means you can whitelist exactly the endpoints a service needs without opening the whole API back up.

= Developer friendly =

The access decision runs through the `tora_grant_rest_api` filter, so developers can extend or override the logic for custom roles, application passwords, or trusted requests.

== Installation ==

1. In your WordPress admin, go to Plugins, then Add New.
2. Search for "Turn Off REST API".
3. Click Install Now, then Activate.
4. Go to Settings, then Turn Off REST API to review the route allow list. Unauthenticated access is disabled by default, so there is nothing else you need to do.

Manual installation:

1. Download the plugin zip from WordPress.org.
2. Upload the `turn-off-rest-api` folder to `/wp-content/plugins/`.
3. Activate the plugin through the Plugins menu in WordPress.

== Frequently Asked Questions ==

= How do I confirm the REST API is blocked? =

Log out of your site (or open a private browser window) and visit `https://your-site.com/wp-json`. You should see an authentication error instead of a list of routes and data. Logged in users will still see the normal response.

= Will this break the block editor (Gutenberg)? =

No. The block editor runs as a logged in user, so it keeps full REST API access. Only unauthenticated requests are blocked.

= I need one endpoint to stay public. Can I allow just that route? =

Yes. Open Settings, then Turn Off REST API, check the route or namespace you want to keep open, and save. Everything else stays blocked.

= Does it work on nginx as well as Apache? =

Yes. The plugin works at the WordPress request level and does not depend on any web server configuration files.

= Can developers customize who is allowed? =

Yes. Use the `tora_grant_rest_api` filter to return true or false based on your own logic. By default it returns whether the current user is logged in.

== Screenshots ==

1. The settings screen, where you can allow specific REST API routes while everything else stays blocked.

== Changelog ==

= 1.1.1 =
* New - A "More on DopeThemes" panel on the settings screen with free plugins, code snippets, themes, and tutorials. No change to how the REST API is protected.

= 1.1.0 =
* New - Site Health check that confirms the REST API is intentionally restricted, so it is never mistaken for an error.
* New - Option to show or hide the REST API discovery links and headers in your page source.
* Tweak - Clearer settings screen with a protection status and a dedicated options section.

= 1.0.5 =
* Tweak - Confirmed compatibility with WordPress 7.0.
* Fix - PHP 8 compatibility: resolved an undefined array key warning during REST route detection.
* Fix - Hardened output escaping on the settings screen.
* Fix - Corrected the internationalization of the authentication error message.
* Tweak - Added Requires PHP header and refreshed the plugin documentation.

= 1.0.4 =
* New - Update license to GPLv3
* Tweak - Compatibility with WP 5+
* Tweak - Update language file
* Tweak - Minor improvements

= 1.0.3 =
* Tweak - Added en_US language file
* Tweak - Added license file
* Tweak - Minor code clean up

= 1.0.2 =
* Tweak - Added endpoints admin page
* Tweak - Minor improvements

= 1.0.1 =
* Tweak - Minor improvements
* Tweak - Optimized filter implementation

= 1.0.0 =
* Initial Release

== Upgrade Notice ==

= 1.1.1 =
Adds a More on DopeThemes panel to the settings screen. No functional changes to the REST API protection.

= 1.1.0 =
Adds a Site Health check and an option to control the REST API discovery links and headers. A safe, additive update.

= 1.0.5 =
Compatibility and security update: PHP 8 fix, WordPress 7.0 support, and hardened admin escaping. Recommended for all users.
