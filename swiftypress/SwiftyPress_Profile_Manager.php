<?php

class SwiftyPress_Profile_Manager {
 
    public function __construct() {
        
    }

    public function subscribe_updates($user_id) {
        // Store profile last modified date
        update_user_meta($user_id, 'wpsp_profile_modified', current_time('timestamp', true));
    }
}