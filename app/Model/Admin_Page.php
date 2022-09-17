<?php

namespace Oxynate\Model;

use WP_Query;

class Admin_Page {

    /**
     * Get Posts for Rest API
     * 
     * @param array $args
     * @return array Posts
     */
    public static function get_rest_posts( $args = [] ) {

        $default = [
            'post_type'      => WP_OXYNATE_POST_TYPE_ADMIN_PAGE,
            'posts_per_page' => -1,
            'page'           => 1,
            'meta_key'       => 'order',
            'orderby'        => 'meta_value_num',
            'order'          => 'ASC'
        ];

        $args = array_merge( $default, $args );
        $query = new WP_Query( $args );

        $posts = [];

        if ( ! $query->have_posts() ) {
            return $posts;
        }

        while( $query->have_posts() ) {
            $query->the_post();

            $post_item['id']        = get_the_id();
            $post_item['title']     = get_the_title();
            $post_item['content']   = get_the_content();
            $post_item['author']    = get_the_author_meta('ID');
            $post_item['permalink'] = get_the_permalink();
            $post_item['thumbnail'] = get_the_post_thumbnail_url();

            $order = get_post_meta( get_the_ID(), 'order', 1 );
            $order = is_numeric( $order ) ? (int) $order : 1;
            $post_item['order'] = $order;

            $posts[] = $post_item;

        }

        wp_reset_postdata();

        return $posts;
    }

}