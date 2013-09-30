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
		
		

		
		/**
		 * @const
		 */
		const DELETE_KEYWORD = 'DELETE';




		public $parent_page = 'edit.php?post_type=%s';
		
		public $menu_slug = '%s-settings';
		
		public $settings_group = '%s-group';
		
		/**
		 * @var array   array of option form sections: key = setting area, value = section label
		 *				Will be set by set_properties() as the section labels need translating
		 * @usedby display_options_page()
		 */
		public $form_sections = array();
/*
				'title'		=> '',
				'fields'	=> array(),
*/

		public $form_field_titles = array();


		/**
		 * @var string settings page registration hook suffix
		 */
		public $hook;

		
/*
			'include'		=> array(
				'all'			=> false,
				'feed'			=> false,
				'home'			=> false,
				'archives'		=> false,
				'tax'			=> false,
				'tag'			=> false,
				'category'		=> false,
				'author'		=> false,
				'date'			=> false,
				'search'		=> false,
				'admin'			=> false,
			),
			'uninstall'		=> array(
				'delete_posts'		=> false,
				'delete_taxonomy'	=> false,
			),
*/

		/**
		 *
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
		 */
		public function set_properties() {

			$this->form_sections = array(
				'include'	=> array(
					'title'		=> __( 'Include the demo quotes in:', Demo_Quotes_Plugin::$name ),
				),
				'uninstall'	=> array(
					'title'		=> __( 'Uninstall Settings', Demo_Quotes_Plugin::$name ),
					'fields'	=> array(
						'delete_posts'		=> __( 'Delete all demo quote posts when uninstalling ?', Demo_Quotes_Plugin::$name ),
						'delete_taxonomy'	=> __( 'Delete all entries in the people taxonomy when uninstalling ?', Demo_Quotes_Plugin::$name ),
					),

				),
			);
/*			$this->form_sections = array(
				'include'	=> __( 'Include the demo quotes in:', Demo_Quotes_Plugin::$name ),
				'uninstall'	=> __( 'Uninstall Settings', Demo_Quotes_Plugin::$name ),
			);

			$this->form_field_titles = array(
				'uninstall'	=> array(
					'delete_posts',
					'delete_taxonomy'
				)
			);
*/

			$this->parent_page    = sprintf( $this->parent_page, Demo_Quotes_Plugin_Cpt::$post_type_name );
			$this->menu_slug      = sprintf( $this->menu_slug, Demo_Quotes_Plugin::$name );
			$this->settings_group = sprintf( $this->settings_group, Demo_Quotes_Plugin::SETTINGS_OPTION );
		}


		/**
		 * Register the options page for all users that have the required capability
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
					foreach ( $section_info['fields'] as $field => $field_title ) {
						add_settings_field(
							Demo_Quotes_Plugin::$name . '_' . $section . '_' . $field, // field id
							$field_title, // field title
							array( $this, 'do_settings_field_text_field' ), // callback for this field
							$this->menu_slug, // page menu slug
							'dqp-' . $section . '-settings', // section id
							array(
								'label_for'	=> Demo_Quotes_Plugin::$name . '_' . $section . '_' . $field,
								'name'		=> Demo_Quotes_Plugin::SETTINGS_OPTION . '[' . $section . '][' . $field . ']',
								'section'	=> $section,
								'field'		=> $field,
							) // array of arguments which will be passed to the callback
						);
					}
				}
			}
/*Parameters

$id
    (string) (required) String for use in the 'id' attribute of tags.

        Default: None 

$title
    (string) (required) Title of the field.

        Default: None 

$callback
    (string) (required) Function that fills the field with the desired inputs as part of the larger form. Passed a single argument, the $args array. Name and id of the input should match the $id given to this function. The function should echo its output.

        Default: None 

$page
    (string) (required) The menu page on which to display this field. Should match $menu_slug from Function Reference/add theme page

        Default: None 

$section
    (string) (optional) The section of the settings page in which to show the box (default or a section you added with add_settings_section, look at the page in the source to see what the existing ones are.)

        Default: default 

$args
    (array) (optional) Additional arguments that are passed to the $callback function. The 'label_for' key/value pair can be used to format the field title like so: <label for="value">$title</label>.

        Default: array() 
*/


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







		/* *** BACK-END: OPTIONS PAGE METHODS *** */

		/**
		 * Validated the settings received from our options page
		 *
		 * @param  array    $received     Our $_POST variables
		 * @return array    Cleaned settings to be saved to the db
		 */
		public function validate_options( $received ) {
//pr_var( $received );
			$clean = $GLOBALS['demo_quotes_plugin']->settings;

			if ( isset( $received['uninstall'] ) && ( is_array( $received['uninstall'] ) && $received['uninstall'] !== array() ) ) {
				foreach ( $received['uninstall'] as $key => $value ) {
					// Check if we have a valid option
					if ( isset( $this->form_sections['uninstall']['fields'][$key] ) ) {
						// Check if the value received is valid
						if ( $value !== '' && $value !== self::DELETE_KEYWORD ) {
							add_settings_error(
								Demo_Quotes_Plugin::SETTINGS_OPTION, // slug title of the setting
								'uninstall_' . $key, // suffix-id for the error message box
								sprintf( __( 'For the uninstall setting "%s", the only valid value is "%s". Otherwise, leave the box empty.', Demo_Quotes_Plugin::$name ), $this->form_sections['uninstall']['fields'][$key], self::DELETE_KEYWORD ), // the error message
								'error' // error type, either 'error' or 'updated'
							);
							$clean['uninstall'][$key] = '';
						}
						else {
							$clean['uninstall'][$key] = $value;
						}
					}
				}
			}
//exit;

			/* General settings */
/*			if ( isset( $received['image_size'] ) && true === in_array( $received['image_size'], $this->sizes ) ) {
				$clean['image_size'] = $received['image_size'];
			}
			else {
				// Edge case: should never happen
				add_settings_error( self::SETTINGS_OPTION, 'image_size', __( 'Invalid image size received', self::$name ) . ', ' . __( 'the value has been reset to the default.', self::$name ), 'error' );
			}

			if ( isset( $received['image_type'] ) && true === in_array( $received['image_type'], $this->image_types ) ) {
				$clean['image_type'] = $received['image_type'];
			}
			else {
				// Edge case: should never happen
				add_settings_error( self::SETTINGS_OPTION, 'image_size', __( 'Invalid image type received', self::$name ) . ', ' . __( 'the value has been reset to the default.', self::$name ), 'error' );
			}

			if ( isset( $received['leftorright'] ) && true === array_key_exists( $received['leftorright'], $this->alignments ) ) {
				$clean['leftorright'] = $received['leftorright'];
			}
			else {
				// Edge case: should never happen
				add_settings_error( self::SETTINGS_OPTION, 'leftorright', __( 'Invalid image placement received', self::$name ) . ', ' . __( 'the value has been reset to the default.', self::$name ), 'error' );
			}


			/* Images settings */
/*			foreach ( $this->mime_types as $mimetype ) {
				$clean['enable_' . $mimetype] = ( ( isset( $received['enable_' . $mimetype] ) && 'true' === $received['enable_' . $mimetype] ) ? true : false );
			}


			/* Advanced settings */
/*			$clean['enable_hidden_class'] = ( ( isset( $received['enable_hidden_class'] ) && 'true' === $received['enable_hidden_class'] ) ? true : false );

			if ( isset( $received['hidden_classname'] ) && '' !== $received['hidden_classname'] ) {
				$classnames = $this->validate_classnames( $received['hidden_classname'] );
				if ( false !== $classnames ) {
					$clean['hidden_classname'] = $classnames;
					if ( $received['hidden_classname'] !== implode( ',', $clean['hidden_classname'] ) && $received['hidden_classname'] !== implode( ', ', $clean['hidden_classname'] ) ) {
						add_settings_error( self::SETTINGS_OPTION, 'hidden_classname', __( 'One or more invalid classname(s) received, the values have been cleaned - this may just be the removal of spaces -, please check.', self::$name ), 'updated' );
					}
				}
				else {
					// Edge case: should never happen
					add_settings_error( self::SETTINGS_OPTION, 'hidden_classname', __( 'No valid classname(s) received', self::$name ) . ', ' . __( 'the value has been reset to the default.', self::$name ), 'error' );
				}
			}


			$clean['show_file_size'] = ( ( isset( $received['show_file_size'] ) && 'true' === $received['show_file_size'] ) ? true : false );

			if ( ( isset( $received['precision'] ) && '' !== $received['precision'] ) && ( true === ctype_digit( $received['precision'] ) && ( intval( $received['precision'] ) == $received['precision'] ) ) ) {
				$clean['precision'] = (int) $received['precision'];
			}
			else {
				add_settings_error( self::SETTINGS_OPTION, 'precision', __( 'Invalid rounding precision received', self::$name ) . ', ' . __( 'the value has been reset to the default.', self::$name ), 'error' );
			}

			$clean['use_cache'] = ( ( isset( $received['use_cache'] ) && 'true' === $received['use_cache'] ) ? true : false );
			// Delete the filesize cache if the cache option was unchecked to make sure a fresh cache will be build if and when the cache option would be checked again
			if ( false === $clean['use_cache'] && $clean['use_cache'] !== $this->settings['use_cache'] ) {
				delete_option( self::CACHE_OPTION );
			}

			// Value received is hours, needs to be converted to seconds before save
			if ( ( isset( $received['cache_time'] ) && '' !== $received['cache_time'] ) && ( true === ctype_digit( $received['cache_time'] ) && ( intval( $received['cache_time'] ) == $received['cache_time'] ) ) ) {
				$clean['cache_time'] = ( (int) $received['cache_time'] * 60 * 60 );
			}
			else {
				add_settings_error( self::SETTINGS_OPTION, 'cache_time', __( 'Invalid cache time received', self::$name ) . ', ' . __( 'the value has been reset to the default.', self::$name ), 'error' );
			}


			$clean['enable_async'] = ( ( isset( $received['enable_async'] ) && 'true' === $received['enable_async'] ) ? true : false );
			$clean['enable_async_debug'] = ( ( isset( $received['enable_async_debug'] ) && 'true' === $received['enable_async_debug'] ) ? true : false );


			/* Always update the version number to current */
			$clean['version'] = Demo_Quotes_Plugin::VERSION;

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
		public function validate_classnames( $classnames = '' ) {
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




		/**
		 * Display our options page using the Settings API
		 *
		 * Useful functions available to get access to the parameters you used in add_submenu_page():
		 * - $parent_slug: get_admin_page_parent()
		 * - $page_title: get_admin_page_title(), or simply global $title
		 * - $menu_slug: global $plugin_page
		 */
		public function display_options_page() {

			if ( !current_user_can( Demo_Quotes_Plugin::SETTINGS_REQUIRED_CAP ) ) {
				/* TRANSLATORS: no need to translate - standard WP core translation will be used */
				wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
			}

			/* Only needed if our settings page is not under options, otherwise it will automatically display */
			settings_errors( Demo_Quotes_Plugin::SETTINGS_OPTION );

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
		 * Display the General Settings section of our options page
		 */
		public function do_settings_section_include() {

			echo 'Include settings<br />';

/*			echo '
			<fieldset class="options" name="general">
				<table cellspacing="2" cellpadding="5" class="editform form-table">
					<tr>
						<th nowrap valign="top" width="33%">
							<label for="image_size">' . esc_html__( 'Image Size', self::$name ) . '</label>
						</th>
						<td>
							<select name="' . esc_attr( self::SETTINGS_OPTION . '[image_size]' ) . '" id="image_size">';

			foreach ( $this->sizes as $v ) {
				echo '
								<option value="' . esc_attr( $v ) . '" ' . selected( $this->settings['image_size'], $v, false ) . '>' . esc_html( $v . 'x' . $v ) . '</option>';
			}
			unset( $v );

			echo '
							</select>
						</td>
					</tr>
					<tr>' /* @todo maybe change this to radio buttons ? if so, remove th label * / . '
						<th nowrap valign="top" width="33%">
							<label for="image_type">' . esc_html__( 'Image Type', self::$name ) . '</label>
						</th>
						<td>
							<select name="' . esc_attr( self::SETTINGS_OPTION . '[image_type]' ) . '" id="image_type">';

			foreach ( $this->image_types as $v ) {
				echo '
									<option value="' . esc_attr( $v ) . '" ' . selected( $this->settings['image_type'], $v, false ) . '>' . esc_html( $v ) . '</option>';
			}
			unset( $v );

			echo '
							</select>
						</td>
					</tr>
					<tr>' /* @todo maybe change this to radio buttons ? if so, remove th label * / . '
						<th nowrap valign="top" width="33%">
							<label for="leftorright">' . esc_html__( 'Display images on left or right', self::$name ) . '</label>
						</th>
						<td>
							<select name="' . esc_attr( self::SETTINGS_OPTION . '[leftorright]' ) . '" id="leftorright">';

			foreach ( $this->alignments as $k => $v ) {
				echo '
									<option value="' . esc_attr( $k ) . '" ' . selected( $this->settings['leftorright'], $k, false ) . '>' . esc_html( $v ) . '</option>';
			}
			unset( $k, $v );

			echo '
							</select>
						</td>
					</tr>
				</table>
			</fieldset>';
*/		}


		/**
		 * Display the Advanced Settings section of our options page
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
			<div class="dqp-explain">
				 <p>' . __( 'N.B.1: When you deactivate the plugin, your information will always stay in the database untouched.', Demo_Quotes_Plugin::$name ) . '</p>
				 <p>' . __( 'N.B.2: Information not added through this plugin (i.e. tags, posts, pages, attachments etc), will <strong><em>not</em></strong> be affected by the choice you make here.', Demo_Quotes_Plugin::$name ) . '</p>
			</div>
			';

		}


		/**
		 * @param $args
		 */
		public function do_settings_field_text_field( $args ) {
			echo '
				 <input type="text" name="' . esc_attr( $args['name'] ) . '" id="' . esc_attr( $args['label_for'] ) . '" value="' . esc_attr( $GLOBALS['demo_quotes_plugin']->settings[$args['section']][$args['field']] ) . '" />
				 <span class="dqp-explain">' . sprintf( __( 'Type the word %s here to give this plugin permission to delete its data', Demo_Quotes_Plugin::$name ), self::DELETE_KEYWORD ) . '</span>
			';
		}

/*			echo '
			<fieldset class="options advanced-1" name="advanced-1">
				<legend>' . __( 'Enable/Disable classnames?', self::$name ) . '</legend>
				<table width="100%" cellspacing="2" cellpadding="5" class="editform form-table">
					<tr>
						<td><label for="enable_hidden_class"><input type="checkbox" name="' . esc_attr( self::SETTINGS_OPTION . '[enable_hidden_class]' ) . '" id="enable_hidden_class" value="true" ' . checked( $this->settings['enable_hidden_class'], true, false ) . ' /> ' . __( 'Tick this box to have one or more <em>classname(s)</em> that will disable the mime type links (ie: around an image or caption).', self::$name ) . '</label></td>
					</tr>
					<tr>
						<td><label for="hidden_classname">' . esc_html__( 'You can change the classname(s) by editing the field below. If you want to exclude several classnames, separate them with a comma (,).', self::$name ) . '</label></td>
					</tr>
					<tr>
						<td><input type="text" name="' . esc_attr( self::SETTINGS_OPTION . '[hidden_classname]' ) . '" id="hidden_classname" value="' . esc_attr( implode( ', ', $this->settings['hidden_classname'] ) ) . '" /></td>
					</tr>
				</table>
			</fieldset>

			<fieldset class="options advanced-2" name="advanced-2">
				<legend>' . esc_html__( 'Show File Size?', self::$name ) . '</legend>
				<table width="100%" cellspacing="2" cellpadding="5" class="editform form-table">
					<tr>
						<td><label for="show_file_size"><input type="checkbox" name="' . esc_attr( self::SETTINGS_OPTION . '[show_file_size]' ) . '" id="show_file_size" value="true" ' . checked( $this->settings['show_file_size'], true, false ) . ' /> ' . __( 'Display the <em>file size</em> of the attachment/linked file.', self::$name ) . '</label></td>
						<td>
							<label for="precision">' . esc_html__( 'File size rounding precision:', self::$name ) . '
							<input type="text" name="' . esc_attr( self::SETTINGS_OPTION . '[precision]' ) . '" id="precision" value="' . esc_attr( $this->settings['precision'] ) . '" /> ' . esc_html__( 'decimals', self::$name ) . '</label><br />
							<small><em>' . __( 'sizes less than 1kB will always have 0 decimals', self::$name ) . '</em></small>
						</td>
					</tr>
					<tr>
						<td colspan="2">' . __( 'Retrieving the file sizes of (external) files can be slow. If the file sizes of the files you link to do not change very often, you may want to cache the results. This will result in faster page loading for most end-users of your website.', self::$name ) . '</td>
					</tr>
					<tr>
						<td><label for="use_cache"><input type="checkbox" name="' . esc_attr( self::SETTINGS_OPTION . '[use_cache]' ) . '" id="use_cache" value="true" ' . checked( $this->settings['use_cache'], true, false ) . ' /> ' . __( 'Cache retrieved file sizes.', self::$name ) . '</label></td>
						<td>
							<label for="cache_time">' . esc_html__( 'Amount of time to cache retrieved file sizes:', self::$name ) . '
							<input type="text" name="' . esc_attr( self::SETTINGS_OPTION . '[cache_time]' ) . '" id="cache_time" value="' . esc_attr( round( $this->settings['cache_time'] / ( 60 * 60 ), 0 ) ) . '" /> ' . esc_html__( 'hours', self::$name ) . '</label>
						</td>
					</tr>
				</table>
			</fieldset>

			<fieldset class="options advanced-3" name="advanced-3">
				<legend>' . esc_html__( 'Enable Asynchronous Replacement?', self::$name ) . '</legend>
				<table width="100%" cellspacing="2" cellpadding="5" class="editform form-table">
					<tr>
						<td colspan="2">' . esc_html__( 'Some themes or plugins may conflict with this plugin. If you find you are having trouble you can switch on asynchronous replacement which (instead of PHP) uses JavaScript to find your links.', self::$name ) . '</td>
					</tr>
					<tr>
						<td><label for="enable_async"><input type="checkbox" name="' . esc_attr( self::SETTINGS_OPTION . '[enable_async]' ) . '" id="enable_async" value="true" ' . checked( $this->settings['enable_async'], true, false ) . ' /> ' . __( 'Tick box to enable <em>asynchronous replacement</em>.', self::$name ) . '</label></td>
						<td><label for="enable_async_debug"><input type="checkbox" name="' . esc_attr( self::SETTINGS_OPTION . '[enable_async_debug]' ) . '" id="enable_async_debug" value="true" ' . checked( $this->settings['enable_async_debug'], true, false ) . ' /> ' . __( 'Tick box to enable <em>asynchronous debug mode</em>.', self::$name ) . '</label></td>
					</tr>
				</table>
			</fieldset>';
*/


	} // End of class
} // End of class exists wrapper