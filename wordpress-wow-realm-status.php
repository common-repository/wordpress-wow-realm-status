<?php
/*
  Plugin Name: Improved WoW Realm Status
  Plugin URI: http://wowhead-tooltips.com/tools/wp-realm-status
  Description: Will display a page of all World of Warcraft US realms and their up/down status, also has a widget where you can always know your realm(s) status.  Based on the plugin by <a href="http://www.yourfirefly.com">Ryan Cain</a>, improved and added more functionality.  You can now list multiple realms in the widget by separating them with a comma.
  Version: 0.4
  Author: Adam Koch
  Author URI: http://wowhead-tooltips.com
*/

/*  Copyright 2009  Adam Koch  (email : support@wowhead-tooltips.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

define('MAGPIE_CACHE_AGE', 10);
define('MAGPIE_CACHE_ON', 1); //2.7 Cache Bug
define('MAGPIE_INPUT_ENCODING', 'UTF-8');
define('MAGPIE_OUTPUT_ENCODING', 'UTF-8');

$realmlist_regions = array(
	'us'		=>	'US - United States Realms',
	'eu_en'	=>	'EU - English Speaking Realms',
	'eu_es'	=>	'EU - Spanish Speaking Realms',
	'eu_fr'	=>	'EU - French Speaking Realms',
	'eu_de'	=>	'EU - German Speaking Realms',
	'eu_ru'	=>	'EU - Russian Speaking Realms'
);
$realmstatus_options['widget_fields']['title'] = array('label'=>'Title:', 'type'=>'text', 'default'=>'WoW Realm Status');
$realmstatus_options['widget_fields']['realm'] = array('label'=>'Realm:', 'type'=>'text', 'default'=>'');
$realmstatus_plugin_basename = plugin_basename(dirname(__FILE__));
$realmstatus_images_url = WP_PLUGIN_URL . '/' . $realmstatus_plugin_basename . '/images/';

function realm_status($realm)
{
	global $realmstatus_options, $realmstatus_images_url;
	include_once(ABSPATH . WPINC . '/rss.php');
	
	// account for multiple realms
	if (strpos($realm, ',') !== false)
		$realms = explode(',', $realm);
	elseif (strpos($realm, ', ') !== false)
		$realms = explode(', ', $realm);
	else
		$realms = $realm;
	
	echo '<ul>';
	
	// check if there are any eu realms, if so then go ahead and pull the entire xml
	if (strpos($realm, 'eu_') !== false)
		$eu_realms = get_eu_realms();
	

	foreach ($realms as $realm)
	{
		if (strpos($realm, ':') === false)
			break;
		
		$r_temp = explode(':', $realm);
		
		$realm_name 	=	$r_temp[1];	// realm name
		$realm_region	=	(strpos($r_temp[0], '_') !== false) ? substr($r_temp[0], 0, 2) : $r_temp[0];	// realm region (us or eu)
		$realm_lang	=	(strpos($r_temp[0], '_') !== false) ? substr($r_temp[0], 3, 2) : '';	// realm lang (en, es, fr, de, or ru)
		
		if ($realm_region == 'eu')
		{
			// make sure language exists
			if (!array_key_exists($realm_lang, $eu_realms))
			{
				echo '<li>Lang "' . $realm_lang . '" not found.</li>';
			}
			else
			{
				// make sure the realm is there
				if (!array_key_exists(strtolower(str_replace(' ', '', $realm_name)), $eu_realms[$realm_lang]))
				{
					echo '<li>Realm "' . $realm_name . '" not found.</li>';
				}
				else
				{
					$formatted = strtolower(str_replace(' ', '', $realm_name));
					$color = ($eu_realms[$realm_lang][$formatted]['status'] = 'Up') ? '#234303' : '#660D02';
					echo '<li><img src="' . $realmstatus_images_url . $eu_realms[$realm_lang][$formatted]['status_image'] . '" alt="' . $eu_realms[$realm_lang][$formatted]['status'] . '" /><span style="color: ' . $color . ';"><acronym title="EU ' . $eu_realms[$realm_lang][$formatted]['lang'] . '">' . $realm_name . '</acronym></span>&nbsp;<strong>Type:</strong> ' . $eu_realms[$realm_lang][$formatted]['type'] . '</li>';
				}
			}
		}
		elseif ($realm_region == 'us')
		{
			$url = "http://www.worldofwarcraft.com/realmstatus/status-events-rss.html?r=" . str_replace("'", "%27", str_replace(' ', '+', $realm_name));
			$status = fetch_rss($url);
			if ( $realm == '' )
			{
				echo 'RSS not configured';
			}
			else
			{
				if ( empty($status->items) )
				{
					echo '<li>Could not retrieve realm status. ' . $realm_name . '</li>';
				}
				else
				{
					foreach ( $status->items as $message )
					{
						if (strstr($message['title'], 'Up'))
						{
							$trim = 2;
							$state = 'up.png';
						}
						else
						{
							$trim = 4;
							$state = 'down.png';
						}
						$type = substr($message['title'], strlen($realm_name), strlen($message['title']) - ($trim + 7) - strlen($realm_name));
						$population = substr($message['description'], 12);
					
						if (strstr($message['title'], 'Up'))
							$color = '#234303';
						else
							$color = '#660D02';
					
						$realm = str_replace('+', ' ', $realm);
						echo '<li><img src="' . $realmstatus_images_url . $state . '" alt="" /><span style="color: ' . $color . '; cursor: pointer;"><acronym title="' . trim($type) . '">' . $realm_name . '</acronym></span> <strong>Pop: </strong>' . $population . '</li>';
						 
						$i++;
					
						if ( $i >= $num ) break;
					}
				}
			}
		}
	}
	echo '</ul>';
}

// Widget initialization
function widget_realmstatus_init() {

	if ( !function_exists('register_sidebar_widget') )
		return;
	
	$check_options = get_option('widget_realmstatus');
	
	if ($check_options['number']=='') {
		$check_options['number'] = 1;
		update_option('widget_realmstatus', $check_options);
	}
  
	function widget_realmstatus($args, $number = 1) {

		global $realmstatus_options;
		
		// $args is an array of strings that help widgets to conform to
		// the active theme: before_widget, before_title, after_widget,
		// and after_title are the array keys. Default tags: li and h2.
		extract($args);

		// Each widget can store its own options. We keep strings here.
		include_once(ABSPATH . WPINC . '/rss.php');
		$options = get_option('widget_realmstatus');
		
		// fill options with default values if value is not set
		$item = $options[$number];
		foreach($realmstatus_options['widget_fields'] as $key => $field) {
			if (! isset($item[$key])) {
				$item[$key] = $field['default'];
			}
		}

		// These lines generate our output.
    		echo $before_widget . $before_title . $item['title'] . $after_title;
		realm_status($item['realm']);
		echo $after_widget;
				
	}

	// This is the function that outputs the form to let the users edit
	// the widget's title. It's an optional feature that users cry for.
	function widget_realmstatus_control($number) {
	
		global $realmstatus_options;

		// Get our options and see if we're handling a form submission.
		$options = get_option('widget_realmstatus');
		if ( isset($_POST['realmstatus-submit']) ) {

			foreach($realmstatus_options['widget_fields'] as $key => $field) {
				$options[$number][$key] = $field['default'];
				$field_name = sprintf('%s_%s_%s', $realmstatus_options['prefix'], $key, $number);

				if ($field['type'] == 'text') {
					$options[$number][$key] = strip_tags(stripslashes($_POST[$field_name]));
				} elseif ($field['type'] == 'checkbox') {
					$options[$number][$key] = isset($_POST[$field_name]);
				}
			}

			update_option('widget_realmstatus', $options);
		}

		foreach($realmstatus_options['widget_fields'] as $key => $field) {
			
			$field_name = sprintf('%s_%s_%s', $realmstatus_options['prefix'], $key, $number);
			$field_checked = '';
			if ($field['type'] == 'text') {
				$field_value = htmlspecialchars($options[$number][$key], ENT_QUOTES);
			} elseif ($field['type'] == 'checkbox') {
				$field_value = 1;
				if (! empty($options[$number][$key])) {
					$field_checked = 'checked="checked"';
				}
			}
			
			printf('<p style="text-align:right;" class="realmstatus_field"><label for="%s">%s <input id="%s" name="%s" type="%s" value="%s" class="%s" %s /></label></p>',
				$field_name, __($field['label']), $field_name, $field_name, $field['type'], $field_value, $field['type'], $field_checked);
		}

		echo '<input type="hidden" id="realmstatus-submit" name="realmstatus-submit" value="1" />';
	}
	
	function widget_realmstatus_setup() {
		$options = $newoptions = get_option('widget_realmstatus');
		
		if ( isset($_POST['realmstatus-number-submit']) ) {
			$number = (int) $_POST['realmstatus-number'];
			$newoptions['number'] = $number;
		}
		
		if ( $options != $newoptions ) {
			update_option('widget_realmstatus', $newoptions);
			widget_realmstatus_register();
		}
	}
	
	
	function widget_realmstatus_page() {
		$options = $newoptions = get_option('widget_realmstatus');
	?>
		<div class="wrap">
			<form method="POST">
				<h2><?php _e('WoW Realm Status Widgets'); ?></h2>
				<p style="line-height: 30px;"><?php _e('How many WoW Realm Status widgets would you like?'); ?>
				<select id="realmstatus-number" name="realmstatus-number" value="<?php echo $options['number']; ?>">
	<?php for ( $i = 1; $i < 10; ++$i ) echo "<option value='$i' ".($options['number']==$i ? "selected='selected'" : '').">$i</option>"; ?>
				</select>
				<span class="submit"><input type="submit" name="realmstatus-number-submit" id="realmstatus-number-submit" value="<?php echo attribute_escape(__('Save')); ?>" /></span></p>
			</form>
		</div>
	<?php
	}
	
	
	function widget_realmstatus_register() {
		
		$options = get_option('widget_realmstatus');
		$dims = array('width' => 300, 'height' => 300);
		$class = array('classname' => 'widget_realmstatus');

		for ($i = 1; $i <= 9; $i++) {
			$name = sprintf(__('WoW Realm Status #%d'), $i);
			$id = "realmstatus-$i"; // Never never never translate an id
			wp_register_sidebar_widget($id, $name, $i <= $options['number'] ? 'widget_realmstatus' : /* unregister */ '', $class, $i);
			wp_register_widget_control($id, $name, $i <= $options['number'] ? 'widget_realmstatus_control' : /* unregister */ '', $dims, $i);
		}
		
		add_action('sidebar_admin_setup', 'widget_realmstatus_setup');
		add_action('sidebar_admin_page', 'widget_realmstatus_page');
	}

	widget_realmstatus_register();
}

