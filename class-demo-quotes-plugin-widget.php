<?php
/**
 * Demo Quote Widget.
 *
 * @package WordPress\Plugins\Demo_Quotes_Plugin
 * @subpackage Widget
 */

// Avoid direct calls to this file.
if ( ! function_exists( 'add_action' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

if ( class_exists( 'Demo_Quotes_Plugin' ) && ( class_exists( 'WP_Widget' ) && ! class_exists( 'Demo_Quotes_Plugin_Widget' ) ) ) {

	/**
	 * Demo Quotes Widget.
	 */
	class Demo_Quotes_Plugin_Widget extends WP_Widget {

		/**
		 * Unique widget name.
		 *
		 * @const string
		 */
		const DQPW_NAME = 'demo_quotes_widget';

		/**
		 * Widget default settings.
		 *
		 * @var array
		 */
		public $dqpw_defaults = array(
			'title'         => null, // Will be set to localized string via dqpw_set_properties().
			'async_next'    => false,
		);


		/**
		 * Register widget with WordPress.
		 */
		public function __construct() {

			$widget_ops = array(
				'classname'     => self::DQPW_NAME,
				'description'   => __( 'Shows a (random) quote from the demo quotes post type.', 'demo-quotes-plugin' ),
			);

			parent::__construct(
				self::DQPW_NAME, // Base ID.
				__( 'Demo Quotes Widget', 'demo-quotes-plugin' ), // Name.
				$widget_ops // Option arguments.
			);

			$this->dqpw_set_properties();
		}


		/**
		 * Fill some property arrays with translated strings.
		 *
		 * @return void
		 */
		private function dqpw_set_properties() {
			$this->dqpw_defaults['title'] = __( 'Demo Quote', 'demo-quotes-plugin' );
		}


		/**
		 * Retrieve the strings for use in the javascript file.
		 *
		 * @param int $id Current quote id.
		 *
		 * @return array
		 */
		private function dqpw_wp_localize_script( $id = null ) {
			$strings = array(
				'ajaxurl'       => esc_js( admin_url( 'admin-ajax.php' ) ),
				'dqpwNonce'     => esc_js( wp_create_nonce( 'demo-quotes-widget-next-nonce' ) ),
				'currentQuote'  => array(
					$this->number   => ( isset( $id ) ? esc_js( $id ) : '' ),
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
		 *
		 * @return void
		 */
		public function widget( $args, $instance ) {

			/* Merge incoming $instance with widget settings defaults. */
			$instance = wp_parse_args( $instance, $this->dqpw_defaults );

			/* Get a quote. */
			$quote = Demo_Quotes_Plugin::get_random_quote( null, false, 'array' );

			/* Queue our js if needed. */
			if ( ( true === $instance['async_next'] && false === wp_script_is( Demo_Quotes_Plugin::$name . '-js', 'enqueued' ) ) && ( false === wp_script_is( Demo_Quotes_Plugin::$name . '-js', 'done' ) && false === wp_script_is( Demo_Quotes_Plugin::$name . '-js', 'to_do' ) ) ) {
				wp_enqueue_script( Demo_Quotes_Plugin::$name . '-js' );
				wp_localize_script( Demo_Quotes_Plugin::$name . '-js', 'i18nDemoQuotes', $this->dqpw_wp_localize_script( $quote['id'] ) );
			}

			/* Prepare data. */
			$title = apply_filters( 'widget_title', $instance['title'], $instance, $this->id_base );
			$quote = apply_filters( 'demo_quote_widget_quote', $quote['html'], $quote['id'] );

			/* Generate output. */
			if ( ! empty( $quote ) && is_string( $quote ) ) {
				echo '
			<!-- BEGIN Demo Quotes Plugin Widget -->
			', wp_kses_post( $args['before_widget'] );

				if ( ! empty( $title ) && is_string( $title ) ) {
					echo '
				', wp_kses_post( $args['before_title'] . $title . $args['after_title'] );
				}

				echo '
				<div class="dqpw-quote-wrapper">
					', wp_kses_post( $quote ), '
				</div>';

				if ( true === $instance['async_next'] ) {
					echo '
				<div class="dqpw-quote-next">
					<p><a href="#">', esc_html__( 'next quote&nbsp;&raquo;', 'demo-quotes-plugin' ), '</a></p>
				</div>
					';
				}

				echo '
			', wp_kses_post( $args['after_widget'] ), '
			<!-- END Demo Quotes Plugin Widget -->';
			}
		}


		/**
		 * Back-end widget form.
		 *
		 * @see WP_Widget::form()
		 *
		 * @param array $instance Previously saved values from database.
		 *
		 * @return void
		 */
		public function form( $instance ) {
			$instance = wp_parse_args( (array) $instance, $this->dqpw_defaults );

			echo '<p>
			<label for="', esc_attr( $this->get_field_id( 'title' ) ), '">',
			esc_html__( 'Title:', 'default' ), '</label>
			<input class="widefat" id="', esc_attr( $this->get_field_id( 'title' ) ), '" name="', esc_attr( $this->get_field_name( 'title' ) ), '" type="text" value="', esc_attr( $instance['title'] ), '" />
			</p>
			<p><input type="checkbox" class="checkbox" id="', esc_attr( $this->get_field_id( 'async_next' ) ), '" name="', esc_attr( $this->get_field_name( 'async_next' ) ), '"', checked( $instance['async_next'], true, false ), ' />
			<label for="', esc_attr( $this->get_field_id( 'async_next' ) ), '">', esc_html__( 'Show "next quote" link ?', 'demo-quotes-plugin' ), '</label><br />';

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


	} /* End of class. */

} /* End of class exists wrapper. */
