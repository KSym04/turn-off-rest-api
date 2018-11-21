=== Turn Off REST API ===
Contributors: ksym04
Tags: disable rest api, json, rest, api, admin
Requires at least: 4.7
Tested up to: 4.9.8
Stable tag: 1.0.3
License: GPL2+
License URI: license.txt

This plugin prevents unauthorized requests from using the WP REST API.

== Description ==

Turn off JSON REST API on your website to anonymous users and prevent unauthorized requests from using the REST API to get information from your website.

Since the release of WordPress 4.0 came out, there have been a lot of hackers exploiting the vulnerabilities of the REST API. By installing this plugin, you will effectively prevent and disable the use of REST API from unauthorized users and protect the information on your website from being accessible. If someone tries to access the REST API on your site, the plugin will return an authentication error on the API endpoints, for any unauthorized users trying to access it.

While WordPress REST API vulnerability exploits continue this plugin effectively prevent and disable the used of REST API from accessing information from your website, this plugin return authentication error and disable all endpoints for any user not logged in on your website.

= Language Support =

* English (en_US)

== Installation ==

1. Upload the `turn-off-rest-api` directory to the `/wp-content/plugins/` directory via FTP
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Enjoy

== Frequently Asked Questions ==

= How may I know if the plugin is working and my WP REST API is secured? =

To test kindly log out and please go to http://[your_website_url].com/wp-json and check if REST API will return an error that reads 'Only authenticated users are allowed an access on REST API'

== Screenshots ==

1. Test if the wp-json is secured from unauthorized access.

== Changelog ==

= 1.0.3 =
* Added en_US language file
* Added license file
* Minor code clean up

= 1.0.2 =
* Added endpoints admin page
* Minor improvements

= 1.0.1 =
* Minor improvements

= 1.0.1 =
* Optimized filter implementation

= 1.0.0 =
* Initial Release