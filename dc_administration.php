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
				   `filename` varchar(100) NOT NULL,
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
	delete_option( 'dc_msg_code_enter' );
	delete_option( 'dc_msg_code_valid' );
	delete_option( 'dc_msg_code_invalid' );
	delete_option( 'dc_msg_max_downloads_reached' );
	delete_option( 'dc_msg_max_attempts_reached' );
	delete_option( 'dc_file_location' );
	delete_option( 'dc_file_types' );
	
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
	add_menu_page( 'Manage Releases', 'Download Codes', 8, 'dc-manage-releases', 'dc_admin_releases');
	
	// Manage releases
	add_submenu_page( 'dc-manage-releases', 'Manage Releases', 'Manage Releases', 8, 'dc-manage-releases', 'dc_admin_releases');
	
	// Manage codes
	add_submenu_page( 'dc-manage-releases', 'Manage Download Codes', 'Manage Codes', 8, 'dc-manage-codes', 'dc_admin_codes');
	
	// General settings
	add_submenu_page( 'dc-manage-releases', 'Download Code Settings', 'Settings', 8, 'dc-manage-settings', 'dc_admin_settings');
	
	// Help
	add_submenu_page( 'dc-manage-releases', 'Download Codes Help', 'Help', 8, 'dc-help', 'dc_admin_help');
}

/**
 * General settings.
 */
