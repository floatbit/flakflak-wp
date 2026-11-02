=== FlakFlak ===
Contributors: flakflak
Tags: feedback, collaboration, embed
Requires at least: 6.2
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Load your FlakFlak project on all frontend pages or selected WordPress post IDs.

== Description ==

Settings > FlakFlak provides:

* A required field for the FlakFlak embed code.
* All pages (default), or Specific pages with a comma-separated list of post IDs.

Selected IDs can identify pages, posts, or custom post types. A configured static homepage or posts page can be selected by its page ID. Archives and the latest-posts homepage do not have a selectable post ID; use All pages to include them. The embed is excluded from wp-admin, feeds, 404s, REST requests, and WordPress's own embedded-post views.

The plugin loads the supplied external script in the footer. Your theme must call wp_footer(). Public/Private mode, accounts, feedback, categories, and attachments are managed by FlakFlak, not this plugin. Both public and private FlakFlak projects are supported.

== Installation ==

1. Copy the flakflak-wp folder into wp-content/plugins and activate FlakFlak.
2. Open Settings > FlakFlak.
3. Paste the complete embed script copied from your FlakFlak project.
4. Leave All pages selected, or choose Specific pages and enter IDs such as 123, 456.
5. Save Settings. Make sure the website's origin is allowed in the FlakFlak project.

Invalid or incomplete settings are rejected and the previous saved settings remain. Only one external script with a valid data-site-id is accepted. Optional data-api-url is preserved; inline code and extra script attributes are not retained. HTTP localhost embeds are supported for local development. Clear any WordPress page cache after changing visibility.

Do not also add the same embed manually through a theme or another script plugin.

== External service ==

This plugin loads the script URL you supply from your FlakFlak installation. Once the widget connects, that installation handles feedback and associated account/name, attachment, page and browser information. The configured FlakFlak service's privacy policy and terms apply. No hosted feedback is stored or deleted by this WordPress plugin. Deleting the plugin removes only its local WordPress settings.

== Development checks ==

With WP-CLI available, run from the WordPress installation:

    wp --skip-plugins --skip-themes eval-file wp-content/plugins/flakflak-wp/tests/integration.php

The checks use in-memory option filters; they do not activate the plugin or save settings.

== Changelog ==

= 1.0.0 =
* Add Settings > FlakFlak with validated embed code and all/specific-page visibility.
