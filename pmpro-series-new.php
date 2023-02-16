<?php
/**
 * Plugin Name: Paid Memberships Pro - Series Add On
 * Plugin URI: https://www.paidmembershipspro.com/add-ons/pmpro-series-for-drip-feed-content/
 * Description: Offer serialized (drip feed) content to your PMPro members.
 * Version: .5
 * Author: Paid Memberships Pro
 * Author URI: https://www.paidmembershipspro.com
 * Text Domain: pmpro-series
 * Domain Path: /languages
 */

/*
	Includes
*/
require_once dirname( __FILE__ ) . '/classes/class.pmproseries.php';
require_once dirname( __FILE__ ) . '/scheduled/crons.php';

/*
	Load textdomain
*/
function pmpros_load_textdomain() {
	$locale = apply_filters( 'plugin_locale', get_locale(), 'pmpro-series' );
	load_textdomain( 'pmpro-series', trailingslashit( WP_LANG_DIR ) . basename( __DIR__ ) . '/languages/pmpro-series-' . $locale . '.mo');
	load_plugin_textdomain( 'pmpro-series', FALSE, basename( __DIR__ ) . '/languages/');
}
add_action( 'init', 'pmpros_load_textdomain' );


/**
 * [pmpros_scripts] Load frontend CSS file.
 *
 * @return void
 */
function pmpros_scripts() {
	wp_enqueue_style( 'pmpros_pmpro', plugins_url( 'css/pmpro_series.css', __FILE__ ) );
}
add_action( 'wp_enqueue_scripts', 'pmpros_scripts' );

/**
 * [pmpros_admin_scripts] Load admin JS files.
 *
 * @param  [type] $hook
 * @return void
 */
function pmpros_admin_scripts( $hook ) {
	if ( in_array( $hook, array( 'post.php', 'post-new.php' ) ) && 'pmpro_series' == get_post_type() ) {
		wp_enqueue_style( 'pmpros-select2', plugins_url( 'css/select2.css', __FILE__ ), '', '3.1', 'screen' );
		wp_enqueue_script( 'pmpros-select2', plugins_url( 'js/select2.js', __FILE__ ), array( 'jquery' ), '3.1' );
		wp_enqueue_style( 'pmpros-admin', plugins_url( 'css/pmpro-series-admin.css', __FILE__ ) );
		wp_register_script( 'pmpros_pmpro', plugins_url( 'js/pmpro-series.js', __FILE__ ), array( 'jquery' ), null, true );

		if ( ! empty( $_GET['post'] ) ) {
			$post_id = intval( $_GET['post'] );
		} else {
			$post_id = '';
		}

		$localize = array(
			'series_id'      => $post_id,
			'save'           => __( 'Save', 'pmpro-series' ),
			'saving'         => __( 'Saving...', 'pmpro-series' ),
			'saving_error_1' => __( 'Error saving series post [1]', 'pmpro-series' ),
			'saving_error_2' => __( 'Error saving series post [2]', 'pmpro-series' ),
			'remove_error_1' => __( 'Error removing series post [1]', 'pmpro-series' ),
			'remove_error_2' => __( 'Error removing series post [2]', 'pmpro-series' ),
		);

		wp_localize_script( 'pmpros_pmpro', 'pmpro_series', $localize );
		wp_enqueue_script( 'pmpros_pmpro' );
	}
}
add_action( 'admin_enqueue_scripts', 'pmpros_admin_scripts' );

/*
	PMPro Series CPT
*/
add_action( 'init', array( 'PMProSeries', 'createCPT' ) );

/*
	Add the PMPro meta box and the meta box to add posts/pages to series
*/
add_action( 'init', array( 'PMProSeries', 'checkForMetaBoxes' ), 20 );


/*
	Detect AJAX calls
*/
function pmpros_ajax() {
	if ( isset( $_REQUEST['pmpros_add_post'] ) ) {
		$series_id = $_REQUEST['pmpros_series'];
		$series    = new PMProSeries( $series_id );
		$series->getPostListForMetaBox();
		exit;
	}
}
add_action( 'init', 'pmpros_ajax' );


