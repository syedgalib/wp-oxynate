<?php

namespace Oxynate\Controller\Taxonomy;

class Location {

    /**
	 * Constuctor
	 * 
     * @return void
	 */
	public function __construct() {
        add_action( 'init', [ $this, 'register_taxonomy' ] );
	}

	/**
	 * Register Taxonomy
	 * 
	 * @return void
	 */
	public function register_taxonomy() {

		$singular_name = __( 'Location', 'wp-oxynate' );
		$plural_name   = __( 'Locations', 'wp-oxynate' );

		$args = [
			'labels'       => wp_oxinate_get_wp_labels( $singular_name, $plural_name ),
			'public'       => true,
			'hierarchical' => true,
		];

		register_taxonomy( WP_OXYNATE_TERM_LOCATION, WP_OXYNATE_POST_TYPE_DONATION_REQUEST, $args );
	}
}