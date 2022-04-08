<?php

namespace Oxynate\Module\Settings_Panel;

class Model {

    private static $options_key = 'wp_oxynate_options';

    /**
     * Get Options
     * 
     * @return array Options
     */
    public static function get_options() {
        $options = [];

        $options['app_name'] = [
            'type'  => 'text',
            'label' => __( 'App Name', 'wp-oxynate' ),
            'value' => self::get_option_value( 'app_name', '' ),
        ];

        $options['admin_phone'] = [
            'type'  => 'tel',
            'label' => __( 'Admin Phone', 'wp-oxynate' ),
            'value' => self::get_option_value( 'admin_phone', '' ),
        ];

        return $options;
    }

    /**
     * Get Option Value
     * 
     * @param string $key Option Key
     * @param mixed $default Default Value
     * @return mixed
     */
    public static function get_option_value( $key = '', $default = '' ) {
        $options = get_option( self::$options_key, [] );
        $options = ( is_array( $options ) ) ? $options : [];

        $value = ( key_exists( $key, $options ) ) ? $options[ $key ] : $default;
        
        return $value;
    }

    /**
     * Update Options
     * 
     * @param array $values
     * @return void
     */
    public static function update_options( $new_options = [] ) {

        if ( ! is_array( $new_options ) ) {
            return;
        }

        $old_options = get_option( self::$options_key, [] );
        $old_options = ( is_array( $old_options ) ) ? $old_options : [];
        $options     = array_merge( $old_options, $new_options );

        update_option( self::$options_key, $options );
    }

    /**
     * Update Option
     * 
     * @param string $option_key Option Key
     * @param mixed $option_value Option Value
     * @return void
     */
    public static function update_option( $option_key = '', $option_value = '' ) {

        $options = get_option( self::$options_key, [] );
        $options = ( is_array( $options ) ) ? $options : [];

        $options[ $option_key ] = $option_value;

        update_option( self::$options_key, $options );
    }

}