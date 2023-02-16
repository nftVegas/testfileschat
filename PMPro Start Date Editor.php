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
function pmpro_start_date_editor_menu() {
    add_submenu_page(
        'pmpro-membershiplevels',
        'Start Date Editor',
        'Start Date Editor',
        'manage_options',
        'pmpro-start-date-editor',
        'pmpro_start_date_editor_page'
    );
}
add_action( 'admin_menu', 'pmpro_start_date_editor_menu' );

// Display the admin page.
function pmpro_start_date_editor_page() {
    global $wpdb;
    
    // If the form has been submitted, update the start date for the user.
    if ( isset( $_POST['user_id'] ) && isset( $_POST['start_date'] ) ) {
        $user_id = intval( $_POST['user_id'] );
        $start_date = sanitize_text_field( $_POST['start_date'] );

        // Update the start date in the database.
        $wpdb->update(
            $wpdb->pmpro_memberships_users,
            array( 'startdate' => $start_date ),
            array( 'user_id' => $user_id ),
            array( '%s' ),
            array( '%d' )
        );
        
        // Output a success message.
        echo '<div class="updated"><p>Start date updated successfully.</p></div>';
    }

    // Output the edit form.
    echo '<div class="wrap">';
    echo '<h1>PMPro Start Date Editor</h1>';
    
    // Get a list of users and their start dates.
    $results = $wpdb->get_results( "SELECT user_id, startdate FROM $wpdb->pmpro_memberships_users" );
    
    if ( $results ) {
        echo '<table class="widefat">';
        echo '<thead><tr><th>User ID</th><th>Username</th><th>Start Date</th><th>Action</th></tr></thead>';
        echo '<tbody>';
        foreach ( $results as $row ) {
            $user_id = intval( $row->user_id );
            $username = get_userdata( $user_id )->user_login;
            $start_date = $row->startdate;
            echo '<tr>';
            echo '<td>' . $user_id . '</td>';
            echo '<td>' . $username . '</td>';
            echo '<td>' . $start_date . '</td>';
            echo '<td><a href="?page=pmpro-start-date-editor&action=edit&user_id=' . $user_id . '">Edit</a></td>';
            echo '</tr>';
        }
        echo '</tbody>';
        echo '</table>';
    } else {
        echo '<p>No users found.</p>';
    }

    echo '</div>';

    // Output the edit form.
    if ( isset( $_GET['action'] ) && $_GET['action'] == 'edit' && isset( $_GET['user_id'] ) ) {
        $user_id = intval( $_GET['user_id'] );
        $username = get_userdata( $user_id )->user_login;
        $start_date = $wpdb->get_var( $wpdb->prepare( "SELECT startdate FROM $wpdb->pmpro_memberships_users WHERE user_id = %d", $user_id ) );
        if ( $start_date ) {
            echo '<div class="wrap">';
            echo '<h1>PMPro Start Date Editor: Edit Start Date for ' . $username . '</h1>';
            echo '<form method="post">';
            echo '<input type="hidden" name="user_id" value="' . $user_id . '">';
            echo '<label for="start_date">Start Date:</label>';
            echo '<input type="text" id="start_date" name="start_date" value="' . esc_attr( $start_date ) . '">';
            echo '<input type="submit" class="button-primary" value="Update">';
            echo '</form>';
            echo '</div>';
        } else {
            echo '<p>No start date found for user with ID ' . $user_id . '.</p>';
        }
    }
}
