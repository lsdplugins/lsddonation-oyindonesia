<?php

namespace LSDDonation\OYIndonesia;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Settings class.
 */
class Admin
{
    /**
     * Plugin Slug
     *
     * @var string
     */
    private $slug;

    /**
     * Plugin Title
     *
     * @var string
     */
    private $title;

    /**
     * Plugin Version
     *
     * @var string
     */
    private $version;

    /**
     * Cloning is forbidden.
     *
     * @since 1.0.0
     */
    public function __clone()
    {
        _doing_it_wrong(__FUNCTION__, esc_html(__('Cloning of is forbidden')), LSDD_OYINDONESIA_VERSION);
    }

    /**
     * Unserializing instances of this class is forbidden.
     *
     * @since 1.0.0
     */

    public function __wakeup()
    {
        _doing_it_wrong(__FUNCTION__, esc_html(__('Unserializing instances of is forbidden')), LSDD_OYINDONESIA_VERSION);
    }

    /**
     * Constructor function.
     *
     * @param object $parent Parent object.
     */
    public function __construct()
    {
    }

    /**
     * Registering OYIndonesia Admin
     *
     * @param string $slug
     * @param string $title
     * @param string $version
     * @return void
     */
    public static function register(string $slug, string $title, string $version)
    {
        $admin = new self();

        $admin->slug = $slug;
        $admin->title = $title;
        $admin->version = $version;

        add_action('admin_init', array($admin, 'admin_init'));
        add_action('admin_notices', array($admin, 'admin_notices'));
    }

    /**
     * Display Admin Notice
     * OYIndonesia must be configure
     *
     * @return void
     */
    public function admin_notices()
    {
        if (lsdd_oyindonesia_get_settings('apikey') == "" && is_admin()) {
            $message = sprintf(esc_html__('LSDDonasi - OY Indonesia belum di konfigurasi. ', 'lsddonation-oyindonesia'), LSDD_OYINDONESIA_REQUIRED);
            $html_message = sprintf('<div class="notice notice-error">%s <a href="https://learn.lsdplugins.com/docs/lsddonasi/ekstensi-konfirmasi-otomatis/konfigurasi-oyindonesia/" target="_blank">' . __('Baca Panduan', 'lsddonation-oyindonesia') . '</a><p>agar konfirmasi otomatis berjalan.</p></div>', wpautop($message));
            echo wp_kses_post($html_message);
        }
    }

    /**
     * Load on Admin Initialize
     *
     * @return void
     */
    public function admin_init()
    {
        // Redirect to License after activate plugin
        if (get_option('oyindonesia_activator_redirect')) {

            $core_plugin = get_plugin_data(LSDD_PATH . 'lsddonation.php');
            if (!version_compare($core_plugin['Version'], LSDD_OYINDONESIA_REQUIRED, '>=')) {
                add_action('admin_notices', 'lsdd_oyindonesia_fail_version');
                $core_version = false;
            } else {
                delete_option('oyindonesia_activator_redirect');
                exit(wp_redirect(admin_url('admin.php?page=lsddonation&tab=licenses')));
            }
        }

        require_once 'class-updater.php';
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles()
    {
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts()
    {
    }
}
