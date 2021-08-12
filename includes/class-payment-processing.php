<?php

/**
 * Handling Notification from Gateway
 * Automatic Confirmation
 */

use LSDDonation\DB;

if (!defined('ABSPATH')) exit;

if (!class_exists('OYIndonesia_Processing') && class_exists('LSDDonation\Payments\Payment_Template_Method')) {

    class OYIndonesia_Processing
    {
        public static function load()
        {
            $processing = new self();
            add_filter('lsddonation/payment/gateway/oyindonesia', [$processing, 'oyindonesia_payment_request'], 10, 2);
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

    OYIndonesia_Processing::load();
}
