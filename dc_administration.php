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

$plugin_dir = plugins_url() . '/' . dirname( plugin_basename(__FILE__) );

/**
 * Initializes the download codes (dc) plugin.
 */
function dc_init() {
	global $wpdb;

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	
	$sql = "CREATE TABLE `" . dc_tbl_codes() . "` (
				   `ID` int(11) NOT NULL auto_increment,
				   `group` int(11) NOT NULL,
				   `code_prefix` varchar(20) NOT NULL,
				   `code_suffix` varchar(20) NOT NULL,
				   `release` int(11) NOT NULL,
				   `final` int(1) NOT NULL,
					PRIMARY KEY  (`ID`)
				 );";
	dbDelta( $sql );

	$sql = "CREATE TABLE `" . dc_tbl_code_groups() . "` (
				   `ID` int(11) NOT NULL auto_increment,
				   `release` int(11) NOT NULL,
					PRIMARY KEY  (`ID`)
				 );";
	dbDelta( $sql );
	
	$sql = "CREATE TABLE `" . dc_tbl_downloads() . "` (
				   `ID` int(11) NOT NULL auto_increment,
				   `IP` varchar(20) NOT NULL,
				   `started_at` timestamp NOT NULL,
				   `code` int(11) NOT NULL,
					PRIMARY KEY  (`ID`)
				 );";
	dbDelta( $sql );

	$sql = "CREATE TABLE `" . dc_tbl_releases() . "` (
				   `ID` int(11) NOT NULL auto_increment,
				   `title` varchar(100) NOT NULL,
				   `artist` varchar(100) NOT NULL,
				   `filename` varchar(100) NOT NULL,
				   `allowed_downloads` int(11) NOT NULL,
				   PRIMARY KEY  (`ID`)
				 );";
	dbDelta( $sql );
	
	// In version 2.0, code groups were introduced, therefore when
	// upgrading from a prior version, it has to be ensured
	// that initial code groups are created for every group
	// of code prefixes
	
	// Retrieve all codes without a code group
	$sql = "
		SELECT	DISTINCT c.code_prefix AS `prefix`, c.release AS `release`
		FROM	". dc_tbl_codes() . " c
		WHERE	c.group IS NULL OR c.group = 0";
	$code_groups = $wpdb->get_results( $sql );
		
	foreach ( $code_groups as $code_group ) {
		// Create a new code group
		$wpdb->insert(	dc_tbl_code_groups(), array( 'release' => $code_group->release ), array ( '%d' ));
		
		// Get the id of the new code group
		$code_group_id = $wpdb->insert_id;
		
		// Update the affected codes with the new code group id
		$wpdb->update(	dc_tbl_codes(), 
						array( 'group' => $code_group_id ),
						array( 'code_prefix' => $code_group->prefix, 'release' => $code_group->release ),
						array( '%d' ),
						array( '%s', '%d' ));
	}	
	
	// Set current plugin version (for future use)
	update_option( 'dc_version', '2.0' );
}

/**
 * Uninstalls the dc plugin.
 */
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
	delete_option( 'dc_version' );
	
	// Delete database tables
	$wpdb->query( "DROP TABLE " . dc_tbl_downloads() );
	$wpdb->query( "DROP TABLE " . dc_tbl_codes() );
	$wpdb->query( "DROP TABLE " . dc_tbl_code_groups() );
	$wpdb->query( "DROP TABLE " . dc_tbl_releases() );
}

/**
 * Creates the dc admin menu.
 * Hooked with the administration menu.
 */
