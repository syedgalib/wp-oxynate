<?php

namespace Oxynate\Controller\Rest_API\Oxynate\Version_1;

use \WP_REST_Server;
use \WP_Error;

use Oxynate\Controller\Rest_API\Oxynate\Version_1\Helper\Rest_Base;

class Users_Bookmark extends Rest_Base {

    /**
	 * Rest Base
	 *
	 * @var string
	 */
	protected $rest_base = 'users/(?P<user_id>[\d]+)/bookmark';

    /**
	 * Register the routes for terms.
	 */
	public function register_routes() {
		register_rest_route( $this->namespace, '/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_item' ),
					'permission_callback' => array( $this, 'create_item_permissions_check' ),
					'args'                => array_merge(
						$this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
						array(
							'id' => array(
								'type'        => 'integer',
								'description' => __( 'Donation request ID.', 'wp-oxynate' ),
								'required'    => true,
							),
						)
					),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		register_rest_route( $this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)', array(
			'args' => array(
				'user_id' => array(
					'description' => __( 'User id.', 'wp-oxynate' ),
					'type'        => 'integer',
				),
				'id' => array(
					'description' => __( 'Donation request ID.', 'wp-oxynate' ),
					'type'        => 'integer',
				),
			),
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'delete_item' ),
				'permission_callback' => array( $this, 'delete_item_permissions_check' ),
				'args'                => array(),
			),
			'schema' => array( $this, 'get_public_item_schema' ),
		) );
	}

	/**
	 * Check if a given request has access to create a user.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function create_item_permissions_check( $request ) {
		$permissions = $this->check_permissions( $request, 'create' );
		if ( is_wp_error( $permissions ) ) {
			return $permissions;
		}

		if ( ! $permissions || ! get_option( 'users_can_register' ) ) {
			return new WP_Error( 'wp_oxynate_rest_cannot_create', __( 'Sorry, you are not allowed to create resources.', 'wp_oxynate' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return $permissions;
	}

	/**
	 * Check if a given request has access to delete a user.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function delete_item_permissions_check( $request ) {
		$permissions = $this->check_permissions( $request, 'delete' );
		if ( is_wp_error( $permissions ) ) {
			return $permissions;
		}

		if ( ! $permissions ) {
			return new WP_Error( 'wp_oxynate_rest_cannot_delete', __( 'Sorry, you are not allowed to delete this resource.', 'wp_oxynate' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return $permissions;
	}

	/**
	 * Check permissions.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @param string          $context Request context.
	 * @return bool|WP_Error
	 */
	protected function check_permissions( $request, $context = 'read' ) {
		// Check permissions for a single user.
		$id = intval( $request['user_id'] );
		if ( $id ) {
			$user = get_userdata( $id );

			if ( empty( $user ) ) {
				return new WP_Error( 'wp_oxynate_rest_user_invalid', __( 'Resource does not exist.', 'wp_oxynate' ), array( 'status' => 404 ) );
			}

			return $this->rest_check_user_favorite_permissions( $context, $user->ID );
		}

		return $this->rest_check_user_favorite_permissions( $context );
	}

	/**
	 * Check permissions of users favorite on REST API.
	 *
	 * @param string $context   Request context.
	 * @param int    $object_id Post ID.
	 * @return bool
	 */
	function rest_check_user_favorite_permissions( $context = 'read', $object_id = 0 ) {
		$contexts = array(
			'read'   => 'read',
			'create' => 'read',
			'edit'   => 'read',
			'delete' => 'read',
			'batch'  => 'read',
		);

		$permission = current_user_can( $contexts[ $context ], $object_id );

		return apply_filters( 'wp_oxynate_rest_check_permissions', $permission, $context, $object_id, 'user_favorite' );
	}

	/**
	 * Create a single user.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function create_item( $request ) {

		$user_id   = (int) $request['user_id'];
		$user_data = get_userdata( $user_id );

		if ( empty( $user_data ) ) {
			return new WP_Error( 'wp_oxynate_rest_invalid_user_id', __( 'Invalid user ID.', 'wp-oxynate' ), 400 );
		}

		$donation_request_id   = (int) $request['id'];
		$donation_request_data = get_post( $donation_request_id );

		if ( empty( $donation_request_data ) || get_post_type( $donation_request_data ) != WP_OXYNATE_POST_TYPE_DONATION_REQUEST ) {
			return new WP_Error( 'wp_oxynate_rest_invalid_donation_request_id', __( 'Invalid donation request id ID.', 'wp-oxynate' ), 400 );
		}

		$old_bookmarks = wp_oxynate_get_user_bookmarks( $user_id );
		$new_bookmarks = wp_oxynate_add_user_bookmarks( $user_id, $donation_request_id );

		$data = array(
			'id'            => $donation_request_id,
			'old_bookmarks' => $old_bookmarks,
			'new_bookmarks' => $new_bookmarks,
		);

		/**
		 * Fires after a user bookmark is created or updated via the REST API.
		 *
		 * @param array           $new_bookmarks User bookmarks.
		 * @param WP_REST_Request $request   Request object.
		 * @param boolean         $creating  True when creating user, false when updating user.
		 */
		do_action( 'wp_oxynate_rest_insert_user_bookmarks', $new_bookmarks, $request, false );

		$request->set_param( 'context', 'edit' );
		$response = $this->prepare_item_for_response( $data, $request );
		$response = rest_ensure_response( $response );
		$response->set_status( 201 );

		return $response;
	}

	/**
	 * Delete a single activity.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function delete_item( $request ) {
		$user_id   = (int) $request['user_id'];
		$user_data = get_userdata( $user_id );

		if ( empty( $user_data ) ) {
			return new WP_Error( 'wp_oxynate_rest_invalid_user_id', __( 'Invalid user ID.', 'wp-oxynate' ), 400 );
		}

		$donation_request_id   = (int) $request['id'];
		$donation_request_data = get_post( $donation_request_id );

		if ( empty( $donation_request_data ) || get_post_type( $donation_request_data ) != WP_OXYNATE_POST_TYPE_DONATION_REQUEST ) {
			return new WP_Error( 'wp_oxynate_rest_invalid_listing_id', __( 'Invalid donation request id ID.', 'wp-oxynate' ), 400 );
		}

		$old_bookmarks = wp_oxynate_get_user_bookmarks( $user_id );

		wp_oxynate_delete_user_bookmarks( $user_id, $donation_request_id );

		$new_bookmarks = wp_oxynate_get_user_bookmarks( $user_id );

		$data = array(
			'id'            => $donation_request_id,
			'old_bookmarks' => $old_bookmarks,
			'new_bookmarks' => $new_bookmarks,
		);

		$request->set_param( 'context', 'edit' );
		$response = $this->prepare_item_for_response( $data, $request );

		/**
		 * Fires after a user bookmark is deleted via the REST API.
		 *
		 * @param arary            $new_bookmarks User bookmarks.
		 * @param WP_REST_Response $response      The response returned from the API.
		 * @param WP_REST_Request  $request       The request sent to the API.
		 */
		do_action( 'wp_oxynate_rest_delete_user_bookmark', $new_bookmarks, $response, $request );

		return $response;
	}

	/**
	 * Prepares a single user output for response.
	 *
	 * @param array           $data
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response object.
	 */
	public function prepare_item_for_response( $data, $request ) {
		$context  = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data     = $this->add_additional_fields_to_object( $data, $request );
		$data     = $this->filter_response_by_context( $data, $context );
		$response = rest_ensure_response( $data );

		/**
		 * Filters user data returned from the REST API.
		 *
		 * @param WP_REST_Response $response The response object.
		 * @param array          $data     Favorites listings id.
		 * @param WP_REST_Request  $request  Request object.
		 */
		return apply_filters( 'wp_oxynate_rest_prepare_user_bookmarks', $response, $data, $request );
	}

	/**
	 * Get the User's favorite schema, conforming to JSON Schema.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'favorites',
			'type'       => 'object',
			'properties' => array(
				'id' => array(
					'description' => __( 'User favorite listing id.', 'wp-oxynate' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );
	}
}