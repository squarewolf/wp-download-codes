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
 * Returns the full path of the download file location.
 */
function dc_file_location( $str_mode = 'full' ) {

	// Get location of download file
	$dc_file_location = ( '' == get_option( 'dc_file_location' ) ? get_option( 'dc_zip_location' ) : get_option( 'dc_file_location' ) );

	// Check if location is an absolute or relative path
	if ( strlen( $dc_file_location ) > 0 && '/' == substr( $dc_file_location, 0, 1) ) {
		// Absolute locations are returned directly
		return $dc_file_location;
	}
	else {
		// Relative locations are returned with the respective upload path directory
		$wp_upload_dir = wp_upload_dir();
		$upload_path = get_option( 'upload_path' );
		
		if ( 'full' == $str_mode ) {
			if ( substr( $wp_upload_dir['basedir'], 0, strlen( $upload_path ) ) == $upload_path ) {
				return  $upload_path . '/' . $dc_file_location;
			}
			else {
				return $wp_upload_dir['basedir'] . '/' . $dc_file_location;
			}
		}
		else {
			return ( !substr( $upload_path, 0, 1) == "/" ? "/" : "") . $upload_path . '/' . $dc_file_location;
		}
	}
}

/**
 * Returns a list of allowed file types.
 */
function dc_file_types() {
	$str_file_types = get_option( 'dc_file_types' );
	
	if ( '' == $str_file_types ) {
		$arr_file_types = array( 'zip', 'mp3' );
	}
	else {
		$arr_file_types = explode( ',', $str_file_types );
	}
	
	return $arr_file_types;
}

/**
 * Get a message  
 */
function dc_msg( $str_msg ) {
	// Try to get option for desired message
	$str_return = get_option( 'dc_msg_' . $str_msg );
	
	if ( '' == $str_return ) {
		// Default messages
		switch ( $str_msg ) {
			case 'code_enter': 
				$str_return = 'Enter download code:';
				break;
			case 'code_valid': 
				$str_return = 'Thank you for entering a valid download code! Please proceed with the download by clicking the following link:';
				break;
			case 'code_invalid': 
				$str_return = 'You have entered an invalid download code, please try again.';
				break;
			case 'max_downloads_reached': 
				$str_return = 'You have reached the maximum number of allowed downloads for this code. Please refer to the administrator for information about reactivating your code.';
				break;
			case 'max_attempts_reached': 
				$str_return = 'You have had too many unsuccessful download attempts today. Please wait and try again.';
				break;
		}
	}
	return $str_return;
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