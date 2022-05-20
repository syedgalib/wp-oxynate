<?php

namespace Oxynate\Controller\Rest_API\Oxynate\Version_1;

use Exception;
use \WP_REST_Server;
use \WP_Error;
use Firebase\JWT\JWT;
use Twilio\Rest\Client as TwilioClient;

use Oxynate\Controller\Rest_API\Oxynate\Version_1\Helper\Rest_Base;
use Oxynate\Module\Settings_Panel\Model as App_Settings;

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
		
		register_rest_route( 
			$this->namespace, 
			'/' . $this->rest_base . '/send-otp',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'send_otp' ],
					'permission_callback' => [ $this, 'create_item_permissions_check' ],
					'args'                => [
						'phone' => [
							'description'       => __( 'Phone', 'wp_oxynate' ),
							'type'              => 'string',
							'default'           => null,
							'required'          => true,
							'sanitize_callback' => 'sanitize_text_field',
						],
					],
				),
			
			'schema' => array( $this, 'get_public_item_schema' ),
		) );
		
		register_rest_route( 
			$this->namespace, 
			'/' . $this->rest_base . '/verify-otp',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'verify_otp' ],
					'permission_callback' => [ $this, 'create_item_permissions_check' ],
					'args'                => [
						'phone' => [
							'description'       => __( 'Phone', 'wp_oxynate' ),
							'type'              => 'string',
							'default'           => null,
							'required'          => true,
							'sanitize_callback' => 'sanitize_text_field',
						],
						'otp' => [
							'description'       => __( 'OTP', 'wp_oxynate' ),
							'type'              => 'string',
							'default'           => null,
							'required'          => true,
							'sanitize_callback' => 'sanitize_text_field',
						],
					],
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
		$user = null;

		$sign_in_type = $request->get_param('sign-in-type');
		$email        = $request->get_param('email');
		$password     = $request->get_param('password');
		$access_token = $request->get_param('access-token');

		switch ( $sign_in_type ) {
			case 'email':
				$user = $this->authenticate_wp_user( $email, $password );
				break;
			case 'google':
				$user = $this->authenticate_google_user( $access_token );
				break;
			case 'facebook':
				$user = $this->authenticate_facebook_user( $access_token );
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

        $response = $this->generate_auth_token( $user );
		$response = rest_ensure_response( $response );

		return $response;
	}

	/**
	 * Generate Auth Token
	 * 
	 * @param WP_User $user
	 * @return WP_Error|array
	 */
	public function generate_auth_token( $user ) {
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
            'token'             => $token,
            'user_email'        => $user->data->user_email,
            'user_nicename'     => $user->data->user_nicename,
            'user_display_name' => $user->data->display_name,
        );

        /** Let the user modify the data before send it back */
        $response = apply_filters('jwt_auth_token_before_dispatch', $data, $user);

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
	public function authenticate_google_user( $access_token = '' ) {
		$user = null;

		$fields    = 'emailAddresses,names,phoneNumbers,photos';
		$graph_url = "https://people.googleapis.com/v1/people/me?sources=READ_SOURCE_TYPE_PROFILE&personFields={$fields}&access_token={$access_token}";
		$request   = wp_remote_get( $graph_url );

		$data = json_decode( $request['body'], true );

		if ( $request['response']['code'] !== 200 ) {
			$message = ( isset( $data['error'] ) && isset( $data['error']['message'] )  ) ? $data['error']['message'] : __( 'Something went wrong', 'wp-oxynate' );
			return new WP_Error( 403, $message );
		}

		$email       = '';
		$first_name  = '';
		$last_name   = '';

		// Email
		if ( ! empty( $data['emailAddresses'] ) && is_array( $data['emailAddresses'] ) ) {
			$email = ( isset( $data['emailAddresses'][0]['value'] ) ) ? $data['emailAddresses'][0]['value'] : '';
		}

		// Name
		if ( ! empty( $data['names'] ) && is_array( $data['names'] ) ) {
			$name       = $data['names'][0];
			$first_name = ( isset( $name['givenName'] ) ) ? $name['givenName'] : '';
			$last_name  = ( isset( $name['familyName'] ) ) ? $name['familyName'] : '';
		}

		if ( empty( $email ) ) {
			return new WP_Error( 403, __( 'Email is required', 'wp-oxynate' ) );
		}

		$user_meta = [
			'first_name' => $first_name,
			'last_name'  => $last_name,
		];

		$user = wp_oxynate_get_or_create_user_by_email( $email, $user_meta );

		return $user;
	}

	/**
	 * Send OTP
	 * 
	 * @return array|WP_Error
	 */
	public function send_otp( $request ) {
		$settings = App_Settings::get_options();
		$phone    = $request->get_param('phone');

		if ( empty( $phone ) ) {
			return new WP_Error( 403, __( 'Phone number is required', 'wp-oxynate' ) );
		}

		$sid   = ( isset( $settings['twilio_sid'] ) ) ? $settings['twilio_sid'] : '';
		$token = ( isset( $settings['twilio_token'] ) ) ? $settings['twilio_token'] : '';

		$client = new TwilioClient( $sid, $token );
		
		$app_name = 'Oxynate';
		$otp      = random_int( 1000, 9999 );

		$from    = ( isset( $settings['twilio_from_phone'] ) ) ? $settings['twilio_from_phone'] : '';
		$to      = '+' . $phone;
		$message = "{$otp} is your {$app_name} veryfication code";

		$transient_key      = "wp_oxynate_otp_{$phone}";
		$transient_duration = MINUTE_IN_SECONDS * 10;

		set_transient( $transient_key, $otp, $transient_duration );

		try {
			$client->messages->create(
				$to, 
				[ 'from' => $from, 'body' => $message ]
			);

			return rest_ensure_response([
				'success' => true,
				'phone'   => $phone,
				'otp'     => $otp,
				'message' => __( 'The OTP has been sent successfuly' )
			]);
			
		} catch ( Exception $e ) {
			return new WP_Error( 403, $e->getMessage() );
		}
	}

	/**
	 * Verify OTP
	 * 
	 * @return array|WP_Error
	 */
	public function verify_otp( $request ) {
		$phone = $request->get_param('phone');
		$otp   = $request->get_param('otp');

		if ( empty( $phone ) ) {
			return new WP_Error( 403, __( 'Phone number is required', 'wp-oxynate' ) );
		}

		if ( empty( $otp ) ) {
			return new WP_Error( 403, __( 'The OTP is required', 'wp-oxynate' ) );
		}

		$transient_key = "wp_oxynate_otp_{$phone}";
		$original_otp  = get_transient( $transient_key );

		if ( ( string ) $otp !== ( string ) $original_otp ) {
			return new WP_Error( 403, __( 'The OTP is not valid', 'wp-oxynate' ) );
		}

		$user = wp_oxynate_get_or_create_user_by_phone( $phone );

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

		$response = $this->generate_auth_token( $user );
		$response = rest_ensure_response( $response );

		delete_transient( $transient_key );

		return $response;
	}

	/**
	 * Authenticate Facebook User
	 * 
	 * @return WP_User|WP_Error
	 */
	public function authenticate_facebook_user( $access_token = '' ) {
		$user = null;

		$fields    = 'id,first_name,middle_name,last_name,gender,birthday,email';
		$graph_url = "https://graph.facebook.com/me?fields={$fields}&access_token={$access_token}";
		$request   = wp_remote_get( $graph_url );

		$data = json_decode( $request['body'], true );

		if ( $request['response']['code'] !== 200 ) {
			$message = ( isset( $data['error'] ) && isset( $data['error']['message'] )  ) ? $data['error']['message'] : __( 'Something went wrong', 'wp-oxynate' );
			return new WP_Error( 403, $message );
		}
		
		$first_name  = ( isset( $data['first_name'] ) ) ? $data['first_name'] : '';
		$middle_name = ( isset( $data['middle_name'] ) ) ? $data['middle_name'] : '';
		$first_name  = $first_name . ' ' . $middle_name;
		$last_name   = ( isset( $data['last_name'] ) ) ? $data['last_name'] : '';
		$email       = ( isset( $data['email'] ) ) ? $data['email'] : '';

		if ( empty( $email ) ) {
			return new WP_Error( 403, __( 'Email is required', 'wp-oxynate' ) );
		}

		$user_meta = [
			'first_name' => $first_name,
			'last_name'  => $last_name,
		];

		$user = wp_oxynate_get_or_create_user_by_email( $email, $user_meta );

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

		$params['sign-in-type'] = array(
			'description'       => __( 'Sign in type', 'wp_oxynate' ),
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

		$params['access-token'] = array(
			'description'       => __( 'Access token', 'wp_oxynate' ),
			'type'              => 'string',
			'default'           => null,
			'sanitize_callback' => 'sanitize_text_field',
		);
		
		return $params;
	}
}