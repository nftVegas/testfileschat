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

	// Check if bulk update was submitted.
	if ( isset( $_POST['pmpro_bulk_update'] ) ) {
		// Get the new start date value from the form submission.
		$new_start_date = $_POST['pmpro_start_date'];

		// Get the selected user IDs.
		$user_ids = array_map( 'intval', $_POST['user_ids'] );

		// Update the start date for the selected users.
		$wpdb->query(
			$wpdb->prepare(
				"
				UPDATE {$wpdb->pmpro_memberships_users}
				SET startdate = %s
				WHERE user_id IN (" . implode( ',', $user_ids ) . ")
				",
				$new_start_date
			)
		);

		// Show a success message.
		echo '<div class="updated"><p>' . count( $user_ids ) . ' user(s) updated.</p></div>';
	}

	// Get all users and their start dates from the database.
	$results = $wpdb->get_results(
		"
		SELECT user_id, startdate, status
		FROM {$wpdb->pmpro_memberships_users}
		"
	);

	// Display the table of users and start dates.
	echo '<div class="wrap">';
	echo '<h1>PMPro Start Date Viewer</h1>';
	echo '<form method="post">';
	echo '<table class="wp-list-table widefat fixed striped">';
	echo '<thead>';
	echo '<tr>';
	echo '<th><input type="checkbox" id="pmpro_check_all"></th>';
	echo '<th>User ID</th>';
	echo '<th>Start Date</th>';
	echo '<th>Status</th>';
	echo '</tr>';
	echo '</thead>';
	echo '<tbody>';
	foreach ( $results as $result ) {
		echo '<tr>';
		echo '<td><input type="checkbox" class="pmpro_check" name="user_ids[]" value="' . $result->user_id . '"></td>';
		echo '<td>' . $result->user_id . '</td>';
		echo '<td><input type="text" name="pmpro_start_date[' . $result->user_id . ']" value="' . $result->startdate . '"></td>';
		echo '<td>' . $result->status . '</td>';
		echo '</tr>';
	}
	echo '</tbody>';
	echo '</table>';
	echo '<br>';
	echo '<input type="submit" name="pmpro_bulk_update" class="button button-primary" value="Update">';
	echo '</form>';
	echo '</div>';
}
