=== Plugin Name ===
Contributors: Adam Koch
Donate link: http://wowhead-tooltips.com/contact-me/donate/
Tags: blizzard, wow, realm status, realm, world of warcraft
Requires at least: 2.8.4
Tested up to: 2.8.4
Stable tag: 0.4

Displays a realm status page similar to WoW's site, also has a widget to display individual realms.

== Description ==

This plugin has the same Widget code as the one by [Ryan Cain](http://yourfirefly.com), except that I have tweaked the code to allow for multiple realms and added an overall realm status page that can be put anywhere by using the `[realmstatus]` tag.

As of version 0.3 European realms are supported for both the realm status page and the Widget.  Due to these changes you must now specify the region for every realm that you intend on displaying on your widget.  The format is: `{region}_{lang}:{realm name}`, only use `_{lang}` if it is a European realm.  For example to display Bleeding Hollow (US) and Aerie Peak (EU, English) the entry would be: `us:Bleeding Hollow,eu_en:Aerie Peak`.  See below for acceptable languages.

= Acceptable Languages =
* en - English
* es - Spanish
* de - German
* ru - Russian
* fr - French

== Installation ==

1. Upload `wp-realm-status` to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Place `[realmstatus]` anywhere in your pages for a realm list.
1. Or add a Widget like you would normally.  For multiple realms separate them with a comma (,). *NO SPACE AFTER THE COMMA.*

== Frequently Asked Questions ==

= Does this script support WoW's EU site. =

As of version 0.3 European realms are supported for both realm status page and the Widget.

= How can I contact you? =

http://wowhead-tooltips.com/contact-me/

== Screenshots ==

1. Widget screenshot.
2. Realm list screenshot.

== Changelog ==

= 0.4 =
* Added options page to set the default region for the realm list page.

= 0.3 =
* Added European realm suport to the server list page and Widget.

= 0.1/0.2 =
* Initial Release
