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


$options = get_option( 'demo_quotes_plugin_options' );
if ( isset( $options['uninstall']['delete_taxonomy'] ) && $options['uninstall']['delete_taxonomy'] === 'DELETE' ) {
	// Get all terms with our taxonomy & all relationships to these
	// Delete
}
if ( isset( $options['uninstall']['delete_posts'] ) && $options['uninstall']['delete_posts'] === 'DELETE' ) {
	// Get all posts with our post_type & all posts where a post with our post_type is the post_parent (revisions)
	// Delete
}

delete_option( 'demo_quotes_plugin_options' );