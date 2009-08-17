<?php
/**
 * WP Download Codes Plugin
 * 
 * FILE
 * dc_administration.php
 *
 * DESCRIPTION
 * Contains functions for the adminstration functions of the plugin:
 *	- General settings
 *	- Creation of downloads
 *	- Generation of download codes
 *	- Management of download codes
 */

/**
 * Initializes the download codes (dc) plugin.
 */
function dc_init() {
	global $wpdb;

	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	
	$sql = "CREATE TABLE `" . dc_tbl_codes() . "` (
				   `ID` int(11) NOT NULL auto_increment,
				   `code_prefix` varchar(20) NOT NULL,
				   `code_suffix` varchar(20) NOT NULL,
				   `release` int(11) NOT NULL,
				   `final` int(1) NOT NULL,
					PRIMARY KEY  (`ID`)
				 );";
	dbDelta($sql);

	$sql = "CREATE TABLE `" . dc_tbl_downloads() . "` (
				   `ID` int(11) NOT NULL auto_increment,
				   `IP` varchar(20) NOT NULL,
				   `started_at` timestamp NOT NULL,
				   `code` int(11) NOT NULL,
					PRIMARY KEY  (`ID`)
				 );";
	dbDelta($sql);

	$sql = "CREATE TABLE `" . dc_tbl_releases() . "` (
				   `ID` int(11) NOT NULL auto_increment,
				   `title` varchar(100) NOT NULL,
				   `filename` varchar(50) NOT NULL,
				   `allowed_downloads` int(11) NOT NULL,
				   PRIMARY KEY  (`ID`)
				 );";
	dbDelta($sql);
}


function dc_uninstall() {
	global $wpdb;

	// Delete wordpress options
	delete_option( 'dc_zip_location' );
	delete_option( 'dc_max_attempts' );
	
	// Delete database tables
	$wpdb->query( "DROP TABLE " . dc_tbl_downloads() );
	$wpdb->query( "DROP TABLE " . dc_tbl_codes() );
	$wpdb->query( "DROP TABLE " . dc_tbl_releases() );
}

/**
 * Creates the dc admin menu.
 * Hooked with the administration menu.
 */
function dc_admin_menu() {
	// Main menu
	add_menu_page( 'Settings', 'Download Codes', 8, __FILE__, 'dc_admin_settings');

	// General settings
	add_submenu_page( __FILE__, 'Download Code Settings', 'Settings', 8, __FILE__, 'dc_admin_settings');

	// Manage Downloads
	add_submenu_page( __FILE__, 'Manage Releases', 'Manage Releases', 8, 'dc-manage-releases', 'dc_admin_releases');
	
	// Manage Codes
	add_submenu_page( __FILE__, 'Manage Download Codes', 'Manage Codes', 8, 'dc-manage-codes', 'dc_admin_codes');
	
	// Help
	add_submenu_page( __FILE__, 'Download Codes Help', 'Help', 8, 'dc-help', 'dc_admin_help');
}

/**
 * General settings.
 */
