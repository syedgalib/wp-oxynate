<?php
// General
defined( 'WP_OXYNATE_VERSION' ) || define( 'WP_OXYNATE_VERSION', '1.0.0' );
defined( 'WP_OXYNATE_FILE' ) || define( 'WP_OXYNATE_FILE', dirname( dirname( __FILE__ ) ) . '/wp-oxynate.php' );
defined( 'WP_OXYNATE_BASE' ) || define( 'WP_OXYNATE_BASE', dirname( dirname( __FILE__ ) ) . '/' );

// Template Paths
defined( 'WP_OXYNATE_TEMPLATE_PATH' ) || define( 'WP_OXYNATE_TEMPLATE_PATH', WP_OXYNATE_BASE . 'template/' );
defined( 'WP_OXYNATE_VIEW_PATH' ) || define( 'WP_OXYNATE_VIEW_PATH', WP_OXYNATE_BASE . 'view/' );

// Asset Config
defined( 'WP_OXYNATE_SCRIPT_VERSION' ) || define( 'WP_OXYNATE_SCRIPT_VERSION', WP_OXYNATE_VERSION );
defined( 'WP_OXYNATE_LOAD_MIN_FILES' ) || define( 'WP_OXYNATE_LOAD_MIN_FILES', ! SCRIPT_DEBUG );

// Asset Paths
defined( 'WP_OXYNATE_URL' ) || define( 'WP_OXYNATE_URL', plugin_dir_url( WP_OXYNATE_FILE ) );
defined( 'WP_OXYNATE_ASSET_URL' ) || define( 'WP_OXYNATE_ASSET_URL', WP_OXYNATE_URL . 'assets/dist/' );
defined( 'WP_OXYNATE_JS_PATH' ) || define( 'WP_OXYNATE_JS_PATH',  WP_OXYNATE_ASSET_URL . 'js/' );
defined( 'WP_OXYNATE_CSS_PATH' ) || define( 'WP_OXYNATE_CSS_PATH',  WP_OXYNATE_ASSET_URL . 'css/' );

// Post Types
defined( 'WP_OXYNATE_POST_TYPE_DONATION_REQUEST' ) || define( 'WP_OXYNATE_POST_TYPE_DONATION_REQUEST', 'donation-request' );
defined( 'WP_OXYNATE_POST_TYPE_ON_BOARDING_PAGES' ) || define( 'WP_OXYNATE_POST_TYPE_ON_BOARDING_PAGES', 'on-boarding-pages' );
defined( 'WP_OXYNATE_POST_TYPE_ADMIN_PAGE' ) || define( 'WP_OXYNATE_POST_TYPE_ADMIN_PAGE', 'oxynate-admin-page' );

// Taxonomy
defined( 'WP_OXYNATE_TERM_LOCATION' ) || define( 'WP_OXYNATE_TERM_LOCATION', 'wp-oxynate-location' );
defined( 'WP_OXYNATE_TERM_BLOOD_GROUP' ) || define( 'WP_OXYNATE_TERM_BLOOD_GROUP', 'wp-oxynate-blood-group' );

// Users Meta Keys
defined( 'WP_OXYNATE_USER_META_AVATER' ) || define( 'WP_OXYNATE_USER_META_AVATER', 'wp_oxynate_user_avater' );
defined( 'WP_OXYNATE_USER_META_GENDER' ) || define( 'WP_OXYNATE_USER_META_GENDER', 'wp_oxynate_gender' );
defined( 'WP_OXYNATE_USER_META_BLOOD_GROUP' ) || define( 'WP_OXYNATE_USER_META_BLOOD_GROUP', 'wp_oxynate_blood_group' );
defined( 'WP_OXYNATE_USER_META_HEMOGLOBIN' ) || define( 'WP_OXYNATE_USER_META_HEMOGLOBIN', 'wp_oxynate_hemoglobin' );
defined( 'WP_OXYNATE_USER_META_PHONE' ) || define( 'WP_OXYNATE_USER_META_PHONE', 'wp_oxynate_phone' );
defined( 'WP_OXYNATE_USER_META_LOCATION' ) || define( 'WP_OXYNATE_USER_META_LOCATION', 'wp_oxynate_location' );
defined( 'WP_OXYNATE_USER_META_ADDRESS' ) || define( 'WP_OXYNATE_USER_META_ADDRESS', 'wp_oxynate_address' );
defined( 'WP_OXYNATE_USER_META_IS_DONOR' ) || define( 'WP_OXYNATE_USER_META_IS_DONOR', 'wp_oxynate_is_donor' );
defined( 'WP_OXYNATE_USER_META_LAST_DONATION_DATE' ) || define( 'WP_OXYNATE_USER_META_LAST_DONATION_DATE', 'wp_oxynate_last_donation_date' );
defined( 'WP_OXYNATE_USER_META_IS_PUBLIC_CONTACT_NUMBER' ) || define( 'WP_OXYNATE_USER_META_IS_PUBLIC_CONTACT_NUMBER', 'wp_oxynate_is_public_contact_number' );
defined( 'WP_OXYNATE_USER_META_BOOKMARKS' ) || define( 'WP_OXYNATE_USER_META_BOOKMARKS', 'wp_oxynate_bookmarks' );

// Post Meta Keys
defined( 'WP_OXYNATE_POST_META_GENDER' ) || define( 'WP_OXYNATE_POST_META_GENDER', 'wp_oxynate_gender' );
defined( 'WP_OXYNATE_POST_META_HEMOGLOBIN' ) || define( 'WP_OXYNATE_POST_META_HEMOGLOBIN', 'wp_oxynate_hemoglobin' );
defined( 'WP_OXYNATE_POST_META_PHONE' ) || define( 'WP_OXYNATE_POST_META_PHONE', 'wp_oxynate_phone' );
defined( 'WP_OXYNATE_POST_META_ADDRESS' ) || define( 'WP_OXYNATE_POST_META_ADDRESS', 'wp_oxynate_address' );
defined( 'WP_OXYNATE_POST_META_LATITUDE' ) || define( 'WP_OXYNATE_POST_META_LATITUDE', 'wp_oxynate_latitude' );
defined( 'WP_OXYNATE_POST_META_LONGITUDE' ) || define( 'WP_OXYNATE_POST_META_LONGITUDE', 'wp_oxynate_longitude' );
defined( 'WP_OXYNATE_POST_META_IMAGES' ) || define( 'WP_OXYNATE_POST_META_IMAGES', 'wp_oxynate_images' );