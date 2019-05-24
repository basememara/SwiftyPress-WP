<?php

class SwiftyPress_REST_V2_Posts_Controller {
 
    // Here initialize our namespace and resource name.
    public function __construct() {
        $this->namespace = '/swiftypress/v2';
        $this->resource_name = 'posts';
        $this->date_format = 'Y-m-d\TH:i:s';
    }
 
    // Register our routes.
    public function register_routes() {
        register_rest_route($this->namespace, '/' . $this->resource_name, array(
            array(
                'methods' => 'GET',
                'callback' => array($this, 'get_items')
            ),
            'schema' => array($this, 'get_item_schema')
        ));

        register_rest_route($this->namespace, '/' . $this->resource_name . '/(?P<id>[\d]+)', array(
            array(
                'methods' => 'GET',
                'callback' => array($this, 'get_item')
            ),
            'schema' => array($this, 'get_item_schema')
        ));
    }
 
    /**
     * Grabs the five most recent posts and outputs them as a rest response.
     *
     * @param WP_REST_Request $request Current request.
     */
    public function get_items($request) {
        // Construct query options
        $params = array();

        if (isset($request['per_page'])) {
            $per_page = (int)$request['per_page'];
            if ($per_page > 0) {
                $params['posts_per_page'] = $per_page;

                if (isset($request['page'])) {
                    $page_nbr = (int)$request['page'];
                    $params['offset'] = $page_nbr * $per_page;
                }
            }
        }

        if (isset($request['orderby'])) {
            $params['orderby'] = $request['orderby'];
        }

        if (isset($request['order'])) {
            $params['order'] = strtoupper($request['order']);
        }

        $posts = get_posts($params);
        $data = array();
 
        if (empty($posts)) {
            return rest_ensure_response($data);
        }
 
        foreach ($posts as $post) {
            $response = $this->prepare_item_for_response($post, $request);
            $data[] = $this->prepare_response_for_collection($response);
        }
 
        // Return all response data.
        return rest_ensure_response($data);
    }
 
    /**
     * Grabs the five most recent posts and outputs them as a rest response.
     *
     * @param WP_REST_Request $request Current request.
     */
    public function get_item($request) {
        $id = (int)$request['id'];
        $post = get_post($id);
 
        if (empty($post)) {
            return rest_ensure_response(array());
        }
 
        $response = $this->prepare_item_for_response($post, $request);
 
        // Return all response data.
        return $response;
    }
 
    /**
     * Matches the post data to the schema we want.
     *
     * @param WP_Post $post The comment object whose response is being prepared.
     */
    public function prepare_item_for_response($post, $request) {
        $post_data = array();
 
        $schema = $this->get_item_schema($request);
 
        // We are also renaming the fields to more understandable names.
        if (isset($schema['properties']['id'])) {
            $post_data['id'] = (int)$post->ID;
        }

        if (isset($schema['properties']['title'])) {
            $post_data['title'] = $post->post_title;
        }
        
        if (isset($schema['properties']['slug'])) {
            $post_data['slug'] = $post->post_name;
        }
        
        if (isset($schema['properties']['type'])) {
            $post_data['type'] = $post->post_type;
        }
        
        if (isset($schema['properties']['excerpt'])) {
            $post_data['excerpt'] = $post->post_excerpt;
        }
        
        if (isset($schema['properties']['date'])) {
            $post_data['date'] = get_the_date($this->date_format, $post->ID);
        }
        
        if (isset($schema['properties']['modified'])) {
            $post_data['modified'] = get_post_modified_time($this->date_format, null, $post->ID);
        }
        
        if (isset($schema['properties']['comment_count'])) {
            $post_data['comment_count'] = (int)$post->comment_count;
        }
        
        if (isset($schema['properties']['link'])) {
            $post_data['link'] = get_permalink($post->ID);
        }
        
        if (isset($schema['properties']['author'])) {
            $post_data['author'] = array(
                'id' => (int)$post->post_author,
                'username' => get_the_author_meta('user_login', $post->post_author),
                'email' => get_the_author_meta('user_email', $post->post_author),
                'name' => get_the_author_meta('display_name', $post->post_author),
                'link' => get_the_author_meta('url', $post->post_author),
                'avatar' => get_avatar_url($post->post_author),
                'description' => get_the_author_meta('description', $post->post_author)
            );
        }
        
        if (isset($schema['properties']['featured_media'])) {
            $attachment_id = get_post_thumbnail_id($post->ID);
            
            if ($attachment_id > 0) {
                $full = wp_get_attachment_image_src($attachment_id, 'full');
                $thumbnail = wp_get_attachment_image_src($attachment_id, 'medium');
                
                $post_data['featured_media'] = array(
                    'link' => $full[0],
                    'width' => (int)$full[1],
                    'height' => (int)$full[2],
                    'thumbnail_link' => $thumbnail[0],
                    'thumbnail_width' => (int)$thumbnail[1],
                    'thumbnail_height' => (int)$thumbnail[2]
                );
            } else {
                $post_data['featured_media'] = null;
            }
        }
        
        if (isset($schema['properties']['categories'])) {
            $terms = get_the_category($post->ID);

            if (!empty($terms)) {
                $post_data['categories'] = array_map( 
                    function($item) {
                        return array(
                            'id' => (int)$item->term_id,
                            'parent' => (int)$item->parent,
                            'name' => $item->name,
                            'slug' => $item->slug,
                            'taxonomy' => $item->taxonomy,
                            'count' => (int)$item->count
                        );
                    }, 
                    $terms
                );
            } else {
                $post_data['categories'] = [];
            }
        }
        
        if (isset($schema['properties']['tags'])) {
            $terms = get_the_tags($post->ID);
            if (!empty($terms)) {
                $post_data['tags'] = array_map( 
                    function($item) {
                        return array(
                            'id' => (int)$item->term_id,
                            'parent' => (int)$item->parent,
                            'name' => $item->name,
                            'slug' => $item->slug,
                            'taxonomy' => $item->taxonomy,
                            'count' => (int)$item->count
                        );
                    }, 
                    $terms
                );
            } else {
                $post_data['tags'] = [];
            }
        }
 
        if (isset($schema['properties']['content'])) {
            $post_data['content'] = apply_filters('the_content', $post->post_content, $post);
        }
 
        return rest_ensure_response($post_data);
    }
 
