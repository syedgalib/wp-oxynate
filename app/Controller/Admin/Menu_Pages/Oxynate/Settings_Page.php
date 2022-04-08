<?php

namespace Oxynate\Controller\Admin\Menu_Pages\Oxynate;

use Oxynate\Module\Settings_Panel\Model as Settings_Panel_Model;
use Oxynate\Module\Settings_Panel\Fields_Template as Settings_Panel_Fields_Template;

class Settings_Page {
    
    /**
     * Constructor
     * 
     * @return void
     */
    public function __construct() {
        add_action( 'admin_menu', [ $this, 'add_menu_pages' ] );
    }

    /**
     * Add menu pages
     * 
     * @return void
     */
    public function add_menu_pages() {

        add_submenu_page( 
            'edit.php?post_type=' . WP_OXYNATE_POST_TYPE_DONATION_REQUEST, 
            'Settings', 
            'Settings',
            'manage_options',
            'wp-oxynate-settings',
            [ $this, 'oxynate_settings_page_callback' ],
        );

    }

    /**
     * Oxynate Settings Page Callback
     * 
     * @return void
     */
    public function oxynate_settings_page_callback() {

        if ( $_POST ) {
            check_admin_referer( 'wp_oxinate_submit_settings_options' );
            Settings_Panel_Model::update_options( $_POST );
        }

        $data = [
            'options'         => Settings_Panel_Model::get_options(),
            'fields_template' => Settings_Panel_Fields_Template::class,
        ];

        wp_oxinate_get_view( 'Admin/Menu_Pages/Oxynate/settings-page', $data );

    }

}