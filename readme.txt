=== Plugin Name ===
Contributors: sandfox
Tags: xmpp, jabber, library
Requires at least: 3.0
Tested up to: 3.8
Stable tag: trunk

XMPP Enabled provides a single-function API for other plugins that use Jabber/XMPP messaging protocol.

== Description ==

XMPP Enabled provides a single function API for other plugins that use Jabber/XMPP messaging protocol.
See API section for details if you want to use XMPP protocol in your notifications.

* GitHub page: https://github.com/sandfox-im/xmpp-enabled
* Packagist page: https://packagist.org/packages/sandfox-im/xmpp-enabled

== Installation ==

1. Upload `xmpp-enabled` folder to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Set up default Jabber account in XMPP Enabled Settings page

== Frequently Asked Questions ==

= Why the distinct plugin? =

To provide a single XMPP account settings page for all Jabber notification plugins

= Can it send messages to multiple contacts? =

It's safe to use xmpp_send() multiple times. All calls will use the same connection

== Thanks to ==
* Mako N (Japanese translation and i18n support)

== Changelog ==

= 1.0.0 =
* Japanese translation and internationalisation support from Mako N
* Consider plugin is stable :)
* Installation via composer

= 0.3.2.2 =
sand-fox.com to sandfox.org

= 0.3.2.1 =
* minor bugfixes

= 0.3.2 =
* Fixed array serialization in logs
* PHP5 is now required minimum

= 0.3.1 =
* Bugfix for disabling encryption

= 0.3.0 =
* Multiple calls to xmpp_send() now use single connection
* Now user can disable encryption
* Fixed menu creation priority

= 0.2.2 =
* Fixed wrong behaviour for custom hostnames.
* Improved logging

= 0.2.1 =
* A small usability change.

= 0.2.0 =
* A first public beta.

== Upgrade Notice ==

= 0.3.2 =
Breaks old logs

= 0.3 =
Multiple calls to xmpp_send() now behave smarter

= 0.2.2 =
Update is strongly recommended for those who manually set server hostname

== Plugin API ==

= The Single Function =

The single function for sending XMPP Messages is `xmpp_enabled`. It can be called directly from any WordPress plugin

`xmpp_enabled($recipient, $message, $subject='', $type='normal');`

* $recipient is a valid (bare or full) JID of the recipient like 'juliet@capulet.net'
* $message is a plain text message to be sent
* $subject is a title line for message. It is usually omitted for chat-type messages
* $type is a type of message. It can be 'chat', 'normal' or 'headline'. See [RFC 3921](http://www.ietf.org/rfc/rfc3921.txt) for details

*Example:* `xmpp_send('subscriber@something.com',"Read our new post:\nhttp://something.com/new-post",'New post is published','headline');`

= Create submenu in the XMPP Enabled section =

XMPP Enabled creates it's own section and you're free to use it for your plugins. The code is `'xmpp-enabled'`

*Example:* `add_submenu_page('xmpp-enabled', 'Jabber Comment Notifications', 'Comment Notifications', 'administrator', __FILE__, 'jcommnotify_settings');`

See [Adding Administration Menus](http://codex.wordpress.org/Adding_Administration_Menus) in WordPress Codex for further details