function dc_admin_settings() {
	echo '<div class="wrap">';
	echo '<h2>Download Codes &raquo; Settings</h2>';
	
	// Overwrite existing options
	if (isset($_POST['submit'])) {
		$dc_zip_location = trim($_POST['dc_zip_location']);
		$dc_max_attempts = $_POST['dc_max_attempts'];
		
		// Update zip location
		if ( $dc_zip_location != '' ) {
			if ( substr( $dc_zip_location, -1 ) != '/') {
				$dc_zip_location .= '/';
			}

			update_option( 'dc_zip_location', $dc_zip_location );
		}
		
		// Update number of maximum attempts
		if ( is_numeric( $dc_max_attempts ) ) {
			update_option( 'dc_max_attempts' , $dc_max_attempts );
		}
		
		// Print message
		echo '<div id="message" class="updated fade">';
		echo __( 'Options saved.' );
		echo '</div>';
	}
	
	echo '<form method="post" action="">';

	echo '<table class="form-table">';

	// Get subfolders of upload directory
	$wp_upload_dir = wp_upload_dir();
	$files = scandir( $wp_upload_dir['basedir'] );
		
	echo '<tr valign="top">';
	echo '<th scope="row">File</th>';
	echo '<td>/' . get_option( 'upload_path' ) . '/ <select name="dc_zip_location" id="dc_zip_location">';
	foreach ($files as $folder) {
		if ( is_dir( $wp_upload_dir['basedir'] . '/' . $folder ) && $folder != '.' && $folder != '..' ) {
			echo '<option' . ( $folder . '/' == get_option( 'dc_zip_location' ) ? ' selected="selected"' : '' ) . '>' . $folder . '</option>';
		}
	}
	echo '</select></td>';
	echo '</tr>';
	
	echo '<tr valign="top">';
	echo '<th scope="row">Maximum invalid download attempts</th>';
	echo '<td><input type="text" name="dc_max_attempts" size="10" value="' . ( get_option( 'dc_max_attempts' ) == '' ? 3 : get_option( 'dc_max_attempts' ) ) . '" />';
	echo '</tr>';
	
	echo '</table>';
	echo '<p class="submit">';
	echo '<input type="submit" name="submit" class="button-secondary" value="Save changes" />';
	echo '</p>';
	echo '</form>';

	echo '</div>';
}

/**
 * Manage releases.
 */
