<?php

class SwiftyPress_REST_V5 {
 
    // Here initialize our namespace and resource name.
    public function __construct() {
        $this->namespace = '/swiftypress/v5';
        $this->date_format = 'Y-m-d\TH:i:s';
    }
 
    /**
     * Check permissions for the posts.
     *
     * @param WP_REST_Request $request Current request.
     */
    public function get_permissions_check($request) {
        if (!current_user_can('read')) {
            return new WP_Error(
                'rest_forbidden',
                esc_html__('You cannot view the ' . $this->resource_name . ' resource.'),
                array('status' => $this->authorization_status_code()));
        }
        return true;
    }
 
    // Sets up the proper HTTP status code for authorization.
    public function authorization_status_code() {
        return is_user_logged_in() ? 403 : 401;
    }

    public function prepare_post_for_render($post, $request, $terms) {
        $post_data = array();
 
        // We are also renaming the fields to more understandable names.
        $post_data['id'] = (int)$post->ID;
        $post_data['title'] = $post->post_title;
        $post_data['slug'] = $post->post_name;
        $post_data['type'] = $post->post_type;
        $post_data['excerpt'] = $post->post_excerpt;
        $post_data['content'] = apply_filters('the_content', $post->post_content, $post);
        $post_data['link'] = get_permalink($post->ID);
        $post_data['comment_count'] = (int)$post->comment_count;
        $post_data['author'] = (int)$post->post_author;
        
        $attachment_id = get_post_thumbnail_id($post->ID);
        $post_data['featured_media'] = $attachment_id > 0 ? (int)$attachment_id : null;
        
        if (!empty($terms)) {
            $post_data['terms'] = array_map( 
                function($item) { return (int)$item['id']; }, 
                $terms
            );
        } else {
            $post_data['terms'] = [];
        }

        $post_data['meta'] = null;
        if (!empty($request['meta_keys'])) {
            foreach (explode(',', $request['meta_keys']) as $key) {
                $post_data['meta'][$key] = strval(get_post_meta($post->ID, $key, true));
            }
        }

        $post_data['created'] = get_post_time($this->date_format, true, $post->ID);
        $post_data['modified'] = get_post_modified_time($this->date_format, true, $post->ID);
 
        return $post_data;
    }
    
    public function prepare_author_for_render($author_id) {
        if (empty($author_id) || $author_id <= 0) {
            return null;
        }

        $user_registered = get_the_author_meta('user_registered', $author_id);

        if (empty($user_registered)) {
            return null;
        }

        $user_created = date($this->date_format, strtotime($user_registered));
        $user_modified = get_the_author_meta('wpsp_profile_modified', $author_id);

        $author_data = array(
            'id' => (int)$author_id,
            'name' => get_the_author_meta('display_name', $author_id),
            'link' => get_the_author_meta('url', $author_id),
            'avatar' => get_avatar_url($author_id),
            'description' => get_the_author_meta('description', $author_id),
            'created' => $user_created,
            'modified' => !empty($user_modified)
                ? gmdate($this->date_format, $user_modified)
                : $user_created
        );

        return $author_data;
    }
    
    public function prepare_media_for_render($attachment_id) {
        if (empty($attachment_id) || $attachment_id <= 0) {
            return null;
        }

        $full = wp_get_attachment_image_src($attachment_id, 'full');
        $thumbnail = wp_get_attachment_image_src($attachment_id, 'medium');

        if (empty($full)) {
            return null;
        }
        
        $media_data = array(
            'id' => (int)$attachment_id,
            'link' => $full[0],
            'width' => (int)$full[1],
            'height' => (int)$full[2],
            'thumbnail_link' => $thumbnail[0],
            'thumbnail_width' => (int)$thumbnail[1],
            'thumbnail_height' => (int)$thumbnail[2]
        );

        return $media_data;
    }
    
    public function prepare_term_for_render($term) {
        if (empty($term)) {
            return null;
        }

        $term_data = array(
            'id' => (int)$term->term_id,
            'parent' => (int)$term->parent,
            'name' => $term->name,
            'slug' => $term->slug,
            'taxonomy' => $term->taxonomy,
            'count' => (int)$term->count
        );

        return $term_data;
    }
    
    public function prepare_terms_for_render($post_ids, $request) {
        if (empty($post_ids)) {
            return array();
        }

        $default_taxonomies = array('category', 'post_tag');
        $taxonomies = !empty($request['taxonomies'])
            ? explode(',', $request['taxonomies'])
            : $default_taxonomies;

        $terms = get_the_terms($post_ids, $taxonomies);

        // Fallback to default if taxonomy doesn't exist
        if (is_wp_error($terms)) {
            $terms = get_the_terms($post_ids, $default_taxonomies);
        }

        $terms_data = array_map(
            array($this, 'prepare_term_for_render'), 
            $terms
        );

        if (empty($terms_data)) {
            return array();
        }

        return array_filter($terms_data, function($item) {
            return $item !== null;
        });
    }
}