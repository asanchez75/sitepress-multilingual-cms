<?php
if (!class_exists('WP_Http')) {
    include_once(ABSPATH . WPINC . '/class-http.php');
}
require_once ICL_PLUGIN_PATH . '/lib/xml2array.php';

class SitePress_Support
{


    function __construct() {

        if (isset($_GET['page']) && $_GET['page'] == ICL_PLUGIN_FOLDER . '/menu/support.php') {
            wp_enqueue_script('sitepress-icl_reminders', ICL_PLUGIN_URL . '/res/js/icl_reminders.js', array('jquery'), ICL_SITEPRESS_VERSION);
            add_action('icl_support_admin_page', array(&$this, 'admin_page'));
        }
    }

    function admin_page() {
        $this->offer_wpml_org_subscription();
    }


    function offer_wpml_org_subscription() {

        if (isset($_POST['icl_subscription_form_nonce'])
                && $_POST['icl_subscription_form_nonce']
                == wp_create_nonce('icl_subscription_form')) {

            global $sitepress;

            $_POST['sub']['subscription_email'] = trim($_POST['sub']['subscription_email'], ' ');
            $_POST['sub']['subscription_key'] = trim($_POST['sub']['subscription_key'], ' ');
            $sitepress->save_settings($_POST['sub']);
            echo '<script type="text/javascript">location.href = "admin.php?page=' . ICL_PLUGIN_FOLDER . '/menu/support.php";</script>';
        }


        global $sitepress_settings;

        $args = new stdClass;
        $args->slug = 'WPML_all';

        global $wpml_plugins;

        $installed = get_plugins();
        // Filter WPML plugins
        foreach ($installed as $key => $plugin) {
            if (!in_array($plugin['Name'], $wpml_plugins)) {
                unset($installed[$key]);
            }
        }
        // TODO Why use json decode?
        //$args->installed = json_encode($installed);
        $args->installed = $installed;

        $plugin_info = get_WPML_plugin_page(0, 'support_information', $args);

        ?>

            <form id="icl_subscription_form" method="post" action="">
            <?php wp_nonce_field('icl_subscription_form', 'icl_subscription_form_nonce'); ?>
            <input type="hidden" name="icl_support_account" value="create" />

        <p style="line-height:1.5"><?php @printf($plugin_info->subscription['before']); ?></p>


        <table class="form-table icl-account-setup">
            <tbody>
                <tr class="form-field">
                    <th scope="row"><?php _e('WPML.org subscription email', 'sitepress'); ?></th>
                    <td><input name="sub[subscription_email]" type="text" value="<?php echo isset($_POST['sub']['subscription_email']) ? $_POST['sub']['subscription_email'] :
                        isset($sitepress_settings['subscription_email']) ? $sitepress_settings['subscription_email'] : ''; ?>" /></td>
                </tr>
                <tr class="form-field">
                    <th scope="row"><?php _e('WPML.org subscription key', 'sitepress'); ?></th>
                    <td><input name="sub[subscription_key]" type="text" value="<?php echo isset($_POST['sub']['subscription_key']) ? $_POST['sub']['subscription_key'] :
                        isset($sitepress_settings['subscription_key']) ? $sitepress_settings['subscription_key'] : ''; ?>" /></td>
                </tr>

            </tbody>
        </table>
        <p class="submit">
            <input type="hidden" name="save_sub" value="1" />
            <input class="button" name="save sub" value="<?php _e('Save subscription details', 'sitepress'); ?>" type="submit" />
        </p>
        <div class="icl_progress" style="display:none;"><?php _e('Saving. Please wait...', 'sitepress'); ?></div>

        <?php @printf($plugin_info->subscription['after']); ?>

    </form>

        <p style="margin-top: 20px;"><?php _e('Technical support for clients is available via <a target="_blank" href="http://forum.wpml.org">WPML forum</a>.','sitepress'); ?></p>
	<?php
        echo '<p style="margin-top: 20px;">' . sprintf(__('For advanced access or to completely uninstall WPML and remove all language information, use the <a href="%s">troubleshooting</a> page.', 'sitepress'),
                'admin.php?page=' . basename(ICL_PLUGIN_PATH) . '/menu/troubleshooting.php') . '</p>';

        }

    }