function dc_admin_releases() {
	global $wpdb;

	// Get parameters
	$get_action = $_GET['action'];
	$get_release = $_GET['release'];

	echo '<div class="wrap">';
	echo '<h2>Download Codes &raquo; Manage Releases</h2>';

	// Update or insert release
	if ( isset($_POST['submit']) ) {
		$post_title = trim($_POST['title']);
		$post_filename = $_POST['filename'];
		$post_release = $_POST['release'];
		$get_release = $post_release;
		$post_downloads = $_POST['downloads'];
		
		// Update or insert entry if title is not empty
		if ( '' != $post_title) {
				
			if ( $post_release == '') {
				$wpdb->insert(	dc_tbl_releases(), 
							array( 'title' => $post_title, 'filename' => $post_filename, 'allowed_downloads' => $post_downloads),
							array( '%s', '%s', '%d') );
					
				// Get ID of new release
				$get_release = $wpdb->insert_id;
					
				$message = "The release was added successfully.";
			}
			else {
				$wpdb->update(	dc_tbl_releases(), 
							array( 'title' => $post_title, 'filename' => $post_filename, 'allowed_downloads' => $post_downloads),
							array( 'ID' => $post_release ),
							array( '%s', '%s', '%d') );
				$message = "The release was updated sucessfully.";
			}
				
		}
		else {
			$message = "The title must not be empty.";		
		}
			
		// Print message
		echo '<div id="message" class="updated fade">';
		echo __( $message );
		echo '</div>';
	}

	if ( $get_action == 'edit' || $get_action == 'new' ) {
	
		//*********************************************
		// Add/Edit release
		//*********************************************
		
		// Get current release
		$release = $wpdb->get_row( "SELECT * FROM " . dc_tbl_releases() . " WHERE ID = $get_release ");
		
		// Write page subtitle
		echo '<h3>' . ( ( 'new' == $get_action ) ? 'Add New' : 'Edit' ) . ' Release</h3>';

		echo '<form method="post" action="admin.php?page=dc-manage-releases">';
		echo '<input type="hidden" name="release" value="' . $release->ID . '" />';

		echo '<table class="form-table">';
		
		echo '<tr valign="top">';
		echo '<th scope="row">Title</th>';
		echo '<td><input type="text" name="title" value="' . $release->title . '" />';
		echo '</tr>';
		
		// Get zip files in download folder
		$files = scandir( dc_zip_location() );
		
		echo '<tr valign="top">';
		echo '<th scope="row">File</th>';
		echo '<td>' . dc_zip_location( 'short' ) . ' <select name="filename">';
		foreach ($files as $filename) {
			if ( strtolower( substr($filename,-4) == ".zip" ) ) {
				echo '<option' . ( $filename == $release->filename ? ' selected="selected"' : '' ) . '>' . $filename . '</option>';
			}
		}
		echo '</select></td>';
		echo '</tr>';
		
		echo '<tr valign="top">';
		echo '<th scope="row">Allowed downloads</th>';
		echo '<td><input type="text" name="downloads" value="' . ( $release->allowed_downloads > 0 ? $release->allowed_downloads : 3 ) . '" />';
		echo '</tr>';
		
		echo '</table>';
		echo '<p class="submit">';
		echo '<input type="submit" name="submit" class="button-secondary" value="Submit" />';
		echo '</p>';

		echo '</form>';
	}
	else {
		//*********************************************
		// List releases
		//*********************************************
		
		// Write page subtitle
		echo '<h3>Releases</h3>';
		
		// Get releases
		$releases = $wpdb->get_results( "SELECT r.ID, r.title, r.filename, COUNT(d.ID) AS downloads, COUNT(DISTINCT c.ID) AS codes FROM " . dc_tbl_releases() . " r LEFT JOIN (" . dc_tbl_codes() . " c LEFT JOIN ". dc_tbl_downloads() . " d ON d.code = c.ID) ON c.release = r.ID GROUP BY r.ID, r.filename, r.title ORDER BY r.title");
		
		echo '<table class="widefat">';
		
		echo '<thead>';
		echo '<tr><th>ID</th><th>Title</th><th>File</th><th># Codes</th><th># Downloads</th><th>Actions</th></tr>';
		echo '</thead>';
		
		echo '<tbody>';
		foreach ($releases as $release) {
			echo '<tr><td>' . $release->ID . '</td><td>' . $release->title . '</td>';
			echo '<td>' . $release->filename . '</td>';
			echo '<td>' . $release->codes . '</td><td>' . $release->downloads . '</td>';
			echo '<td><a href="' . $_SERVER["REQUEST_URI"] . '&amp;release=' . $release->ID . '&amp;action=edit">Edit</a> | ';
			echo '<a href="admin.php?page=dc-manage-codes&amp;release=' . $release->ID . '">Manage codes</a></td></tr>';
		}
		echo '</tbody>';
		
		echo '<tfoot>';
		echo '<tr><th>ID</th><th>Title</th><th>File</th><th># Codes</th><th># Downloads</th><th>Actions</th></tr>';
		echo '</tfoot>';

		echo '</table>';

		// Show link to add a new release
		echo '<p><a class="button-secondary" href="' . str_replace('&action=new', '', $_SERVER["REQUEST_URI"]) . '&amp;action=new">Add new release</a></p>';		
	}
	
	echo '</div>';
}

/**
 * Manage download codes.
 */
