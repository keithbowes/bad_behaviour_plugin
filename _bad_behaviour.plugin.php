<?php
/**
 *
 * This file implements the Bad Behaviour plugin for {@link http://b2evolution.net/}.
 *
 * @copyright (c)2009 by Walter Cruz - {@link http://waltercruz.com/}.
 *
 * @license GNU General Public License 3 (GPL) - http://www.opensource.org/licenses/gpl-license.php
 *
 * @package plugins
 *
 * @author Walter Cruz
 *
 */

define('BB2_CWD', dirname(__FILE__));
// Calls inward to Bad Behavor itself.
require_once(BB2_CWD . "/bad-behavior/core.inc.php");
require_once(BB2_CWD . "/bad-behavior-mysql.php");

/**
 * Bad Behaviour Plugin
 *
 * This plugin implements a version of bad behaviour
 *
 * @package plugins
 */
class bad_behaviour_plugin extends Plugin
{
	/**
	 * Code, if this is a renderer or pingback plugin.
	 */
	var $code = 'b2_bad_behaviour';
	var $priority = 50;
	var $version = '0.4';
	var $author = 'https://github.com/keithbowes/bad_behaviour_plugin';
	var $help_url = 'http://bad-behavior.ioerror.us/support/';
	var $group = 'antispam';

	var $apply_rendering = 'opt-in';
	var $number_of_installs = 1;

	/* Workaround to get Plugin::T_() to work with the plugin admin page */
	var $plug;


	/**
	 * Init
	 *
	 * This gets called after a plugin has been registered/instantiated.
	 */
	function PluginInit( & $params )
	{
		if ('enabled' == $params['db_row']['plug_status'])
		{
			global $Plugins;
			$this->plug = $Plugins->get_by_code( 'b2_bad_behaviour' );
		}
		else
			$this->plug = $this;
		$this->name = $this->plug->T_('Bad Behaviour Plugin for b2evolution');
		$this->short_desc = $this->plug->T_('The Web\'s premier link spam killer.');
	}


	function GetDbLayout()
	{
		$tablename = $this->get_sql_table('bad_behavior');

		/* If the table doesn't exist, create it */
		$res = bb2_db_query("SHOW TABLES LIKE '$tablename'");
		if (0 == bb2_db_num_rows($res))
		{
			return array(bb2_table_structure($tablename));
		}
		else
		{
			/* If the table does exist, change the old field names 'kkey' and 'request_key' to the new one 'key' */
			$res = bb2_db_query("SHOW COLUMNS FROM `$tablename`");
			$num_rows = bb2_db_num_rows($res);
			for ($i = 1; $num_rows > 1 && $i < $num_rows; $i++)
			{
				$field_name = $res[$i]['Field'];
				if ('kkey' == $field_name || 'request_key' == $field_name)
				{
					bb2_db_query("ALTER TABLE `$tablename` CHANGE `$field_name` `key` TEXT NOT NULL");
					break;
				}
			}
		}
	}

	function SkinBeginHtmlHead( & $params )
	{
		global $bb2_timer_total;
		global $bb2_javascript;
		add_headline("\n<!-- " . $this->T_('Bad Behaviour') . ' ' . BB2_VERSION . ', ' . $this->T_('run time: ') . number_format(1000 * $bb2_timer_total, 3) . ' ' . $this->T_('milliseconds') . " -->\n");
		add_headline($bb2_javascript);
	}

	function BeforeBlogDisplay ( & $params )
	{
		global $bb2_result, $bb2_timer_total;
		$bb2_mtime = explode(" ", microtime());
		$bb2_timer_start = $bb2_mtime[1] + $bb2_mtime[0];

		$bb2_result = bb2_start(bb2_read_settings());

		$bb2_mtime = explode(" ", microtime());
		$bb2_timer_stop = $bb2_mtime[1] + $bb2_mtime[0];
		$bb2_timer_total = $bb2_timer_stop - $bb2_timer_start;
	}

	/**
	 * Define settings that the plugin uses/provides.
	 */
	function get_default_value($name, $default)
	{
		if (isset($this->settings[$name]))
			return $this->settings[$name];
		else
			return $default;
	}

