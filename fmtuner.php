<?php
    
    /*
    Plugin Name: fmTuner
    Version: 1.1
    Plugin URI: http://www.command-tab.com/2008/09/06/fmtuner-a-lastfm-plugin-for-wordpress
    Description: Displays recent, top, or loved <a href="http://www.last.fm/home" target="_blank">Last.fm</a> tracks in a customizable format.
    Author: Collin Allen
    Author URI: http://www.command-tab.com
    */
    

    /*
    Copyright (c) 2010 Collin Allen, http://www.command-tab.com

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
    
    
    // Display last.fm tracks, no duplicates
    function fmtuner()
    {
        if (function_exists('simplexml_load_string') && function_exists('file_put_contents'))
        {
            // Fetch options from WordPress DB and set up variables
            $iCacheTime = get_option('fmtuner_update_frequency');
            $sCachePath = get_option('fmtuner_cachepath');
            $iTrackLimit = get_option('fmtuner_track_limit');
            $iTrackLimitWithBuffer = $iTrackLimit + 15; // Grab extra in case some albums don't have artwork
            $sBaseUrl = 'http://ws.audioscrobbler.com/2.0/?method=';
            $sMethod = get_option('fmtuner_track_type');
            $sUsername = get_option('fmtuner_username');
            $sApiKey = 'ff0eaca3b7e2660755d6c652af7b0489';
            $sDisplayFormat = get_option('fmtuner_display_format');
            $sPlaceholderImage = get_option('fmtuner_placeholder_image');
            $sApiUrl = "{$sBaseUrl}{$sMethod}&user={$sUsername}&api_key={$sApiKey}&limit={$iTrackLimitWithBuffer}";
            
            // Check if we're using images or not
            $bUsingImages = false;
            if (strpos($sDisplayFormat, '[::image::]') === false)
                $bUsingImages = false;
            else
                $bUsingImages = true;
            
		// DIsable HTTPS certificate bitching
			$opts=array(
										"ssl"=>array(
										"verify_peer"=>false,
										"verify_peer_name"=>false,
													),
										);  
			
			
			
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
                        $sTracksXml = fmtuner_fetch($sApiUrl);
						$sTracksXml = str_ireplace('http:', 'https:', $sTracksXml);
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
                    $sTracksXml = fmtuner_fetch($sApiUrl);
				    $sTracksXml = str_ireplace('http:', 'https:', $sTracksXml);
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
                        // Handle tracks without artwork and placeholder image swapping
                        $sImage = $oTrack->image[2];
                        if ($sPlaceholderImage != '')
                            $sPlaceholderImage = str_replace('[::template_directory::]', get_bloginfo('template_directory'), $sPlaceholderImage);
                        if ($bUsingImages && $sImage == '' && $sPlaceholderImage == '')
                            continue;
                        if ($bUsingImages && $sImage == '' && $sPlaceholderImage != '')
                            $sImage = $sPlaceholderImage;
                        
                        // 'Recent tracks' <artist> node has no <name> child node, while other methods do.
                        // Sort it out and get the artist name into $sArtist
                        if ($sMethod == 'user.getrecenttracks')
                            $sArtist = $oTrack->artist;
                        else
                            $sArtist = $oTrack->artist->name;
                        
                        // Store each track in $aTracks, and check it every iteration so as not to output duplicates
                        $sKey = $sArtist . ' - ' . $oTrack->name;
                        
                        // If the current track is not in $aTracks and we haven't hit the track limit
                        if (!in_array($sKey, $aTracks) != "" && $iTotal <= $iTrackLimit)
                        {
                            // Shove the current track into $aTracks to be checked for next time around
                            $aTracks[] = $sKey;
                            
                            // Dump out the blob of HTML with data embedded
                            $aTags = array(
                                '[::album::]',
                                '[::artist::]',
                                '[::image::]',
                                '[::number::]',
                                '[::title::]',
                                '[::url::]'
                            );
                            
							$aData = array(
                                $oTrack->album,
                                $sArtist,
                                $sImage,
                                $iTotal,
                                $oTrack->name,
                                (strpos($oTrack->url, 'http') === 0) ? $oTrack->url : 'https://' . $oTrack->url // Some tracks start with 'www', which creates a bad link
                            );
                            
							
							
							
							// Clean up data, prevent XSS, etc.
                            foreach ($aData as $iKey => $sValue)
                                $aData[$iKey] = trim(strip_tags(htmlspecialchars($sValue)));
                            
                            // Merge $aTags and $aData
                            echo str_replace($aTags, $aData, $sDisplayFormat);
                            
                            // Increment the counter so we can check the track limit next time around
                            $iTotal++;
                        }
                    } // end foreach loop
                } // end if (any parsed tracks)
            }
            else
            {
                echo 'Please <a href="' . get_bloginfo('wpurl') . '/wp-admin/options-general.php?page=fmtuner/fmtuner.php">set fmTuner options</a> in your WordPress administration panel.';
            } // end if (username)
        }
        else
        {
            echo 'fmTuner requires PHP version 5 or greater.  Please contact your web host for more information.';
        } // end PHP5 check
    } // end fmtuner()
    
    
    
    // Fetch a given URL using file_get_contents or cURL
    function fmtuner_fetch($sUrl)
    {
        // Check if file_get_contents will work
        if ( ini_get('allow_url_fopen') && function_exists('file_get_contents') && $sUrl )
        {
            // Use file_get_contents
            return file_get_contents($sUrl);
        }
        elseif ( function_exists('curl_init') && $sUrl )
        {
            // Fall back to cURL
            $hCurl = curl_init();
            $iTimeout = 5;
            curl_setopt($hCurl, CURLOPT_URL, $sUrl);
            curl_setopt($hCurl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($hCurl, CURLOPT_CONNECTTIMEOUT, $iTimeout);
            $sFileContents = curl_exec($hCurl);
            curl_close($hCurl);
            return $sFileContents;
        }
        else
        {
            return false;
        }
    }
    
    
    
    // Add default options to the DB
    function add_fmtuner_options()
    {
        add_option('fmtuner_cachepath', dirname(__FILE__).'/fmtuner_cache.xml'); // Default cache location
        add_option('fmtuner_username', ''); // Default to ''
        add_option('fmtuner_track_type', 'user.getrecenttracks'); // Default to 'Recent Tracks'
        add_option('fmtuner_update_frequency', 3600); // Default to 'Every hour'
        add_option('fmtuner_track_limit', 2); // Default to max of 2 tracks
        add_option('fmtuner_display_format', '<li>[::artist::] - [::title::]<img src="[::image::]" alt="[::title::] by [::artist::]" /></li>'); // Default format
        add_option('fmtuner_placeholder_image', ''); // Default placeholder image
    }
    
    
    
    // Delete the cache file and options stored in the DB
    function delete_fmtuner_options()
    {
        $sCachePath = get_option('fmtuner_cachepath');
        if (file_exists($sCachePath))
            unlink($sCachePath);
        
        delete_option('fmtuner_cachepath');
        delete_option('fmtuner_username');
        delete_option('fmtuner_track_type');
        delete_option('fmtuner_update_frequency');
        delete_option('fmtuner_track_limit');
        delete_option('fmtuner_display_format');
        delete_option('fmtuner_placeholder_image');
    }
    
    
    
    // Add the options page to the admin area under Settings when called
    function setup_fmtuner_options()
    {
        add_options_page('fmTuner Settings', 'fmTuner', 1, __FILE__, 'fmtuner_options');
    }
    
    
    
    // Add a Settings link to the plugin actions list
    function setup_fmtuner_settings_link($aActionLinks)
    {
        $sSettingsLink = '<a class="edit" title="Change fmTuner settings" href="options-general.php?page=' . plugin_basename(__FILE__) . '">Settings</a>';
        array_unshift($aActionLinks, $sSettingsLink); 
        return $aActionLinks; 
    }
    
    
    
    // Hook into WordPress to add a Settings link
    add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'setup_fmtuner_settings_link');
    
    
    
    // Register fmTuner plugin activation/deactivation hooks
    register_activation_hook(__FILE__, 'add_fmtuner_options');
    register_deactivation_hook(__FILE__, 'delete_fmtuner_options');
    
    
    
    // Hook into WordPress to call setup_fmtuner_options() when the admin menu is loaded
    add_action('admin_menu', 'setup_fmtuner_options');
    
    
    
    // Display the options page in wp-admin
    function fmtuner_options()
    { ?>
        <div id="fmtuner_body">
            <div class="wrap">
<?php
        if (function_exists('simplexml_load_string') && function_exists('file_put_contents'))
        {
            // Fetch XML again, since key options (username, track type) may have changed
            $sBaseUrl = 'http://ws.audioscrobbler.com/2.0/?method=';
            $sMethod = get_option('fmtuner_track_type');
            $sUsername = get_option('fmtuner_username');
            $iTrackLimit = get_option('fmtuner_track_limit');
            $iTrackLimitWithBuffer = $iTrackLimit + 15; // Grab extra in case some albums don't have artwork
            $sApiKey = 'ff0eaca3b7e2660755d6c652af7b0489';
            $sApiUrl = "{$sBaseUrl}{$sMethod}&user={$sUsername}&api_key={$sApiKey}&limit={$iTrackLimitWithBuffer}";
            if ($sUsername != '')
            {
                $sTracksXml = fmtuner_fetch($sApiUrl);
				$sTracksXml = str_ireplace('http:', 'https:', $sTracksXml);
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
                <div class="icon32" id="icon-options-general"><br /></div>
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
                                    <span class="description">Enter your <a href="http://www.last.fm/home" target="_blank">Last.fm</a> username</span>
                                </td>
                            </tr>
                            <tr valign="top">
                                <th scope="row">Track Type</th>
                                <td>
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
                                </td>
                            </tr>
                            <tr valign="top">
                                <th scope="row">
                                    <label for="fmtuner_track_limit">Track Limit</label>
                                </th>
                                <td>
                                    <input type="text" size="3" value="<?php echo get_option('fmtuner_track_limit'); ?>" id="fmtuner_track_limit" name="fmtuner_track_limit" />
                                    <span class="description">How many tracks should be displayed at most?</span>
                                </td>
                            </tr>
                            <tr valign="top">
                                <th scope="row">
                                    <label for="fmtuner_update_frequency">Update Frequency</label>
                                </th>
                                <td>
                                    <select id="fmtuner_update_frequency" name="fmtuner_update_frequency">
                                        <?php $iUpdateFrequency = get_option('fmtuner_update_frequency'); ?>
                                        <option <?php if ($iUpdateFrequency == 900) { echo 'selected="selected" '; } ?> value="900">Every 15 minutes</option>
                                        <option <?php if ($iUpdateFrequency == 1800) { echo 'selected="selected" '; } ?> value="1800">Every 30 minutes</option>
                                        <option <?php if ($iUpdateFrequency == 3600) { echo 'selected="selected" '; } ?> value="3600">Every hour</option>
                                        <option <?php if ($iUpdateFrequency == 86400) { echo 'selected="selected" '; } ?> value="86400">Every day</option>
                                    </select>
                                    <span class="description">How often should fmTuner update from Last.fm?</span>
                                </td>
                            </tr>
                            <tr valign="top">
                                <th scope="row">
                                    <label for="fmtuner_display_format">Display Format</label>
                                </th>
                                <td>
                                    The fmTuner tags below can be used among standard <abbr title="HyperText Markup Language">HTML</abbr> to customize the album display format.  Tags can be used more than once, or completely left out, depending on your preferences.  The block of code you design below will be used for each track, so put other unrelated code (like <code>&lt;ul&gt;</code> HTML tags, for example) around your fmTuner call in your WordPress template.
                                    <table>
                                        <tr>
                                            <td><code>[::album::]</code></td>
                                            <td>Album name (only available for <strong>Recent tracks</strong>)</td>
                                        </tr>
                                        <tr>
                                            <td><code>[::artist::]</code></td>
                                            <td>Artist name</td>
                                        </tr>
                                        <tr>
                                            <td><code>[::image::]</code></td>
                                            <td>Album artwork address (usually around 120&times;120 pixels in size, but may not be perfectly square)</td>
                                        </tr>
                                        <tr>
                                            <td><code>[::number::]</code></td>
                                            <td>Track number within the fmTuner set (for a numbered list)</td>
                                        </tr>
                                        <tr>
                                            <td><code>[::title::]</code></td>
                                            <td>Track title</td>
                                        </tr>
                                        <tr>
                                            <td><code>[::url::]</code></td>
                                            <td>Last.fm track address</td>
                                        </tr>
                                    </table>
                                    <textarea class="code" style="width: 98%; font-size: 12px;" id="fmtuner_display_format" rows="8" cols="60" name="fmtuner_display_format"><?php echo get_option('fmtuner_display_format'); ?></textarea>
                                    Once your display format code is designed above, don't forget to put <code>&lt;?php if(function_exists('fmtuner')) { fmtuner(); } ?&gt;</code> into one of your WordPress template files, such as <code>wp-content/themes/themename/sidebar.php</code>.
                                </td>
                            </tr>
                            <tr valign="top">
                                <th scope="row">
                                    <label for="fmtuner_placeholder_image">Placeholder Image Address</label>
                                </th>
                                <td>
                                    <input type="text" size="80" value="<?php echo get_option('fmtuner_placeholder_image'); ?>" id="fmtuner_placeholder_image" name="fmtuner_placeholder_image" />
                                    <br />
                                    When no album artwork address is provided by Last.fm, this placeholder address will be substituted.<br />Leave this field blank to skip tracks lacking artwork.<br />You can also use <code>[::template_directory::]</code> in this field to customize the placeholder on a per-theme basis.<br />Examples: <code>http://www.example.com/blank.jpg</code> or <code>[::template_directory::]/images/placeholder.jpg</code>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <p class="submit">
                        <input type="hidden" name="action" value="update" />
                        <input type="hidden" name="page_options" value="fmtuner_username,fmtuner_track_type,fmtuner_update_frequency,fmtuner_track_limit,fmtuner_display_format,fmtuner_placeholder_image" />
                        <input type="submit" name="Submit" value="<?php _e('Save Changes') ?>" />
                    </p>
                </form>
            </div>
        </div>
            
        <?php
    }
    
?>
