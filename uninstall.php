<?php
/**
 * Code used when the plugin is removed (not just deactivated but actively deleted by the WordPress Admin).
 *
 * @package WordPress\Plugins\DemoQuotesPlugin
 * @subpackage Uninstall
 * @version 1.0
 *
 * @author Juliette Reinders Folmer
 * @license http://creativecommons.org/licenses/GPL/3.0/ GNU General Public License, version 3
 */

if ( !current_user_can( 'activate_plugins' ) || ( !defined( 'ABSPATH' ) || !defined( 'WP_UNINSTALL_PLUGIN' ) ) )
	exit();