function dc_admin_settings() {
	echo '<div class="wrap">';
	echo '<h2>Download Codes &raquo; Settings</h2>';
	
	// Overwrite existing options
	if ( isset( $_POST['submit'] ) ) {
		$dc_file_location = trim( ( '' != trim($_POST['dc_file_location_abs']) ? $_POST['dc_file_location_abs'] : $_POST['dc_file_location'] ) );
		$dc_max_attempts = $_POST['dc_max_attempts'];
		
		// Update zip location
		if ( $dc_file_location != '' ) {
			if ( substr( $dc_file_location, -1 ) != '/') {
				$dc_file_location .= '/';
			}

			update_option( 'dc_file_location', $dc_file_location );
		}
		
		// Update number of maximum attempts
		if ( is_numeric( $dc_max_attempts ) ) {
			update_option( 'dc_max_attempts' , $dc_max_attempts );
		}
		
		// Update messages
		update_option( 'dc_msg_code_enter' , $_POST['dc_msg_code_enter'] );
		update_option( 'dc_msg_code_valid' , $_POST['dc_msg_code_valid'] );
		update_option( 'dc_msg_code_invalid' , $_POST['dc_msg_code_invalid'] );
		update_option( 'dc_msg_max_downloads_reached' , $_POST['dc_msg_max_downloads_reached'] );
		update_option( 'dc_msg_max_attempts_reached' , $_POST['dc_msg_max_attempts_reached'] );
		
		// Update file types
		if ( '' != trim( $_POST['dc_file_types'] ) ) {
			update_option( 'dc_file_types' , trim( $_POST['dc_file_types'] ) );
		}
		
		// Print message
		echo '<div id="message" class="updated fade">';
		echo __( 'Options saved.' );
		echo '</div>';
	}
	
	echo '<form method="post" action="">';

	echo '<table class="form-table">';

	/**
	 * Location of download files
	 */
	
	echo '<tr valign="top">';
	echo '<th scope="row">Location of download files</th>';
	
	if ( '' == get_option( 'dc_file_location' ) || ( '' != get_option( 'dc_file_location' ) && '/' != substr( get_option( 'dc_file_location' ), 0, 1 ) ) ) {
		// If current location of download files is empty or relative, try to locate the upload folder
		$wp_upload_dir = wp_upload_dir();
		$files = scandir( $wp_upload_dir['basedir'] );	
		
		echo '<td>' . $wp_upload_dir['basedir']  . '/ <select name="dc_file_location" id="dc_file_location">';
		foreach ($files as $folder) {
			if ( is_dir( $wp_upload_dir['basedir'] . '/' . $folder ) && $folder != '.' && $folder != '..' ) {
				echo '<option' . ( $folder . '/' == get_option( 'dc_file_location' ) ? ' selected="selected"' : '' ) . '>' . $folder . '</option>';
			}
		}
		echo '</select>';
		
		// Provide possibility to define upload path directly
		echo '<br /><br />';
		echo 'If the upload folder cannot be determined or if the release management does not work somehow, or if you want to have another download file location, you can specify the absolute path of the download file location here:<br />';
		echo '<input type="text" name="dc_file_location_abs" size="100" / >';
		
		echo '</td>';
	}
	else {
		echo '<td><input type="text" name="dc_file_location" size="100" value="' . get_option( 'dc_file_location' ) . '" /></td>';
	}
	
	echo '</tr>';
	
	echo '<tr valign="top">';
	echo '<th scope="row">Maximum invalid download attempts</th>';
	echo '<td><input type="text" name="dc_max_attempts" size="10" value="' . ( get_option( 'dc_max_attempts' ) == '' ? 3 : get_option( 'dc_max_attempts' ) ) . '" /></td>';
	echo '</tr>';
	
	echo '<tr valign="top">';
	echo '<th scope="row">List of allowed file types (separated by comma)</th>';
	echo '<td><input type="text" name="dc_file_types" size="100" value="' . ( implode( ',', dc_file_types() ) ) . '" /></td>';
	echo '</tr>';
	
	echo '<tr valign="top">';
	echo '<th scope="row">Message "Enter code"</th>';
	echo '<td><input type="text" name="dc_msg_code_enter" size="100" value="' . dc_msg( 'code_enter' ) . '" /></td>';
	echo '</tr>';

	echo '<tr valign="top">';
	echo '<th scope="row">Message "Code valid"</th>';
	echo '<td><input type="text" name="dc_msg_code_valid" size="100" value="' . dc_msg( 'code_valid' ) . '" /></td>';
	echo '</tr>';

	echo '<tr valign="top">';
	echo '<th scope="row">Message "Code invalid"</th>';
	echo '<td><input type="text" name="dc_msg_code_invalid" size="100" value="' . dc_msg( 'code_invalid' ) . '" /></td>';
	echo '</tr>';

	echo '<tr valign="top">';
	echo '<th scope="row">Message "Maximum of downloads reached"</th>';
	echo '<td><input type="text" name="dc_msg_max_downloads_reached" size="100" value="' . dc_msg( 'max_downloads_reached' ) . '" /></td>';
	echo '</tr>';

	echo '<tr valign="top">';
	echo '<th scope="row">Message "Maximum of attempts reached"</th>';
	echo '<td><input type="text" name="dc_msg_max_attempts_reached" size="100" value="' . dc_msg( 'max_attempts_reached' ) . '" /></td>';
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
		$post_action = $_POST['action'];
		$post_title = trim($_POST['title']);
		$post_filename = $_POST['filename'];
		$post_release = $_POST['release'];
		$post_downloads = $_POST['downloads'];
		
		// Check if all fields have been filled out properly
		if ( '' == $post_title ) {
			$message = "The title must not be empty.<br />";	
		}
		if ( '' == $post_filename ) {
			$message .= "Please choose a valid file for this release.<br />";	
		}
		if ( !is_numeric( $post_downloads ) ) {
			$message .= '"Allowed downloads" must be a number.<br />';
		}
		
		// Update or insert if no errors occurred.
		if ( '' == $message ) {			
			if ( $post_action == 'new') {
				$wpdb->insert(	dc_tbl_releases(), 
							array( 'title' => $post_title, 'filename' => $post_filename, 'allowed_downloads' => $post_downloads),
							array( '%s', '%s', '%d') );
												
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
			$get_action = $post_action;
			$get_release = $post_release;
		}
	}

	if ( $get_action == 'edit' || $get_action == 'new' ) {
	
		//*********************************************
		// Add/Edit release
		//*********************************************
		
		// Get zip files in download folder
		$files = scandir( dc_file_location() );
		
		// Get current release
		if ( '' != $get_release ) {
			$release = $wpdb->get_row( "SELECT * FROM " . dc_tbl_releases() . " WHERE ID = $get_release ");
		}
		
		// Write page subtitle
		echo '<h3>' . ( ( 'new' == $get_action ) ? 'Add New' : 'Edit' ) . ' Release</h3>';
		
		// Check if there are any file in the download folder
		foreach ($files as $filename) {
			if ( in_array(strtolower( substr($filename,-3) ), dc_file_types() ) ) {
				$num_download_files++;
			}
		}
		if ( $num_download_files == 0) {
			$message .= 'No file has been uploaded yet. You may want to upload one or several files to <em>' . dc_file_location() . '</em> first before you add a release.';
		}
		
		// Print the message
		if ( '' != $message) {
			echo '<div id="message" class="updated fade">';
			echo __( $message );
			echo '</div>';
		}

		echo '<form method="post" action="admin.php?page=dc-manage-releases">';
		echo '<input type="hidden" name="release" value="' . $release->ID . '" />';
		echo '<input type="hidden" name="action" value="' . $get_action . '" />';
		
		echo '<table class="form-table">';
		
		echo '<tr valign="top">';
		echo '<th scope="row">Title</th>';
		echo '<td><input type="text" name="title" size="100" value="' . $release->title . '" />';
		echo '</tr>';
		
		echo '<tr valign="top">';
		echo '<th scope="row">File</th>';
		echo '<td>' . dc_file_location() . ' <select name="filename">';
		foreach ($files as $filename) {
			if ( in_array(strtolower( substr($filename,-3) ), dc_file_types() ) ) {
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
		
		// Check if the releases are empty
		if ( sizeof( $releases ) == 0) {
			echo '<div id="message" class="updated fade">No release has been created yet.</div>';
			echo '<p>You may want to <a href="admin.php?page=dc-manage-releases&action=new">add a new release</a> first.</p>';
		}
		else {
		
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
		}

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
		$wpdb->show_errors();
		/*$wpdb->update( dc_tbl_codes(), 
						array( 'final' => 1 ),
						array( 'release' => (int) $get_release, 'code_prefix' => $_GET['prefix'] ),
						array( '%d' ),
						array( '%d', '%s' ) );*/
		$wpdb->query( "UPDATE " . dc_tbl_codes() . " SET `final` = 1 WHERE `release` = " . $get_release . " AND code_prefix = '". $_GET['prefix'] . "'" );
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
		echo '<p><a class="button-secondary" href="' . str_replace('&action=new', '', $_SERVER["REQUEST_URI"]) . '&amp;action=new">Add new release</a></p>';		
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
			$post_code_reset = $_POST['code_reset'];
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
		
			// Reset code(s)
			if ( isset( $_POST['submit'] ) && $post_code_reset != '' ) {
				
				// Delete downloads
				$wpdb->query( "DELETE FROM " . dc_tbl_downloads() . " WHERE `code` = (SELECT ID FROM " . dc_tbl_codes() . " WHERE `release` = $get_release " . ( $post_code_reset != 'All' ? " AND CONCAT(code_prefix, code_suffix) ='" . $post_code_reset . "'" : "" ) . ")" );

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
			
			// Show form to reset codes
			echo '<form method="post" action="admin.php?page=dc-manage-codes">';
			echo '<input type="hidden" name="release" value="' . $release->ID . '" />';
			echo '<table class="form-table">';
			echo '<tr valign="top">';
			echo '<th scope="row"><strong>Reset downloads</strong></th>';
			echo '<td>Code ';
			echo '<select name="code_reset"><option value="">--- Select code ---</option><option>All</option>';
			$codes = $wpdb->get_results( "SELECT r.title, c.code_prefix, c.code_suffix FROM " . dc_tbl_releases() . " r INNER JOIN " . dc_tbl_codes() . " c ON c.release = r.ID WHERE r.ID = $get_release ORDER BY c.code_prefix, c.code_suffix" );
			foreach ( $codes as $code ) {
				echo '<option>' . $code->code_prefix . $code->code_suffix . '</option>';
			}
			echo '</select> <input type="submit" name="submit" class="button-secondary" value="Reset" /></td>';
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