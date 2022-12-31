<?php

use Oxynate\Controller;
use Oxynate\Helper;
use Oxynate\Service\Init as Services;

final class Oxynate {

    private static $instance;

    private function __construct() {

        // Register Services
        add_action( 'init', [ $this, 'register_services' ] );

        // Register Controllers
        $controllers = $this->get_controllers();
        Helper\Serve::register_services( $controllers );

    }

    public static function get_instance() {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function get_controllers() {
        return [
            Controller\Init::class,
        ];
    }

    public function register_services() {
        $GLOBALS['oxynate_service'] = new Services();
    }

    public function __clone() {
		// Cloning instances of the class is forbidden.
		_doing_it_wrong( __FUNCTION__, __('Cheatin&#8217; huh?', 'wp-oxynate'), '1.0' );
	}

	public function __wakeup() {
		// Unserializing instances of the class is forbidden.
		_doing_it_wrong( __FUNCTION__, __('Cheatin&#8217; huh?', 'wp-oxynate'), '1.0' );
	}

}