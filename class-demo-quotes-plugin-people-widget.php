<?php
/**
 * People Widget.
 *
 * @package WordPress\Plugins\Demo_Quotes_Plugin
 * @subpackage People_Widget
 */

// Avoid direct calls to this file.
if ( ! function_exists( 'add_action' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

if ( class_exists( 'Demo_Quotes_Plugin' ) && ( class_exists( 'WP_Widget' ) && ! class_exists( 'Demo_Quotes_Plugin_People_Widget' ) ) ) {

	/**
	 * Demo Quotes People Widget.
	 * Based on WP Native Categories widget.
	 */
	class Demo_Quotes_Plugin_People_Widget extends WP_Widget {

		/**
		 * Widget name.
		 *
		 * @const string
		 */
		const DQPW_NAME = 'demo_quotes_people_widget';

		/**
		 * Widget default settings.
		 *
		 * @var array
		 */
		public $dqpw_defaults = array(
			'title'         => null, // Will be set to localized string via dqpw_set_properties().
			'count'         => true,
			'hierarchical'  => true,
			'dropdown'      => false,
		);


		/**
		 * Register widget with WordPress.
		 */
		public function __construct() {

			$widget_ops = array(
				'classname'     => 'widget_categories, ' . self::DQPW_NAME,
				'description'   => __( 'A list or drop-down of people of whom quotes are available.', 'demo-quotes-plugin' ),
			);

			parent::__construct(
				self::DQPW_NAME, // Base ID.
				__( 'Demo Quotes People Widget', 'demo-quotes-plugin' ), // Name.
				$widget_ops // Option arguments.
			);

			add_action( 'wp_enqueue_scripts', array( $this, 'dqpw_wp_enqueue_scripts' ), 12 );

			$this->dqpw_set_properties();
		}


		/**
		 * Fill some property arrays with translated strings.
		 *
		 * @return  void
		 */
		private function dqpw_set_properties() {
			$this->dqpw_defaults['title'] = __( 'Quotes by:', 'demo-quotes-plugin' );
		}


		/**
		 * Conditionally add front-end scripts and styles.
		 *
		 * @return  void
		 */
		public function dqpw_wp_enqueue_scripts() {

			if ( is_active_widget( false, false, $this->id_base, true ) ) {
				wp_enqueue_style( Demo_Quotes_Plugin::$name . '-css' );
			}
		}


		/**
		 * Generate the widget output.
		 *
		 * @param array $args     Widget arguments.
		 * @param array $instance Current Widget instance.
		 *
		 * @return void
		 */
		public function widget( $args, $instance ) {

			/* Merge incoming $instance with widget settings defaults. */
			$instance = wp_parse_args( (array) $instance, $this->dqpw_defaults );

			$title    = apply_filters( 'widget_title', $instance['title'], $instance, $this->id_base );
			$tax_args = array(
				'taxonomy'      => Demo_Quotes_Plugin_Cpt::$taxonomy_name,
				'orderby'       => 'name',
				'show_count'    => $instance['count'],
				'hierarchical'  => $instance['hierarchical'],
				'hide_empty'    => true,
			);

			/* Generate output. */
			echo '
			<!-- BEGIN Demo Quotes Plugin People Widget -->
			' . wp_kses_post( $args['before_widget'] );

			if ( ! empty( $title ) && is_string( $title ) ) {
				echo '
				' . wp_kses_post( $args['before_title'] . $title . $args['after_title'] );
			}

			// People drop-down.
			if ( true === $instance['dropdown'] ) {
				$tax_args['show_option_none'] = __( 'Select Person', 'demo-quotes-plugin' );
				$tax_args['id']               = self::DQPW_NAME . '-dropdown';
				$this->dropdown_custom_taxonomy( apply_filters( 'demo_quotes_people_widget_dropdown_args', $tax_args ) );

?>
	<script type='text/javascript'>
	/* <![CDATA[ */
		// People Widget drop-down.
		var dqppwDropdown = document.getElementById('<?php echo esc_js( self::DQPW_NAME . '-dropdown' ); ?>');
		function dqppwOnPersonChange() {
			if ( dqppwDropdown.options[dqppwDropdown.selectedIndex].value != 0 && dqppwDropdown.options[dqppwDropdown.selectedIndex].value != -1 ) {
				location.href = "<?php echo esc_js( home_url() ); ?>/?<?php echo esc_js( Demo_Quotes_Plugin_Cpt::$taxonomy_name ); ?>="+dqppwDropdown.options[dqppwDropdown.selectedIndex].value;
			}
		}
		dqppwDropdown.onchange = dqppwOnPersonChange;
	/* ]]> */
	</script>
<?php
			} else {

				// People list.
				echo '
			<ul>';

				$tax_args['title_li'] = '';
				wp_list_categories( apply_filters( 'demo_quotes_people_widget_args', $tax_args ) );

				echo '
			</ul>';
			}

			echo '
			', wp_kses_post( $args['after_widget'] ), '
			<!-- END Demo Quotes Plugin People Widget -->';
		}


		/**
		 * Show drop-down for custom taxonomy with working slugs.
		 * Based on wp_dropdown_categories().
		 *
		 * @param array $args Settings to create the drop-down.
		 *
		 * @return string
		 */
		private function dropdown_custom_taxonomy( $args ) {

			$defaults = array(
				'show_option_all'  => '',
				'show_option_none' => '',
				'orderby'          => 'id',
				'order'            => 'ASC',
				'show_count'       => 0,
				'hide_empty'       => 1,
				'child_of'         => 0,
				'exclude'          => '',
				'echo'             => 1,
				'selected'         => 0,
				'hierarchical'     => 0,
				'name'             => 'cat',
				'id'               => '',
				'class'            => 'postform',
				'depth'            => 0,
				'tab_index'        => 0,
				'taxonomy'         => 'category',
				'hide_if_empty'    => false,
			);

			$defaults['selected'] = ( is_category() ) ? get_query_var( 'cat' ) : 0;

			$args = wp_parse_args( $args, $defaults );

			if ( ! isset( $args['pad_counts'] ) && $args['show_count'] && $args['hierarchical'] ) {
				$args['pad_counts'] = true;
			}

			$tab_index_attribute = '';
			if ( (int) $args['tab_index'] > 0 ) {
				$tab_index_attribute = ' tabindex="' . intval( $args['tab_index'] ) . '"';
			}

			$terms  = get_terms( $args['taxonomy'], $args );
			$name   = esc_attr( $args['name'] );
			$class  = esc_attr( $args['class'] );
			$id     = ( $args['id'] ) ? esc_attr( $args['id'] ) : $name;
			$output = '';

			if ( ! $args['hide_if_empty'] || ! empty( $terms ) ) {
				$output = '<select name="' . $name . '" id="' . $id . '" class="' . $class . '" ' . $tab_index_attribute . ">\n";
			}

			if ( empty( $terms ) && ! $args['hide_if_empty'] && ! empty( $show_option_none ) ) {
				$show_option_none = apply_filters( 'list_cats', $show_option_none );
				$output          .= "\t" . '<option value="-1" selected="selected">' . esc_html( $show_option_none ) . "</option>\n";
			}

			if ( ! empty( $terms ) ) {
				if ( $args['show_option_all'] ) {
					$show_option_all = apply_filters( 'list_cats', $args['show_option_all'] );
					$selected        = ( '0' === strval( $args['selected'] ) ) ? ' selected="selected"' : '';
					$output         .= "\t<option value=\"0\"$selected>" . esc_html( $show_option_all ) . "</option>\n";
				}

				if ( $args['show_option_none'] ) {
					$show_option_none = apply_filters( 'list_cats', $args['show_option_none'] );
					$selected         = ( '-1' === strval( $args['selected'] ) ) ? ' selected="selected"' : '';
					$output          .= "\t<option value=\"-1\"$selected>" . esc_html( $show_option_none ) . "</option>\n";
				}

				// Disregard depth.
				foreach ( $terms as $term ) {
					$term_name = apply_filters( 'list_cats', $term->name, $term );
					$output   .= "\t" . '<option class="level-0" value="' . esc_attr( $term->slug ) . '"';
					if ( $term->term_id == $args['selected'] ) {
						$output .= ' selected="selected"';
					}
					$output .= '>' . esc_html( $term_name );
					if ( $args['show_count'] ) {
						$output .= '&nbsp;&nbsp;(' . esc_html( $term->count ) . ')';
					}
					$output .= "</option>\n";
				}
			}

			if ( ! $args['hide_if_empty'] || ! empty( $terms ) ) {
				$output .= "</select>\n";
			}

			$output = apply_filters( 'wp_dropdown_cats', $output );

			if ( $args['echo'] ) {
				echo $output; // WPCS: XSS ok.
			}
			return $output;
		}


		/**
		 * Update widget settings.
		 *
		 * @param array $new_instance Incoming widget settings.
		 * @param array $old_instance Previously saved widget settings.
		 *
		 * @return array
		 */
		public function update( $new_instance, $old_instance ) {
			$instance = $old_instance;

			$instance['title']    = strip_tags( $new_instance['title'] );
			$instance['count']    = ( ! empty( $new_instance['count'] ) ? true : false );
			$instance['dropdown'] = ( ! empty( $new_instance['dropdown'] ) ? true : false );

			return $instance;
		}


		/**
		 * Show widget settings form.
		 *
		 * @param array $instance The Widget settings.
		 *
		 * @return void
		 */
		public function form( $instance ) {
			$instance = wp_parse_args( (array) $instance, $this->dqpw_defaults );

			echo '
			<p><label for="', esc_attr( $this->get_field_id( 'title' ) ), '">',
			/* translators: no need to translate, core translation will be used. */
			esc_html__( 'Title:' ), '</label>
			<input class="widefat" id="', esc_attr( $this->get_field_id( 'title' ) ), '" name="', esc_attr( $this->get_field_name( 'title' ) ), '" type="text" value="', esc_attr( $instance['title'] ), '" /></p>

			<p><input type="checkbox" class="checkbox" id="', esc_attr( $this->get_field_id( 'dropdown' ) ), '" name="', esc_attr( $this->get_field_name( 'dropdown' ) ), '"', checked( $instance['dropdown'], true, false ), ' />
			<label for="', esc_attr( $this->get_field_id( 'dropdown' ) ), '">',
			/* translators: no need to translate, core translation will be used. */
			esc_html__( 'Display as dropdown' ), '</label><br />

			<input type="checkbox" class="checkbox" id="', esc_attr( $this->get_field_id( 'count' ) ), '" name="', esc_attr( $this->get_field_name( 'count' ) ), '"', checked( $instance['count'], true, false ), ' />
			<label for="', esc_attr( $this->get_field_id( 'count' ) ), '">', esc_html__( 'Show quote counts', 'demo-quotes-plugin' ), '</label><p>';
		}


	} /* End of class. */

} /* End of class exists wrapper. */
