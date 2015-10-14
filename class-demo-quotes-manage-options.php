<?php
/**
 * Option Management.
 *
 * @package WordPress\Plugins\Demo_Quotes_Plugin
 * @subpackage Option
 */

// Avoid direct calls to this file.
if ( ! function_exists( 'add_action' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

if ( class_exists( 'Demo_Quotes_Plugin' ) && ! class_exists( 'Demo_Quotes_Plugin_Option' ) ) {

	/**
	 * Demo Quotes Option Management.
	 */
	class Demo_Quotes_Plugin_Option {


		/* *** DEFINE CLASS CONSTANTS *** */

		/**
		 * Name of options variable containing the plugin proprietary settings.
		 *
		 * @const string
		 */
		const NAME = 'demo_quotes_plugin_options';

		/**
		 * Minimum required capability to access the settings page and change the plugin options.
		 *
		 * @const string
		 */
		const REQUIRED_CAP = 'manage_options';

		/**
		 * Keyword used for the uninstall settings.
		 *
		 * @const string
		 */
		const DELETE_KEYWORD = 'DELETE';


		/* *** DEFINE CLASS PROPERTIES *** */

		/* *** Static Properties *** */

		/**
		 * Unique group identifier for all our options together.
		 *
		 * @var string
		 */
		public static $settings_group = '%s-group';

		/**
		 * Default option values.
		 *
		 * @var array
		 */
		public static $defaults = array(
			'version'		=> null,
			'include'		=> array(
				'all'			=> false,
				'feed'			=> false,
				'home'			=> false,
				'archives'		=> false,
				'tax'			=> false,
				'tag'			=> true,
				'category'		=> false,
				'author'		=> false,
				'date'			=> false,
				'search'		=> true,
			),
			'uninstall'		=> array(
				'delete_posts'		=> '',
				'delete_taxonomy'	=> '',
			),
		);

		/* *** Properties Holding Various Parts of the Class' State *** */

		/**
		 * Property holding the current options - auto-magically updated.
		 *
		 * @var	array
		 */
		public static $current;


		/* *** CLASS METHODS *** */

		/**
		 * Initialize our option and add all relevant actions and filters
		 *
		 * @return void
		 */
		public static function init() {

			/* Initialize properties. */
			self::set_properties();

			/*
			 * Make sure the option will always get validated, independently of register_setting()
			 * which is only available in the back-end.
			 */
			add_filter( 'sanitize_option_' . self::NAME, array( __CLASS__, 'validate_options' ) );

			/* Register our option for the admin pages. */
			add_action( 'admin_init', array( __CLASS__, 'register_setting' ) );

			/* Add filters which get applied to get_options() results. */
			self::add_default_filter();
			add_filter( 'option_' . self::NAME, array( __CLASS__, 'filter_option' ) );

			/*
			 * The option validation routines remove the default filters to prevent failing to insert
			 * an options if it's new. Let's add them back afterwards.
			 */
			add_action( 'add_option', array( __CLASS__, 'add_default_filter' ) );
			add_action( 'update_option', array( __CLASS__, 'add_default_filter' ) );

			/* Refresh the $current property on successful option update. */
			add_action( 'add_option_' . self::NAME, array( __CLASS__, 'on_add_option' ), 10, 2 );
			add_action( 'update_option_' . self::NAME, array( __CLASS__, 'on_update_option' ), 10, 2 );

			/* Initialize the $current property. */
			self::refresh_current();
		}


		/**
		 * Adjust property value.
		 *
		 * @return void
		 */
		public static function set_properties() {
			self::$settings_group = sprintf( self::$settings_group, self::NAME );
		}


		/**
		 * Register our option.
		 *
		 * @return void
		 */
		public static function register_setting() {
			register_setting(
				self::$settings_group,
				self::NAME // Option name.
			);
		}


		/**
		 * Add filtering of the option default values.
		 *
		 * @return void
		 */
		public static function add_default_filter() {
			if ( has_filter( 'default_option_' . self::NAME, array( __CLASS__, 'filter_option_defaults' ) ) === false ) {
				add_filter( 'default_option_' . self::NAME, array( __CLASS__, 'filter_option_defaults' ) );
			};
		}


		/**
		 * Remove filtering of the option default values.
		 *
		 * This is needed to allow for inserting of the option if it doesn't exist.
		 * Should be called from our validation routine.
		 *
		 * @return void
		 */
		public static function remove_default_filter() {
			remove_filter( 'default_option_' . self::NAME, array( __CLASS__, 'filter_option_defaults' ) );
		}


		/**
		 * Filter option defaults.
		 *
		 * This in effect means that get_option() will not return false if the option is not found,
		 * but will instead return our defaults. This way we always have all of our option values available.
		 *
		 * @return array
		 */
		public static function filter_option_defaults() {
			self::refresh_current( self::$defaults );
			return self::$defaults;
		}


		/**
		 * Filter option.
		 *
		 * This in effect means that get_option() will not just return our option from the database,
		 * but will instead return that option merged with our defaults.
		 * This way we always have all of our option values available. Even when we add new option
		 * values (to the defaults array) when the plugin is upgraded.
		 *
		 * @param array $options Current options.
		 *
		 * @return array
		 */
		public static function filter_option( $options ) {
			$options = self::array_filter_merge( self::$defaults, $options );
			self::refresh_current( $options );
			return $options;
		}


		/**
		 * Set the $current property to the value of our option.
		 *
		 * @param mixed $value Option value.
		 *
		 * @return void
		 */
		public static function refresh_current( $value = null ) {
			if ( ! isset( $value ) ) {
				$value = get_option( self::NAME );
			}
			self::$current = $value;
		}


		/**
		 * Refresh the $current property when our property is added to WP.
		 *
		 * @param string $option_name Option name, not used as hooked in in a way that
		 *                            this function will only run on our option anyway.
		 * @param mixed  $value       Current value of the option.
		 *
		 * @return void
		 */
		public static function on_add_option( $option_name, $value ) {
			self::refresh_current( $value );
		}


		/**
		 * Refresh the $current property when our property is updated.
		 *
		 * @param mixed $old_value Old value of the option.
		 * @param mixed $value     New value of the option.
		 *
		 * @return void
		 */
		public static function on_update_option( $old_value, $value ) {
			self::refresh_current( $value );
		}


		/* *** HELPER METHODS *** */

		/**
		 * Helper method - Combines a fixed array of default values with an options array
		 * while filtering out any keys which are not in the defaults array.
		 *
		 * @static
		 *
		 * @param array	$defaults Entire list of supported defaults.
		 * @param array	$options  Current options.
		 *
		 * @return array Combined and filtered options array.
		 */
		public static function array_filter_merge( $defaults, $options ) {
			$options = (array) $options;
			$return  = array();

			foreach ( $defaults as $name => $default ) {
				if ( array_key_exists( $name, $options ) ) {
					$return[ $name ] = $options[ $name ];
				}
				else {
					$return[ $name ] = $default;
				}
			}
			return $return;
		}


		/* *** OPTION VALIDATION *** */

		/**
		 * Validated the settings received from our options page.
		 *
		 * @todo inform user of validation errors on upgrade via transient API
		 *
		 * @param array $received Our $_POST variables.
		 *
		 * @return array Cleaned settings to be saved to the db
		 */
		public static function validate_options( $received ) {

			self::remove_default_filter();

			/* Don't change anything if user does not have the required capability. */
			if ( false === is_admin() || false === current_user_can( self::REQUIRED_CAP ) ) {
				return self::$current;
			}

			/* Start off with the current settings and where applicable, replace values with valid received values. */
			$clean = self::$current;

			/* Validate the Include section. */
			foreach ( $clean['include'] as $key => $value ) {
				// Check if we have received this option.
				if ( isset( $received['include'][ $key ] ) ) {
					$clean['include'][ $key ] = filter_var( $received['include'][ $key ], FILTER_VALIDATE_BOOLEAN );
				}
				else {
					$clean['include'][ $key ] = false;
				}
			}
			unset( $key, $value );

			/* Validate the Uninstall section. */
			if ( ! empty( $received['uninstall'] ) && is_array( $received['uninstall'] ) ) {
				foreach ( $received['uninstall'] as $key => $value ) {
					// Check if we have a valid option.
					if ( isset( $clean['uninstall'][ $key ] ) ) {
						// Check if the value received is valid.
						// @todo - maybe figure out a way to send error via transient if encountered when settings API not loaded (yet).
						if ( ! empty( $value ) && self::DELETE_KEYWORD !== trim( $value ) && function_exists( 'add_settings_error' ) ) {
							add_settings_error(
								self::$settings_group, // Slug title of the setting.
								'uninstall_' . $key, // Suffix-id for the error message box.
								sprintf(
									/* TRANSLATORS: 1: Setting name, 2: Valid Setting value. */
									esc_html__( 'For the uninstall setting "%1$s", the only valid value is "%2$s". Otherwise, leave the box empty.', 'demo-quotes-plugin' ),
									'<em>' . esc_html( $GLOBALS['demo_quotes_plugin']->settings_page->form_sections['uninstall']['fields'][ $key ]['title'] ) . '</em>',
									self::DELETE_KEYWORD
								), // The error message.
								'error' // Error type, either 'error' or 'updated'.
							);
							$clean['uninstall'][ $key ] = '';
						}
						else {
							$clean['uninstall'][ $key ] = $value;
						}
					}
				}
				unset( $key, $value );
			}

			$clean['version'] = Demo_Quotes_Plugin::VERSION;

			return $clean;
		}
	} /* End of class. */

	/* Add our actions and filters. */
	Demo_Quotes_Plugin_Option::init();

} /* End of class exists wrapper. */
