<?php

class SwiftyPress_REST_V5_Modified_Controller extends SwiftyPress_REST_V5 {
 
    // Here initialize our namespace and resource name.
    public function __construct() {
        parent::__construct();
        $this->resource_name = 'modified';
    }
 
    // Register our routes.
    public function register_routes() {
        register_rest_route($this->namespace, '/' . $this->resource_name, array(
            array(
                'methods' => 'GET',
                'callback' => array($this, 'get_items')
            )
        ));
    }
 
    public function get_items($request) {
        $data = array(
            'posts' => array(),
            'authors' => array(),
            'media' => array(),
            'terms' => array()
        );

        // Construct posts query options
        $post_params = array();
        
        if (isset($request['after'])) {
            $modified_after = '@' . $request['after'];
            
            $post_params['date_query'] = array(
                array(
                    'after' => $modified_after,
                    'column' => 'post_modified_gmt'
                )
            );
        }

        if (isset($request['limit']) && is_numeric($request['limit']) && (int)$request['limit'] > 0) {
            $post_params['posts_per_page'] = (int)$request['limit'];
        } else {
            $post_params['nopaging'] = true;
        }

        $post_params['orderby'] = 'post_modified';
        $post_params['order'] = 'DESC';

        $post_query = new WP_Query($post_params);
 
        if ($post_query->have_posts()) {
            foreach ($post_query->posts as $element) {
                $post = get_post($element->ID);

                if (empty($post)) {
                    continue;
                }

                // Add unique terms
                $terms_data = $this->prepare_terms_for_render($post->ID, $request);
                foreach ($terms_data as $term) {
                    $term_id = $term['id'];

                    if (empty(array_filter($data['terms'], function($item) use ($term_id) {
                        return $item['id'] === $term_id;
                    }))) {
                        $data['terms'][] = $term;
                    }
                }
                
                // Add post
                $post_data = $this->prepare_post_for_render($post, $request, $terms_data);
                $data['posts'][] = $post_data;

                // Add unique authors
                $author_id = $post_data['author'];
                if (!empty($author_id) && empty(array_filter($data['authors'], function($item) use ($author_id) {
                    return $item['id'] === $author_id;
                }))) {
                    $author_data = $this->prepare_author_for_render($author_id);

                    if (!empty($author_data)) {
                        $data['authors'][] = $author_data;
                    }
                }
                
                // Add unique media
                $attachment_id = $post_data['featured_media'];
                if (!empty($attachment_id) && empty(array_filter($data['media'], function($item) use ($attachment_id) {
                    return $item['id'] === $attachment_id;
                }))) {
                    $media_data = $this->prepare_media_for_render($attachment_id);
                    
                    if (!empty($media_data)) {
                        $data['media'][] = $media_data;
                    }
                }
            }
        }
        
        // Construct authors query options
        $user_params = array(
            'meta_key'  => 'wpsp_profile_modified'
        );
        
        if (isset($request['after'])) {
            $user_params['meta_query'] = array(
                array(
                    'key' => 'wpsp_profile_modified',
                    'value' => $request['after'],
                    'type' => 'numeric',
                    'compare' => '>'
                )
            );
        }

        $user_params['orderby'] = array('meta_value_num' => 'DESC');
        $user_params['has_published_posts'] = true;

        $user_query = new WP_User_Query($user_params);

        foreach ($user_query->get_results() as $author) {
            $author_id = $author->ID;

            // Add unique authors to output
            if (empty(array_filter($data['authors'], function($item) use ($author_id) {
                return $item['id'] === $author_id;
            }))) {
                $author_data = $this->prepare_author_for_render($author_id);

                if (!empty($author_data)) {
                    $data['authors'][] = $author_data;
                }
            }
        }

        $isEmpty = array_reduce($data, function($result, $next) {
            return $result && empty($next);
        }, true);

        if ($isEmpty) {
            return new WP_REST_Response(null, 304);
        }

        // Return all response data.
        return rest_ensure_response($data);
    }
}