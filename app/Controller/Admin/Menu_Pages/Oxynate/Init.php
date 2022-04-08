<?php

namespace Oxynate\Controller\Admin\Menu_Pages\Oxynate;

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
            Settings_Page::class,
        ];
    }

}