function dc_admin_menu() {

	global $plugin_dir;
	$hooknames = array();
	
	// Main menu
	$hooknames[] = add_menu_page( 'Manage Releases', 'Download Codes', 'manage_options', 'dc-manage-releases', 'dc_admin_releases', $plugin_dir . '/resources/icon.png' );
	
	// Manage releases
	$hooknames[] = add_submenu_page( 'dc-manage-releases', 'Manage Releases', 'Manage Releases', 'manage_options', 'dc-manage-releases', 'dc_admin_releases' );
	
	// Manage codes
	$hooknames[] = add_submenu_page( 'dc-manage-releases', 'Manage Download Codes', 'Manage Codes', 'manage_options', 'dc-manage-codes', 'dc_admin_codes' );
	
	// General settings
	$hooknames[] = add_submenu_page( 'dc-manage-releases', 'Download Code Settings', 'Settings', 'manage_options', 'dc-manage-settings', 'dc_admin_settings' );
	
	// Help
	$hooknames[] = add_submenu_page( 'dc-manage-releases', 'Download Codes Help', 'Help', 'manage_options', 'dc-help', 'dc_admin_help' );
	
	// Load external files
	foreach ( $hooknames as $hookname ) 
	{
		add_action( "admin_print_scripts-$hookname", 'dc_resources_admin_head' );	
	}
}

/**
 * Adds JS and CSS to WP header for dc pages.
 */
function dc_resources_admin_head() {
	global $plugin_dir;
	
	wp_enqueue_script( 'dc_plugin_js', $plugin_dir . '/resources/js/wp-download-codes.js', 'jquery', '1.0' );
	echo '<link rel="stylesheet" type="text/css" href="' . $plugin_dir . '/resources/css/wp-download-codes.css" type="text/css" />' . "\n";
}

/**
 * General settings.
 */
