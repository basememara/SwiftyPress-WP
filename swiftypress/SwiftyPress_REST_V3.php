<?php

class SwiftyPress_REST_V3 {
 
    // Here initialize our namespace and resource name.
    public function __construct() {
        $this->namespace = '/swiftypress/v3';
        $this->date_format = 'Y-m-d\TH:i:s';
    }
 
    /**
     * Matches the post data to the schema we want.
     *
     * @param WP_Post $post The post object whose response is being prepared.
     */
    public function prepare_post_for_response($post, $request) {
        $schema = $this->get_post_schema($request);
        $post_data = array();
 
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
        
        if (isset($schema['properties']['created'])) {
            $post_data['created'] = get_post_time($this->date_format, true, $post->ID);
        }
        
        if (isset($schema['properties']['modified'])) {
            $post_data['modified'] = get_post_modified_time($this->date_format, true, $post->ID);
        }
        
        if (isset($schema['properties']['comment_count'])) {
            $post_data['comment_count'] = (int)$post->comment_count;
        }
        
        if (isset($schema['properties']['link'])) {
            $post_data['link'] = get_permalink($post->ID);
        }
        
        if (isset($schema['properties']['author'])) {
            $post_data['author'] = (int)$post->post_author;
        }
        
        if (isset($schema['properties']['featured_media'])) {
            $attachment_id = get_post_thumbnail_id($post->ID);
            $post_data['featured_media'] = $attachment_id > 0 ? (int)$attachment_id : null;
        }
        
        if (isset($schema['properties']['categories'])) {
            $terms = get_the_category($post->ID);

            if (!empty($terms)) {
                $post_data['categories'] = array_map( 
                    function($item) { return (int)$item->term_id; }, 
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
                    function($item) { return (int)$item->term_id; }, 
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
    * Matches the term data to the schema we want.
    *
    * @param WP_Post $term The term object whose response is being prepared.
    */
    public function prepare_term_for_response($terms) {
        if (empty($terms)) {
            return rest_ensure_response(array());
        }

        $term_data = array_map( 
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

        return rest_ensure_response($term_data);
    }
    
    /**
    * Matches the author data to the schema we want.
    *
    * @param WP_Post $term The author object whose response is being prepared.
    */
    public function prepare_author_for_response($author_id) {
        $user_registered = date(
            $this->date_format,
            strtotime(get_the_author_meta('user_registered', $author_id))
        );

        $user_modified = get_the_author_meta('wpsp_profile_modified', $author_id);

        $author_data = array(
            'id' => (int)$author_id,
            'name' => get_the_author_meta('display_name', $author_id),
            'link' => get_the_author_meta('url', $author_id),
            'avatar' => get_avatar_url($author_id),
            'description' => get_the_author_meta('description', $author_id),
            'created' => $user_registered,
            'modified' => !empty($user_modified)
                ? gmdate($this->date_format, $user_modified)
                : $user_registered
        );

        return rest_ensure_response($author_data);
    }
    
    /**
    * Matches the media data to the schema we want.
    *
    * @param WP_Post $term The media object whose response is being prepared.
    */
    public function prepare_media_for_response($attachment_id) {
        if (empty($attachment_id) || $attachment_id <= 0) {
            return null;
        }

        $full = wp_get_attachment_image_src($attachment_id, 'full');
        $thumbnail = wp_get_attachment_image_src($attachment_id, 'medium');
        
        $media_data = array(
            'id' => (int)$attachment_id,
            'link' => $full[0],
            'width' => (int)$full[1],
            'height' => (int)$full[2],
            'thumbnail_link' => $thumbnail[0],
            'thumbnail_width' => (int)$thumbnail[1],
            'thumbnail_height' => (int)$thumbnail[2]
        );

        return rest_ensure_response($media_data);
    }
 
    /**
     * Prepare a response for inserting into a collection of responses.
     *
     * This is copied from WP_REST_Controller class in the WP REST API v2 plugin.
     *
     * @param WP_REST_Response $response Response object.
     * @return array Response data, ready for insertion into collection data.
     */
    public function prepare_response_for_render($response) {
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
    public function get_post_schema($request) {
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
                'created' => array(
                    'description' => esc_html__('The created date for the object.', 'my-textdomain'),
                    'type'  => 'string'
                ),
                'modified' => array(
                    'description' => esc_html__('The modified date for the object.', 'my-textdomain'),
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
                    'description' => esc_html__('The author ID for the object.', 'my-textdomain'),
                    'type' => 'integer'
                ),
                'featured_media' => array(
                    'description' => esc_html__('The featured media for the object.', 'my-textdomain'),
                    'type' => 'integer'
                ),
                'content' => array(
                    'description' => esc_html__('The content for the object.', 'my-textdomain'),
                    'type'  => 'string'
                ),
                'categories' => array(
                    'description' => esc_html__('The category IDs for the object.', 'my-textdomain'),
                    'type' => 'array'
                ),
                'tags' => array(
                    'description' => esc_html__('The tag IDs for the object.', 'my-textdomain'),
                    'type' => 'array'
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