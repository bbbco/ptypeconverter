=== pTypeConverter===
Author: Brian D. Goad (bbbco)
Author URI: http://www.briandgoad.com/blog
Plugin URI: http://www.briandgoad.com/blog/pTypeConverter
Tags: post, posts, page, pages, admin, plugin, convert, change, switch, pTC, p2pConverter, pTypeConverter, custom post, custom posts, post type, post types
Requires at least: 2.9
Tested up to: 3.5
Stable tag: 0.2.8.1
=======

== Description ==
Converts post types from one to another. This plugin is a complete reworking of my old plugin p2pConverter. pTypeConverter allows you to easily convert any post type of a certain post to another in an easy to use interface.  A pTypeConverter role capability prevents unwanted users from converting pages (i.e. only Administrators and Editors have this ability to begin with), which can be adjusted by using a Role Manager plugin.

== Installation ==
Copy the `pTypeConverter` directory to your plugins directory and activate the pTypeConverter
plugin from WordPress. To access pTypeConverter, look under the Tools menu for the pTypeConverter
submenu.

== Frequently Asked Questions ==
 - Will my comments follow?
 + Yes, they are stored in the database, and will follow the converted post. Please ensure that the post type you are converting to employs comments within your theme.

 - Why are my comments not showing up after converting to a X?
 + Please check to make sure you have enabled Discussion Comments on the converted post. To do so, click on the Screen Options while Editing the post, and ensure the Discussion checkbox is checked. This will enable a Discussion module to be displayed underneath the main editing area. Please ensure the "Allow Comments" checkbox is checked, and click to Update your post. If this still does not resolve your issue, please check to ensure your theme allows comments to be displayed for the post type by including <?php comments_template('', true); ?>

 - What about the URL? My users will be lost if the bookmarked the old one after I convert!
 + No worries! The plugin rewrites the permalinks after conversion so that your old URL will automagically forward the user to the new URL. Wordpress takes care of most of the magic behind the scenes. If you are really concerned about URLs because of SEO, you can use a plugin lik Platinum SEO to configure things more to your liking.

 - Will converting posts to pages affect my Menu structure?
 + No, this should not affect your menu structure, unless you have it setup someway to automatically add new Pages to your menu.

[Ask a question] mailto: bdgoad (at) gmail (dot) com

== Future Plans ==
* Implement proper Admin display of posts screen

== Version History ==

= Version 0.2.8.1 =
* Fixed a bug with new requirements in WP 3.5

= Version 0.2.8 =
* Fixed bugs with converting to blank type/disappearing posts (thanks Stephanie!), Logging bugs, other small tweaks

= Version 0.2.7 =
* Fixed bugs

= Version 0.2.5 =
* Added Welcome screen with instructions and FAQ
* Enhanced user interface functionality with convert messages

= Version 0.2.1 =
* Fixed blocking issue with install

= Version 0.2 =
* jQuerified and Ajaxified the plugin
* Added filtering, logging, and other options

= Version 0.1.5 =
* Fixed activation issue (thanks Erlend from jMonkeyEngine!)

= Version 0.1 =
* Rewrote from my old plugin p2pConverter
* Created its own submenu page
* Allow bulk conversion
