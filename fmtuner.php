<?php
	
	/*
	Plugin Name: fmTuner
	Version: 1.0.1
	Plugin URI: http://www.command-tab.com
	Description: Displays recent, top, or loved <a href="http://www.last.fm/home" target="_blank">Last.fm</a> tracks in a <a href="options-general.php?page=fmtuner/fmtuner.php">customizable format</a>.
	Author: Collin Allen
	Author URI: http://www.command-tab.com
	*/
	
	
	/*
	Copyright (c) 2008 Collin Allen, http://www.command-tab.com/

	Permission is hereby granted, free of charge, to any person obtaining
	a copy of this software and associated documentation files (the
	"Software"), to deal in the Software without restriction, including
	without limitation the rights to use, copy, modify, merge, publish,
	distribute, sublicense, and/or sell copies of the Software, and to
	permit persons to whom the Software is furnished to do so, subject to
	the following conditions:

	The above copyright notice and this permission notice shall be
	included in all copies or substantial portions of the Software.

	THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
	EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
	MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
	NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
	LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
	OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
	WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
	*/
	
	
	
	// Display last.fm tracks, no duplicates, none without "large" artwork
	function fmtuner()
	{
		if (function_exists('simplexml_load_string') && function_exists('file_put_contents'))
		{
			// Fetch options from WordPress DB and set up variables
			$iCacheTime = get_option('fmtuner_update_frequency');
			$sCachePath = get_option('fmtuner_cachepath');
			$iTrackLimit = get_option('fmtuner_track_limit');
			$sBaseUrl = 'http://ws.audioscrobbler.com/2.0/?method=';
			$sMethod = get_option('fmtuner_track_type');
			$sUsername = get_option('fmtuner_username');
			$sApiKey = 'ff0eaca3b7e2660755d6c652af7b0489';
			$sApiUrl = "{$sBaseUrl}{$sMethod}&user={$sUsername}&api_key={$sApiKey}";
			$sDisplayFormat = get_option('fmtuner_display_format');
			$bUsingImages = false;
			if (strpos($sDisplayFormat, '[::image::]') === false)
			{
				$bUsingImages = false;
			}
			else
			{
				$bUsingImages = true;
			}
			
			// Run only if a username is set
			if ($sUsername)
			{
				// If the cached XML exists on disk
				if (file_exists($sCachePath))
				{
					// Compare file modification time against update frequency
					if (time() - filemtime($sCachePath) > $iCacheTime)
					{
						// Cache miss
						$sTracksXml = file_get_contents($sApiUrl);
						file_put_contents($sCachePath, $sTracksXml);
					}
					else
					{
						// Cache hit
						$sTracksXml = file_get_contents($sCachePath);
					}
				}
				else
				{
					// Fetch the XML for the first time
					$sTracksXml = file_get_contents($sApiUrl);
					file_put_contents($sCachePath, $sTracksXml);
				}
				
				// Parse the XML
				$xTracksXml = simplexml_load_string($sTracksXml);
				$aTracks = array();
				$iTotal = 1;
				
				// If we have any parsed tracks
				if ($xTracksXml)
				{
					// Switch based on selected track type
					switch($sMethod)
					{
						case 'user.getlovedtracks':
							$xTracks = $xTracksXml->lovedtracks->track;
							break;
						case 'user.getrecenttracks':
							$xTracks = $xTracksXml->recenttracks->track;
							break;
						case 'user.gettoptracks':
							$xTracks = $xTracksXml->toptracks->track;
							break;
						default:
							$xTracks = $xTracksXml->lovedtracks->track;
							break;
					}
					
					// Loop over each track, outputting it in the desired format
					foreach($xTracks as $oTrack)
					{
						// If we want to use images, but the current $oTrack has no big image, skip it
						if ($bUsingImages && $oTrack->image[2] == '')
						{
							continue;
						}
						
						// 'Recent tracks' <artist> node has no <name> child node, while other methods do.
						// Sort it out and get the artist name into $sArtist
						if ($sMethod == 'user.getrecenttracks')
						{
							$sArtist = $oTrack->artist;
						}
						else
						{
							$sArtist = $oTrack->artist->name;
						}
						
						// Store each track in $aTracks, and check it every iteration so as not to output duplicates
						$sKey = $sArtist . ' - ' . $oTrack->name;
						
						// If the current track is not in $aTracks and we haven't hit the track limit
						if (!in_array($sKey, $aTracks) != "" && $iTotal <= $iTrackLimit)
						{
							// Shove the current track into $aTracks to be checked for next time around
							$aTracks[] = $sKey;
							
							// Dump out the blob of HTML with data embedded
							$aTags = array(
								'/\[::album::\]/',
								'/\[::artist::\]/',
								'/\[::image::\]/',
								'/\[::title::\]/',
								'/\[::url::\]/'
							);
							$aData = array(
								$oTrack->album,
								$sArtist,
								$oTrack->image[2],
								$oTrack->name,
								$oTrack->url
							);
							
							// Merge $aTags and $aData
							echo preg_replace($aTags, $aData, $sDisplayFormat);
							
							// Increment the counter so we can check the track limit next time around
							$iTotal++;
						}
					} // end foreach loop
				} // end if (any parsed tracks)
			}
			else
			{
				echo 'Please <a href="'.get_bloginfo('wpurl').'/wp-admin/options-general.php?page=fmtuner/fmtuner.php">set fmTuner options</a> in your WordPress administration panel.';
			} // end if (username)
		}
		else
		{
			echo 'fmTuner requires PHP version 5 or greater.  Please contact your web host for more information.';
		} // end PHP5 check
	} // end fmtuner()
	
	
	
	// Add default options to the DB
	function add_fmtuner_options()
	{
		add_option('fmtuner_cachepath', dirname(__FILE__).'/fmtuner_cache.xml'); // Default cache location
		add_option('fmtuner_username', ''); // Default to ''
		add_option('fmtuner_track_type', 'user.getlovedtracks'); // Default to 'Loved tracks'
		add_option('fmtuner_update_frequency', 3600); // Default to 'Every hour'
		add_option('fmtuner_track_limit', 2); // Default to max of 2 tracks
		add_option('fmtuner_display_format', '<li>[::artist::] - [::title::]<img src="[::image::]" alt="[::title::] by [::artist::]" /></li>'); // Default format
	}
	
	
	
	// Delete the cache file and options stored in the DB
	function delete_fmtuner_options()
	{
		$sCachePath = get_option('fmtuner_cachepath');
		if (file_exists($sCachePath))
		{
			unlink($sCachePath);
		}
		
		delete_option('fmtuner_cachepath');
		delete_option('fmtuner_username');
		delete_option('fmtuner_track_type');
		delete_option('fmtuner_update_frequency');
		delete_option('fmtuner_track_limit');
		delete_option('fmtuner_display_format');
	}
	
	
	
	// Add the options page to the admin area under Settings when called
	function setup_fmtuner_options()
	{
		add_options_page('fmTuner Settings', 'fmTuner', 1, __FILE__, 'fmtuner_options');
	}
	
	
	
	// Register fmTuner plugin activation/deactivation hooks
	register_activation_hook(__FILE__, 'add_fmtuner_options');
	register_deactivation_hook(__FILE__, 'delete_fmtuner_options');
	
	
	
	// Hook into WordPress to call |setup_fmtuner_options| when the admin menu is loaded
	add_action('admin_menu', 'setup_fmtuner_options');
	
	
	
	// Display the options page in wp-admin
	function fmtuner_options()
	{ ?>
		<div id="wpbody">
			<div class="wrap">
<?php
		if (function_exists('simplexml_load_string') && function_exists('file_put_contents'))
		{
			// Fetch XML again, since key options (username, track type) may have changed
			$sBaseUrl = 'http://ws.audioscrobbler.com/2.0/?method=';
			$sMethod = get_option('fmtuner_track_type');
			$sUsername = get_option('fmtuner_username');
			$sApiKey = 'ff0eaca3b7e2660755d6c652af7b0489';
			$sApiUrl = "{$sBaseUrl}{$sMethod}&user={$sUsername}&api_key={$sApiKey}";
			if ($sUsername != '')
			{
				$sTracksXml = file_get_contents($sApiUrl);
				file_put_contents(get_option('fmtuner_cachepath'), $sTracksXml);
			}
		}
		else
		{
?>
			<div class="error" style="padding: 5px; font-weight: bold;">fmTuner requires PHP version 5 or greater. Please contact your web host for more information.</div>
<?php
		}
?>
				<h2>fmTuner Settings</h2>
				<form action="options.php" method="post">
					<?php wp_nonce_field('update-options'); // Protect against XSS ?>
					<table class="form-table">
						<tbody>
							<tr valign="top">
								<th scope="row">
									<label for="fmtuner_username">Last.fm Username</label>
								</th>
								<td>
									<input type="text" size="25" value="<?php echo get_option('fmtuner_username'); ?>" id="fmtuner_username" name="fmtuner_username" />
									<br />Enter your <a href="http://www.last.fm/home" target="_blank">Last.fm</a> username
								</td>
							</tr>
							<tr valign="top">
								<th scope="row">Track Type</th>
								<td>
									<fieldset>
										<?php $sTrackType = get_option('fmtuner_track_type'); ?>
										<p>
											<label>
												<input type="radio" <?php if ($sTrackType == 'user.getrecenttracks') { echo 'checked="checked" '; } ?> class="tog" value="user.getrecenttracks" name="fmtuner_track_type" /> Recent tracks
											</label>
										</p>
										<p>
											<label>
												<input type="radio" <?php if ($sTrackType == 'user.getlovedtracks') { echo 'checked="checked" '; } ?> class="tog" value="user.getlovedtracks" name="fmtuner_track_type" /> Loved tracks
											</label>
										</p>
										<p>
											<label>
												<input type="radio" <?php if ($sTrackType == 'user.gettoptracks') { echo 'checked="checked" '; } ?> class="tog" value="user.gettoptracks" name="fmtuner_track_type" /> Top tracks
											</label>
										</p>
									</fieldset>
								</td>
							</tr>
							<tr valign="top">
								<th scope="row">
									<label for="fmtuner_track_limit">Track Limit</label>
								</th>
								<td>
									Show <input type="text" size="3" value="<?php echo get_option('fmtuner_track_limit'); ?>" id="fmtuner_track_limit" name="fmtuner_track_limit" /> tracks at most.
								</td>
							</tr>
							<tr valign="top">
								<th scope="row">
									<label for="fmtuner_update_frequency">Update Frequency</label>
								</th>
								<td>
									<select id="fmtuner_update_frequency" name="fmtuner_update_frequency">
										<?php $iUpdateFrequency = get_option('fmtuner_update_frequency'); ?>
										<option <?php if ($iUpdateFrequency == 900) { echo 'selected="selected" '; } ?> value="900">every 15 minutes</option>
										<option <?php if ($iUpdateFrequency == 1800) { echo 'selected="selected" '; } ?> value="1800">every 30 minutes</option>
										<option <?php if ($iUpdateFrequency == 3600) { echo 'selected="selected" '; } ?> value="3600">every hour</option>
										<option <?php if ($iUpdateFrequency == 86400) { echo 'selected="selected" '; } ?> value="86400">every day</option>
									</select>
								</td>
							</tr>
							<tr valign="top">
								<th scope="row">
									<label for="fmtuner_display_format">Display Format</label>
								</th>
								<td>
									<fieldset>
										<p>
											<label for="fmtuner_display_format">The fmTuner tags below can be used among standard <abbr title="HyperText Markup Language">HTML</abbr> to customize the album display format.  Tags can be used more than once, or completely left out, depending on your preferences.
												<ul style="margin: 0px; padding: 0px; list-style: none;">
													<li><code>[::album::]</code> Album name (Only available for <strong>Recent tracks</strong>.)</li>
													<li><code>[::artist::]</code> Artist name</li>
													<li><code>[::image::]</code> Album artwork address (Usually ~120 pixels in size &mdash; may not be square.  If used, only tracks with artwork will be shown.)</li>
													<li><code>[::title::]</code> Track title</li>
													<li><code>[::url::]</code> Last.fm track address</li>
												</ul>
											</label>
										</p>
										<p>
											<textarea class="code" style="width: 98%; font-size: 12px;" id="fmtuner_display_format" rows="8" cols="60" name="fmtuner_display_format"><?php echo get_option('fmtuner_display_format'); ?></textarea>
										</p>
									</fieldset>
								</td>
							</tr>
						</tbody>
					</table>
					<p class="submit">
						<input type="hidden" name="action" value="update" />
						<input type="hidden" name="page_options" value="fmtuner_username,fmtuner_track_type,fmtuner_update_frequency,fmtuner_track_limit,fmtuner_display_format" />
						<input type="submit" name="Submit" value="<?php _e('Save Changes') ?>" />
					</p>
				</form>
			</div>
		</div>
			
		<?php
	}
	

	
?>