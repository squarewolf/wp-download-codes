<?php
/*
Plugin Name: WP Download Codes
Plugin URI: http://wordpress.org/extend/plugins/wp-download-codes/

Description: The plugin enables to generation and management of download codes for .zip files. It was written to enable the free download of records and CDs with dedicated codes printed on the cover of the releases or on separate download cards.

Version: 1.0.8
Author: misanthrop
Author URI: http://www.misantropolis.de

	Copyright 2009 Armin Fischer  (email : misantropolis@gmail.com)
	
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

/**
 * Inclusion of administration, template and general functions.
 */

   include( 'dc_administration.php' );
   include( 'dc_template.php' );
   include( 'dc_functions.php' );

/**
 * Addition of dc functions to hooks.
 */

if (is_admin()) {
   // Create administration menu 
   add_action( 'admin_menu', 'dc_admin_menu' );
}
else {
   // Sending headers for download of files
   add_action( 'send_headers', 'dc_headers' );
}

// Shortcode for [download-code id="..."]
add_shortcode( 'download-code', 'dc_download_form' );

// Activation of plugin
register_activation_hook( __FILE__, 'dc_init' );

// Uninstallation of plugin
if ( function_exists('register_uninstall_hook') )
	register_uninstall_hook(__FILE__, 'dc_uninstall');
?>