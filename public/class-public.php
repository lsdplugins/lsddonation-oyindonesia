<?php

namespace LSDDonation\OYIndonesia;



if (!defined('ABSPATH')) exit;

class Frontend
{
    /**
     * Register the admin page class with all the appropriate WordPress hooks.
     *
     * @param Options $options
     */
    public static function register()
    {
        $public = new self();

        add_action('wp_enqueue_scripts', [$public, 'enqueue_styles']);
        add_action('wp_enqueue_scripts', [$public, 'enquene_scripts']);
    }

    public function enquene_scripts()
    {
        wp_enqueue_script('lsdd-oyindonesia', LSDD_OYINDONESIA_URL . 'public/js/oyindonesia.js', array('jquery'), LSDD_OYINDONESIA_VERSION, false);
    }

    public function enqueue_styles()
    {
    }
}
