<?php

function simple_todo_get_template( string $path = '', array $data = [], bool $extract = true ) {

    $file = WP_OXYNATE_TEMPLATE_PATH . $path . '.php';

    if ( ! file_exists( $file ) ) {
        return;
    }

    if ( $extract ) {
        extract( $data );
    }
    
    include $file;
}

function simple_todo_get_view( string $path = '', array $data = [], bool $extract = true ) {

    $file = WP_OXYNATE_VIEW_PATH . $path . '.php';

    if ( ! file_exists( $file ) ) {
        return;
    }

    if ( $extract ) {
        extract( $data );
    }
    
    include $file;
}