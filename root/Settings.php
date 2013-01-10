<?php
/**
 * Wedge
 *
 * Contains the master settings for Wedge, including database credentials.
 *
 * @package wedge
 * @copyright Wedgeward, wedge.org
 * @license http://wedge.org/license/
 */

########## Maintenance ##########
# Note: If $maintenance is set to 2, the forum will be unusable!  Change it to 0 to fix it.
$maintenance = 0;					# Set to 1 to enable Maintenance Mode, 2 to make the forum untouchable. (you'll have to make it 0 again manually!)
$mtitle = 'Maintenance Mode';		# Title for the Maintenance Mode message.
$mmessage = 'We are currently working on website maintenance. Please bear with us, we\'ll restore access as soon as we can!';	# Description of why the forum is in maintenance mode.

########## Forum Info ##########
$mbname = 'My Community';						# The name of your forum.
$language = 'english';							# The default language file set for the forum.
$boardurl = 'http://127.0.0.1/wedge';			# URL to your forum's folder. (without the trailing /!)
$webmaster_email = 'noreply@myserver.com';		# Email address to send emails from. (like noreply@yourdomain.com.)
$cookiename = 'WedgeCookie01';					# Name of the cookie to set for authentication.

########## Database Info ##########
$db_server = 'localhost';
$db_name = 'wedge';
$db_user = 'root';
$db_passwd = '';
$ssi_db_user = '';
$ssi_db_passwd = '';
$db_prefix = 'wedge_';
$db_persist = 0;
$db_error_send = 1;
$db_show_debug = false;

########## Directories/Files ##########
# Note: These directories do not have to be changed unless you move things.
$boarddir = dirname(__FILE__);					# The absolute path to the forum's folder. (not just '.'!)
$sourcedir = dirname(__FILE__) . '/Sources';	# Path to the Sources directory.
$cachedir = dirname(__FILE__) . '/cache';		# Path to the cache directory.
$cssdir = dirname(__FILE__) . '/css';			# Path to the CSS cache directory.
$jsdir = dirname(__FILE__) . '/js';				# Path to the JS cache directory.
$pluginsdir = dirname(__FILE__) . '/Plugins';	# Path to the plugins directory.
$pluginsurl = $boardurl . '/Plugins';			# URL to the Plugins area root.

########## Error-Catching ##########
# Note: You shouldn't touch these settings.
$db_last_error = 0;

if (file_exists(dirname(__FILE__) . '/install.php'))
{
	header('Location: http' . (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == 'on' ? 's' : '') . '://' . (empty($_SERVER['HTTP_HOST']) ? $_SERVER['SERVER_NAME'] . (empty($_SERVER['SERVER_PORT']) || $_SERVER['SERVER_PORT'] == '80' ? '' : ':' . $_SERVER['SERVER_PORT']) : $_SERVER['HTTP_HOST']) . (strtr(dirname($_SERVER['PHP_SELF']), '\\', '/') == '/' ? '' : strtr(dirname($_SERVER['PHP_SELF']), '\\', '/')) . '/install.php');
	exit;
}

# Make sure the paths are correct... at least try to fix them.
if (!file_exists($boarddir) && file_exists(dirname(__FILE__) . '/agreement.txt'))
	$boarddir = dirname(__FILE__);
if (!file_exists($sourcedir) && file_exists($boarddir . '/Sources'))
	$sourcedir = $boarddir . '/Sources';
if (!file_exists($pluginsdir) && file_exists($boarddir . '/Plugins'))
	$pluginsdir = $boarddir . '/Plugins';

# Make absolutely sure the cache directories are defined.
foreach (array('cache', 'css', 'js') as $var)
{
	$dir = $var . 'dir';
	if ((empty($$dir) || ($$dir !== $boarddir . '/' . $var && !file_exists($$dir))) && file_exists($boarddir . '/' . $var))
		$$dir = $boarddir . '/' . $var;
	if (!file_exists($$dir))
		exit('Missing cache folder: $' . $dir . ' (' . $$dir . ')');
}
