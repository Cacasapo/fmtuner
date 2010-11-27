=== fmTuner ===
Contributors: command_tab
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=533819
Tags: music,last.fm,sidebar,mp3,cd,cover,album,artwork
Requires at least: 2.7
Tested up to: 3.0
Stable tag: 1.1

fmTuner displays recent, top, or loved Last.fm tracks in a customizable format.

== Description ==

fmTuner pulls track information from your Last.fm account, including recent tracks, loved tracks, and top tracks.  Using built-in options and simple tags, you can fully customize how tracks appear on your site.

= Features =

* Choose between recent, loved, and top tracks
* Limit how many tracks are shown
* Adjust how often tracks get pulled from Last.fm
* Customize track appearance using HTML and placeholders

= Requirements =

* A Last.fm account to which you "scrobble" (publish) music details
* WordPress 2.7 or newer
* PHP 5 or newer

== Installation ==

Installation of fmTuner is straightforward, however it does require PHP 5 or newer.

1. Upload `fmtuner.php` to your `/wp-content/plugins/` directory, within a directory like `fmtuner`.
1. Ensure `/wp-content/plugins/fmtuner/` is writable by your webserver (`chmod 755 fmtuner`).
1. Activate the plugin through the 'Plugins' page in the WordPress admin.
1. Set your fmTuner preferences in the "Settings" menu in WordPress.
1. Place `<?php if(function_exists('fmtuner')) { fmtuner(); } ?>` in your desired template.

== Frequently Asked Questions ==

= How does fmTuner work? =

fmTuner pulls your latest tracks from Last.fm according to the settings page in the WordPress administration area.  Tracks get pulled from Last.fm when a visitor comes to your site, and are then cached for future visits.  If the cache has expired (that is, the cache's age has passed the Update Frequency you've chosen), it gets pulled again, and your page is updated.  Track information is displayed using HTML and fmTuner Tags, also in settings page. 

= What are fmTuner Tags? =

fmTuner tags are simple placeholders that can be sprinkled among HTML to customize the album display format used for each track.  Tags can be used more than once, or completely left out, depending on your preferences.  A simple example is provided when you install fmTuner, so you won't be left in the dark if you have even basic HTML knowledge.

* `[::album::]` Album name (only available for Recent tracks)
* `[::artist::]` Artist name
* `[::image::]` Album artwork address (usually around 120&times;120 pixels in size, but may not be perfectly square)
* `[::number::]` Track number within the fmTuner set (for a numbered list)
* `[::title::]` Track title
* `[::url::]` Last.fm track address

Using CSS and JavaScript, you can do even more, limited only by your skills and imagination.  See [this tutorial](http://www.komodomedia.com/blog/2009/03/sexy-music-album-overlays/ "Sexy Music Album Overlays at KomodoMedia") for details on how to make albums look gorgeous with transparent overlay images and a little CSS.

= Can I customize the HTML around the displayed tracks? =

Absolutely! While the customizable Display Format and fmTuner Tags are used for each track, you can place any additional HTML around the `<?php if(function_exists('fmtuner')) { fmtuner(); } ?>` call.

= How many tracks can I display? =

The number of tracks to be displayed can be set in the fmTuner Settings page in the WordPress administration area.  Between 1 and 10 is recommended, just to keep things looking sane.

== Troubleshooting ==

**Why are no tracks displayed?**

1. Make sure fmTuner is installed and activated by visiting your Manage Plugins page in the WordPress administration area.
1. Ensure your Last.fm username is set in the fmTuner Settings page, as well as any other necessary options (e.g. Recent Tracks instead of Loved Tracks if you have no Loved Tracks).
1. Confirm that `<?php if(function_exists('fmtuner')) { fmtuner(); } ?>` exists somewhere in your current WordPress theme.
1. Listen to some music, and perhaps mark some tracks as Loved, to make sure you have available music to show.
1. Finally, try setting a placeholder image address in the fmTuner Settings page, which will be used when tracks have no artwork address.

**Why does the number of tracks displayed not match my setting?**

Occasionally, you may find that the number of displayed tracks does not match the number you set in the fmTuner Settings page.  This is most often attributed to the `[::image::]` fmTuner tag.  Because fmTuner cannot know in advance which tracks don't have images, it will skip ones without artwork.  Alternatively, you can provide a placeholder image address in the fmTuner Settings page which will be used when Last.fm does not provide artwork.  Using this, your number of displayed tracks should stay constant.

**Why do I get PHP errors?**

fmTuner needs certain PHP functions to talk to Last.fm and handle responses, and while it takes precautions to avoid blatant errors, it's possible your server doesn't meet the necessary requirements.

1. Ensure your server is running PHP 5 or later.  If using a hosted environment, your provider may be able to do this for you.
1. fmTuner needs to communicate with Last.fm.  Set `allow_url_fopen`="On" in your php.ini file, or confirm that the cURL extension is installed.  fmTuner will try one method of fetching track listings, and automatically fall back to the other if needed.

== Removal ==

Sorry to see you go!  Here's how to remove fmTuner:

1. Deactivate the plugin through the 'Plugins' menu in WordPress.
1. Delete the `fmtuner` directory from your `wp-content/plugins/` directory.

Be sure to [get in touch](http://www.command-tab.com) if there's a particular feature you think would make fmTuner better!

== Screenshots ==

1. fmTuner Settings screen.
1. One of many possible display options. You are free to configure fmTuner how you prefer!

== Changelog ==

= 1.1 =
* Added a placeholder image field to the fmTuner Settings page, which will be substituted when tracks have no artwork.
* Tested under WordPress 2.9.1.

= 1.0.9 =
* Added a Settings link to the plugin actions list.
* Tested under WordPress 2.9.

= 1.0.8 =
* Fixed a bug with the `[::url::]` fmTuner tag that caused Last.fm links to appear incorrectly.

= 1.0.7 =
* Tracks with foreign character sets now display more accurately.

= 1.0.6 =
* You can now display more than 10 Recent Tracks, and you should get fewer tracks without artwork.

= 1.0.5 =
* Track information is now properly escaped to handle $ signs, quotes, and other non-alphanumeric characters.

= 1.0.4 =
* Made minor tweaks for fmTuner Settings page under WordPress 2.7.

= 1.0.3 =
* Added a `[::number::]` fmTuner tag has been added, which prints a sequential number for each track (starting at 1). This is particularly useful for CSS and JavaScript hooks.

= 1.0.2 =
* Added a cURL-based alternative to `file_get_contents` to hopefully resolve "URL file-access is disabled" issues. If `allow_url_fopen` is disabled in your php.ini, cURL will be used to fetch the Last.fm feed instead.

= 1.0.1 =
* Added better failure checking and informational messages, removed development code, and updated instructions.

= 1.0 =
* Initial release.