	function GetDefaultSettings( & $params )
	{
		$this->settings = (array) @parse_ini_file(BB2_CWD . '/settings.ini');
		$whitelist = (array) @parse_ini_file(BB2_CWD . '/whitelist.ini');

		return array(
			'display_stats' => array(
				'label' => $this->plug->T_('Display Stats'),
				'type' => 'checkbox',
				'defaultvalue' => $this->get_default_value('display_stats', 1),
			),
			'strict' => array(
				'label' => $this->plug->T_('Strict'),
				'type' => 'checkbox',
				'defaultvalue' => $this->get_default_value('strict', 0),
				'note' => $this->plug->T_('Strict checking (blocks more spam but may block some people)')
			),
			'logging' => array(
				'label' => $this->plug->T_('Logging'),
				'type' => 'checkbox',
				'defaultvalue' => $this->get_default_value('logging', 1),
				'note' => $this->plug->T_('HTTP request logging (recommended)'),
			),
			'verbose' => array(
				'label' => $this->plug->T_('Verbose Logging'),
				'type'=>'checkbox',
				'defaultvalue' => $this->get_default_value('verbose', 0),
				'note' => $this->plug->T_('Log all requests'),
			),
			'httpbl_key' =>array(
				'label' => $this->plug->T_('http:BL Access Key'),
				'type'  => 'text',
				'maxlength' => 12,
				'defaultvalue' => $this->get_default_value('httpbl_key', ''),
			),
			'httpbl_threat' => array(
				'label' => $this->plug->T_('Minimum Threat Level (25 is recommended)'),
				'type'  => 'text',
				'defaultvalue' => $this->get_default_value('httpbl_threat', 25),
			),
			'httpbl_maxage' => array(
				'label' => $this->plug->T_('Maximum Age of Data (30 is recommended)'),
				'type'  => 'text',
				'defaultvalue' => $this->get_default_value('httpbl_maxage', 30),
			),
			'offsite_forms' => array(
				'label' => $this->plug->T_('Offsite forms'),
				'type' => 'checkbox',
				'defaultvalue' => $this->get_default_value('offsete_forms', 0),
				'note' => $this->plug->T_('Allow forms submitted from other websites'),
			),
			'eu_cookie' => array(
				'label' => $this->plug->T_('Strict EU cookies'),
				'type' => 'checkbox',
				'defaultvalue' => $this->get_default_value('eu_cookie', 0),
				'note' => $this->plug->T_('Disables cookie-based filters'),
			),
			'reverse_proxy' => array(
				'label' => $this->plug->T_('Reverse Proxy'),
				'type' => 'checkbox',
				'defaultvalue' => $this->get_default_value('reverse_proxy', 0),
				'note' => $this->plug->T_('This site is behind a reverse proxy'),
			),
			'reverse_proxy_header' => array(
				'label' => $this->plug->T_('Reverse proxy header'),
				'type' => 'text',
				'defaultvalue' => $this->get_default_value('reverse_proxy_header', 'X-Forwarded-For'),
			),
			'reverse_proxy_addresses' => array(
				'label' => $this->plug->T_('Reverse proxy addresses'),
				'type' => 'textarea',
				'defaultvalue' => implode("\n", (array) $this->get_default_value('reverse_proxy_addresses', array())),
				'note' => $this->plug->T_('List of IP addresses of your reverse proxy.  ') . $this->plug->T_('One per line.'),
			),

			/* Whitelist options */
			'whitelist_ips' => array(
				'label' => $this->plug->T_('Whitelist IP addresses'),
				'type' => 'textarea',
				'defaultvalue' => implode("\n", (array) @$whitelist['ip']),
				'note' => $this->plug->T_('List of IP addresses that are never filtered.  ') . $this->plug->T_('One per line.'),
			),
			'whitelist_user_agents' => array(
				'label' => $this->plug->T_('Whitelist user agents'),
				'type' => 'textarea',
				'defaultvalue' => implode("\n", (array) @$whitelist['useragent']),
				'note' => $this->plug->T_('List of user agents that are never filtered.  ') . $this->plug->T_('One per line.'),
			),
			'whitelist_urls' => array(
				'label' => $this->plug->T_('Whitelist URLs'),
				'type' => 'textarea',
				'defaultvalue' => implode("\n", (array) @$whitelist['url']),
				'note' => $this->plug->T_('List of URLs that are never filtered.  ') . $this->plug->T_('One per line.'),
			),
		);
	}

	function AdminAfterMenuInit()
	{
		$this->register_menu_entry( $this->T_('Bad Behaviour') );
	}

