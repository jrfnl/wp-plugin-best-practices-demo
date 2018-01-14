<?php
/**
 * Demo Quotes Plugin.
 *
 * @package     WordPress\Plugins\Demo_Quotes_Plugin
 * @author      Juliette Reinders Folmer <wpplugins_nospam@adviesenzo.nl>
 * @link        https://github.com/jrfnl/wp-plugin-best-practices-demo
 * @version     1.0.1
 *
 * @copyright   2013-2015 Juliette Reinders Folmer
 * @license     http://creativecommons.org/licenses/GPL/2.0/ GNU General Public License, version 3 or higher
 *
 * @wordpress-plugin
 * Plugin Name: Demo Quotes Plugin
 * Plugin URI: https://github.com/jrfnl/wp-plugin-best-practices-demo
 * Description: Demo plugin for WordPress Plugins Best Practices Tutorial
 * Version: 1.1
 * Author: Juliette Reinders Folmer
 * Author URI: http://adviesenzo.nl/
 * Text Domain: demo-quotes-plugin
 * Domain Path: /languages
 * License: GPL v3
 *
 * Copyright (C) 2013, Juliette Reinders Folmer - wp-best-practices@adviesenzo.nl
 *
 * GNU General Public License, Free Software Foundation <http://creativecommons.org/licenses/GPL/3.0/>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

if ( ! class_exists( 'Demo_Quotes_Plugin' ) ) {

	/**
	 * Demo Quotes Plugin.
	 */
	class Demo_Quotes_Plugin {


		/* *** DEFINE CLASS CONSTANTS *** */

		/**
		 * Plugin version number.
		 *
		 * @const string
		 */
		const VERSION = '1.0.1';

		/**
		 * Version in which the front-end styles where last changed.
		 *
		 * @const string
		 */
		const STYLES_VERSION = '1.0';

		/**
		 * Version in which the front-end scripts where last changed.
		 *
		 * @const string
		 */
		const SCRIPTS_VERSION = '1.0';

		/**
		 * Version in which the admin styles where last changed.
		 *
		 * @const string
		 */
		const ADMIN_STYLES_VERSION = '1.0';

		/**
		 * Version in which the admin scripts where last changed.
		 *
		 * @const string
		 */
		const ADMIN_SCRIPTS_VERSION = '1.0';

		/**
		 * Name of our shortcode.
		 *
		 * @const string
		 */
		const SHORTCODE = 'demo_quote';

		/**
		 * Name of our update check transient.
		 *
		 * @const string
		 */
		const UPDATE_TRANSIENT = 'demo_quote_update';


		/* *** DEFINE STATIC CLASS PROPERTIES *** */

		/**
		 * These static properties will be initialized - *before* class instantiation -
		 * by the static init() function.
		 */

		/**
		 * Plugin Basename = 'dir/file.php'.
		 *
		 * @var string
		 */
		public static $basename;

		/**
		 * Plugin name    = dirname of the plugin.
		 *
		 * @var string
		 */
		public static $name;

		/**
		 * Suffix to use if scripts/styles are in debug mode.
		 *
		 * @var string
		 */
		public static $suffix;


		/* *** DEFINE CLASS PROPERTIES *** */

		/* *** Properties Holding Various Parts of the Class' State. *** */

		/**
		 * Settings page class.
		 *
		 * @var object
		 */
		public $settings_page;


		/* *** PLUGIN INITIALIZATION METHODS *** */


		/**
		 * Object constructor for plugin.
		 *
		 * @uses Demo_Quotes_Plugin::VERSION
		 * @uses Demo_Quotes_Plugin::SHORTCODE
		 *
		 * @return Demo_Quotes_Plugin
		 */
		public function __construct() {

			spl_autoload_register( array( $this, 'auto_load' ) );

			/* Check if we have any upgrade actions to do. */
			if ( ! isset( Demo_Quotes_Plugin_Option::$current['version'] ) || version_compare( self::VERSION, Demo_Quotes_Plugin_Option::$current['version'], '>' ) ) {
				add_action( 'init', array( $this, 'upgrade' ), 1 );
			}
			// Make sure that the upgrade actions are run on (re-)activation as well.
			// @todo check if this will really work.
			add_action( 'demo_quotes_plugin_activate', array( $this, 'upgrade' ) );

			/* Register the plugin initialization actions. */
			add_action( 'init', array( $this, 'init' ), 8 );
			add_action( 'admin_menu', array( $this, 'setup_options_page' ) );
			add_action( 'admin_init', array( $this, 'admin_init' ) );

			/* Register the widget. */
			add_action( 'widgets_init', array( $this, 'widgets_init' ) );

			/* Register the shortcode. */
			add_shortcode( self::SHORTCODE, array( $this, 'do_shortcode' ) );
		}


		/**
		 * Set the static path and directory variables for this class
		 * Is called from the global space *before* instantiating the class to make
		 * sure the correct values are available to the object
		 *
		 * @return void
		 */
		public static function init_statics() {

			self::$basename = plugin_basename( __FILE__ );
			self::$name     = trim( dirname( self::$basename ) );
			self::$suffix   = ( ( defined( 'SCRIPT_DEBUG' ) && true === SCRIPT_DEBUG ) ? '' : '.min' );
		}


		/**
		 * Auto load our class files.
		 *
		 * @param string $class Class name.
		 *
		 * @return void
		 */
		public function auto_load( $class ) {
			static $classes = null;

			if ( null === $classes ) {
				$classes = array(
					'demo_quotes_plugin_cpt'            => 'class-demo-quotes-plugin-cpt.php',
					'demo_quotes_plugin_option'         => 'class-demo-quotes-plugin-option.php',
					'demo_quotes_plugin_settings_page'  => 'class-demo-quotes-plugin-settings-page.php',
					'demo_quotes_plugin_widget'         => 'class-demo-quotes-plugin-widget.php',
					'demo_quotes_plugin_people_widget'  => 'class-demo-quotes-plugin-people-widget.php',
				);
			}

			$cn = strtolower( $class );

			if ( isset( $classes[ $cn ] ) ) {
				include_once plugin_dir_path( __FILE__ ) . $classes[ $cn ];
			}
		}


		/* *** ADMINISTRATIVE METHODS *** */


		/**
		 * Add the actions for the front-end functionality.
		 * Add actions which are needed for both front-end and back-end functionality.
		 *
		 * @return void
		 */
		public function init() {

			/* Allow filtering of our plugin name. */
			self::filter_statics();

			/*
			 *  Load plugin text strings.
			 * @see http://geertdedeckere.be/article/loading-wordpress-language-files-the-right-way
			 *
			 * If you'll be hosting your plugin at wordpress.org and using the translations as
			 * provided via GlotPress (translate.wordpress.org), you can simplify this to the
			 * below and you can remove the local `load_textdomain()` function as well:
			 *
			 * `load_plugin_textdomain( 'demo-quotes-plugin' );`
			 *
			 * The net effect of this will be that WP will ignore translations included with the
			 * plugin and will look in the `wp-content/languages/plugins/` folder for translations
			 * instead.
			 */
			$this->load_textdomain( 'demo-quotes-plugin' );

			/* Register the Quotes Custom Post Type and add any related action and filters. */
			Demo_Quotes_Plugin_Cpt::init();

			/* Register our ajax actions for the widget. */
			add_action( 'wp_ajax_demo_quotes_widget_next', array( $this, 'demo_quotes_widget_next' ) );
			add_action( 'wp_ajax_nopriv_demo_quotes_widget_next', array( $this, 'demo_quotes_widget_next' ) );

			/* Add js and css files. */
			add_action( 'wp_enqueue_scripts', array( $this, 'wp_enqueue_scripts' ) );
		}


		/**
		 * Allow filtering of the plugin name.
		 * Mainly useful for non-standard directory setups.
		 *
		 * @return void
		 */
		public static function filter_statics() {
			self::$name = apply_filters( 'demo_quotes_plugin_name', self::$name );
		}


		/**
		 * Load the plugin text strings.
		 *
		 * Compatible with use of the plugin in the must-use plugins directory.
		 *
		 * @param string $domain Text domain to load.
		 */
		protected function load_textdomain( $domain ) {
			$lang_path = dirname( plugin_basename( __FILE__ ) ) . '/languages';
			if ( false === strpos( __FILE__, basename( WPMU_PLUGIN_DIR ) ) ) {
				load_plugin_textdomain( $domain, false, $lang_path );
			} else {
				load_muplugin_textdomain( $domain, $lang_path );
			}
		}


		/**
		 * Add back-end functionality.
		 *
		 * @return void
		 */
		public function admin_init() {
			/* Add actions and filters for our custom post type. */
			Demo_Quotes_Plugin_Cpt::admin_init();

			/* Add js and css files. */
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		}


		/**
		 * Register the options page for all users that have the required capability.
		 *
		 * @return void
		 */
		public function setup_options_page() {

			/* Don't do anything if user does not have the required capability. */
			if ( false === is_admin() || false === current_user_can( Demo_Quotes_Plugin_Option::REQUIRED_CAP ) ) {
				return;
			}
			$this->settings_page = new Demo_Quotes_Plugin_Settings_Page();
		}


		/**
		 * Register the Widgets.
		 *
		 * @see register_widget()
		 *
		 * @return void
		 */
		public function widgets_init() {
			register_widget( 'Demo_Quotes_Plugin_Widget' );
			register_widget( 'Demo_Quotes_Plugin_People_Widget' );
		}


		/**
		 * Register, but don't yet enqueue necessary javascript and css files for the front-end.
		 *
		 * @uses Demo_Quotes_Plugin::STYLES_VERSION
		 * @uses Demo_Quotes_Plugin::SCRIPTS_VERSION
		 *
		 * @return void
		 */
		public function wp_enqueue_scripts() {
			wp_register_style(
				self::$name . '-css', // ID.
				plugins_url( 'css/style' . self::$suffix . '.css', __FILE__ ), // URL.
				array(), // Not used.
				self::STYLES_VERSION, // Version.
				'all' // Media.
			);

			wp_register_script(
				self::$name . '-js', // ID.
				plugins_url( 'js/interaction' . self::$suffix . '.js', __FILE__ ), // URL.
				array( 'jquery', 'wp-ajax-response' ), // Dependants.
				self::SCRIPTS_VERSION, // Version.
				true // Load in footer ?
			);
		}


		/**
		 * Conditionally add necessary javascript and css files for the back-end on the appropriate screens.
		 *
		 * @uses Demo_Quotes_Plugin::$name
		 * @uses Demo_Quotes_Plugin::$suffix
		 * @uses Demo_Quotes_Plugin::$settings_page
		 * @uses Demo_Quotes_Plugin_Cpt::$post_type_name
		 * @uses Demo_Quotes_Plugin::ADMIN_STYLES_VERSION
		 * @uses Demo_Quotes_Plugin::ADMIN_SCRIPTS_VERSION
		 *
		 * @return void
		 */
		public function admin_enqueue_scripts() {

			$screen = get_current_screen();

			if ( property_exists( $screen, 'post_type' ) && Demo_Quotes_Plugin_Cpt::$post_type_name === $screen->post_type ) {
				wp_enqueue_style(
					self::$name . '-admin-css', // ID.
					plugins_url( 'css/admin-style' . self::$suffix . '.css', __FILE__ ), // URL.
					array(), // Not used.
					self::ADMIN_STYLES_VERSION, // Version.
					'all' // Media.
				);
			}

			/* Admin js for settings page only. */
			if ( property_exists( $screen, 'base' ) && ( isset( $this->settings_page ) && $screen->base === $this->settings_page->hook ) ) {
				wp_enqueue_script(
					self::$name . '-admin-js', // ID.
					plugins_url( 'js/admin-interaction' . self::$suffix . '.js', __FILE__ ), // URL.
					array( 'jquery', 'jquery-ui-accordion' ), // Dependants.
					self::ADMIN_SCRIPTS_VERSION, // Version.
					true // Load in footer ?
				);
			}
		}


		/**
		 * Function containing the helptext strings.
		 *
		 * Of course in a real plugin, we'd have proper helpful texts here.
		 *
		 * @static
		 *
		 * @param object $screen Screen object for the screen the user is on.
		 * @param array  $tab    Help tab being requested.
		 */
		public static function get_helptext( $screen, $tab ) {

			switch ( $tab['id'] ) {
				case self::$name . '-main':
					echo '
								<p>', esc_html__( 'Here comes a helpful help text ;-)', 'demo-quotes-plugin' ), '</p>
								<p>', esc_html__( 'And some more help.', 'demo-quotes-plugin' ), '</p>';
					break;

				case self::$name . '-add':
					echo '
								<p>', esc_html__( 'Some specific information about editing a quote', 'demo-quotes-plugin' ), '</p>
								<p>', esc_html__( 'And some more help.', 'demo-quotes-plugin' ), '</p>';
					break;

				case self::$name . '-advanced':
					echo '
								<p>', esc_html__( 'Some information about advanced features if we create any.', 'demo-quotes-plugin' ), '</p>';
					break;

				case self::$name . '-extras':
					echo '
								<p>', esc_html__( 'And here we may say something on extra\'s we add to the post type', 'demo-quotes-plugin' ), '</p>';
					break;

				case self::$name . '-settings':
					echo '
								<p>', esc_html__( 'Some information on the effect of the settings', 'demo-quotes-plugin' ), '</p>';
					break;

				default:
					// Nothing here.
					break;
			}
		}


		/**
		 * Generate the links for the help sidebar.
		 * Of course in a real plugin, we'd have proper links here.
		 *
		 * @static
		 *
		 * @return string
		 */
		public static function get_help_sidebar() {
			return '
				<p><strong>' . esc_html__( 'For more information:', 'default' ) . '</strong></p>
				<p>
					<a href="https://wordpress.org/plugins/" target="_blank">' . esc_html__( 'Official plugin page (if there would be one)', 'demo-quotes-plugin' ) . '</a> |
					<a href="#" target="_blank">' . esc_html__( 'FAQ', 'demo-quotes-plugin' ) . '</a> |
					<a href="#" target="_blank">' . esc_html__( 'Changelog', 'demo-quotes-plugin' ) . '</a> |
					<a href="https://github.com/jrfnl/wp-plugin-best-practices-demo/issues" target="_blank">' . esc_html__( 'Report issues', 'demo-quotes-plugin' ) . '</a>
				</p>
				<p><a href="https://github.com/jrfnl/wp-plugin-best-practices-demo" target="_blank">' . esc_html__( 'Github repository', 'demo-quotes-plugin' ) . '</a></p>
				<p>' .
				wp_kses_post(
					sprintf(
						/* translators: 1: link tag; 2: link closing tag. */
						__( 'Created by %1$sAdvies en zo%2$s', 'demo-quotes-plugin' ),
						'<a href="http://adviesenzo.nl/" target="_blank">',
						'</a>'
					)
				) .
				'</p>
			';
		}


		/* *** PLUGIN ACTIVATION, UPGRADING AND DEACTIVATION *** */


		/**
		 * Plugin Activation routine.
		 *
		 * @return void
		 */
		public static function activate() {
			/* Security check. */
			if ( ! current_user_can( 'activate_plugins' ) ) {
				return;
			}

			/* Register the Quotes Custom Post Type so WP knows how to adjust the rewrite rules. */
			Demo_Quotes_Plugin_Cpt::register_post_type();
			Demo_Quotes_Plugin_Cpt::register_taxonomy();

			/* Make sure our post type and taxonomy slugs will be recognized. */
			flush_rewrite_rules();

			/* Execute any extra actions registered. */
			do_action( 'demo_quotes_plugin_activate' );
		}


		/**
		 * Plugin deactivation routine.
		 *
		 * @return void
		 */
		public static function deactivate() {
			/* Security check. */
			if ( ! current_user_can( 'activate_plugins' ) ) {
				return;
			}
			$plugin = ( isset( $_REQUEST['plugin'] ) ? sanitize_text_field( $_REQUEST['plugin'] ) : '' );
			check_admin_referer( 'deactivate-plugin_' . $plugin );

			/* Make sure our post type and taxonomy slugs will be removed. */
			flush_rewrite_rules();

			/* Execute any extra actions registered. */
			do_action( 'demo_quotes_plugin_deactivate' );
		}


		/**
		 * Function used when activating and/or upgrading the plugin.
		 *
		 * Upgrades for any version of this plugin lower than x.x.
		 * N.B.: Version nr has to be hard coded to be future-proof, i.e. facilitate
		 * upgrade routines for various versions.
		 *
		 * - Initial activate: Save version number to option.
		 * - v0.2 ensure post format is always set to 'quote'.
		 * - v0.3 auto-set the post title and slug for our post type posts.
		 *
		 * @todo - figure out if any special actions need to be run if multisite
		 * Probably not as this is run on init, so as soon as a page of another site in a multisite
		 * install is requested, the upgrade will run.
		 *
		 * @uses Demo_Quotes_Plugin::VERSION
		 *
		 * @return void
		 */
		public function upgrade() {
			/**
			 * Check to make sure that the upgrade is not already being run in a parallel process.
			 * This is especially important when running intensive "only-once" database queries
			 * which take longer then a few microseconds in an upgrade process.
			 * Both the standard WP AJAX call might kick in while the upgrade is being run just as
			 * another user might be visiting the site and quite often the changes can start conflicting
			 * if run twice in parallel.
			 * Using this transient will prevent all that.
			 */
			if ( get_transient( self::UPDATE_TRANSIENT ) !== false ) {
				return;
			}

			set_transient( self::UPDATE_TRANSIENT, true, HOUR_IN_SECONDS );

			/**
			 * Now we don't want the user to surf away from a page which is running the update and break
			 * the update in the process, so let's make sure that doesn't happen.
			 */
			$ignore = ignore_user_abort( true );

			/* Get our currently saved option to find out from which version we'll need to upgrade. */
			$options = Demo_Quotes_Plugin_Option::$current;

			/**
			 * Cpt post format upgrade for version 0.2.
			 *
			 * Ensure all posts of our custom post type have the 'quote' post format.
			 */
			if ( ! isset( $options['version'] ) || version_compare( $options['version'], '0.2', '<' ) ) {
				/* Get all posts of our custom post type which currently do not have the 'quote' post format. */
				$args  = array(
					'post_type' => Demo_Quotes_Plugin_Cpt::$post_type_name,
					'tax_query' => array(
						array(
							'taxonomy' => 'post_format',
							'field'    => 'slug',
							'terms'    => array( 'post-format-quote' ),
							'operator' => 'NOT IN',
						),
					),
					'nopaging'  => true,
				);
				$query = new WP_Query( $args );

				/* Set the post format. */
				while ( $query->have_posts() ) {
					$query->next_post();
					set_post_format( $query->post->ID, 'quote' );
				}
				wp_reset_postdata(); // Always restore original Post Data.
				unset( $args, $query );
			}

			/**
			 * Cpt slug and title upgrade for version 0.3.
			 *
			 * Ensure all posts of our custom post type posts have a title and a textual slug.
			 */
			if ( ! isset( $options['version'] ) || version_compare( $options['version'], '0.3', '<' ) ) {
				/*
				 * Get all posts of our custom post type except for those with post status auto-draft,
				 * inherit (=revision) or trash.
				 * Alternative way of getting the results for demonstration purposes.
				 */
				$sql    = $GLOBALS['wpdb']->prepare(
					'SELECT *
					FROM `' . $GLOBALS['wpdb']->posts . '`
					WHERE `post_type` = %s
					AND `post_status` NOT IN ( "auto-draft", "inherit", "trash" )
					',
					Demo_Quotes_Plugin_Cpt::$post_type_name
				);
				$result = $GLOBALS['wpdb']->get_results( $sql );

				/* Update the post title and post slug. */
				if ( is_array( $result ) && array() !== $result ) {
					foreach ( $result as $row ) {
						Demo_Quotes_Plugin_Cpt::update_post_title_and_name( $row->ID, $row );
					}
					unset( $row );
				}
				unset( $sql, $result );
			}

			/**
			 * Custom taxonomies upgrade for version 0.5.
			 *
			 * Ensure the rewrite rules are refreshed.
			 */
			if ( ! isset( $options['version'] ) || version_compare( $options['version'], '0.5', '<' ) ) {
				/* Register the Quotes Custom Post Type so WP knows how to adjust the rewrite rules. */
				Demo_Quotes_Plugin_Cpt::register_post_type();
				Demo_Quotes_Plugin_Cpt::register_taxonomy();
				flush_rewrite_rules();
			}

			/* Always update the version number. */
			$options['version'] = self::VERSION;

			/*
			 * Update the settings and refresh our property.
			 *
			 * We'll be saving our options during the upgrade routine *before* the setting
			 * is registered (and therefore the validation is registered), so make sure that the
			 * options are validated anyway.
			 */
			update_option( Demo_Quotes_Plugin_Option::NAME, $options );

			delete_transient( self::UPDATE_TRANSIENT );
			ignore_user_abort( $ignore );
		}


		/* *** HELPER METHODS *** */

		/* *** FRONT-END: DISPLAY METHODS *** */


		/**
		 * Retrieve the next quote to display and send it back to the AJAX request.
		 *
		 * @return void
		 */
		public function demo_quotes_widget_next() {
			/* Security check. */
			check_ajax_referer(
				'demo-quotes-widget-next-nonce', // Name of our nonce.
				'dqpwNonce', // $REQUEST variable to look at.
				true // Die if check fails.
			);

			$not = null;
			if ( isset( $_POST['currentQuote'] ) ) {
				$not = intval( $_POST['currentQuote'] );
			}

			$quote = self::get_random_quote( $not, false, 'array' );

			$response = new WP_Ajax_Response();

			$response->add(
				array(
					'what'          => 'quote',
					'action'        => 'next_quote',
					'data'          => '',
					'supplemental'  => array(
						'quoteid'       => $quote['id'],
						'quote'         => '<div class="dqpw-quote-wrapper">' . $quote['html'] . '</div>',
					),
				)
			);
			$response->send();

			exit;
		}


		/**
		 * Return random quote via shortcode.
		 *
		 * @uses Demo_Quotes_Plugin::get_random_quote()
		 *
		 * @param array $args Shortcode arguments received.
		 *
		 * @return  mixed
		 */
		public function do_shortcode( $args ) {
			/*
			// Filter received arguments and combine them with our defaults.
			$args = shortcode_atts(
				$this->shortcode_defaults, // the defaults
				$args, // the received shortcode arguments
				self::SHORTCODE // Shortcode name to be used by shortcode_args_{$shortcode} filter (WP 3.6+)
			);
			*/
			return self::get_random_quote();
		}


		/**
		 * Get a quote at random.
		 *
		 * @param int|null $not         (optional) Post id to exclude, defaults to none.
		 * @param bool     $echo        (optional) Whether to echo the result, defaults to false.
		 * @param string   $return_type (optional) What to return:
		 *                                          'string' = html string
		 *                                          'array' = array consisting of:
		 *                                               'html' => html string,
		 *                                               'id'   => post id
		 *                                               'post' => post object
		 *                              Defaults to 'string'.
		 *
		 * @return mixed False if no quotes found, null if echo = true, string/array if echo = false
		 */
		public static function get_random_quote( $not = null, $echo = false, $return_type = 'string' ) {

			// WP_Query arguments.
			$args = array(
				'post_type'              => Demo_Quotes_Plugin_Cpt::$post_type_name,
				'post_status'            => 'publish',
				'posts_per_page'         => '1',
				'orderby'                => 'rand',
			);
			if ( isset( $not ) && false !== filter_var( $not, FILTER_VALIDATE_INT ) ) {
				$args['post__not_in'] = (array) $not;
			}

			/*
			// @todo
			if ( ID ) {
				// add id to query
			} elseif ( person ||tag || most recent) {
				if ( person ) {
					// add to query
				}
				if ( tag ) {
					// add to query
				}
				if ( most recent ) {
					// add most recent to query
				}
			} else {
				// add random to query
			}
			*/

			// The Query.
			$query = new WP_Query( $args );

			$html = '';
			if ( 1 === $query->post_count ) {
				$html .= '
			<div class="dqp-quote dqp-quote-' . esc_attr( $query->post->ID ) . '">
				<p>' . wp_kses_post( $query->post->post_content ) . '</p>
			</div>';
				$html .= self::get_quoted_by( $query->post->ID, false );

				if ( true === $echo ) {
					echo $html; // WPCS: XSS ok.
					wp_reset_postdata();

				} else {
					$return = null;
					if ( 'array' === $return_type ) {
						$return = array(
							'html'      => $html,
							'id'        => $query->post->ID,
							'object'    => $query->post,
						);
					} else {
						$return = $html;
					}
					wp_reset_postdata();
					return $return;
				}
			}

			return false;
		}


		/**
		 * Generate link to person quoted.
		 *
		 * @param int  $post_id Current post ID.
		 * @param bool $echo    Whether to echo out the result. Defaults to false.
		 *
		 * @return string
		 */
		public static function get_quoted_by( $post_id, $echo = false ) {

			$html  = '';
			$terms = wp_get_post_terms( $post_id, Demo_Quotes_Plugin_Cpt::$taxonomy_name );

			if ( is_array( $terms ) && array() !== $terms ) {
				$html .= '
				<div class="dqp-quote-by"><p>';

				foreach ( $terms as $term ) {
					/* translators: %s: Quotee (author of the quote). */
					$title_attr = sprintf( __( 'View more quotes by %s', 'demo-quotes-plugin' ), $term->name );
					$html      .= '
					<a href="' . esc_url( get_term_link( $term ) ) . '" title="' . esc_attr( $title_attr ) . '">' . esc_html( $term->name ) . '</a>';
				}
				$html .= '
				</p></div>';
			}

			if ( true === $echo ) {
				echo $html; // WPCS: XSS ok.
				return '';
			} else {
				return $html;
			}
		}


	} /* End of class. */


	/*
	 * Instantiate our class.
	 *
	 * wp_installing() function was introduced in WP 4.4.
	 */
	if ( ( function_exists( 'wp_installing' ) && wp_installing() === false ) || ( ! function_exists( 'wp_installing' ) && ( ! defined( 'WP_INSTALLING' ) || WP_INSTALLING === false ) ) ) {
		add_action( 'plugins_loaded', 'demo_quotes_plugin_init' );
	}

	if ( ! function_exists( 'demo_quotes_plugin_init' ) ) {
		/**
		 * Initialize the class.
		 *
		 * @return void
		 */
		function demo_quotes_plugin_init() {
			/* Initialize the static variables. */
			Demo_Quotes_Plugin::init_statics();

			$GLOBALS['demo_quotes_plugin'] = new Demo_Quotes_Plugin();
		}
	}


	if ( ! function_exists( 'dqp_get_demo_quote' ) ) {
		/**
		 * Template tag to display a quote.
		 *
		 * @param array $args Arguments to retrieve the quote.
		 * @param bool  $echo Whether or not to echo out the result.
		 *
		 * @return string|bool False if no quote found, else a properly escaped html string.
		 */
		function dqp_get_demo_quote( $args, $echo = false ) {
			return Demo_Quotes_Plugin::get_random_quote( $args, $echo );
		}
	}

	/* Set up the (de-)activation actions. */
	register_activation_hook( __FILE__, array( 'Demo_Quotes_Plugin', 'activate' ) );
	register_deactivation_hook( __FILE__, array( 'Demo_Quotes_Plugin', 'deactivate' ) );

} /* End of class-exists wrapper. */
