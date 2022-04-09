<?php

namespace Oxynate\Controller\Hook;

class JWT {

    /**
	 * Constuctor
	 * 
     * @return void
	 */
	public function __construct() {
        add_filter( 'jwt_auth_token_before_dispatch', [ $this, 'add_data_to_token' ], 20, 2 );
	}

    /**
	 * Add Data To Token
	 * 
     * @return array Data
	 */
	public function add_data_to_token( $data = [], $user ) {

        $data['user_id'] = ( int ) $user->data->ID;

        return $data;
    }
}