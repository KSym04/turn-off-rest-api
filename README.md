# Turn Off REST API #

**Contributors:** ksym04\
**Tags:** disable rest api, rest api, security, json, api\
**Requires at least:** 4.7\
**Tested up to:** 7.0\
**Requires PHP:** 7.4\
**Stable tag:** 1.0.5\
**License:** GPLv3\
**License URI:** [https://www.gnu.org/licenses/gpl-3.0.html](https://www.gnu.org/licenses/gpl-3.0.html)

Disable the WordPress REST API for unauthenticated visitors, with a per route allow list so you decide exactly what stays open.

## Description ##

Turn Off REST API blocks the WordPress REST API for visitors who are not logged in. Anonymous requests to your `/wp-json` endpoints receive an authentication error instead of your site data, while logged in users and your own theme and plugins keep working normally.

By default WordPress exposes a lot of information through the REST API, including the list of user accounts, published content, and details about your site. For many sites that anonymous access is unnecessary and only widens the attack surface. This plugin closes it in one click and gives you a clear settings screen to reopen only the specific routes you actually need.

### What it does ###

* Returns an authentication error for unauthenticated REST API requests.
* Removes the REST API discovery links from your site header and HTTP headers.
* Lets you build an allow list of routes that should stay public (for example a contact form or a specific integration).
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
4. Go to Settings, then Turn Off REST API to review the route allow list. Unauthenticated access is disabled by default.

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

### Does it work on nginx as well as Apache? ###

Yes. The plugin works at the WordPress request level and does not depend on any web server configuration files.

## Changelog ##

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
