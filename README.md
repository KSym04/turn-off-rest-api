# Turn Off REST API #

**Contributors:** ksym04\
**Tags:** disable rest api, rest api, security, json, wp-json\
**Requires at least:** 4.7\
**Tested up to:** 7.1\
**Requires PHP:** 7.4\
**Stable tag:** 1.1.4\
**License:** GPLv3\
**License URI:** [https://www.gnu.org/licenses/gpl-3.0.html](https://www.gnu.org/licenses/gpl-3.0.html)

Disable the WordPress REST API for logged out visitors and lock down your /wp-json endpoints, with a per route allow list so you stay in control.

## Description ##

Turn Off REST API blocks the WordPress REST API for visitors who are not logged in. Anonymous requests to your `/wp-json` endpoints receive an authentication error instead of your site data, while logged in users, the block editor, and your admin area keep working normally.

Because every route is blocked for logged out visitors by default, features that call the REST API on behalf of visitors stop working for them until you allow their routes. This includes the WooCommerce Cart and Checkout blocks and Contact Form 7 form submissions. The FAQ below shows exactly which routes to allow.

By default WordPress exposes a lot of information through the REST API, including the list of user accounts, published content, and details about your site. For many sites that anonymous access is unnecessary and only widens the attack surface. This plugin closes it in one click and gives you a clear settings screen to reopen only the specific routes you actually need.

### What it does ###

* Returns an authentication error for unauthenticated REST API requests.
* Optionally removes the REST API discovery links and headers from your page source.
* Lets you build an allow list of routes that should stay public (for example a contact form or a specific integration).
* Adds a Site Health check so the restriction is clearly explained and never mistaken for a fault.
* Keeps the admin area, the block editor, and logged in functionality fully working.

### Built for control, not breakage ###

Some security plugins disable the REST API completely and break the block editor or third party integrations in the process. Turn Off REST API only blocks unauthenticated access, and the per route allow list means you can whitelist exactly the endpoints a service needs without opening the whole API back up.

### Developer friendly ###

The access decision runs through the `tora_grant_rest_api` filter, so developers can extend or override the logic for custom roles, application passwords, or trusted requests.

```php
// Example: also grant access to requests carrying a valid application password.
add_filter( 'tora_grant_rest_api', function ( $granted ) {
	return $granted; // return true to allow, false to block.
} );
```

## Installation ##

1. In your WordPress admin, go to Plugins, then Add New.
2. Search for "Turn Off REST API".
3. Click Install Now, then Activate.
4. Go to Settings, then Turn Off REST API to review the route allow list. Unauthenticated access is disabled by default. If your site uses the WooCommerce Cart or Checkout blocks or Contact Form 7, allow their routes as described in the FAQ.

Manual installation:

1. Download the plugin zip from WordPress.org.
2. Upload the `turn-off-rest-api` folder to `/wp-content/plugins/`.
3. Activate the plugin through the Plugins menu in WordPress.

## Frequently Asked Questions ##

### How do I confirm the REST API is blocked? ###

Log out of your site (or open a private browser window) and visit `https://your-site.com/wp-json`. You should see an authentication error instead of a list of routes and data. Logged in users will still see the normal response.

### Will this break the block editor (Gutenberg)? ###

No. The block editor runs as a logged in user, so it keeps full REST API access. Only unauthenticated requests are blocked.

### I need one endpoint to stay public. Can I allow just that route? ###

Yes. Open Settings, then Turn Off REST API, check the route or namespace you want to keep open, and save. Everything else stays blocked.

### My WooCommerce cart or checkout, or my Contact Form 7 form, stopped working for visitors. How do I fix it? ###

These features send REST API requests on behalf of logged out visitors, and the plugin blocks every route for visitors until you allow it. The WooCommerce Cart and Checkout blocks use the routes under `/wc/store/v1`. Contact Form 7 sends each form submission through its own routes under `/contact-form-7/v1`.

1. Go to Settings, then Turn Off REST API.
2. Under Allowed REST API Routes, find the `/wc/store/v1` heading and check its box. This also checks every route listed under it. The separate `/wc/store` heading above it is not used by the blocks.
3. For Contact Form 7, find the `/contact-form-7/v1` heading and check only the three routes under it that end in `/feedback`, `/feedback/schema`, and `/refill`.
4. Click Save Changes.

Only the routes you check are opened, and everything else stays blocked. If a later WooCommerce or Contact Form 7 update adds a new route, come back to this screen and check it as well.

### Does it work on nginx as well as Apache? ###

Yes. The plugin works at the WordPress request level and does not depend on any web server configuration files.

## Changelog ##

### 1.1.4 ###

* Tested with WordPress 7.1.2.
* Fixed - security: when another security plugin or your own code had already blocked a REST API request from a logged out visitor, this plugin could lift that block and let the request through. The earlier block is now always kept.
* Fixed - the option to hide REST API discovery links and headers now also removes the REST API link that WordPress sends in the headers of every page. Before, only the links in the page source were removed.
* Fixed - the settings screen no longer stops with an error when a save request contains malformed route data. Such a request is treated like one with no routes ticked.
* Tweak - removed support code for WordPress versions older than 4.7, which the plugin already required.

### 1.1.3 ###

* Fixed - WordPress 6.7 and newer logged a "translation loading was triggered too early" notice for this plugin on sites with debugging enabled. The plugin name was being translated while the plugin loaded, before WordPress is ready to serve translations.
* Tweak - the version used to cache bust the settings screen assets now comes from a single source, so it can never fall out of step with the plugin version again.
* No change to how the REST API is protected.

### 1.1.2 ###

* Tested with WordPress 7.1.
* Fixed - a PHP notice on PHP 8.2 and newer, caused by a plugin property being created on the fly instead of being declared. On a future PHP 9 this would have stopped the plugin from loading.
* Fixed - the settings screen stylesheet and script were still labelled with the previous version number, so browsers could keep serving the old cached files after an update.
* No change to how the REST API is protected.

### 1.1.1 ###

* New - A "More on DopeThemes" panel on the settings screen with free plugins, code snippets, themes, and tutorials. No change to how the REST API is protected.

### 1.1.0 ###

* New - Site Health check that confirms the REST API is intentionally restricted, so it is never mistaken for an error.
* New - Option to show or hide the REST API discovery links and headers in your page source.
* Tweak - Clearer settings screen with a protection status and a dedicated options section.

### 1.0.5 ###

* Tweak - Confirmed compatibility with WordPress 7.0.
* Fix - PHP 8 compatibility: resolved an undefined array key warning during REST route detection.
* Fix - Hardened output escaping on the settings screen.
* Fix - Corrected the internationalization of the authentication error message.
* Tweak - Added Requires PHP header and refreshed the plugin documentation.

### 1.0.4 - Mar 26, 2019 ###

* New - Update license to GPLv3
* Tweak - Compatibility with WP 5+
* Tweak - Update language file
* Tweak - Minor improvements

### 1.0.3 - Nov 7, 2017 ###

* Tweak - Added en_US language file
* Tweak - Added license file
* Tweak - Minor code clean up

### 1.0.2 - Sep 27, 2017 ###

* Tweak - Added endpoints admin page
* Tweak - Minor improvements

### 1.0.1 - Mar 23, 2017 ###

* Tweak - Minor improvements
* Tweak - Optimized filter implementation

### 1.0.0 - Mar 23, 2017 ###

* Initial Release
