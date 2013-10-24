<?php

// Avoid direct calls to this file
if ( !function_exists( 'add_action' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

if ( class_exists( 'Demo_Quotes_Plugin' ) && ! class_exists( 'Demo_Quotes_Plugin_Option' ) ) {
	/**
	 * @package WordPress\Plugins\Demo_Quotes_Plugin
	 * @subpackage Option
	 * @version 1.0
	 * @link https://github.com/jrfnl/wp-plugin-best-practices-demo WP Plugin Best Practices Demo
	 *
	 * @copyright 2013 Juliette Reinders Folmer
	 * @license http://creativecommons.org/licenses/GPL/3.0/ GNU General Public License, version 3
	 */
	class Demo_Quotes_Plugin_Option {
		

		/* *** DEFINE CLASS CONSTANTS *** */

		/**
		 * @const	string	Name of options variable containing the plugin proprietary settings
		 */
		const NAME = 'demo_quotes_plugin_options';

		/**
		 * @const	string	Minimum required capability to access the settings page and change the plugin options
		 */
		const REQUIRED_CAP = 'manage_options';


		/**
		 * @const	string	Keyword used for the uninstall settings
		 */
		const DELETE_KEYWORD = 'DELETE';


		/* *** DEFINE CLASS PROPERTIES *** */


		/* *** Static Properties *** */

		/**
		 * @var string	Unique group identifier for all our options together
		 */
		public static $settings_group = '%s-group';


		/**
		 * @var array	Default option values
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
			'upgrading'		=> false, // will never change, only used to distinguish a call from the upgrade method
		);
		
		/* *** Properties Holding Various Parts of the Class' State *** */

		/**
		 * @var	array	Property holding the current options - automagically updated
		 */
		public static $current;



		/* *** CLASS METHODS *** */

		/**
		 * Initialize our option and add all relevant actions and filters
		 */
		public static function init() {
			
			/* Initialize properties */
			self::set_properties();
			
			/* Register our option (and it's validation) as early as possible */
			add_action( 'admin_init', array( __CLASS__, 'register_setting' ), 1 );


			/* Add filters which get applied to get_options() results */
			self::add_default_filter();
			add_filter( 'option_' . self::NAME, array( __CLASS__, 'filter_option' ) );

			/* The option validation routines remove the default filters to prevent failing to insert
			   an options if it's new. Let's add them back afterwards */
			add_action( 'add_option', array( __CLASS__, 'add_default_filter' ) );
			// @todo - figure out a way to add our filters back if the database update failed - false is returned without an action hook - not a problem with add_option
			// Actually, the better fix would be to make the change in core as it is now inconsistent
			//add_action( 'update_option', array( __CLASS__, 'add_default_filter' ) );
			// Current solution - abuse a filter:
			add_filter( 'pre_update_option_' . self::NAME, array( __CLASS__, 'pre_update_option' ) );



			/* Refresh the $current property on succesfull option update */
			add_action( 'add_option_' . self::NAME, array( __CLASS__, 'on_add_option' ), 10, 2 );
			add_action( 'update_option_' . self::NAME, array( __CLASS__, 'on_update_option' ), 10, 2 );

			/* Lastly, we'll be saving our option during the upgrade routine *before* the setting
			   is registered (and therefore the validation is registered), so make sure that the
			   option is validated anyway. */
			add_filter( 'demo_quotes_save_option_on_upgrade', array( __CLASS__, 'validate_options' ) );

			/* Initialize the $current property */
			self::refresh_current();
		}


		/**
		 * Adjust property value
		 *
		 * @return void
		 */
		public static function set_properties() {
			self::$settings_group = sprintf( self::$settings_group, self::NAME );
		}
		
		/**
		 * Register our option
		 */
		public static function register_setting() {
			register_setting(
				self::$settings_group,
				self::NAME, // option name
				array( __CLASS__, 'validate_options' ) // validation callback
			);
		}

		/**
		 * Add filtering of the option default values
		 */
		public static function add_default_filter() {
			if ( has_filter( 'default_option_' . self::NAME, array( __CLASS__, 'filter_option_defaults' ) ) === false ) {
				add_filter( 'default_option_' . self::NAME, array( __CLASS__, 'filter_option_defaults' ) );
			};
		}
		

		static function pre_update_option( $new_value ) {
			self::add_default_filter();
			return $new_value;
		}


		/**
		 * Remove filtering of the option default values
		 *
		 * This is need to allow for inserting of option if it doesn't exist
		 * Should be called from our validation routine
		 */
		public static function remove_default_filter() {
			remove_filter( 'default_option_' . self::NAME, array( __CLASS__, 'filter_option_defaults' ) );
		}


		/**
		 * Filter option defaults
		 *
		 * This in effect means that get_option() will not return false if the option is not found,
		 * but will instead return our defaults. This way we always have all of our option values available.
		 */
		public static function filter_option_defaults() {
			self::refresh_current( self::$defaults );
			return self::$defaults;
		}


		/**
		 * Filter option
		 *
		 * This in effect means that get_option() will not just return our option from the database,
		 * but will instead return that option merged with our defaults.
		 * This way we always have all of our option values available. Even when we add new option
		 * values (to the defaults array) when the plugin is upgraded.
		 */
		public static function filter_option( $options ) {
			$options = self::array_filter_merge( self::$defaults, $options );
			self::refresh_current( $options );
			return $options;
		}


		/**
		 * Set the $current property to the value of our option
		 */
		public static function refresh_current( $value = null ) {
			if ( !isset( $value ) ) {
				$value = get_option( self::NAME );
			}
			self::$current = $value;
		}


		/**
		 * Refresh the $current property when our property is added to wp
		 */
		public static function on_add_option( $option_name, $value ) {
			self::refresh_current( $value );
		}


		/**
		 * Refresh the $current property when our property is updated
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
		 * @param	array	$defaults	Entire list of supported defaults.
		 * @param	array	$options	Current options.
		 * @return	array	Combined and filtered options array.
		 */
		public static function array_filter_merge( $defaults, $options ) {
			$options = (array) $options;
			$return  = array();
		
			foreach ( $defaults as $name => $default ) {
				if ( array_key_exists( $name, $options ) )
					$return[$name] = $options[$name];
				else
					$return[$name] = $default;
			}
			return $return;
		}




		/* *** OPTION VALIDATION *** */

		/**
		 * Validated the settings received from our options page
		 *
		 * @todo inform user of validation errors on upgrade via transient API
		 *
		 * @param  array    $received     Our $_POST variables
		 * @return array    Cleaned settings to be saved to the db
		 */
		public static function validate_options( $received ) {

			self::remove_default_filter();

			/* Don't change anything if user does not have the required capability */
			if ( false === is_admin() || false === current_user_can( self::REQUIRED_CAP ) ) {
				return self::$current;
			}


			/* Start off with the current settings and where applicable, replace values with valid received values */
			$clean = self::$current;


			/* Validate the Include section */
			foreach ( $clean['include'] as $key => $value ) {
				// Check if we have received this option
				if ( isset( $received['include'][$key] ) ) {
					$clean['include'][$key] = filter_var( $received['include'][$key], FILTER_VALIDATE_BOOLEAN );
				}
				else {
					$clean['include'][$key] = false;
				}
			}
			unset( $key, $value );


			/* Validate the Uninstall section */
			if ( isset( $received['uninstall'] ) && ( is_array( $received['uninstall'] ) && $received['uninstall'] !== array() ) ) {
				foreach ( $received['uninstall'] as $key => $value ) {
					// Check if we have a valid option
					if ( isset( $clean['uninstall'][$key] ) ) {
						// Check if the value received is valid
						if ( $value !== '' && $value !== self::DELETE_KEYWORD && $received['upgrading'] !== true ) {
							add_settings_error(
								self::$settings_group, // slug title of the setting
								'uninstall_' . $key, // suffix-id for the error message box
								sprintf( __( 'For the uninstall setting "%s", the only valid value is "%s". Otherwise, leave the box empty.', Demo_Quotes_Plugin::$name ), '<em>' . $GLOBALS['demo_quotes_plugin']->settings_page->form_sections['uninstall']['fields'][$key]['title'] . '</em>', self::DELETE_KEYWORD ), // the error message
								'error' // error type, either 'error' or 'updated'
							);
							$clean['uninstall'][$key] = '';
						}
						else {
							$clean['uninstall'][$key] = $value;
						}
					}
				}
				unset( $key, $value );
			}

			return $clean;
		}
	} // End of class
	
	/* Add our actions and filters */
	Demo_Quotes_Plugin_Option::init();

} // End of class exists wrapper