/**
 * [pmpros_the_content] Show list of series pages at end of series.
 *
 * @param  [type] $content
 * @return [type]
 */
function pmpros_the_content( $content ) {
	global $post;

	if ( $post->post_type == 'pmpro_series' ) {
		
		// Display the Series if Paid Memberships Pro is active.
		if ( !function_exists( 'pmpro_has_membership_access' ) || pmpro_has_membership_access() ) {
			$content .= '<div id="pmpro-series-' . absint( $post->ID ) . '" class="pmpro-series-post-list">';
			$series   = new PMProSeries( $post->ID );
			$member_days = intval( pmpro_getMemberDays() );
			if ( $member_days >= $series->getLongestPostDelay( 'publish' ) ) {
				$content .= '<p class="pmpro_series_all_posts_available_text">' . __( 'All posts in this series are now available.', 'pmpro-series' ) . '</p>';
			} else {
				$content .= '<p class="pmpro_series_days_into_membership_text">' . sprintf( __( 'You are on day %d of your membership.', 'pmpro-series' ), $member_days ) . '</p>';
			}
			$content .= $series->getPostList();
			$content .= '</div> <!-- end pmpro-series -->';
		}
		
		// Note: Let's eventually work to make this compatible if Paid Memberships Pro is not active.		
	}
	
	return $content;
}
add_filter( 'the_content', 'pmpros_the_content' );

/**
 * Check if a user has access to a series.
 */
function pmpros_hasAccessToSeries( $series_id, $user_id, $return_membership_levels = false ) {
	if ( function_exists( 'pmpro_has_membership_access' ) ) {
		// Remove our filter to avoid loops.
		remove_filter( 'pmpro_has_membership_access_filter', 'pmpros_pmpro_has_membership_access_filter', 10, 4 );
		
		// Get results.
		$results = pmpro_has_membership_access( $series_id, $user_id, true );
		
		// Add the filter back.
		add_filter( 'pmpro_has_membership_access_filter', 'pmpros_pmpro_has_membership_access_filter', 10, 4 );
	} else {
		// Assume true
		$results = array();
		$results[] = true;
		$results[] = array();
	}
	
	if ( $return_membership_levels ) {
		return $results;
	} else {
		$user = get_userdata($user_id);
		$registration_date = get_user_meta( $user_id, 'pmpro_approval_date', true ); // Change 'pmpro_approval_date' to the appropriate meta key for your site

		if ( empty( $registration_date ) ) {
			$days_since_join = null;
		} else {
			$days_since_join = round((time() - strtotime($registration_date)) / DAY_IN_SECONDS);
		}

		$has_access = $days_since_join <= 180 || pmpro_hasMembershipLevel( null, $user_id );

		if ( ! $has_access ) {
			return false;
		}

		$registration_date_text = ! empty( $registration_date ) ? date( get_option( 'date_format' ), strtotime( $registration_date ) ) : 'N/A';

		// If the user does have access to the series
		$series_text = '';

		if ( ! empty( $series_id ) ) {
			$series = get_post( $series_id );
			$series_title = $series->post_title;
			$series_permalink = get_permalink( $series_id );
			$series_text = sprintf( '<a href="%s">%s</a>', $series_permalink, $series_title );
		}

		if ( ! empty( $days_since_join ) && $days_since_join < 180 ) {
			// If the user has joined within the last 180 days
			$days_left = 180 - $days_since_join;
			$message = sprintf(
				__( 'You joined on %s and have access to this series for the next %d days.', 'pmpro-series' ),
				$registration_date_text,
				$days_left
			);

			if ( ! empty( $series_text ) ) {
				$message .= sprintf( ' ' . __( 'Please visit %s to start the series or continue where you left off.', 'pmpro-series' ), $series_text );
			}

			return $message;
		} elseif ( pmpro_hasMembershipLevel( null, $user_id ) ) {
			// If the user has an active membership level
			return true;
		} else {
			// If the user joined more than 180 days ago but does not have an active membership level
			$message = __( 'Sorry, you do not have access to this content.', 'pmpro-series' );
			return $message;
		}
	}
}


