<?php
/**
 * WP Download Codes Plugin
 * 
 * FILE
 * dc_functions.php
 *
 * DESCRIPTION
 * Contains common global functions for the WP Download Codes plugin
 *   - table names
 *   - ...
 *
 */

/**
 * Returns the name of the downloads table.
 */
function dc_tbl_downloads() {
   global $wpdb;
   
   return $wpdb->prefix . "dc_downloads";
}

/**
 * Returns the name of the releases table.
 */
function dc_tbl_releases() {
   global $wpdb;
   
   return $wpdb->prefix . "dc_releases";
}

/**
 * Returns the name of the codes table.
 */
function dc_tbl_codes() {
   global $wpdb;
   
   return $wpdb->prefix . "dc_codes";
}

/**
 * Returns the number of maximum attempts.
 */
function dc_max_attempts() {
   return ( '' == get_option( 'dc_max_attempts' ) ? 5 : get_option( 'dc_max_attempts' ) );
}

/**
 * Returns the full path of the zip location.
 */
function dc_zip_location( $str_mode = 'full' ) {
	
	$wp_upload_dir = wp_upload_dir();
	$upload_path = get_option( 'upload_path' );
	
	if ( 'full' == $str_mode ) {
		if ( substr( $wp_upload_dir['basedir'], 0, strlen( $upload_path ) ) == $upload_path ) {
			return  $upload_path . '/' . get_option( 'dc_zip_location' );
		}
		else {
			return $wp_upload_dir['basedir'] . '/' . get_option( 'dc_zip_location' );
		}
	}
	else {
		return ( !substr( $upload_path, 0, 1) == "/" ? "/" : "") . $upload_path . '/' . get_option( 'dc_zip_location' );
	}	
}

/**
 * Returns a list of allowed file types.
 */
function dc_file_types() {
   return array( 'zip', 'mp3' );
}

/**
 * Get message for entering download code
 */
function dc_msg_code_enter() {
	return "Enter download code: ";
}

/**
 * Get message for valid download code
 */
function dc_msg_code_valid() {
	return "Thank you for entering a valid download code! Please proceed with the download by clicking the following link:";
}

/**
 * Get message for invalid download code
 */
function dc_msg_code_invalid() {
	return "You have entered an invalid download code, please try again.";
}

/**
 * Get message for reaching maximum number of downloads
 */
function dc_msg_max_downloads_reached() {
	return "You have reached the maximum number of allowed downloads for this code. Please refer to the administrator for information about reactivating your code.";
}

/**
 * Get message for reaching maximum number of downloads
 */
function dc_msg_max_attempts_reached() {
	return "You have had too many unsuccessful download attempts today. Please wait and try again.";
}

/**
 * Generate a random character string
 */
function rand_str($length = 32, $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ01234567890')
{
    // Length of character list
    $chars_length = (strlen($chars) - 1);

    // Start our string
    $string = $chars{rand(0, $chars_length)};
   
    // Generate random string
    for ($i = 1; $i < $length; $i = strlen($string))
    {
        // Grab a random character from our list
        $r = $chars{rand(0, $chars_length)};
       
        // Make sure the same two characters don't appear next to each other
        if ($r != $string{$i - 1}) $string .=  $r;
    }
   
    // Return the string
    return $string;
}
?>