	function AdminTabPayload()
	{
		global $baseurl;
		require_once(BB2_CORE . '/responses.inc.php');

		$query = "SELECT * FROM " . $this->get_sql_table('bad_behavior') . " WHERE `key` NOT LIKE '00000000'";
		$blocked_list = bb2_db_query( $query );
		echo '<h2>'.$this->T_('Bad Behaviour') . $this->T_(' has blocked the following access attempts in the last 7 days').'</h2>';
		$count = 0;
		foreach( $blocked_list as $access_attempt ) 
		{
			echo '<table class="grouped" cellspacing="0">';
			echo '<tbody>';
			echo '<tr>'."\n";
			echo '<th width="10%">'. $this->T_('IP') . '</th>';
			echo '<td><a href="http://whois.domaintools.com/'. $access_attempt['ip'] .'" title="' . $this->T_('More information about this ip address') . '">'. $access_attempt['ip'] .'</a></td>'."\n";
			echo '</tr>'."\n";

			echo '<tr>'."\n";
			echo '<th width="10%">'.$this->T_('Date').'</th>';
			echo '<td>'. $access_attempt['date'] .'</td>'."\n";
			echo '</tr>'."\n";

			$url = parse_url($baseurl);
			$url['path'] = $access_attempt['request_uri'];
			$url = sprintf("%s://%s:%u%s", $url['scheme'], $url['host'], $url['port'], $url['path']);
			echo '<tr>'."\n";
			echo '<th width="10%">'.$this->T_('Request URI').'</th>';
			echo '<td><a href="' . $url .'" title="' . $this->T_('View this uri on your blog') . '">'. $access_attempt['request_uri'] .'</a></td>'."\n";
			echo '</tr>'."\n";

			echo '<tr>'."\n";
			echo '<th width="10%">'.$this->T_('HTTP Headers').'</th>';
			echo '<td>'. nl2br($access_attempt['http_headers']) .'</td>'."\n";
			echo '</tr>'."\n";

			echo '<tr>'."\n";
			echo '<th width="10%">'.$this->T_('User Agent').'</th>';
			echo '<td>'. $access_attempt['user_agent'] .'</td>'."\n";
			echo '</tr>'."\n";

			$resp = bb2_get_response($access_attempt['key']);
			echo '<th width="10%">'.$this->T_('Explanation').'</th>';
			echo '<td>'. $resp['explanation'] . ' (' . $resp['log'] .'.)</td>'."\n";
			echo '</tr>'."\n";

			echo '<th width="10%">'.$this->T_('Code').'</th>';
			echo '<td>'. $resp['response'] .'</td>'."\n";
			echo '</tr>'."\n";

			echo '</tbody></table>'."\n";

			$count++;
      }
		echo '<p>' . $this->T_('A total of ') . $count . $this->T_(' access attempts blocked.') . '</p>';
		echo '<p>' .$this->T_('More about ') . '<a href="http://www.bad-behavior.ioerror.us/">' .$this->T_('Bad Behaviour') . '</a></p>';
	}


	function SkinEndHtmlBody( & $params )
	{
		global $bb2_result;
		$settings = bb2_read_settings();
		$dbname = $settings['log_table'];

		if ($settings['display_stats'])
		{
			$query = "SELECT COUNT(*) FROM $dbname WHERE `key` NOT LIKE '00000000'";
			$blocked = bb2_db_query( $query );

			if ($blocked !== FALSE)
			{
				echo sprintf('<div><a href="http://www.bad-behavior.ioerror.us/"><cite>%1$s</cite></a> %2$s <strong>%3$s</strong> %4$s</div>', $this->T_('Bad Behaviour'), $this->T_('has blocked'), $blocked[0]["COUNT(*)"], $this->T_('access attempts in the last 7 days.'));
			}
		}
		if (@!empty($bb2_result)) {
		echo sprintf($this->T_("\n<!-- %s result was %s! This request would have been blocked. -->\n"), $this->T_('Bad Behaviour'), $bb2_result);
		unset($bb2_result);
		}
	}


	/**
	 * Define user settings that the plugin uses/provides.
	 */
	function GetDefaultUserSettings( & $params )
	{
		return array(

			);
	}


}

function bb2_db_date() {
	return gmdate('Y-m-d H:i:s');
}

// Return affected rows from most recent query.
function bb2_db_affected_rows() {
	global $DB;

	return $DB->rows_affected;
}

// Escape a string for database usage
function bb2_db_escape($string) {
	global $DB;

	return $DB->escape($string);
}

// Return the number of rows in a particular query.
function bb2_db_num_rows($result) {
	if ($result !== FALSE)
		return count($result);
	return 0;
}

// Run a query and return the results, if any.
// Should return FALSE if an error occurred.
// Bad Behavior will use the return value here in other callbacks.
function bb2_db_query($query) {
	global $DB, $debug;

	$DB->show_errors = FALSE;
	$result = $DB->get_results($query, ARRAY_A);
	if (isset($debug) && $debug !== 0)
		$DB->show_errors = TRUE;
	if ($DB->error) {
		return FALSE;
	}
	return $result;
}

