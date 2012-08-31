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
 * Returns the name of the code groups table.
 */
function dc_tbl_code_groups() {
   global $wpdb;
   
   return $wpdb->prefix . "dc_code_groups";
}

/**
 * Returns the number of maximum attempts.
 */
function dc_max_attempts() {
   return ( '' == get_option( 'dc_max_attempts' ) ? DC_MAX_ATTEMPTS : get_option( 'dc_max_attempts' ) );
}

/**
 * Returns the characters codes can be generated from.
 */
function dc_code_chars() {
   return ( '' == get_option( 'dc_code_chars' ) ? DC_CODE_CHARS : get_option( 'dc_code_chars' ) );
}

/**
 * Returns the full path of the download file location.
 */
function dc_file_location() {

	// Get location of download file (for compatibility of older versions)
	$dc_file_location = ( '' == get_option( 'dc_file_location' ) ? get_option( 'dc_zip_location' ) : get_option( 'dc_file_location' ) );

	// Check if location is an absolute or relative path
	if ( strlen( $dc_file_location ) > 0 && '/' == substr( $dc_file_location, 0, 1 ) ) {
		// Absolute locations are returned directly
		return $dc_file_location;
	}
	else {
		// Relative locations are returned with the respective upload path directory
		$wp_upload_dir = wp_upload_dir();
		$upload_path = get_option( 'upload_path' );
		
		if ( ( strlen( $upload_path ) > 0 ) && ( substr( $wp_upload_dir['basedir'], 0, strlen( $upload_path ) ) == $upload_path ) ) {
			return  $upload_path . '/' . $dc_file_location;
		}
		else {
			return $wp_upload_dir['basedir'] . '/' . $dc_file_location;
		}
	}
}

/**
 * Returns a list of allowed file types.
 */
function dc_file_types() {
	$str_file_types = get_option( 'dc_file_types' );
	
	if ( '' == $str_file_types ) {
		$arr_file_types = explode( ',', DC_FILE_TYPES);
	}
	else {
		$arr_file_types = explode( ',', $str_file_types );
	}
	
	// trim white space
	array_walk($arr_file_types, 'dc_trim_value');
	
	return $arr_file_types;
}

// callback function to trim array value white space
function dc_trim_value(&$value) 
{ 
    $value = trim($value); 
}

/**
 * Output a list of download codes.
 */
function dc_list_codes( $release_id, $group, $show = TRUE )
{
	global $wpdb;
	
	if (!$group) $group = 'all';
	
	echo '<div id="dc_list-' . $group . '" class="updated fade dc_list" ' . ( $show ? '' : 'style="display: none;"' ) . '>';
	 
	$codes = dc_get_codes( $release_id, $group );
			
	if ( $codes ) 
	{
		foreach ( $codes as $code ) {
			echo '<p>'. $code->code_prefix . $code->code_suffix . '</p>' . "\n";
		}
	}
	else {
		echo '<p>No download codes</p>';
	}
	
	echo '</div>';
}

/**
 * Output a list of downloads.
 */
