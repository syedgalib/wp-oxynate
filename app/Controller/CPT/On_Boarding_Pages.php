<?php

namespace Oxynate\Controller\CPT;

class On_Boarding_Pages {

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

        $singular = __( 'On Boarding Page', 'wp-oxynate' );
        $plural   = __( 'On Boarding Pages', 'wp-oxynate' );
        $args     = [ 'all_items' => $plural ];

        $args = [
            'labels'       => wp_oxinate_get_wp_labels( $singular, $plural, '', $args ),
            'public'       => true,
            'has_archive'  => false,
            'show_in_menu' => 'edit.php?post_type=donation-request',
            'menu_icon'    => 'dashicons-heart',
            'supports'     => [ 
                'title',
                'editor',
                'thumbnail',
                'custom-fields',
            ],
        ];

        register_post_type( WP_OXYNATE_POST_TYPE_ON_BOARDING_PAGES, $args );

    }
}