function dc_admin_settings() {
	echo '<div class="wrap">';
	echo '<h2>Download Codes &raquo; Settings</h2>';
	
	// Overwrite existing options
	if ( isset( $_POST['submit'] ) ) {
		$dc_file_location = trim( ( '' != trim( $_POST['dc_file_location_abs'] ) ? $_POST['dc_file_location_abs'] : $_POST['dc_file_location'] ) );
		$dc_max_attempts = $_POST['dc_max_attempts'];
		
		// Update zip location
		if ( $dc_file_location != '' ) {
			if ( substr( $dc_file_location, -1 ) != '/' ) {
				$dc_file_location .= '/';
			}

			update_option( 'dc_file_location', $dc_file_location );
		}
		
		// Update number of maximum attempts
		if ( is_numeric( $dc_max_attempts ) ) {
			update_option( 'dc_max_attempts' , $dc_max_attempts );
		}
		
		// Update character list
		update_option( 'dc_code_chars' , $_POST['dc_code_chars'] == '' ? DC_CODE_CHARS : $_POST['dc_code_chars'] );
		
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
		echo dc_admin_message( 'The settings were updated.' );	
	}
	
	echo '<form action="admin.php?page=dc-manage-settings" method="post">';

	echo '<h3>File Settings</h3>';

	echo '<table class="form-table">';

	/**
	 * Location of download files
	 */
	
	echo '<tr valign="top">';
	echo '<th scope="row"><label for="settings-location">Location of download files</label></th>';
	
	if ( '' == get_option( 'dc_file_location' ) || ( '' != get_option( 'dc_file_location' ) && '/' != substr( get_option( 'dc_file_location' ), 0, 1 ) ) ) {
		// If current location of download files is empty or relative, try to locate the upload folder
		$wp_upload_dir = wp_upload_dir();
		$files = scandir( $wp_upload_dir['basedir'] );	
		
		echo '<td>' . $wp_upload_dir['basedir']  . '/ <select name="dc_file_location" id="settings-location">';
		foreach ( $files as $folder ) {
			if ( is_dir( $wp_upload_dir['basedir'] . '/' . $folder ) && $folder != '.' && $folder != '..' ) {
				echo '<option' . ( $folder . '/' == get_option( 'dc_file_location' ) ? ' selected="selected"' : '' ) . '>' . $folder . '</option>';
			}
		}
		echo '</select>';
		
		// Provide possibility to define upload path directly
		echo '<p>If the upload folder cannot be determined or if the release management does not work (or if you want to have another download file location) you may specify the absolute path of the download file location here:</p>';
		echo '<input type="text" name="dc_file_location_abs" class="large-text" / >';
		
		echo '</td>';
	}
	else {
		echo '<td><input type="text" name="dc_file_location" id="settings-location" class="large-text" value="' . get_option( 'dc_file_location' ) . '" /></td>';
	}
	
	echo '</tr>';
	
	echo '<tr valign="top">';
	echo '<th scope="row"><label for="settings-max">Maximum attempts</label></th>';
	echo '<td><input type="text" name="dc_max_attempts" id="settings-max" class="small-text" value="' . dc_max_attempts() . '" />';
	echo ' <span class="description">Maximum invalid download attempts</span></td>';	
	echo '</tr>';
	
	echo '<tr valign="top">';
	echo '<th scope="row"><label for="settings-filetypes">Allowed file types</label></th>';
	echo '<td><input type="text" name="dc_file_types" id="settings-filetypes" class="regular-text" value="' . ( implode( ', ', dc_file_types() ) ) . '" />';
	echo ' <span class="description">Separated by comma</span></td>';	
	echo '</tr>';
	
	echo '<tr valign="top">';
	echo '<th scope="row"><label for="settings-chars">Allowed characters</label></th>';
	echo '<td><input type="text" name="dc_code_chars" id="settings-chars" class="regular-text" value="' . dc_code_chars() . '" />';
	echo ' <span class="description">Codes will contain a random mix of these characters</span></td>';	
	echo '</tr>';
	
	echo '</table>';
	
	
	echo '<h3>Messages</h3>';
	
	echo '<p>Specify custom messages that your users see while downloading releases:</p>';

	echo '<table class="form-table">';
	
	echo '<tr valign="top">';
	echo '<th scope="row"><label for="settings-msg-enter">"Enter code"</label></th>';
	echo '<td><input type="text" name="dc_msg_code_enter" id="settings-msg-enter" class="large-text" value="' . dc_msg( 'code_enter' ) . '" /></td>';
	echo '</tr>';

	echo '<tr valign="top">';
	echo '<th scope="row"><label for="settings-msg-valid">"Code valid"</label></th>';
	echo '<td><input type="text" name="dc_msg_code_valid" id="settings-msg-valid" class="large-text" value="' . dc_msg( 'code_valid' ) . '" /></td>';
	echo '</tr>';

	echo '<tr valign="top">';
	echo '<th scope="row"><label for="settings-msg-invalid">"Code invalid"</label></th>';
	echo '<td><input type="text" name="dc_msg_code_invalid" id="settings-msg-invalid" class="large-text" value="' . dc_msg( 'code_invalid' ) . '" /></td>';
	echo '</tr>';

	echo '<tr valign="top">';
	echo '<th scope="row"><label for="settings-msg-downloads">"Maximum downloads reached"</label></th>';
	echo '<td><input type="text" name="dc_msg_max_downloads_reached" id="settings-msg-downloads" class="large-text" value="' . dc_msg( 'max_downloads_reached' ) . '" /></td>';
	echo '</tr>';

	echo '<tr valign="top">';
	echo '<th scope="row"><label for="settings-msg-attempts">"Maximum attempts reached"</label></th>';
	echo '<td><input type="text" name="dc_msg_max_attempts_reached" id="settings-msg-attempts" class="large-text" value="' . dc_msg( 'max_attempts_reached' ) . '" /></td>';
	echo '</tr>';
	
	echo '</table>';
	
	echo '<p class="submit">';
	echo '<input type="submit" name="submit" class="button-primary" value="Save Changes" />';
	echo '</p>';
	echo '</form>';

	echo '</div>';
}

/**
 * Manage releases.
 */
