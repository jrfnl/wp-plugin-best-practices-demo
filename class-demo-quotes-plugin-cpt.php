<?php
/**
 * Custom Post Type.
 *
 * @package WordPress\Plugins\Demo_Quotes_Plugin
 * @subpackage Cpt
 */

// Avoid direct calls to this file.
if ( ! function_exists( 'add_action' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

if ( class_exists( 'Demo_Quotes_Plugin' ) && ! class_exists( 'Demo_Quotes_Plugin_Cpt' ) ) {

	/**
	 * Demo Quotes Custom Post Type.
	 */
	class Demo_Quotes_Plugin_Cpt {

		/**
		 * Post Type Name.
		 *
		 * @var string
		 */
		public static $post_type_name = 'demo_quote';

		/**
		 * Taxonomy Name.
		 *
		 * @var string
		 */
		public static $taxonomy_name = 'demo-quote-people';

		/**
		 * Default post format to use for this Post Type.
		 *
		 * @var string
		 */
		public static $default_post_format = 'quote';

		/**
		 * Default title cut-off length.
		 *
		 * @var string
		 */
		public static $default_post_title_length = 35;

		/**
		 * Whether our post type has successfully been registered.
		 *
		 * @internal Used to avoid double registration if post type is registered early
		 * (like from the upgrade routine).
		 *
		 * @var bool
		 */
		public static $cpt_registered = false;

		/**
		 * Whether our taxonomy has successfully been registered.
		 *
		 * Used to avoid double registration if taxonomy is registered early
		 * (like from the upgrade routine).
		 *
		 * @var bool
		 */
		public static $tax_registered = false;


		/* *** HOOK IN *** */


		/**
		 * Register our post type, taxonomy and link them together.
		 *
		 * @static
		 *
		 * @return void
		 */
		public static function init() {
			/* Register our post type and taxonomy. */
			self::register_post_type();
			self::register_taxonomy();

			/* Filter our post type archive title. */
			add_filter( 'post_type_archive_title', array( __CLASS__, 'post_type_archive_title' ) );

			/* Add our post type to queries. */
			add_filter( 'pre_get_posts', array( __CLASS__, 'filter_pre_get_posts' ) );

			/* Add Taxonomy to post. */
			add_filter( 'the_content', array( __CLASS__, 'filter_content' ) );

		}


		/**
		 * Add actions and filters for just the back-end.
		 *
		 * @static
		 *
		 * @return void
		 */
		public static function admin_init() {
			/* Filter for 'post updated' messages for our custom post type. */
			add_filter( 'post_updated_messages', array( __CLASS__, 'filter_post_updated_messages' ) );

			/* Add help tabs for our custom post type. */
			add_action( 'load-edit.php', array( __CLASS__, 'add_help_tab' ) );
			add_action( 'load-post.php', array( __CLASS__, 'add_help_tab' ) );
			add_action( 'load-post-new.php', array( __CLASS__, 'add_help_tab' ) );

			/* Save our post type specific info when creating or updating a post. */
			add_action( 'save_post', array( __CLASS__, 'save_post' ), 10, 2 );

			/* Add our post type to the Admin Dashboard 'At a Glance' widget (WP3.8+). */
			add_filter( 'dashboard_glance_items', array( __CLASS__, 'add_to_dashboard_at_a_glance' ) );
			add_action( 'admin_head-index.php', array( __CLASS__, 'at_a_glance_custom_icons' ) );

			/* Sortable taxonomy column. */
			add_filter( 'manage_edit-' . self::$post_type_name . '_sortable_columns', array( __CLASS__, 'sortable_columns' ) );

			/* Add taxonomy filter to overview page. */
			add_action( 'restrict_manage_posts', array( __CLASS__, 'restrict_manage_posts' ) );
			add_filter( 'parse_query', array( __CLASS__, 'taxonomy_filter_parse_query' ) );

		}


		/* *** METHODS REGISTERING OUR CPT & TAXONOMY *** */


		/**
		 * Registers our post type.
		 *
		 * @static
		 *
		 * @return void
		 */
		public static function register_post_type() {

			/* Set up the arguments for the post type. */
			$args = array(

				/**
				 * A short description of what your post type is. As far as I know, this isn't used anywhere
				 * in core WordPress. However, themes may choose to display this on post type archives.
				 *
				 * Type: string
				 */
				'description'         => __( 'This is a description for my post type.', 'demo-quotes-plugin' ),

				/**
				 * Whether the post type should be used publicly via the admin or by front-end users. This
				 * argument is sort of a catchall for many of the following arguments. I would focus more
				 * on adjusting them to your liking than this argument.
				 *
				 * Type:    bool
				 * Default: false
				 */
				'public'              => true,

				/**
				 * Whether queries can be performed on the front end as part of parse_request().
				 *
				 * Type:    bool
				 * Default: the value of 'public'.
				 */
				'publicly_queryable'  => true,

				/**
				 * Whether to exclude posts with this post type from front end search results.
				 *
				 * Type:    bool
				 * Default: the value of 'public'.
				 */
				'exclude_from_search' => ( ! Demo_Quotes_Plugin_Option::$current['include']['search'] ),

				/**
				 * Whether individual post type items are available for selection in navigation menus.
				 *
				 * Type:    bool
				 * Default: the value of 'public'.
				 */
				'show_in_nav_menus'   => true,

				/**
				 * Whether to generate a default UI for managing this post type in the admin. You'll have
				 * more control over what's shown in the admin with the other arguments. To build your
				 * own UI, set this to FALSE.
				 *
				 * Type:    bool
				 * Default: the value of 'public'.
				 */
				'show_ui'             => true,

				/**
				 * Whether to show post type in the admin menu. 'show_ui' must be true for this to work.
				 *
				 * Type:    bool
				 * Default: the value of 'show_ui'.
				 */
				'show_in_menu'        => true,

				/**
				 * Whether to make this post type available in the WordPress admin bar. The admin bar adds
				 * a link to add a new post type item.
				 *
				 * Type:    bool
				 * Default: the value of 'show_in_menu'.
				 */
				'show_in_admin_bar'   => true,

				/**
				 * The position in the menu order the post type should appear. 'show_in_menu' must be true
				 * for this to work.
				 *
				 * Type:    int
				 * Default: 25 (= underneath the 'Comments' menu item).
				 */
				'menu_position'       => 20,

				/**
				 * The URI to the icon to use for the admin menu item. There is no header icon argument, so
				 * you'll need to use CSS to add one.
				 *
				 * Type:    string
				 * Default: null, if not set will use the standard 'post' icon.
				 */
				'menu_icon'           => 'dashicons-testimonial',

				/**
				 * Whether the posts of this post type can be exported via the WordPress import/export plugin
				 * or a similar plugin.
				 *
				 * Type:    bool
				 * Default: true
				 */
				'can_export'          => true,

				/**
				 * Whether to delete posts of this type when deleting a user who has written posts.
				 *
				 * Type:    bool
				 * Default: true as long as the post type supports the 'author' field.
				 */
				'delete_with_user'    => false,

				/**
				 * Whether this post type should allow hierarchical (parent/child/grandchild/etc.) posts.
				 *
				 * Type:    bool
				 * Default: false
				 */
				'hierarchical'        => false,

				/**
				 * Whether the post type has an index/archive/root page like the "page for posts" for regular
				 * posts. If set to TRUE, the post type name will be used for the archive slug. You can also
				 * set this to a string to control the exact name of the archive slug.
				 *
				 * Type:    bool|string
				 * Default: false
				 */
				'has_archive'         => true,

				/**
				 * Sets the query_var key for this post type. If set to TRUE, the post type name will be used.
				 * You can also set this to a custom string to control the exact key.
				 *
				 * Type:    bool|string
				 * Default: true (= post type name).
				 */
				'query_var'           => true,

				/**
				 * A string used to build the edit, delete, and read capabilities for posts of this type. You
				 * can use a string or an array (for singular and plural forms). The array is useful if the
				 * plural form can't be made by simply adding an 's' to the end of the word. For example,
				 * array( 'box', 'boxes' ).
				 *
				 * Type:    string|array
				 * Default: 'post'
				 */
				'capability_type'     => 'post',

				/**
				 * Whether WordPress should map the meta capabilities (edit_post, read_post, delete_post) for
				 * you. If set to FALSE, you'll need to roll your own handling of this by filtering the
				 * 'map_meta_cap' hook.
				 *
				 * Type:    bool
				 * Default: false
				 */
				'map_meta_cap'        => true,

				/**
				 * Provides more precise control over the capabilities than the defaults. By default, WordPress
				 * will use the 'capability_type' argument to build these capabilities. More often than not,
				 * this results in many extra capabilities that you probably don't need. The following is how
				 * I set up capabilities for many post types, which only uses three basic capabilities you need
				 * to assign to roles: 'manage_examples', 'edit_examples', 'create_examples'. Each post type
				 * is unique though, so you'll want to adjust it to fit your needs.
				 *
				 * Type:    array
				 * /
				/*
				'capabilities' => array(

					// meta caps (don't assign these to roles)
					'edit_post'              => 'edit_' . self::$post_type_name,
					'read_post'              => 'read_' . self::$post_type_name,
					'delete_post'            => 'delete_' . self::$post_type_name,

					// primitive/meta caps
					'create_posts'           => 'create_' . self::$post_type_name . 's',

					// primitive caps used outside of map_meta_cap()
					'edit_posts'             => 'edit_' . self::$post_type_name . 's',
					'edit_others_posts'      => 'manage_' . self::$post_type_name . 's',
					'publish_posts'          => 'manage_' . self::$post_type_name . 's',
					'read_private_posts'     => 'read',

					// primitive caps used inside of map_meta_cap()
					'read'                   => 'read',
					'delete_posts'           => 'manage_' . self::$post_type_name . 's',
					'delete_private_posts'   => 'manage_' . self::$post_type_name . 's',
					'delete_published_posts' => 'manage_' . self::$post_type_name . 's',
					'delete_others_posts'    => 'manage_' . self::$post_type_name . 's',
					'edit_private_posts'     => 'edit_' . self::$post_type_name . 's',
					'edit_published_posts'   => 'edit_' . self::$post_type_name . 's'
				),
				*/

				/**
				 * How the URL structure should be handled with this post type. You can set this to an
				 * array of specific arguments or true|false. If set to FALSE, it will prevent rewrite
				 * rules from being created.
				 *
				 * Type:    array|bool
				 */
				'rewrite' => array(

					/**
					 * The slug to use for individual posts of this type.
					 * Should be translatable according to the Codex.
					 *
					 * Type:    string
					 * Default: post type name
					 */
					'slug'       => _x( 'demo-quotes', 'Post type slug for use in url', 'demo-quotes-plugin' ),

					/**
					 * Whether to show the $wp_rewrite->front slug in the permalink.
					 *
					 * Type:    bool
					 * Default: true
					 */
					'with_front' => true,

					/**
					 * Whether to allow single post pagination via the <!--nextpage--> quicktag.
					 *
					 * Type:    bool
					 * Default: true
					 */
					'pages'      => false,

					/**
					 * Whether to create pretty links for feeds for this post type.
					 *
					 * Type:    bool
					 * Default: the value of the 'has_archive' argument
					 */
					'feeds'      => true,

					/**
					 * Assign an endpoint mask to this permalink.
					 *
					 * Type:    const
					 * Default: EP_PERMALINK
					 */
					'ep_mask'    => EP_PERMALINK,
				),

				/**
				 * What WordPress features the post type supports. Many arguments are strictly useful on
				 * the edit post screen in the admin. However, this will help other themes and plugins
				 * decide what to do in certain situations. You can pass an array of specific features or
				 * set it to FALSE to prevent any features from being added. You can use
				 * add_post_type_support() to add features or remove_post_type_support() to remove features
				 * later. The default features are 'title' and 'editor'.
				 *
				 * Type:    array|bool
				 * Default: array( 'title', 'editor' )
				 */
				'supports' => array(

					/* Post titles ($post->post_title). */
					// 'title',

					/* Post content ($post->post_content). */
					'editor',

					/* Post excerpt ($post->post_excerpt). */
					// 'excerpt',

					/* Post author ($post->post_author). */
					'author',

					/* Featured images (the user's theme must support 'post-thumbnails'). */
					'thumbnail',

					/* Displays comments meta box. If set, comments (any type) are allowed for the post. */
					'comments',

					/* Displays meta box to send trackbacks from the edit post screen. */
					// 'trackbacks',

					/* Displays the Custom Fields meta box. Post meta is supported regardless. */
					'custom-fields',

					/* Displays the Revisions meta box. If set, stores post revisions in the database. */
					'revisions',

					/* Displays the Attributes meta box with a parent selector and menu_order input box. */
					// 'page-attributes',

					/* Displays the Format meta box and allows post formats to be used with the posts. */
					'post-formats',
				),

				/**
				 * Provide a callback function that will be called when setting up the meta boxes
				 * for the edit form. Do remove_meta_box() and add_meta_box() calls in the callback.
				 *
				 * [Optional]
				 * Type:    callback
				 */
				'register_meta_box_cb'	=> array( __CLASS__, 'register_meta_box_cb' ),

				/**
				 * An array of registered taxonomies like category or post_tag that will be used
				 * with this post type.
				 * This can be used in lieu of calling register_taxonomy_for_object_type() directly.
				 * Custom taxonomies still need to be registered with register_taxonomy().
				 *
				 * [Optional]
				 * Type:    array
				 */
				'taxonomies'			=> array(
					'post_tag',
					self::$taxonomy_name,
				),

				/**
				 * Labels used when displaying the posts in the admin and sometimes on the front end. These
				 * labels do not cover post updated, error, and related messages. You'll need to filter the
				 * 'post_updated_messages' hook to customize those.
				 *
				 * Type:    array
				 */
				'labels' => array(
					'name'               => __( 'Demo Quotes',				'demo-quotes-plugin' ),
					'singular_name'      => __( 'Demo Quote',				'demo-quotes-plugin' ),
					'menu_name'          => __( 'Demo Quotes',				'demo-quotes-plugin' ),
					'name_admin_bar'     => __( 'Demo Quotes',				'demo-quotes-plugin' ),
					'add_new'            => __( 'Add New',					'demo-quotes-plugin' ),
					'add_new_item'       => __( 'Add New Quote',			'demo-quotes-plugin' ),
					'edit_item'          => __( 'Edit Quote',				'demo-quotes-plugin' ),
					'new_item'           => __( 'New Quote',				'demo-quotes-plugin' ),
					'view_item'          => __( 'View Quote',				'demo-quotes-plugin' ),
					'search_items'       => __( 'Search Quotes',			'demo-quotes-plugin' ),
					'not_found'          => __( 'No quotes found',			'demo-quotes-plugin' ),
					'not_found_in_trash' => __( 'No quotes found in trash',	'demo-quotes-plugin' ),
					'all_items'          => __( 'All Quotes',				'demo-quotes-plugin' ),

					/* Labels for hierarchical post types only. */
					// 'parent_item'        => __( 'Parent Quote',             'demo-quotes-plugin' ),
					// 'parent_item_colon'  => __( 'Parent Quote:',            'demo-quotes-plugin' ),

					/* Custom archive label. Must filter 'post_type_archive_title' to use. */
					'archive_title'      => __( 'Quotes Archive',			'demo-quotes-plugin' ),
				),
			);

			/* Register the post type. */
			if ( false === self::$cpt_registered ) {
				$pt = register_post_type(
					self::$post_type_name, // Post type name. Max of 20 characters. Uppercase and spaces not allowed.
					$args // Arguments for post type.
				);
				if ( true !== is_wp_error( $pt ) ) {
					self::$cpt_registered = true;
				}
			}
		}


		/**
		 * Register the custom taxonomy.
		 *
		 * @return void
		 */
		public static function register_taxonomy() {

			/* Set up the arguments for the post type. */
			$args = array(

				/**
				 * Should this taxonomy be exposed in the admin UI ?
				 *
				 * Type:    bool
				 * Default: true
				 */
				'public'				=> true,

				/**
				 * Whether to generate a default UI for managing this taxonomy.
				 *
				 * Type:    bool
				 * Default: the value of 'public'
				 */
				'show_ui'				=> true,

				/**
				 * Whether individual taxonomy items are available for selection in navigation menus.
				 *
				 * Type:    bool
				 * Default: the value of 'public'
				 */
				'show_in_nav_menus'   	=> true,

				/**
				 * Whether to allow the Tag Cloud widget to use this taxonomy.
				 *
				 * Type:    bool
				 * Default: the value of 'show_ui'
				 */
				'show_tagcloud'   		=> true,

				/**
				 * Whether to allow automatic creation of taxonomy columns on associated post-types.
				 * (Available since 3.5).
				 *
				 * Type:    bool
				 * Default: false
				 */
				'show_admin_column'		=> true,

				/**
				 * Is this taxonomy hierarchical (has descendants) like categories or not hierarchical like tags ?
				 *
				 * Type:    bool
				 * Default: false
				 */
				'hierarchical'			=> true,

				/**
				 * A function name that will be called when the count of an associated $object_type,
				 * such as post, is updated. Works much like a hook.
				 *
				 * Note: If you want to ensure that your custom taxonomy behaves like a tag, you must
				 * add the option 'update_count_callback' => '_update_post_term_count'. Not doing so will
				 * result in multiple comma-separated items added at once being saved as a single value,
				 * not as separate values.
				 * IMPORTANT: see additional notes on this argument in the codex!
				 */
				'update_count_callback'	=> '',

				/**
				 * Sets the query_var key for this taxonomy. If set to TRUE, the taxonomy name will be used.
				 * You can also set this to a custom string to control the exact key.
				 *
				 * Type:    bool|string
				 * Default: true (=taxonomy name)
				 */
				'query_var'				=> true,

				/**
				 * Whether this taxonomy should remember the order in which terms are added to objects.
				 *
				 * Type:    bool
				 * Default: None
				 */
				'sort'					=> true,

				/**
				 * Control the capabilities for this taxonomy.
				 *
				 * Type:    array
				 * Default: None
				 * /
				/*
				'capabilities' => array(

					'manage_terms'	=> 'manage_' . self::$taxonomy_name,
					'edit_terms'	=> 'manage_' . self::$taxonomy_name,
					'delete_terms'	=> 'manage_' . self::$taxonomy_name,
					'assign_terms'	=> 'edit_posts',
				),
				*/

				/**
				 * How the URL structure should be handled with this taxonomy. You can set this to an
				 * array of specific arguments or true|false. If set to FALSE, it will prevent rewrite
				 * rules from being created.
				 *
				 * Type:    array|bool
				 */
				'rewrite' => array(

					/**
					 * The slug to use for individual taxonomy items of this type.
					 *
					 * Type:    string
					 * Default: taxonomy name
					 */
					'slug'			=> _x( 'quotes-by', 'Taxonomy slug for use in url', 'demo-quotes-plugin' ),

					/**
					 * Whether to show the $wp_rewrite->front slug in the permalink.
					 *
					 * Type:    bool
					 * Default: true
					 */
					'with_front'	=> true,

					/**
					 * Whether to allow hierarchical urls (implemented in Version 3.1).
					 *
					 * Type:    bool
					 * Default: false
					 */
					'hierarchical'	=> false,

					/**
					 * Assign an endpoint mask to this permalink.
					 *
					 * Type:    const
					 * Default: EP_NONE
					 */
					'ep_mask'   	=> EP_NONE,
				),

				/**
				 * Labels used when displaying the taxonomy in the admin and sometimes on the front end. These
				 * labels do not cover updated, error, and related messages. You'll need to filter the
				 * 'post_updated_messages' hook to customize those.
				 *
				 * Type:    array
				 */
				'labels' => array(
					'name' 				=> _x( 'People', 'taxonomy general name',	'demo-quotes-plugin' ),
					'singular_name' 	=> _x( 'Person', 'taxonomy singular name',	'demo-quotes-plugin' ),
					// 'menu_name'	// This string is the name to give menu items. Defaults to value of name.

					'all_items' 		=> __( 'All People',						'demo-quotes-plugin' ),
					'edit_item' 		=> __( 'Edit Person',						'demo-quotes-plugin' ),
					'view_item' 		=> __( 'View Person',						'demo-quotes-plugin' ),
					'update_item' 		=> __( 'Update Person',						'demo-quotes-plugin' ),
					'add_new_item' 		=> __( 'Add New Person',					'demo-quotes-plugin' ),
					'new_item_name' 	=> __( 'New Name of Person',				'demo-quotes-plugin' ),

					'search_items' 		=> __( 'Search People',						'demo-quotes-plugin' ),

					/* Only used for hierarchical taxonomies. */
					'parent_item' 		=> __( 'Parent',							'demo-quotes-plugin' ),
					'parent_item_colon' => __( 'Parent:',							'demo-quotes-plugin' ),

					/* Only used for non-hierarchical taxonomies (tag-like). * /
					/*
					'popular_items' 				=> __( 'Popular People',		'demo-quotes-plugin' ),
					'separate_items_with_commas'	=> __( 'Separate People with commas',	'demo-quotes-plugin' ),
					'add_or_remove_items' 			=> __( 'Add or remove people',	'demo-quotes-plugin' ),
					'choose_from_most_used' 		=> __( 'Choose from the most used people',	'demo-quotes-plugin' ),
					'not_found'  					=> __( 'No people found.',		'demo-quotes-plugin' ), // (3.6+)
					*/
				),
			);

			/* Register the taxonomy. */

			if ( false === self::$tax_registered ) {
				$tax = register_taxonomy(
					self::$taxonomy_name, // Taxonomy internal name. Max 32 characters. Uppercase and spaces not allowed.
					array(
						self::$post_type_name,
					), // Post types to register this taxonomy for.
					$args // Arguments for taxonomy.
				);
				if ( true !== is_wp_error( $tax ) ) {
					self::$tax_registered = true;
				}
			}
		}

		// get_the_term_list( $post->ID, 'people', 'People: ', ', ', '' );


		/* *** METHODS CUSTOMIZING OUR CPT ADMIN PAGES *** */


		/**
		 * Filter 'post updated' message so as to display our custom post type name.
		 *
		 * @static
		 *
		 * @param array $messages Message strings.
		 *
		 * @return array
		 */
		public static function filter_post_updated_messages( $messages ) {

			$messages[ self::$post_type_name ] = array(
				0 => '', // Unused. Messages start at index 1.
				1 => sprintf( __( 'Quote updated. <a href="%s">View quote</a>', 'demo-quotes-plugin' ), esc_url( get_permalink( $GLOBALS['post_ID'] ) ) ),
				2 => esc_html__( 'Custom field updated.', 'demo-quotes-plugin' ),
				3 => esc_html__( 'Custom field deleted.', 'demo-quotes-plugin' ),
				4 => esc_html__( 'Quote updated.', 'demo-quotes-plugin' ),
				/* TRANSLATORS: %s: date and time of the revision. */
				5 => isset( $_GET['revision'] ) ? sprintf( esc_html__( 'Quote restored to revision from %s', 'demo-quotes-plugin' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
				6 => sprintf( __( 'Quote published. <a href="%s">View quote</a>', 'demo-quotes-plugin' ), esc_url( get_permalink( $GLOBALS['post_ID'] ) ) ),
				7 => esc_html__( 'Quote saved.', 'demo-quotes-plugin' ),
				8 => sprintf( __( 'Quote submitted. <a target="_blank" href="%s">Preview quote</a>', 'demo-quotes-plugin' ), esc_url( add_query_arg( 'preview', 'true', get_permalink( $GLOBALS['post_ID'] ) ) ) ),
				9 => sprintf(
					__( 'Quote scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview quote</a>', 'demo-quotes-plugin' ),
					// TRANSLATORS: Publish box date format, see http://php.net/date.
					date_i18n( __( 'M j, Y @ G:i' ), strtotime( $GLOBALS['post']->post_date ) ),
					esc_url( get_permalink( $GLOBALS['post_ID'] ) )
				),
				10 => sprintf( __( 'Quote draft updated. <a target="_blank" href="%s">Preview quote</a>', 'demo-quotes-plugin' ), esc_url( add_query_arg( 'preview', 'true', get_permalink( $GLOBALS['post_ID'] ) ) ) ),
			);

			return $messages;
		}


		/**
		 * Adds contextual help tabs to the custom post type pages.
		 *
		 * @static
		 *
		 * @return void
		 */
		public static function add_help_tab() {

			$screen = get_current_screen();

			if ( property_exists( $screen, 'post_type' ) && $screen->post_type === self::$post_type_name ) {
				$screen->add_help_tab(
					array(
						'id'	  => Demo_Quotes_Plugin::$name . '-main', // This should be unique for the screen.
						'title'   => __( 'Demo Quotes', 'demo-quotes-plugin' ),
						'callback' => array( 'Demo_Quotes_Plugin', 'get_helptext' ),
					)
				);

				/* Extra tab just for the add/edit screen. */
				if ( property_exists( $screen, 'base' ) && 'post' === $screen->base  ) {
					$screen->add_help_tab(
						array(
							'id'	  => Demo_Quotes_Plugin::$name . '-add', // This should be unique for the screen.
							'title'   => __( 'How to...', 'demo-quotes-plugin' ),
							'callback' => array( 'Demo_Quotes_Plugin', 'get_helptext' ),
						)
					);
				}

				$screen->add_help_tab(
					array(
						'id'	  => Demo_Quotes_Plugin::$name . '-advanced', // This should be unique for the screen.
						'title'   => __( 'Advanced Settings', 'demo-quotes-plugin' ),
						'callback' => array( 'Demo_Quotes_Plugin', 'get_helptext' ),
					)
				);
				$screen->add_help_tab(
					array(
						'id'	  => Demo_Quotes_Plugin::$name . '-extras', // This should be unique for the screen.
						'title'   => __( 'Extras', 'demo-quotes-plugin' ),
						'callback' => array( 'Demo_Quotes_Plugin', 'get_helptext' ),
					)
				);

				$screen->set_help_sidebar( Demo_Quotes_Plugin::get_help_sidebar() );
			}
		}


		/**
		 * Adjust which meta-boxes display on the edit page for our custom post type.
		 *
		 * @static
		 *
		 * @return void
		 */
		public static function register_meta_box_cb() {
			/* Remove the post format metabox from the screen as we'll be setting this ourselves. */
			remove_meta_box( 'formatdiv', self::$post_type_name, 'side' );

			/* Remove the title and slug meta-boxes from the screen as we'll be setting this ourselves. */
			//remove_meta_box( 'titlediv', self::$post_type_name, 'normal' );
			remove_meta_box( 'slugdiv', self::$post_type_name, 'normal' );

		}


		/* *** METHODS CUSTOMIZING THE SAVING OF OUR CPT *** */

		/**
		 * Save post custom post type specific info when a post is saved.
		 *
		 * @static
		 *
		 * @param int    $post_id The ID of the post.
		 * @param object $post    Post object.
		 *
		 * @return void
		 */
		public static function save_post( $post_id, $post ) {

			/* Make sure this is not an auto-save and that this is a save for our post type. */
			if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || self::$post_type_name !== $post->post_type ) {
				return;
			}

			/* Update the post title and post slug. */
			self::update_post_title_and_name( $post_id, $post );

			/* Make sure we save to the actual post id, not to a revision. */
			$parent_id = wp_is_post_revision( $post_id );
			if ( false !== $parent_id ) {
				$post_id = $parent_id;
			}

			/**
			 * Set the post format to quote.
			 * @api	string	$post_format	Allows changing of the default post format used for the
			 *								demo quotes post type.
			 */
			$post_format = apply_filters( 'demo_quotes_post_format', self::$default_post_format );
			set_post_format( $post_id, $post_format );
		}


		/**
		 * Update the post title and slug on each publishing save.
		 *
		 * @static
		 *
		 * @param int    $post_id The ID of the post.
		 * @param object $post    Post object.
		 *
		 * @return void
		 */
		public static function update_post_title_and_name( $post_id, $post ) {
			/**
			 * Is this a save for our post type and not a revision ?
			 */
			if ( $post->post_type === self::$post_type_name && ! wp_is_post_revision( $post_id ) ) {
				/**
				 * (Re-)Set the title based on the actual content.
				 *
				 * Cuts the title to the part before the last space (so as not to have half-words in the title)
				 * within the allowed length parameters.
				 * Strips shortcodes, html, line breaks etc in a utf-8 safe manner.
				 */
				$title = $post->post_title;
				if ( '' !== $post->post_content ) {
					$title = strip_shortcodes( $post->post_content );
					$title = trim( preg_replace( "`[\n\r\t ]+`", ' ', $title ), ' ' );
					/**
					 * Filter to change the length of the generated title for the demo quote.
					 *
					 * @api	int	$post_title_length Post title length.
					 */
					$title_length = apply_filters( 'demo_quotes_plugin_title_length', self::$default_post_title_length );
					$title = wp_html_excerpt( $title, (int) $title_length );
					$title = mb_substr( $title, 0, mb_strrpos( $title, ' ' ) ) . '&hellip;';
					$title = sanitize_text_field( $title );
				}

				/**
				 * Set the post name based on the post title if there isn't a slug or the slug is numerical.
				 * Should only run on first publishing save of a post of our custom post type
				 * (as after that there should already be a non-numeric slug).
				 *
				 * Uses the WP internal way for generating an unique slug.
				 */
				$post_name = $post->post_name;
				if ( ( 'publish' === $post->post_status && '' !== $title ) && ( '' === $post_name || true === ctype_digit( (string) $post_name ) ) ) {
					$post_name = trim( str_replace( '&hellip;', '', $title ) );
					$post_name = wp_unique_post_slug( $post_name, $post_id, $post->post_status, $post->post_type, $post->post_parent );

					$post_name = sanitize_title( $post_name );
				}

				/**
				 * Check if an update is needed.
				 * Unhook our save_post method, update the post and re-hook the method (avoid infinite loops ).
				 */
				if ( $title !== $post->post_title || $post_name !== $post->post_name ) {
					remove_action( 'save_post', array( __CLASS__, 'save_post' ), 10, 2 );

					$update = array(
						'ID'			=> $post_id,
						'post_title'	=> $title,
						'post_name'		=> $post_name,
					);
					wp_update_post( $update );

					add_action( 'save_post', array( __CLASS__, 'save_post' ), 10, 2 );
				}
			}
		}


		/**
		 * Make custom taxonomy column sortable.
		 *
		 * @param array	$columns Columns.
		 *
		 * @return array
		 */
		public static function sortable_columns( $columns ) {
			$columns[ 'taxonomy-' . self::$taxonomy_name ] = 'taxonomy-' . self::$taxonomy_name;
			return $columns;
		}


		/**
		 * Add custom taxonomy filter drop-down to cpt overview page.
		 *
		 * @return void
		 */
		public static function restrict_manage_posts() {

			if ( $GLOBALS['typenow'] !== self::$post_type_name ) {
				return;
			}

			$taxonomy = get_taxonomy( self::$taxonomy_name );

			$args = array(
				/* TRANSLATORS: %s: Custom Post Type Name Plural. */
				'show_option_all'	=> sprintf( __( 'Show All %s', 'demo-quotes-plugin' ), $taxonomy->labels->name ),
				'taxonomy'			=> self::$taxonomy_name,
				'name'				=> self::$taxonomy_name,
				'orderby'			=> 'name',
				'selected'			=> ( isset( $_GET[ self::$taxonomy_name ] ) ? sanitize_text_field( $_GET[ self::$taxonomy_name ] ) : '' ),
				'hierarchical'		=> $taxonomy->hierarchical,
				'show_count'		=> true,
				'hide_empty'		=> true,
			);
			wp_dropdown_categories( $args );
		}


		/**
		 * Filter the cpt overview page based on taxonomy drop-down.
		 *
		 * @param object $query WP query object.
		 *
		 * @return void
		 */
		public static function taxonomy_filter_parse_query( $query ) {

			if ( 'edit.php' === $GLOBALS['pagenow'] ) {
				$filters = get_object_taxonomies( $GLOBALS['typenow'] );
				foreach ( $filters as $tax ) {
					$var = &$query->query_vars[ $tax ];
					if ( isset( $var ) ) {
						$term = get_term_by( 'id', $var, $tax );
						if ( is_object( $term ) ) {
							$var = $term->slug;
						}
					}
				}
			}
		}


		/* *** METHODS INTERACTING WITH OTHER ADMIN PAGES *** */

		/**
		 * Add our post type and taxonomy to the Admin Dashboard 'At a glance' widget (WP 3.8+).
		 *
		 * We need to abuse a filter as the filter does not allow us to style our items and we do
		 * of course want to do so.
		 *
		 * @param array $items Dashboard items.
		 *
		 * @return array
		 */
		public static function add_to_dashboard_at_a_glance( $items ) {
			$my_items = self::get_dashboard_items();

			foreach ( $my_items as $item ) {
				if ( true === $item['link'] ) {
					echo '<li class="', esc_attr( $item['class'] ), '"><a href="', esc_url( $item['url'] ), '">', esc_html( $item['nr'], ' ', $item['text'] ), '</a></li>';
				}
				else {
					echo '<li class="', esc_attr( $item['class'] ), '">', esc_html( $item['nr'] . ' ' . $item['text'] ), '</li>';
				}
			}

			return $items;
		}


		/**
		 * Add Some CSS to "At a Glance" Widget.
		 *
		 * @return void
		 */
		public static function at_a_glance_custom_icons() {
		    echo '<style type="text/css">
		        #dashboard_right_now .demo-quote-count a:before {content:"\f473"!important}
		        #dashboard_right_now .people-count a:before {content:"\f110"!important}
		        </style>';
		}


		/**
		 * Retrieve the information on the items we want to add to the Dashboard Right Now/At a glance widget
		 *
		 * @return array
		 */
		private static function get_dashboard_items() {
			$to_add = array();

			/* Custom Post Type */
			$count                  = wp_count_posts( self::$post_type_name, 'readable' );
			$to_add['cpt']['nr']    = number_format_i18n( $count->publish );
			$to_add['cpt']['text']  = _n( 'Demo Quote', 'Demo Quotes', $count->publish, 'demo-quotes-plugin' );
			$to_add['cpt']['link']  = false;
			$to_add['cpt']['url']   = '';
			$to_add['cpt']['class'] = 'demo-quote-count';

			if ( current_user_can( 'edit_posts' ) ) { // Or use edit_CPT capability if defined.
				$to_add['cpt']['link'] = true;
				$to_add['cpt']['url']  = admin_url( 'edit.php?post_type=' . self::$post_type_name );
			}

			/* Taxonomy. */
			$count                  = wp_count_terms( self::$taxonomy_name );
			$to_add['tax']['nr']    = number_format_i18n( $count );
			$to_add['tax']['text']  = _n( 'Person', 'People', $count, 'demo-quotes-plugin' );
			$to_add['tax']['link']  = false;
			$to_add['tax']['url']   = '';
			$to_add['tax']['class'] = 'people-count';

			if ( current_user_can( 'manage_categories' ) ) { // Or use edit_CT capability if defined.
				$to_add['tax']['link'] = true;
				$to_add['tax']['url']  = admin_url( 'edit-tags.php?taxonomy=' . self::$taxonomy_name );
			}

			return $to_add;
		}


		/* *** METHODS INFLUENCING FRONT END DISPLAY *** */

		/**
		 * Adjust Post Archive Title for our custom post type.
		 * Will only work if the theme respects the Post Archive Title.
		 *
		 * @param string $title Post Archive Title.
		 *
		 * @return string
		 */
		public static function post_type_archive_title( $title ) {
			$post_type_obj = get_queried_object();
			if ( $post_type_obj->name === self::$post_type_name ) {
				$title = $post_type_obj->labels->archive_title;
			}
			return $title;
		}


		/**
		 * Add author link below quote.
		 *
		 * @param string $content Post Content.
		 *
		 * @return string
		 */
		public static function filter_content( $content ) {

			if ( $GLOBALS['post']->post_type !== self::$post_type_name ) {
				return $content;
			}

			$content = $content . Demo_Quotes_Plugin::get_quoted_by( $GLOBALS['post']->ID, false );
			return $content;
		}


		/**
		 * Make sure our post type is included in queries.
		 *
		 * @static
		 *
		 * @param object $query	WP_Query object.
		 *
		 * @return object
		 */
		public static function filter_pre_get_posts( $query ) {

			/*if( isset( $query->query_vars['suppress_filters'] ) && $query->query_vars['suppress_filters'] === true ) {
				return $query;
			}*/

			$include = false;
			$options = Demo_Quotes_Plugin_Option::$current;

			$front_end = false;
			if ( is_admin() === false || ( is_admin() === true && ( defined( 'DOING_AJAX' ) && DOING_AJAX === true ) ) ) {
				$front_end = true;
			}

			/**
			 * Determine based on requested page & user settings whether to include our cpt in the query or not.
			 */
			if ( is_feed() ) {
				if ( true === $options['include']['feed'] ) {
					$include = true;
				}
			}
			else if ( true === $front_end && true === $options['include']['all'] ) {
				$include = true;
			}
			else if ( true === $front_end && $query->is_main_query() ) {
				/* Main blog page. */
				if ( true === $options['include']['home'] && is_home() ) {
					$include = true;
				}
				/* Archives except post type specific archives. */
				else if ( is_archive() && ! is_post_type_archive() ) {
					if ( true === $options['include']['archives'] ) {
						$include = true;
					}
					else if ( true === $options['include']['tag'] && is_tag() ) {
						$include = true;
					}
					/* Will generally not be applicable as we didn't add the category taxonomy to our post type. */
					else if ( true === $options['include']['category'] && is_category() ) {
						$include = true;
					}
					else if ( true === $options['include']['tax'] && is_tax() ) {
						/* Include for all possible taxonomies. */
						$include = true;
					}
					else if ( true === $options['include']['author'] && is_author() ) {
						$include = true;
					}
					else if ( true === $options['include']['date'] && is_date() ) {
						$include = true;
					}
				}
			}

			//$include = !is_admin(); // temp!!!!

			/* Add our cpt to the query. */
			if ( true === $include ) {
				$post_type = $query->get( 'post_type' );

				/* Don't do anything if the query does not look at post_type or if it already includes all post types. */
				if ( is_string( $post_type ) ) {
					$tax_query = $query->get( 'tax_query' );
					if ( 'any' === $post_type || ( '' === $post_type && is_array( $tax_query ) ) ) {
						return $query;
					}

					if ( '' === $post_type ) {
						$post_type = array( 'post' );
					}
					else {
						$post_type = array( $post_type );
					}
				}

				/* Add our post type to the query. */
				if ( is_array( $post_type ) && ! in_array( self::$post_type_name, $post_type ) ) {
					$post_type[] = self::$post_type_name;
					$query->set( 'post_type', $post_type );
					return $query;
				}
			}
			return $query; // Always return the query.
		}
	} /* End of class. */

} /* End of class exists wrapper. */