/**
 * [pmpros_hasAccess] Makes sure people can't view content they don't have access to. This function returns true if a user has access to a page, including logic for series/delays.
 *
 * @param  [type] $user_id
 * @param  [type] $post_id
 * @return [type]
 */
function pmpros_hasAccess( $user_id, $post_id ) {
	// is this post in a series?
	$post_series = pmpros_getPostSeries( $post_id );
	if ( empty( $post_series ) ) {
		return true;        // not in a series
	}
	
	// does this user have a level giving them access to everything?
	$all_access_levels = apply_filters( 'pmproap_all_access_levels', array(), $user_id, $post_id );
	if ( ! empty( $all_access_levels )
	&& function_exists( 'pmpro_hasMembershipLevel' )
	&& pmpro_hasMembershipLevel( $all_access_levels, $user_id ) ) {
		return true;    // user has one of the all access levels
	}

	// check each series
	foreach ( $post_series as $series_id ) {
		
		// does the user have access to any of the series pages?
		if ( function_exists( 'pmpro_has_membership_access' ) ) {
			$results = pmpros_hasAccessToSeries( $series_id, $user_id, true );
			$hasaccess = $results[0];
		} else {
			$hasaccess = 1;	// PMPro not active. Assume access, but check MemberDays below.
		}

		if ( $hasaccess ) {
			// has the user been around long enough for any of the delays?
			$series_posts = get_post_meta( $series_id, '_series_posts', true );
			if ( ! empty( $series_posts ) ) {
				foreach ( $series_posts as $sp ) {
					// this post we are checking is in this series
					if ( $sp->id == $post_id ) {
						if ( ! empty( $results ) ) {
							// check specifically for the levels with access to this series
							foreach ( $results[1] as $level_id ) {
								if ( max( 0, pmpro_getMemberDays( $user_id, $level_id ) ) >= $sp->delay ) {
									return true;    // user has access to this series and has been around longer than this post's delay
								}
							}
						} else {
							// check if they've been a user long enough
							if ( max( 0, pmpro_getMemberDays( $user_id ) ) >= $sp->delay ) {
								return true;
							}
						}
					}
				}
			}
		}
	}

	// haven't found anything yet. so must not have access
	return false;
}

/**
 * [pmpros_pmpro_has_membership_access_filter] Filter pmpro_has_membership_access based on series access.
 *
 * @param  [type] $hasaccess
 * @param  [type] $mypost
 * @param  [type] $myuser
 * @param  [type] $post_membership_levels
 * @return [type]
 */
function pmpros_pmpro_has_membership_access_filter( $hasaccess, $post, $user, $post_membership_levels ) {
	// If the user doesn't have access already, we won't change that. So only check if they already have access.
	if ( $hasaccess && !empty( $post ) ) {
		// okay check if the user has access
		if ( pmpros_hasAccess( $user->ID, $post->ID ) ) {
			$hasaccess = true;
		} else {
			$hasaccess = false;
		}
	}

	return $hasaccess;
}
add_filter( 'pmpro_has_membership_access_filter', 'pmpros_pmpro_has_membership_access_filter', 10, 4 );

/**
 * Get the series a post is in.
 * @param $post_id	int	ID of the post to check series for.
 * NOTE: When getting/setting the _post_series post meta, use get_post_meta
 * 		 to get the value directly.
 */
