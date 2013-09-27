<?php

// Avoid direct calls to this file
if ( !function_exists('add_action') ) {
	header('Status: 403 Forbidden');
	header('HTTP/1.1 403 Forbidden');
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
			'title'		=>	null, // will be set to localized string via dqpw_set_properties()

		);



		/**
		 * Register widget with WordPress
		 */
		function __construct() {

			$widget_ops = array(
				'class'			=> 'demo-quotes-widget',
				'description'	=> __( 'A Widget which shows quotes from the demo quotes post type.', Demo_Quotes_Plugin::$name ),
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
		function dqpw_set_properties() {

			$this->dqpw_defaults = array(
				'title'	   => __( 'Demo Quote', Demo_Quotes_Plugin::$name ),
			);
		}


		/**
		 * Conditionally add front-end scripts and styles
		 */
		function dqpw_wp_enqueue_scripts() {

			if( is_active_widget( false, false, $this->id_base, true ) ) {

/*				wp_register_style(
					self::$name, // id
					add_query_arg(
						'cssvars',
						base64_encode( 'mtli_height=' . $this->settings['image_size'] . '&mtli_image_type=' . $this->settings['image_type'] . '&mtli_leftorright=' . $this->settings['leftorright'] ),
						self::$url . 'css/style.php'
					), // url
					array(), // not used
					self::STYLES_VERSION, // version
					'all'
				);
				wp_enqueue_style( self::$name );
	
	
				if ( ( true === $this->settings['enable_hidden_class'] && ( is_array( $this->settings['hidden_classname'] ) && 0 < count( $this->settings['hidden_classname'] ) ) ) || ( true === $this->settings['enable_async'] && ( is_array( $this->active_mimetypes ) && 0 < count( $this->active_mimetypes ) ) ) ) {
					wp_enqueue_script(
						self::$name, // id
						self::$url . 'js/interaction' . self::$suffix . '.js', // url
						array( 'jquery' ), // dependants
						self::SCRIPTS_VERSION, // version
						true // load in footer
					);
				}
	
				wp_localize_script( self::$name, 'i18n_demoquotes', $this->wp_localize_script() );
*/
			}
		}


		/**
		 * Retrieve the strings for use in the javascript file
		 *
		 * @usedby	wp_enqueue_scripts()
		 *
		 * @return	array
		 */
		function dqpw_wp_localize_script() {
/*			$strings = array(
				'hidethings'			=> ( ( true === $this->settings['enable_hidden_class'] && ( is_array( $this->settings['hidden_classname'] ) && 0 < count( $this->settings['hidden_classname'] ) ) ) ? true : false ),
				'enable_async'			=> ( ( true === $this->settings['enable_async'] && ( is_array( $this->active_mimetypes ) && 0 < count( $this->active_mimetypes ) ) ) ? true : false ),
				'enable_async_debug'	=> ( ( true === $this->settings['enable_async_debug'] && ( is_array( $this->active_mimetypes ) && 0 < count( $this->active_mimetypes ) ) ) ? true : false ),
			);

			return $strings;*/
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
			/**
			 *  Merge incoming $instance with widget settings defaults
			 */
			$instance = wp_parse_args( $instance, $this->dqpw_defaults );

			/* Prepare title */
			$title = apply_filters( 'widget_title', $instance['title'], $instance, $this->id_base );

			/* Get a quote based on the instance choices */
			$quote = 'to be queried';
			$quote_author = 'to be queried';
			$quote_id = 0;

/*			$quote_link = get_permalink( $page->ID );
			$quote_author_link = get_permalink( $quote->authorID );
*/
			$quote = apply_filters( 'demo_quote_widget_quote', $quote, $quote_id );

			if( isset( $quote ) && is_string( $quote ) && $quote !== '' ) {
				echo '
			<!-- BEGIN Demo Quotes Plugin Widget -->
			' . $args['before_widget'];

				if( is_string( $title ) && $title !== '' ) {
					echo '
				' . $args['before_title'] . $title . $args['after_title'];
				}

/*				echo '
				<div class="dqpw-quote">
					<p>
						<a href="' . esc_url( $quote_link ) . '" title="' . esc_attr( $page->post_name ) . '">' . $quote . '</a>
					</p>
					<p>
						<a href="' . esc_url( $quote_author_link ) . '" title="' . esc_attr__( sprintf( 'View more quotes from %s', Demo_Quotes_Plugin::$name ), $quote_author ) . '">' . esc_html( $quote_author ) . '</a>
					</p>
				</div>';
*/
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
		 */
		public function form( $instance ) {
			if ( isset( $instance[ 'title' ] ) ) {
				$title = $instance[ 'title' ];
			}
			else {
				$title = __( 'Demo Quote', Demo_Quotes_Plugin::$name );
			}
			?>
			<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
			</p>
			<?php
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
			
			$defaults = apply_filters( 'demo_quote_widget_defaults', $this->dqpw_defaults );

			$instance = array();
			$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';

			return $instance;
		}
	} // class Demo_Quotes_Plugin_Widget
} // if class exists
