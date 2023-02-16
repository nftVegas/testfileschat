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

// This will add a new "Edit" link to each row in the table
echo '<td><a href="?page=pmpro-start-date-editor&action=edit&user_id=' . $user_id . '">Edit</a></td>';
if ( isset( $_GET['action'] ) && $_GET['action'] == 'edit' ) {
    pmpro_start_date_editor_edit_form();
}


function pmpro_start_date_editor_edit_form() {
    if ( isset( $_GET['user_id'] ) ) {
        $user_id = intval( $_GET['user_id'] );
        $user_info = get_userdata( $user_id );
        $start_date = get_user_meta( $user_id, 'pmpro_start_date', true );
        
        if ( ! empty( $start_date ) ) {
            echo '<h2>Edit Start Date for ' . $user_info->user_login . '</h2>';
            echo '<form method="post">';
            echo '<input type="hidden" name="user_id" value="' . $user_id . '">';
            echo '<label for="start_date">Start Date:</label>';
            echo '<input type="date" name="start_date" id="start_date" value="' . $start_date . '">';
            echo '<input type="submit" name="submit" value="Save" class="button-primary">';
            echo '</form>';
        } else {
            echo '<p>Start date not found for user.</p>';
        }
    } else {
        echo '<p>No user selected.</p>';
    }
}

function pmpro_edit_start_date( $user_id ) {
    global $wpdb;
    
    if ( isset( $_POST['pmpro_edit_start_date_nonce'] ) && wp_verify_nonce( $_POST['pmpro_edit_start_date_nonce'], 'pmpro_edit_start_date_' . $user_id ) ) {
        $new_start_date = sanitize_text_field( $_POST['pmpro_edit_start_date'] );
        
        // Update the start date in the database.
        $wpdb->update(
            $wpdb->pmpro_memberships_users,
            array( 'startdate' => $new_start_date ),
            array( 'user_id' => $user_id ),
            array( '%s' ),
            array( '%d' )
        );
        
        echo '<div class="updated"><p>Start date updated successfully.</p></div>';
    }
    
    $start_date = $wpdb->get_var( $wpdb->prepare( "SELECT startdate FROM $wpdb->pmpro_memberships_users WHERE user_id = %d", $user_id ) );
    
    echo '<h2>Edit Start Date for User ID ' . $user_id . '</h2>';
    echo '<form method="post">';
    wp_nonce_field( 'pmpro_edit_start_date_' . $user_id, 'pmpro_edit_start_date_nonce' );
    echo '<p><label for="pmpro_edit_start_date">New Start Date:</label> <input type="text" id="pmpro_edit_start_date" name="pmpro_edit_start_date" value="' . esc_attr( $start_date ) . '"></p>';
    echo '<p><input type="submit" value="Update Start Date"></p>';
    echo '</form>';
}
