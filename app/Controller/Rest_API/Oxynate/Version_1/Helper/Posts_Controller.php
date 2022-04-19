<?php

namespace Oxynate\Controller\Rest_API\Oxynate\Version_1\Helper;

use \WP_Error;

abstract class Posts_Controller extends Rest_Base {

    protected $post_type = '';

    /**
     * Check permissions for the posts.
     *
     * @param WP_REST_Request $request Current request.
     */
    public function get_items_permissions_check( $request ) {

        if ( ! $this->check_post_permissions( $this->post_type, 'read' ) ) {
			return new WP_Error( 'wp_oxynate_rest_cannot_view', __( 'Sorry, you cannot list resources.', 'wp-oxynate' ), [ 'status' => rest_authorization_required_code() ] );
		}

        return true;
    }

	/**
	 * Check if a given request has access to create an item.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function create_item_permissions_check( $request ) {

		if ( ! $this->check_post_permissions( $this->post_type, 'create' ) ) {
			return new WP_Error( 'wp_oxynate_rest_cannot_create', __( 'Sorry, you are not allowed to create resources.', 'wp-oxynate' ), [ 'status' => rest_authorization_required_code() ] );
		}

		return true;
	}

	/**
	 * Check if a given request has access to read an item.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function get_item_permissions_check( $request ) {

		$post = get_post( (int) $request['id'] );

		if ( $post && ! $this->check_post_permissions( $this->post_type, 'read', $post->ID ) ) {
			return new WP_Error( 'wp_oxynate_rest_cannot_view', __( 'Sorry, you cannot view this resource.', 'wp-oxynate' ), [ 'status' => rest_authorization_required_code() ] );
		}

		return true;
	}

	/**
	 * Check if a given request has access to update an item.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function update_item_permissions_check( $request ) {

		$post = get_post( (int) $request['id'] );

		if ( $post && ! $this->check_post_permissions( $this->post_type, 'edit', $post->ID ) ) {
			return new WP_Error( 'wp_oxynate_rest_cannot_edit', __( 'Sorry, you are not allowed to edit this resource.', 'wp-oxynate' ), [ 'status' => rest_authorization_required_code() ] );
		}

		return true;
	}

	/**
	 * Check if a given request has access to delete an item.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return bool|WP_Error
	 */
	public function delete_item_permissions_check( $request ) {

		$post = get_post( (int) $request['id'] );

		if ( $post && ! $this->check_post_permissions( $this->post_type, 'delete', $post->ID ) ) {
			return new WP_Error( 'wp_oxynate_rest_cannot_delete', __( 'Sorry, you are not allowed to delete this resource.', 'wp-oxynate' ), [ 'status' => rest_authorization_required_code() ] );
		}

		return true;
	}

    /**
	 * Check permissions of posts on REST API.
	 *
	 * @param string $post_type Post type.
	 * @param string $context   Request context.
	 * @param int    $object_id Post ID.
	 * @return bool
	 */
	protected function check_post_permissions( $post_type, $context = 'read', $object_id = 0 ) {
		
		$contexts = [
			'read'   => 'read_private_posts',
			'create' => 'publish_posts',
			'edit'   => 'edit_post',
			'delete' => 'delete_post',
		];

		if ( 'revision' === $post_type ) {
			$permission = false;
		} else {
			$cap              = $contexts[ $context ];
			$post_type_object = get_post_type_object( $post_type );
			$permission       = current_user_can( $post_type_object->cap->$cap, $object_id );
		}

		return apply_filters( 'wp_oxynate_rest_check_permissions', $permission, $context, $object_id, $post_type );
	}

