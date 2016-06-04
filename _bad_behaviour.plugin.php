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

	var $log_table;
	/* Workaround to get Plugin::T_() to work with the plugin admin page */
	var $plug;

	/**
	 * Init
	 *
	 * This gets called after a plugin has been registered/instantiated.
	 */
	function PluginInit( & $params )
	{
		if ('enabled' == @$params['db_row']['plug_status'])
		{
			global $Plugins;
			$this->plug = $Plugins->get_by_code( 'b2_bad_behaviour' );
		}
		else
			$this->plug = $this;
		$this->name = $this->plug->T_('Bad Behaviour Plugin for b2evolution');
		$this->short_desc = $this->plug->T_('The Web\'s premier link spam killer.');
		$this->log_table = $this->get_sql_table('bad_behavior');
	}

	function GetDbLayout()
	{
		$tablename = $this->log_table;

		/* If the table doesn't exist, create it */
		$res = bb2_db_query("SHOW TABLES LIKE '$tablename'");
		if (0 == bb2_db_num_rows($res))
		{
			return array(bb2_table_structure($tablename));
		}
		/* If the table does exist, do some conversions from old versions */
		else
		{
			$res = bb2_db_query("SHOW COLUMNS FROM `$tablename`");
			$num_rows = bb2_db_num_rows($res);
			for ($i = 1; $num_rows > 1 && $i < $num_rows; $i++)
			{
				$field_name = $res[$i]['Field'];
				switch ($field_name)
				{
					/* Change the default date to what it's supposed to be */
					case 'date':
						bb2_db_query("ALTER TABLE `$tablename` MODIFY `$field_name` DATETIME NOT NULL DEFAULT '1970-01-01 00:00:00'"); 
						break;
					/*change the old field names 'kkey' and 'request_key' to the new one 'key' */
					case 'kkey':
					case 'request_key':
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

		/* Use NumberFormatter if possible, so we can get localized number formats */
		if (class_exists('NumberFormatter'))
		{
			$nf =new NumberFormatter(locale_lang(false), NumberFormatter::PATTERN_DECIMAL, '#,##0.000');
			$ms = $nf->format(1000 * $bb2_timer_total);
		}
		/* If not, we can just use the US-English number format */
		else
			$ms = number_format(1000 * $bb2_timer_total, 3);

		/* TRANS: The first two format chars are for the name and version of Bad Behaviour */
		add_headline(sprintf($this->T_("\n<!-- %1\$s %2\$s, run time: %3\$s milliseconds -->\n"), $this->T_('Bad Behaviour'), BB2_VERSION, $ms));
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

	function GetDefaultSettings( & $params )
	{
		return array(
			'display_stats' => array(
				'label' => $this->plug->T_('Display Stats'),
				'type' => 'checkbox',
				'defaultvalue' => 1,
			),
			'strict' => array(
				'label' => $this->plug->T_('Strict'),
				'type' => 'checkbox',
				'defaultvalue' => 0,
				'note' => $this->plug->T_('Strict checking (blocks more spam but may block some people)')
			),
			'logging' => array(
				'label' => $this->plug->T_('Logging'),
				'type' => 'checkbox',
				'defaultvalue' => 1,
				'note' => $this->plug->T_('HTTP request logging (recommended)'),
			),
			'verbose' => array(
				'label' => $this->plug->T_('Verbose Logging'),
				'type'=>'checkbox',
				'defaultvalue' => 0,
				'note' => $this->plug->T_('Log all requests'),
			),
			'httpbl_key' =>array(
				'label' => $this->plug->T_('http:BL Access Key'),
				'type'  => 'text',
				'maxlength' => 12,
				'defaultvalue' => '',
			),
			'httpbl_threat' => array(
				'label' => $this->plug->T_('Minimum Threat Level (25 is recommended)'),
				'type'  => 'text',
				'defaultvalue' => 25,
			),
			'httpbl_maxage' => array(
				'label' => $this->plug->T_('Maximum Age of Data (30 is recommended)'),
				'type'  => 'text',
				'defaultvalue' => 30,
			),
			'offsite_forms' => array(
				'label' => $this->plug->T_('Offsite forms'),
				'type' => 'checkbox',
				'defaultvalue' => 0,
				'note' => $this->plug->T_('Allow forms submitted from other websites'),
			),
			'eu_cookie' => array(
				'label' => $this->plug->T_('Strict EU cookies'),
				'type' => 'checkbox',
				'defaultvalue' => 0,
				'note' => $this->plug->T_('Disables cookie-based filters'),
			),
			'reverse_proxy' => array(
				'label' => $this->plug->T_('Reverse Proxy'),
				'type' => 'checkbox',
				'defaultvalue' => 0,
				'note' => $this->plug->T_('This site is behind a reverse proxy'),
			),
			'reverse_proxy_header' => array(
				'label' => $this->plug->T_('Reverse proxy header'),
				'type' => 'text',
				'defaultvalue' => 'X-Forwarded-For',
			),
			'reverse_proxy_addresses' => array(
				'label' => $this->plug->T_('Reverse proxy addresses'),
				'type' => 'textarea',
				'defaultvalue' => array(),
				'note' => $this->plug->T_('List of IP addresses of your reverse proxy.  ') . $this->plug->T_('One per line.'),
			),

			/* Whitelist options */
			'whitelist_ips' => array(
				'label' => $this->plug->T_('Whitelist IP addresses'),
				'type' => 'textarea',
				'defaultvalue' => implode("\n", array('64.191.203.0/24', '208.67.217.130', '10.0.0.0/8', '172.16.0.0/12', '192.168.0.0/16')),
				'note' => $this->plug->T_('List of IP addresses that are never filtered.  ') . $this->plug->T_('One per line.'),
			),
			'whitelist_user_agents' => array(
				'label' => $this->plug->T_('Whitelist user agents'),
				'type' => 'textarea',
				'defaultvalue' => '',
				'note' => $this->plug->T_('List of user agents that are never filtered.  ') . $this->plug->T_('One per line.'),
			),
			'whitelist_urls' => array(
				'label' => $this->plug->T_('Whitelist URLs'),
				'type' => 'textarea',
				'defaultvalue' => implode("\n", array('/example.php', '/openid/server')),
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

		$query = "SELECT * FROM " . $this->log_table . " WHERE `key` NOT LIKE '00000000'";
		$blocked_list = bb2_db_query( $query );
		printf($this->T_('<h2>%s has blocked the following access attempts in the last 7 days.</h2>'), $this->T_('Bad Behaviour'));
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
			$url = sprintf("%s://%s:%u%s", $url['scheme'], $url['host'], @$url['port'], $url['path']);
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
		printf($this->T_('<p>A total of %d access attepts blocked.</p>'), $count);
		printf($this->T_('<p>More about <a href="http://www.bad-behavior.ioerror.us/">%s</a></p>'), $this->T_('Bad Behaviour'));
	}

	function SkinEndHtmlBody( & $params )
	{
		global $bb2_result, $bb2_settings;

		if ($bb2_settings['display_stats'])
		{
			$query = 'SELECT COUNT(*) FROM `' . $this->log_table . '` WHERE `key` NOT LIKE \'00000000\'';
			$blocked = bb2_db_query( $query );

			if ($blocked !== FALSE)
			{
				printf($this->T_('<div><a href="http://www.bad-behavior.ioerror.us/"><cite>%1$s</cite></a> has blocked %2$d access attempts in the last 7 days.</div>' . "\n"), $this->T_('Bad Behaviour'), $blocked[0]["COUNT(*)"]);
			}
		}
		if (@!empty($bb2_result)) {
			/* TRANS: The first format char is for the Bad Behaviour name.  The second is for the hypothetical result. */
			printf($this->T_("\n<!-- %1$s result was %2$s! This request would have been blocked. -->\n"), $this->T_('Bad Behaviour'), $bb2_result);
			unset($bb2_result);
		}
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
	global $DB, $old_errors;

	$old_errors = $DB->show_errors;
	$DB->show_errors = FALSE;
	$result = $DB->get_results($query, ARRAY_A);
	$DB->show_errors = $old_errors;
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
	global $bb2_whitelist;
	if (isset($bb2_whitelist))
		return $bb2_whitelist;

	global $bb2_settings;

	$bb2_whitelist['ip'] = explode("\n", $bb2_settings['whitelist_ips']);
	$bb2_whitelist['useragent'] = explode("\n", $bb2_settings['whitelist_user_agents']);
	$bb2_whitelist['url'] = explode("\n", $bb2_settings['whitelist_urls']);

	return $bb2_whitelist;
}

// retrieve settings from database
function bb2_read_settings() {
	global $bb2_settings;
	if (isset($bb2_settings))
		return $bb2_settings;

	global $Plugins;
	$plug = $Plugins->get_by_code( 'b2_bad_behaviour' );
	$bb2_settings['log_table'] = $plug->log_table;

	$bb2_settings['display_stats'] = $plug->Settings->get('display_stats');
	$bb2_settings['strict'] = $plug->Settings->get('strict');
	$bb2_settings['verbose'] = $plug->Settings->get('verbose');
	$bb2_settings['logging'] = $plug->Settings->get('logging');
	$bb2_settings['httpbl_key'] = $plug->Settings->get('httpbl_key');
	$bb2_settings['httpbl_threat'] = $plug->Settings->get('httpbl_threat');
	$bb2_settings['httpbl_maxage'] = $plug->Settings->get('httpbl_maxage');
	$bb2_settings['offsite_forms'] = $plug->Settings->get('offsite_forms');
	$bb2_settings['eu_cookie'] = $plug->Settings->get('eu_cookie');
	$bb2_settings['reverse_proxy'] = $plug->Settings->get('reverse_proxy');
	$bb2_settings['reverse_proxy_header'] = $plug->Settings->get('reverse_proxy_header');
	$bb2_settings['reverse_proxy_addresses'] = explode("\n", $plug->Settings->get('reverse_proxy_addresses'));

	/* Whitelist settings */
	$bb2_settings['whitelist_ips'] = $plug->Settings->get('whitelist_ips');
	$bb2_settings['whitelist_user_agents'] = $plug->Settings->get('whitelist_user_agents');
	$bb2_settings['whitelist_urls'] = $plug->Settings->get('whitelist_urls');

	return $bb2_settings;
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
	global $cookie_path;
	return $cookie_path;
}
?>
