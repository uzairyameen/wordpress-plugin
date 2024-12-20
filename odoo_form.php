<?php
function odoo_form_shortcode() {
    ob_start(); ?>
    <div class="odoo-form-container">
        <form id="odoo_form" method="POST" action="">

            <label for="name">Name:</label>
            <input type="text" id="name" name="name" required><br>

            <label for="phone">Phone Number:</label>
            <input type="text" id="phone" name="phone" required><br>

            <label for="email">Email:</label>
            <input type="email" id="email" name="email"><br>

            <label for="serial">Product Serial:</label>
            <input type="text" id="serial" name="serial" required><br>

            <input type="submit" name="submit_form" value="Submit">
        </form>

        <div id="form-error" style="color: red; display: none;">Please fill out all required fields!</div>
    </div>

    <script type="text/javascript">
    jQuery(document).ready(function($) {
        $('#odoo_form').submit(function(e) {
            e.preventDefault(); // Prevent the default form submission

            // Get the geolocation data
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(position) {
                    var formData = {
                        action: 'odoo_form_submit',
                        odoo_form_submitted: 1,
                        name: $('#name').val(),
                        phone: $('#phone').val(),
                        email: $('#email').val(),
                        serial: $('#serial').val(),
                        latitude: position.coords.latitude,  // Pass latitude
                        longitude: position.coords.longitude  // Pass longitude
                    };

                    // Perform the AJAX request
                    $.post(ajaxurl, formData, function(response) {
                        if (response.success) {
                            alert(response.data.message); // Display the appropriate message
                        } else {
                            alert(response.data.message); // Display the error message when serial is not found
                        }
                    });
                }, function(error) {
                    alert('Geolocation failed: ' + error.message); // Handle geolocation error
                });
            } else {
                alert("Geolocation is not supported by this browser.");
            }
        });
    });
</script>

    <?php
    return ob_get_clean();
}
add_shortcode('odoo_form', 'odoo_form_shortcode');

// Handle form submission on the backend
function handle_odoo_form_submission() {
    if (isset($_POST['odoo_form_submitted']) && $_POST['odoo_form_submitted'] == 1) {
        // Extract form data
        $name = sanitize_text_field($_POST['name']);
        $phone = sanitize_text_field($_POST['phone']);
        $email = sanitize_email($_POST['email']);
        $serial = sanitize_text_field($_POST['serial']);

        // Set search parameters for Odoo
        $searchParams = [['serial', '=', $serial]];  // Corrected field name to 'serial'

        // Call the search function in Odoo
        $result = search_and_create_in_odoo($searchParams, []); // Ensure the parameters are passed

        // Check the result and prepare the message to return
        if ($result['success']) {
            wp_send_json_success([
                'message' => $result['message'],  // Message will include both success and record creation details
            ]);
        } else {
            wp_send_json_success([
                'message' => 'Unfortunately, this serial number does not exist.',
            ]);
        }
    }
}
add_action('wp_ajax_odoo_form_submit', 'handle_odoo_form_submission');
add_action('wp_ajax_nopriv_odoo_form_submit', 'handle_odoo_form_submission');