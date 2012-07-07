<?php
/*
Plugin Name: WP Download Codes
Plugin URI: http://wordpress.org/extend/plugins/wp-download-codes/

Description: The plugin enables to generation and management of download codes for .zip files. It was written to enable the free download of records and CDs with dedicated codes printed on the cover of the releases or on separate download cards.

Version: 2.1.1
Author: misanthrop, spalmer
Author URI: http://www.misantropolis.de, http://quoperative.com

	Copyright 2009-2012 Armin Fischer  (email : misantropolis@gmail.com)
	
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
 * Default values
 */
 
define( DC_MAX_ATTEMPTS, 3 );
define( DC_ALLOWED_DOWNLOADS, 3 );
define( DC_FILE_TYPES, 'zip, mp3' );
define( DC_CODE_CHARS, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ01234567890' );

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
   // Send headers for file downloads
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