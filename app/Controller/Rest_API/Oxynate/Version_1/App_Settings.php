<?php

namespace Oxynate\Controller\Rest_API\Oxynate\Version_1;

use \WP_REST_Server;
use \WP_Error;

use Oxynate\Module\Settings_Panel\Model as Settings_Panel_Model;
use Oxynate\Controller\Rest_API\Oxynate\Version_1\Helper\Rest_Base;

class App_Settings extends Rest_Base {

    /**
	 * Rest Base
	 *
	 * @var string
	 */
    protected $rest_base = 'app-settings';

    /**
	 * Register the routes for terms.
	 */
	public function register_routes() {
		register_rest_route( $this->namespace, '/' . $this->rest_base, array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_items' ],
				'permission_callback' => [ $this, 'get_items_permissions_check' ],
				'args'                => $this->get_collection_params(),
			),

			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'update_item' ),
				'permission_callback' => array( $this, 'update_item_permissions_check' ),
				'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
			),

			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'delete_item' ),
				'permission_callback' => array( $this, 'delete_item_permissions_check' ),
				'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
			),
			
			'schema' => array( $this, 'get_public_item_schema' ),
		) );
		
	}

	/**
	 * Check if a given request has access to read the users.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function get_items_permissions_check( $request ) {
		$permissions = $this->check_permissions( $request, 'read' );
		
		if ( is_wp_error( $permissions ) ) {
			return $permissions;
		}

		if ( ! $permissions ) {
			return new WP_Error( 'wp_oxynate_rest_cannot_view', __( 'Sorry, you cannot list resources.', 'wp_oxynate' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return $permissions;
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
	 * Check if a given request has access to read a user.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function get_item_permissions_check( $request ) {

		return true;

		$permissions = $this->check_permissions( $request, 'read' );
		if ( is_wp_error( $permissions ) ) {
			return $permissions;
		}

		if ( ! $permissions ) {
			return new WP_Error( 'wp_oxynate_rest_cannot_view', __( 'Sorry, you cannot view this resource.', 'wp_oxynate' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return $permissions;
	}

	/**
	 * Check if a given request has access to update a user.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function update_item_permissions_check( $request ) {

		return true;

		$permissions = $this->check_permissions( $request, 'edit' );
		if ( is_wp_error( $permissions ) ) {
			return $permissions;
		}

		if ( ! $permissions ) {
			return new WP_Error( 'wp_oxynate_rest_cannot_edit', __( 'Sorry, you are not allowed to edit this resource.', 'wp_oxynate' ), array( 'status' => rest_authorization_required_code() ) );
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

		return true;

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
		$id = intval( $request['id'] );
		if ( $id ) {
			$user = get_userdata( $id );

			if ( empty( $user ) ) {
				return new WP_Error( 'wp_oxynate_rest_user_invalid', __( 'Resource does not exist.', 'wp_oxynate' ), array( 'status' => 404 ) );
			}

			return $this->rest_check_user_permissions( $context, $user->ID );
		}

		return $this->rest_check_user_permissions( $context );
	}

	/**
	 * Check permissions of users on REST API.
	 *
	 * Copied from wc_rest_check_user_permissions
	 *
	 * @param string $context   Request context.
	 * @param int    $object_id Post ID.
	 * @return bool
	 */
	protected function rest_check_user_permissions( $context = 'read', $object_id = 0 ) {
		$contexts = array(
			'read'   => 'edit_user',
			'create' => 'promote_users',
			'edit'   => 'edit_user',
			'delete' => 'delete_users',
			'batch'  => 'promote_users',
		);

		$permission = current_user_can( $contexts[ $context ], $object_id );

		return apply_filters( 'wp_oxinate_rest_check_permissions', $permission, $context, $object_id, 'user' );
	}

	/**
	 * Get all users.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_items( $request ) {

		$key = ( isset( $request[ 'key'] ) ) ? sanitize_key( $request[ 'key'] ) : '';

		$response = Settings_Panel_Model::get_options();

		if ( ! empty( $key ) && isset( $response[ $key ] ) ) {
			$response = $response[ $key ];
		}

		$response = rest_ensure_response( $response );

		return $response;
	}

	/**
	 * Update Settings
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function update_item( $request ) {
		$options  = $request['options'];
		$options  = Settings_Panel_Model::get_validated_options( $options );
		$response = Settings_Panel_Model::update_options( $options );
		$response = rest_ensure_response( $response );

		$response->set_status( 201 );

		return $response;
	}

	/**
	 * Update Settings
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function delete_item( $request ) {
		$key      = ( isset( $request[ 'key' ] ) ) ? sanitize_text_field( $request[ 'key' ] ) : '';
		$response = Settings_Panel_Model::delete_option( $key );
		$response = rest_ensure_response( $response );

		return $response;
	}

	/**
	 * Get the query params for collections.
	 *
	 * @return array
	 */
	public function get_collection_params() {
		$params = parent::get_collection_params();

		$params['context']['default'] = 'view';

		$params['key'] = array(
			'description'       => __( 'Limit result set to specific settings key', 'wp_oxynate' ),
			'type'              => 'string',
			'default'           => null,
			'sanitize_callback' => 'sanitize_key',
		);
		
		return $params;
	}
}