	/**
	 * Get all the WP Query vars that are allowed for the API request.
	 *
	 * @return array
	 */
	protected function get_allowed_query_vars() {

		global $wp;

		/**
		 * Filter the publicly allowed query vars.
		 *
		 * Allows adjusting of the default query vars that are made public.
		 *
		 * @param array  Array of allowed WP_Query query vars.
		 */
		$valid_vars = apply_filters( 'query_vars', $wp->public_query_vars );
		$post_type_obj = get_post_type_object( $this->post_type );

		if ( current_user_can( $post_type_obj->cap->edit_posts ) ) {
			/**
			 * Filter the allowed 'private' query vars for authorized users.
			 *
			 * If the user has the `edit_posts` capability, we also allow use of
			 * private query parameters, which are only undesirable on the
			 * frontend, but are safe for use in query strings.
			 *
			 * To disable anyway, use
			 * `add_filter( 'wp_oxynate_rest_private_query_vars', '__return_empty_array' );`
			 *
			 * @param array $private_query_vars Array of allowed query vars for authorized users.
			 *
			 */
			$private = apply_filters( 'wp_oxynate_rest_private_query_vars', $wp->private_query_vars );
			$valid_vars = array_merge( $valid_vars, $private );
		}

		// Define our own in addition to WP's normal vars.
		$rest_valid = array(
			'date_query',
			'ignore_sticky_posts',
			'offset',
			'post__in',
			'post__not_in',
			'post_parent',
			'post_parent__in',
			'post_parent__not_in',
			'posts_per_page',
			'meta_query',
			'tax_query',
			'meta_key',
			'meta_value',
			'meta_compare',
			'meta_value_num',
		);
		$valid_vars = array_merge( $valid_vars, $rest_valid );

		/**
		 * Filter allowed query vars for the REST API.
		 *
		 * This filter allows you to add or remove query vars from the final allowed
		 * list for all requests, including unauthenticated ones. To alter the
		 * vars for editors only.
		 *
		 * @param array {
		 *    Array of allowed WP_Query query vars.
		 *
		 *    @param string $allowed_query_var The query var to allow.
		 * }
		 */
		$valid_vars = apply_filters( 'wp_oxynate_rest_query_vars', $valid_vars );

		return $valid_vars;
	}

	/**
	 * Get the images for a post
	 *
	 * @param WP_Post $post
	 *
	 * @return array
	 */
	protected function get_images( $post ) {

		$images         = array();
		$attachment_ids = array();

		// Add featured image.
		if ( has_post_thumbnail( $post ) ) {
			$attachment_ids[] = get_post_thumbnail_id( $post );
		}

		// Build image data.
		foreach ( $attachment_ids as $position => $attachment_id ) {

			$attachment_post = get_post( $attachment_id );

			if ( is_null( $attachment_post ) ) {
				continue;
			}

			$attachment = wp_get_attachment_image_src( $attachment_id, 'full' );

			if ( ! is_array( $attachment ) ) {
				continue;
			}

			$images[] = array(
				'id'                => (int) $attachment_id,
				'date_created'      => $attachment_post->post_date,
				'date_created_gmt'  => strtotime( $attachment_post->post_date_gmt ),
				'date_modified'     => $attachment_post->post_modified,
				'date_modified_gmt' => strtotime( $attachment_post->post_modified_gmt ),
				'src'               => current( $attachment ),
				'name'              => get_the_title( $attachment_id ),
				'alt'               => get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ),
				'position'          => (int) $position,
			);
		}

		// Set a placeholder image if the listing has no images set.
		if ( empty( $images ) ) {
			// $images[] = array(
			// 	'id'                => 0,
			// 	'date_created'      => wp_oxynaterest_prepare_date_response( current_time( 'mysql' ), false ), // Default to now.
			// 	'date_created_gmt'  => wp_oxynaterest_prepare_date_response( time() ), // Default to now.
			// 	'date_modified'     => wp_oxynaterest_prepare_date_response( current_time( 'mysql' ), false ),
			// 	'date_modified_gmt' => wp_oxynaterest_prepare_date_response( time() ),
			// 	'src'               => wp_oxynateplaceholder_img_src(),
			// 	'name'              => __( 'Placeholder', 'wp-oxynate' ),
			// 	'alt'               => __( 'Placeholder', 'wp-oxynate' ),
			// 	'position'          => 0,
			// );

			$images = null;
		}

		return $images;
	}

	/**
	 * Delete a single post.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function delete_item( $request ) {
		$post_type = $this->post_type;
		$force    = isset( $request['force'] ) ? (bool) $request['force'] : false;

		// We don't support trashing for this type, error out.
		if ( ! $force ) {
			return new WP_Error( 'wp_oxynate_rest_trash_not_supported', __( 'Resource does not support trashing.', 'wp-oxynate' ), array( 'status' => 501 ) );
		}

		$post = get_post( (int) $request['id'] );

		$request->set_param( 'context', 'edit' );
		$response = $this->prepare_item_for_response( $post, $request );

		$retval = wp_delete_post( $post->ID, true );

		if ( ! $retval ) {
			return new WP_Error( 'wp_oxynate_rest_cannot_delete ' . $request['id'], __( 'The resource cannot be deleted.', 'wp-oxynate' ), array( 'status' => 500 ) );
		}

		/**
		 * Fires after a single term is deleted via the REST API.
		 *
		 * @param WP_Term          $term     The deleted term.
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 */
		do_action( "wp_oxynate_rest_delete_{$post_type}", $post, $response, $request );

		return $response;
	}
}