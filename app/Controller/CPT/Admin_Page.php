<?php

namespace Oxynate\Controller\CPT;

class Admin_Page {

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
        
        $singular = __( 'Admin Page', 'wp-oxynate' );
        $plural   = __( 'Admin Pages', 'wp-oxynate' );
        $args     = [ 'all_items' => $plural ];

        $args = [
            'labels'       => wp_oxinate_get_wp_labels( $singular, $plural, '', $args ),
            'public'       => true,
            'has_archive'  => false,
            'show_in_menu' => 'edit.php?post_type=donation-request',
            'supports'     => [ 
                'title',
                'editor',
                'thumbnail',
                'custom-fields',
            ],
        ];

        register_post_type( WP_OXYNATE_POST_TYPE_ADMIN_PAGE, $args );

    }
}