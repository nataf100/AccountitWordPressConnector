<?php

/**
 * Settings page for AccountIT WordPress Connector
 *
 * @package AccountitWordPressConnector
 */

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

add_action('wp_ajax_my_action', 'my_ajax_action_function');

function my_ajax_action_function(){

    $reponse = array();
    $env = $_POST['env'];
    $prefix = $_POST['company'];
    $api_code = $_POST['appkey'];
    $type = $_POST['type'];
    /*
    $url = AccountAPI::GetEnvUrl($env);
    $client = new nusoap_client($url."/api_ws.php?wsdl"); // Create a instance for nusoap client
    if($type == 1) //get caccounts
    {
        $response_call = $client->call('get_accounts', array("company_code" => $prefix, "app_key"=>$api_code, "account"=>0, "type"=>0));
        if($response_call && count($response_call) > 0)
        {
            $response['response'][] = ["num"=>$response_call[0]["num"], "name"=>$response_call[0]["company"]];
        }
    }
    else if($type == 2) //get items
    {
        $response_call = $client->call('get_items', array("company_code" => $prefix, "app_key"=>$api_code, "num"=>0));
        if($response_call && count($response_call) > 0)
        {
            $response['response'][] = ["num"=>$response_call[0]["cat_num"], "name"=>$response_call[0]["description"]];
        }
    }
    else
    {
        $response['response'] = "Invalid action";
        header( "Content-Type: application/json" );
        echo json_encode($response);

        //Don't forget to always exit in the ajax function.
        exit();
    }
  
    header( "Content-Type: application/json" );
    echo json_encode($response);
    */
    //Don't forget to always exit in the ajax function.
    exit();

}

/**
 * Export WooCommerce products in standard WooCommerce CSV format
 */
function export_products_to_accountit() {
    // Verify nonce and permissions
    if (!wp_verify_nonce($_REQUEST['security'], 'accountit_export_nonce') || !current_user_can('manage_options')) {
        wp_send_json_error(__('Permission denied', 'woo-account-it-text-domain'));
    }

    // Get all products
    $args = array(
        'status' => 'publish',
        'limit' => -1,
        'return' => 'objects',
    );
    $products = wc_get_products($args);

    if (empty($products)) {
        wp_send_json_error(__('No products found', 'woo-account-it-text-domain'));
    }

    // Prepare CSV headers (matching WooCommerce export format)
    $headers = array(
        'ID',
        'Type',
        'SKU',
        'Name',
        'Published',
        'Is featured?',
        'Visibility in catalog',
        'Short description',
        'Description',
        'Date sale price starts',
        'Date sale price ends',
        'Tax status',
        'Tax class',
        'In stock?',
        'Stock',
        'Backorders allowed?',
        'Sold individually?',
        'Weight (kg)',
        'Length (cm)',
        'Width (cm)',
        'Height (cm)',
        'Allow customer reviews?',
        'Purchase note',
        'Sale price',
        'Regular price',
        'Categories',
        'Tags',
        'Shipping class',
        'Images',
        'Download limit',
        'Download expiry days',
        'Parent',
        'Grouped products',
        'Upsells',
        'Cross-sells',
        'External URL',
        'Button text',
        'Position'
    );

    // Prepare data rows
    $data = array();
    foreach ($products as $product) {
        $data[] = array(
            $product->get_id(),
            $product->get_type(),
            $product->get_sku(),
            $product->get_name(),
            $product->get_status() === 'publish' ? 1 : 0,
            $product->get_featured() ? 1 : 0,
            $product->get_catalog_visibility(),
            $product->get_short_description(),
            $product->get_description(),
            $product->get_date_on_sale_from(),
            $product->get_date_on_sale_to(),
            $product->get_tax_status(),
            $product->get_tax_class(),
            $product->is_in_stock() ? 1 : 0,
            $product->get_stock_quantity(),
            $product->get_backorders(),
            $product->is_sold_individually() ? 1 : 0,
            $product->get_weight(),
            $product->get_length(),
            $product->get_width(),
            $product->get_height(),
            $product->get_reviews_allowed() ? 1 : 0,
            $product->get_purchase_note(),
            $product->get_sale_price(),
            $product->get_regular_price(),
            implode(', ', wp_list_pluck($product->get_category_ids(), 'name')),
            implode(', ', wp_list_pluck($product->get_tag_ids(), 'name')),
            $product->get_shipping_class(),
            implode(', ', $product->get_gallery_image_ids()),
            $product->get_download_limit(),
            $product->get_download_expiry(),
            $product->get_parent_id(),
            implode(', ', $product->get_children()),
            implode(', ', $product->get_upsell_ids()),
            implode(', ', $product->get_cross_sell_ids()),
            $product->get_type() === 'external' ? $product->get_product_url() : '',
            $product->get_type() === 'external' ? $product->get_button_text() : '',
            $product->get_menu_order()
        );
    }

    // Generate CSV file
    $filename = 'woocommerce-products-export-' . date('Y-m-d') . '.csv';
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    // Add BOM to fix UTF-8 in Excel
    fwrite($output, "\xEF\xBB\xBF");
    
    // Write headers
    fputcsv($output, $headers);
    
    // Write data
    foreach ($data as $row) {
        fputcsv($output, $row);
    }
    
    fclose($output);
    exit;
}
add_action('wp_ajax_export_products_to_accountit', 'export_products_to_accountit');