/**
 * Lists the realm list from WoW's US site and its status
 * @param string $content
 * @return 
 */
function realmlist_status($content)
{
	global $realmstatus_images_url, $realmlist_regions;

	// get the region from $_POST
	$region = (isset($_POST['region'])) ? $_POST['region'] : get_option('realmlist_region');
	
	$url_us = 'http://www.worldofwarcraft.com/realmstatus/status.xml';
	$url_eu = 'http://www.wow-europe.com/realmstatus/index.xml';
	
	// set the url depending on region
	$url = ($region == 'us') ? $url_us : $url_eu;

	// get the language, if specified
	$lang = (strpos($region, 'us') === false) ? substr($region, strpos($region, '_') + 1, 2) : '';
	
	// print form to change locale
	?>
	<div align="center">
		<form action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>" method="post">
			<strong>Change Realm List:</strong>
			<select name="region">
				<?php
				foreach ($realmlist_regions as $opt => $value)
				{
					echo '<option value="' . $opt . '" ';
					
					if ($region == $opt)
						echo 'selected="selected"';
					
					echo '>' . $value . '</option>';
				}
				?>	
			</select>
			<input type="submit" name="do_submit" value="<?php _e('Update'); ?>" />
		</form>	
	</div>
	<?php
	
	// we'll try file_get_contents first, if not then cURL
	if (ini_get('allow_url_fopen') == 1)
	{
		$xml_data = @file_get_contents($url);
	}
	else
	{
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_HEADER, 1);
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		$xml_data = curl_exec($curl);	// query wow's site
		curl_close($curl);
	}
	
	if (!$xml_data)
	{
		// query failed
		print '<span style=\"color: #ff0000; font-weight: bold;\">Failed to connect to WoW\'s site.</span>';
	}
	else
	{
		// create a new SimpleXML object from the cURL result, also stripping the CDATA
		$xml = simplexml_load_string($xml_data, 'SimpleXMLElement', LIBXML_NOCDATA);
		
		if (!$xml)
		{
			// invalid XML
			print '<span style=\"color: #ff0000; font-weight: bold;\">Result returned invalid XML.</span>';	
		}
		else
		{	
			// print our opening table
			print '
			<div align="center">
				<table width="95%" cellspacing="2" cellpadding="0">
					<tr>
						<th width="10%">Status</th>
						<th width="50%">Realm Name</th>
						<th width="20%">Type</th>
						<th width="20%">Population</th>
					</tr>		
			';
		
			$r_count = 0;
			if (trim($lang) == '')
			{
				foreach ($xml->rs->r as $realm)
				{
					$r_count++;
					$status			=	((int)$realm['s'] == 1) ? 'Up' : 'Down'; 
					$status_image	=	((int)$realm['s'] == 1) ? 'up.gif' : 'down.gif';	// up or down
				
					// print each table row
					print '
						<tr>
							<td><div align="center"><img src="' . $realmstatus_images_url . $status_image . '" alt="' . $status . '" /></div></td>
							<td>' . colorize_realm((string)$realm['n'], (int)$realm['s']) . '</td>
							<td align="center">' . colorize_realm_type((int)$realm['t']) . '</td>
							<td align="center">' . colorize_realm_population((int)$realm['l']) . '</td>
						</tr>
					';
				}
			}
			else
			{		
				// gd it blizzard standarize your sites please...	
				foreach ($xml->channel->item as $realm)
				{
					if ((string)$realm->category[1] == $lang)
					{
						$r_count++;
						
						// up or down, and the image
						$status = (strtolower(str_replace(' ', '', (string)$realm->category[0])) == 'realmup') ? 'Up' : 'Down';
						$status_image = (strtolower(str_replace(' ', '', (string)$realm->category[0])) == 'realmup') ? 'up.gif' : 'down.gif';
						
						print '
							<tr>
								<td><div align="center"><img src="' . $realmstatus_images_url . $status_image . '" alt="' . $status . '" /></div></td>
								<td>' . colorize_eu_realm((string)$realm->title, (string)$realm->category[0]) . '</td>
								<td><div align="center">' . colorize_realm_type((string)$realm->category[2]) . '</div></td>
								<td><div align="center">' . colorize_eu_realm_population((string)$realm->category[3]) . '</div></td>
							</tr>
						';
					}
				}
			}
			
			print '</table>';
			
			print '<em>Tracking ' . $r_count . ' realms.  For more information go <a href="http://www.worldofwarcraft.com/realmstatus/">here</a>.</em></div>';	// close the table and div
		}
	}
}

