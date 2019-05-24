<?php

class SwiftyPress_REST_V5_Post_Controller extends SwiftyPress_REST_V5 {
 
    // Here initialize our namespace and resource name.
    public function __construct() {
        parent::__construct();
        $this->resource_name = 'post';
    }
 
    // Register our routes.
    public function register_routes() {
        register_rest_route($this->namespace, '/' . $this->resource_name . '/(?P<id>[\d]+)', array(
            array(
                'methods' => 'GET',
                'callback' => array($this, 'get_item')
            )
        ));
    }
 
    public function get_item($request) {
        $id = (int)$request['id'];
        $post = get_post($id);

        if (empty($post)) {
            return new WP_Error(
                'post_does_not_exist', 
                'The post you are looking for does not exist.', 
                array('status' => 404)
            );
        }

        $data = array(
            'post' => null,
            'author' => null,
            'media' => null,
            'terms' => array()
        );

        $terms_data = $this->prepare_terms_for_render($post->ID, $request);
        $data['terms'] = $terms_data;
 
        $post_data = $this->prepare_post_for_render($post, $request, $terms_data);
        $data['post'] = $post_data;

        $author_data = $this->prepare_author_for_render($post_data['author']);
        $data['author'] = $author_data;
        
        $media_data = $this->prepare_media_for_render($post_data['featured_media']);
        $data['media'] = $media_data;
        
        
        // Return all response data.
        return rest_ensure_response($data);
    }
}