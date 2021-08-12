<?php

namespace LSDDonation\OYIndonesia;

use LSDDonation\DB;

class Plugin
{
    /**
     * Loads the plugin into WordPress.
     */
    public static function load()
    {
        $oyindonesia = new self();
        add_action('plugins_loaded', [$oyindonesia, 'loaded']);

        // Load Admin Class [Only For Admin Needs]
        if (is_admin()) {
            require_once LSDD_OYINDONESIA_PATH . 'admin/class-admin.php';
            Admin::register('lsddonation-oyindonesia', 'LSDDonasi - OY Indonesia', LSDD_OYINDONESIA_VERSION);
        }

        // Load Helper Function
        require_once LSDD_OYINDONESIA_PATH . 'includes/functions-helper.php';

        // Load Frontend Only
        require_once LSDD_OYINDONESIA_PATH . 'public/class-public.php';
        Frontend::register();
    }

    public function loaded()
    {
        require_once LSDD_OYINDONESIA_PATH . 'includes/class-payment-oyindonesia-va.php';
        require_once LSDD_OYINDONESIA_PATH . 'includes/class-payment-processing.php';
        require_once LSDD_OYINDONESIA_PATH . 'includes/class-payment-confirmation.php';
    }

}
Plugin::load();
