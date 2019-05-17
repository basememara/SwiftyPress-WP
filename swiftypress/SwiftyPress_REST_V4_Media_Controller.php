<?php

class SwiftyPress_REST_V4_Media_Controller extends SwiftyPress_REST_V4 {
 
    // Here initialize our namespace and resource name.
    public function __construct() {
        parent::__construct();
        $this->resource_name = 'media';
    }
 
    // Register our routes.
    public function register_routes() {
        register_rest_route($this->namespace, '/' . $this->resource_name . '/(?P<id>[\d]+)', array(
            array(
                'methods' => 'GET',
                'callback' => array($this, 'get_media')
            ),
            'schema' => array($this, 'get_media_schema')
        ));
    }
 
    /**
     * Get the media and outputs it as a rest response.
     *
     * @param WP_REST_Request $request Current request.
     */
    public function get_media($request) {
        $id = (int)$request['id'];
        $media = $this->prepare_media_for_response($id);
 
        if (empty($media)) {
            return new WP_Error(
                'media_does_not_exist', 
                'The media you are looking for does not exist.', 
                array('status' => 404)
            );
        }
        
        $data = array(
            'media' => $this->prepare_response_for_render($media)
        );
        
        // Return all response data.
        return rest_ensure_response($data);
    }
}