function get_eu_realms()
{
	// convert the shitty xml format to readable arrays
	
	$url = 'http://www.wow-europe.com/realmstatus/index.xml';
	
	// we'll try file_get_contents first, if not then cURL
	if (ini_get('allow_url_fopen') == 1)
	{
		$xml_data = @file_get_contents($url);
	}
	else
	{
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_HEADER, 1);
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		$xml_data = curl_exec($curl);	// query wow's site
		curl_close($curl);
	}
	
	$xml = simplexml_load_string($xml_data, 'SimpleXMLElement', LIBXML_NOCDATA);
	
	if (!$xml)
	{
		return false;
	}
	else
	{
		$rlist = array();
		
		foreach ($xml->channel->item as $item)
		{
			// loop through each realm and add it to our realm list
			$lang = (string)$item->category[1];
			$rtitle = strtolower(str_replace(' ', '', (string)$item->title));
			$status_image = (strtolower(str_replace(' ', '', (string)$item->category[0])) == 'realmup') ? 'up.png' : 'down.png';
			$rlist[$lang][$rtitle] = array(
				'name'		=>	(string)$item->title,
				'status'		=>	(strtolower(str_replace(' ', '', (string)$item->category[0])) == 'realmup') ? 'Up' : 'Down',
				'status_image'	=>	$status_image,
				'lang'		=>	get_eu_language((string)$item->category[1]),
				'type'		=>	(string)$item->category[2]
			);
		}
		
		return $rlist;
	}	
}

