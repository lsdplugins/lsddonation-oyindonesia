<?php

/**
 * @lsddonation extension
 * Plugin Name:       LSDDonasi - OY Indonesia
 * Plugin URI:        https://lsdplugins.com/product/lsddonasi-oyindonesia
 * Description:       Ekstensi OYIndonesia untuk LSDDonasi | Indonesia
 * Version:           1.0.0
 * Author:            LSD Plugins
 * Author URI:        https://lsdplugins.com/lsddonasi-oyindonesia/
 * License:           GPL-3.0+
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain:       lsddonation-oyindonesia
 * Domain Path:       /languages
 *
 * Build: Development
 */

// If this file is accessed directory, then abort.
if (!defined('ABSPATH')) exit;

// Define Constant
defined('LSDD_OYINDONESIA_REQUIRED') or define('LSDD_OYINDONESIA_REQUIRED', '4.1.0');
defined('LSDD_OYINDONESIA_VERSION') or define('LSDD_OYINDONESIA_VERSION', '1.0.0');
defined('LSDD_OYINDONESIA_BASE') or define('LSDD_OYINDONESIA_BASE', plugin_basename(__FILE__));
defined('LSDD_OYINDONESIA_PATH') or define('LSDD_OYINDONESIA_PATH', plugin_dir_path(__FILE__));
defined('LSDD_OYINDONESIA_URL') or define('LSDD_OYINDONESIA_URL', plugin_dir_url(__FILE__));

/**
 * Dependency Checking
 *
 * @return void
 */
function lsdd_oyindonesia_dependency()
{
    $core_active = true;
    $core_version = true;

    // Checking Core Active
    if (is_admin() && current_user_can('activate_plugins') && !is_plugin_active('lsddonation/lsddonation.php')) {
        add_action('admin_notices', function () {
            // %s: 4.0.3
            $message = sprintf(esc_html__('LSDDonasi versi %s+ dibutuhkan. tolong aktifkan plugin utama sebelum ekstensi LSDDonasi - OY Indonesia.', 'lsddonation-oyindonesia'), LSDD_OYINDONESIA_REQUIRED);
            $html_message = sprintf('<div class="error">%s</div>', wpautop($message));
            echo wp_kses_post($html_message);
        });
        $core_active = false;
    }

    // Checking Core Version
    if (defined('LSDD_PATH')) {
        $core_plugin = get_plugin_data(LSDD_PATH . 'lsddonation.php');
        if (!version_compare($core_plugin['Version'], LSDD_OYINDONESIA_REQUIRED, '>=')) {
            add_action('admin_notices', 'lsdd_oyindonesia_fail_version');
            $core_version = false;
        }
    } else {
        add_action('admin_notices', 'lsdd_oyindonesia_fail_version');
        $core_version = false;
    }

    // Deactivate Extension
    if (!$core_version || !$core_active) {
        deactivate_plugins(plugin_basename(__FILE__));
        if (isset($_GET['activate'])) {
            unset($_GET['activate']);
        }
    }
}
add_action('admin_init', 'lsdd_oyindonesia_dependency');

// Acceptable -> Run Extension
$active_plugins = apply_filters('active_plugins', get_option('active_plugins'));
if (in_array('lsddonation/lsddonation.php', $active_plugins)) {
    require_once LSDD_OYINDONESIA_PATH . 'includes/plugin.php';
}

/**
 * LSDDonation admin notice for minimum LSDDonation version.
 * Warning when the site doesn't have the minimum required LSDDonation version.
 *
 * @return void
 */
function lsdd_oyindonesia_fail_version()
{
    $message = sprintf(esc_html__('LSDDonasi - OY Indonesia membutuhkan LSDDOnasi versi %s+. Karena kamu menggunakan versi lama, ekstensi ini tidak dapat berjalan.', 'lsddonation-oyindonesia'), LSDD_OYINDONESIA_REQUIRED);
    $html_message = sprintf('<div class="error">%s</div>', wpautop($message));
    echo wp_kses_post($html_message);
}
