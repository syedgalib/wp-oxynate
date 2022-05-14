<?php

namespace Oxynate\Module\Settings_Panel;

use Oxynate\Model\On_Boarding_Pages;

class Model {

    private static $options_key = 'wp_oxynate_options';

    /**
     * Get Option Fields
     * 
     * @return array Options Fields
     */
    public static function get_option_fields() {
        
        $option_fields = [];

        $option_fields['app_name'] = [
            'type'  => 'text',
            'label' => __( 'App Name', 'wp-oxynate' ),
            'value' => self::get_option_value( 'app_name', '' ),
        ];

        $option_fields['admin_phone'] = [
            'type'  => 'tel',
            'label' => __( 'Admin Phone', 'wp-oxynate' ),
            'value' => self::get_option_value( 'admin_phone', '' ),
        ];

        $option_fields['twilio_sid'] = [
            'type'  => 'text',
            'label' => __( 'Twilio : SID', 'wp-oxynate' ),
            'value' => self::get_option_value( 'twilio_sid', '' ),
        ];

        $option_fields['twilio_token'] = [
            'type'  => 'text',
            'label' => __( 'Twilio : Token', 'wp-oxynate' ),
            'value' => self::get_option_value( 'twilio_token', '' ),
        ];

        $option_fields['twilio_from_phone'] = [
            'type'  => 'text',
            'label' => __( 'Twilio : From Phone', 'wp-oxynate' ),
            'value' => self::get_option_value( 'twilio_from_phone', '' ),
        ];

        return $option_fields;
    }

    /**
     * Get Option Value
     * 
     * @param string $key Option Key
     * @param mixed $default Default Value
     * @return mixed
     */
    public static function get_option_value( $key = '', $default = '' ) {
        
        $options = self::get_options();
        $value = ( key_exists( $key, $options ) ) ? $options[ $key ] : $default;
        
        return $value;
    }

    /**
     * Update Options
     * 
     * @param array $values
     * @return array
     */
    public static function update_options( $new_options = [] ) {
        
        $status = [];
        $status['success'] = false;
        $status['message'] = '';

        if ( empty( $new_options ) ) {
            $status['message'] = __( 'Nothing to update', 'wp-oxynate' );
            $status['new_options'] = $new_options;
            return $status;
        }

        $new_options = self::get_validated_options( $new_options );
        $old_options = self::get_options();
        $options     = array_merge( $old_options, $new_options );

        update_option( self::$options_key, $options );

        $status['success'] = true;
        $status['message'] = __( 'The settings has been updated successfuly', 'wp-oxynate' );
        
        return $status;
    }

    /**
     * Delete Option
     * 
     * @param string $key
     * @return array
     */
    public static function delete_option( $key = '' ) {
        
        $status = [];
        $status['success'] = false;
        $status['message'] = '';

        $options = self::get_options();
        $keys = ( ! empty( $key ) && is_string( $key ) ) ? explode( ',', $key ) : []; 

        if ( empty( $keys ) || ! is_array( $keys ) ) {
            $status['message'] = __( 'Nothing to delete', 'wp-oxynate' );
            return $status;
        }

        foreach( $keys as $_key ) {
            if ( isset( $options[ $_key ] ) ) {
                unset( $options[ $_key ] );   
            }
        }

        update_option( self::$options_key, $options );

        $status['success'] = true;
        $status['message'] = __( 'The settings has been deleted successfuly', 'wp-oxynate' );

        return $status;
    }

    /**
     * Get Validated Options
     * 
     * @param array|mixed $options
     * @return array
     */
    public static function get_validated_options( $options = [] ) {
        
        if ( ! is_array( $options ) ) {
            return [];
        }

        $fields      = self::get_option_fields();
        $fields_keys = ( is_array( $fields ) ) ? array_keys( $fields ) : [];

        if ( empty( $fields_keys ) ) {
            return [];
        }

        $valid_options = [];

        foreach( $options as $option_key => $option_value ) {
            
            if ( ! in_array( $option_key, $fields_keys ) ) {
                continue;
            }

            $valid_options[ $option_key ] = $option_value;

        }

        return $valid_options;
    }

    /**
     * Update Option
     * 
     * @param string $option_key Option Key
     * @param mixed $option_value Option Value
     * @return void
     */
    public static function update_option( $option_key = '', $option_value = '' ) {
        
        $options = self::get_options();
        $options[ $option_key ] = $option_value;

        update_option( self::$options_key, $options );
    }

    /**
     * Get Options
     * 
     * @return array Options
     */
    public static function get_options() {
        
        $options = get_option( self::$options_key, [] );
        $options = ( is_array( $options ) ) ? $options : [];

        $options['on_boarding_pages'] = On_Boarding_Pages::get_rest_posts();

        return $options;
    }
    
    /**
     * Get Settings Options
     * 
     * @return array Settings
     */
    public static function get_settings_options() {

        $option_fields = self::get_option_fields();
        $option_fields = ( is_array( $option_fields ) ) ? $option_fields : [];

        $settings = [];

        foreach ( $option_fields as $_key => $_args ) {
            $settings[ $_key ] = ( isset( $_args['value'] ) ) ? $_args['value'] : null;
        }

        $settings['on_boarding_pages'] = On_Boarding_Pages::get_rest_posts();

        return $settings;
    }

}