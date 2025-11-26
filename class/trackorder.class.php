<?php
defined('ABSPATH') or die('No script kiddies please!');

add_action('woocommerce_order_status_changed', 'track_order_details', 10, 3);

function track_order_details($order_id, $old_status = null, $new_status = null) {
    if (!$order_id) {
        return;
    }

    $order = wc_get_order($order_id);
    if (!$order) {
        return;
    }

    // === Helper: Normalize status to prefixed (for DB/hook consistency) ===
    //make sure we are not redeclare the function normalize_status_to_prefixed
    if (!function_exists('normalize_status_to_prefixed')) {
        function normalize_status_to_prefixed($status) {
            if (strpos($status, 'wc-') !== 0) {
                return 'wc-' . $status;
            }
            return $status;
        }
    }

    // === Get plugin settings ===
    $api_user_name      = get_option('acc_it_username');
    $api_app_key        = get_option('acc_it_appkey');
    $api_company_key    = get_option('acc_it_company');
    $api_doc_type       = get_option('acc_it_doc_type');
    $api_account_id     = get_option('acc_it_account_id');
    $api_item_id        = get_option('acc_it_item_id');
    $acc_it_manage_tax  = get_option('acc_it_manage_tax');
    $acc_email_client   = get_option('acc_email_client');
    $acc_auto_create_on_new_order        = get_option('acc_auto_create_on_new_order');
    $acc_auto_create_on_new_order_triger = get_option('acc_auto_create_on_new_order_triger', array());
    $acc_it_update_inventory             = get_option('acc_it_update_inventory');

    // === Early exits ===
    if ($acc_auto_create_on_new_order != '1') {
        return; // Plugin disabled or Manual mode (handled elsewhere)
    }

    // Use new_status from hook (prefixed, e.g., "wc-completed")
    $current_status = normalize_status_to_prefixed($new_status ?: $order->get_status());

    // Ensure trigger is array; normalize each trigger status to prefixed for comparison
    if (!is_array($acc_auto_create_on_new_order_triger)) {
        $acc_auto_create_on_new_order_triger = array();
    }
    $normalized_triggers = array_map('normalize_status_to_prefixed', $acc_auto_create_on_new_order_triger);
    if (!in_array($current_status, $normalized_triggers)) {
        return;
    }

    // Check if already sent (custom meta, unprefixed is fine)
    $acc_status = $order->get_meta('acc_status', true);
    if ($acc_status === '1') {
        return;
    }

    // Bad statuses (prefixed for DB consistency)
    $bad_statuses = array('wc-cancelled', 'wc-refunded', 'wc-failed');
    if (in_array($current_status, $bad_statuses)) {
        return;
    }

    accountit_create_invoice($order_id);
}

