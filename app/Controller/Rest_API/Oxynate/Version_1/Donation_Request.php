<?php

namespace Oxynate\Controller\Rest_API\Oxynate\Version_1;

use \WP_REST_Server;
use \WP_Query;
use \WP_Error;

use Oxynate\Controller\Rest_API\Oxynate\Version_1\Helper\Posts_Controller;

class Donation_Request extends Posts_Controller {

    /**
	 * Rest Base
	 *
	 * @var string
	 */
    protected $rest_base = 'donation-requests';

    /**
	 * Post type
	 *
	 * @var string
	 */
	protected $post_type = WP_OXYNATE_POST_TYPE_DONATION_REQUEST;

    /**
     * Register Routes
     * 
     * @return void
     */
    public function register_routes() {

        // Get All Donation Request Posts
        register_rest_route(
            $this->namespace, '/' . 
            $this->rest_base, 
            [
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [ $this, 'get_items' ],
                    'permission_callback' => [ $this, 'get_items_permissions_check' ],
                    'args'                => $this->get_collection_params(),
                ],
                [
                    'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_item' ),
					'permission_callback' => array( $this, 'create_item_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
                ],

                'schema' => [ $this, 'get_public_item_schema' ],
            ]
        );

        // Get Single Donation Request Post
        register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)',
			array(
				'args'   => array(
					'id' => array(
						'description' => __( 'Unique identifier for the resource.', 'wp-oxynate' ),
						'type'        => 'integer',
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
					'args'                => array(
						'context' => $this->get_context_param(
							array(
								'default' => 'view',
							)
						),
					),
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
					'args'                => array(
						'force' => array(
							'default'     => false,
							'type'        => 'boolean',
							'description' => __( 'Required to be true, as resource does not support trashing.', 'wp-oxynate' ),
						),
					),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

    }

    /**
     * Get Items
     * 
     * @param WP_REST_Request $request Current request
     * @return void
     */
    public function get_items( $request ) {

        $query_args    = $this->prepare_objects_query( $request );
		$query_results = $this->get_posts( $query_args );

		$objects = array();

		foreach ( $query_results['objects'] as $object ) {
			if ( ! $this->check_post_permissions( $this->post_type, 'read', $object->ID ) ) {
				continue;
			}

			$data = $this->prepare_item_for_response( $object, $request );
			$objects[] = $this->prepare_response_for_collection( $data );
		}

		$page      = (int) $query_args['paged'];
		$max_pages = $query_results['pages'];

		$response = rest_ensure_response( $objects );
		$response->header( 'X-WP-Total', $query_results['total'] );
		$response->header( 'X-WP-TotalPages', (int) $max_pages );

		$base = add_query_arg( $request->get_query_params(), rest_url( sprintf( '/%s/%s', $this->namespace, $this->rest_base ) ) );

		if ( $page > 1 ) {
			$prev_page = $page - 1;
			if ( $prev_page > $max_pages ) {
				$prev_page = $max_pages;
			}
			$prev_link = add_query_arg( 'page', $prev_page, $base );
			$response->link_header( 'prev', $prev_link );
		}
		if ( $max_pages > $page ) {
			$next_page = $page + 1;
			$next_link = add_query_arg( 'page', $next_page, $base );
			$response->link_header( 'next', $next_link );
		}

		return $response;

    }

    /**
	 * Get a single item.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_item( $request ) {
		$id   = (int) $request['id'];
		$post = get_post( $id );

		if ( empty( $id ) || empty( $post->ID ) || $post->post_type !== $this->post_type ) {
			return new WP_Error( "wp_oxynate_rest_invalid_{$this->post_type}_id", __( 'Invalid ID.', 'wp-oxynate' ), array( 'status' => 404 ) );
		}

		$data = $this->prepare_item_for_response( $post, $request );
		$response = rest_ensure_response( $data );

		if ( $this->public ) {
			$response->link_header( 'alternate', get_permalink( $id ), array( 'type' => 'text/html' ) );
		}

		return $response;
	}

	/**
	 * Create a single post.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Request|WP_Error
	 */
	public function create_item( $request ) {
		$post_type = $this->post_type;
		$post      = $this->save_post( $request );

		if ( is_wp_error( $post ) ) {
			return $post;
		}

		$this->update_additional_fields_for_object( $post, $request );

		/**
		 * Fires after a single post is created or updated via the REST API.
		 *
		 * @param WP_POST         $post      Inserted Post object.
		 * @param WP_REST_Request $request   Request object.
		 * @param boolean         $creating  True when creating post, false when updating.
		 */
		do_action( "wp_oxynate_rest_insert_{$post_type}", $post, $request, true );

		$data = $this->prepare_item_for_response( $post, $request );
		$response = rest_ensure_response( $data );
		$response->set_status( 201 );

		$base = '/' . $this->namespace . '/' . $this->rest_base;

		$response->header( 'Location', rest_url( $base . '/' . $post->id ) );

		return $response;
	}

	/**
	 * Update a single post.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Request|WP_Error
	 */
	public function update_item( $request ) {
		$post_type = $this->post_type;
		$post      = $this->save_post( $request );

		if ( is_wp_error( $post ) ) {
			return $post;
		}

		$this->update_additional_fields_for_object( $post, $request );

		/**
		 * Fires after a single post is created or updated via the REST API.
		 *
		 * @param WP_POST         $post      Inserted Post object.
		 * @param WP_REST_Request $request   Request object.
		 * @param boolean         $creating  True when creating post, false when updating.
		 */
		do_action( "wp_oxynate_rest_insert_{$post_type}", $post, $request, false );

		$data = $this->prepare_item_for_response( $post, $request );
		$response = rest_ensure_response( $data );
		$response->set_status( 201 );

		$base = '/' . $this->namespace . '/' . $this->rest_base;

		$response->header( 'Location', rest_url( $base . '/' . $post->id ) );

		return $response;
	}

	/**
	 * Save Post
	 * 
	 * @return WP_Post|WP_Error
	 */
	public function save_post( $request = [] ) {
		$args = $this->parse_post_args( $request );

		$post_id             = ( isset( $args['id'] ) ) ? $args['id'] : 0;
		$inserting_post_args = $this->extract_inserting_post_args( $args );

		if ( empty( $post_id ) && empty( $inserting_post_args ) ) {
			return new WP_Error( 'wp_oxynate_rest_nothing_to_save', __( 'Please provide required fields to save the post.', 'wp-oxynate' ), [ 'status' => rest_authorization_required_code() ] );
		}

		if ( ! empty( $inserting_post_args ) ) {
			$post_id = wp_insert_post( $inserting_post_args );
		}

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		// Save Location
		if ( isset( $args['location'] ) && ! empty( $args['location']['id'] ) ) {

			wp_set_object_terms( $post_id, (int) $args['location']['id'], WP_OXYNATE_TERM_LOCATION );

		}

		// Save Blood Group
		if ( isset( $args['blood_group'] ) && ! empty( $args['blood_group']['id'] ) ) {

			wp_set_object_terms( $post_id, (int) $args['blood_group']['id'], WP_OXYNATE_TERM_BLOOD_GROUP );

		}

		// Save Images
		if ( isset( $args['images'] ) ) {

			$this->set_post_images( $post_id, $args['images'] );

		}

		// Update Preset Fields
		$this->update_preset_fields( $post_id, $args );

		// Post Meta Data
		if ( ! empty( $args['meta_data'] ) ) {
			$this->update_post_metadata( $post_id, $args['meta_data'] );
		}

		return get_post( $post_id );
	}

	/**
	 * Update Post Metadata
	 * 
	 * @param int $post_id
	 * @param array $metadata
	 * 
	 * @return void
	 */
	public function update_post_metadata( $post_id, $metadata = [] ) {

		if ( empty( $metadata ) ) {
			return;
		}

		foreach( $metadata as $meta ) {

			if ( ! isset( $meta['key'] ) && ! isset( $meta['value'] ) ) {
				continue;
			}

			update_post_meta( $post_id, $meta['key'], sanitize_text_field( $meta['value'] ) );
		}

	}

	/**
	 * Update Preset Fields
	 * 
	 * @param int $post_id
	 * @param array $args
	 * 
	 * @return void
	 */
	public function update_preset_fields( $post_id, $args = [] ) {

		$preset_metas = [
			'gender'     => WP_OXYNATE_POST_META_GENDER,
			'hemoglobin' => WP_OXYNATE_POST_META_HEMOGLOBIN,
			'phone'      => WP_OXYNATE_POST_META_PHONE,
			'address'    => WP_OXYNATE_POST_META_ADDRESS,
			'latitude'   => WP_OXYNATE_POST_META_LATITUDE,
			'longitude'  => WP_OXYNATE_POST_META_LONGITUDE,
		];

		foreach( $preset_metas as $meta_id => $meta_key ) {
			
			if ( isset( $args[ $meta_id ] ) ) {

				update_post_meta( $post_id, $meta_key, $args[ $meta_id ] );

			}
		}

	}

	/**
	 * Set Post Images
	 * 
	 * @return void
	 */
	public function set_post_images( $post_id, $images ) {

		$image_ids = [];

		foreach( $images as $image ) {
			$image_id = 0;

			if ( empty( $image['id'] ) && ! empty( $image['src'] ) ) {

				$upload = wp_oxynate_rest_upload_image_from_url( esc_url_raw( $image['src'] ) );

				if ( ! is_wp_error( $upload ) ) {
					$image_id = wp_oxynate_rest_set_uploaded_image_as_attachment( $upload );
				}
	
			} else {
				$image_id = isset( $image['id'] ) ? absint( $image['id'] ) : 0;
			}

			if ( empty( $image_id ) ) {
				continue;
			}

			if ( ! wp_attachment_is_image( $image_id ) ) {
				continue;
			}

			$image_ids[] = $image_id;

		}

		if ( empty( $image_ids ) ) {
			delete_post_thumbnail( $post_id );
		} else {
			set_post_thumbnail( $post_id, $image_ids[0] );
		}

		update_post_meta( $post_id, WP_OXYNATE_POST_META_IMAGES, $image_ids );
	}

	/**
	 * Set Post Terms
	 * 
	 * @param int $post_id
	 * @param array $terms
	 * @param string $taxonomy
	 * 
	 * @return void
	 */
	public function set_post_terms( $post_id, $terms, $taxonomy ) {

		$terms_ids = [];

		foreach( $terms as $term ) {

			if ( ! isset( $term['id'] ) ) {
				continue;
			}

			$terms_ids[] = $term['id'];
		}

		if ( empty( $terms_ids ) ) {
			return;
		}
		
		wp_set_object_terms( $post_id, $terms_ids, $taxonomy );
	}

	/**
	 * Extract Inserting Post Args
	 * 
	 * @return array
	 */
	public function extract_inserting_post_args( $args = [] ) {
		$inserting_post_args = [];

		$allowed_key = [ 
			'ID',
			'post_type',
			'post_title',
			'post_content',
			'post_excerpt',
			'post_status',
			'post_author',
		];

		$inserting_post_args = array_filter(
			$args,
			function ( $key ) use ( $allowed_key ) {
				return in_array( $key, $allowed_key );
			},
			ARRAY_FILTER_USE_KEY
		);

		return $inserting_post_args;
	}

	/**
	 * Parse Post Args
	 * 
	 * @param array $request
	 * @return array Post Args
	 */
	public function parse_post_args( $request = [] ) {
		$args = [];

		$args['post_type'] = $this->post_type;

		// Post ID
		if ( isset( $request['id'] ) ) {
			$args['ID'] = sanitize_text_field( $request['id'] );
		}
		// Post Author
		if ( isset( $request['author'] ) ) {
			$args['post_author'] = sanitize_text_field( $request['author'] );
		}

		// Post Title
		if ( isset( $request['title'] ) ) {
			$args['post_title'] = sanitize_text_field( $request['title'] );
		}

		if ( isset( $request['name'] ) ) {
			$args['post_title'] = sanitize_text_field( $request['name'] );
		}

		// Post Content
		if ( isset( $request['description'] ) ) {
			$args['post_content'] = sanitize_textarea_field( $request['description'] );
		}

		// Post Excerpt
		if ( isset( $request['short_description'] ) ) {
			$args['post_excerpt'] = sanitize_text_field( $request['short_description'] );
		}

		// Post Status
		$args['post_status'] = 'publish';

		if ( isset( $request['status'] ) ) {
			$requested_post_status   = sanitize_text_field( $request['status'] );
			$supportated_post_status = array_keys( get_post_statuses() );
			$is_valid_post_status    = in_array( $requested_post_status, $supportated_post_status );

			if ( $is_valid_post_status ) {
				$args['post_status'] = $requested_post_status;
			}
		}

		// Locations
		if ( isset( $request['location'] ) ) {
			$args['location'] = $request['location'];
		}

		// Blood Group
		if ( isset( $request['blood_group'] ) ) {
			$args['blood_group'] = $request['blood_group'];
		}

		// Images
		if ( isset( $request['images'] ) ) {
			$args['images'] = $request['images'];
		}

		// Gender
		if ( isset( $request['gender'] ) ) {
			$args['gender'] = sanitize_text_field( $request['gender'] );
		}

		// Hemoglobin
		if ( isset( $request['hemoglobin'] ) ) {
			$args['hemoglobin'] = sanitize_text_field( $request['hemoglobin'] );
		}

		// Phone
		if ( isset( $request['phone'] ) ) {
			$args['phone'] = sanitize_text_field( $request['phone'] );
		}

		// Address
		if ( isset( $request['address'] ) ) {
			$args['address'] = sanitize_text_field( $request['address'] );
		}

		// Latitude
		if ( isset( $request['latitude'] ) ) {
			$args['latitude'] = sanitize_text_field( $request['latitude'] );
		}

		// longitude
		if ( isset( $request['longitude'] ) ) {
			$args['longitude'] = sanitize_text_field( $request['longitude'] );
		}

		// Meta Data
		if ( isset( $request['meta_data'] ) ) {
			$args['meta_data'] = $request['meta_data'];
		}

		return $args;
	}

    /**
	 * Get the Post's schema, conforming to JSON Schema.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$schema         = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => $this->post_type,
			'type'       => 'object',
			'properties' => array(
				'id' => array(
					'description' => __( 'Unique identifier for the resource.', 'wp-oxynate' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'name' => array(
					'description' => __( 'Post title.', 'wp-oxynate' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'required'    => true,
				),
				'slug' => array(
					'description' => __( 'Post slug.', 'wp-oxynate' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'permalink' => array(
					'description' => __( 'Post URL.', 'wp-oxynate' ),
					'type'        => 'string',
					'format'      => 'uri',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'date_created' => array(
					'description' => __( "The date the post was created, in the site's timezone.", 'wp-oxynate' ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'date_created_gmt' => array(
					'description' => __( 'The date the post was created, as GMT.', 'wp-oxynate' ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'date_modified' => array(
					'description' => __( "The date the post was last modified, in the site's timezone.", 'wp-oxynate' ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'date_modified_gmt' => array(
					'description' => __( 'The date the post was last modified, as GMT.', 'wp-oxynate' ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'description' => array(
					'description' => __( 'Post description.', 'wp-oxynate' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'short_description' => array(
					'description' => __( 'Post short description.', 'wp-oxynate' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'status' => array(
					'description' => __( 'Post status.', 'wp-oxynate' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'gender' => array(
					'description' => __( 'Gender.', 'wp-oxynate' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'hemoglobin' => array(
					'description' => __( 'Hemoglobin.', 'wp-oxynate' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'phone' => array(
					'description' => __( 'Phone number.', 'wp-oxynate' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'address' => array(
					'description' => __( 'Listing address.', 'wp-oxynate' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'latitude' => array(
					'description' => __( 'Address location latitude.', 'wp-oxynate' ),
					'type'        => 'number',
					'context'     => array( 'view', 'edit' ),
				),
				'longitude'              => array(
					'description' => __( 'Address location longitude.', 'wp-oxynate' ),
					'type'        => 'number',
					'context'     => array( 'view', 'edit' ),
				),
				'location' => array(
					'description' => __( 'Location of the post.', 'wp-oxynate' ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit' ),
					'properties'  => array(
						'type'       => 'object',
						'properties' => array(
							'id'   => array(
								'description' => __( 'Location ID.', 'wp-oxynate' ),
								'type'        => 'integer',
								'context'     => array( 'view', 'edit' ),
							),
							'name' => array(
								'description' => __( 'Location name.', 'wp-oxynate' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'slug' => array(
								'description' => __( 'Location slug.', 'wp-oxynate' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
						),
					),
				),
				'district' => array(
					'description' => __( 'District of the post.', 'wp-oxynate' ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
					'properties'  => array(
						'type'       => 'object',
						'properties' => array(
							'id'   => array(
								'description' => __( 'District ID.', 'wp-oxynate' ),
								'type'        => 'integer',
								'context'     => array( 'view', 'edit' ),
							),
							'name' => array(
								'description' => __( 'District name.', 'wp-oxynate' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'slug' => array(
								'description' => __( 'District slug.', 'wp-oxynate' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
						),
					),
				),
				'area' => array(
					'description' => __( 'Area of the post.', 'wp-oxynate' ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
					'properties'  => array(
						'type'       => 'object',
						'properties' => array(
							'id'   => array(
								'description' => __( 'Area ID.', 'wp-oxynate' ),
								'type'        => 'integer',
								'context'     => array( 'view', 'edit' ),
							),
							'name' => array(
								'description' => __( 'Area name.', 'wp-oxynate' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'slug' => array(
								'description' => __( 'Area slug.', 'wp-oxynate' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
						),
					),
				),
				'blood_group' => array(
					'description' => __( 'Blood Group', 'wp-oxynate' ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit' ),
                    'properties'  => array(
						'type'       => 'object',
						'properties' => array(
							'id'   => array(
								'description' => __( 'Location ID.', 'wp-oxynate' ),
								'type'        => 'integer',
								'context'     => array( 'view', 'edit' ),
							),
							'name' => array(
								'description' => __( 'Location name.', 'wp-oxynate' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'slug' => array(
								'description' => __( 'Location slug.', 'wp-oxynate' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
						),
					),
				),
				'images' => array(
					'description' => __( 'List of images.', 'wp-oxynate' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'id'                => array(
								'description' => __( 'Image ID.', 'wp-oxynate' ),
								'type'        => 'integer',
								'context'     => array( 'view', 'edit' ),
							),
							'date_created'      => array(
								'description' => __( "The date the image was created, in the site's timezone.", 'wp-oxynate' ),
								'type'        => 'date-time',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'date_created_gmt'  => array(
								'description' => __( 'The date the image was created, as GMT.', 'wp-oxynate' ),
								'type'        => 'date-time',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'date_modified'     => array(
								'description' => __( "The date the image was last modified, in the site's timezone.", 'wp-oxynate' ),
								'type'        => 'date-time',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'date_modified_gmt' => array(
								'description' => __( 'The date the image was last modified, as GMT.', 'wp-oxynate' ),
								'type'        => 'date-time',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'src'               => array(
								'description' => __( 'Image URL.', 'wp-oxynate' ),
								'type'        => 'string',
								'format'      => 'uri',
								'context'     => array( 'view', 'edit' ),
							),
							'name'              => array(
								'description' => __( 'Image name.', 'wp-oxynate' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'alt'               => array(
								'description' => __( 'Image alternative text.', 'wp-oxynate' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'position'          => array(
								'description' => __( 'Image position. 0 means that the image is featured.', 'wp-oxynate' ),
								'type'        => 'integer',
								'context'     => array( 'view', 'edit' ),
							),
						),
					),
				),
				'menu_order' => array(
					'description' => __( 'Menu order, used to custom sort listings.', 'wp-oxynate' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
				),
				'author' => array(
					'description' => __( 'Listing author id.', 'wp-oxynate' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
				),
				'meta_data' => array(
					'description' => __( 'Meta data.', 'wp-oxynate' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'id'    => array(
								'description' => __( 'Meta ID.', 'wp-oxynate' ),
								'type'        => 'integer',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'key'   => array(
								'description' => __( 'Meta key.', 'wp-oxynate' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'value' => array(
								'description' => __( 'Meta value.', 'wp-oxynate' ),
								'type'        => 'mixed',
								'context'     => array( 'view', 'edit' ),
							),
						),
					),
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );
	}

    /**
	 * Get the query params for collections of listings.
	 *
	 * @return array
	 */
	public function get_collection_params() {
		$params = parent::get_collection_params();

		$params['context']['default'] = 'view';

		$params['exclude'] = array(
			'description'       => __( 'Ensure result set excludes specific IDs.', 'wp-oxynate' ),
			'type'              => 'string',
			'sanitize_callback' => 'wp_parse_id_list',
			'validate_callback'  => 'rest_validate_request_arg',
		);
		$params['include'] = array(
			'description'       => __( 'Limit result set to specific IDs.', 'wp-oxynate' ),
			'type'              => 'string',
			'sanitize_callback' => 'wp_parse_id_list',
			'validate_callback'  => 'rest_validate_request_arg',
		);
		$params['offset'] = array(
			'description'        => __( 'Offset the result set by a specific number of items.', 'wp-oxynate' ),
			'type'               => 'integer',
			'sanitize_callback'  => 'absint',
			'validate_callback'  => 'rest_validate_request_arg',
		);
		$params['order'] = array(
			'default'            => 'desc',
			'description'        => __( 'Order sort attribute ascending or descending.', 'wp-oxynate' ),
			'enum'               => array( 'asc', 'desc' ),
			'sanitize_callback'  => 'sanitize_key',
			'type'               => 'string',
			'validate_callback'  => 'rest_validate_request_arg',
		);
		$params['orderby'] = array(
			'description'        => __( 'Sort collection by object attribute.', 'wp-oxynate' ),
			'enum'               => array_keys( $this->get_orderby_possibles() ),
			'sanitize_callback'  => 'sanitize_key',
			'type'               => 'string',
			'validate_callback'  => 'rest_validate_request_arg',
		);
		$params['slug'] = array(
			'description'       => __( 'Limit result set to posts with a specific slug.', 'wp-oxynate' ),
			'type'              => 'string',
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['status'] = array(
			'default'           => 'publish',
			'description'       => __( 'Limit result set to posts assigned a specific status.', 'wp-oxynate' ),
			'type'              => 'string',
			'enum'              => array_merge( array( 'any', 'future', 'trash', 'expired' ), array_keys( get_post_statuses() ) ),
			'sanitize_callback' => 'sanitize_key',
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['location'] = array(
			'description'       => __( 'Limit result set to posts assigned a specific location ID.', 'wp-oxynate' ),
			'type'              => 'string',
			'sanitize_callback' => 'wp_parse_id_list',
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['blood_group'] = array(
			'description'       => __( 'Limit result set to posts assigned a specific blood group', 'wp-oxynate' ),
			'type'              => 'string',
			'sanitize_callback' => 'wp_parse_id_list',
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['author'] = array(
			'description'       => __( 'Limit result set to posts specific to author ID.', 'wp-oxynate' ),
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);

		return $params;
	}

    protected function get_orderby_possibles() {
		return array(
			'id'      => 'ID',
			'include' => 'include',
			'title'   => 'title',
			'date'    => 'date',
		);
	}

    protected function get_posts( $query_args ) {
		$query  = new WP_Query();
		$result = $query->query( $query_args );

		$total_posts = $query->found_posts;

		if ( $total_posts < 1 ) {
			// Out-of-bounds, run the query again without LIMIT for total count.
			unset( $query_args['paged'] );
			$count_query = new WP_Query();
			$count_query->query( $query_args );
			$total_posts = $count_query->found_posts;
		}

		return array(
			'objects' => $result,
			'total'   => (int) $total_posts,
			'pages'   => (int) ceil( $total_posts / (int) $query->query_vars['posts_per_page'] ),
		);
	}

    /**
	 * Prepare objects query.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return array
	 */
	protected function prepare_objects_query( $request ) {
		$args                   = [];
		$args['offset']         = $request['offset'];
		$args['order']          = $request['order'];
		$args['orderby']        = $request['orderby'];
		$args['paged']          = $request['page'];
		$args['post__in']       = $request['include'];
		$args['post__not_in']   = $request['exclude'];
		$args['posts_per_page'] = $request['per_page'];
		$args['name']           = $request['slug'];
		$args['s']              = $request['search'];
		$args['post_status']    = $request['status'];
		$args['fields']         = $this->get_fields_for_response( $request );

		// Taxonomy query.
		$tax_query = [];
		// Meta query.
		$meta_query = [];
		// Date query.
		$args['date_query'] = [];

		// Set before into date query. Date query must be specified as an array of an array.
		if ( isset( $request['before'] ) ) {
			$args['date_query'][0]['before'] = $request['before'];
		}

		// Set after into date query. Date query must be specified as an array of an array.
		if ( isset( $request['after'] ) ) {
			$args['date_query'][0]['after'] = $request['after'];
		}

		// Check flag to use post_date vs post_date_gmt.
		if ( true === $request['dates_are_gmt'] ) {
			if ( isset( $request['before'] ) || isset( $request['after'] ) ) {
				$args['date_query'][0]['column'] = 'post_date_gmt';
			}
		}

		// Set author query.
		if ( isset( $request['author'] ) ) {
			$args['author'] = $request['author'];
		}

		// Set blood group query.
		if ( isset( $request['blood_group'] ) ) {
			$tax_query['tax_query'][] = [
				'taxonomy'         => WP_OXYNATE_TERM_BLOOD_GROUP,
				'field'            => 'term_id',
				'terms'            => $request['blood_group'],
				'include_children' => true, /*@todo; Add option to include children or exclude it*/
			];
		}

		// Set locations query.
		if ( isset( $request['location'] ) ) {
			$tax_query['tax_query'][] = [
				'taxonomy'         => WP_OXYNATE_TERM_LOCATION,
				'field'            => 'term_id',
				'terms'            => $request['location'],
				'include_children' => true, /*@todo; Add option to include children or exclude it*/
			];
		}

		switch ( $args['orderby'] ) {
			case 'id':
				$args['orderby'] = 'ID';
				break;

			case 'include':
				$args['orderby'] = 'post__in';
				break;

			// case 'title':
			// 	break;

			// case 'date':
			// 	break;
		}

		// Radius query.
		if ( isset( $request['radius'] ) ) {
			$args['oxynate_geo_query'] = array(
				'lat_field' => '_latitude',
				'lng_field' => '_longitude',
				'latitude'  => $request['radius']['latitude'],
				'longitude' => $request['radius']['longitude'],
				'distance'  => $request['radius']['distance'],
				'units'     => 'miles',
			);
		}

		if ( ! empty( $meta_query ) ) {
			$meta_query[]['relation'] = 'AND';
			$args['meta_query'] = $meta_query;
		}

		if ( ! empty( $tax_query ) ) {
			$tax_query[]['relation'] = 'AND';
			$args['tax_query']       = $tax_query;
		}

		/**
		 * Filter the query arguments for a request.
		 *
		 * Enables adding extra arguments or setting defaults for a post
		 * collection request.
		 *
		 * @param array           $args    Key value array of query var to query value.
		 * @param WP_REST_Request $request The request used.
		 */
		$args = apply_filters( "wp_oxynate_rest_{$this->post_type}_object_query", $args, $request );

		// Force the post_type argument, since it's not a user input variable.
		$args['post_type'] = $this->post_type;

		return $this->prepare_items_query( $args, $request );
	}


    /**
	 * Determine the allowed query_vars for a get_items() response and
	 * prepare for WP_Query.
	 *
	 * @param array           $prepared_args Prepared arguments.
	 * @param WP_REST_Request $request Request object.
	 * @return array          $query_args
	 */
	protected function prepare_items_query( $prepared_args = array(), $request = null ) {
		$valid_vars = array_flip( $this->get_allowed_query_vars() );
		$query_args = array();

		foreach ( $valid_vars as $var => $index ) {
			if ( isset( $prepared_args[ $var ] ) ) {
				/**
				 * Filter the query_vars used in `get_items` for the constructed query.
				 *
				 * The dynamic portion of the hook name, $var, refers to the query_var key.
				 *
				 * @param mixed $prepared_args[ $var ] The query_var value.
				 */
				$query_args[ $var ] = apply_filters( "wp_oxynate_rest_query_var-{$var}", $prepared_args[ $var ] );
			}
		}

		$query_args['ignore_sticky_posts'] = true;

		if ( 'include' === $query_args['orderby'] ) {
			$query_args['orderby'] = 'post__in';
		} elseif ( 'id' === $query_args['orderby'] ) {
			$query_args['orderby'] = 'ID'; // ID must be capitalized.
		} elseif ( 'slug' === $query_args['orderby'] ) {
			$query_args['orderby'] = 'name';
		}

		return $query_args;
	}

    /**
	 * Prepare a single listings output for response.
	 *
	 * @param WP_Post         $object  Object data.
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response
	 */
	public function prepare_item_for_response( $object, $request ) {
		$context       = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$this->request = $request;
		$data          = $this->get_post_data( $object, $context, $request );
		$data          = $this->add_additional_fields_to_object( $data, $request );
		$data          = $this->filter_response_by_context( $data, $context );

		$response = rest_ensure_response( $data );
		$response->add_links( $this->prepare_links( $object, $request ) );

		/**
		 * Filter the data for a response.
		 *
		 * The dynamic portion of the hook name, $this->post_type,
		 * refers to object type being prepared for the response.
		 *
		 * @param WP_REST_Response $response The response object.
		 * @param WP_Post          $object   Object data.
		 * @param WP_REST_Request  $request  Request object.
		 */
		return apply_filters( "wp_oxynate_rest_prepare_{$this->post_type}_object", $response, $object, $request );
	}

    /**
	 * Prepare links for the request.
	 *
	 * @param WP_Post         $object  Object data.
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return array Links for the given post.
	 */
	protected function prepare_links( $object, $request ) {
		$links = array(
			'self'       => array(
				'href' => rest_url( sprintf( '/%s/%s/%d', $this->namespace, $this->rest_base, $object->ID ) ),  // @codingStandardsIgnoreLine.
			),
			'collection' => array(
				'href' => rest_url( sprintf( '/%s/%s', $this->namespace, $this->rest_base ) ),  // @codingStandardsIgnoreLine.
			),
		);

		if ( $object->post_parent ) {
			$links['up'] = array(
				'href' => rest_url( sprintf( '/%s/listings/%d', $this->namespace, $object->post_parent ) ),  // @codingStandardsIgnoreLine.
			);
		}

		return $links;
	}

    /**
	 * Get post data.
	 *
	 * @param WP_Post   $posts WP_Post instance.
	 * @param string    $context Request context. Options: 'view' and 'edit'.
	 *
	 * @return array
	 */
	protected function get_post_data( $post, $context = 'view', $request ) {
		$fields  = $this->get_fields_for_response( $request );

		$locations = wp_oxynate_get_taxonomy_terms( $post->ID, WP_OXYNATE_TERM_LOCATION );
		$location  = ( ! empty( $locations ) ) ? $locations[0] : null;

		$location_by_area_types = ( ! empty( $location ) ) ? wp_oxynate_rest_get_location_by_area_types( $location['id'], WP_OXYNATE_TERM_LOCATION ) : [];

		$district = ( isset( $location_by_area_types['district'] ) ) ? $location_by_area_types['district'] : null;
		$area     = ( isset( $location_by_area_types['area'] ) ) ? $location_by_area_types['area'] : null;
    	
		$base_data = array();

		foreach ( $fields as $field ) {
			switch ( $field ) {
				case 'id':
					$base_data['id'] = $post->ID;
					break;
				case 'name':
					$base_data['name'] = get_the_title( $post );
					break;
				case 'slug':
					$base_data['slug'] = $post->post_name;
					break;
				case 'permalink':
					$base_data['permalink'] = get_the_permalink( $post );
					break;
				case 'date_created':
					$base_data['date_created'] = $post->post_date;
					break;
				case 'date_created_gmt':
					$base_data['date_created_gmt'] = $post->post_date_gmt;
					break;
				case 'date_modified':
					$base_data['date_modified'] = $post->post_date_modified;
					break;
				case 'date_modified_gmt':
					$base_data['date_modified_gmt'] = $post->post_date_modified_gmt;
					break;
				case 'description':
					$base_data['description'] = 'view' === $context ? wp_kses( do_shortcode( $post->post_content ), [] ): $post->post_content;
					break;
				case 'short_description':
					$base_data['short_description'] = 'view' === $context ? $post->post_excerpt : $post->post_excerpt;
					break;
				case 'gender':
					$base_data['gender'] = get_post_meta( $post->ID, WP_OXYNATE_POST_META_GENDER, true );
				case 'hemoglobin':
					$base_data['hemoglobin'] = get_post_meta( $post->ID, WP_OXYNATE_POST_META_HEMOGLOBIN, true );
				case 'phone':
					$base_data['phone'] = get_post_meta( $post->ID, WP_OXYNATE_POST_META_PHONE, true );
					break;
				case 'address':
					$base_data['address'] = get_post_meta( $post->ID, WP_OXYNATE_POST_META_ADDRESS, true );
					break;
				case 'latitude':
					$base_data['latitude'] = get_post_meta( $post->ID, WP_OXYNATE_POST_META_LATITUDE, true );
					break;
				case 'longitude':
					$base_data['longitude'] = get_post_meta( $post->ID, WP_OXYNATE_POST_META_LONGITUDE, true );
					break;
				case 'status':
					$base_data['status'] = $post->post_status;
					break;
				case 'location':
					$base_data['location'] = $location;
					break;
				case 'district':
					$base_data['district'] = $district;
					break;
				case 'area':
					$base_data['area'] = $area;
					break;
				case 'blood_group':
                    $blood_groups = wp_oxynate_get_taxonomy_terms( $post->ID, WP_OXYNATE_TERM_BLOOD_GROUP );
                    $blood_group  = ( ! empty( $blood_groups ) ) ? $blood_groups[0] : null;
					$base_data['blood_group'] = $blood_group;
					break;
				case 'images':
					$base_data['images'] = $this->get_images( $post );
					break;
				case 'menu_order':
					$base_data['menu_order'] = (int) $post->menu_order;
					break;
				case 'author':
					$author = get_user_by( 'id', $post->post_author );
					$author_info = [];

					$author_info['id'] = (int) $post->post_author;

					if ( empty( $author ) ||  is_wp_error( $author ) ) {
						$base_data['author'] = $author_info;
						break;
					}

					$full_name = $author->first_name .' '. $author->last_name;

					$author_info['name']    = $full_name;
					$author_info['email']   = $author->user_email;
					$author_info['avaiter'] = wp_oxynate_get_user_avater( $author->id );

					$base_data['author'] = $author_info;

					break;
			}
		}

		return $base_data;
	}
}