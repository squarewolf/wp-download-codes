<?php
/**
 * WP Download Codes Plugin
 * 
 * FILE
 * dc_download.php
 *
 * DESCRIPTION
 * This file is only used for streaming of download files. After entering a valid
 * code, the session variables are set (code, filename and location), and after
 * clicking the file link, the browser is redirected to this file. This avoids
 * side effects resulting from other WP plugins or template modifications.
 *
 */
 
// Start session
if ( !session_id() ) {
	session_start();
}

// Get name and location of download file
$dc_filename = $_SESSION['dc_filename'];
$dc_location= $_SESSION['dc_location'];

// Delete session variable and destroy session
unset( $_SESSION['dc_code'] );
$_SESSION = array();
session_destroy();

// Send headers for download
header("Pragma: public");
header("Expires: 0");
header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
header("Content-Description: File Transfer");
header("Content-Type: application/force-download");
header("Content-Type: application/octet-stream");
header("Content-Type: application/download");
header("Content-Disposition: attachment; filename=\"" . $dc_filename . "\"");
header("Content-Transfer-Encoding: binary");
header("Content-Length: ".filesize( $dc_location ));
flush();

// Stream file
$handle = fopen( $dc_location, 'rb' );
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
?>