<?php
require_once(plugin_dir_path(__FILE__) . '../../../wp-load.php');
require_once(plugin_dir_path(__FILE__) . 'vendor/autoload.php');

use GuzzleHttp\Client;

// Odoo configuration
$odoo_url = 'https://talluq17.maxronlubricants.com';
$database = 'talluqdb';
$username = 'uzair@teamredge.com';
$password = 'abcd1234';

$client = new Client();
$url = $odoo_url . '/jsonrpc';

/**
 * Authenticate with Odoo
 * 
 * @return mixed User ID on success, false on failure
 */
function authenticate_odoo() {
    global $client, $url, $database, $username, $password;

    try {
        $response = $client->post($url, [
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'jsonrpc' => '2.0',
                'method'  => 'call',
                'params'  => [
                    'service' => 'common',
                    'method'  => 'authenticate',
                    'args'    => [$database, $username, $password, []],
                ],
                'id' => 1,
            ],
        ]);

        $data = json_decode($response->getBody(), true);

        if (!empty($data['result'])) {
            error_log('Odoo Authentication successful: UID = ' . $data['result']);
            return $data['result'];
        }

        error_log('Odoo Authentication failed.');
        return false;
    } catch (Exception $e) {
        error_log('Authentication Error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Search and create in Odoo
 * 
 * @param array $searchParams
 * @param array $createParams
 * @return array
 */
function search_and_create_in_odoo($searchParams, $createParams) {
    global $client, $url, $database, $username, $password;

    $uid = authenticate_odoo();
    if (!$uid) {
        return ['success' => false, 'message' => 'Authentication failed.'];
    }

    try {
        // Search for serial number
        $searchResponse = $client->post($url, [
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'jsonrpc' => '2.0',
                'method'  => 'call',
                'params'  => [
                    'service' => 'object',
                    'method'  => 'execute_kw',
                    'args'    => [
                        $database,
                        $uid,
                        $password,
                        'genuine.check', // Model name
                        'search_read',
                        [$searchParams], // Serial number should be part of search parameters
                        ['fields' => ['id', 'serial', 'product_id']], // Ensure these fields exist in your Odoo model
                    ],
                ],
                'id' => 2,
            ],
        ]);

        $searchData = json_decode($searchResponse->getBody(), true);

        if (!empty($searchData['result'])) {
            // Serial found
            $productId = $searchData['result'][0]['product_id'][0]; // Assuming product_id is a Many2one field
            $productName = ''; // Adjust as needed

            // Fetch product name from the 'product.product' model
            if ($productId) {
                $productResponse = $client->post($url, [
                    'headers' => [
                        'Content-Type' => 'application/json',
                    ],
                    'json' => [
                        'jsonrpc' => '2.0',
                        'method'  => 'call',
                        'params'  => [
                            'service' => 'object',
                            'method'  => 'execute_kw',
                            'args'    => [
                                $database,
                                $uid,
                                $password,
                                'product.product', // Model name for products
                                'read',
                                [[$productId]], // Fetch the product using its ID
                                ['fields' => ['name']], // We only need the name
                            ],
                        ],
                        'id' => 3,
                    ],
                ]);

                $productData = json_decode($productResponse->getBody(), true);
                if (!empty($productData['result'])) {
                    $productName = $productData['result'][0]['name'];
                }
            }

            // If serial found, update the longitude and latitude fields
            if (!empty($_POST['longitude']) && !empty($_POST['latitude'])) {
                // Update the record in Odoo with longitude and latitude values
                $updateParams = [
                    'consumption_longitude' => $_POST['longitude'],
                    'consumption_latitude'  => $_POST['latitude'],
                ];

                $updateResponse = $client->post($url, [
                    'headers' => [
                        'Content-Type' => 'application/json',
                    ],
                    'json' => [
                        'jsonrpc' => '2.0',
                        'method'  => 'call',
                        'params'  => [
                            'service' => 'object',
                            'method'  => 'execute_kw',
                            'args'    => [
                                $database,
                                $uid,
                                $password,
                                'genuine.check', // Model where you want to update the record
                                'write',
                                [$searchData['result'][0]['id'], $updateParams],
                            ],
                        ],
                    ],
                ]);

                // If update is successful
                return [
                    'success' => true,
                    'message' => "Your Product Serial {$searchData['result'][0]['serial']} for the Product {$productName} is Genuine.Thanks for purchasing!"
                ];
            }

            return [
                'success' => true,
                'message' => "Your Product Serial {$searchData['result'][0]['serial']} for the Product {$productName} is Genuine. No location data provided."
            ];
        }

        return ['success' => false, 'message' => 'Serial number not found.'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