function dc_admin_releases() {
	global $wpdb;
	
	$wpdb->query('SET OPTION SQL_BIG_SELECTS = 1');

	// Get parameters
	$get_action 	= $_GET['action'];
	$get_release 	= $_GET['release'];

	// Post parameters
	$post_action = $_POST['action'];
	$post_release = $_POST['release'];
	
	// Show page title
	echo '<div class="wrap">';
	echo '<h2>Download Codes &raquo; Manage Releases</h2>';

	switch( $get_action )
	{
		case 'edit':
		case 'add':
			// Update or insert release
			if ( isset($_POST['submit']) ) {
				
				if ( $post_action == 'add' ) {
					$result = dc_add_release();
					if ( is_array($result) )
					{
						echo dc_admin_message( implode( '</p><p>', $result ) );
					} 
					else {
						if ( $result === FALSE )
							echo dc_admin_message( 'There was an error adding the release' ); 
						else {
							echo dc_admin_message( 'The release was added successfully' );
							$add_success = true;
						}
					}
				}
				if ( $post_action == 'edit' ) {
					$result = dc_edit_release();
					if ( is_array($result) )
					{
						// display errors
					} 
					else {
						if ( $result === FALSE )
							echo dc_admin_message( 'There was an error updating the release' ); 
						else {
							echo dc_admin_message( 'The release was updated successfully' );			
							$edit_success = true;
						}
					}
				}
			}
			break;
		case 'delete':
			$result = dc_delete_release( $get_release );
			if ( $result ) {
				echo dc_admin_message( 'The release was deleted successfully' );
			} 
			else {
				echo dc_admin_message( 'There was an error deleting the release' );
			}
			break;
	}

	if ( ( $get_action == 'edit' || $get_action == 'add' ) && !$add_success ) {

		//*********************************************
		// Add or edit a release
		//*********************************************
	
		// Get zip files in download folder
		$files = scandir( dc_file_location() );
		foreach ( $files as $filename ) {
			if ( in_array(strtolower( substr($filename,-3) ), dc_file_types() ) ) {
				$num_download_files++;
			}
		}
		if ( $num_download_files == 0) {
			echo dc_admin_message( 'No files have been uploaded to the releases folder: <em>' . dc_file_location() . '</em></p><p><strong>You must do this first before adding a release!</strong>' );
		}
		
		// Get current release
		if ( '' != $get_release ) {
			$release = dc_get_release( $get_release );
		}
		if ( '' != $post_release ) {
			$release = dc_get_release( $post_release );
		}
		
		// Write page subtitle
		echo '<h3>' . ( ( 'add' == $get_action ) ? 'Add New' : 'Edit' ) . ' Release</h3>';
		echo '<p><a href="admin.php?page=dc-manage-releases">&laquo; Back to releases</a></p>';
		
				
		// Display form
		echo '<form action="admin.php?page=dc-manage-releases&action=' . $get_action . '" method="post">';
		echo '<input type="hidden" name="release" value="' . $release->ID . '" />';
		echo '<input type="hidden" name="action" value="' . $get_action . '" />';
		
		echo '<table class="form-table">';
		
		// title
		echo '<tr valign="top">';
		echo '<th scope="row"><label for="release-title">Title</label></th>';
		echo '<td><input type="text" name="title" id="release-title" class="regular-text" value="' . $release->title . '" />';
		echo ' <span class="description">For example, the album title</span></td>';
		echo '</tr>';
		
		// artist
		echo '<tr valign="top">';
		echo '<th scope="row"><label for="release-artist">Artist (optional)</label></th>';
		echo '<td><input type="text" name="artist" id="release-artist" class="regular-text" value="' . $release->artist . '" />';
		echo ' <span class="description">The band or artist</span></td>';
		echo '</tr>';
		
		// file
		echo '<tr valign="top">';
		echo '<th scope="row"><label for="release-file">File</label></th>';
		echo '<td>' . dc_file_location() . ' <select name="filename" id="release-file">';
		foreach ( $files as $filename ) {
			if ( in_array(strtolower( substr($filename, -3) ), dc_file_types() ) ) {
				echo '<option' . ( $filename == $release->filename ? ' selected="selected"' : '' ) . '>' . $filename . '</option>';
			}
		}
		echo '</select></td>';
		echo '</tr>';
		
		// allowed downloads
		echo '<tr valign="top">';
		echo '<th scope="row"><label for="release-downloads">Allowed downloads</label></th>';
		echo '<td><input type="text" name="downloads" id="release-downloads" class="small-text" value="' . ( $release->allowed_downloads > 0 ? $release->allowed_downloads : DC_ALLOWED_DOWNLOADS ) . '" />';
		echo ' <span class="description">Maximum number of times each code can be used</span></td>';
		echo '</tr>';
		
		echo '</table>';
		
		// submit
		echo '<p class="submit">';
		echo '<input type="submit" name="submit" class="button-primary" value="' . ( $get_action == 'edit' ? 'Save Changes' : 'Add Release' ) . '" />';
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
		$releases = dc_get_releases();
		
		// Check if the releases are empty
		if ( sizeof( $releases ) == 0) {
			echo dc_admin_message( 'No releases have been created yet' );
			echo '<p>You might want to <a href="admin.php?page=dc-manage-releases&action=add">add a new release</a></p>';
		}
		else {
		
			echo '<table class="widefat">';
			
			echo '<thead>';
			echo '<tr><th>Title</th><th>Artist</th><th>File</th><th>Codes</th><th>Downloaded</th><th>Actions</th></tr>';
			echo '</thead>';
			
			echo '<tbody>';
			foreach ( $releases as $release ) {
				echo '<tr>';
				echo '<td><strong>' . $release->title . '</strong></td><td>' . $release->artist . '</td>';
				echo '<td>' . $release->filename . '</td>';
				echo '<td>' . $release->codes . '</td><td>' . $release->downloads . '</td>';
				echo '<td>';
				echo '<a href="' . $_SERVER["REQUEST_URI"] . '&amp;release=' . $release->ID . '&amp;action=edit" class="action-edit">Edit</a> | ';
				echo '<a href="admin.php?page=dc-manage-codes&amp;release=' . $release->ID . '" class="action-manage">Manage codes</a> | '; 
				echo '<a href="admin.php?page=dc-manage-codes&amp;release=' . $release->ID . '&amp;action=report" class="action-report" rel="dc_downloads-all">View report</a> | ';
				echo '<a href="' . $_SERVER["REQUEST_URI"] . '&amp;release=' . $release->ID . '&amp;action=delete" class="action-delete">Delete</a>';
				echo '</td>';
				echo '</tr>';
			}
			echo '</tbody>';
			
			echo '<tfoot>';
			echo '<tr><th>Title</th><th>Artist</th><th>File</th><th>Codes</th><th>Downloaded</th><th>Actions</th></tr>';
			echo '</tfoot>';

			echo '</table>';
			
			foreach ( $releases as $release ) {
				dc_list_downloads( $release->ID, null, FALSE );
			}
		}

		// Show link to add a new release
		echo '<p><a class="button-primary" href="' . str_replace( '&action=add', '', $_SERVER["REQUEST_URI"]) . '&amp;action=add">Add New Release</a></p>';		
	}
	
	echo '</div>';
}

