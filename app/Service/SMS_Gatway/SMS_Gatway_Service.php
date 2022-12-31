<?php

namespace Oxynate\Service\SMS_Gatway;

interface SMS_Gatway_Service {

    /**
     * Send
     * 
     * @param srring $to
     * @param srring $message
     * 
     * @return array Status
     */
    public function send( $to = '', $message = '' );

}