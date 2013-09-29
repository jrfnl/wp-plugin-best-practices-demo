<?php

// Avoid direct calls to this file
if ( !function_exists( 'add_action' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

if ( class_exists( 'Demo_Quotes_Plugin' ) && ! class_exists( 'Demo_Quotes_Plugin_Cpt' ) ) {
	/**
	 * @package WordPress\Plugins\Demo_Quotes_Plugin
	 * @subpackage CustomPostTypes
	 * @version 1.0
	 * @link https://github.com/jrfnl/wp-plugin-best-practices-demo WP Plugin Best Practices Demo
	 *
	 * @copyright 2013 Juliette Reinders Folmer
	 * @license http://creativecommons.org/licenses/GPL/3.0/ GNU General Public License, version 3
	 */
	class Demo_Quotes_Plugin_Cpt {
		
		public static $post_type_name = 'demo_quote';

		public static $post_type_slug = 'demo-quotes';
		
//		public static $post_type_capability_type = 'demo_quote';
		
		public static $default_post_format = 'quote';
		
		public static $default_post_title_length = 35;

		/**
		 * Registers post types needed by the plugin.
		 *
		 * @static
		 * @access public
		 * @return void
		 */
		public static function register_post_types() {

			/* Set up the arguments for the post type. */
			$args = array(
		
				/**
				 * A short description of what your post type is. As far as I know, this isn't used anywhere 
				 * in core WordPress.  However, themes may choose to display this on post type archives. 
				 */
				'description'         => __( 'This is a description for my post type.', Demo_Quotes_Plugin::$name ), // string
		
				/** 
				 * Whether the post type should be used publicly via the admin or by front-end users.  This 
				 * argument is sort of a catchall for many of the following arguments.  I would focus more 
				 * on adjusting them to your liking than this argument.
				 */
				'public'              => true, // bool (default is FALSE)
		
				/**
				 * Whether queries can be performed on the front end as part of parse_request(). 
				 */
				'publicly_queryable'  => true, // bool (defaults to 'public').
		
				/**
				 * Whether to exclude posts with this post type from front end search results.
				 */
				'exclude_from_search' => false, // bool (defaults to 'public')
		
				/**
				 * Whether individual post type items are available for selection in navigation menus. 
				 */
				'show_in_nav_menus'   => true, // bool (defaults to 'public')
		
				/**
				 * Whether to generate a default UI for managing this post type in the admin. You'll have 
				 * more control over what's shown in the admin with the other arguments.  To build your 
				 * own UI, set this to FALSE.
				 */
				'show_ui'             => true, // bool (defaults to 'public')
		
				/**
				 * Whether to show post type in the admin menu. 'show_ui' must be true for this to work. 
				 */
				'show_in_menu'        => true, // bool (defaults to 'show_ui')
		
				/**
				 * Whether to make this post type available in the WordPress admin bar. The admin bar adds 
				 * a link to add a new post type item.
				 */
				'show_in_admin_bar'   => true, // bool (defaults to 'show_in_menu')
		
				/**
				 * The position in the menu order the post type should appear. 'show_in_menu' must be true 
				 * for this to work.
				 */
				'menu_position'       => 20, // int (defaults to 25 - below comments)
		
				/**
				 * The URI to the icon to use for the admin menu item. There is no header icon argument, so 
				 * you'll need to use CSS to add one.
				 */
//				'menu_icon'           => null, // string (defaults to use the post icon)
				'menu_icon'           => plugins_url( 'images/demo-quotes-icon-16.png', __FILE__ ),
		
				/**
				 * Whether the posts of this post type can be exported via the WordPress import/export plugin 
				 * or a similar plugin. 
				 */
				'can_export'          => true, // bool (defaults to TRUE)
		
				/**
				 * Whether to delete posts of this type when deleting a user who has written posts. 
				 */
				'delete_with_user'    => false, // bool (defaults to TRUE if the post type supports 'author')
		
				/**
				 * Whether this post type should allow hierarchical (parent/child/grandchild/etc.) posts. 
				 */
				'hierarchical'        => false, // bool (defaults to FALSE)
		
				/** 
				 * Whether the post type has an index/archive/root page like the "page for posts" for regular 
				 * posts. If set to TRUE, the post type name will be used for the archive slug.  You can also 
				 * set this to a string to control the exact name of the archive slug.
				 */
				'has_archive'         => self::$post_type_slug, // bool|string (defaults to FALSE)
		
				/**
				 * Sets the query_var key for this post type. If set to TRUE, the post type name will be used. 
				 * You can also set this to a custom string to control the exact key.
				 */
				'query_var'           => true, // bool|string (defaults to TRUE - post type name)
		
				/**
				 * A string used to build the edit, delete, and read capabilities for posts of this type. You 
				 * can use a string or an array (for singular and plural forms).  The array is useful if the 
				 * plural form can't be made by simply adding an 's' to the end of the word.  For example, 
				 * array( 'box', 'boxes' ).
				 */
				'capability_type'     => 'post', // string|array (defaults to 'post')
		
				/**
				 * Whether WordPress should map the meta capabilities (edit_post, read_post, delete_post) for 
				 * you.  If set to FALSE, you'll need to roll your own handling of this by filtering the 
				 * 'map_meta_cap' hook.
				 */
				'map_meta_cap'        => true, // bool (defaults to FALSE)
		
				/**
				 * Provides more precise control over the capabilities than the defaults.  By default, WordPress 
				 * will use the 'capability_type' argument to build these capabilities.  More often than not, 
				 * this results in many extra capabilities that you probably don't need.  The following is how 
				 * I set up capabilities for many post types, which only uses three basic capabilities you need 
				 * to assign to roles: 'manage_examples', 'edit_examples', 'create_examples'.  Each post type 
				 * is unique though, so you'll want to adjust it to fit your needs.
				 */
/*				'capabilities' => array(
		
					// meta caps (don't assign these to roles)
					'edit_post'              => 'edit_' . self::$post_type_capability_type,
					'read_post'              => 'read_' . self::$post_type_capability_type,
					'delete_post'            => 'delete_' . self::$post_type_capability_type,
		
					// primitive/meta caps
					'create_posts'           => 'create_' . self::$post_type_capability_type . 's',
		
					// primitive caps used outside of map_meta_cap()
					'edit_posts'             => 'edit_' . self::$post_type_capability_type . 's',
					'edit_others_posts'      => 'manage_' . self::$post_type_capability_type . 's',
					'publish_posts'          => 'manage_' . self::$post_type_capability_type . 's',
					'read_private_posts'     => 'read',
		
					// primitive caps used inside of map_meta_cap()
					'read'                   => 'read',
					'delete_posts'           => 'manage_' . self::$post_type_capability_type . 's',
					'delete_private_posts'   => 'manage_' . self::$post_type_capability_type . 's',
					'delete_published_posts' => 'manage_' . self::$post_type_capability_type . 's',
					'delete_others_posts'    => 'manage_' . self::$post_type_capability_type . 's',
					'edit_private_posts'     => 'edit_' . self::$post_type_capability_type . 's',
					'edit_published_posts'   => 'edit_' . self::$post_type_capability_type . 's'
				),
*/
				/** 
				 * How the URL structure should be handled with this post type.  You can set this to an 
				 * array of specific arguments or true|false.  If set to FALSE, it will prevent rewrite 
				 * rules from being created.
				 */
				'rewrite' => array(
		
					/* The slug to use for individual posts of this type. */
//					'slug'       => __( self::$post_type_slug, Demo_Quotes_Plugin::$name ), // string (defaults to the post type name) - Codex says 'should be translatable'
					'slug'       => self::$post_type_slug, // string (defaults to the post type name)
		
					/* Whether to show the $wp_rewrite->front slug in the permalink. */
					'with_front' => true, // bool (defaults to TRUE)
		
					/* Whether to allow single post pagination via the <!--nextpage--> quicktag. */
					'pages'      => false, // bool (defaults to TRUE)
		
					/* Whether to create feeds for this post type. */
					'feeds'      => true, // bool (defaults to the 'has_archive' argument)
		
					/* Assign an endpoint mask to this permalink. */
					'ep_mask'    => EP_PERMALINK, // const (defaults to EP_PERMALINK)
				),
		
				/**
				 * What WordPress features the post type supports.  Many arguments are strictly useful on 
				 * the edit post screen in the admin.  However, this will help other themes and plugins 
				 * decide what to do in certain situations.  You can pass an array of specific features or 
				 * set it to FALSE to prevent any features from being added.  You can use 
				 * add_post_type_support() to add features or remove_post_type_support() to remove features 
				 * later.  The default features are 'title' and 'editor'.
				 */
				'supports' => array(
		
					/* Post titles ($post->post_title). */
//					'title',
		
					/* Post content ($post->post_content). */
					'editor',
		
					/* Post excerpt ($post->post_excerpt). */
//					'excerpt',

					/* Post author ($post->post_author). */
					'author',

					/* Featured images (the user's theme must support 'post-thumbnails'). */
					'thumbnail',

					/* Displays comments meta box.  If set, comments (any type) are allowed for the post. */
					'comments',

					/* Displays meta box to send trackbacks from the edit post screen. */
					'trackbacks',

					/* Displays the Custom Fields meta box. Post meta is supported regardless. */
					'custom-fields',

					/* Displays the Revisions meta box. If set, stores post revisions in the database. */
					'revisions',

					/* Displays the Attributes meta box with a parent selector and menu_order input box. */
//					'page-attributes',

					/* Displays the Format meta box and allows post formats to be used with the posts. */
					'post-formats',
				),
				
				/**
				 * Provide a callback function that will be called when setting up the meta boxes
				 * for the edit form. Do remove_meta_box() and add_meta_box() calls in the callback.
				 */
				'register_meta_box_cb'	=>	array( 'Demo_Quotes_Plugin_Cpt', 'register_meta_box_cb' ), // Optional, expects string callback
				
				/**
				 * An array of registered taxonomies like category or post_tag that will be used
				 * with this post type.
				 * This can be used in lieu of calling register_taxonomy_for_object_type() directly.
				 * Custom taxonomies still need to be registered with register_taxonomy().
				 */
				'taxonomies'			=>	array(
					'post_tag',
				), // Optional


				/**
				 * Labels used when displaying the posts in the admin and sometimes on the front end.  These 
				 * labels do not cover post updated, error, and related messages.  You'll need to filter the 
				 * 'post_updated_messages' hook to customize those.
				 */
				'labels' => array(
					'name'               => __( 'Demo Quotes',				Demo_Quotes_Plugin::$name ),
					'singular_name'      => __( 'Demo Quote',				Demo_Quotes_Plugin::$name ),
					'menu_name'          => __( 'Demo Quotes',				Demo_Quotes_Plugin::$name ),
					'name_admin_bar'     => __( 'Demo Quotes',				Demo_Quotes_Plugin::$name ),
					'add_new'            => __( 'Add New',					Demo_Quotes_Plugin::$name ),
					'add_new_item'       => __( 'Add New Quote',			Demo_Quotes_Plugin::$name ),
					'edit_item'          => __( 'Edit Quote',				Demo_Quotes_Plugin::$name ),
					'new_item'           => __( 'New Quote',				Demo_Quotes_Plugin::$name ),
					'view_item'          => __( 'View Quote',				Demo_Quotes_Plugin::$name ),
					'search_items'       => __( 'Search Quotes',			Demo_Quotes_Plugin::$name ),
					'not_found'          => __( 'No quotes found',			Demo_Quotes_Plugin::$name ),
					'not_found_in_trash' => __( 'No quotes found in trash',	Demo_Quotes_Plugin::$name ),
					'all_items'          => __( 'All Quotes',				Demo_Quotes_Plugin::$name ),
		
					/* Labels for hierarchical post types only. */
					//'parent_item'        => __( 'Parent Quote',             Demo_Quotes_Plugin::$name ),
					//'parent_item_colon'  => __( 'Parent Quote:',            Demo_Quotes_Plugin::$name ),
		
					/* Custom archive label.  Must filter 'post_type_archive_title' to use. */
					'archive_title'      => __( 'Quotes',					Demo_Quotes_Plugin::$name ),
				)
			);
		
			/* Register the post type. */
			register_post_type(
				self::$post_type_name, // Post type name. Max of 20 characters. Uppercase and spaces not allowed.
				$args      // Arguments for post type.
			);
		}
		
		
		/**
		 * Filter 'post updated' message so as to display our custom post type name
		 *
		 * @static
		 * @param	array	$messages
		 * @return	array
		 */
		public static function filter_post_updated_messages( $messages ) {
			global $post, $post_ID;

			$messages[self::$post_type_name] = array(
				0 => '', // Unused. Messages start at index 1.
				1 => sprintf(
					__( 'Quote updated. <a href="%s">View quote</a>', Demo_Quotes_Plugin::$name ),
					esc_url( get_permalink( $post_ID ) )
				),
				2 => esc_html__( 'Custom field updated.', Demo_Quotes_Plugin::$name ),
				3 => esc_html__( 'Custom field deleted.', Demo_Quotes_Plugin::$name ),
				4 => esc_html__( 'Quote updated.', Demo_Quotes_Plugin::$name ),
				/* translators: %s: date and time of the revision */
				5 => isset( $_GET['revision'] ) ? sprintf(
					esc_html__( 'Quote restored to revision from %s', Demo_Quotes_Plugin::$name ),
						wp_post_revision_title( (int) $_GET['revision'], false )
					) : false,
				6 => sprintf(
					__( 'Quote published. <a href="%s">View quote</a>', Demo_Quotes_Plugin::$name ),
					esc_url( get_permalink( $post_ID ) )
				),
				7 => esc_html__( 'Quote saved.', Demo_Quotes_Plugin::$name ),
				8 => sprintf(
					__( 'Quote submitted. <a target="_blank" href="%s">Preview quote</a>', Demo_Quotes_Plugin::$name ),
					esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) )
				),
				9 => sprintf(
					__( 'Quote scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview quote</a>', Demo_Quotes_Plugin::$name ),
					// translators: Publish box date format, see http://php.net/date
					date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ), esc_url( get_permalink( $post_ID ) )
				),
				10 => sprintf(
					__( 'Quote draft updated. <a target="_blank" href="%s">Preview quote</a>', Demo_Quotes_Plugin::$name ),
					esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) )
				),
			);
		
			return $messages;
		}

		/**
		 * Adds contextual help tab to the custom post type page
		 *
		 * Would be nice to be able to use 'self::get_helptext' as the callback, unfortunately that's PHP5.3+
		 */
		public static function add_help_tab() {

			$screen = get_current_screen();

			if ( property_exists( $screen, 'post_type' ) && $screen->post_type === self::$post_type_name ) {
				$screen->add_help_tab(
					array(
						'id'	  => Demo_Quotes_Plugin::$name . '-main', // This should be unique for the screen.
						'title'   => __( 'Demo Quotes', Demo_Quotes_Plugin::$name ),
						'callback' => array( 'Demo_Quotes_Plugin_Cpt', 'get_helptext' ),
					)
				);

				/* Extra tab just for the add/edit screen */
				if ( property_exists( $screen, 'base' ) && $screen->base === 'post' ) {
					$screen->add_help_tab(
						array(
							'id'	  => Demo_Quotes_Plugin::$name . '-add', // This should be unique for the screen.
							'title'   => __( 'How to...', Demo_Quotes_Plugin::$name ),
							'callback' => array( 'Demo_Quotes_Plugin_Cpt', 'get_helptext' ),
						)
					);
				}

				$screen->add_help_tab(
					array(
						'id'	  => Demo_Quotes_Plugin::$name . '-advanced', // This should be unique for the screen.
						'title'   => __( 'Advanced Settings', Demo_Quotes_Plugin::$name ),
						'callback' => array( 'Demo_Quotes_Plugin_Cpt', 'get_helptext' ),
					)
				);
				$screen->add_help_tab(
					array(
						'id'	  => Demo_Quotes_Plugin::$name . '-extras', // This should be unique for the screen.
						'title'   => __( 'Extras', Demo_Quotes_Plugin::$name ),
						'callback' => array( 'Demo_Quotes_Plugin_Cpt', 'get_helptext' ),
					)
				);

				$screen->set_help_sidebar( self::get_help_sidebar() );
			}
		}



		/**
		 * Function containing the helptext strings
		 *
		 * Of course in a real plugin, we'd have proper helpful texts here
		 *
		 * @param 	object	$screen
		 * @param 			$tab
		 * @return  string  help text
		 */
		public static function get_helptext( $screen, $tab ) {

			switch ( $tab ) {
				case Demo_Quotes_Plugin::$name . '-main' :
					echo '
								<p>' . esc_html__( 'Here comes a helpful help text ;-)', Demo_Quotes_Plugin::$name ) . '</p>
								<p>' . esc_html__( 'And some more help.', Demo_Quotes_Plugin::$name ) . '</p>';
					return true;

				case Demo_Quotes_Plugin::$name . '-add' :
					echo '
								<p>' . esc_html__( 'Some specific information about editing a quote', Demo_Quotes_Plugin::$name ) . '</p>
								<p>' . esc_html__( 'And some more help.', Demo_Quotes_Plugin::$name ) . '</p>';
					return true;

				case Demo_Quotes_Plugin::$name . '-advanced' :
					echo '
								<p>' . esc_html__( 'Some information about advanced features if we create any.', Demo_Quotes_Plugin::$name ) . '</p>';
					return true;

				case Demo_Quotes_Plugin::$name . '-extras' :
					echo '
								<p>' . esc_html__( 'And here we may say something on extra\'s we add to the post type', Demo_Quotes_Plugin::$name ) . '</p>';
					return true;

				default:
					return false;
			}
		}


		/**
		 * Generate the links for the help sidebar
		 *
		 * Of course in a real plugin, we'd have proper links here
		 *
		 * @return string
		 */
		public static function get_help_sidebar() {
			return '
				   <p><strong>' . /* TRANSLATORS: no need to translate - standard WP core translation will be used */ __( 'For more information:' ) . '</strong></p>
				   <p>
						<a href="http://wordpress.org/extend/plugins/" target="_blank">' . __( 'Official plugin page (if there would be one)', Demo_Quotes_Plugin::$name ) . '</a> |
						<a href="#" target="_blank">' . __( 'FAQ', Demo_Quotes_Plugin::$name ) . '</a> |
						<a href="#" target="_blank">' . __( 'Changelog', Demo_Quotes_Plugin::$name ) . '</a> |
						<a href="https://github.com/jrfnl/wp-plugin-best-practices-demo/issues" target="_blank">' . __( 'Report issues', Demo_Quotes_Plugin::$name ) . '</a>
					</p>
				   <p><a href="https://github.com/jrfnl/wp-plugin-best-practices-demo" target="_blank">' . __( 'Github repository', Demo_Quotes_Plugin::$name ) . '</a></p>
				   <p>' . sprintf( __( 'Created by %sAdvies en zo', Demo_Quotes_Plugin::$name ), '<a href="http://adviesenzo.nl/" target="_blank">' ) . '</a></p>
			';
		}


		public static function register_meta_box_cb() {
			/* Remove the post format metabox from the screen as we'll be setting this ourselves */
			remove_meta_box( 'formatdiv', self::$post_type_name, 'side' );
			
			/* Remove the title and slug metaboxes from the screen as we'll be setting this ourselves */
			remove_meta_box( 'titlediv', self::$post_type_name, 'normal' );
			remove_meta_box( 'slugdiv', self::$post_type_name, 'normal' );

		}



		/**
		 * Save post custom post type specific info when a post is saved.
		 *
		 * @param	int		$post_id The ID of the post.
		 * @param	object	$post object
		 */
		public static function save_post( $post_id, $post ) {
//pr_var( $post );
			/* Make sure this is not an auto-save and that this is a save for our post type */
			if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || self::$post_type_name !== $post->post_type ){
				return;
			}

			/* Update the post title and post slug */
			self::update_post_title_and_name( $post_id, $post );


			/* Make sure we save to the actual post id, not to a revision */
			$parent_id = wp_is_post_revision( $post_id );
			if( $parent_id !== false ) {
				$post_id = $parent_id;
			}

		    /**
			 * Set the post format to quote.
			 * @api	string	$post_format	Allows changing of the default post format used for the
			 *								demo quotes post type
			 */
		    $post_format = apply_filters( 'demo_quotes_post_format', self::$default_post_format );
		    set_post_format( $post_id, $post_format );


		    // - Update the post's metadata.
		/*    if ( isset( $_REQUEST['book_author'] ) ) {
		        update_post_meta( $post_id, 'book_author', sanitize_text_field( $_REQUEST['book_author'] ) );
		    }
		*/
		}
		
		
		public static function update_post_title_and_name( $post_id, $post ) {
			/**
			 * Is this a save for our post type and not a revision ?
			 */
			if( $post->post_type === self::$post_type_name && ! wp_is_post_revision( $post_id ) ) {
				/**
				 * (Re-)Set the title based on the actual content
				 *
				 * Cuts the title to the part before the last space (so as not to have half-words in the title)
				 * within the allowed length parameters.
				 * Strips shortcodes, html, line breaks etc in a utf-8 safe manner.
				 */
				$title = $post->post_title;
				if ( $post->post_content !== '' ) {
					$title = strip_shortcodes( $post->post_content );
					$title = trim( preg_replace( "`[\n\r\t ]+`", ' ', $title ), ' ' );
					/**
					 * @api	int	$post_title_length	Filter to change the length of the generated title
					 *								for the demo quote
					 */
					$title_length = apply_filters( 'demo_quotes_plugin_title_length', self::$default_post_title_length );
					$title = wp_html_excerpt( $title, (int) $title_length );
					$title = mb_substr( $title, 0, mb_strrpos ( $title, ' ' ) ) . '&hellip;';
					$title = sanitize_text_field( $title );
				}

				/**
				 * Set the post name based on the post title if there isn't a slug or the slug is numerical
				 * Should only run on first publishing save of a post of our custom post type
				 * (as after that there should already be a non-numeric slug)
				 *
				 * Uses the WP internal way for generating an unique slug
				 */
				$post_name = $post->post_name;
				if( ( $post->post_status === 'publish' && $title !== '' ) && ( $post_name === '' || ctype_digit( (string) $post_name ) === true ) ) {
					$post_name = trim( str_replace( '&hellip;', '', $title ) );
					$post_name = wp_unique_post_slug( $post_name, $post_id, $post->post_status, $post->post_type, $post->post_parent );

					$post_name = sanitize_title( $post_name );
				}

				/**
				 * Check if an update is needed
				 * Unhook our save_post method, update the post and re-hook the method (avoid infinite loops )
				 */
				if( $title !== $post->post_title || $post_name !== $post->post_name ) {
					remove_action( 'save_post', array( 'Demo_Quotes_Plugin_Cpt', 'save_post' ), 10, 2 );
			
					$update = array(
						'ID'			=> $post_id,
						'post_title'	=> $title,
						'post_name'		=> $post_name,
					);
					wp_update_post( $update );
			
					add_action( 'save_post', array( 'Demo_Quotes_Plugin_Cpt', 'save_post' ), 10, 2 );
				}
			}
		}

		
		/**
		 * Make sure our post type is included in queries
		 *
		 * @param	object	$query	WP_Query object
		 * @return	object
		 */
		public static function filter_pre_get_posts( $query ) {
			
			$include = false;
/*			$options = get_option();
			
			$frond_end = false;
			if ( is_admin() === false || ( is_admin() === true && ( defined( 'DOING_AJAX' ) && DOING_AJAX === true ) ) ) {
				$front_end = true;
			}

			if ( $frond_end === true && $options['include']['all'] === true ) {
				$include = true;
			}
			else if ( $options['include']['feed'] === true && is_feed() ) {
				$include = true;
			}
			else if ( $frond_end === true && $query->is_main_query() ) {
				/* Main blog page * /
				if ( $options['include']['home'] === true && is_home() ) {
					$include = true;
				}
				/* Archives except post type specific archives * /
				else if ( is_archive() && ! is_post_type_archive() ) ) {
					if ( $options['include']['archives'] === true ) {
						$include = true;
					}
					else if ( is_tax() ) {
						if ( $options['include']['tax'] === true ) {
							/* include for all possible taxonomies * /
							$include = true;
						}
						else if ( $options['include']['tag'] === true && is_tag() ) {
							$include = true;
						}
						/* Will never be applicable as we didn't add the category taxonomy to our post type * /
						else if ( $options['include']['category'] === true && is_category() ) {
							$include = true;
						}
					}
					else if ( $options['include']['author'] === true && is_author() ) {
						$include = true;
					}
					else if ( $options['include']['date'] === true && is_date() ) {
						$include = true;
					}
				}
				// Check for is_search not needed as is part of the cpt registration
			}
*/

			$include = !is_admin(); // temp!!!!

			if ( $include === true ) {
//pr_var( $query->query, 'the current query' );
				$post_type = $query->get( 'post_type' );

				/* Don't do anything if the query does not look at post_type or if it already includes all post types */
				if ( is_string( $post_type ) ) {
					$tax_query = $query->get( 'tax_query' );
					if ( $post_type === 'any' || ( $post_type === '' && is_array( $tax_query ) ) ) {
						return $query;
					}

					if( $post_type === '' ) {
						$post_type = array( 'post' );
					}
					else {
						$post_type = array( $post_type );
					}
				}

				/* Add our post type to the query */
				if ( is_array( $post_type ) && ! in_array( self::$post_type_name, $post_type ) ) {
					$post_type[] = self::$post_type_name;
//pr_var( $post_type, '$post_type AFTER addition of ours' );
					$query->set( 'post_type', $post_type );
					return $query;
				}
			}
			return $query; // always return the query
		}
	} // End of class
} // End of class exists wrapper