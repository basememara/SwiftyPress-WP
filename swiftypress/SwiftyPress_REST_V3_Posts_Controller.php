<?php

class SwiftyPress_REST_V3_Posts_Controller extends SwiftyPress_REST_V3 {
 
    // Here initialize our namespace and resource name.
    public function __construct() {
        parent::__construct();
        $this->resource_name = 'posts';
    }
 
    // Register our routes.
    public function register_routes() {
        register_rest_route($this->namespace, '/' . $this->resource_name . '/(?P<id>[\d]+)', array(
            array(
                'methods' => 'GET',
                'callback' => array($this, 'get_post')
            ),
            'schema' => array($this, 'get_post_schema')
        ));
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
            'author' => null,
            'media' => null,
            'categories' => array(),
            'tags' => array()
        );
 
        if (empty($post)) {
            return rest_ensure_response($data);
        }
 
        $post_data = $this->prepare_response_for_render(
            $this->prepare_post_for_response($post, $request)
        );

        $data['post'] = $post_data;

        $data['author'] = $this->prepare_response_for_render(
            $this->prepare_author_for_response($post->post_author)
        );
        
        $attachment_id = $post_data['featured_media'];
        $data['media'] = $this->prepare_response_for_render(
            $this->prepare_media_for_response($attachment_id)
        );

        $data['categories'] = $this->prepare_response_for_render(
            $this->prepare_term_for_response(get_the_category($post->ID))
        );

        $data['tags'] = $this->prepare_response_for_render(
            $this->prepare_term_for_response(get_the_tags($post->ID))
        );
        
        // Return all response data.
        return rest_ensure_response($data);
    }
}