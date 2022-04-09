<?php

namespace Oxynate\Helper;

class Serve {

    /**
     * Register Services
     * 
     * @param array $services Services
     * @return void
     */
    public static function register_services( $services = [] ) {

        foreach( $services as $service ) {

            if ( ! class_exists( $service ) ) {
                continue;
            }

            new $service();

        }
        
    }

    /**
     * Register Rest Controller
     * 
     * @param array $controllers Controllers
     * @return void
     */
    public static function register_rest_controllers( $controllers = [] ) {

        foreach( $controllers as $controller ) {

            if ( ! class_exists( $controller ) ) {
                continue;
            }

            $rest_controller = new $controller();

            if ( ! method_exists( $rest_controller, 'register_routes' ) ) {
                return;
            }


            $rest_controller->register_routes();
        }
        
    }

}