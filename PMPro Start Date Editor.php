<?php
/**
 * Plugin Name: PMPro Start Date Editor
 * Plugin URI: https://escapeplan.space
 * Description: A plugin to edit PMPro start dates.
 * Version: 1.0.0
 * Author: Janno Kurss
 * Author URI: https://escapeplan.space
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
	echo '</tr>';
	echo '</thead>';
	echo '<tbody>';
	foreach ( $results as $result ) {
		echo '<tr>';
		echo '<td>' . $result->user_id . '</td>';
		echo '<td>' . $result->startdate . '</td>';
		echo '</tr>';
	}
	echo '</tbody>';
	echo '</table>';
	echo '</div>';
}
