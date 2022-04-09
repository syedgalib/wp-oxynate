<?php

namespace Oxynate\Controller\CPT;

class Donation_Request {

    /**
	 * Constuctor
	 * 
     * @return void
	 */
	public function __construct() {
        add_action( 'init', [ $this, 'register_post_type' ] );
	}

    /**
	 * Register Post Type
	 * 
     * @return void
	 */
    public function register_post_type() {

        $name     = __( 'Oxynate', 'wp-oxynate' );
        $singular = __( 'Donation Request', 'wp-oxynate' );
        $plural   = __( 'Donation Requests', 'wp-oxynate' );

        $args = [
            'labels'      => wp_oxinate_get_wp_labels( $singular, $plural, $name ),
            'public'      => true,
            'has_archive' => true,
            'rewrite' => [
                'slug' => 'donation-requests'
            ],
            'menu_icon'   => 'dashicons-heart',
            'supports'    => [ 
                'title', 
                'editor', 
                'thumbnail', 
                'author',
                'custom-fields',
            ],
        ];

        register_post_type( WP_OXYNATE_POST_TYPE_DONATION_REQUEST, $args );

    }
}