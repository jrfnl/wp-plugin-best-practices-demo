<?php
/**
 * Admin Settings Page.
 *
 * @package WordPress\Plugins\Demo_Quotes_Plugin
 * @subpackage Settings_Page
 */

// Avoid direct calls to this file.
if ( ! function_exists( 'add_action' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

if ( class_exists( 'Demo_Quotes_Plugin' ) && ! class_exists( 'Demo_Quotes_Plugin_Settings_Page' ) ) {

	/**
	 * Demo Quotes Admin Settings Page.
	 */
	class Demo_Quotes_Plugin_Settings_Page {

		/* *** DEFINE CLASS CONSTANTS *** */


		/* *** DEFINE CLASS PROPERTIES *** */

		/**
		 * Parent page to hook our settings page under.
		 *
		 * @var string
		 */
		public $parent_page = 'edit.php?post_type=%s';

		/**
		 * Menu slug for our settings page.
		 *
		 * @var string
		 */
		public $menu_slug = '%s-settings';

		/**
		 * Unique prefix for use in class names and such.
		 *
		 * @var string
		 */
		public $setting_prefix = 'dqp';

		/**
		 * Array of option form sections.
		 * Will be set by set_properties() as the section (and field) labels need translating.
		 *
		 * @var array
		 *
		 * @usedby display_options_page()
		 */
		public $form_sections = array();


		/* *** Properties Holding Various Parts of the Class' State *** */

		/**
		 * Settings page registration hook suffix.
		 *
		 * @var string
		 */
		public $hook;


		/**
		 * Constructor. Runs on admin_menu hook.
		 */
		public function __construct() {

			/* Translate a number of strings. */
			$this->set_properties();

			/* Add the options page. */
			$this->add_submenu_page();

			/* Add option page related actions. */
			add_action( 'admin_init', array( $this, 'admin_init' ) );
		}


		/**
		 * Fill some property arrays with translated strings.
		 * Enrich some others.
		 *
		 * @return void
		 */
		public function set_properties() {

			$this->form_sections = array(
				'include'	=> array(
					'title'			=> __( 'Website integration:',     'demo-quotes-plugin' ),
					'field_label'	=> __( 'Show the Demo Quotes on:', 'demo-quotes-plugin' ),

					/*
					 * For this section, the fields are not defined as plain fields as we want more control
					 * over the presentation.
					 * We'll add these ourselves via the section callback rather than let WP
					 * add the fields via the fields callback.
					 */
					'section_fields_def'	=> array(
						'frontend'		=> array(
							'title'			=> __( 'Front-end', 'demo-quotes-plugin' ),
							'fields'		=> array(
								'all'			=> array(
									'label'			=> __( 'Include Demo Quotes in all front-end queries ?', 'demo-quotes-plugin' ),
									'explain'		=> __( 'This means that the demo quotes will also show up in, for instance, \'Recent Posts\' widgets and the like.', 'demo-quotes-plugin' ),
									'parents'		=> false,
								),
								'home'			=> array(
									'label'			=> __( 'Show Demo Quotes on the main blog page ?', 'demo-quotes-plugin' ),
									'parents'		=> array( 'all' ),
								),
								'archives'		=> array(
									'label'			=> __( 'Show Demo Quotes on all archive pages ?', 'demo-quotes-plugin' ),
									'parents'		=> array( 'all' ),
								),
								'tag'			=> array(
									'label'			=> __( 'Show Demo Quotes on tag archive pages ?', 'demo-quotes-plugin' ),
									'parents'		=> array( 'all', 'archives' ),
								),
								'category'		=> array(
									'label'			=> __( 'Show Demo Quotes on category archive pages ?', 'demo-quotes-plugin' ),
									'explain'		=> __( 'As the category taxonomy is disabled for demo quotes, this will have no effect. Unless, of course, you enable categories for demo quotes. (link to FAQ)', 'demo-quotes-plugin' ),
									'parents'		=> array( 'all', 'archives' ),
								),
								'tax'			=> array(
									'label'			=> __( 'Show Demo Quotes on custom taxonomy archive pages ?', 'demo-quotes-plugin' ),
									'parents'		=> array( 'all', 'archives' ),
								),
								'author'		=> array(
									'label'			=> __( 'Show Demo Quotes on author archive pages ?', 'demo-quotes-plugin' ),
									'explain'		=> __( 'This is unrelated to the people taxonomy. We mean ... link to user\'s own page ...', 'demo-quotes-plugin' ),
									'parents'		=> array( 'all', 'archives' ),
								),
								'date'			=> array(
									'label'			=> __( 'Show Demo Quotes on date based archive pages ?', 'demo-quotes-plugin' ),
									'parents'		=> array( 'all', 'archives' ),
								),
							),
						),
						'frontend_misc'		=> array(
							'title'			=> __( 'Front-end miscellaneous', 'demo-quotes-plugin' ),
							'fields'		=> array(
								'feed'			=> array(
									'label'			=> __( 'Include Demo Quotes in the normal RSS feed ?', 'demo-quotes-plugin' ),
								),
								'search'		=> array(
									'label'			=> __( 'Include Demo Quotes in the results of user searches ?', 'demo-quotes-plugin' ),
								),
							),
						),
					),
				),
				'uninstall'	=> array(
					'title'		=> __( 'Uninstall Settings', 'demo-quotes-plugin' ),
					'fields'	=> array(
						'delete_posts'		=> array(
							'title'		=> __( 'Delete all demo quote posts when uninstalling ?', 'demo-quotes-plugin' ),
							'callback'	=> 'do_settings_field_text_field',
						),
						'delete_taxonomy'	=> array(
							'title'		=> __( 'Delete all entries in the people taxonomy when uninstalling ?', 'demo-quotes-plugin' ),
							'callback'	=> 'do_settings_field_text_field',
						),
					),

				),
			);

			$this->parent_page = sprintf( $this->parent_page, Demo_Quotes_Plugin_Cpt::$post_type_name );
			$this->menu_slug   = sprintf( $this->menu_slug, Demo_Quotes_Plugin::$name );
		}


		/**
		 * Register the settings page for all users that have the required capability.
		 *
		 * @return void
		 */
		public function add_submenu_page() {

			$this->hook = add_submenu_page(
				$this->parent_page, // Parent slug.
				__( 'Demo Quotes Plugin Settings', 'demo-quotes-plugin' ), // Page title.
				__( 'Settings', 'demo-quotes-plugin' ), // Menu title.
				Demo_Quotes_Plugin_Option::REQUIRED_CAP, // Capability.
				$this->menu_slug, /* menu slug */
				array( $this, 'display_options_page' ) // Function for subpanel.
			);
		}


		/**
		 * Set up our settings page.
		 *
		 * @return void
		 */
		public function admin_init() {

			/* Don't do anything if user does not have the required capability. */
			if ( false === is_admin() || false === current_user_can( Demo_Quotes_Plugin_Option::REQUIRED_CAP ) ) {
				return;
			}

			/* Register the settings sections and their callbacks. */
			foreach ( $this->form_sections as $section => $section_info ) {
				add_settings_section(
					$this->setting_prefix . '-' . $section . '-settings', // ID.
					$section_info['title'], // Title.
					array( $this, 'do_settings_section_' . $section ), // Callback for this section.
					$this->menu_slug // Page menu_slug.
				);

				/* Register settings fields for the section */
				if ( isset( $section_info['fields'] ) && ( is_array( $section_info['fields'] ) && $section_info['fields'] !== array() ) ) {
					foreach ( $section_info['fields'] as $field => $field_def ) {
						add_settings_field(
							$this->setting_prefix . '_' . $section . '_' . $field, // Field id.
							$field_def['title'], // Field title.
							array( $this, $field_def['callback'] ), // Callback for this field.
							$this->menu_slug, // Page menu slug.
							$this->setting_prefix . '-' . $section . '-settings', // Section id.
							array(
								'label_for'	=> $this->setting_prefix . '_' . $section . '_' . $field,
								'name'		=> Demo_Quotes_Plugin_Option::NAME . '[' . $section . '][' . $field . ']',
								'section'	=> $section,
								'field'		=> $field,
							) // Array of arguments which will be passed to the callback.
						);
					}
				}
			}

			/* Add settings link on plugin page. */
			add_filter( 'plugin_action_links_' . Demo_Quotes_Plugin::$basename, array( $this, 'add_settings_link' ), 10, 2 );

			/* Add help tabs for our settings page. */
			add_action( 'load-' . $this->hook, array( $this, 'add_help_tab' ) );
		}


		/**
		 * Add settings link to plugin row.
		 *
		 * @param array  $links Current links for the current plugin.
		 * @param string $file  The file for the current plugin.
		 *
		 * @return array
		 */
		public function add_settings_link( $links, $file ) {

			if ( Demo_Quotes_Plugin::$basename === $file && current_user_can( Demo_Quotes_Plugin_Option::REQUIRED_CAP ) ) {
				$links[] = '<a href="' . esc_url( $this->plugin_options_url() ) . '" alt="' . esc_attr__( 'Demo Quotes Plugin Settings', 'demo-quotes-plugin' ) . '">' . esc_html__( 'Settings', 'demo-quotes-plugin' ) . '</a>';
			}
			return $links;
		}


		/**
		 * Return absolute URL of options page.
		 *
		 * @return string
		 */
		public function plugin_options_url() {
			return add_query_arg( 'page', $this->menu_slug, admin_url( $this->parent_page ) );
		}


		/**
		 * Adds contextual help tab to the plugin settings page.
		 *
		 * @return void
		 */
		public function add_help_tab() {

			$screen = get_current_screen();

			if ( property_exists( $screen, 'base' ) && $screen->base === $this->hook ) {
				$screen->add_help_tab(
					array(
						'id'	  => Demo_Quotes_Plugin::$name . '-settings', // This should be unique for the screen.
						'title'   => __( 'Settings', 'demo-quotes-plugin' ),
						'callback' => array( 'Demo_Quotes_Plugin', 'get_helptext' ),
					)
				);
				$screen->add_help_tab(
					array(
						'id'	  => Demo_Quotes_Plugin::$name . '-main', // This should be unique for the screen.
						'title'   => __( 'About', 'demo-quotes-plugin' ),
						'callback' => array( 'Demo_Quotes_Plugin', 'get_helptext' ),
					)
				);

				$screen->set_help_sidebar( Demo_Quotes_Plugin::get_help_sidebar() );
			}
		}


		/* *** SETTINGS PAGE DISPLAY METHODS *** */

		/**
		 * Display our options page using the Settings API.
		 *
		 * Useful functions available to get access to the parameters you used in add_submenu_page():
		 * - $parent_slug: get_admin_page_parent()
		 * - $page_title: get_admin_page_title(), or simply global $title
		 * - $menu_slug: global $plugin_page
		 *
		 * @return void
		 */
		public function display_options_page() {

			if ( ! current_user_can( Demo_Quotes_Plugin_Option::REQUIRED_CAP ) ) {
				/* TRANSLATORS: no need to translate - standard WP core translation will be used. */
				wp_die( esc_html__( 'You do not have sufficient permissions to access this page.' ) );
			}

			/**
			 * Display the updated/error messages.
			 * Only needed if our settings page is not under options, otherwise it will automatically be included.
			 * @see settings_errors()
			 */
			include_once( ABSPATH . 'wp-admin/options-head.php' );

			/* Display the settings page. */
			echo '
		<div class="wrap">';

			echo '
		<h2>' . wp_kses_post( get_admin_page_title() ) . '</h2>
		<form action="' . esc_url( admin_url( 'options.php' ) ) . '" method="post" accept-charset="' . esc_attr( get_bloginfo( 'charset' ) ) . '">';

			settings_fields( Demo_Quotes_Plugin_Option::$settings_group );
			do_settings_sections( $this->menu_slug );
			/* @api Allow other plugins to add to our settings page. */
			do_action( 'demo_quotes_settings_page' );
			submit_button();

			echo '
		</form>';

			/* Add our current settings array to the page for debugging purposes. */
			if ( WP_DEBUG === true || defined( 'DQP_DEBUG' ) && DQP_DEBUG === true ) {
				echo '
		<div id="poststuff">
		<div id="' . esc_attr( $this->setting_prefix ) . '-debug-info" class="postbox">

			<h3 class="hndle"><span>', esc_html__( 'Debug Information', 'demo-quotes-plugin' ), '</span></h3>
			<div class="inside">';
				if ( ! extension_loaded( 'xdebug' ) ) {
					echo '<pre>';
				}

				var_dump( Demo_Quotes_Plugin_Option::$current );

				if ( ! extension_loaded( 'xdebug' ) ) {
					echo '</pre>';
				}

				echo '
			</div>
		</div>
		</div>';
			}

			echo '
		</div>';
		}


		/**
		 * Display the Include Settings section of our options page.
		 *
		 * Note: If you want more complex fields than what you can accomplish with add_settings_field() while still
		 * generating valid HTML, you can 'abuse' the settings_section callback to generate the form fields
		 * for the section.
		 *
		 * @return void
		 */
		public function do_settings_section_include() {

			$section = 'include';

			echo '
			<table class="form-table">
			<tbody>
				<tr valign="top">
					<th scope="row">' . esc_html( $this->form_sections[ $section ]['field_label'] ) . '</th>
					<td>
						<fieldset class="' . esc_attr( 'options ' . $this->setting_prefix . '-' . $section ) . '" name="' . esc_attr( $this->setting_prefix . '-' . $section ) . '">';

			foreach ( $this->form_sections[ $section ]['section_fields_def'] as $group => $fieldset ) {
				if ( is_array( $fieldset['fields'] ) && $fieldset['fields'] !== array() ) {
					echo '
						<h4>' . esc_html( $fieldset['title'] ) . '</h4>
						<div class="' . esc_attr( $this->setting_prefix . '-' . $section . '-group ' . $this->setting_prefix . '-' . $section . '-group-' . $group ) . '">';

					foreach ( $fieldset['fields'] as $field => $field_def ) {
						$args = array(
							'name'		=> Demo_Quotes_Plugin_Option::NAME . '[' . $section . '][' . $field . ']',
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
		 * Prefix a value (for use with array_map).
		 *
		 * @access	private
		 *
		 * @param string $value String to add prefix to.
		 *
		 * @return string
		 */
		private function class_prefix( $value ) {
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
			<div class="', esc_attr( $this->setting_prefix . '-explain' ), '">
				<p>', esc_html__( 'Here you can determine what happens with the information you added to your website with this plugin in case you would decide to uninstall the plugin.', 'demo-quotes-plugin' ), '</p>
				<p>', wp_kses_post( __( 'Generally it is considered good practice to <em>clean up</em> when uninstalling a plugin. This means in practice that all data added to the database through this plugin should be deleted.', 'demo-quotes-plugin' ) ), '</p>
				<p>', esc_html__( 'This also means that if - at a later point in time - you would decide to re-install the plugin, all your previously entered data will be gone.', 'demo-quotes-plugin' ), '</p>
				<p>', wp_kses_post( __( 'So, rather than just going ahead and deleting everything, I believe it\'s up to <strong>you</strong> to decide what happens to your data.', 'demo-quotes-plugin' ) ), '</p>
				<p>', esc_html(
					sprintf(
						/* TRANSLATORS: %s: Delete keyword. */
						__( 'If you leave the below boxes empty, nothing will happen to your data when you uninstall the plugin. However, if you type the word %s in any of the boxes, that particular data will be deleted.', 'demo-quotes-plugin' ),
						Demo_Quotes_Plugin_Option::DELETE_KEYWORD
					)
				), '</p>
				<p>', wp_kses_post( __( '<em>Make sure you make no spelling mistakes!</em>', 'demo-quotes-plugin' ) ), '</p>
			</div>
			<div class="', esc_attr( $this->setting_prefix . '-explain important' ), '">
				<p>', esc_html__( 'N.B.1: When you deactivate the plugin, your information will always stay in the database untouched.', 'demo-quotes-plugin' ), '</p>
				<p>', wp_kses_post( __( 'N.B.2: Information not added through this plugin (i.e. tags, posts, pages, attachments etc), will <strong><em>not</em></strong> be affected by the choice you make here.', 'demo-quotes-plugin' ) ), '</p>
			</div>
			';
		}


		/**
		 * Generate a text form field.
		 *
		 * @param array $args Text field parameters.
		 *
		 * @return void
		 */
		public function do_settings_field_text_field( $args ) {
			echo '
				<input type="text" name="', esc_attr( $args['name'] ), '" id="', esc_attr( $args['label_for'] ), '" value="', esc_attr( Demo_Quotes_Plugin_Option::$current[ $args['section'] ][ $args['field'] ] ), '" autocomplete="off" />
				<span class="', esc_attr( $this->setting_prefix . '-explain' ), '">',
				esc_html(
					sprintf(
						/* TRANSLATORS: %s: Delete keyword. */
						__( 'Type the word %s here to give this plugin permission to delete its data.', 'demo-quotes-plugin' ),
						Demo_Quotes_Plugin_Option::DELETE_KEYWORD
					)
				), '</span>
			';
		}


		/**
		 * Generate a checkbox form field.
		 *
		 * @param array	$args Checkbox field parameters.
		 *
		 * @return void
		 */
		public function do_settings_field_checkbox_field( $args ) {

			$checked = checked( true, Demo_Quotes_Plugin_Option::$current[ $args['section'] ][ $args['field'] ], false );
			echo '
				 <input type="checkbox" name="' . esc_attr( $args['name'] ) . '" id="' . esc_attr( $args['label_for'] ) . '" value="on" ' . $checked . '/>';

			if ( ( isset( $args['label'] ) && $args['label'] !== '' ) && isset( $args['id'] ) && $args['id'] !== '' ){
				echo '<label for="' . esc_attr( $args['id'] ) . '"> ' . esc_html( $args['label'] ) . '</label>';
			}

			if ( isset( $args['explain'] ) && $args['explain'] !== '' ) {
				echo '<br />
				<span class="' . esc_attr( $this->setting_prefix . '-explain' ) . '">' . wp_kses_post( $args['explain'] ) . '</span>';
			}
		}
	} /* End of class. */

} /* End of class exists wrapper. */
