<?php

namespace LSDDonation\OYIndonesia;

use LSDDonation\DB;
use LSDDonation\Licenses;

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

        require_once LSDD_OYINDONESIA_PATH . 'includes/functions-helper.php';
    }

    public function loaded()
    {
        // Load Frontend Only

        if (Licenses::get('lsddonation') && Licenses::get(false, 'lsddonation-oyindonesia') && !is_admin()) {
            require_once LSDD_OYINDONESIA_PATH . 'public/class-frontend.php';
            Frontend::register('lsddonation-oyindonesia', 'LSDDonasi - OY Indonesia', LSDD_OYINDONESIA_VERSION);
        }

        if (Licenses::get('lsddonation') && Licenses::get(false, 'lsddonation-oyindonesia')) {
            require_once LSDD_OYINDONESIA_PATH . 'includes/class-payment-oyindonesia.php';
            add_filter('lsddonation/payment/gateway/oyindonesia', [$this, 'oyindonesia_payment_request'], 10, 2);
        }
    }

    /**
     * Filter ayment Gateway Request
     *
     * $report = array();
     * $report['report_id'] = 1;
     * $report['name'] = 'Lasida';
     * $report['phone'] = '08561655028';
     * $report['email'] = 'lasidaziz@gmail.com';
     * $report['program_id'] = 1;
     * $report['total'] = 56500;
     *
     * @param int $report_id
     * @param array $report
     * @return void
     */
    public function oyindonesia_payment_request($report_id, $report)
    {

        #1 Selecting Endpoint Server
        if (lsdd_oyindonesia_get_settings('production') == 'on') {
            $url = 'https://my.oyindonesia.com/payment';
        } else {
            $url = 'https://sandbox.oyindonesia.com/payment';
        }

        if (empty(lsdd_oyindonesia_get_settings('apikey'))) {
            lsdd_generate_log('oyindonesia', LSDD_OYINDONESIA_PATH, 'Ipaymu Credentials Required');
            $report['response'] = "failed";
            $report['status'] = "failed";
            return $report;
        }

        // #2 Generate Payment URL
        $headers = array(
            'Content-Type' => 'application/json',
        );

        $body = array(
            'key' => lsdd_oyindonesia_get_settings('apikey'),
            'action' => 'payment',
            'product' => get_the_title($report['program_id']),
            'price' => $report['total'],
            'quantity' => 1,
            'ureturn' => lsdd_payment_url() . lsdd_payment_key($report_id) . '/?payment=oyindonesia',
            'unotify' => get_rest_url() . 'lsdd/v1/notification/oyindonesia', // Set Webhook
            'ucancel' => lsdd_payment_url() . lsdd_payment_key($report_id) . '/?payment=oyindonesia&status=cancel',
            'buyer_name' => $report['name'],
            'buyer_phone' => $report['phone'],
            'buyer_email' => $report['email'],
            'format' => 'json',
            'reference_id' => $report_id,
        );

        $request = array(
            'method' => 'POST',
            'timeout' => 30,
            'headers' => $headers,
            'httpversion' => '1.0',
            'sslverify' => false,
            'body' => json_encode($body),
            'cookies' => array(),
        );

        // Send Request to Ipaymu
        $raw = wp_remote_post($url, $request);

        if (is_a($raw, 'WP_Error')) {
            lsdd_generate_log('oyindonesia', LSDD_OYINDONESIA_PATH, 'Error :: ' . json_encode($raw->errors));
        }

        $response = json_decode(wp_remote_retrieve_body($raw), true);

        // Response Exists
        if (!empty($response)) {

            // Updating Report
            $db = new DB\Reports_Repository();
            $db->update(array('reference' => $response['sessionID']), $report_id);

            // Send Redirect Url
            $report['response'] = $response['url'];

            lsdd_generate_log('oyindonesia', LSDD_OYINDONESIA_PATH, 'Generate Session Ipaymu :: ' . $response['sessionID']);
        } else {
            lsdd_generate_log('oyindonesia', LSDD_OYINDONESIA_PATH, 'Failed Generate Session Ipaymu');

            $report['response'] = "failed";
            $report['status'] = "failed";
        }

        return $report;
    }
}