function import_accountit_items_to_woocommerce($sync_type = 'full') {
    // Get API credentials from options
    $username = get_option('acc_it_username');
    $appkey = get_option('acc_it_appkey');
    $company = get_option('acc_it_company');
    $env = get_option('acc_it_env');
    
    $api_user_name = get_option('acc_it_username');
    $api_app_key = get_option('acc_it_appkey');
    $api_company_key = get_option('acc_it_company');
    if (empty($username) || empty($appkey) || empty($company)) {
        // Add error notice if credentials are missing
        add_action('admin_notices', function() {
            ?>
            <div class="notice notice-error is-dismissible">
                <p><?php _e('Please configure AccountIT API credentials in the settings.', 'woo-account-it-text-domain'); ?></p>
            </div>
            <?php
        });
        return false;
    }
    $accit = new AccountAPI($api_user_name, $api_app_key, $api_company_key);

    
    try {
        // Get items from AccountIT
        $response = $accit->getItemData(($sync_type == 'full') ? 0 : 100); // Example: 0 for all, 100 for recent
       
       
        if (is_wp_error($response)) {
            throw new Exception('Failed to retrieve data from AccountIT: ' . $response->get_error_message());
        }
        
        
        $items = $response;
        if (!is_array($items)) {
            throw new Exception('Invalid API response format');
        }
        
        $imported_count = 0;
        $updated_count = 0;
        
        

        foreach ($response as $item) {
            if (empty($item['num']) || empty($item['name'])) continue;
            
            $product_id = wc_get_product_id_by_sku($item['num']);
            
            // For partial sync, skip existing products
            if ($sync_type == 'partial' && $product_id) {
                continue;
            }
            
            if ($product_id) {
                $product = wc_get_product($product_id);
                $updated_count++;
            } else {
                $product = new WC_Product();
                $imported_count++;
            }
            
            // Set product data
            $product->set_name($item['name']);
            $product->set_sku($item['num']);
            $product->set_status('publish');
            
            if (isset($item['defprice']) && is_numeric($item['defprice'])) {
                $product->set_regular_price($item['defprice']);
            }
            
            if (!empty($item['description'])) {
                $product->set_description($item['description']);
            }
            
            if (isset($item['unit']) && is_numeric($item['unit'])) {
                $product->set_manage_stock(true);
                $product->set_stock_quantity($item['unit']);
            }
            
            $product->save();
        }
        
        return array(
            'success' => true,
            'imported' => $imported_count,
            'updated' => $updated_count
        );
        
    } catch (Exception $e) {
        return new WP_Error('import_error', $e->getMessage());
    }
}

add_action('wp_ajax_accountit_sync_items', 'handle_accountit_sync_items_ajax');

function handle_accountit_sync_items_ajax() {
    check_ajax_referer('accountit_sync_nonce', 'security');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized', 403);
    }
    
    $sync_type = isset($_POST['sync_type']) ? sanitize_text_field($_POST['sync_type']) : 'full';
    $result = import_accountit_items_to_woocommerce($sync_type);
    
    if (is_wp_error($result)) {
        wp_send_json_error($result->get_error_message());
    } else {
        wp_send_json_success(array(
            'imported' => $result['imported'],
            'updated' => $result['updated']
        ));
    }
}

