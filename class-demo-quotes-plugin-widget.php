<?php

// Avoid direct calls to this file
if ( !function_exists( 'add_action' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

if ( class_exists( 'Demo_Quotes_Plugin' ) && ( class_exists( 'WP_Widget' ) && ! class_exists( 'Demo_Quotes_Plugin_Widget' ) ) ) {
	/**
	 * @package WordPress\Plugins\Demo_Quotes_Plugin
	 * @subpackage Widget
	 * @version 1.0
	 * @link https://github.com/jrfnl/wp-plugin-best-practices-demo WP Plugin Best Practices Demo
	 *
	 * @copyright 2013 Juliette Reinders Folmer
	 * @license http://creativecommons.org/licenses/GPL/3.0/ GNU General Public License, version 3
	 */
	class Demo_Quotes_Plugin_Widget extends WP_Widget {


		/**
		 * @const string	Version number when scripts where last changed
		 * @todo check whether these are needed or if the main constants can be used
		 */
		const DQPW_SCRIPTS_VERSION = '1.0';

		/**
		 * @const string	Version number when styles where last changed
		 * @todo check whether these are needed or if the main constants can be used
		 */
		const DQPW_STYLES_VERSION = '1.0';

		/**
		 * @const   string  Screen base of the widgets page (for admin scripts/styles)
		 */
		const DQPW_SCREEN_BASE = 'widgets';
		
		
		const DQPW_NAME = 'demo_quotes_widget';


		/**
		 * @var		array	Widget default settings
		 */
		public $dqpw_defaults = array(
			'title'			=>	null, // will be set to localized string via dqpw_set_properties()
			'async_next'	=>	false,
		);



		/**
		 * Register widget with WordPress
		 */
		public function __construct() {

			$widget_ops = array(
				'classname'		=> self::DQPW_NAME,
				'description'	=> __( 'Shows a (random) quote from the demo quotes post type.', Demo_Quotes_Plugin::$name ),
			);

			parent::__construct(
				self::DQPW_NAME, // Base ID
				__( 'Demo Quotes Widget', Demo_Quotes_Plugin::$name ), // Name
				$widget_ops // Option arguments
			);

			add_action( 'wp_enqueue_scripts', array( $this, 'dqpw_wp_enqueue_scripts' ) );
			
			$this->dqpw_set_properties();
		}

		
		/**
		 * Fill some property arrays with translated strings
		 */
		private function dqpw_set_properties() {
			$this->dqpw_defaults['title'] = __( 'Demo Quote', Demo_Quotes_Plugin::$name );
		}


		/**
		 * Conditionally add front-end scripts and styles
		 */
		public function dqpw_wp_enqueue_scripts() {

			if ( is_active_widget( false, false, $this->id_base, true ) ) {
//pr_var( $this );
				wp_register_style(
					Demo_Quotes_Plugin::$name . '-css', // id
					plugins_url( 'css/style' . Demo_Quotes_Plugin::$suffix . '.css', __FILE__ ), // url
					array(), // not used
					self::DQPW_STYLES_VERSION, // version
					'all' // media
				);
				wp_enqueue_style( Demo_Quotes_Plugin::$name . '-css' );

				/* Register, but don't enqueue yet */
				wp_register_script(
					Demo_Quotes_Plugin::$name . '-js', // id
					plugins_url( 'js/interaction' . Demo_Quotes_Plugin::$suffix . '.js', __FILE__ ), // url
					array( 'jquery', 'wp-ajax-response' ), // dependants
					self::DQPW_SCRIPTS_VERSION, // version
					true // load in footer
				);
			}

/*

				if ( ( true === $this->settings['enable_hidden_class'] && ( is_array( $this->settings['hidden_classname'] ) && 0 < count( $this->settings['hidden_classname'] ) ) ) || ( true === $this->settings['enable_async'] && ( is_array( $this->active_mimetypes ) && 0 < count( $this->active_mimetypes ) ) ) ) {
					wp_enqueue_script(
						self::DQPW_NAME, // id
						self::$url . 'js/interaction' . self::$suffix . '.js', // url
						array( 'jquery' ), // dependants
						self::SCRIPTS_VERSION, // version
						true // load in footer
					);
				}
	
				wp_localize_script( self::$name, 'i18n_demo_quotes', $this->wp_localize_script() );

			}*/
		}


		/**
		 * Retrieve the strings for use in the javascript file
		 *
		 * @usedby    wp_enqueue_scripts()
		 *
		 * @param   int     $id     Current quote id
		 * @return    array
		 */
		private function dqpw_wp_localize_script( $id = null ) {
			$strings = array(
				'ajaxurl'	=> esc_js( admin_url( 'admin-ajax.php' ) ),
				'dqpwNonce' => esc_js( wp_create_nonce( 'demo-quotes-widget-next-nonce' ) ),
				'currentQuote'	=> array(
					$this->number	=>	( isset( $id ) ? esc_js( $id ) : '' ),
				),
			);
			return $strings;
		}

	
		/**
		 * Front-end display of widget.
		 *
		 * @see WP_Widget::widget()
		 *
		 * @param array $args     Widget arguments.
		 * @param array $instance Saved values from database.
		 */
		public function widget( $args, $instance ) {
//pr_var( $this );
			/* Merge incoming $instance with widget settings defaults */
			$instance = wp_parse_args( $instance, $this->dqpw_defaults );

			/* Get a quote */
			$quote = Demo_Quotes_Plugin::get_random_quote( null, false, 'array' );

			/* Queue our js if needed */
			if ( ( $instance['async_next'] === true && wp_script_is( Demo_Quotes_Plugin::$name . '-js', 'enqueued' ) === false ) && ( wp_script_is( Demo_Quotes_Plugin::$name . '-js', 'done' ) === false && wp_script_is( Demo_Quotes_Plugin::$name . '-js', 'to_do' ) === false ) ) {
				wp_enqueue_script( Demo_Quotes_Plugin::$name . '-js' );
				wp_localize_script( Demo_Quotes_Plugin::$name . '-js', 'i18n_demo_quotes', $this->dqpw_wp_localize_script( $quote['id'] ) );
			}

			/* Prepare data */
			$title = apply_filters( 'widget_title', $instance['title'], $instance, $this->id_base );
			$quote = apply_filters( 'demo_quote_widget_quote', $quote['html'], $quote['id'] );


			if ( isset( $quote ) && is_string( $quote ) && $quote !== '' ) {
				echo '
			<!-- BEGIN Demo Quotes Plugin Widget -->
			' . $args['before_widget'];

				if ( is_string( $title ) && $title !== '' ) {
					echo '
				' . $args['before_title'] . $title . $args['after_title'];
				}

				echo '
				<div class="dqpw-quote-wrapper">
					' . $quote . '
				</div>';

				if ( $instance['async_next'] === true ) {
					echo '
				<div class="dqpw-quote-next">
					<p><a href="#">' . __( 'next quote&nbsp;&raquo;', Demo_Quotes_Plugin::$name ) . '</a></p>
				</div>
					';
				}

				echo '
			' . $args['after_widget'] . '
			<!-- END Demo Quotes Plugin Widget -->';
			}
		}

		/**
		 * Back-end widget form.
		 *
		 * @see WP_Widget::form()
		 *
		 * @param array $instance Previously saved values from database.
		 * @return string|void
		 */
		public function form( $instance ) {
			$instance = wp_parse_args( (array) $instance, $this->dqpw_defaults );
			
			echo '<p>
			<label for="' . esc_attr( $this->get_field_id( 'title' ) ) . '">' . esc_html__( 'Title:' ) . '</label>
			<input class="widefat" id="' . esc_attr( $this->get_field_id( 'title' ) ) .'" name="' . esc_attr( $this->get_field_name( 'title' ) ) . '" type="text" value="' . esc_attr( $instance['title'] ) . '" />
			</p>
			<p><input type="checkbox" class="checkbox" id="' . $this->get_field_id( 'async_next' ) . '" name="' . $this->get_field_name( 'async_next' ) . '"' . checked( $instance['async_next'], true, false ) . ' />
			<label for="' . $this->get_field_id( 'async_next' ) . '">' . __( 'Show "next quote" link ?', Demo_Quotes_Plugin::$name ) . '</label><br />';

			/**
			 * Potential extra option:
			 * Show :
			 * radio..:
			 * - random quotes
			 * - quotes from person x
			 * - quotes with tag xx
			 * - most recent quote (async will give random quote)
			 */

			/**
			 * Potential extra option:
			 * Theming of widget
			 */
		}
	
		/**
		 * Sanitize widget form values as they are saved.
		 *
		 * @see WP_Widget::update()
		 *
		 * @param array $new_instance Values just sent to be saved.
		 * @param array $old_instance Previously saved values from database.
		 *
		 * @return array Updated safe values to be saved.
		 */
		public function update( $new_instance, $old_instance ) {
			$instance = $old_instance;

			$instance['title']      = strip_tags( $new_instance['title'] );
			$instance['async_next'] = ( ! empty( $new_instance['async_next'] ) ? true : false );

			return $instance;
		}
	} // class Demo_Quotes_Plugin_Widget
} // if class exists
