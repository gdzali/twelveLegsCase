<?php
/*
Plugin Name: Custom WooCommerce Checkout
Description: Customizes WooCommerce checkout by validating postal codes.
Version: 1.0
Author: Ali G.
*/

class Custom_WooCommerce_Checkout {
    public function __construct() {
        add_action('admin_menu', array($this, 'add_options_page'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('woocommerce_checkout_process', array($this, 'validate_postal_codes'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_custom_wc_get_allowed_postcodes', array($this, 'get_allowed_postcodes'));
        add_action('wp_ajax_nopriv_custom_wc_get_allowed_postcodes', array($this, 'get_allowed_postcodes'));
    }

    // Add custom options page
    public function add_options_page() {
        add_options_page(
            'Custom WooCommerce Settings',
            'Custom WooCommerce',
            'manage_options',
            'custom-woocommerce-settings',
            array($this, 'render_options_page')
        );
    }

    // Render options page content
    public function render_options_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('custom_wc_settings');
                do_settings_sections('custom_wc_settings');
                submit_button('Save Settings');
                ?>
            </form>
        </div>
        <?php
    }

    // Register plugin settings
    public function register_settings() {
        register_setting('custom_wc_settings', 'custom_wc_postal_codes');
        add_settings_section('custom_wc_section', 'Postal Codes', array($this, 'section_callback'), 'custom_wc_settings');
        add_settings_field('custom_wc_postal_codes', 'Enter Comma-separated Postal Codes:', array($this, 'postal_codes_callback'), 'custom_wc_settings', 'custom_wc_section');
    }

    // Section callback
    public function section_callback() {
        echo '<p>Enter the list of postal codes separated by commas.</p>';
    }

    // Postal codes callback
    public function postal_codes_callback() {
        $postal_codes = get_option('custom_wc_postal_codes');
        echo '<input type="text" name="custom_wc_postal_codes" value="' . esc_attr($postal_codes) . '" />';
    }

    // Validate postal codes
    public function validate_postal_codes() {
        $postal_codes = get_option('custom_wc_postal_codes');

        if (!empty($postal_codes)) {
            $entered_postcode = isset($_POST['billing_postcode']) ? $_POST['billing_postcode'] : '';

            $allowed_postcodes = array_map('trim', explode(',', $postal_codes));

            if (!in_array($entered_postcode, $allowed_postcodes)) {
                wc_add_notice('Sorry, we do not deliver to this location.', 'error');
            }
        }
    }

    // Enqueue JavaScript for frontend validation
    public function enqueue_scripts() {
        wp_enqueue_script('custom-wc-frontend-validation', plugin_dir_url(__FILE__) . 'js/frontend-validation.js', array('jquery'), '1.0', true);
        wp_localize_script('custom-wc-frontend-validation', 'custom_wc_frontend_validation_params', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('custom_wc_ajax_nonce')
        ));
    }

    // AJAX handler to get allowed postcodes
    public function get_allowed_postcodes() {
        // Verify nonce
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'custom_wc_ajax_nonce' ) ) {
            wp_send_json_error( 'Invalid nonce' );
        }

        $postal_codes = get_option('custom_wc_postal_codes');
        $allowed_postcodes = array_map('trim', explode(',', $postal_codes));
        
        wp_send_json_success(array('allowed_postcodes' => $allowed_postcodes));
    }
}

// Initialize the plugin
new Custom_WooCommerce_Checkout();
