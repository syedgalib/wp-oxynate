<?php

namespace Oxynate\Controller;

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
     * @return array
	 */
	protected function get_controllers() {
        return [
            Asset\Init::class,
            User\Init::class,
            CPT\Init::class,
            Taxonomy\Init::class,
            Admin\Init::class,
        ];
    }
}