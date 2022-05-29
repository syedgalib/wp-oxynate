<?php

/**
 * Get taxonomy terms.
 *
 * @param int     $post_id  Post id.
 * @param string  $taxonomy Taxonomy slug.
 *
 * @return array
 */
function wp_oxynate_get_taxonomy_terms( $post_id, $taxonomy = '' ) {
    $terms = array();

    foreach ( wp_oxynate_get_object_terms( $post_id, $taxonomy ) as $term ) {
        $_term = wp_oxynate_rest_get_term_data( $term );

        $terms[] = $_term;
    }

    return $terms;
}

/**
 * Helper to get cached object terms and filter by field using wp_list_pluck().
 * Works as a cached alternative for wp_get_post_terms() and wp_get_object_terms().
 *
 * @param  int    $object_id Object ID.
 * @param  string $taxonomy  Taxonomy slug.
 * @param  string $field     Field name.
 * @param  string $index_key Index key name.
 * @return array
 */
function wp_oxynate_get_object_terms( $object_id, $taxonomy, $field = null, $index_key = null ) {
	// Test if terms exists. get_the_terms() return false when it finds no terms.
	$terms = get_the_terms( $object_id, $taxonomy );

	if ( ! $terms || is_wp_error( $terms ) ) {
		return array();
	}

	return is_null( $field ) ? $terms : wp_list_pluck( $terms, $field, $index_key );
}


/**
 * Wp Oxynate Get User Bookmarks
 * 
 * @return array User Bookmarks
 */
function wp_oxynate_get_user_bookmarks( $user_id ) {
    $bookmarks = get_user_meta( $user_id, 'wp_oxynate_bookmarks', true );

	if ( ! empty( $bookmarks ) && is_array( $bookmarks ) ) {
		$bookmarks = wp_oxynate_prepare_user_bookmarks( $bookmarks );
	} else {
		$bookmarks = array();
	}

	/**
	 * User favorite bookmarks filter hook.
	 *
	 * @param array $bookmarks
	 * @param int $user_id
	 */
	$bookmarks = apply_filters( 'wp_oxynate_user_bookmarks', $bookmarks, $user_id );

	return $bookmarks;
}

/**
 * WP Oxynate Prepare User Bookmarks
 * 
 * @return array User Bookmarks
 */
function wp_oxynate_prepare_user_bookmarks( $bookmarks ) {
    $bookmarks = array_values( $bookmarks );
	$bookmarks = array_map( 'absint', $bookmarks );
	$bookmarks = array_filter( $bookmarks );
	$bookmarks = array_unique( $bookmarks );

    return $bookmarks;   
}

/*
 * Clean variables using sanitize_text_field. Arrays are cleaned recursively.
 * Non-scalar values are ignored.
 *
 * @param string|array $var Data to sanitize.
 * @return string|array
 */
function wp_oxynate_clean( $var ) {
    if ( is_array( $var ) ) {
        return array_map( 'wp_oxynate_clean', $var );
    } else {
        return is_scalar( $var ) ? sanitize_text_field( $var ) : $var;
    }
}

/**
 * Upload image from URL.
 *
 * Copied from wc_rest_upload_image_from_url
 *
 * @param string $image_url Image URL.
 * @return array|WP_Error Attachment data or error message.
 */