function pmpros_getPostSeries( $post_id = NULL ) {
	// Default to the global post.
	if ( empty( $post_id ) ) {
		global $post;
		
		if ( ! empty( $post ) && ! empty( $post->ID ) ) {
			$post_id = $post->ID;
		}
	}
	
	// Get ID from post object if passed in.
	if ( is_object( $post_id ) && ! empty( $post_id->ID ) ) {
		$post_id = $post_id->ID;
	}
	
	// Bail if no post.
	if ( empty( $post_id ) ) {
		return array();
	}
	
	// If this is a series itself, bail.
	if ( get_post_type( $post_id ) == 'pmpro_series' ) {
		return array();
	}
	
	// Get series from post meta.
	$post_series = get_post_meta( $post_id, '_post_series', true );

	// Make sure it's an array.
	if ( empty( $post_series ) ) {
		$post_series = array();
	} elseif ( ! is_array( $post_series ) ) {
		$post_series = array( $post_series );
	}
	
	// Make sure the posts are published.
	$new_post_series = array();
	$deleted_post_series = array();
	foreach( $post_series as $series_id ) {
		if ( ! empty( $series_id ) ) {
			$post_status = get_post_status( $series_id );
			if ( 'publish' === $post_status ) {
				$new_post_series[] = $series_id;
			} elseif ( 'trash' === $post_status || false === $post_status ) {
				$deleted_post_series[] = $series_id;
			}
		}
	}

	if ( ! empty( $deleted_post_series ) ) {
		update_post_meta( $post_id, '_post_series', array_diff( $post_series, $deleted_post_series ) );
	}

	return $new_post_series;
}

/**
 * [pmpros_pmpro_text_filter] Filter the message for users without access.
 *
 * @param  string $text
 * @return string
 */
function pmpros_pmpro_text_filter( $text ) {
	global $wpdb, $current_user, $post;

	if ( ! empty( $current_user ) && ! empty( $post ) ) {
		if ( ! pmpros_hasAccess( $current_user->ID, $post->ID ) ) {
			// Update text. The either have to wait or sign up.
			$post_series = pmpros_getPostSeries( $post->ID );

			$inseries = false;
			foreach ( $post_series as $ps ) {
				if ( !function_exists('pmpro_has_membership_access') || pmpro_has_membership_access( $ps ) ) {
					$inseries = $ps;
					break;
				}
			}

			if ( $inseries ) {
				// user has one of the series levels, find out which one and tell him how many days left
				$series = new PMProSeries( $inseries );
				$day    = $series->getDelayForPost( $post->ID );

				$member_days = pmpro_getMemberDays( $current_user->ID );
				$days_left   = ceil( $day - $member_days );
				$series_date_text        = date_i18n( get_option( 'date_format' ), strtotime( "+ $days_left Days", current_time( 'timestamp' ) ) );

				$series_link_text = '<a href="' . get_permalink( $inseries ) . '">' . get_the_title( $inseries ) . '</a>';
				$text = sprintf( __( 'This content is part of the %s series. You will gain access on %s.', 'pmpro-series' ),  $series_link_text, $series_date_text );

				$text = apply_filters( 'pmpros_days_left_message', $text, $member_days, $days_left, $current_user->ID );
			} else {
				// user has to sign up for one of the series
				if ( count( $post_series ) == 1 ) {
					$series_link_text = '<a href="' . get_permalink( $post_series[0] ) . '">' . get_the_title( $post_series[0] ) . '</a>';
					$text = sprintf( __( 'This content is part of the %s series.', 'pmpro-series' ),  $series_link_text );
					
					$text = apply_filters( 'pmpros_content_access_message_single_item', $text, $post_series );
				} else {
					$series = array();
					foreach ( $post_series as $series_id ) {
						$series[] = "<a href='" . get_permalink( $series_id ) . "'>" . get_the_title( $series_id ) . '</a>';
					}
					$series_list_text = implode( ', ', $series ) . '.';
					
					$text   = sprintf( __( 'This content is part of the following series: %s', 'pmpro-series' ), $series_list_text );
					
					$text  = apply_filters( 'pmpros_content_access_message_many_items', $text, $post_series );
				}
			}
		}
	}

	return $text;
}
add_filter( 'pmpro_non_member_text_filter', 'pmpros_pmpro_text_filter' );
add_filter( 'pmpro_not_logged_in_text_filter', 'pmpros_pmpro_text_filter' );

