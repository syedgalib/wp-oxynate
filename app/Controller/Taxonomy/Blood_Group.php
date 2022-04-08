<?php

namespace Oxynate\Controller\Taxonomy;

class Blood_Group {

    /**
	 * Constuctor
	 * 
     * @return void
	 */
	function __construct() {
        add_action( 'init', [ $this, 'register_taxonomy' ] );
	}

	/**
	 * Register Taxonomy
	 * 
	 * @return void
	 */
	public function register_taxonomy() {

		$singular_name = __( 'Blood Group', 'wp-oxynate' );
		$plural_name   = __( 'Blood Groups', 'wp-oxynate' );

		$args = [
			'labels'       => wp_oxinate_get_wp_labels( $singular_name, $plural_name ),
			'public'       => true,
			'hierarchical' => true,
		];

		register_taxonomy( WP_OXYNATE_TERM_BLOOD_GROUP, WP_OXYNATE_POST_TYPE_DONATION_REQUEST, $args );
	}
}