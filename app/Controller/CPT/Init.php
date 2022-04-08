<?php

namespace Oxynate\Controller\CPT;

use Oxynate\Helper;

class Init {

    /**
	 * Constuctor
	 * 
     * @return void
	 */
	public function __construct() {

		// Register Controllers
        $controllers = $this->get_controllers();
        Helper\Serve::register_services( $controllers );

	}

    /**
	 * Controllers
	 * 
     * @return array Controllers
	 */
	protected function get_controllers() {
        return [
            Donation_Request::class,
            On_Boarding_Pages::class,
        ];
    }
}