function dc_list_downloads( $release_id, $group, $show = TRUE )
{
	global $wpdb;
	
	if ($group == '') $group = 'all';
	
	$release = dc_get_release( $release_id );

	echo '<div id="dc_downloads-' . $group . '" class="dc_downloads" ' . ( $show ? '' : 'style="display: none;"' ) . '>';
	if ( !$show ) {
		echo '<h3>Download Report</h3>' . "\n";
		echo '<p>for <em>' . $release->title . '</em></p>' . "\n";
	}
	
	$downloads = $wpdb->get_results( 
		"SELECT 	r.title, 
					r.artist,
					c.code_prefix,
					c.code_suffix,
					d.IP,
					DATE_FORMAT(d.started_at, '%b %e, %Y @ %H:%i:%S') AS download_time 
		FROM 		(" . dc_tbl_releases() . " r 
		INNER JOIN 	" . dc_tbl_codes() . " c 
		ON 			c.release = r.ID) 
		INNER JOIN 	" . dc_tbl_downloads() . " d 
		ON 			d.code = c.ID 
		WHERE 		r.ID = $release_id " . ( $group == 'all' ? "" : "AND c.group = $group" ) . " 
		ORDER BY 	d.started_at" );
		
	if ( $downloads )
	{
		echo '<table class="widefat">';
		echo '<thead><tr><th>Code</th><th>IP Address</th><th>Date</th></tr></thead>';
		foreach ( $downloads as $download ) {
			echo '<tr><td>' . $download->code_prefix . $download->code_suffix . '</td><td>' . $download->IP . '</td><td>' . $download->download_time . '</td></tr>' . "\n";
		}
		echo '</table>';
	} 
	else {
		echo '<p>No downloads yet</p>';
	}
				
	echo '</div>';
}

/**
 * Get all the code groups for a release.
 */
function dc_get_code_groups( $release_id )
{
	global $wpdb;
	$groups = $wpdb->get_results( 
		"SELECT 	r.ID, 
					r.title, 
					r.artist, 
					r.filename, 
					COUNT(d.ID) AS downloads,
					COUNT(DISTINCT d.code) AS downloaded_codes,
					c.code_prefix, 
					c.group, 
					c.final, 
					COUNT(DISTINCT c.ID) AS codes, 
					MIN(c.code_suffix) as code_example 
		FROM 		" . dc_tbl_releases() . " r 
		LEFT JOIN 	(" . dc_tbl_codes() . " c
		LEFT JOIN 	". dc_tbl_downloads() . " d 
		ON 			d.code = c.ID) 
		ON 			c.release = r.ID 
		WHERE 		r.ID = $release_id 
		GROUP BY 	r.ID, 
					r.filename, 
					r.title, 
					r.artist, 
					c.code_prefix, 
					c.group, 
					c.final 
		ORDER BY 	c.code_prefix" );
	
	return $groups;
}

function dc_get_codes( $release_id, $group )
{
	global $wpdb;
	
	if (!$group) $group = 'all';
	
	$codes = $wpdb->get_results( "
		SELECT 		r.title, 
					r.artist, 
					c.code_prefix, 
					c.code_suffix 
		FROM 		" . dc_tbl_releases() . " r 
		INNER JOIN 	" . dc_tbl_codes() . " c 
		ON 			c.release = r.ID 
		WHERE 		r.ID = $release_id " . ( $group == 'all' ? "" : "AND c.group = $group" ) . " 
		ORDER BY 	c.group, c.code_prefix, c.code_suffix" );
	
	return $codes;
}

/**
 * Finalize the code group for a release.
 */
function dc_finalize_codes( $release_id, $group )
{
	global $wpdb;
	return $wpdb->query( "UPDATE " . dc_tbl_codes() . " SET `final` = 1 WHERE `release` = $release_id AND `group` = $group" );
}

/**
 * Delete the code group for a release.
 */
function dc_delete_codes( $release_id, $group )
{
	global $wpdb;
	return $wpdb->query( "DELETE FROM " . dc_tbl_codes() . " WHERE `release` = $release_id AND `group` = $group" );
}

/**
 * Get all the releases.
 */
function dc_get_releases()
{
	global $wpdb;
	return $wpdb->get_results( 
		"SELECT 	r.ID, 
					r.title, 
					r.artist, 
					r.filename, 
					COUNT(d.ID) AS downloads,
					COUNT(DISTINCT c.ID) AS codes
		FROM 		" . dc_tbl_releases() . " r 
		LEFT JOIN 	(" . dc_tbl_codes() . " c
		LEFT JOIN 	". dc_tbl_downloads() . " d 
		ON 			d.code = c.ID) 
		ON 			c.release = r.ID 
		GROUP BY 	r.ID, 
					r.filename,
					r.title,
					r.artist
		ORDER BY 	r.artist, r.title");
}

/**
 * Get a particular release.
 */
function dc_get_release( $release_id )
{
	global $wpdb;
	return $wpdb->get_row( "SELECT * FROM " . dc_tbl_releases() . " WHERE ID = $release_id ");
}

/**
 * Generate codes for a release.
 */
function dc_generate_codes( $release, $prefix, $codes, $characters )
{
	global $wpdb;
	
	// Make sure all fields are filled out
	if ( !is_numeric($codes) || !is_numeric($characters) ) return false;
	
	// Create new code group
	$wpdb->insert(	dc_tbl_code_groups(), 
					array( 'release' => $release->ID ),
					array( '%d' ) );
	$group = $wpdb->insert_id;

	// Creates desired number of random codes
	for ( $i = 0; $i < $codes; $i++ ) {
	
		// Create random str
		$code_unique = false;
		while ( !$code_unique ) {
			$suffix = rand_str( $characters );
			
			// Check if code already exists
			$code_db = $wpdb->get_row( "SELECT ID FROM " . dc_tbl_codes() . " WHERE code_prefix = `$prefix` AND code_suffix = `$suffix` AND `release` = " . $release->ID );
			$code_unique = ( sizeof( $code_db ) == 0);			
		}
		
		// Insert code
		$wpdb->insert(	dc_tbl_codes(), 
						array( 'code_prefix' => $prefix, 'code_suffix' => $suffix, 'group' => $group, 'release' => $release->ID ),
						array( '%s', '%s', '%d', '%d' ) );
	}
	
	return true;
}

/**
 * Reset the code(s) for a release.
 */
function dc_reset_codes( $release_id, $code )
{
	global $wpdb;
	if (!$code) return 0;
	return $wpdb->query( "DELETE FROM " . dc_tbl_downloads() . " WHERE `code` IN (SELECT ID FROM " . dc_tbl_codes() . " WHERE `release` = $release_id " . ( $code != 'all' ? " AND CONCAT(code_prefix, code_suffix) ='" . $code . "'" : "" ) . ")" );
}

/**
 * Add a new release.
 */
function dc_add_release()
{
	global $wpdb;
	
	$title 			= trim($_POST['title']);
	$artist 		= trim($_POST['artist']);
	$filename 		= $_POST['filename'];
	$downloads 		= $_POST['downloads'];
	
	$errors = array();
	
	// Check if all fields have been filled out properly
	if ( '' == $title ) {
		$errors[] = "The title must not be empty";	
	}
	if ( '' == $filename ) {
		$errors[] = "Please choose a valid file for this release";	
	}
	if ( !is_numeric( $downloads ) ) {
		$errors[] = "Allowed downloads must be a number";
	}
	
	// Update or insert if no errors occurred.
	if ( !sizeof($errors) ) 
	{
		return 
		$wpdb->insert(	dc_tbl_releases(), 
					array( 'title' => $title, 'artist' => $artist, 'filename' => $filename, 'allowed_downloads' => $downloads),
					array( '%s', '%s', '%s', '%d' ) );
	} else
	{
		return $errors;
	}
}

/**
 * Edit a release.
 */
function dc_edit_release()
{
	global $wpdb;

	$title 			= trim($_POST['title']);
	$artist 		= trim($_POST['artist']);
	$filename 		= $_POST['filename'];
	$downloads 		= $_POST['downloads'];
	$release_id		= $_POST['release'];
	
	$errors = array();
	
	// Check if all fields have been filled out properly
	if ( '' == $title ) {
		$errors[] = "The title must not be empty";	
	}
	if ( '' == $filename ) {
		$errors[] = "Please choose a valid file for this release";	
	}
	if ( !is_numeric( $downloads ) ) {
		$errors[] = "Allowed downloads must be a number";
	}
	
	// Update or insert if no errors occurred.
	if ( !sizeof($errors) ) 
	{
		return 
		$wpdb->update(	dc_tbl_releases(), 
					array( 'title' => $title, 'artist' => $artist, 'filename' => $filename, 'allowed_downloads' => $downloads),
					array( 'ID' => $release_id ),
					array( '%s', '%s', '%s', '%d' ) );
	} else
	{
		return $errors;
	}
}

/**
 * Delete a release.
 */
function dc_delete_release( $release_id )
{
	global $wpdb;
	
	$result = 0;
	
	// delete release
	$result += $wpdb->query( "DELETE FROM " . dc_tbl_releases() . " WHERE `ID` = $release_id" );
	// delete code groups
	$result += $wpdb->query( "DELETE FROM " . dc_tbl_code_groups() . " WHERE `release` = $release_id" );
	// delete codes
	$result += $wpdb->query( "DELETE FROM " . dc_tbl_codes() . " WHERE `release` = $release_id" );
	
	return $result;
}

/**
 * Applies basic formatting to status messages.
 */
function dc_admin_message( $message )
{
	return '<div id="message" class="updated"><p>' . $message . '</p></div>';
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
function rand_str( $length = 32, $chars = '' )
{
	// Character list
	if($chars == '') $chars = dc_code_chars();

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

/**
 * Converts bytes into meaningful file size
 */
function format_bytes( $filesize ) 
{
    $units = array( ' B', ' KB', ' MB', ' GB', ' TB' );
    for ( $i = 0; $filesize >= 1024 && $i < 4; $i++ ) $filesize /= 1024;
    return round($filesize, 2) . $units[$i];
}

?>