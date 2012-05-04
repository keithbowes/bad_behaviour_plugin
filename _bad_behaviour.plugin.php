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
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

$bb2_mtime = explode(" ", microtime());
$bb2_timer_start = $bb2_mtime[1] + $bb2_mtime[0];

define('BB2_CWD', dirname(__FILE__));
// Calls inward to Bad Behavor itself.
require_once(BB2_CWD . "/bad-behavior/core.inc.php");

/**
 * Bad Behaviour Plugin
 *
 * This plugin implements a version of bad behaviour
 *
 * @package plugins
 */
class bad_behaviour_plugin extends Plugin
{
	var $name = 'Bad Behaviour Plugin for b2evolution';
	/**
	 * Code, if this is a renderer or pingback plugin.
	 */
	var $code = 'b2_bad_behaviour';
	var $priority = 50;
	var $version = '0.2';
	var $author = 'http://waltercruz.com/';
	var $help_url = '';
	var $group = 'antispam';

	var $apply_rendering = 'opt-in';


	/**
	 * Init
	 *
	 * This gets called after a plugin has been registered/instantiated.
	 */
	function PluginInit( & $params )
	{
		$this->short_desc = $this->T_('The Web\'s premier link spam killer.');
		$this->GetDbLayout();
	}


	function GetDbLayout()
	{
		$file = BB2_CWD . "/bad-behavior-mysql.php";
		if (is_file($file)) {
			require_once $file;
		}
		else {
			return FALSE;
		}

		$tablename = $this->get_sql_table('bad_behavior');
		$sql = bb2_table_structure($tablename);
		$sql = str_replace('0000-00-00 00:00:00','2008-12-08 14:27:46',$sql);
		bb2_db_query($sql);
		return array($sql);
	}

	function SkinBeginHtmlHead()
	{
		global $bb2_timer_total;
		global $bb2_javascript;
		add_headline("\n<!-- Bad Behavior " . BB2_VERSION . " run time: " . number_format(1000 * $bb2_timer_total, 3) . " ms -->\n");
		add_headline($bb2_javascript);
	}

	function BeforeBlogDisplay ( $params )
	{
		bb2_start(bb2_read_settings());

	}
	/**
	 * Define settings that the plugin uses/provides.
	 */
	function GetDefaultSettings()
	{
		return array(
			'strict' => array(
				'label' => $this->T_('Strict'),
				'type' => 'checkbox',
				'defaultvalue' => 0,
				'note' => $this->T_('Strict checking (blocks more spam but may block some people)')
			),
			'logging' => array(
				'label' => $this->T_('Logging'),
				'type' => 'checkbox',
				'note' => $this->T_('HTTP request logging (recommended)'),
				'defaultvalue' => 1,
			),
			'display_stats' => array(
				'label' => $this->T_('Display Stats'),
				'type' => 'checkbox',
				'defaultvalue' => 1,
			),
			'verbose' => array(
				'label' => $this->T_('Verbose Logging'),
				'type'=>'checkbox',
				'defaultvalue'=> 0,
				'note' => $this->T_('Verbose logging'),
			),
			'httpbl_key' =>array(
				'label' => $this->T_('http:BL Access Key'),
				'type'  => 'text',
				'maxlength' => 12,
			),
			'httpbl_threat' => array(
				'label' => $this->T_('Minimum Threat Level (25 is recommended)'),
				'defaultvalue' => '25',
			),
			'httpbl_maxage' => array(
				'label' => $this->T_('Maximum Age of Data (30 is recommended)'),
				'defaultvalue' => '30',
			),
			'eu_cookie' => array(
				'label' => $this->T_('Strict EU cookies'),
				'type' => 'checkbox',
				'defaultvalue' => 0,
				'note' => $this->T_('Disables cookie-based filters'),
			),
			);
		bb2_read_settings();
	}


	function SkinEndHtmlBody( $params )
	{
		global $DB;
		$settings = bb2_read_settings();
		$dbname = $settings['log_table'];

		if ($settings['display_stats'])
		{
			$query = "SELECT COUNT(*) FROM $dbname WHERE `key` NOT LIKE '00000000'";
			$res = $DB->get_var( $query );

			if ($res !== FALSE)
			{
				echo sprintf('<p><a href="http://www.bad-behavior.ioerror.us/">%1$s</a> %2$s <strong>%3$s</strong> %4$s</p>', $this->T_('Bad Behavior'), $this->T_('has blocked'), $res, $this->T_('access attempts in the last 7 days.'));
			}
		}
	}


	/**
	 * Define user settings that the plugin uses/provides.
	 */
	function GetDefaultUserSettings()
	{
		return array(

			);
	}


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
	global $DB;

	//	$wpdb->hide_errors();
	$result = $DB->get_results($query, ARRAY_A);
	//	$wpdb->show_errors();
	if (mysql_error()) {
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

// retrieve settings from database
function bb2_read_settings() {
	global $Plugins;
	$plug = $Plugins->get_by_code( 'b2_bad_behaviour' );
	$ret= array();
	$ret['log_table'] = $plug->get_sql_table('bad_behavior');
	$ret['display_stats'] = $plug->Settings->get('display_stats');
	$ret['strict'] = $plug->Settings->get('strict');
	$ret['verbose'] = $plug->Settings->get('verbose');
	$ret['logging'] = $plug->Settings->get('logging');
	$ret['httpbl_key'] = $plug->Settings->get('httpbl_key');
	$ret['httpbl_threat'] = $plug->Settings->get('httpbl_threat');
	$ret['httpbl_maxage'] = $plug->Settings->get('httpbl_maxage');
	$ret['offsite_forms'] = $plug->get_sql_table('offsite_forms');
	$ret['eu_cookie'] = $plug->get_sql_table('eu_cookie');
	$ret['reverse_proxy'] = false;
	$ret['reverse_proxy_header'] = 'X-Forwarded-For';
	$ret['reverse_proxy_addresses'] = array();

	$settings = @parse_ini_file(dirname(__FILE__) . "/settings.ini");
	if (!$settings) $settings = array();

	$ret = @array_merge($ret, $settings);
	return $ret;
}

// See bad_behaviour_plugin::GetDbLayout()
function bb2_install() {
}

// See bad_behaviour_plugin::SkinBeginHtmlHead()
function bb2_insert_head() {
}

// See bad_behaviour_plugin::SkinEndHtmlBody()
function bb2_insert_stats($force = false) {
}

// See bad_baviour_plugin::GetDefaultSettings()
function bb2_write_settings($settings) {
	return false;
}

// Return the top-level relative path of wherever we are (for cookies)
function bb2_relative_path() {
	global $Blog;
	$url = parse_url($Blog->gen_baseurl());
	return $url['path'];
}

function bb2_db_date() {
	return gmdate('Y-m-d H:i:s');
}

$bb2_mtime = explode(" ", microtime());
$bb2_timer_stop = $bb2_mtime[1] + $bb2_mtime[0];
$bb2_timer_total = $bb2_timer_stop - $bb2_timer_start;
?>
