<?php

class SwiftyPress_REST_V5_Author_Controller extends SwiftyPress_REST_V5 {
 
    // Here initialize our namespace and resource name.
    public function __construct() {
        parent::__construct();
        $this->resource_name = 'author';
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
        $author = $this->prepare_author_for_render($id);
 
        if (empty($author)) {
            return new WP_Error(
                'author_does_not_exist', 
                'The author you are looking for does not exist.', 
                array('status' => 404)
            );
        }
        
        $data = array('author' => $author);
        
        // Return all response data.
        return rest_ensure_response($data);
    }
}