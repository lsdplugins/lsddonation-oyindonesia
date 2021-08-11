<?php

namespace LSDDonation\OYIndonesia;

use LSDDonation\Licenses;
use LSDDonation\LOG;

class Updater
{
    protected $id = 'lsddonation-oyindonesia'; // ***CHANGE THIS****
    protected $server = 'https://lsdplugins.com/api/';
    protected $server_slug = array('lsddonasi-oyindonesia', 'lsddonation-oyindonesia'); // ***CHANGE THIS****
    protected $plugin_version = LSDD_OYINDONESIA_VERSION; // ***CHANGE THIS****
    protected $plugin_file = LSDD_OYINDONESIA_BASE;

    public function __construct()
    {
        global $pagenow;

        // Adding Plugin Info on Update Available
        if ($pagenow == 'plugins.php') {
            add_action('upgrader_process_complete', array($this, 'destroy_update'), 10, 2);
            add_action('in_plugin_update_message-' . $this->id . '/' . $this->id . '.php', array($this, 'renew_message'), 10, 2);

            if (Licenses::get('key', $this->id)) {
                add_filter('site_transient_update_plugins', array($this, 'new_update'));
                add_filter('transient_update_plugins', array($this, 'new_update'));
                add_filter('plugins_api', array($this, 'plugin_info'), 30, 3);
                add_filter('plugin_row_meta', array($this, 'custom_row'), 10, 3);
                $this->manual_check();
            }
        }
    }

    /**
     * Fetch New Update form Server
     *
     * @param object $transient
     * @return void
     */
    public function new_update($transient)
    {
        // Check Transient Object
        if (!is_object($transient)) {
            return $transient;
        }

        $license_key = Licenses::get('key', $this->id);
        $domain = str_replace(".", "_", parse_url(get_site_url())['host']);

        //Get Transient & Information Update
        if (false == $remote = get_transient($this->id . '_update') && $license_key) { // If Transient Empty
            LOG::INFO("OYIndonesia :: Proccessing New Updates");

            // Remote GET
            $remote = wp_remote_get(
                $this->server . 'v1/license/update/' . $license_key . '__' . $domain,
                array(
                    'timeout' => 30,
                    'headers' => array(
                        'Accept' => 'application/json',
                    )
                )
            );

            if (!is_wp_error($remote)) { // WP Error Causing CURL
                $remote = json_decode($remote['body']);
            } else {
                set_transient($this->id . '_update', 'failed_get_update', 300); // Waiting 5 minutes
            }

            // Reconnect License
            if (isset($remote->code) && $remote->code == 999) {
                $licenses = get_option('lsddonation_licenses');
                unset($licenses[$this->id]);
                update_option('lsddonation_licenses', $licenses);
            }

            //Get Response Body
            if (!is_wp_error($remote) && isset($remote->slug) && in_array($remote->slug, $this->server_slug)) {
                set_transient($this->id . '_update', $remote, 60 * 60 * 6); // 6 hours cache
                LOG::INFO("OYIndonesia :: Update Exist");
            } else {
                set_transient($this->id . '_update', 'failed_get_update', 60); // 6 hours cache
            }
        }

        // Processing Update
        $transient_check = get_transient($this->id . '_update');
        $transient_flag = get_transient($this->id . '_update_check');

        if (get_transient($this->id . '_update') != 'failed_get_update') {
            $remote = get_transient($this->id . '_update');

            // Version Check : Delay 30 Minutes
            if (!is_wp_error($remote) && empty($transient_flag)) {

                if ($remote && version_compare($this->plugin_version, $remote->version, '<')) { //change this if want to update
                    $res = new \stdClass();
                    $res->slug = $this->id;
                    $res->plugin = $this->id . '/' . $this->id . '.php';
                    $res->new_version = $remote->version;
                    $res->tested = $remote->tested;
                    if (isset($remote->download_url)) {
                        $res->package = $remote->download_url;
                    }

                    $transient->response[$res->plugin] = $res;
                    set_transient($this->id . '_update_check', $transient, 1800); // 30 minute
                    LOG::INFO("OYIndonesia :: New Updates !!! " . $remote->version);
                }
            } else {
                if (!empty($transient_flag)) {
                    $transient = $transient_flag;
                }
            }
        } else {
            if (!empty($transient_flag)) {
                $transient = $transient_flag;
            }
        }
        return $transient;
    }

    /**
     * Display Plugin Information
     *
     * @param object $res
     * @param string $action
     * @param array $args
     * @return void
     */
    public function plugin_info($res, $action, $args)
    {
        $remote = get_transient($this->id . '_update');

        if (!is_wp_error($remote) && isset($args->slug) && $args->slug == $this->id) {
            $res = new \stdClass();

            $res->name = $remote->name;
            $res->slug = $this->id;
            $res->version = $remote->version;
            $res->tested = $remote->tested;
            $res->requires = $remote->requires;
            $res->author = $remote->author; // I decided to write it directly in the plugin
            $res->author_profile = $remote->author_profile; // WordPress.org profile
            if (isset($remote->download_url)) {
                $res->download_link = $remote->download_url;
                $res->trunk = $remote->download_url;
            }
            $res->last_updated = $remote->last_updated;
            $sections = $remote->sections[0];
            $res->sections = array(
                'description' => $sections->description, // description tab
                'installation' => $sections->installation, // installation tab
                'changelog' => $sections->changelog,
            );
            $res->banners = array(
                'low' => $remote->low_image,
                'high' => $remote->high_image
            );
        }

        return $res;
    }

    /**
     * Destroying Update Cache
     *
     * @param object $upgrader_object
     * @param array $options
     * @return void
     */
    public function destroy_update($upgrader_object, $options)
    {
        if ($options['action'] == 'update' && $options['type'] === 'plugin') {
            delete_transient($this->id . '_update');
            delete_transient($this->id . '_update_check');
        }
    }

    /**
     * Renew Plugin for Update Message
     *
     * @param array $dataserver
     * @param object $plugin_info_object
     * @return void
     */
    public function renew_message($dataserver, $plugin_info_object)
    {
        if (empty($dataserver['package'])) {
            printf(__(' Please %s renew your license %s to update', 'lsdd'), '<a href="https://lsdplugins.com/member/" target="_blank">', '</a>');
        }
    }

    /**
     * Add Custom Row in Plugin Lists
     *
     * @param array $links_array
     * @param name $plugin_file_name
     * @return void
     */
    public function custom_row($links_array, $plugin_file_name)
    {
        if (strpos($plugin_file_name, basename($this->plugin_file))) {

            // $links_array[] = '<a href="#">Support</a>';
            $links_array[] = '<a href="' . admin_url('plugins.php?check_update=' . $this->id) . '">' . __('Cek Pembaharuan', 'lsddonation-oyindonesia') . '</a>';
        }
        return $links_array;
    }

    /**
     * Manual Check Update
     *
     * When user click link check for updates in plugin admin page
     *
     * @return void
     */
    public function manual_check()
    {
        global $pagenow;

        if (isset($_GET['check_update']) && $_GET['check_update'] == $this->id && $pagenow == 'plugins.php') {
            delete_transient($this->id . '_update');
            delete_transient($this->id . '_update_check');
            LOG::INFO("OYIndonesia :: Manual Update Check");
        }
    }
}

/* Self Init */
new Updater();