function get_eu_language($lang)
{
	switch ($lang)
	{
		case 'es':	// spanish
			return 'Spanish';
			break;
			
		case 'fr':	// french
			return 'French';
			break;
			
		case 'de':	// german
			return 'German';
			break;
			
		case 'ru':	// russian
			return 'Russian';
			break;
	
		case 'en':	// english
		default:
			return 'English';
			break;
	}
}

function colorize_eu_realm_population($cat)
{

	if ($cat == 'Full')
	{
		return '<span style="color: #FF0000;">Full</span>';
	}
	elseif ($cat == 'Recommended')
	{
		return '<span style="color: #0000FF;">Recommended</span>';
	}
	else
	{
		return '<span style="color: #C0C0C0;">Not Specified</span>';
	}
}

function colorize_eu_realm($r_name, $r_status)
{
	if (strtolower(str_replace(' ', '', $r_status)) == 'realmup')
	{
		$color = '#234303';
	}
	else
	{
		$color = '#69170B';
	}
	
	return '<span style="color: ' . $color . ';">' . $r_name . '</span>';
}

function colorize_realm_population($pop)
{
	switch ($pop)
	{
		case 1:		// low
			return '<span style="color: #234303;">Low</span>';
			break;
			
		case 2:		// medium
			return '<span style="color: #9D700C;">Medium</span>';
			break;
			
		case 3:		// high
			return '<span style="color: #69170B;">High</span>';
			break;
			
		case 4:		// max (queued)
			return '<span style="color: #FF0000;">Max (Queued)</span>';
			break;
			
		default: break;	
	}
}

