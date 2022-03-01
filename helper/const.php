<?php

if ( ! defined( 'WP_OXYNATE_VERSION' ) ) {
    define( 'WP_OXYNATE_VERSION', '1.0.0' );
}

if ( ! defined( 'WP_OXYNATE_SCRIPT_VERSION' ) ) {
    define( 'WP_OXYNATE_SCRIPT_VERSION', WP_OXYNATE_VERSION );
}

if ( ! defined( 'WP_OXYNATE_FILE' ) ) {
    define( 'WP_OXYNATE_FILE', dirname( dirname( __FILE__ ) ) . '/wp-oxynate.php' );
}

if ( ! defined( 'WP_OXYNATE_BASE' ) ) {
    define( 'WP_OXYNATE_BASE', dirname( dirname( __FILE__ ) ) . '/' );
}

if ( ! defined( 'WP_OXYNATE_POST_TYPE' ) ) {
    define( 'WP_OXYNATE_POST_TYPE', 'wp-oxynate' );
}

if ( ! defined( 'WP_OXYNATE_TEMPLATE_PATH' ) ) {
    define( 'WP_OXYNATE_TEMPLATE_PATH', WP_OXYNATE_BASE . 'template/' );
}

if ( ! defined( 'WP_OXYNATE_VIEW_PATH' ) ) {
    define( 'WP_OXYNATE_VIEW_PATH', WP_OXYNATE_BASE . 'view/' );
}

if ( ! defined( 'WP_OXYNATE_URL' ) ) {
    define( 'WP_OXYNATE_URL', plugin_dir_url( WP_OXYNATE_FILE ) );
}

if ( ! defined( 'WP_OXYNATE_ASSET_URL' ) ) {
    define( 'WP_OXYNATE_ASSET_URL', WP_OXYNATE_URL . 'assets/dist/' );
}

if ( ! defined( 'WP_OXYNATE_JS_PATH' ) ) {
    define( 'WP_OXYNATE_JS_PATH',  WP_OXYNATE_ASSET_URL . 'js/' );
}

if ( ! defined( 'WP_OXYNATE_CSS_PATH' ) ) {
    define( 'WP_OXYNATE_CSS_PATH', WP_OXYNATE_ASSET_URL . 'css/' );
}

if ( ! defined( 'WP_OXYNATE_LOAD_MIN_FILES' ) ) {
    define( 'WP_OXYNATE_LOAD_MIN_FILES', SCRIPT_DEBUG );
}