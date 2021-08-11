<?php

/**
 * Setter Payment Confirmation
 *
 * @return void
 */
function lsdd_oyindonesia_set_confirmation()
{
    $payments = get_option('lsdd_payment_settings');
    $payments['oyindonesia']['confirmation'] = 'automatic';
    update_option('lsdd_payment_settings', $payments);
}
add_action('lsddonation/admin/tabs/payments', 'lsdd_oyindonesia_set_confirmation');
add_action('lsddonation/payment/channel', 'lsdd_oyindonesia_set_confirmation', 999);

/**
 * Get Payment Status
 *
 * @return void
 */
function lsdd_oyindonesia_get_status()
{
    $status = get_option('lsdd_payment_status');
    if (!isset($status['oyindonesia'])) {
        $status['oyindonesia'] = 'off';
    }
    return $status['oyindonesia'] == 'on' ? 'on' : 'off';
}

/**
 * Get Payment Settings
 *
 * @param string $item
 * @return string
 */
function lsdd_oyindonesia_get_settings(string $item)
{
    $payment = lsdd_payment_settings();
    switch ($item) {
        case 'production':
            return isset($payment['oyindonesia']) ? esc_attr($payment['oyindonesia']['production']) : null;
            break;
        case 'apikey':
            return isset($payment['oyindonesia']) ? esc_attr($payment['oyindonesia']['apikey']) : null;
            break;
    }
}
