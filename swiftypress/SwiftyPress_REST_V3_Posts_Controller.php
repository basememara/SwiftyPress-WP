<?php

class SwiftyPress_REST_V3_Posts_Controller extends SwiftyPress_REST_V3 {
 
    // Here initialize our namespace and resource name.
    public function __construct() {
        parent::__construct();
        $this->resource_name = 'posts';
    }
 
    // Register our routes.
    public function register_routes() {
        register_rest_route($this->namespace, '/' . $this->resource_name, array(
            array(
                'methods' => 'GET',
                'callback' => array($this, 'get_posts')
            ),
            'schema' => array($this, 'get_post_schema')
        ));

        register_rest_route($this->namespace, '/' . $this->resource_name . '/(?P<id>[\d]+)', array(
            array(
                'methods' => 'GET',
                'callback' => array($this, 'get_post')
            ),
            'schema' => array($this, 'get_post_schema')
        ));
    }
 
    /**
     * Get the modified posts and outputs them as a rest response.
     *
     * @param WP_REST_Request $request Current request.
     */
    public function get_posts($request) {
        // Construct query options
        $params = array();
        
        if (isset($request['modified_after'])) {
            $modified_after = $request['modified_after'];
            
            $params['date_query'] = array(
                array(
                    'after' => $modified_after,
                    'column' => 'post_modified'
                )
            );

            $params['orderby'] = array('post_modified' => 'DESC');
            $params['nopaging'] = true;
        } else {
            if (isset($request['per_page'])) {
                $perPage = (int)$request['per_page'];
                
                if ($perPage > 0) {
                    $params['posts_per_page'] = $perPage;
                }
            }

            if (isset($request['page'])) {
                $params['paged'] = (int)$request['page'];
            }
    
            if (isset($request['orderby'])) {
                $params['orderby'] = $request['orderby'];
            }
    
            if (isset($request['order'])) {
                $params['order'] = strtoupper($request['order']);
            }
        }

        $query = new WP_Query($params);
        $data = array(
            'posts' => array(),
            'categories' => array(),
            'tags' => array(),
            'authors' => array()
        );
 
        if (!$query->have_posts()) {
            return rest_ensure_response($data);
        }

        // Used for preventing duplicates
        $categoryIDs = array();
        $tagIDs = array();
        $authorIDs = array();

        foreach ($query->posts as $post) {
            // Add post
            $data['posts'][] = $this->prepare_response_for_render(
                $this->prepare_post_for_response($post, $request)
            );

            // Add unique categories
            $categories = array();
            foreach (get_the_category($post->ID) as $term) {
                if (!in_array($term->term_id, $categoryIDs)) {
                    $categories[] = $term;
                    $categoryIDs[] = $term->term_id;
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
                if (!in_array($term->term_id, $tagIDs)) {
                    $tags[] = $term;
                    $tagIDs[] = $term->term_id;
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
            if (!in_array($post->post_author, $authorIDs)) {
                $data['authors'][] = $this->prepare_response_for_render(
                    $this->prepare_author_for_response($post->post_author)
                );

                $authorIDs[] = $post->post_author;
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