/**
 * Manage download codes.
 */
function dc_admin_codes() {
	global $wpdb;
	
	$wpdb->query('SET OPTION SQL_BIG_SELECTS = 1');

	// Get parameters
	$get_release 	= $_GET['release'];
	$get_group		= $_GET['group'];
	$get_action 	= $_GET['action'];

	// Post parameters
	$post_release	= $_POST['release'];
	$post_action 	= $_POST['action'];
	
	// List of releases
	$releases = dc_get_releases();
	if ( $get_release == '' &&  $post_release == '' ) {
		$release_id = $releases[0]->ID;
	} 
	elseif ( $post_release != '') {
		$release_id = $post_release;
	}
	elseif ( $get_release != '') {
		$release_id = $get_release;
	}
	
	// Show page title
	echo '<div class="wrap">';
	echo '<h2>Download Codes &raquo; Manage Codes</h2>';
		
	switch( $get_action ) 
	{
		case 'make-final':
			$finalize_count = dc_finalize_codes( $release_id, $get_group );
			echo dc_admin_message( '' . $finalize_count . ' download code(s) were finalized' );
			
			break;
		case 'delete':
			$deleted_count = dc_delete_codes( $release_id, $get_group );
			echo dc_admin_message( '' . $deleted_count . ' download code(s) were deleted' );
		
			break;
		case 'generate':

			// Get codes for current release
			$codes 			= dc_get_code_groups( $release_id );
			$release 		= dc_get_release( $release_id );
			
			// Generate new codes
			$prefix 		= strtoupper(trim( $_POST['prefix'] ));
			$codes 			= trim( $_POST['codes'] );
			$characters 	= trim( $_POST['characters'] );
			
			$success 		= dc_generate_codes( $release, $prefix, $codes, $characters );
			
			if ( $success )
			{
				echo dc_admin_message( 'Download codes were successfully created' );
			} 
			else {
				echo dc_admin_message( 'Please fill out all fields before generating codes' );			
			}

			// Refresh list of codes
			$codes 			= dc_get_code_groups( $release_id );
			$release 		= dc_get_release( $release_id );
		
			break;
		case 'reset':
		
			// Code reset
			$code 			= $_POST['code_reset'];
			$reset_count	= dc_reset_codes( $release_id, $code );
			echo dc_admin_message( '' . $reset_count . ' download code(s) reset' );
		
			break;
	
	}
	
	if ( sizeof( $releases ) == 0) {
		// Display message if no release exists yet
		echo dc_admin_message( 'No releases have been created yet' );
		echo '<p><a class="button-secondary" href="' . str_replace( '&action=add', '', $_SERVER["REQUEST_URI"]) . '&amp;action=add">Add New Release</a></p>';		
	}
	else {
		// There are some releases	
		echo '<form action="admin.php?page=dc-manage-codes&action=select" method="post">';
		echo '<input type="hidden" name="action" value="select" />';
		
		// Display release picker
		echo '<h3>Select a Release: ';
		echo '<select name="release" id="release" onchange="submit();">';
		foreach ( $releases as $release ) {
			echo '<option value="' . $release->ID . '"' . ( $release->ID == $release_id ? ' selected="selected"' : '' ) . '>' . ( $release->artist ? $release->artist . ' - ' : '' ) . $release->title . ' (' . $release->filename . ')</option>';
		}
		echo '</select>';
		echo '</h3>';
		echo '</form>';
		
		// Get codes for current release
		$code_groups = dc_get_code_groups( $release_id );
		$release = $code_groups[0];
		
		if ( sizeof($code_groups) > 0) {
		
			// Subtitle
			echo '<h3>' . $release->artist . ' - ' . $release->title . ' (' . $release->filename . ')</h3>';
			
			echo '<table class="widefat dc_codes">';
			
			echo '<thead>';
			echo '<tr><th>Prefix</th><th>Finalized</th><th>Codes</th><th>Sample Code</th><th>Downloaded</th><th>Actions</th></tr>';
			echo '</thead>';
			
			// List codes
			echo '<tbody>';
			
			// Check that codes are actual data
			if ( $code_groups[0]->group != '' ) {	

				foreach ( $code_groups as $code_group ) {
					echo '<tr><td>' . $code_group->code_prefix . '</td><td>' . ( $code_group->final == 1 ? "Yes" : "No" ) . '</td>';
					echo '<td>' . $code_group->codes . '</td>';
					echo '<td>' . $code_group->code_prefix . $code_group->code_example . '</td>';
					echo '<td>' . $code_group->downloads . ' (' . $code_group->downloaded_codes . ' ' . ( $code_group->downloaded_codes == 1 ? 'code' : 'codes' ) . ')</td>';
					echo '<td>';
					
					// Link to make codes final/delete codes or to export final codes
					if ( $code_group->final == 0 ) {
						echo '<a href="admin.php?page=dc-manage-codes&amp;release=' . $release->ID . '&amp;group=' . $code_group->group . '&amp;action=make-final" class="action-finalize">Finalize</a> | ';
						echo '<a href="admin.php?page=dc-manage-codes&amp;release=' . $release->ID . '&amp;group=' . $code_group->group . '&amp;action=delete" class="action-delete">Delete</a>';
					}
					else {
						echo '<a href="admin.php?page=dc-manage-codes&amp;release=' . $release->ID . '&amp;group=' . $code_group->group . '&amp;action=list" class="action-list" rel="dc_list-' . $code_group->group . '">List codes</a> | ';
						echo '<a href="admin.php?page=dc-manage-codes&amp;release=' . $release->ID . '&amp;group=' . $code_group->group . '&amp;action=report" class="action-report" rel="dc_downloads-' . $code_group->group . '">View report</a>';
					}
					
					echo '</td></tr>';
				}
				
			}
			echo '</tbody>';
			
			echo '<tfoot>';
			echo '<tr><th>Prefix</th><th>Finalized</th><th>Codes</th><th>Sample Code</th><th>Downloaded</th><th>Actions</th></tr>';
			echo '</tfoot>';
		
			echo '</table>';
			
			// Output codes and downloads for lightbox option
			foreach ( $code_groups as $code_group ) 
			{
				dc_list_codes( $release_id, $code_group->group, FALSE );
				dc_list_downloads( $release_id, $code_group->group, FALSE );
			}
		
			// Show form to add codes
			echo '<form action="admin.php?page=dc-manage-codes&action=generate" method="post">';
			echo '<input type="hidden" name="action" value="generate" />';
			echo '<input type="hidden" name="release" value="' . $release->ID . '" />';
			
			echo '<h3>Generate New Batch of Codes</h3>';
			
			echo '<table class="form-table">';

			echo '<tr valign="top">';
			echo '<th scope="row"><label for="new-prefix">Code prefix</label></th>';
			echo '<td><input type="text" name="prefix" id="new-prefix" class="small-text" value="' . $post_prefix . '" />';
			echo ' <span class="description">First characters of each code</span></td>';
			echo '</tr>';

			echo '<tr valign="top">';
			echo '<th scope="row"><label for="new-quantity">Quantity</label></th>';
			echo '<td><input type="text" name="codes" id="new-quantity" class="small-text" maxlength="4" value="' . $post_codes .'" />';
			echo ' <span class="description">Number of codes to generate</span></td>';
			echo '</tr>';

			echo '<tr valign="top">';
			echo '<th scope="row"><label for="new-length">Length</label></th>';
			echo '<td><input type="text" name="characters" id="new-length" class="small-text" maxlength="2" value="' . ( $post_characters != '' ? $post_characters : '8' ) .'" />';
			echo ' <span class="description">Number of random characters each code contains</span></td>';
			echo '</tr>';

			echo '</table>';
			
			echo '<p class="submit">';
			echo '<input type="submit" name="submit" class="button-secondary" value="Generate Codes" />';
			echo '</p>';

			echo '</form>';
			
			// Show form to reset codes
			echo '<form action="admin.php?page=dc-manage-codes&action=reset" method="post">';
			echo '<input type="hidden" name="action" value="reset" />';
			echo '<input type="hidden" name="release" value="' . $release->ID . '" />';

			echo '<h3>Reset Download Code(s)</h3>';
			
			echo '<table class="form-table">';
			
			echo '<tr valign="top">';
			echo '<th scope="row"><label for="reset-code">Download code</label></th>';
			echo '<td><select name="code_reset" id="reset-code"><option value="">Select code...</option><option value="all">All codes</option>';
			
			$codes = dc_get_codes( $release_id );
			foreach ( $codes as $code ) {
				echo '<option>' . $code->code_prefix . $code->code_suffix . '</option>';
			}
			echo '</select></td>';
			echo '</tr>';

			echo '</table>';
			
			echo '<p class="submit">';
			echo '<input type="submit" name="submit" class="button-secondary" value="Reset Codes" />';
			echo '</p>';

			echo '</form>';

		}
		
		switch( $get_action )
		{
			case 'list':
				echo '<h3>List of Download Codes</h3>';
				dc_list_codes( $release_id, $get_group );
	
				break;
			case 'report':
				echo '<h3>Code Usage Report</h3>';
				dc_list_downloads( $release_id, $get_group);	
			
				break;
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