function dc_admin_codes() {
	global $wpdb;

	// Get parameters
	$get_release = ( $_GET['release'] == '' ? $_POST['release'] : $_GET['release'] );
	$get_action = $_GET['action'];
	
	// Handle code managing GET actions
	if ( $get_action == 'make-final' ) {
	
		// Make code with certain prefix final
		$wpdb->update( dc_tbl_codes(), 
						array( 'final' => 1 ),
						array( 'release' => $get_release, 'code_prefix' => $_GET['prefix'] ),
						array( '%d' ),
						array( '%d', '%s' ) );
				
	}
	elseif ( $get_action == 'delete' ) {
	
		// Delete codes
		$wpdb->query( "DELETE FROM " . dc_tbl_codes() . " WHERE `release` = $get_release AND `code_prefix` = '" . $_GET['prefix'] . "'" );
		
	}

	echo '<div class="wrap">';
	echo '<h2>Download Codes &raquo; Manage Codes</h2>';
	
	// List of releases
	$releases = $wpdb->get_results( "SELECT ID, title FROM " . dc_tbl_releases() . " ORDER BY title" );

	// Display message if no release exists yet
	if ( sizeof( $releases ) == 0) {
		echo '<div id="message" class="updated fade">No release has been created yet.</div>';
		echo '<p>You may want to <a href="admin.php?page=dc-manage-releases&action=new">add a new release</a> first.</p>';
	}
	else {
	echo '<form action="admin.php?page=dc-manage-codes" method="post">';
	echo '<select name="release" id="release" onchange="submit()">';
	foreach ( $releases as $r ) {
		echo '<option value="' . $r->ID . '"' . ( $r->ID == $get_release ? ' selected="selected"' : '' ) . '>' . $r->title . '</option>';
	}
	echo '</select>';
	echo '</form>';
	if ( $get_release == '' ) {
		$get_release = $releases[0]->ID;
	}
		
	// Get codes for current release
	$codes = $wpdb->get_results( "SELECT r.ID, r.title, r.filename, c.code_prefix, c.final, COUNT(c.ID) AS codes, MIN(c.code_suffix) as code_example FROM " . dc_tbl_releases() . " r LEFT JOIN " . dc_tbl_codes() . " c ON c.release = r.ID WHERE r.ID = $get_release GROUP BY r.ID, r.filename, r.title, c.code_prefix, c.final ORDER BY c.code_prefix" );
	
	if ( sizeof($codes) > 0) {
	
		// Get release
		$release = $codes[0];
		
		// Generate new codes
		$post_prefix = strtoupper(trim( $_POST['prefix'] ));
		$post_codes = trim( $_POST['codes'] );
		$post_characters = trim( $_POST['characters'] );
		if ( isset( $_POST['submit'] ) && $post_prefix != '' && is_numeric( $post_codes ) && is_numeric( $post_characters ) ) {
			
			// Creates desired number of random codes
			for ( $i = 0; $i < $post_codes; $i++ ) {
			
				// Create random str
				$code_unique = false;
				while ( !$code_unique ) {
					$suffix = rand_str( $post_characters );
					
					// Check if code already exists
					$code_db = $wpdb->get_row( "SELECT ID FROM " . dc_tbl_codes() . " WHERE code_prefix = `$post_prefix` AND code_suffix = `$suffix` AND `release` = " . $release->ID );
					$code_unique = ( sizeof( $code_db ) == 0);			
				}
				
				// Insert code
				$wpdb->insert(	dc_tbl_codes(), 
								array( 'code_prefix' => $post_prefix, 'code_suffix' => $suffix, 'release' => $release->ID ),
								array( '%s', '%s', '%d' ) );
			}
			
			// Refresh list of codes
			$codes = $wpdb->get_results( "SELECT r.ID, r.title, r.filename, c.code_prefix, c.final, COUNT(c.ID) AS codes, MIN(c.code_suffix) as code_example FROM " . dc_tbl_releases() . " r LEFT JOIN " . dc_tbl_codes() . " c ON c.release = r.ID WHERE r.ID = $get_release GROUP BY r.ID, r.filename, r.title, c.code_prefix, c.final ORDER BY c.code_prefix" );
			$release = $codes[0];
		}
	
		// Subtitle
		echo '<h3>' . $release->title . ' (' . $release->filename . ')</h3>';
		
		echo '<table class="widefat">';
		
		echo '<thead>';
		echo '<tr><th>Prefix</th><th>Final</th><th># Codes</th><th>Example</th><th>Actions</th></tr>';
		echo '</thead>';
		
		// List codes
		if ($release->code_prefix != '') {
			echo '<tbody>';
			foreach ($codes as $code) {
				echo '<tr><td>' . $code->code_prefix . '</td><td>' . ( $code->final == 1 ? "Yes" : "No" ) . '</td>';
				echo '<td>' . $code->codes . '</td>';
				echo '<td>' . $code->code_example . '</td>';
				echo '<td>';
				
				// Link to make codes final/delete codes or to export final codes
				if ( $code->final == 0 ) {
					echo '<a href="admin.php?page=dc-manage-codes&amp;release=' . $release->ID . '&amp;prefix=' . $code->code_prefix . '&amp;action=make-final">Make final</a> | ';
					echo '<a href="admin.php?page=dc-manage-codes&amp;release=' . $release->ID . '&amp;prefix=' . $code->code_prefix . '&amp;action=delete">Delete</a>';
				}
				else {
					echo '<a href="admin.php?page=dc-manage-codes&amp;release=' . $release->ID . '&amp;prefix=' . $code->code_prefix . '&amp;action=export">Export</a> | ';
					echo '<a href="admin.php?page=dc-manage-codes&amp;release=' . $release->ID . '&amp;prefix=' . $code->code_prefix . '&amp;action=analyze">Analyze</a>';
				}
				
				echo '</td></tr>';
			}
			echo '</tbody>';
		}
		
		echo '<tfoot>';
		echo '<tr><th>Prefix</th><th>Final</th><th># Codes</th><th>Example</th><th>Actions</th></tr>';
		echo '</tfoot>';
	
		echo '</table>';
	
		// Show form to add codes
		echo '<form method="post" action="admin.php?page=dc-manage-codes">';
		echo '<input type="hidden" name="release" value="' . $release->ID . '" />';
	
		echo '<table class="form-table">';
		
		echo '<tr valign="top">';
		echo '<th scope="row"><strong>Generate new codes</strong></th>';
		echo '<td>Prefix <input type="text" name="prefix" value="' . $post_prefix . '" /></td>';
		echo '<td># of Codes <input type="text" name="codes" size="4" maxlength="4" value="' . $post_codes .'" /></td>';
		echo '<td># of random characters <input type="text" name="characters" size="2" maxlength="2" value="' . ( $post_characters != '' ? $post_characters : '8' ) .'" /></td>';
		echo '<td><input type="submit" name="submit" class="button-secondary" value="Generate" /></td>';
		echo '</tr>';
		echo '</table>';
	
		echo '</form>';
	}
	
	// Show codes to be exported or downloas to be analyzed
	if ( $get_action == 'export' ) {
	
		// Export codes
		echo '<div id="message" class="updated fade">';
		
		$codes = $wpdb->get_results( "SELECT r.title, c.code_prefix, c.code_suffix FROM " . dc_tbl_releases() . " r INNER JOIN " . dc_tbl_codes() . " c ON c.release = r.ID WHERE r.ID = $get_release AND c.code_prefix = '". $_GET['prefix'] . "' ORDER BY c.code_prefix, c.code_suffix" );
		
		foreach ( $codes as $code ) {
			echo $code->code_prefix . $code->code_suffix . "<br />";
		}
		
		echo '</div>';
	}
	elseif ( $get_action == 'analyze' ) {
	
		// List downloads
		echo '<div id="message" class="updated fade">';
		
		$downloads = $wpdb->get_results( "SELECT r.title, c.code_prefix, c.code_suffix, DATE_FORMAT(d.started_at, '%d.%m.%Y %H:%i') AS download_time FROM (" . dc_tbl_releases() . " r INNER JOIN " . dc_tbl_codes() . " c ON c.release = r.ID) INNER JOIN " . dc_tbl_downloads() . " d ON d.code = c.ID WHERE r.ID = $get_release AND c.code_prefix = '". $_GET['prefix'] . "' ORDER BY c.code_prefix, c.code_suffix " );
		
		foreach ( $downloads as $download ) {
			echo $download->code_prefix . $download->code_suffix . ' [' . $download->download_time . ']<br />';
		}
		
		echo '</div>';

		
	}
	}
	
	echo '</div>';
}

/**
 * Help page.
 */
function dc_admin_help() {
	echo '<div class="wrap">';
	echo '<h2>Download Codes &raquo; Help</h2>';
	
	echo '<p>Please visit the homepage of the plugin for more information: <a href="http://wordpress.org/extend/plugins/wp-download-codes/">http://wordpress.org/extend/plugins/wp-download-codes/</a>.</p>';
	
	echo '</div>';
}
?>