=== fmTuner ===
Contributors: command_tab
Donate link: http://www.command-tab.com/
Tags: music, last.fm, sidebar, mp3
Requires at least: 2.5
Tested up to: 2.6.2
Stable tag: 1.0.1

fmTuner displays recent, top, or loved Last.fm tracks in a customizable format.

== Description ==

fmTuner pulls track information from your Last.fm account, including recent tracks, loved tracks, and top tracks.  Using
the built-in options and simple tags, you can fully customize how tracks appear on your site.

Features:

*   Choose between recent, loved, and top tracks
*   Adjust how many tracks are shown
*   Adjust how often tracks get pulled from Last.fm
*   Customizable appearance using basic tags

Requirements:

*	A Last.fm account to which you "scrobble" (publish) music details
*	WordPress 2.5 or greater
*	PHP 5 or greater

== Installation ==

Installation of fmTuner is rather straightforward, however it does require PHP 5 or greater.

1. Upload `fmtuner.php` to your `/wp-content/plugins/` directory, within a directory like `fmtuner`.
1. Ensure `/wp-content/plugins/fmtuner/` is writable by your webserver (`chmod 755 fmtuner`).
1. Activate the plugin through the 'Plugins' menu in WordPress.
1. Set up options in the "Settings" menu in WordPress.
1. Place `<?php if(function_exists('fmtuner')) { fmtuner(); } ?>` in your templates.

== Frequently Asked Questions ==

= How does fmTuner work? =

fmTuner pulls your latest tracks from Last.fm according to the settings page in the WordPress administration area.  Tracks get pulled from Last.fm when a visitor comes to your site, and are then cached for future visits.  If the cache has expired (i.e. passed the Update Frequency you've chosen), it gets pulled again, and your page is updated.

= How many tracks can I display? =

Tracks can be limited in the settings page for fmTuner, however Last.fm provides up to 10 Recent Tracks.  Loved and Top Tracks offer many, many more.  Between 1 and 10 is recommended.

== Removal ==

Should you need to remove fmTuner:

1. Deactivate the plugin through the 'Plugins' menu in WordPress.
1. Delete the `fmtuner` directory from your `/wp-content/plugins/` directory.

== Screenshots ==

1. fmTuner settings interface in WordPress 2.6.
1. One of many possible display options. You are free to configure fmTuner how you prefer!