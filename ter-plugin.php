<?php
/**
 * * Plugin Name
 *
 * @package    The Editor Recommendations (TER Plugin)
 * @author     Bjorn Inge Vaarvik
 * @copyright  2022 Bjorn Inge Vaarvik
 * @license    GPL-3.0-or-later
 * 
 * @Wordpress-plugin
 * 
 * Plugin Name: OLD The Editor Recommendations (TER Plugin)
 * Plugin URI: http://www.vaarvik.com/ter-widget
 * Description: Plugin that show post in a widget that the editor recommmend with mark post with star.
 * Version: 0.1
 * Author: Bjorn Inge Vaarvik
 * Author URI: http://www.vaarvik.com
 * Text domain:  ter_widget-lang
 * Domain path:  /languages
 */



/**
 * Load plugin textdomain.
 */
function terplugin_init() {
    load_plugin_textdomain( 'terplugin-lang', false, dirname(plugin_basename( __FILE__ ) ) . '/languages' ); 
}
add_action( 'plugins_loaded', 'terplugin_init' );



/**
 * Load style CSS.
 */

function terplugin_enqueue_scripts() {
   wp_enqueue_style('custom-style', plugins_url( '/assets/css/style.css', __FILE__ ), array(),'all');
}
add_action( 'wp_enqueue_scripts', 'terplugin_enqueue_scripts' );




/*
 * ---------------------------------- *
 * constants
 * ---------------------------------- *
*/

if ( ! defined( 'TERplugin_PLUGIN_DIR' ) ) {
	define( 'TERplugin_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'TERplugin_PLUGIN_URL' ) ) {
	define( 'TERplugin_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}


/*
 * -------------------------------------------- *
 * Add editor recommandations checkbox column
 * -------------------------------------------- *
 */


if(!defined('ABSPATH')) {
    exit('No access');
}
// This path variable can be used for the textdomain setup, ignore if not needed
$dir = plugin_dir_path(__FILE__);


add_action('manage_post_posts_custom_column', function($column_key, $post_id) {
// $checked_post should contain your result from your database
// which I didn't show to save
$checked_post = get_post_meta($post_id, 'checked_post', true);
?>
<input type="checkbox" value="true" checked>
<?php
}, 10, 2);

// Add title to head and bottom of column
add_filter('manage_post_posts_columns', function($columns) {
    return array_merge($columns, ['verified' => __('Recommendations', 'terplugin-lang')]);
});



/* --------------------------------------------- *
 * Add icon for the custom recommendation column 
 * --------------------------------------------- *
 */

add_filter( 'manage_post_posts_columns', 'filter_manage_post_posts_columns', 11, 1 );
function filter_manage_post_posts_columns( $columns ) {

  // just to be sure the custom column is present
  if ( ! isset( $columns['verified'] ) ) {
    return $columns;
  }

  // Lets imitate how the default comments icon is handeled
  $columns['verified'] = sprintf(
    '<span class="dashicons dashicons-thumbs-up" title="%1$s"><span class="screen-reader-text">%2$s</span></span>',
    __('Recommendations', 'terplugin-lang'), // copied from the plugin to use translated text, if it exists
    __('Recommendations', 'terplugin-lang')
  );

  // modified array
  return $columns;
}



/* ---------------------------------- *
 * Format the column width with CSS
 * ---------------------------------- *
 */

add_action('admin_head', 'terplugin_add_admin_styles');
function terplugin_add_admin_styles() {
  echo '<style>.column-terplugin_thumb {width: 80px;}</style>';
}


// Creating the widget 
class TER_widget extends WP_Widget {
  
function __construct() {
parent::__construct(
  
// Base ID of your widget
'ter_widget', 
  
// Widget name will appear in UI
__('Editor Recommandetions', 'terplugin-lang'), 
  
// Widget description
array( 'Show post that the editor recommmend' => __( 'Editor Recommandetions', 'terplugin-lang' ), ) 
);
}


  
// Creating widget front-end
  
public function widget( $args, $instance ) {
$title = apply_filters( 'widget_title', $instance['title'] );
  
// before and after widget arguments are defined by themes
echo wp_kses_post($args['before_widget']);
if ( ! empty( $title ) )
echo wp_kses_post($args['before_title'] . $title . $args['after_title']);

  
// This is where you run the code and display the output
echo wp_kses_post(terplugin_show());

}

          
// Widget Backend 
public function form( $instance ) {
if ( isset( $instance[ 'title' ] ) ) {
$title = $instance[ 'title' ];
}
else {
$title = __( 'New title', 'terplugin-lang' );
}


// Widget admin form
?>
<p>
<label for="<?php echo esc_attr($this->get_field_id( 'title' )); ?>"><?php _e( 'Title:' ); ?></label> 
<input class="widefat" id="<?php echo esc_attr($this->get_field_id( 'title' )); ?>" name="<?php echo esc_attr($this->get_field_name( 'title' )); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
</p>
<?php 
}
      
// Updating widget replacing old instances with new
public function update( $new_instance, $old_instance ) {
$instance = array();
$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
return $instance;
}
 
// Class snup_widget ends here
}

// Register and load the widget
function terplugin_load_widget() {
    register_widget( 'ter_widget' );
}
add_action( 'widgets_init', 'terplugin_load_widget' );



/*
 * ---------------------------------- *
 * Show the info in widget
 * ---------------------------------- *
 */
function terplugin_show() { 

    $output = '';

    // The query to fetch future posts
    $the_query = new WP_Query(array( 
        'post_status' => 'publish',
        'posts_per_page' => 2,
        'orderby' => 'date',
        'order' => 'ASC'
    ));
 

// The loop to display posts
if ( $the_query->have_posts() ) {
    $output .='<ul>';
    while ( $the_query->have_posts()) : $the_query-> the_post();
        $output .= ''. get_the_post_thumbnail() .' <div class="terplugin_title"> '. get_the_title() .' </div><div class="terplugin_text"> '. get_post_meta( get_the_id(), 'terplugintext', true ). ' </div><div class="terplugin_time"> '.  get_the_time('d.m.Y') .') </div>';
    endwhile;
    $output .='</ul>';

} else {

// Show this when no future posts are found
    $output .= '<div class="terplugin_noplan"> '. __('No planed posts yet.', 'terplugin-lang-lang') . '</div>';
}

// Reset post data
wp_reset_postdata();
 
// Return output
 
return $output; 
}






// Add shortcode
add_shortcode('terplugin-plugin', 'terplugin_output_plugin'); 
// Enable shortcode execution inside text widgets
add_filter('widget_text', 'do_shortcode');







?>