/*
	Couple functions from PMPro in case we don't have them yet.
*/
if ( ! function_exists( 'pmpro_getMemberStartdate' ) ) {
	/**
	 * [pmpro_getMemberStartdate] Get a member's start date... either in general or for a specific level_id.
	 *
	 * @param  [type]  $user_id
	 * @param  integer $level_id
	 * @return [type]
	 */
	function pmpro_getMemberStartdate( $user_id = null, $level_id = 0 ) {
		if ( empty( $user_id ) ) {
			global $current_user;
			$user_id = $current_user->ID;
		}

		global $pmpro_startdates;   // for cache
		if ( empty( $pmpro_startdates[ $user_id ][ $level_id ] ) ) {
			global $wpdb;

			if ( ! empty( $level_id ) ) {
				$sqlQuery = "SELECT UNIX_TIMESTAMP(CONVERT_TZ(startdate, '+00:00', @@global.time_zone)) FROM $wpdb->pmpro_memberships_users WHERE status = 'active' AND membership_id IN(" . $wpdb->escape( $level_id ) . ") AND user_id = '" . $user_id . "' ORDER BY id LIMIT 1";
			} elseif( !empty( $wpdb->pmpro_memberships_users) ) {
				$sqlQuery = "SELECT UNIX_TIMESTAMP(CONVERT_TZ(startdate, '+00:00', @@global.time_zone)) FROM $wpdb->pmpro_memberships_users WHERE status = 'active' AND user_id = '" . $user_id . "' ORDER BY id LIMIT 1";
			} else {
				$sqlQuery = "SELECT UNIX_TIMESTAMP(CONVERT_TZ(user_registered, '+00:00', @@global.time_zone)) FROM $wpdb->users WHERE ID = '" . esc_sql( $user_id ) . "' LIMIT 1";
			}

			$startdate = apply_filters( 'pmpro_member_startdate', $wpdb->get_var( $sqlQuery ), $user_id, $level_id );

			$pmpro_startdates[ $user_id ][ $level_id ] = $startdate;
		}

		return $pmpro_startdates[ $user_id ][ $level_id ];
	}

	/**
	 * [pmpro_getMemberDays description]
	 *
	 * @param  [type]  $user_id
	 * @param  integer $level_id
	 * @return [type]
	 */
	function pmpro_getMemberDays( $user_id = null, $level_id = 0 ) {
		if ( empty( $user_id ) ) {
			global $current_user;
			$user_id = $current_user->ID;
		}

		global $pmpro_member_days;
		if ( empty( $pmpro_member_days[ $user_id ][ $level_id ] ) ) {
			$startdate = pmpro_getMemberStartdate( $user_id, $level_id );

			// check that there was a startdate at all
			if ( empty( $startdate ) ) {
				$pmpro_member_days[ $user_id ][ $level_id ] = 0;
			} else {
				$now  = current_time( 'timestamp' );
				$days = ( $now - $startdate ) / 3600 / 24;

				$pmpro_member_days[ $user_id ][ $level_id ] = $days;
			}
		}

		return $pmpro_member_days[ $user_id ][ $level_id ];
	}
}

/*
	We need to flush rewrite rules on activation/etc for the CPTs.
	Register/unregister crons on activation/deactivation.
*/
/**
 * [pmpros_activation description]
 *
 * @return [type]
 */
function pmpros_activation() {
	// flush rewrite rules
	PMProSeries::createCPT();
	flush_rewrite_rules();

	// setup cron
	wp_schedule_event( current_time( 'timestamp' ), 'daily', 'pmpros_check_for_new_content' );
}
register_activation_hook( __FILE__, 'pmpros_activation' );
function pmpros_deactivation() {
	// flush rewrite rules
	global $pmpros_deactivating;
	$pmpros_deactivating = true;
	flush_rewrite_rules();

	// remove cron
	wp_clear_scheduled_hook( current_time( 'timestamp' ), 'daily', 'pmpros_check_for_new_content' );
}
register_deactivation_hook( __FILE__, 'pmpros_deactivation' );

