<?php
/**
 * WP Download Codes Plugin
 * 
 * FILE
 * dc_template.php
 *
 * DESCRIPTION
 * Contains functions for the template integration of the download codes.
 */
 
/**
 * Creates a download form for the shortcode "download-code"
 */
function dc_download_form( $atts ) {
	global $wpdb;
	
	// Get attributes
	extract(shortcode_atts(array(
		'id' => '0',
		'bar' => 'default bar',
	), $atts));
	
	if (isset( $_POST['submit'] )) {
		// Get current IP
		$IP = $_SERVER['REMOTE_ADDR'];
				
		// Get post variables
		$post_code = strtoupper(trim($_POST['code']));
	
		// Check if code is valid
		$wpdb->show_errors();
		$code = $wpdb->get_row( "SELECT ID, `release` FROM " . dc_tbl_codes() . " WHERE CONCAT(code_prefix, code_suffix) = '" . $post_code . "'");
		
		if ( $code->ID ) {
			// Get release details
			if ( $id != 0 ) {
				// Get release by ID
				$release = $wpdb->get_row( "SELECT * FROM " . dc_tbl_releases() . " WHERE ID = " . $id);	
			}
			else {
				// Get release by code
				$release = $wpdb->get_row( "SELECT * FROM " . dc_tbl_releases() . " WHERE ID = " . $code->release);	
			}
			
			// Get # of downloads with this code
			$downloads = $wpdb->get_row( "SELECT COUNT(*) AS downloads FROM " . dc_tbl_downloads() . " WHERE code=(SELECT ID FROM " . dc_tbl_codes() . " WHERE CONCAT(code_prefix, code_suffix) ='" . $post_code . "')");
			
			// Start download if maximum of allowed downloads is not reached
			if ($downloads->downloads < $release->allowed_downloads) {
				// Set temporary download lease id (TODO: replace this with a random id in a lease table later)
				$download_lease_id = md5( 'wp-dl-hash' . $code->ID );
			}
			else {
				$ret = dc_msg( 'max_downloads_reached' );
			}
		}
		else {
			// Get # of attempts from this IP
			$attempts = $wpdb->get_row( "SELECT COUNT(*) AS attempts FROM " . dc_tbl_downloads() . " WHERE IP='" . $IP . "' AND code = -1 AND DATE(started_at) > DATE(CURRENT_DATE() - 1)");		
			
			if ($attempts->attempts < dc_max_attempts()) {
				// Insert attempt
				$wpdb->insert(	dc_tbl_downloads(),
								array( 'code' => -1, 'IP' => $IP),
								array( '%d', '%s') );

				$ret = dc_msg( 'code_invalid' );
			}
			else {
				$ret = dc_msg( 'max_attempts_reached' );
			}	
		}
	}
	
	$html = '<div class="dc-download-code">';
	if ( !$download_lease_id ) {
		// Show message
		if ( $ret != '' ) {
			$html .= '<p>' . $ret . '</p>';
		}
		
		// Display form
		$html .= '<form action="" name="dc_form" method="post">';
		$html .= '<p><input type="hidden" name="release" value="' . $id . '" />'; 
		$html .= dc_msg( 'code_enter' ) .' <input type="text" name="code" value="' . $post_code . '" size="20" /> ';
		$html .= '<input type="submit" name="submit" value="' . __( 'Submit') . '" /></p>';
		$html .= '</form>';
	}
	else {
		// Show link for download
		$html .= '<p>' . dc_msg( 'code_valid' ) . '</p>';
		$html .= '<p><a href="?lease=' . $download_lease_id . '">' . $release->filename . '</a></p>'; 
	}
	$html .= '</div>';
	
	return $html;
}

/**
 * Sends headers to redirect to dc_download.php when download code was entered successfully.
 */
function dc_headers() {
	global $wpdb;
	
	if (isset( $_GET['lease'] )) {
	
		// Get details for code and release
		$release = $wpdb->get_row( "SELECT r.*, c.ID as code, c.code_prefix, c.code_suffix FROM " . dc_tbl_releases() . " r INNER JOIN " . dc_tbl_codes() ." c ON c.release = r.ID WHERE MD5(CONCAT('wp-dl-hash',c.ID)) = '" . $_GET['lease'] . "'" );
		
		// Get # of downloads with this code
		$downloads = $wpdb->get_row( "SELECT COUNT(*) AS downloads FROM " . dc_tbl_downloads() . " WHERE code= " . $release->code );
		
		// Start download if maximum of allowed downloads is not reached
		if ($downloads->downloads < $release->allowed_downloads) {
			// Get current IP
			$IP = $_SERVER['REMOTE_ADDR'];
			
			// Insert download in downloads table
			$wpdb->insert(	dc_tbl_downloads(),
							array( 'code' => $release->code, 'IP' => $IP),
							array( '%d', '%s') );
			
			// Send headers for download
			header("Pragma: public");
			header("Expires: 0");
			header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
			header("Content-Description: File Transfer");
			header("Content-Type: application/force-download");
			header("Content-Type: application/octet-stream");
			header("Content-Type: application/download");
			header("Content-Disposition: attachment; filename=\"" . $release->filename . "\"");
			header("Content-Transfer-Encoding: binary");
			header("Content-Length: ".filesize( dc_file_location() . $release->filename ));
			flush();

			// Stream file
			$handle = fopen( dc_file_location() . $release->filename, 'rb' );
			$chunksize = 1*(1024*1024); 
			$buffer = '';
			if ($handle === false) {
				exit;
			}
			while (!feof($handle)) {
				$buffer = fread($handle, $chunksize);
				echo $buffer;
				flush();
			}

			// Close file
			fclose($handle);
		}
	}
}
?>