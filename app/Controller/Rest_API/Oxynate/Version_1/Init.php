<?php

namespace Oxynate\Controller\Rest_API\Oxynate\Version_1;

use Oxynate\Helper;

class Init {

    /**
	 * Constuctor
	 * 
     * @return void
	 */
	public function __construct() {

        add_action( 'rest_api_init', [ $this, 'register_rest_controllers' ] );
        
	}

    /**
     * Register Rest Controllers
     * 
     * @return void
     */
    public function register_rest_controllers() {

        // Register Controllers
        $controllers = $this->get_controllers();
        Helper\Serve::register_rest_controllers( $controllers );

    }

    /**
	 * Controllers
	 * 
     * @return array Controllers
	 */
	protected function get_controllers() {
        return [
            Authentication::class,
            Users::class,
            Donation_Request::class,
            Blood_Groups::class,
            Locations::class,
            App_Settings::class,
        ];
    }
}