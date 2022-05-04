<?php


/**
 * WP Oxinate Get WP Labels
 * 
 * @param string $singular
 * @param string $plural
 * @return array labels 
 */
function wp_oxinate_get_wp_labels( $singular = '', $plural = '', $name = '', $args = [] ) {
    $name = ( ! empty( $name ) ) ? $name : $plural;

    $default = [
        'name'                       => ucwords( $name ),
        'singular_name'              => ucwords( $singular ),
        'search_items'               => ucwords( "Search ${plural}" ),
        'popular_items'              => ucwords( "Popular ${plural}" ),
        'all_items'                  => ucwords( "All ${plural}" ),
        'parent_item'                => ucwords( "Parent ${singular}" ),
        'parent_item_colon'          => ucwords( "Parent ${singular}:" ),
        'edit_item'                  => ucwords( "Edit ${singular}:" ),
        'view_item'                  => ucwords( "View ${singular}:" ),
        'update_item'                => ucwords( "Update ${singular}:" ),
        'add_new_item'               => 'Add new ' . ucwords( $singular ),
        'new_item_name'              => 'New ' . ucwords( $singular ),
        'separate_items_with_commas' => 'Separate ' . ucwords( $singular ) . ' with commas',
        'add_or_remove_items'        => 'Add or remove ' . ucwords( $singular ),
        'choose_from_most_used'      => 'Choose from the most used ' . ucwords( $singular ),
        'not_found'                  => 'No ' . ucwords( $singular ) . ' found',
        'no_terms'                   => 'No ' . ucwords( $plural ) . ' found',
        'filter_by_item'             => 'Filter by ' . ucwords( $singular ),
        'back_to_items'              => 'Back to ' . ucwords( $plural ),
        'item_link'                  => ucwords( $plural ) . ' Link',
        'item_link_description'      => 'A link to a ' . ucwords( $singular ),
    ];

    $labels = ( is_array( $args ) ) ? array_merge( $default, $args ) : $default;

    return $labels;
}


/**
 * WP Oxynate Ensure Valid Role
 * 
 * @param string $role Role
 * @return string Role
 */
function wp_oxynate_ensure_valid_role( $role = '' ) {
    $available_roles = wp_roles();
    $available_roles = ( ! empty( $available_roles ) ) ? array_keys( $available_roles->role_names ) : [];

    $role = ( in_array( $role, $available_roles ) ) ? $role : 'subscriber';

    return $role;
}


/**
 * Get or create user by email
 * 
 * @param string $email
 * @return WP_User|WP_Error
 */
function wp_oxynate_get_or_create_user_by_email( $email = '' ) {
    $user  = get_user_by( 'email', $email );

    if ( empty( $user ) ) {
        $username = preg_replace( '/@.+$/', '', $email );
        $username = wp_oxynate_generate_unique_username( $username );
        $password = wp_generate_password();
        $user     = wp_create_user( $username, $password, $email );
        $user     = get_user_by( 'id', $user );
    }

    if ( empty( $user ) || is_wp_error( $user ) ) {
        return new WP_Error( 403, __( 'Something went wrong!, please try again.', 'wp-oxynate' ) );
    }

    return $user;
}

/**
 * Generate Unique Username
 * 
 * @param string $username
 * @return string $username
 */
function wp_oxynate_generate_unique_username( $username ) {

	$username = sanitize_user( $username );

	static $i;
	if ( null === $i ) {
		$i = 1;
	} else {
		$i ++;
	}
    
	if ( ! username_exists( $username ) ) {
		return $username;
	}

	$new_username = sprintf( '%s-%s', $username, $i );

	if ( ! username_exists( $new_username ) ) {
		return $new_username;
	} else {
		return call_user_func( __FUNCTION__, $username );
	}
}