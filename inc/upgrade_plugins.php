<?php

    define ('WPML_UPDATE_URL', 'http://wpml.org/?wpml_plugin_info=1');

    $wpml_plugins = array('WPML CMS Nav',
                          'WPML Multilingual CMS',
                          'WPML String Translation',
                          'WPML Sticky Links',
                          'WPML Translation Management');


    add_filter('pre_set_site_transient_update_plugins', 'check_for_WPML_plugin_updates');
    add_filter('plugins_api', 'get_WPML_plugin_page', 1, 3);

    // Force WP to check for plugin updates
    if (function_exists('get_site_transient')) {
        $WPML_check_done = get_option('WPML_check_done', false);
        if (!$WPML_check_done) {
            $current = get_site_transient( 'update_plugins' );
            $current->last_checked = 0;
            set_site_transient( 'update_plugins', $current );
            
            update_option('WPML_check_done', true);
        }
    }
    

    function check_for_WPML_plugin_updates($value) {
        // called when the update_plugins transient is saved.
        
        global $wpml_plugins, $sitepress_settings;
        
        if(empty($wpml_plugins)) return $value;
        
	    if ( function_exists( 'get_plugins' )) {

            
            $plugins = get_plugins();
            // Filter WPML plugins
            foreach ($plugins as $key => $plugin) {
                if (!in_array($plugin['Name'], $wpml_plugins)) {
                    unset($plugins[$key]);
                }
            }
            
            $request = wp_remote_post(WPML_UPDATE_URL, array(
                'timeout' => 15,
                'body' => array(
                    'action' => 'update_information',
                    'subscription_email' => isset($sitepress_settings['subscription_email'])?$sitepress_settings['subscription_email']:false,
                    'subscription_key' => isset($sitepress_settings['subscription_key'])?$sitepress_settings['subscription_key']:false,
                    'plugins' => $plugins,
                    'lc' => get_option('WPLANG'),
                    )));
            // TODO we're not returning anything as WP_Error yet
            if ( is_wp_error($request) ) {
                $res = false;
            } else {
                $res = maybe_unserialize($request['body']);
            }
            
            if ($res !== false) {        
                // check for WPML plugins
                foreach ($plugins as $key => $plugin) {
                    if(!empty($res[$key])){
                        $value->response[$key] = $res[$key];
                    } else {
                        if (isset($value->response[$key])) {
                            unset($value->response[$key]);
                        }
                    }
                }
            }
        }

        return $value;
    }
    
    function get_WPML_plugin_page($state, $action, $args) {
        global $wpdb, $sitepress_settings, $sitepress;
        
        global $wpml_plugins;
        
        $res = false;

        if ($args->slug == "WPML_all" || in_array(str_replace('_', ' ', $args->slug), $wpml_plugins)) {

            if (!isset($args->installed)) {
                $args->installed = "";
            }
            $body_array = array('action' => $action,
                                    'request' => serialize($args),
                                    'slug' => $args->slug,
                                    'installed' => $args->installed,
                                    'subscription_email' => isset($sitepress_settings['subscription_email'])?$sitepress_settings['subscription_email']:false,
                                    'subscription_key' => isset($sitepress_settings['subscription_key'])?$sitepress_settings['subscription_key']:false,
                                    'lc' => get_option('WPLANG'),
                                    );
            
            $request = wp_remote_post(WPML_UPDATE_URL, array( 'timeout' => 15, 'body' => $body_array) );
            if ( is_wp_error($request) ) {
                $res = new WP_Error('plugins_api_failed', __('An Unexpected HTTP Error occurred during the API request.', 'sitepress'), $request->get_error_message() );
            } else {
                $res = maybe_unserialize($request['body']);
                if ( false === $res )
                    $res = new WP_Error('plugins_api_failed', __('An unknown error occurred.', 'sitepress'), $request['body']);
            }
        }
        
        return $res;
    }
?>