function woo_tracker_settings_details() {

     $status = null;
// Get current tab
        $current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'api_settings';
     if( isset($_POST['save-credence']) && !empty($_POST['username']) && !empty($_POST['appkey']) && !empty($_POST['company'])  && !empty($_POST['doc_type']) 
     && is_numeric($_POST['doc_type']) && is_numeric($_POST['account_id']) && is_numeric($_POST['item_id'])):

     

        $status = update_option( 'acc_it_username', $_POST['username'] );
        $status = update_option( 'acc_it_appkey', $_POST['appkey'] );
        $status = update_option( 'acc_it_company', $_POST['company'] );
        $status = update_option( 'acc_it_doc_type', $_POST['doc_type'] );
        $status = update_option( 'acc_it_account_id', $_POST['account_id'] );
        $status = update_option( 'acc_it_item_id', $_POST['item_id'] );
        $status = update_option( 'acc_it_manage_tax', $_POST['manage_tax'] );
        $status = update_option( 'acc_email_client', $_POST['email_client'] );
        //1.3.1
        $status = update_option( 'acc_auto_create_on_new_order', $_POST['auto_create_on_new_order'] );
        $status = update_option( 'acc_auto_create_on_new_order_triger', $_POST['auto_create_on_new_order_triger'] );
        $status = update_option( 'acc_it_env', $_POST['env'] );
        //1.5.1
        $status = update_option( 'acc_it_update_inventory', $_POST['update_inventory'] );
        
    endif;
?>



<script type="text/javascript" >
jQuery(document).ready(function($) {

    $("#refresh_account_id").click(function(){
            var env_id = $("#env").val();
            var company_id = $("#company").val();
            var appkey_id = $("#appkey").val();
            jQuery.ajax({
                type: "POST",
                url: ajaxurl,
                data: { action: 'my_action' , env: env_id, company: company_id, appkey: appkey_id, type: 1 }
                      }).done(function( msg ) {
                          if(msg && msg.response && msg.response.length > 0)
                              $("#account_id").val( msg.response[0].num);
                      });
        });
        
        $("#refresh_item_id").click(function(){
            var env_id = $("#env").val();
            var company_id = $("#company").val();
            var appkey_id = $("#appkey").val();
            jQuery.ajax({
                type: "POST",
                url: ajaxurl,
                data: { action: 'my_action' , env: env_id, company: company_id, appkey: appkey_id, type: 2 }
                      }).done(function( msg ) {
                             if(msg && msg.response && msg.response.length > 0)
                                 $("#item_id").val( msg.response[0].num);
                     });
        });

        // New AJAX for import/export
    $('#start-sync').click(function() {
        var sync_type = $('#sync_type').val();
        var $status = $('#sync-status');
        var $progress = $('#sync-progress');
        var $progressBar = $('.progress-bar');
        var $syncDetails = $('.sync-details');

        // Reset UI
        $status.html('<span class="spinner is-active"></span> Starting sync...').css('color', 'blue');
        $progress.show();
        $progressBar.css('width', '0%');
        $syncDetails.text('');
        
        // Disable button during sync
        $(this).prop('disabled', true);
        
        // Start time tracking
        var startTime = new Date();

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'accountit_sync_items',
                sync_type: sync_type,
                security: '<?php echo wp_create_nonce("accountit_sync_nonce"); ?>'
            },
            xhr: function() {
                var xhr = new window.XMLHttpRequest();
                xhr.addEventListener("progress", function(evt) {
                    if (evt.lengthComputable) {
                        var percentComplete = evt.loaded / evt.total;
                        var currentWidth = Math.round(percentComplete * 100);
                        $progressBar.css('width', currentWidth + '%');
                        
                        // Calculate estimated time remaining
                        var currentTime = new Date();
                        var elapsed = (currentTime - startTime) / 1000; // in seconds
                        var estimatedTotal = elapsed / percentComplete;
                        var remaining = Math.round(estimatedTotal - elapsed);
                        
                        var statusText = 'Processing: ' + currentWidth + '% complete';
                        if (remaining > 0) {
                            statusText += ' - About ' + remaining + 's remaining';
                        }
                        
                        $syncDetails.text(statusText);
                    }
                }, false);
                return xhr;
            },
            success: function(response) {
                if (response.success) {
                    $status.html('✅ Sync completed successfully!').css('color', 'green');
                    $syncDetails.html(
                        'Imported: <strong>' + response.data.imported + '</strong> items<br>' +
                        'Updated: <strong>' + response.data.updated + '</strong> items<br>' +
                        '<small>Total time: ' + Math.round((new Date() - startTime)/1000) + 's</small>'
                    );
                } else {
                    $status.html('❌ Sync failed: ' + response.data).css('color', 'red');
                }
            },
            error: function(xhr, status, error) {
                $status.html('❌ Sync error: ' + error).css('color', 'red');
            },
            complete: function() {
                $('#start-sync').prop('disabled', false);
            }
        });
    });
    
    $('#export-products').click(function() {
    var $btn = $(this);
    var $status = $('#export-status');
    
    $btn.prop('disabled', true);
    $status.html('<span class="spinner is-active"></span> Preparing export...').css('color', 'blue');
    
    $.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
            action: 'export_products_to_accountit',
            security: '<?php echo wp_create_nonce("accountit_export_nonce"); ?>'
        },
        // Important: Don't set dataType as we're handling raw response
        success: function(csvData, textStatus, xhr) {
            // Check if we actually got CSV data
            if (xhr.getResponseHeader('Content-Type').includes('text/csv')) {
                var blob = new Blob([csvData], {type: 'text/csv;charset=utf-8;'});
                var url = URL.createObjectURL(blob);
                var a = document.createElement('a');
                a.href = url;
                a.download = 'woocommerce-products-export-' + new Date().toISOString().slice(0,10) + '.csv';
                document.body.appendChild(a);
                a.click();
                setTimeout(function() {
                    document.body.removeChild(a);
                    window.URL.revokeObjectURL(url);
                }, 100);
                
                $status.html('✓ Export completed!').css('color', 'green');
            } else {
                // Handle unexpected response
                try {
                    var json = JSON.parse(csvData);
                    if (json.data) {
                        alert('Error: ' + json.data);
                        $status.html('✗ ' + json.data).css('color', 'red');
                    }
                } catch (e) {
                    alert('Unexpected response from server');
                    $status.html('Products not found').css('color', 'red');
                    console.error('Export response:', csvData);
                }
            }
        },
        error: function(xhr) {
            var errorMsg = 'Export failed';
            try {
                var json = JSON.parse(xhr.responseText);
                if (json.data) errorMsg = json.data;
            } catch (e) {
                errorMsg += ' (HTTP ' + xhr.status + ')';
            }
            alert(errorMsg);
            $status.html('✗ Export failed').css('color', 'red');
        },
        complete: function() {
            $btn.prop('disabled', false);
        }
    });
});
});
</script> 