// Return all rows in a particular query.
// Should contain an array of all rows generated by calling mysql_fetch_assoc()
// or equivalent and appending the result of each call to an array.
// For WP this is pretty much a no-op.
function bb2_db_rows($result) {
	return $result;
}

// Return emergency contact email address.
function bb2_email() {
	global $admin_email;
	return $admin_email;
}

// retrieve whitelist
function bb2_read_whitelist() {
	$settings = bb2_read_settings();
	$whitelist = (array) @parse_ini_file(BB2_CWD . '/whitelist.ini');

	$ret = array();
	if (($set = $settings['whitelist_ips']) !== NULL)
		$ret['ip'] = explode("\n", $set);
	if (($set = $settings['whitelist_user_agents']) !== NULL)
		$ret['useragent'] = explode("\n", $set);
	if (($set = $settings['whitelist_urls']) !== NULL)
		$ret['url'] = explode("\n", $set);

	$ret = @array_merge($whitelist, $ret);
	return $ret;
}

// retrieve settings from database
function bb2_read_settings() {
	global $Plugins;
	$plug = $Plugins->get_by_code( 'b2_bad_behaviour' );
	$ret = array();
	$ret['log_table'] = $plug->get_sql_table('bad_behavior');

	/* We only want to fill the element of the array ret
	 * if the setting has been set.
	 * Otherwise, we'll read from settings.ini, if it exists.
	 * See the array_merge() below. */
	if (($set = $plug->Settings->get('display_stats')) !== NULL)
		$ret['display_stats'] = $set;
	if (($set = $plug->Settings->get('strict')) !== NULL)
		$ret['strict'] = $set;
	if (($set = $plug->Settings->get('verbose')) !== NULL)
		$ret['verbose'] = $set;
	if (($set = $plug->Settings->get('logging')) !== NULL)
		$ret['logging'] = $set;
	if (($set = $plug->Settings->get('httpbl_key')) !== NULL)
		$ret['httpbl_key'] = $set;
	if (($set = $plug->Settings->get('httpbl_threat')) !== NULL)
		$ret['httpbl_threat'] = $set;
	if (($set = $plug->Settings->get('httpbl_maxage')) !== NULL)
		$ret['httpbl_maxage'] = $set;
	if (($set = $plug->Settings->get('offsite_forms')) !== NULL)
		$ret['offsite_forms'] = $set;
	if (($set = $plug->Settings->get('eu_cookie')) !== NULL)
		$ret['eu_cookie'] = $set;
	if (($set = $plug->Settings->get('reverse_proxy')) !== NULL)
		$ret['reverse_proxy'] = $set;
	if (($setlm = $plug->Settings->get('reverse_proxy_header')) !== NULL)
		$ret['reverse_proxy_header'] = $set;
	if (($set = $plug->Settings->get('reverse_proxy_addresses')) !== NULL)
		$ret['reverse_proxy_addresses'] = explode("\n", $set);


	/* Whitelist settings */
	$ret['whitelist_ips'] = $plug->Settings->get('whitelist_ips');
	$ret['whitelist_user_agents'] = $plug->Settings->get('whitelist_user_agents');
	$ret['whitelist_urls'] = $plug->Settings->get('whitelist_urls');

	$settings = @parse_ini_file(BB2_CWD . "/settings.ini");
	if (!$settings) $settings = array();

	$ret = @array_merge($settings, $ret);
	return $ret;
}

// See bad_behaviour_plugin::GetDefaultSettings()
function bb2_write_settings($settings) {
	return false;
}

// See bad_behavior_plugin::GetDbLayout()
function bb2_install() {
	return false;
}

// See bad_behaviour_plugin::SkinBeginHtmlHead()
function bb2_insert_head() {
	return false;
}

function bb2_approved_callback($settings, $package) {
	global $bb2_package;

	// Save package for possible later use
	$bb2_package = $package;
}

// Capture missed spam and log it
function bb2_capture_spam($id, $comment) {
	global $bb2_package;

	// Capture only spam
	if ('spam' != $comment->comment_approved) return;

	// Don't capture if HTTP request no longer active
	if (array_key_exists("request_entity", $bb2_package) && array_key_exists("author", $bb2_package['request_entity']) && $bb2_package['request_entity']['author'] == $comment->comment_author) {
		bb2_db_query(bb2_insert(bb2_read_settings(), $bb2_package, "00000000"));
	}
}

// See bad_behaviour_plugin::SkinEndHtmlBody()
function bb2_insert_stats($force = false) {
	return false;
}

// Return the top-level relative path of wherever we are (for cookies)
function bb2_relative_path() {
	global $Blog;
	$url = parse_url($Blog->gen_baseurl());
	if (array_key_exists('path', $url))
		return $url['path'];
	return '';
}
?>
