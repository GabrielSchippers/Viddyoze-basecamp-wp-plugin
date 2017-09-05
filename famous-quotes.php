<?php
/** 
 * Plugin Name: Famous Quote
 * Description: Famous Quotes plugin with Ajax powered Random Quote sidebar widget helps you collect and display your favourite quotes in your WordPress blog/website.
 * Version: 1.0.0
 * Author: Gabriel Schippers
 * Domain Path: /languages/
 */

/*  
	This program is domo test software; 
*/

/** Prevent direct access to the file **/
defined( 'ABSPATH' ) or die( 'Access denied' );

require_once( 'inc/famous-quotes-collection.php' );
include_once( 'inc/famous-quotes-collection-widget.php' );
if( is_admin() ) {
	require_once( 'inc/famous-quotes-admin-list-table.php' );
	require_once( 'inc/famous-quotes-admin.php' );
}

register_activation_hook( __FILE__, array( 'Famous_Quotes_Collection', 'activate' ) );
add_action( 'plugins_loaded', array( 'Famous_Quotes_Collection', 'load' ) );
add_action('widgets_init', array( 'Famous_Quotes_Collection_Widget', 'register' ) );


/**
 * The template function that generates a random quote
 *
 * @param array $args {
 *     'show_author'    => true,
 *     'ajax_refresh'   => true,
 *     'random'         => true,
 *     'auto_refresh'   => false,
 *     'tags'           => '',
 *     'char_limit'     => 1000,
 *     'echo'           => true,
 * }
 *
 * @return string containing the quote block, if 'echo' is passed in as false
 * @return bool true when quote is already echoed, ie., when echo is true
 * @return bool false on error
 *
 */
function famousquotescollection_quote( $args = NULL ) {

	global $quotescollection;
	global $quotescollection_instances;


	if( NULL === $args || ( !is_string($args) && !is_array($args) ) ) {
		$args = array();
	}
	else if( is_string($args) ) { // If args are passed as a string
		// Covert the string into array
		$key_value = explode('&', $args);
		$args = array();
		foreach($key_value as $value) {
			$x = explode('=', $value);
			$args[$x[0]] = $x[1]; // $options['key'] = 'value';
		}
	}

	if( NULL === $quotescollection_instances ) {
		$quotescollection_instances = 0;
	}
	
	$quotescollection_instances++;
	$args['instance'] = "tf_quotescollection_".$quotescollection_instances;

	return $quotescollection->quote( $args );

}

/** Returns the plugin's home directory. If $path is passed, it's appended. */
function famousquotescollection_rel_path( $path = "" ) {
	// If $path comes with a slash, remove it as we'll be adding
	if( $path && '/' == $path[0]) {
		$path = substr( $path, 1 );
	}
	return dirname( plugin_basename( __FILE__) ) . '/'. $path;
}

/** Returns the plugin's url. If $path is passed, it's appended. */
function famousquotescollection_url( $path = "" ) {
	// If $path comes with a slash, remove it as the function be adding
	if( $path && '/' == $path[0]) {
		$path = substr( $path, 1 );
	}
	return plugins_url( $path, __FILE__ );
}
?>
