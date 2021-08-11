<?php

namespace LSDDonation\OYIndonesia;

use LSDDonation\DB;
use LSDDonation\LOG;

if (!defined('ABSPATH')) {
    exit;
}

class Frontend
{
    /**
     * Register the admin page class with all the appropriate WordPress hooks.
     *
     * @param Options $options
     */
    public static function register($slug, $name, $version)
    {
        $frontend = new self();

        $frontend->slug = $slug;
        $frontend->name = $name;
        $frontend->version = $version;

        add_action('wp_enqueue_scripts', [$frontend, 'enqueue_styles']);
        add_action('wp_enqueue_scripts', [$frontend, 'enquene_scripts']);
        add_action('rest_api_init', [$frontend, 'rest']);
    }

    /**
     * Registering WebHook for Handle Notification
     * from Payment Gateway System
     *
     * @return void
     */
    public function rest()
    {
        register_rest_route('lsdd/v1', '/notification/oyindonesia', [
            'methods' => \WP_REST_Server::CREATABLE,
            'callback' => [$this, 'rest_handler'],
            'permission_callback' => '__return_true',
        ]);
    }

    /**
     * Processing Notification Json and
     * Completed Payment
     *
     * @return void
     */
    public function rest_handler()
    {
        $handlers = stripslashes_deep(file_get_contents('php://input'));
        parse_str($handlers, $params);

        lsdd_generate_log('oyindonesia', LSDD_OYINDONESIA_PATH, '--- Ipaymu Automatic Confirmation ---');
        lsdd_generate_log('oyindonesia', LSDD_OYINDONESIA_PATH, $handlers);

        $session_id = esc_attr($params['sid']);
        $status_code = esc_attr($params['status_code']);

        $db = new DB\Reports_Repository();
        $report = $db->read(array('reference' => $session_id));

        if ($report && isset($report[0])) {
            if ($status_code == 1) {
                $report_id = $report[0]->report_id;
                if ($report[0]->status != 'completed') { // CHeck Status in Report

                    $db->completed($report_id); // Make it Completed
                    lsdd_generate_log('oyindonesia', LSDD_OYINDONESIA_PATH, '-> Auto Complete Report : ID #' . $report_id);
                }
            } else {
                lsdd_generate_log('oyindonesia', LSDD_OYINDONESIA_PATH, '-> Failed :: Status Failed');
            }
        } else {
            lsdd_generate_log('oyindonesia', LSDD_OYINDONESIA_PATH, '-> Failed :: Session ID Not Found');
        }
    }

    public function enquene_scripts()
    {
        wp_enqueue_script('lsdd-oyindonesia', LSDD_OYINDONESIA_URL . 'assets/frontend/js/oyindonesia.js', array('jquery'), LSDD_OYINDONESIA_VERSION, false);
    }

    public function enqueue_styles()
    {
    }
}
