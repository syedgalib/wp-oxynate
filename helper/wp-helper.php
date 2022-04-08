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

    $labels = ( is_array( $args ) && ! empty( $args ) ) ? array_merge( $default, $args ) : $args;

    return $labels;
}