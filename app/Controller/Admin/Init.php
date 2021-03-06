<?php

namespace Oxynate\Controller\Admin;

use Oxynate\Helper;

class Init {
    
    /**
     * Constructor
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
            Menu_Pages\Init::class,
        ];
    }

}