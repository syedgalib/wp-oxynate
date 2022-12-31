<?php

namespace Oxynate\Service;

use Oxynate\Service\SMS_Gatway\SMS_Gatway;

class Init {

    /**
     * SMS Gatway
     * 
     * @var SMS_Gatway_Service
     */
    public $sms_getway;

    public function __construct() {
        $this->register_services();
    }

    /**
     * Register Services
     * 
     * @return void
     */
    public function register_services() {
        $this->sms_getway = new SMS_Gatway();
    }

}