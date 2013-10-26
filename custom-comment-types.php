<?php
/*
Plugin Name: Custom Comment Types
Plugin URI: https://github.com/sc0ttkclark/wp-custom-comment-types
Description: A proposal for a Custom Comment Type API for potential inclusion in a future WordPress release
Version: 0.1 Alpha 1
Author: Scott Kingsley Clark, Justin Tadlock, John Billion
Author URI: https://github.com/sc0ttkclark/wp-custom-comment-types

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

define( 'WP_CCT_DIR', plugin_dir_path( __FILE__ ) );

add_action( 'plugins_loaded', 'wp_custom_comment_types_load', 20 );
function wp_custom_comment_types_load() {

	// Avoid potential clusterducks
	if ( !function_exists( 'register_comment_type' ) ) {
		include_once WP_CCT_DIR . 'comment.php';
	}

}