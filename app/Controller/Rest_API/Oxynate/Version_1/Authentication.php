<?php

namespace Oxynate\Controller\Rest_API\Oxynate\Version_1;

use \WP_REST_Server;
use \WP_Error;
use Firebase\JWT\JWT;

use Oxynate\Controller\Rest_API\Oxynate\Version_1\Helper\Rest_Base;

class Authentication extends Rest_Base {

    /**
	 * Rest Base
	 *
	 * @var string
	 */
    protected $rest_base = 'authentication';

    /**
	 * Register the routes for terms.
	 */
	public function register_routes() {
		register_rest_route( 
			$this->namespace, 
			'/' . $this->rest_base . '/create-token',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'create_token' ],
					'permission_callback' => [ $this, 'create_item_permissions_check' ],
					'args'                => $this->get_collection_params(),
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
		return true;
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
	 * Create authentication token.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function create_token( $request ) {

		$secret_key = defined('JWT_AUTH_SECRET_KEY') ? JWT_AUTH_SECRET_KEY : false;

        /** First thing, check the secret key if not exist return a error*/
        if ( ! $secret_key ) {
            return new WP_Error(
                'jwt_auth_bad_config',
                __('JWT is not configurated properly, please contact the admin', 'wp-api-jwt-auth'),
                [
                    'status' => 403,
				]
            );
        }

		$user = null;

		$auth_type  = $request->get_param('auth-type');
		$email      = $request->get_param('email');
		$password   = $request->get_param('password');
		$auth_token = $request->get_param('auth-token');

		switch ( $auth_type ) {
			case 'email':
				$user = $this->authenticate_wp_user( $email, $password );
				break;
			case 'google':
				$user = $this->authenticate_google_user( $auth_token );
				break;
			case 'facebook':
				$user = $this->authenticate_facebook_user( $email );
				break;
		}

		if ( empty( $user  ) ) {
			return new WP_Error(
				'[jwt_auth] ' . 403,
				__( 'Couldn\'t authenticate the user', 'wp-oxynate' ),
				[
					'status' => 403,
				]
			);
		}

		if ( is_wp_error( $user  ) ) {
			$error_code = $user->get_error_code();

			return new WP_Error(
				'[jwt_auth] ' . $error_code,
				$user->get_error_message($error_code),
				array(
					'status' => 403,
				)
			);
		}

        /** Valid credentials, the user exists create the according Token */
        $issuedAt  = time();
        $notBefore = apply_filters('jwt_auth_not_before', $issuedAt, $issuedAt );
        $expire    = apply_filters('jwt_auth_expire', $issuedAt + ( DAY_IN_SECONDS * 7 ), $issuedAt );

        $token = array(
            'iss' => get_bloginfo('url'),
            'iat' => $issuedAt,
            'nbf' => $notBefore,
            'exp' => $expire,
            'data' => array(
                'user' => array(
                    'id' => $user->data->ID,
                ),
            ),
        );

        /** Let the user modify the token data before the sign. */
        $token = JWT::encode( apply_filters( 'jwt_auth_token_before_sign', $token, $user ), $secret_key, 'HS256' );

        /** The token is signed, now create the object with no sensible user data to the client*/
        $data = array(
            'token' => $token,
            'user_email' => $user->data->user_email,
            'user_nicename' => $user->data->user_nicename,
            'user_display_name' => $user->data->display_name,
        );

        /** Let the user modify the data before send it back */
        $response = apply_filters('jwt_auth_token_before_dispatch', $data, $user);
		$response = rest_ensure_response( $response );

		return $response;
	}

	/**
	 * Authenticate WP User
	 * 
	 * @return WP_USER|bool
	 */
	public function authenticate_wp_user( $email_or_username = '', $password = '' ) {
		return wp_authenticate( $email_or_username, $password );
	}

	/**
	 * Authenticate Google User
	 * 
	 * @return WP_USER|bool
	 */
	public function authenticate_google_user( $auth_token = '' ) {
		$user = null;

		$email = 'dev-email@flywheel.local';
		$user  = wp_oxynate_get_or_create_user_by_email( $email );

		return $user;
	}

	/**
	 * Authenticate Facebook User
	 * 
	 * @return WP_User|WP_Error
	 */
	public function authenticate_facebook_user( $auth_token = '' ) {
		$user = null;

		$email = 'dev-email@flywheel.local';
		$user  = wp_oxynate_get_or_create_user_by_email( $email );

		return $user;
	}

	/**
	 * Get the query params for collections.
	 *
	 * @return array
	 */
	public function get_collection_params() {
		$params = parent::get_collection_params();

		$params['context']['default'] = 'view';

		$params['auth-type'] = array(
			'description'       => __( 'Authentication type', 'wp_oxynate' ),
			'type'              => 'string',
			'default'           => null,
			'required'          => true,
			'sanitize_callback' => 'sanitize_text_field',
		);

		$params['email'] = array(
			'description'       => __( 'Email', 'wp_oxynate' ),
			'type'              => 'string',
			'default'           => null,
			'sanitize_callback' => 'sanitize_text_field',
		);

		$params['password'] = array(
			'description'       => __( 'Password', 'wp_oxynate' ),
			'type'              => 'string',
			'default'           => null,
			'sanitize_callback' => 'sanitize_text_field',
		);

		$params['auth-token'] = array(
			'description'       => __( 'Social Authentication token', 'wp_oxynate' ),
			'type'              => 'string',
			'default'           => null,
			'sanitize_callback' => 'sanitize_text_field',
		);
		
		return $params;
	}
}