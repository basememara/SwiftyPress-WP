<?php

class SwiftyPress_REST_V3_Payloads_Controller extends SwiftyPress_REST_V3 {
 
    // Here initialize our namespace and resource name.
    public function __construct() {
        parent::__construct();
        $this->resource_name = 'payloads';
    }
 
    // Register our routes.
    public function register_routes() {
        register_rest_route($this->namespace, '/' . $this->resource_name . '/modified', array(
            array(
                'methods' => 'GET',
                'callback' => array($this, 'get_modified')
            ),
            'schema' => array($this, 'get_post_schema')
        ));
    }
 
    /**
     * Get the modified objects and outputs them as a rest response.
     *
     * @param WP_REST_Request $request Current request.
     */
    public function get_modified($request) {
        $data = array(
            'posts' => array(),
            'categories' => array(),
            'tags' => array(),
            'authors' => array(),
            'media' => array()
        );

        // Construct posts query options
        $post_params = array();
        
        if (isset($request['after'])) {
            $modified_after = '@' . $request['after'];
            
            $post_params['date_query'] = array(
                array(
                    'after' => $modified_after,
                    'column' => 'post_modified'
                )
            );
        }

        $post_params['orderby'] = array('post_modified' => 'DESC');
        $post_params['nopaging'] = true;

        $post_query = new WP_Query($post_params);
 
        if ($post_query->have_posts()) {
            // Used for preventing duplicates
            $category_ids = array();
            $tag_ids = array();
            $author_ids = array();
            $media_ids = array();

            foreach ($post_query->posts as $post) {
                // Add post
                $post_data = $this->prepare_response_for_render(
                    $this->prepare_post_for_response($post, $request)
                );
        
                $data['posts'][] = $post_data;

                // Add unique categories
                $categories = array();
                foreach (get_the_category($post->ID) as $term) {
                    if (!in_array($term->term_id, $category_ids)) {
                        $categories[] = $term;
                        $category_ids[] = $term->term_id;
                    }
                }

                if (!empty($categories)) {
                    $data['categories'] = array_merge(
                        $data['categories'],
                        $this->prepare_response_for_render(
                            $this->prepare_term_for_response($categories)
                        )
                    );
                }
                
                // Add unique tags
                $tags = array();
                foreach (get_the_tags($post->ID) as $term) {
                    if (!in_array($term->term_id, $tag_ids)) {
                        $tags[] = $term;
                        $tag_ids[] = $term->term_id;
                    }
                }

                if (!empty($tags)) {
                    $data['tags'] = array_merge(
                        $data['tags'],
                        $this->prepare_response_for_render(
                            $this->prepare_term_for_response($tags)
                        )
                    );
                }
                
                // Add unique authors
                if (!in_array($post->post_author, $author_ids)) {
                    $data['authors'][] = $this->prepare_response_for_render(
                        $this->prepare_author_for_response($post->post_author)
                    );

                    $author_ids[] = $post->post_author;
                }
                
                // Add unique media
                $attachment_id = $post_data['featured_media'];
                if (isset($attachment_id) && !in_array($attachment_id, $media_ids)) {
                    $data['media'][] = $this->prepare_response_for_render(
                        $this->prepare_media_for_response($attachment_id)
                    );

                    $media_ids[] = $attachment_id;
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
            // Add unique authors to output
            if (!in_array($author->ID, $author_ids)) {
                $data['authors'][] = $this->prepare_response_for_render(
                    $this->prepare_author_for_response($author->ID)
                );

                $author_ids[] = $author->ID;
            }
        }

        // Return all response data.
        return rest_ensure_response($data);
    }
 
    /**
     * Get the post and outputs it as a rest response.
     *
     * @param WP_REST_Request $request Current request.
     */
    public function get_post($request) {
        $id = (int)$request['id'];
        $post = get_post($id);
        $data = array(
            'post' => null,
            'categories' => array(),
            'tags' => array(),
            'author' => null
        );
 
        if (empty($post)) {
            return rest_ensure_response($data);
        }
 
        $data['post'] = $this->prepare_response_for_render(
            $this->prepare_post_for_response($post)
        );

        $data['categories'] = $this->prepare_response_for_render(
            $this->prepare_term_for_response(get_the_category($post->ID))
        );

        $data['tags'] = $this->prepare_response_for_render(
            $this->prepare_term_for_response(get_the_tags($post->ID))
        );

        $data['author'] = $this->prepare_response_for_render(
            $this->prepare_author_for_response($post->post_author)
        );
        
        // Return all response data.
        return rest_ensure_response($data);
    }
}