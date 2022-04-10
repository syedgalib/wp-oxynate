<?php

namespace Oxynate\Controller\Rest_API\Oxynate\Version_1\Helper;

use \WP_REST_Controller;

abstract class Rest_Base extends WP_REST_Controller {

    /**
	 * Namespace
	 *
	 * @var string
	 */
    protected $namespace = 'wp-oxynate/v1';

    /**
     * Check permissions for the posts.
     *
     * @param WP_REST_Request $request Current request.
     */
    public function get_items_permissions_check( $request ) {

        return true;

    }
}