/*
	Add series post links to account page
*/
function pmpros_member_links_bottom() {
	global $wpdb, $current_user;

	// get all series
	$all_series = $wpdb->get_results(
		"
        SELECT *
        FROM $wpdb->posts
        WHERE post_type = 'pmpro_series'
    "
	);

	if ( empty( $all_series ) ) {
		return;
	}

	foreach ( $all_series as $s ) {
		$series       = new PMProSeries( $s->ID );
		$series_posts = $series->getPosts();

		if ( ! empty( $series_posts ) ) {
			foreach ( $series_posts as $series_post ) {
				if ( pmpros_hasAccess( $current_user->ID, $series_post->id ) ) {
					?>
					<li><a href="<?php echo get_permalink( $series_post->id ); ?>" title="<?php echo get_the_title( $series_post->id ); ?>"><?php echo get_the_title( $series_post->id ); ?></a></li>
					<?php
				}
			}
		}
	}
}
add_action( 'pmpro_member_links_bottom', 'pmpros_member_links_bottom' );

/**
 * [pmpros_email_templates] Integrate with Email Templates Admin Editor
 *
 * @param  [type] $templates
 * @return [type]
 */
function pmpros_email_templates( $templates ) {
	// Add the new content template.
	$templates['new_content'] = array(
		'subject'     => __( 'New content is available at !!sitename!!', 'pmpro-series' ),
		'description' => __( 'New Series Content Notification', 'pmpro-series' ),
		'body'        => file_get_contents( dirname( __FILE__ ) . '/email/new_content.html' ),
	);
	return $templates;
}
add_filter( 'pmproet_templates', 'pmpros_email_templates', 10, 1 );

/**
 * [pmpros_add_email_template]
 *
 * @param  [type] $templates
 * @param  [type] $page_name
 * @param  string $type
 * @param  string $where
 * @param  string $ext
 * @return [type]
 */
function pmpros_add_email_template( $templates, $page_name, $type = 'emails', $where = 'local', $ext = 'html' ) {
	$templates[] = dirname( __FILE__ ) . '/email/new_content.html';
	return $templates;
}
add_filter( 'pmpro_email_custom_template_path', 'pmpros_add_email_template', 10, 5 );

/**
 * [pmpros_plugin_action_links] Function to add links to the plugin action links
 *
 * @param array $links Array of links to be shown in plugin action links.
 */
function pmpros_plugin_action_links( $links ) {
	if ( current_user_can( 'manage_options' ) ) {
		$new_links = array(
			'<a href="' . get_admin_url( null, 'edit.php?post_type=pmpro_series' ) . '">' . __( 'Settings', 'pmpro-series' ) . '</a>',
		);
	}
	return array_merge( $new_links, $links );
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'pmpros_plugin_action_links' );

/**
 * [pmpros_plugin_row_meta] Function to add links to the plugin row meta
 *
 * @param array  $links Array of links to be shown in plugin meta.
 * @param string $file Filename of the plugin meta is being shown for.
 */
function pmpros_plugin_row_meta( $links, $file ) {
	if ( strpos( $file, 'pmpro-series.php' ) !== false ) {
		$new_links = array(
			'<a href="' . esc_url( 'https://www.paidmembershipspro.com/add-ons/pmpro-series-for-drip-feed-content/' ) . '" title="' . esc_attr( __( 'View Documentation', 'pmpro-series' ) ) . '">' . __( 'Docs', 'pmpro-series' ) . '</a>',
			'<a href="' . esc_url( 'https://www.paidmembershipspro.com/support/' ) . '" title="' . esc_attr( __( 'Visit Customer Support Forum', 'pmpro-series' ) ) . '">' . __( 'Support', 'pmpro-series' ) . '</a>',
		);
		$links     = array_merge( $links, $new_links );
	}
	return $links;
}
add_filter( 'plugin_row_meta', 'pmpros_plugin_row_meta', 10, 2 );

