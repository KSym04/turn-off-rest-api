=== Turn Off REST API ===
Contributors: ksym04
Tags: security, api, json, REST, admin, turn off, disable, kill
Requires at least: 4.7
Tested up to: 4.8.2
Stable tag: 1.0.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Turn off JSON REST API on your website to anonymous users and prevent unauthorized requests from using the REST API to get information from your website.

== Description ==

Since the release of WordPress 4.0 came out, there have been a lot of hackers exploiting the vulnerabilities of the REST API. By installing this plugin, you will effectively prevent and disable the use of REST API from unauthorized users and protect the information on your website from being accessible. If someone tries to access the REST API on your site, the plugin will return an authentication error on the API endpoints, for any unauthorized users trying to access it.

While WordPress REST API vulnerability exploits continue this plugin effectively prevent and disable the used of REST API from accessing information from your website, this plugin return authentication error and disable all endpoints for any user not logged in on your website.

== Installation ==

1. Upload the `turn-off-rest-api` directory to the `/wp-content/plugins/` directory via FTP
2. Activate the plugin through the 'Plugins' menu in WordPress
3. To test kindly logout and please go to http://[your_website_url].com/wp-json and check if REST API will return an error that reads 'Only authenticated users are allowed an access on REST API'

== Changelog ==

= 1.0.2 =
[09/27/2017]
* Added endpoints admin page
* Minor improvements

= 1.0.1 =
[03/31/2017]
* Minor improvements

= 1.0.1 =
[03/29/2017]
* Optimized filter implementation

= 1.0.0 =
[03/23/2017]
* Initial Release