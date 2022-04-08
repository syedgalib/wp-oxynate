<?php

namespace Oxynate\Module\Settings_Panel;

class Fields_Template {

    /**
     * Get Field
     * 
     * @return void
     */
    public static function get_field( $field_key = '', $field_args = [] ) {

        $field_type = ( isset( $field_args['type'] ) ) ? $field_args['type'] : '';

        $callback = 'get_' . $field_type . '_field';

        if ( ! method_exists( new self, $callback ) ) {
            return;
        }

        self::$callback( $field_key, $field_args );
    }

    /**
     * Get Text Field
     * 
     * @return void
     */
    public static function get_text_field( $field_key = '', $field_args = [] ) {

        $value = ( isset( $field_args['value'] ) ) ? $field_args['value'] : '';

        echo '<input name="'. $field_key .'" type="text" value="'. $value .'" class="regular-text">';
    }

    /**
     * Get Tel Field
     * 
     * @return void
     */
    public static function get_tel_field( $field_key = '', $field_args = [] ) {

        $value = ( isset( $field_args['value'] ) ) ? $field_args['value'] : '';

        echo '<input name="'. $field_key .'" type="tel" value="'. $value .'" class="regular-text">';
    }

}