function accountit_create_invoice($order_id) {
    $order = wc_get_order($order_id);
    if (!$order) {
        return false;
    }

    // === Get plugin settings ===
    $api_user_name      = get_option('acc_it_username');
    $api_app_key        = get_option('acc_it_appkey');
    $api_company_key    = get_option('acc_it_company');
    $api_doc_type       = get_option('acc_it_doc_type');
    $api_account_id     = get_option('acc_it_account_id');
    $api_item_id        = get_option('acc_it_item_id');
    $acc_it_manage_tax  = get_option('acc_it_manage_tax');
    $acc_email_client   = get_option('acc_email_client');
    $acc_it_update_inventory = get_option('acc_it_update_inventory');

    // === Initialize API ===
    $accit = new AccountAPI($api_user_name, $api_app_key, $api_company_key);
    $acc_it_docdetials = array();
    $acc_it_rcptdetials = array();
    $docDate = date('d-m-Y');
    $id = 1;

    $total_taxable   = 0.0;
    $total_notaxable = 0.0;
    $prices_include_tax = $order->get_prices_include_tax(); // bool (true/false)

    // Only process items for certain doc types
    if (in_array($api_doc_type, array(3, 9, 7, 12))) {
        foreach ($order->get_items() as $item_id => $item) {
            $product = $item->get_product();
            $qty     = (int) $item->get_quantity();
            $line_total = (float) $item->get_total();   // Excl. tax
            $line_tax   = (float) $item->get_total_tax();

            // Price calculation (raw numeric)
            //$price = $prices_include_tax ? $line_total + $line_tax : $line_total;
            $price = $line_total;
            $unit_price = $qty > 0 ? $price / $qty : $price;

            if ($line_tax > 0) {
                $total_taxable += $price;
            } else {
                $total_notaxable += $price;
            }

            $ext_cat_num = $api_item_id;
            if ($product && $product->get_sku()) {
                $ext_cat_num = $product->get_sku();
            }

            $acc_it_docdetials[] = array(
                "num"               => "",
                "cat_num"           => $api_item_id,
                "ext_cat_num"       => $ext_cat_num,
                "description"       => $item->get_name(),
                "qty"               => $qty,
                "unit_price"        => $unit_price,
                "currency"          => "0",
                "price"             => $price,
                "nisprice"          => "",
                "id"                => $id++,
                "status"            => "2",
                "discount_type"     => "0",
                "discount_price"    => $unit_price,
                "discount"          => "0.00",
                "currency_sign"     => $order->get_currency()
            );
        }
    }

    // === Shipping as line item (raw numeric values) ===
    $shipping_total_raw = (float) $order->get_shipping_total(true); // true = raw float
    $shipping_tax_raw   = (float) $order->get_shipping_tax(true);   // true = raw float

    if ($shipping_total_raw > 0) {
        $shipping_price = $prices_include_tax ? $shipping_total_raw + $shipping_tax_raw : $shipping_total_raw;

        if ($shipping_tax_raw > 0) {
            $total_taxable += $shipping_total_raw;//$shipping_price;
        } 
        else if ($shipping_tax_raw == 0 && (float) $order->get_total_tax() <= 0) {
            $total_notaxable += $shipping_total_raw;
        }
        else {
            //this is tax free shipping but there is a tax on the order -> this is not allowed
            return false;
        }
        //get the actual shipping name
        $shipping_name = $order->get_shipping_method();
        $shipping_name = $shipping_name ? $shipping_name : "משלוח";

        $acc_it_docdetials[] = array(
            "num"               => "",
            "cat_num"           => $api_item_id,
            "description"       => $shipping_name,
            "qty"               => 1,
            "unit_price"        => $shipping_total_raw,
            "currency"          => "0",
            "price"             => $shipping_total_raw,
            "nisprice"          => "",
            "id"                => $id++,
            "status"            => "2",
            "discount_type"     => "0",
            "discount_price"    => $shipping_total_raw,
            "discount"          => "0.00",
            "currency_sign"     => $order->get_currency()
        );
    }

    // === Payment details (for receipt/invoice+receipt) ===
    if (in_array($api_doc_type, array(8, 9))) {
        $payment_method = $order->get_payment_method();
        $_payment_type  = "3"; // Credit card default
        $creditcompany  = "";

        switch (strtolower($payment_method)) {
            case 'cod':
                $_payment_type = "1";
                break;
            case 'cheque':
                $_payment_type = "2";
                break;
            case 'bacs':
                $_payment_type = "4";
                $bacs = new WC_Gateway_BACS();
                $accounts = $bacs->account_details ?? array();
                if (!empty($accounts[0]['sort_code'] ?? '')) {
                    $creditcompany = $accounts[0]['sort_code'];
                }
                break;
        }

        $acc_it_rcptdetials[] = array(
            "type"           => $_payment_type,
            "creditcompany"  => $creditcompany,
            "cheque_num"     => $order_id,
            "bank"           => "",
            "branch"         => "",
            "cheque_acct"    => "",
            "cheque_date"    => $docDate,
            "sum"            => (float) $order->get_total(),
            "bank_refnum"    => "",
            "dep_date"       => "",
            "id"             => "1",
            "currency_sign"  => $order->get_currency(),
        );
    }

    // === Adjust taxable total if prices include tax ===
    $vat = 0;
    
    if ($acc_it_manage_tax == 0) {
        $vat = 0;
        $sub_total = 0;
        $total_no_discount = 0;
        $total_taxable = 0;
    }
    else{
        $vat = (float) $order->get_total_tax();
        if ($prices_include_tax) {
            $total_taxable = $order->get_total();
            $sub_total = $order->get_total() - $vat;
        }
        else{
            $total_taxable = $order->get_total();
            $sub_total = $order->get_total() - $vat;
        }
        
        $total_no_discount = $sub_total;
    }
    

    // === Build final data array ===
    $acc_it_data = array(
        "doctype"              => $api_doc_type,
        "account"              => $api_account_id,
        "company"              => trim($order->get_billing_first_name() . ' ' . $order->get_billing_last_name()),
        "address"              => $order->get_billing_address_1(),
        "city"                 => $order->get_shipping_city() ?: $order->get_billing_city(),
        "zip"                  => $order->get_shipping_postcode() ?: $order->get_billing_postcode(),
        "phone"                => $order->get_billing_phone(),
        "issue_date"           => $docDate,
        "due_date"             => $docDate,
        "refnum"               => $order_id,
        "sub_total"            => $sub_total,
        "novat_total"          => "0",
        "vat"                  => $vat,
        "total"                => $total_taxable,
        "src_tax"              => "0",
        "issue_time"           => date('H:i:s'),
        "total_discount_percent" => "0",
        "total_discount"       => "0",
        "total_no_discount"    => $total_no_discount,
        "docdetials"           => $acc_it_docdetials,
        "rcptdetials"          => $acc_it_rcptdetials,
        "price_include_vat"    => $prices_include_tax ? "1" : "0",
        "manage_tax"           => $acc_it_manage_tax,
        "billing_email"        => $order->get_billing_email(),
        "email_client"         => $acc_email_client,
        "currency_sign"        => $order->get_currency(),
        'update_inventory'     => $acc_it_update_inventory,
    );

    // === Send to AccountIT API ===
    $accit = new AccountAPI($api_user_name, $api_app_key, $api_company_key);
    $acc_stat = $accit->putData($acc_it_data);

    if (!empty($acc_stat) && !empty($acc_stat["data"]) && $acc_stat["data"] > 0) {
        // Save using CRUD-compatible method
        $order->update_meta_data('acc_status', '1');
        $order->update_meta_data('acc_docnum', $acc_stat["docnum"] ?? 0);
        $order->update_meta_data('acc_doctype', $acc_stat["doctype"] ?? 0);
        $order->save(); // Critical: saves meta even with HPOS

        //add meta to order
        update_post_meta($order_id, "acc_status", "1");
        update_post_meta($order_id, "acc_docnum", isset($acc_stat["docnum"]) && $acc_stat["docnum"] > 0 ?  $acc_stat["docnum"] : 0 );
        update_post_meta($order_id, "acc_doctype", isset($acc_stat["doctype"]) && $acc_stat["doctype"] > 0 ?  $acc_stat["doctype"] : 0 );

        // Optional: show admin notice
        add_action('admin_notices', 'accit_success_message');
        return true;
    }
    return false;
}

// Optional success message
function accit_success_message() {
    echo '<div class="notice notice-success is-dismissible"><p>AccountIT: מסמך נוצר בהצלחה!</p></div>';
}