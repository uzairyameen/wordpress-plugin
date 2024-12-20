<?php
/**
 * Plugin Name:  Odoo Plugin
 * Plugin URI:  https://example.com/my-first-plugin
 * Description: This plugin will handle the Odoo integration.
 * Version:     1.0
 * Author:      Uzair
 * Author URI:  https://example.com
 */

// Prevent direct access to the file
if (!defined('ABSPATH')) {
    die('Cannot access this file directly.');
}

require_once plugin_dir_path(__FILE__) . 'odoo_form.php';
require_once plugin_dir_path(__FILE__) . 'odoo_authentication.php';

// Add a menu item for the plugin
add_action('admin_menu', 'my_plugin_add_menu');

function my_plugin_add_menu() {
    add_menu_page(
        'Plugin Settings',           // Page title
        'Odoo',                      // Menu title
        'manage_options',            // Capability
        'my-plugin-settings',        // Menu slug
        'my_plugin_settings_page',   // Callback function
        'dashicons-admin-generic',   // Icon
        100                          // Position
    );
}

// Render the settings page
function my_plugin_settings_page() {
    ?>
    <div class="wrap">
        <h1>Odoo Backend Form</h1>

        <!-- The Form inside a div -->
        <div id="odoo-form-container" style="max-width: 600px; padding: 20px; background-color: #f4f4f4; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1);">
            <h2>Submit Odoo Integration Details</h2>
            <form id="my-plugin-form" method="post" action="">

                <label for="my_plugin_url" style="display:block; margin-bottom: 8px;">Odoo URL</label>
                <input type="text" id="my_plugin_url" name="my_plugin_url" style="width: 100%; padding: 8px; margin-bottom: 12px;">

                <label for="my_plugin_api" style="display:block; margin-bottom: 8px;">Odoo API Key</label>
                <input type="text" id="my_plugin_api" name="my_plugin_api" style="width: 100%; padding: 8px; margin-bottom: 12px;">

                <input type="submit" name="submit_settings" value="Submit" style="background-color: #0073aa; color: white; padding: 10px 15px; border: none; cursor: pointer;">
                <div id="form-error" style="color: red; display: none; margin-top: 10px;">Please fill out all required fields!</div>
            </form>
        </div>

        <!-- Display success message after form submission -->
        <?php
        if (isset($_POST['submit_settings'])) {
            // Sanitize and save the fields
            update_option('my_plugin_url', sanitize_text_field($_POST['my_plugin_url']));
            update_option('my_plugin_api', sanitize_text_field($_POST['my_plugin_api']));

            echo '<div class="notice notice-success is-dismissible"><p>Settings saved successfully!</p></div>';
        }
        ?>

        <style>
            #odoo-form-container input[type="text"] {
                border: 1px solid #ddd;
                border-radius: 4px;
            }
            #odoo-form-container input[type="submit"] {
                background-color: #0073aa;
                color: white;
                border-radius: 4px;
                border: none;
                cursor: pointer;
            }
            #odoo-form-container input[type="submit"]:hover {
                background-color: #005a8d;
            }
        </style>

        <script>
            jQuery(document).ready(function ($) {
                // Attach the submit event to the form
                $('#my-plugin-form').on('submit', function (e) {
                    const url = $('#my_plugin_url').val().trim();
                    const api = $('#my_plugin_api').val().trim();

                    // Check if the URL is empty
                    if (!url || !api) {
                        $('#form-error').show(); // Show the error message
                        e.preventDefault(); // Prevent form submission
                    } else {
                        $('#form-error').hide(); // Hide the error message
                    }
                });
            });
        </script>
    </div>

    <?php
}

// Register the AJAX action for both logged-in and non-logged-in users
add_action('wp_ajax_odoo_form_submit', 'odoo_form_submit_handler');
add_action('wp_ajax_nopriv_odoo_form_submit', 'odoo_form_submit_handler');

// Handle the form submission via AJAX
function odoo_form_submit_handler() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = sanitize_text_field($_POST['name']);
        $phone = sanitize_text_field($_POST['phone']);
        $email = sanitize_email($_POST['email']);
        $serial = sanitize_text_field($_POST['serial']); // Corrected field name to 'serial'

        require_once(plugin_dir_path(__FILE__) . 'odoo_authentication.php');

        // Call the function to search the serial number and create a record in Odoo
        $result = search_and_create_in_odoo(
            [['serial', '=', $serial]], // Corrected field name to 'serial'
            ['name' => $name, 'phone' => $phone, 'email' => $email, 'serial' => $serial] // Corrected field name to 'serial'
        );

        // Return the result message as a JSON response
        wp_send_json_success(['message' => $result['message']]);
    }
}

// Add localized script to make sure ajaxurl is available in JavaScript
function my_plugin_enqueue_scripts() {
    wp_enqueue_script('jquery');  // Ensure jQuery is loaded
    wp_localize_script('jquery', 'ajaxurl', admin_url('admin-ajax.php'));
}
add_action('wp_enqueue_scripts', 'my_plugin_enqueue_scripts');
?>

