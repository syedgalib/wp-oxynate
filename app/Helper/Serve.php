<?php

namespace Oxynate\Helper;

class Serve {

    public static function register_services( array $services = [] ) {

        foreach( $services as $service ) {

            if ( ! class_exists( $service ) ) {
                continue;
            }

            new $service();

        }
        
    }

}