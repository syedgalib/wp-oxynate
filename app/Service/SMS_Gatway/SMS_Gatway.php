<?php

namespace Oxynate\Service\SMS_Gatway;

class SMS_Gatway implements SMS_Gatway_Service {

    /**
     * SMS Gatway
     * 
     * @var SMS_Gatway_Service
     */
    public $gatway;

    public function __construct( $gatway = null ) {

        $this->gatway = new GreenWebSMSGatway();

        if ( ! is_null( $gatway ) ) {
            $this->gatway = $gatway;
        }

    }

    /**
     * Send
     * 
     * @param srring $to
     * @param srring $message
     * 
     * @return array Status
     */
    public function send( $to = '', $message = '' ) {
        return $this->gatway->send( $to, $message );
    }

}