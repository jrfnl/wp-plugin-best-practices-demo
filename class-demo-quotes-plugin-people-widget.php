<?php

// Avoid direct calls to this file
if ( ! function_exists( 'add_action' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

if ( class_exists( 'Demo_Quotes_Plugin' ) && ( class_exists( 'WP_Widget' ) && ! class_exists( 'Demo_Quotes_Plugin_People_Widget' ) ) ) {
	/**
	 * @package WordPress\Plugins\Demo_Quotes_Plugin
	 * @subpackage People Widget
	 * @version 1.0
	 * @link https://github.com/jrfnl/wp-plugin-best-practices-demo WP Plugin Best Practices Demo
	 *
	 * Based on Categories widget
	 *
	 * @copyright 2013 Juliette Reinders Folmer
	 * @license http://creativecommons.org/licenses/GPL/3.0/ GNU General Public License, version 3
	 */
	class Demo_Quotes_Plugin_People_Widget extends WP_Widget {


		const DQPW_NAME = 'demo_quotes_people_widget';


		/**
		 * @var		array	Widget default settings
		 */
		public $dqpw_defaults = array(
			'title'			=> null, // will be set to localized string via dqpw_set_properties()
			'count'			=> true,
			'hierarchical'	=> true,
			'dropdown'		=> false,
		);



		/**
		 * Register widget with WordPress
		 */
		public function __construct() {

			$widget_ops = array(
				//'classname'	    => self::DQPW_NAME,
				'classname'     => 'widget_categories',
				'description'	=> __( 'A list or dropdown of people of whom quotes are available.', Demo_Quotes_Plugin::$name ),
			);

			parent::__construct(
				self::DQPW_NAME, // Base ID
				__( 'Demo Quotes People Widget', Demo_Quotes_Plugin::$name ), // Name
				$widget_ops // Option arguments
			);

			add_action( 'wp_enqueue_scripts', array( $this, 'dqpw_wp_enqueue_scripts' ), 12 );
			
			$this->dqpw_set_properties();
		}

		
		/**
		 * Fill some property arrays with translated strings
		 */
		private function dqpw_set_properties() {
			$this->dqpw_defaults['title'] = __( 'Quotes by:', Demo_Quotes_Plugin::$name );
		}
		
		/**
		 * Conditionally add front-end scripts and styles
		 */
		public function dqpw_wp_enqueue_scripts() {

			if ( is_active_widget( false, false, $this->id_base, true ) ) {
				wp_enqueue_style( Demo_Quotes_Plugin::$name . '-css' );
			}
		}


		/**
		 * Generate the widget output
		 *
		 * @param   array   $args
		 * @param   array   $instance
		 * @return  void
		 */
		public function widget( $args, $instance ) {

			/* Merge incoming $instance with widget settings defaults */
			$instance = wp_parse_args( (array) $instance, $this->dqpw_defaults );

			$title    = apply_filters( 'widget_title', $instance['title'], $instance, $this->id_base );
			$tax_args = array(
				'taxonomy'		=> Demo_Quotes_Plugin_Cpt::$taxonomy_name,
				'orderby' 		=> 'name',
				'show_count'	=> $instance['count'],
				'hierarchical'	=> $instance['hierarchical'],
				'hide_empty'	=> true,
			);


			echo '
			<!-- BEGIN Demo Quotes Plugin People Widget -->
			' . $args['before_widget'];

			if ( is_string( $title ) && $title !== '' ) {
				echo '
				' . $args['before_title'] . $title . $args['after_title'];
			}


			if ( $instance['dropdown'] === true ) {
				$tax_args['show_option_none'] = __( 'Select Person', Demo_Quotes_Plugin::$name );
				$tax_args['id']				  = self::DQPW_NAME . '-dropdown';
				$this->dropdown_custom_taxonomy( apply_filters( 'demo_quotes_people_widget_dropdown_args', $tax_args ) );

?>
	<script type='text/javascript'>
	/* <![CDATA[ */
		/* People Widget dropdown */
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
			}
			else {
				echo '
			<ul>';

				$tax_args['title_li'] = '';
				wp_list_categories( apply_filters( 'demo_quotes_people_widget_args', $tax_args ) );
				
				echo '
			</ul>';
			}
	
			echo '
			' . $args['after_widget'] . '
			<!-- END Demo Quotes Plugin People Widget -->';
		}
		
		/**
		 * Show dropdown for custom taxonomy with working slugs
		 * Based on wp_dropdown_categories()
		 *
		 * @param   array   $args
		 * @return  void
		 */
		private function dropdown_custom_taxonomy( $args ) {

			$defaults = array(
				'show_option_all' => '',
				'show_option_none' => '',
				'orderby' => 'id',
				'order' => 'ASC',
				'show_count' => 0,
				'hide_empty' => 1,
				'child_of' => 0,
				'exclude' => '',
				'echo' => 1,
				'selected' => 0,
				'hierarchical' => 0,
				'name' => 'cat',
				'id' => '',
				'class' => 'postform',
				'depth' => 0,
				'tab_index' => 0,
				'taxonomy' => 'category',
				'hide_if_empty' => false,
			);
			
			$defaults['selected'] = ( is_category() ) ? get_query_var( 'cat' ) : 0;

			$r = wp_parse_args( $args, $defaults );
			
			if ( ! isset( $r['pad_counts'] ) && $r['show_count'] && $r['hierarchical'] ) {
				$r['pad_counts'] = true;
			}
			
			extract( $r );
			
			$tab_index_attribute = '';
			if ( (int) $tab_index > 0 ) {
				$tab_index_attribute = ' tabindex="' . $tab_index . '"';
			}

			$terms = get_terms( $taxonomy, $r );
			$name  = esc_attr( $name );
			$class = esc_attr( $class );
			$id    = $id ? esc_attr( $id ) : $name;

			if ( ! $r['hide_if_empty'] || ! empty( $terms ) ) {
				$output = '<select name="' . $name . '" id="' . $id . '" class="' . $class . '" ' . $tab_index_attribute . ">\n";
			}
			else {
				$output = '';
			}
			
			if ( empty( $terms ) && ! $r['hide_if_empty'] && ! empty( $show_option_none ) ) {
				$show_option_none = apply_filters( 'list_cats', $show_option_none );
				$output .= "\t<option value=\"-1\" selected=\"selected\">$show_option_none</option>\n";
			}
			
			if ( ! empty( $terms ) ) {
				if ( $show_option_all ) {
					$show_option_all = apply_filters( 'list_cats', $show_option_all );
					$selected = ( '0' === strval( $r['selected'] ) ) ? ' selected="selected"' : '';
					$output .= "\t<option value=\"0\"$selected>$show_option_all</option>\n";
				}
				
				if ( $show_option_none ) {
					$show_option_none = apply_filters( 'list_cats', $show_option_none );
					$selected = ( '-1' === strval( $r['selected'] ) ) ? ' selected="selected"' : '';
					$output .= "\t<option value=\"-1\"$selected>$show_option_none</option>\n";
				}

				/*if ( $hierarchical ) {
					$depth = $r['depth'];  // Walk the full depth.
				else
					$depth = -1; // Flat.

				$output .= walk_category_dropdown_tree( $terms, $depth, $r );*/
				// Disregard depth
				foreach ( $terms as $term ) {
					$term_name = apply_filters( 'list_cats', $term->name, $term );
					$output   .= "\t" . '<option class="level-0" value="' . esc_attr( $term->slug ) . '"';
					if ( $term->term_id == $r['selected'] ) {
						$output .= ' selected="selected"';
					}
					$output .= '>' . esc_html( $term_name );
					if ( $args['show_count'] ) {
						$output .= '&nbsp;&nbsp;('. esc_html( $term->count ) .')';
					}
					$output .= "</option>\n";
				}
			}

			if ( ! $r['hide_if_empty'] || ! empty( $terms ) ) {
				$output .= "</select>\n";
			}
			
			$output = apply_filters( 'wp_dropdown_cats', $output );
			
			if ( $echo ) {
				echo $output;
			}
			return $output;
		}


		/**
		 * Update widget settings
		 *
		 * @param   array   $new_instance
		 * @param   array   $old_instance
		 * @return  array
		 */
		public function update( $new_instance, $old_instance ) {
			$instance = $old_instance;

			$instance['title']        = strip_tags( $new_instance['title'] );
			$instance['count']        = ( ! empty( $new_instance['count'] ) ? true : false );
//			$instance['hierarchical'] = ( ! empty( $new_instance['hierarchical'] ) ? true : false );
			$instance['dropdown']     = ( ! empty( $new_instance['dropdown'] ) ? true : false );

			return $instance;
		}


		/**
		 * Show widget settings form
		 *
		 * @param   array   $instance
		 * @return  void
		 */
		public function form( $instance ) {
			$instance = wp_parse_args( (array) $instance, $this->dqpw_defaults );

			echo '
			<p><label for="' . $this->get_field_id( 'title' ) . '">' . __( 'Title:' ) . '</label>
			<input class="widefat" id="' . $this->get_field_id( 'title' ) . '" name="' . $this->get_field_name( 'title' ) . '" type="text" value="' . esc_attr( $instance['title'] ) . '" /></p>

			<p><input type="checkbox" class="checkbox" id="' . $this->get_field_id( 'dropdown' ) . '" name="' . $this->get_field_name( 'dropdown' ) . '"' . checked( $instance['dropdown'], true, false ) . ' />
			<label for="' . $this->get_field_id( 'dropdown' ) . '">' . __( 'Display as dropdown' ) . '</label><br />

			<input type="checkbox" class="checkbox" id="' . $this->get_field_id( 'count' ) . '" name="' . $this->get_field_name( 'count' ) . '"' . checked( $instance['count'], true, false ) . ' />
			<label for="' . $this->get_field_id( 'count' ) . '">' . __( 'Show post counts' ) . '</label>' . '<p>';

			/*<br />

			<input type="checkbox" class="checkbox" id="' . $this->get_field_id('hierarchical') . '" name="' . $this->get_field_name('hierarchical') . '"' . checked( $instance['hierarchical'], true, false ) . ' />
			<label for="' . $this->get_field_id('hierarchical') . '">' . __( 'Show hierarchy' ) . '</label> '</p>';*/
		}
	}
}