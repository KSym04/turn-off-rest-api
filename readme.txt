=== Turn Off REST API ===
Contributors: ksym04
Tags: api, json, REST, admin, turn off, disable
Requires at least: 4.7
Tested up to: 4.7
Stable tag: 1.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Turn off JSON REST API on your website to anonymous users and prevent unauthorized requests from using the REST API to get information from your website.

== Description ==

While WordPress REST API vulnerability exploits continue this plugin effectively prevent and disable the used of REST API from accessing information from your website, this plugin return authentication error and disable all endpoints for any user not logged in on your website.

== Installation ==

1. Upload the `turn-off-rest-api` directory to the `/wp-content/plugins/` directory via FTP
2. Activate the plugin through the 'Plugins' menu in WordPress
3. To test kindly logout and please go to your-website-url.com/wp-json and check if API will return 'Only authenticated users allowed access to REST API'

== Changelog ==

= 1.0 =
[03/23/2017]
* Initial Release