function wp_oxynate_rest_upload_image_from_url( $image_url ) {
	$parsed_url = wp_parse_url( $image_url );

	// Check parsed URL.
	if ( ! $parsed_url || ! is_array( $parsed_url ) ) {
		/* translators: %s: image URL */
		return new WP_Error( 'wp_oxynate_rest_invalid_image_url', sprintf( __( 'Invalid URL %s.', 'wp-oxynate' ), $image_url ), array( 'status' => 400 ) );
	}

	// Ensure url is valid.
	$image_url = esc_url_raw( $image_url );

	// download_url function is part of wp-admin.
	if ( ! function_exists( 'download_url' ) ) {
		include_once ABSPATH . 'wp-admin/includes/file.php';
	}

	$file_array         = array();
	$file_array['name'] = basename( current( explode( '?', $image_url ) ) );

	// Download file to temp location.
	$file_array['tmp_name'] = download_url( $image_url );

	// If error storing temporarily, return the error.
	if ( is_wp_error( $file_array['tmp_name'] ) ) {
		return new WP_Error(
			'wp_oxynate_rest_invalid_remote_image_url',
			/* translators: %s: image URL */
			sprintf( __( 'Error getting remote image %s.', 'wp-oxynate' ), $image_url ) . ' '
			/* translators: %s: error message */
			. sprintf( __( 'Error: %s', 'wp-oxynate' ), $file_array['tmp_name']->get_error_message() ),
			array( 'status' => 400 )
		);
	}

	// Do the validation and storage stuff.
	$file = wp_handle_sideload(
		$file_array,
		array(
			'test_form' => false,
			'mimes'     => wp_oxynate_rest_allowed_image_mime_types(),
		),
		current_time( 'Y/m' )
	);

	if ( isset( $file['error'] ) ) {
		@unlink( $file_array['tmp_name'] ); // @codingStandardsIgnoreLine.

		/* translators: %s: error message */
		return new WP_Error( 'wp_oxynate_rest_invalid_image', sprintf( __( 'Invalid image: %s', 'wp-oxynate' ), $file['error'] ), array( 'status' => 400 ) );
	}

	do_action( 'wp_oxynate_rest_api_uploaded_image_from_url', $file, $image_url );

	return $file;
}

/**
 * Returns image mime types users are allowed to upload via the API.
 *
 * Copied from wc_rest_allowed_image_mime_types
 *
 * @return array
 */
function wp_oxynate_rest_allowed_image_mime_types() {
	return apply_filters(
		'wp_oxynate_rest_allowed_image_mime_types',
		array(
			'jpg|jpeg|jpe' => 'image/jpeg',
			// 'gif'          => 'image/gif',
			'png'          => 'image/png',
			// 'bmp'          => 'image/bmp',
			// 'tiff|tif'     => 'image/tiff',
			// 'ico'          => 'image/x-icon',
		)
	);
}

/**
 * Set uploaded image as attachment.
 *
 * Copied from wc_rest_set_uploaded_image_as_attachment
 *
 * @param array $upload Upload information from wp_upload_bits.
 * @param int   $id Post ID. Default to 0.
 * @return int Attachment ID
 */
function wp_oxynate_rest_set_uploaded_image_as_attachment( $upload, $id = 0 ) {
	$info    = wp_check_filetype( $upload['file'] );
	$title   = '';
	$content = '';

	if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
		include_once ABSPATH . 'wp-admin/includes/image.php';
	}

	$image_meta = wp_read_image_metadata( $upload['file'] );
	if ( $image_meta ) {
		if ( trim( $image_meta['title'] ) && ! is_numeric( sanitize_title( $image_meta['title'] ) ) ) {
			$title = wp_oxynate_clean( $image_meta['title'] );
		}
		if ( trim( $image_meta['caption'] ) ) {
			$content = wp_oxynate_clean( $image_meta['caption'] );
		}
	}

	$attachment = array(
		'post_mime_type' => $info['type'],
		'guid'           => $upload['url'],
		'post_parent'    => $id,
		'post_title'     => $title ? $title : basename( $upload['file'] ),
		'post_content'   => $content,
	);

	$attachment_id = wp_insert_attachment( $attachment, $upload['file'], $id );
	if ( ! is_wp_error( $attachment_id ) ) {
		wp_update_attachment_metadata( $attachment_id, wp_generate_attachment_metadata( $attachment_id, $upload['file'] ) );
	}

	return $attachment_id;
}

