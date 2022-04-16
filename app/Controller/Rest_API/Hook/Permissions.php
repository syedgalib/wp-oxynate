<?php 

namespace Oxynate\Controller\Rest_API\Hook;

class Permissions {

    /**
     * Constructor
     * 
     * @return void
     */
    public function __construct() {

        add_filter( 'wp_oxynate_rest_check_permissions', [ $this, 'allow_read_context_permission' ], 20, 4 );

    }

    /**
     * Allow rest api endpoint permission to all read context requests.
     *
     * @param boolen $permission
     * @param string $context
     * @param integer $object_id
     * @param string @object_type
     *
     * @return boolen
     */
    public function allow_read_context_permission( $permission, $context, $object_id, $object_type ) {
        
        if ( $context === 'read' ) {
            $permission = true;
        }
    
        if ( $context === 'create' && $object_type === 'user' ) {
            $permission = true;
        }
    
        return $permission;
    }



}