<?php
/*
Plugin Name: PMPro Start Date Viewer
Plugin URI: https://example.com
Description: A plugin to view PMPro start dates.
Version: 1.0
Author: Your Name
Author URI: https://example.com
License: GPL2
*/

// Add a new admin menu item.
function pmpro_start_date_viewer_menu() {
	add_menu_page(
		'PMPro Start Date Viewer',
		'Start Date Viewer',
		'manage_options',
		'pmpro-start-date-viewer',
		'pmpro_start_date_viewer_page'
	);
}
add_action( 'admin_menu', 'pmpro_start_date_viewer_menu' );

// Display the admin page.
function pmpro_start_date_viewer_page() {
	global $wpdb;

	if ( isset( $_POST['pmpro-start-date'] ) ) {
		$user_id = (int) $_POST['user-id'];
		$start_date = sanitize_text_field( $_POST['pmpro-start-date'] );

		// Update the start date in the database.
		$wpdb->update(
			$wpdb->pmpro_memberships_users,
			array( 'startdate' => $start_date ),
			array( 'user_id' => $user_id ),
			array( '%s' ),
			array( '%d' )
		);

		echo '<div class="notice notice-success"><p>Start date updated successfully!</p></div>';
	}

	// Get all users and their start dates from the database.
	$results = $wpdb->get_results(
		"
		SELECT user_id, startdate
		FROM $wpdb->pmpro_memberships_users
		"
	);

	// Display the table of users and start dates.
	echo '<div class="wrap">';
	echo '<h1>PMPro Start Date Viewer</h1>';
	echo '<table class="wp-list-table widefat fixed striped">';
	echo '<thead>';
	echo '<tr>';
	echo '<th>User ID</th>';
	echo '<th>Start Date</th>';
	echo '<th>Edit Start Date</th>';
	echo '</tr>';
	echo '</thead>';
	echo '<tbody>';
	foreach ( $results as $result ) {
		echo '<tr>';
		echo '<td>' . $result->user_id . '</td>';
		echo '<td>' . $result->startdate . '</td>';
		echo '<td>';
		echo '<form method="post">';
		echo '<input type="hidden" name="user-id" value="' . $result->user_id . '">';
		echo '<input type="text" name="pmpro-start-date" value="' . $result->startdate . '">';
		echo '<input type="submit" class="button" value="Update">';
		echo '</form>';
		echo '</td>';
		echo '</tr>';
	}
	echo '</tbody>';
	echo '</table>';
	echo '</div>';
}
