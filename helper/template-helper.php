<?php

/**
 * WP Oxinate Get Template
 * 
 * @return void|string Contents
 */
function wp_oxinate_get_template( $path = '', $data = [], $extract = true, $echo = true ) {
    
    $contents = wp_oxinate_get_file_content( WP_OXYNATE_TEMPLATE_PATH, $path, $data, $extract, false );

    if ( ! $echo ) {
        return $contents;
    }

    echo $contents;
}

/**
 * WP Oxinate Get View
 * 
 * @return void|string Contents
 */
function wp_oxinate_get_view( $path = '', $data = [], $extract = true, $echo = true ) {

    $contents = wp_oxinate_get_file_content( WP_OXYNATE_VIEW_PATH, $path, $data, $extract, false );

    if ( ! $echo ) {
        return $contents;
    }

    echo $contents;

    return;
}

/**
 * WP Oxinate Get File Content
 * 
 * @return void|string Contents
 */
function wp_oxinate_get_file_content( $base_path = '', $path = '', $data = [], $extract = true, $echo = true ) {

    $file = $base_path . $path . '.php';

    $content = '';

    if ( ! file_exists( $file ) ) {

        if ( ! $echo ) {
            return $content;
        }

        echo $content;

        return;
    }

    if ( $extract ) {
        extract( $data );
    }

    ob_start();
    
    include $file;

    $content = ob_get_clean();

    if ( ! $echo ) {
        return $content;
    }

    echo $content;
}