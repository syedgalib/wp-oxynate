<?php

namespace Oxynate\Service\SMS_Gatway;

use Oxynate\Module\Settings_Panel\Model as App_Settings;

class GreenWebSMSGatway implements SMS_Gatway_Service {

    public $sms_token = '';

    public function __construct() {
        $this->sms_token = App_Settings::get_option( 'gwsg_token' );
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
        $status = [ 
            'success' => false, 
            'message' => __( 'Couln\'t send the message, please try again', 'oxynate' ),
        ];

        if ( empty( $to ) ) {
            $status['message'] = __( 'The phone number must not be empty', 'oxynate' );
            return $status;
        }

        if ( empty( $message ) ) {
            $status['message'] = __( 'Message must not be empty', 'oxynate' );
            return $status;
        }

        $sms_token = $this->sms_token;

        $url = "http://api.greenweb.com.bd/api.php?json&token={$sms_token}&message={$message}&to={$to}";

        $response = wp_remote_post( $url );

        if ( is_wp_error( $response ) ) {
            return $status;
        }

        $response_data = json_decode( $response['body'], true );

        if ( ! is_array( $response_data ) ) {
            return $status;
        }

        if ( empty( $response_data ) ) {
            return $status;
        }

        if ( empty( $response_data[0]['status'] ) ) {
            return $status;
        }

        if ( 'SENT' !== $response_data[0]['status'] ) {
            return $status;
        }

        $status['success'] = true;
        $status['message'] = __( 'The Message has been sent successfully', 'oxynate' );

        return $status;
    }

}