<?php


include_once('SwiftyPress_LifeCycle.php');

class SwiftyPress_Plugin extends SwiftyPress_LifeCycle {

    /**
     * See: http://plugin.michael-simpson.com/?page_id=31
     * @return array of option meta data.
     */
    public function getOptionMetaData() {
        //  http://plugin.michael-simpson.com/?page_id=31
        return array(
            //'_version' => array('Installed Version'), // Leave this one commented-out. Uncomment to test upgrades.
            'iOSID' => array(__('Apple iOS ID', 'swiftypress')),
            'AndroidID' => array(__('Google Android ID', 'swiftypress'))
        );
    }

//    protected function getOptionValueI18nString($optionValue) {
//        $i18nValue = parent::getOptionValueI18nString($optionValue);
//        return $i18nValue;
//    }

    protected function initOptions() {
        $options = $this->getOptionMetaData();
        if (!empty($options)) {
            foreach ($options as $key => $arr) {
                if (is_array($arr) && count($arr > 1)) {
                    $this->addOption($key, $arr[1]);
                }
            }
        }
    }

    public function getPluginDisplayName() {
        return 'SwiftyPress';
    }

    protected function getMainPluginFileName() {
        return 'swiftypress.php';
    }

    /**
     * See: http://plugin.michael-simpson.com/?page_id=101
     * Called by install() to create any database tables if needed.
     * Best Practice:
     * (1) Prefix all table names with $wpdb->prefix
     * (2) make table names lower case only
     * @return void
     */
    protected function installDatabaseTables() {
        //        global $wpdb;
        //        $tableName = $this->prefixTableName('mytable');
        //        $wpdb->query("CREATE TABLE IF NOT EXISTS `$tableName` (
        //            `id` INTEGER NOT NULL");
    }

    /**
     * See: http://plugin.michael-simpson.com/?page_id=101
     * Drop plugin-created tables on uninstall.
     * @return void
     */
    protected function unInstallDatabaseTables() {
        //        global $wpdb;
        //        $tableName = $this->prefixTableName('mytable');
        //        $wpdb->query("DROP TABLE IF EXISTS `$tableName`");
    }


    /**
     * Perform actions when upgrading from version X to version Y
     * See: http://plugin.michael-simpson.com/?page_id=35
     * @return void
     */
    public function upgrade() {
    }

    public function addActionsAndFilters() {

        // Add options administration page
        // http://plugin.michael-simpson.com/?page_id=47
        add_action('admin_menu', array(&$this, 'addSettingsSubMenuPage'));

        // Example adding a script & style just for the options administration page
        // http://plugin.michael-simpson.com/?page_id=47
        //        if (strpos($_SERVER['REQUEST_URI'], $this->getSettingsSlug()) !== false) {
        //            wp_enqueue_script('my-script', plugins_url('/js/my-script.js', __FILE__));
        //            wp_enqueue_style('my-style', plugins_url('/css/my-style.css', __FILE__));
        //        }


        // Add Actions & Filters
        // http://plugin.michael-simpson.com/?page_id=37


        // Adding scripts & styles to all pages
        // Examples:
        //        wp_enqueue_script('jquery');
        //        wp_enqueue_style('my-style', plugins_url('/css/my-style.css', __FILE__));
        //        wp_enqueue_script('my-script', plugins_url('/js/my-script.js', __FILE__));


        // Register short codes
        // http://plugin.michael-simpson.com/?page_id=39


        // Register AJAX hooks
        // http://plugin.michael-simpson.com/?page_id=41

        add_action('wp_head', array(&$this, 'add_meta'));
    		add_filter('json_endpoints', array(&$this, 'register_routes'));
    		add_filter('json_prepare_post', array(&$this, 'register_post_extensions'), 10, 3);
    		add_action('wp_json_server_before_serve', array(&$this, 'pre_register_routes'));

        if (isset($_GET['mobileembed']) && $_GET['mobileembed'] == "1") {
            wp_enqueue_style('mobileembed-style', get_stylesheet_directory_uri() . '/mobile-embed.css', __FILE__);
			      wp_enqueue_script('mobileembed-script', plugins_url('js/mobile-embed.js', __FILE__), array('jquery'));
        }
    }

    public function add_meta() {
        global $post;

        $url = $_SERVER['HTTP_HOST'] . rtrim($_SERVER['REQUEST_URI'], '/');

        // Smart App Banner for Safari and iOS
        echo '<meta name="apple-itunes-app" content="app-id=' . $this->getOption('iOSID') . ', app-argument=' . 'http://' . $url . '">';

        // Google App Indexing
        echo '<link rel="alternate" href="android-app://' . $this->getOption('AndroidID') . '/' . 'http/' . $url . '" />';
        echo '<link rel="alternate" href="ios-app://' . $this->getOption('iOSID') . '/' . 'http/' . $url . '" />';

        // App Icons
        echo '<link rel="icon" type="image/png" href="/favicon-32x32.png" sizes="32x32">';
        echo '<link rel="icon" type="image/png" href="/android-chrome-192x192.png" sizes="192x192">';
        echo '<link rel="icon" type="image/png" href="/favicon-96x96.png" sizes="96x96">';
        echo '<link rel="icon" type="image/png" href="/favicon-16x16.png" sizes="16x16">';
        echo '<link rel="manifest" href="/manifest.json">';
        echo '<link rel="mask-icon" href="/safari-pinned-tab.svg" color="#5bbad5">';
        echo '<meta name="msapplication-TileColor" content="#da532c">';
        echo '<meta name="msapplication-TileImage" content="/mstile-144x144.png">';
    }

    public function register_routes($routes) {
        $routes['/comments/count'] = array(
            array(array($this, 'get_comment_count'), WP_JSON_Server::READABLE)
        );

        $routes['/comments/(?P<id>[\d]+)/count'] = array(
            array(array($this, 'get_comment_count_for_post'), WP_JSON_Server::READABLE)
        );

        return $routes;
    }

    public function get_comment_count() {
        global $wpdb;
        $sql = "SELECT id AS ID, comment_count AS comments_count FROM {$wpdb->prefix}posts WHERE post_type = 'post' AND comment_count > 0 ORDER BY comment_count DESC";
        return $wpdb->get_results($sql);
    }

    public function get_comment_count_for_post($id) {
        $post = get_post($id);
        return (int)$post->comment_count;
    }

    public function pre_register_routes() {
        if (is_plugin_active('crayon-syntax-highlighter/crayon_wp.class.php')) {
            // Avoid <pre><code> content from being mangled up in web services
            remove_filter('the_posts', 'CrayonWP::the_posts', 100);
        }
    }

    public function register_post_extensions($postray, $postdat, $context) {
        $extensions = array("comment_count" => wp_count_comments($postdat['ID']));
        return array_merge($postray, $extensions);
    }
}
