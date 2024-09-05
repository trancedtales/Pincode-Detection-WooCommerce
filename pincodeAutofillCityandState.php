<?php

// Enqueue jQuery and the custom script in the footer
add_action('wp_enqueue_scripts', 'enqueue_pincode_script');
function enqueue_pincode_script() {
    if (is_checkout()) {
        wp_enqueue_script('jquery');
        // Inline script instead of a separate JS file
        wp_add_inline_script('jquery', '
            jQuery(document).ready(function($) {
                $("#billing_postcode").on("change", function() {
                    var pincode = $(this).val();

                    if (pincode.length === 6) { // Indian Pincode length is 6 digits
                        $.ajax({
                            type: "POST",
                            url: wc_checkout_params.ajax_url,
                            data: {
                                action: "get_city_state",
                                pincode: pincode,
                            },
                            success: function(response) {
                                if (response.success) {
                                    $("#billing_city").val(response.data.city);
                                    var stateField = $("#billing_state");
                                    
                                    // Check if the state field is a dropdown or a text input
                                    if (stateField.is("select")) {
                                        stateField.val(response.data.state).trigger("change");
                                    } else {
                                        stateField.val(response.data.state);
                                    }
                                } else {
                                    console.log(response.data.message);
                                }
                            }
                        });
                    }
                });
            });
        ');
    }
}

// State mapping function
function get_state_code($state_name) {
    $states = array(
        'Andhra Pradesh' => 'AP',
        'Arunachal Pradesh' => 'AR',
        'Assam' => 'AS',
        'Bihar' => 'BR',
        'Chhattisgarh' => 'CT',
        'Goa' => 'GA',
        'Gujarat' => 'GJ',
        'Haryana' => 'HR',
        'Himachal Pradesh' => 'HP',
        'Jharkhand' => 'JH',
        'Karnataka' => 'KA',
        'Kerala' => 'KL',
        'Madhya Pradesh' => 'MP',
        'Maharashtra' => 'MH',
        'Manipur' => 'MN',
        'Meghalaya' => 'ML',
        'Mizoram' => 'MZ',
        'Nagaland' => 'NL',
        'Odisha' => 'OR',
        'Punjab' => 'PB',
        'Rajasthan' => 'RJ',
        'Sikkim' => 'SK',
        'Tamil Nadu' => 'TN',
        'Telangana' => 'TG',
        'Tripura' => 'TR',
        'Uttar Pradesh' => 'UP',
        'Uttarakhand' => 'UK',
        'West Bengal' => 'WB',
        'Andaman and Nicobar Islands' => 'AN',
        'Chandigarh' => 'CH',
        'Dadra and Nagar Haveli and Daman and Diu' => 'DN',
        'Lakshadweep' => 'LD',
        'Delhi' => 'DL',
        'Puducherry' => 'PY',
        'Ladakh' => 'LA',
        'Jammu and Kashmir' => 'JK'
    );

    return isset($states[$state_name]) ? $states[$state_name] : '';
}

// AJAX handler for getting city and state based on pincode
add_action('wp_ajax_get_city_state', 'get_city_state');
add_action('wp_ajax_nopriv_get_city_state', 'get_city_state');

function get_city_state() {
    $pincode = sanitize_text_field($_POST['pincode']);

    // Replace with your API Key and URL
    $api_url = "https://api.postalpincode.in/pincode/" . $pincode;

    $response = wp_remote_get($api_url);

    if (is_wp_error($response)) {
        wp_send_json_error(['message' => 'Unable to retrieve data']);
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body);

    if ($data[0]->Status === 'Success') {
        $city = $data[0]->PostOffice[0]->District;
        $state_name = $data[0]->PostOffice[0]->State;

        // Get WooCommerce state code from state name
        $state_code = get_state_code($state_name);

        if ($state_code) {
            wp_send_json_success(['city' => $city, 'state' => $state_code]);
        } else {
            wp_send_json_error(['message' => 'State not found in WooCommerce']);
        }
    } else {
        wp_send_json_error(['message' => 'Invalid Pincode']);
    }

    wp_die();
}


>