<link rel="stylesheet" href="<?php echo AddFile::addFiles('assets/css', 'bootstrap.min', 'css', true); ?>">
<style media="screen">
.red{
    color:red;
}
.form-area
{
    background-color: #FAFAFA;
    padding: 10px 40px 60px;
    margin: 10px 0px 60px;
}
.refresh_btn
{
    float: left;
}

.nav-tab-wrapper {
    margin: 20px 0 15px;
    border-bottom: 1px solid #ccc;
}
.nav-tab {
    border: 1px solid #ccc;
    background: #e5e5e5;
    padding: 5px 15px;
    margin-right: 5px;
    text-decoration: none;
    color: #555;
    border-bottom: none;
}
.nav-tab-active {
    background: #f1f1f1;
    border-bottom: 1px solid #f1f1f1;
    margin-bottom: -1px;
    color: #000;
}
.import-export-section {
    padding: 20px;
    background: #fff;
    border: 1px solid #ddd;
    margin-top: 20px;
}
.progress {
    height: 20px;
    margin-bottom: 20px;
    overflow: hidden;
    background-color: #f5f5f5;
    border-radius: 4px;
    box-shadow: inset 0 1px 2px rgba(0,0,0,.1);
}
.progress-bar {
    float: left;
    width: 0;
    height: 100%;
    font-size: 12px;
    line-height: 20px;
    color: #fff;
    text-align: center;
    background-color: #337ab7;
    transition: width .6s ease;
}
.export-section {
    background: #fff;
    padding: 20px;
    margin-top: 20px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.export-options {
    padding: 10px;
    background: #f9f9f9;
    border-radius: 4px;
}

.btn-primary .dashicons {
    vertical-align: middle;
    margin-right: 5px;
}
</style>
<p></p>
<div class="container">
    <div class="col-md-8 col-md-push-1">
        <div class="form-area">
            <img class="img-responsive center-block" src="<?php echo AddFile::addFiles('assets/images', 'icon', 'png', true); ?>" alt="logo">
             <h3>AccountIT - Advanced Accounting System</h3>
             <small>Version: <?php echo VERSION; ?></small>
             <!-- Tab Navigation -->
                <h2 class="nav-tab-wrapper">
                    <a href="?page=views/settings.php&tab=api_settings" class="nav-tab <?php echo $current_tab == 'api_settings' ? 'nav-tab-active' : ''; ?>">API Settings</a>
                    <a href="?page=views/settings.php&tab=import_export" class="nav-tab <?php echo $current_tab == 'import_export' ? 'nav-tab-active' : ''; ?>">Import/Export</a>
                </h2>
        <?php if($current_tab == 'api_settings'): ?>
            <form action="" method="post">
                <br style="clear:both">
                <h4>API Settings</h4>
		<div class="form-group">
                    <label for="username">User Name</label>
					<input type="text" class="form-control" id="username" name="username" value="<?php echo get_option('acc_it_username'); ?>" placeholder="User Name" required>
		</div>
		<div class="form-group">
                    <label for="username">API Key</label>
					<input type="text" class="form-control" id="appkey" name="appkey" value="<?php echo get_option('acc_it_appkey'); ?>" placeholder="App Key" required>
		</div>
                <div class="form-group">
                    <label for="company">Company ID</label>
					<input type="text" class="form-control" id="company" name="company" value="<?php echo get_option('acc_it_company'); ?>" placeholder="Company Key" required>
		</div>
                <hr>
                <h4>Tax & Company Info</h4>
                <div class="form-group">
                    <label for="company">Generated Document Type: <small>(on order complete)</small></label>
                    
                    <select class="form-control" id="doc_type" name="doc_type" required>
                        <option value=""></option>
                        <option value="9" <?php selected( get_option('acc_it_doc_type'), 9 ); ?>>Invoice & Receipt</option>
                        <option value="8" <?php selected( get_option('acc_it_doc_type'), 8 ); ?>>Receipt</option>
                        <option value="3" <?php selected( get_option('acc_it_doc_type'), 3 ); ?>>Invoice</option>

                    </select>
                </div>
                <div class="row">
                    <div class="form-group col-lg-6">
                        <label for="company">Activation Trigger:</label>

                        <select class="form-control" id="auto_create_on_new_order" name="auto_create_on_new_order" required>
                            <option value="0" <?php selected( get_option('acc_auto_create_on_new_order'), 0 ); ?>>Disabled</option>
                            <option value="1" <?php selected( get_option('acc_auto_create_on_new_order'), 1 ); ?>>Run when order status changed to:</option>
                        </select>

                    </div>
                    <div class="form-group col-lg-6">
                        <label for="company">Select Trigger<small></small></label>
                        <?php $statuses = wc_get_order_statuses(); 
                        $op = get_option('acc_auto_create_on_new_order_triger');
                        $op = is_array($op) ? $op : array();
                        ?>

                        <select class="form-control" id="auto_create_on_new_order_triger" name="auto_create_on_new_order_triger[]" required multiple>
                            <?php foreach ( $statuses as $item_id => $item_data ):  ?>
                                <option value="<?=$item_id ?>" <?php echo in_array( $item_id, $op ) == "1"  ? "selected" : "";//selected( get_option('acc_auto_create_on_new_order_triger'), $item_id ); ?>><?=$item_id ?></option>
                            <?php endforeach; ?>

                        </select>
                        <small>(Select "wc-on-hold" to automatic create document for new orders)</small>
                    </div>
                </div>
                <div class="form-group">
                    <label for="company">Client (Buyer) Notification: <small>(on order complete)</small></label>
                    
                    <select class="form-control" id="email_client" name="email_client" required>
                        <option value="0">None</option>
                        <option value="1" <?php selected( get_option('acc_email_client'), 1 ); ?>>Email with PDF Attached</option>
                    </select>
                </div>
                <br>
                <div class="form-group">
                    <label for="company">AccountIT Client ID <small>(to which client account register the sales; please make sure this account exists in AccountIT)</small></label>
                    <input type="number" class="form-control" id="account_id" name="account_id" value="<?php echo get_option('acc_it_account_id'); ?>" placeholder="Account number" required number>
                    <button name="button" class="refresh_btn" id="refresh_account_id" type="button">Pull Client id from AccountIT</button>
                </div>
                <br>
                <div class="form-group">
                    <label for="company">AccountIT Item <small>(which item to use in AccountIT catalog; please make sure this item exists in AccountIT)</small></label>
                    <input type="number" class="form-control" id="item_id" name="item_id" value="<?php echo get_option('acc_it_item_id'); ?>" placeholder="Item number" required number>
                    <button name="button" class="refresh_btn" id="refresh_item_id" type="button">Pull Item id from AccountIT</button>
                </div>
                <br>
                <div class="form-group">
                    <label for="company">VAT Calculation: <small></small></label>
                    
                    <select class="form-control" id="manage_tax" name="manage_tax" required>
                        <option value="0" <?php selected( get_option('acc_it_manage_tax'), 0 ); ?>>Let AccountIT recalculate VAT based on your company settings</option>
                        <option value="1" <?php selected( get_option('acc_it_manage_tax'), 1 ); ?>>Allow Zero VAT deals (Based on WooCommerce Standard Rates)</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="company">Update Inventory: <small>(AccountIT inventory will be update based on incoming orders;<br>please make sure item's SKU [catalog number] is the same in both AccountIT and WP)</small></label>
                    
                    <select class="form-control" id="update_inventory" name="update_inventory" required>
                        <option value="0" <?php selected( get_option('acc_it_update_inventory'), 0 ); ?>>Do not update AccountIT inventory</option>
                        <option value="1" <?php selected( get_option('acc_it_update_inventory'), 1 ); ?>>Update AccountIT inventory</option>
                        <option value="2" <?php selected( get_option('acc_it_update_inventory'), 2 ); ?>>Update AccountIT inventory and add new items if missing </option>
                    </select>
                </div>
                
                <br>
                <div class="form-group">
                    <label for="env">Environment (Advanced): <small></small></label>
                    
                    <select class="form-control" id="env" name="env" required>
                        <option value="0" <?php selected( get_option('acc_it_env'), 0 ); ?>>Live</option>
                        <option value="1" <?php selected( get_option('acc_it_env'), 1 ); ?>>Testing</option>
                        <option value="2" <?php selected( get_option('acc_it_env'), 2 ); ?>>Develop</option>
                    </select>
                </div>
                <br>
                <input id="submit" type="submit" name="save-credence" class="btn btn-success btn-sm btn-block" value="Save Data">
            </form>

            <?php elseif($current_tab == 'import_export'): ?>
                    <!-- Import/Export Tab Content -->
                    <div class="import-export-section">
                        <h4>Import Items from AccountIT</h4>
                        
                        <div class="form-group">
                            <label>Sync Options</label>
                            <select class="form-control" id="sync_type">
                                <option value="full">Full Sync (Import all items)</option>
                                <option value="partial">Partial Sync (Import only new items)</option>
                            </select>
                        </div>
                        
                        <button id="start-sync" class="btn btn-primary">Start Sync Now</button>
                        <span id="sync-status" style="margin-left: 10px;"></span>
                        
                        <div id="sync-progress" style="margin-top: 20px; display: none;">
                            <div class="progress">
                                <div class="progress-bar" role="progressbar" style="width: 0%"></div>
                            </div>
                            <p class="sync-details"></p>
                        </div>
                        
                        <hr>
                        <div class="export-section">
                            <h4>Export Products to AccountIT</h4>
                            <p>Export your current WooCommerce products in standard WooCommerce CSV format.</p>
                            
                            <button id="export-products" class="btn btn-primary">
                                <span class="dashicons dashicons-download"></span> Export Products
                            </button>
                            <span id="export-status" style="margin-left: 10px;"></span>
                        </div>
                    </div>
                <?php endif; ?>
            <?php if(!empty($status)): ?>
                <br>
                <div class="alert alert-success" role="alert"> <strong>Well done!</strong> Successfully Saved </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php
}