    /**
     * Prepare a response for inserting into a collection of responses.
     *
     * This is copied from WP_REST_Controller class in the WP REST API v2 plugin.
     *
     * @param WP_REST_Response $response Response object.
     * @return array Response data, ready for insertion into collection data.
     */
    public function prepare_response_for_collection($response) {
        if (!($response instanceof WP_REST_Response)) {
            return $response;
        }
 
        $data = (array)$response->get_data();
        $server = rest_get_server();
 
        if (method_exists($server, 'get_compact_response_links')) {
            $links = call_user_func(array($server, 'get_compact_response_links'), $response);
        } else {
            $links = call_user_func(array($server, 'get_response_links'), $response);
        }
 
        if (!empty($links)) {
            $data['_links'] = $links;
        }
 
        return $data;
    }
 
    /**
     * Get our sample schema for a post.
     *
     * @param WP_REST_Request $request Current request.
     */
    public function get_item_schema($request) {
        $schema = array(
            // This tells the spec of JSON Schema we are using which is draft 4.
            '$schema' => 'http://json-schema.org/draft-04/schema#',
            // The title property marks the identity of the resource.
            'title' => 'post',
            'type' => 'object',
            // In JSON Schema you can specify object properties in the properties attribute.
            'properties' => array(
                'id' => array(
                    'description' => esc_html__('Unique identifier for the object.', 'my-textdomain'),
                    'type' => 'integer',
                    'context' => array('view', 'edit', 'embed'),
                    'readonly' => true
                ),
                'title' => array(
                    'description' => esc_html__('The title for the object.', 'my-textdomain'),
                    'type' => 'string'
                ),
                'slug' => array(
                    'description' => esc_html__('The slug for the object.', 'my-textdomain'),
                    'type'  => 'string'
                ),
                'type' => array(
                    'description' => esc_html__('The type for the object.', 'my-textdomain'),
                    'type'  => 'string'
                ),
                'excerpt' => array(
                    'description' => esc_html__('The excerpt for the object.', 'my-textdomain'),
                    'type'  => 'string'
                ),
                'date' => array(
                    'description' => esc_html__('The date for the object.', 'my-textdomain'),
                    'type'  => 'string'
                ),
                'modified' => array(
                    'description' => esc_html__('The modified for the object.', 'my-textdomain'),
                    'type'  => 'string'
                ),
                'comment_count' => array(
                    'description' => esc_html__('The comment count for the object.', 'my-textdomain'),
                    'type'  => 'integer'
                ),
                'link' => array(
                    'description' => esc_html__('The link for the object.', 'my-textdomain'),
                    'type'  => 'string'
                ),
                'author' => array(
                    'description' => esc_html__('The author for the object.', 'my-textdomain'),
                    'type' => 'object'
                ),
                'featured_media' => array(
                    'description' => esc_html__('The featured media for the object.', 'my-textdomain'),
                    'type' => 'object'
                ),
                'content' => array(
                    'description' => esc_html__('The content for the object.', 'my-textdomain'),
                    'type'  => 'string'
                ),
                'categories' => array(
                    'description' => esc_html__('The categories for the object.', 'my-textdomain'),
                    'type' => 'object'
                ),
                'tags' => array(
                    'description' => esc_html__('The tags for the object.', 'my-textdomain'),
                    'type' => 'object'
                )
            )
        );
 
        return $schema;
    }
 
    // Sets up the proper HTTP status code for authorization.
    public function authorization_status_code() {
        return is_user_logged_in() ? 403 : 401;
    }
}