/**
 * WP Oxynate Get Term Parents List
 * 
 * @param object $term_id
 * @param string $taxonomy
 * 
 * @return array Terms with parents
 */
function wp_oxynate_get_term_parents_list( $term_id, $taxonomy ) {

	if ( empty( $term_id ) ) {
		return [];
	}

	$parents_list = get_term_parents_list( $term_id, $taxonomy, [
        'format'    => 'slug',
        'separator' => ',',
        'link'      => false
    ]);

    $parents_list_by_slug = ( ! empty( $parents_list ) ) ? explode( ',', trim( $parents_list, ',' ) ) : [];
    $parents_list = [];

    foreach( $parents_list_by_slug as $slug ) {
        $term = get_term_by( 'slug', $slug, $taxonomy );

        if ( empty( $term ) || is_wp_error( $term ) ) {
            continue;
        }

        $parents_list[] = $term;

    }

	return $parents_list;
}

/**
 * WP Oxynate Rest Location by Area Types
 * 
 * @param int $term_id
 * @return array Location by area types
 */
function wp_oxynate_rest_get_location_by_area_types( $term_id ) {

	$area_types = [ 'district', 'area' ];
	$location_parent_list = wp_oxynate_get_term_parents_list( $term_id, WP_OXYNATE_TERM_LOCATION );

	if ( empty( $location_parent_list ) ) {
		return [];
	}

	$count     = 0;
	$locations = [];

	foreach( $location_parent_list as $location ) {

		if ( $count >= count( $area_types ) ) {
			break;
		}

		$area_type = $area_types[ $count ];
		$locations[ $area_type ] = wp_oxynate_rest_get_term_data( $location );

		$count++;

	}

	return $locations;
}

/**
 * Wp Oxynate Rest Get Term Data
 * 
 * @param object $term
 * @return array|null
 */
function wp_oxynate_rest_get_term_data( $term ) {

	$term_data = [];

	$term_data['id'] = $term->term_id;
	$term_data['name'] = $term->name;
	$term_data['slug'] = $term->slug;

	return $term_data;
}

/**
 * Get term top ancestor
 * 
 * @param int $term_id
 * @param string $taxonomy
 * @param string $return_type
 * 
 * @return mixed|null
 */
function wp_oxynate_get_term_top_ancestor( $term_id, $taxonomy, $return_type = 'object' ) {
	$ancestors = get_term_parents_list( $term_id, $taxonomy, [
		'format'    => 'slug',
		'separator' => ',',
		'link'      => false,
	]);

	$ancestors = ( ! empty( $ancestors ) ) ? explode( ',', rtrim( $ancestors, ',' ) ) : [];
	$ancestor  = ( ! empty( $ancestors ) ) ? $ancestors[0] : '';
	$ancestor  = ( ! empty( $ancestor ) ) ? get_term_by( 'slug', $ancestor, $taxonomy ) : '';

	if ( empty( $ancestor ) ) {
		return null;
	}

	if ( 'id' == $return_type ) {
		return $ancestor->term_id;
	}

	return $ancestor;
}

/**
 * Get User Avater
 * 
 * @param int $id
 * 
 * @return array|null User Avater or null
 */
function wp_oxynate_get_user_avater( $id ) {

	$image_id = get_user_meta( $id, WP_OXYNATE_USER_META_AVATER, true );

	if ( ! $image_id ) {
		return null;
	}

	if ( ! $image_id ) {
		return null;
	}

	$attachment = get_post( $image_id );

	if ( empty( $attachment ) ) {
		return null;
	}

	$avater = [
		'id'                => (int) $image_id,
		'date_created'      => $attachment->post_date,
		'date_created_gmt'  => $attachment->post_date_gmt,
		'date_modified'     => $attachment->post_modified,
		'date_modified_gmt' => $attachment->post_modified_gmt,
		'src'               => wp_get_attachment_url( $image_id ),
	];

	return $avater;

}