/**
 * Adds coloring <span> depending on $r_status
 * @param string $r_name
 * @param string $r_status
 * @return 
 */
function colorize_realm($r_name, $r_status)
{
	return ($r_status == 1) ? '<span style="color: #234303;">' . $r_name . '</span>' : '<span style="color: #69170B;">' . $r_name . '</span>';
}

/**
 * Returns realm type from integer
 * @param string $t
 * @return 
 */
function colorize_realm_type($t)
{
	if (!is_numeric($t)) { $t = strtolower($t); }
	switch ($t)
	{	
		case "pve";	
		case 1:
			return '<span style="color: #234303;">Normal</span>';
			break;	
		
		case "pvp":
		case 2:
			return '<span style="color: #69170B;">PvP</span>';
			break;
		
		case "rp-pve":
		case 3:
			return '<span style="color: #9D700C;">RP</span>';
			break;
		
		case "rp-pvp":
		case 4:
			return '<span style="color: #9D700C;">RPPvP</span>';
			break;	
				
		default:
			return '<span style="color: #FF0000;">Unknown</span>';
			break;
	}
}

/**
 * Main Admin Page
 */
function realmstatus_menu()
{
	global $realmlist_regions;
	$realmlist_region = get_option('realmlist_region');
	
	if (isset($_POST['realmlist_region']))
	{
		update_option('realmlist_region', $_POST['realmlist_region']);
		?>
		<div class="updated fade"><p>Settings were updated.</p</div>
		<?php
		unset($_POST['realmlist_region']);
	}

	?>
	<div class="wrap">
		<h2>Manage Realm Status Defaults</h2>
		
		<form name="add_site" method="post" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>">
			<p>
				<?php _e('Default Realm List Region:'); ?><br/>
				<select name="realmlist_region">
					<?php
					foreach ($realmlist_regions as $opt => $value)
					{
						echo '<option value="' . $opt . '" ';
						
						if ($realmlist_region == $opt)
							echo 'selected="selected"';
						
						echo '>' . $value . '</option>';
					}
					?>
				</select>
			</p>
			<hr />
			<p class="submit">
				<input type="submit" name="update_options" value="<?php _e('Submit'); ?>" />
			</p>
		</form>
	</div>
	<?php
}

/**
 * Installation Function
 */
function realmstatus_activate()
{
	add_option('realmlist_region', 'us');	// default list region
}

/**
 * Uninstallation Function
 */
function realmstatus_deactivate()
{
	delete_option('realmlist_region');
}

/**
 * Admin Menu
 */
function realmstatus_admin_menu()
{
	add_submenu_page('plugins.php', 'Set Default Realm Status Options', 'Realm Status', 8, __FILE__, 'realmstatus_menu');
}

/**
 * Filters
 */
add_shortcode('realmstatus', 'realmlist_status');
add_action('widgets_init', 'widget_realmstatus_init');

// activation hooks
register_activation_hook(__FILE__, 'realmstatus_activate');
register_deactivation_hook(__FILE__, 'realmstatus_deactivate');

// admin page
add_action('admin_menu', 'realmstatus_admin_menu');
?>
