<?php

// Avoid direct calls to this file
if ( !function_exists( 'add_action' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

if ( class_exists( 'Demo_Quotes_Plugin' ) && ! class_exists( 'Demo_Quotes_Plugin_Settings_Page' ) ) {
	/**
	 * @package WordPress\Plugins\Demo_Quotes_Plugin
	 * @subpackage Settings_Page
	 * @version 1.0
	 * @link https://github.com/jrfnl/wp-plugin-best-practices-demo WP Plugin Best Practices Demo
	 *
	 * @copyright 2013 Juliette Reinders Folmer
	 * @license http://creativecommons.org/licenses/GPL/3.0/ GNU General Public License, version 3
	 */
	class Demo_Quotes_Plugin_Settings_Page {
		
		/* *** DEFINE CLASS CONSTANTS *** */

		/**
		 * @const
		 */
		const DELETE_KEYWORD = 'DELETE';


		/* *** DEFINE CLASS PROPERTIES *** */

		/**
		 * @var string	Parent page to hook our settings page under
		 */
		public $parent_page = 'edit.php?post_type=%s';

		/**
		 * @var string	Menu slug for our settings page
		 */
		public $menu_slug = '%s-settings';
		
		/**
		 * @var string	Unique group identifier for all our options together
		 */
		public $settings_group = '%s-group';
		
		public $setting_prefix = 'dqp';
		
		/**
		 * @var array   array of option form sections
		 *				Will be set by set_properties() as the section (and field) labels need translating
		 * @usedby display_options_page()
		 */
		public $form_sections = array();


		/* *** Properties Holding Various Parts of the Class' State *** */

		/**
		 * @var string settings page registration hook suffix
		 */
		public $hook;


		/**
		 * Constructor
		 *
		 * @return \Demo_Quotes_Plugin_Settings_Page
		 */
		public function __construct() {
			
			/* Translate a number of strings */
			$this->set_properties();
			
			/* Add the options page */
			$this->add_submenu_page();

			/* Add option page related actions */
			add_action( 'admin_init', array( $this, 'admin_init' ) );
		}


		/**
		 * Fill some property arrays with translated strings
		 * Enrich some others
		 *
		 * @return void
		 */
		public function set_properties() {

			$this->form_sections = array(
				'include'	=> array(
					'title'			=> __( 'Website integration:', Demo_Quotes_Plugin::$name ),
					'field_label'	=> __( 'Show the Demo Quotes on:', Demo_Quotes_Plugin::$name ),
					/* For this section, the fields are not defined as plain fields as we want more control
					   over the presentation.
					   We'll add these ourselves via the section callback rather than let WP
					   add the fields via the fields callback */
					'section_fields_def'	=> array(
						'frontend'		=> array(
							'title'			=> __( 'Front-end', Demo_Quotes_Plugin::$name ),
							'fields'		=> array(
								'all'			=> array(
									'label'			=> __( 'Include Demo Quotes in all front-end queries ?', Demo_Quotes_Plugin::$name ),
									'explain'		=> __( 'This means that the demo quotes will also show up in, for instance, \'Recent Posts\' widgets and the like.', Demo_Quotes_Plugin::$name ),
									'parents'		=> false,
								),
								'home'			=>  array(
									'label'			=> __( 'Show Demo Quotes on the main blog page ?', Demo_Quotes_Plugin::$name ),
									'parents'		=> array( 'all' ),
								),
								'archives'		=> array(
									'label'			=> __( 'Show Demo Quotes on all archive pages ?', Demo_Quotes_Plugin::$name ),
									'parents'		=> array( 'all' ),
								),
								'tag'			=> array(
									'label'			=> __( 'Show Demo Quotes on tag archive pages ?', Demo_Quotes_Plugin::$name ),
									'parents'		=> array( 'all', 'archives' ),
								),
								'category'		=> array(
									'label'			=> __( 'Show Demo Quotes on category archive pages ?', Demo_Quotes_Plugin::$name ),
									'explain'		=> __( 'As the category taxonomy is disabled for demo quotes, this will have no effect. Unless, of course, you enable categories for demo quotes. (link to FAQ)', Demo_Quotes_Plugin::$name ),
									'parents'		=> array( 'all', 'archives' ),
								),
								'tax'			=> array(
									'label'			=> __( 'Show Demo Quotes on custom taxonomy archive pages ?', Demo_Quotes_Plugin::$name ),
									'parents'		=> array( 'all', 'archives' ),
								),
								'author'		=> array(
									'label'			=> __( 'Show Demo Quotes on author archive pages ?', Demo_Quotes_Plugin::$name ),
									'explain'		=> __( 'This is unrelated to the people taxonomy. We mean ... link to user\'s own page ...', Demo_Quotes_Plugin::$name ),
									'parents'		=> array( 'all', 'archives' ),
								),
								'date'			=> array(
									'label'			=> __( 'Show Demo Quotes on date based archive pages ?', Demo_Quotes_Plugin::$name ),
									'parents'		=> array( 'all', 'archives' ),
								),
							),
						),
						'frontend_misc'		=> array(
							'title'			=> __( 'Front-end miscellaneous', Demo_Quotes_Plugin::$name ),
							'fields'		=> array(
								'feed'			=>  array(
									'label'			=> __( 'Include Demo Quotes in the normal RSS feed ?', Demo_Quotes_Plugin::$name ),
								),
								'search'		=> array(
									'label'			=> __( 'Include Demo Quotes in the results of user searches ?', Demo_Quotes_Plugin::$name ),
								),
							),
						),
					),
				),
				'uninstall'	=> array(
					'title'		=> __( 'Uninstall Settings', Demo_Quotes_Plugin::$name ),
					'fields'	=> array(
						'delete_posts'		=> array(
							'title'		=>	__( 'Delete all demo quote posts when uninstalling ?', Demo_Quotes_Plugin::$name ),
							'callback'	=> 'do_settings_field_text_field',
						),
						'delete_taxonomy'	=> array(
							'title'		=> __( 'Delete all entries in the people taxonomy when uninstalling ?', Demo_Quotes_Plugin::$name ),
							'callback'	=> 'do_settings_field_text_field',
						),
					),

				),
			);

			$this->parent_page    = sprintf( $this->parent_page, Demo_Quotes_Plugin_Cpt::$post_type_name );
			$this->menu_slug      = sprintf( $this->menu_slug, Demo_Quotes_Plugin::$name );
			$this->settings_group = sprintf( $this->settings_group, Demo_Quotes_Plugin::SETTINGS_OPTION );
		}


		/**
		 * Register the settings page for all users that have the required capability
		 *
		 * @return void
		 */
		public function add_submenu_page() {

			$this->hook = add_submenu_page(
				$this->parent_page, /* parent slug */
				__( 'Demo Quotes Plugin Settings', Demo_Quotes_Plugin::$name ), /* page title */
				__( 'Settings', Demo_Quotes_Plugin::$name ), /* menu title */
				Demo_Quotes_Plugin::SETTINGS_REQUIRED_CAP, /* capability */
				$this->menu_slug, /* menu slug */
				array( $this, 'display_options_page' ) /* function for subpanel */
			);
		}



		/**
		 * Set up our settings page
		 *
		 * @return void
		 */
		public function admin_init() {

			/* Don't do anything if user does not have the required capability */
			if ( false === is_admin() || false === current_user_can( Demo_Quotes_Plugin::SETTINGS_REQUIRED_CAP ) ) {
				return;
			}

			/* Register our options field */
			register_setting(
				$this->settings_group,
				Demo_Quotes_Plugin::SETTINGS_OPTION, // option name
				array( $this, 'validate_options' ) // validation callback
			);

			/* Register the settings sections and their callbacks */
			foreach ( $this->form_sections as $section => $section_info ) {
				add_settings_section(
					'dqp-' . $section . '-settings', // id
					$section_info['title'], // title
					array( $this, 'do_settings_section_' . $section ), // callback for this section
					$this->menu_slug // page menu_slug
				);
				
				/* Register settings fields for the section */
				if ( isset( $section_info['fields'] ) && ( is_array( $section_info['fields'] ) && $section_info['fields'] !== array() ) ) {
					foreach ( $section_info['fields'] as $field => $field_def ) {
						add_settings_field(
							$this->setting_prefix . '_' . $section . '_' . $field, // field id
							$field_def['title'], // field title
							array( $this, $field_def['callback'] ), // callback for this field
							$this->menu_slug, // page menu slug
							'dqp-' . $section . '-settings', // section id
							array(
								'label_for'	=> $this->setting_prefix . '_' . $section . '_' . $field,
								'name'		=> Demo_Quotes_Plugin::SETTINGS_OPTION . '[' . $section . '][' . $field . ']',
								'section'	=> $section,
								'field'		=> $field,
							) // array of arguments which will be passed to the callback
						);
					}
				}
			}


			/* Add settings link on plugin page */
			add_filter( 'plugin_action_links_' . Demo_Quotes_Plugin::$basename , array( $this, 'add_settings_link' ), 10, 2 );

			/* Add help tabs for our settings page */
			add_action( 'load-' . $this->hook, array( $this, 'add_help_tab' ) );
		}
		



		/**
		 * Add settings link to plugin row
		 *
		 * @param	array	$links	Current links for the current plugin
		 * @param	string	$file	The file for the current plugin
		 * @return	array
		 */
		public function add_settings_link( $links, $file ) {

			if ( Demo_Quotes_Plugin::$basename === $file && current_user_can( Demo_Quotes_Plugin::SETTINGS_REQUIRED_CAP ) ) {
				$links[] = '<a href="' . esc_url( $this->plugin_options_url() ) . '" alt="' . esc_attr__( 'Demo Quotes Plugin Settings', Demo_Quotes_Plugin::$name ) . '">' . esc_html__( 'Settings', Demo_Quotes_Plugin::$name ) . '</a>';
			}
			return $links;
		}

		/**
		 * Return absolute URL of options page
		 *
		 * @return string
		 */
		public function plugin_options_url() {
			return add_query_arg( 'page', $this->menu_slug, admin_url( $this->parent_page ) );
		}


		/**
		 * Adds contextual help tab to the plugin settings page
		 *
		 * @return void
		 */
		public function add_help_tab() {

			$screen = get_current_screen();

			if ( property_exists( $screen, 'base' ) && $screen->base === $this->hook ) {
				$screen->add_help_tab(
					array(
						'id'	  => Demo_Quotes_Plugin::$name . '-settings', // This should be unique for the screen.
						'title'   => __( 'Settings', Demo_Quotes_Plugin::$name ),
						'callback' => array( 'Demo_Quotes_Plugin', 'get_helptext' ),
					)
				);
				$screen->add_help_tab(
					array(
						'id'	  => Demo_Quotes_Plugin::$name . '-main', // This should be unique for the screen.
						'title'   => __( 'About', Demo_Quotes_Plugin::$name ),
						'callback' => array( 'Demo_Quotes_Plugin', 'get_helptext' ),
					)
				);

				$screen->set_help_sidebar( Demo_Quotes_Plugin::get_help_sidebar() );
			}
		}







		/* *** OPTION VALIDATION *** */

		/**
		 * Validated the settings received from our options page
		 *
		 * @param  array    $received     Our $_POST variables
		 * @return array    Cleaned settings to be saved to the db
		 */
		public function validate_options( $received ) {

			/* Don't change anything if user does not have the required capability */
			if ( false === is_admin() || false === current_user_can( Demo_Quotes_Plugin::SETTINGS_REQUIRED_CAP ) ) {
				return $GLOBALS['demo_quotes_plugin']->settings;
			}


			/* Start off with the current settings and where applicable, replace values with valid received values */
			$clean = $GLOBALS['demo_quotes_plugin']->settings;


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
						if ( $value !== '' && $value !== self::DELETE_KEYWORD ) {
							add_settings_error(
//								Demo_Quotes_Plugin::SETTINGS_OPTION, // slug title of the setting
								$this->settings_group, // slug title of the setting
								'uninstall_' . $key, // suffix-id for the error message box
								sprintf( __( 'For the uninstall setting "%s", the only valid value is "%s". Otherwise, leave the box empty.', Demo_Quotes_Plugin::$name ), '<em>' . $this->form_sections['uninstall']['fields'][$key]['title'] . '</em>', self::DELETE_KEYWORD ), // the error message
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


		/**
		 * Validate received classnames and parse them from a string to an array
		 * Returns false if received value is not a string or empty
		 *
		 * @usedby validate_options() and upgrade_options()
		 * @param string $classnames
		 * @return array|bool
		 */
/*		public function validate_classnames( $classnames = '' ) {
			$return = false;

			if ( is_string( $classnames ) && '' !== $classnames ) {
				$classnames = sanitize_text_field( $classnames );
				$classnames = explode( ',', $classnames );
				$classnames = array_map( 'trim', $classnames );
				$classnames = array_map( 'sanitize_html_class', $classnames );
				$classnames = array_filter( $classnames ); // removes empty strings
				if ( is_array( $classnames ) && 0 < count( $classnames ) ) {
					$return = $classnames;
				}
			}
			return $return;
		}
*/


		/* *** SETTINGS PAGE DISPLAY METHODS *** */

		/**
		 * Display our options page using the Settings API
		 *
		 * Useful functions available to get access to the parameters you used in add_submenu_page():
		 * - $parent_slug: get_admin_page_parent()
		 * - $page_title: get_admin_page_title(), or simply global $title
		 * - $menu_slug: global $plugin_page
		 *
		 * @return void
		 */
		public function display_options_page() {

			if ( !current_user_can( Demo_Quotes_Plugin::SETTINGS_REQUIRED_CAP ) ) {
				/* TRANSLATORS: no need to translate - standard WP core translation will be used */
				wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
			}

			/**
			 * Display the updated/error messages
			 * Only needed if our settings page is not under options, otherwise it will automatically be included
			 * @see settings_errors()
			 */
			include_once( 'options-head.php' );


			/* Display the settings page */
			echo '
		<div class="wrap">';

			screen_icon();

			echo '
		<h2>' . get_admin_page_title() . '</h2>
		<form action="options.php" method="post"' . ( ( defined( 'DB_CHARSET' ) && DB_CHARSET === 'utf8' ) ? ' accept-charset="utf-8"' : '' ) . '>';

			settings_fields( $this->settings_group );
			do_settings_sections( $this->menu_slug );
			submit_button();


			echo '
		</form>';

			/* Add our current settings array to the page for debugging purposes */
			if ( WP_DEBUG ) {
				echo '
		<div id="poststuff">
		<div id="dqp-debug-info" class="postbox">

			<h3 class="hndle"><span>' . __( 'Debug Information', Demo_Quotes_Plugin::$name ) . '</span></h3>
			<div class="inside">
				<pre>';
				print_r( $GLOBALS['demo_quotes_plugin']->settings );
				echo '
				</pre>
			</div>
		</div>
		</div>';
			}
		}


		/**
		 * Display the Include Settings section of our options page
		 *
		 * Note: If you want more complex fields than what you can accomplish with add_settings_field() while still
		 * generating valid HTML, you can 'abuse' the settings_section callback to generate the form fields
		 * for the section
		 *
		 * @return void
		 */
		public function do_settings_section_include() {

			$section = 'include';

			echo '
			<table class="form-table">
			<tbody>
				<tr valign="top">
					<th scope="row">' . $this->form_sections[$section]['field_label'] . '</th>
					<td>
						<fieldset class="options dqp-' . $section . '" name="dqp-' . $section . '">';

			foreach ( $this->form_sections[$section]['section_fields_def'] as $group => $fieldset ) {

				if( is_array( $fieldset['fields'] ) && $fieldset['fields'] !== array() ) {
					echo '
						<h4>' . $fieldset['title']. '</h4>
						<div class="dqp-' . $section . '-group">';

					foreach ( $fieldset['fields'] as $field => $field_def ) {
						$args = array(
							'name'		=> Demo_Quotes_Plugin::SETTINGS_OPTION . '[' . $section . '][' . $field . ']',
							'label_for'	=> $this->setting_prefix . '_' . $section . '_' . $field,
							'label'		=> ( isset( $field_def['label'] ) ? $field_def['label'] : null ),
							'explain'	=> ( isset( $field_def['explain'] ) ? $field_def['explain'] : null ),
							'section'	=> $section,
							'field'		=> $field,
						);
						$args['id'] = $args['label_for'];
	
						$classes = '';
						if ( isset( $field_def['parents'] ) && $field_def['parents'] !== false ) {
							$classes = array( 'indent-' . ( count( $field_def['parents'] ) + 1 ) );
							$parents = array_map( array( $this, 'class_prefix' ), $field_def['parents'] );
							$classes = array_merge( $classes, array( 'has-parents' ), $parents );
							$classes = ' class="' . implode( ' ', $classes ) . '"';
						}
						echo '
							<div' . $classes . '>';

						$this->do_settings_field_checkbox_field( $args );
	
						echo '
						 	</div>';
					}

					echo '
						</div>';
				}
			}

			echo '
	  			  		</fieldset>
	  				</td>
	  			</tr>
	  		</tbody>
	  		</table>';
		}


		/**
		 * Prefix a value (for use with array_map)
		 *
		 * @param	string	$value
		 * @param	string	$prefix
		 * @return	string
		 */
		public function class_prefix( $value ) {
			$prefix = $this->setting_prefix . '_include_';
			return $prefix . $value;
		}


		/**
		 * Display the Uninstall Settings section of our options page
		 *
		 * @return void
		 */
		public function do_settings_section_uninstall() {

			echo '
			<div class="dqp-explain">
				 <p>' . __( 'Here you can determine what happens with the information you added to your website with this plugin in case you would decide to uninstall the plugin.', Demo_Quotes_Plugin::$name ) . '</p>
				 <p>' . __( 'Generally it is considered good practice to <em>clean up</em> when uninstalling a plugin. This means in practice that all data added to the database through this plugin should be deleted.', Demo_Quotes_Plugin::$name ) . '</p>
				 <p>' . __( 'This also means that if - at a later point in time - you would decide to re-install the plugin, all your previously entered data will be gone.', Demo_Quotes_Plugin::$name ) . '</p>
				 <p>' . __( 'So, rather than just going ahead and deleting everything, I believe it\'s up to <strong>you</strong> to decide what happens to your data.', Demo_Quotes_Plugin::$name ) . '</p>
				 <p>' . sprintf( __( 'If you leave the below boxes empty, nothing will happen to your data when you uninstall the plugin. However, if you type the word %s in any of the boxes, that particular data will be deleted.', Demo_Quotes_Plugin::$name ), self::DELETE_KEYWORD ) . '</p>
				 <p>' . sprintf( __( '<em>Make sure you make no spelling mistakes!</em>', Demo_Quotes_Plugin::$name ), 'DELETE' ) . '</p>
			</div>
			<div class="dqp-explain important">
				 <p>' . __( 'N.B.1: When you deactivate the plugin, your information will always stay in the database untouched.', Demo_Quotes_Plugin::$name ) . '</p>
				 <p>' . __( 'N.B.2: Information not added through this plugin (i.e. tags, posts, pages, attachments etc), will <strong><em>not</em></strong> be affected by the choice you make here.', Demo_Quotes_Plugin::$name ) . '</p>
			</div>
			';

		}


		/**
		 * Generate a text form field
		 *
		 * @param array		$args
		 * @return void
		 */
		public function do_settings_field_text_field( $args ) {
			echo '
				 <input type="text" name="' . esc_attr( $args['name'] ) . '" id="' . esc_attr( $args['label_for'] ) . '" value="' . esc_attr( $GLOBALS['demo_quotes_plugin']->settings[$args['section']][$args['field']] ) . '" />
				 <span class="dqp-explain">' . sprintf( __( 'Type the word %s here to give this plugin permission to delete its data', Demo_Quotes_Plugin::$name ), self::DELETE_KEYWORD ) . '</span>
			';
		}


		/**
		 * Generate a checkbox form field
		 *
		 * @param array		$args
		 * @return void
		 */
		public function do_settings_field_checkbox_field( $args ) {

			$checked = checked( true, $GLOBALS['demo_quotes_plugin']->settings[$args['section']][$args['field']], false );
			echo '
				 <input type="checkbox" name="' . esc_attr( $args['name'] ) . '" id="' . esc_attr( $args['label_for'] ) . '" value="on" ' . $checked . '/>';

			if ( ( isset( $args['label'] ) && $args['label'] !== '' ) && isset( $args['id'] ) && $args['id'] !== '' ){
				echo '<label for="' . esc_attr( $args['id'] ) . '"> ' . esc_html( $args['label'] ) . '</label>';
			}

			if ( isset( $args['explain'] ) && $args['explain'] !== '' ) {
				echo '<br />
				<span class="dqp-explain">' . $args['explain'] . '</span>';
			}
		}
	} // End of class
} // End of class exists wrapper