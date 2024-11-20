<?php

defined('ABSPATH') or die('No script kiddies please!');
//add_action( 'woocommerce_order_status_completed', 'track_order_details' );
add_action('woocommerce_order_status_changed', 'track_order_details');

//add_action('woocommerce_email_after_order_table', 'wh_addTransactionIdOrderEmail', 10, 2);



function track_order_details($order_id) {//, $source) {
    if ($order_id == null)://  || $source != "new_order" && $acc_auto_create_on_new_order == 1 ):
        return;
    endif;

    // 1) Get the Order object
    $order = wc_get_order($order_id);
    // 2) Get the Order meta data
    $order_meta = get_post_meta($order_id);
    // 3) Get the order items
    $items = $order->get_items();

    /////////////////////for DEBUG use only///////////////////// 
    /* $debug_order = array('order'=>$order, 'order_meta'=>$order_meta, 'items'=>$items);
      $commands['json'] = json_encode($debug_order);
      $url = 'http://test.accountit.co.il/debug/getall.php';
      $ch = curl_init($url);

      curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
      curl_setopt($ch,CURLOPT_POSTFIELDS,$commands);
      curl_setopt($ch,CURLOPT_FRESH_CONNECT,TRUE);

      $result1 = curl_exec($ch);
      $result = json_decode($result1,true);
      curl_close($ch); */
    ////////////////////end DEBUG/////////////////////////////////
    // variables
    $api_user_name = get_option('acc_it_username');
    $api_app_key = get_option('acc_it_appkey');
    $api_company_key = get_option('acc_it_company');
    $api_doc_type = get_option('acc_it_doc_type');
    $api_account_id = get_option('acc_it_account_id');
    $api_item_id = get_option('acc_it_item_id');
    $ext_cat_num = $api_item_id;
    $acc_it_manage_tax = get_option('acc_it_manage_tax');
    $acc_email_client = get_option('acc_email_client');
    $accit = new AccountAPI($api_user_name, $api_app_key, $api_company_key);
    $acc_it_docdetials = array();
    $acc_it_rcptdetials = array();
    $docDate = date('d-m-Y');
    $id = 1;
    // ends
    //v 1.3.1
    $acc_auto_create_on_new_order = get_option('acc_auto_create_on_new_order');
    $acc_auto_create_on_new_order_triger = get_option('acc_auto_create_on_new_order_triger');
    //v 1.5.1
    $acc_it_update_inventory = get_option('acc_it_update_inventory'); 
    
    //check if function $order->get_status() exists
    if (method_exists($order, 'get_status')) {
        $order_status = "wc-".$order->get_status(); //status changed in woo 9.0
    } else {
        $order_status = $order->post_status;
    }
    
    //bad status do not continue
    if ($order->post_status == "wc-cancelled" || $order->post_status == "wc-refunded" || $order->post_status == "wc-failed")
        return;

    $acc_status = isset($order_meta["acc_status"]) && sizeof($order_meta["acc_status"]) > 0 ? $order_meta["acc_status"][0] : null;
    
    if ($acc_auto_create_on_new_order == 0) //plugin is disabled -> exit
        return;

    //not the right status -> exit
    if (in_array($order_status, $acc_auto_create_on_new_order_triger) != "1")
        return;

    if ($acc_status == 1) //doc was already send  ->exit
        return;

    $total_taxable = 0;
    $total_notaxable = 0;

    $_prices_include_tax = $order_meta['_prices_include_tax'][0];
    if ($api_doc_type == 3 || $api_doc_type == 9 || $api_doc_type == 7 || $api_doc_type == 12):
        foreach ($items as $item_id => $item_data):
            $qty = $order->get_item_meta($item_id, '_qty', true);
            if (!isset($qty) || $qty == "" || $qty == "0")
                $qty = 1;
            $line_tax = $order->get_item_meta($item_id, '_line_tax', true);
            if ($_prices_include_tax == "yes")
                $price = $order->get_item_meta($item_id, '_line_total', true) + $line_tax;
            else
                $price = $order->get_item_meta($item_id, '_line_total', true);

            if ($line_tax > 0)
                $total_taxable += $price;
            else
                $total_notaxable += $price;

            $unit_price = $price / $qty;
            
            $product = wc_get_product($item_data->get_product_id());
            if (isset($product)){
                 $item_sku[] = $product->get_sku();
                 if(isset($item_sku) && count($item_sku) > 0)
                    $ext_cat_num = $item_sku[0];
            }
           
            $acc_it_docdetials[] = array(
                "num" => "",
                "cat_num" => $api_item_id,
                "ext_cat_num" => $ext_cat_num, 
                "description" => $item_data['name'],
                "qty" => $qty,
                "unit_price" => $unit_price,
                "currency" => "0", //for_now... other wise: get_woocommerce_currency(),
                "price" => $price,
                "nisprice" => "",
                "id" => $id,
                "status" => "2",
                "discount_type" => "0",
                "discount_price" => $unit_price,
                "discount" => "0.00",
                "currency_sign" => $order_meta["_order_currency"][0]
            );
            $id++;

        endforeach;
    endif;

    $shipping_total = 0;
    if (isset($order_meta['_order_shipping']) && sizeof($order_meta['_order_shipping']) > 0 && floatval($order_meta['_order_shipping'][0]) > 0) {
        foreach ($order_meta['_order_shipping'] as $shippment) {
            $shipping_total += floatval($order_meta['_order_shipping'][0]);
        }
    } else if (isset($order->data["shipping_total"]) && $order->data["shipping_total"] > 0) {
        $shipping_total = $order->data["shipping_total"];
    }
    //add shipping as item
    if ($shipping_total > 0) {
        $acc_it_docdetials[] = array(
            "num" => "",
            "cat_num" => $api_item_id,
            "description" => "משלוח",
            "qty" => "1",
            "unit_price" => $shipping_total, //$order->data["shipping_total"],
            "currency" => "0", //for_now... other wise: get_woocommerce_currency(),
            "price" => $shipping_total, //$order->data["shipping_total"],
            "nisprice" => "",
            "id" => $id,
            "status" => "2",
            "discount_type" => "0",
            "discount_price" => $shipping_total, //$shipping_total$order->data["shipping_total"],
            "discount" => "0.00",
            "currency_sign" => $order_meta["_order_currency"][0]
        );
        $id++;

        if ($order->data["shipping_tax"] > 0)
            $total_taxable += $shipping_total; 
        else
            $total_notaxable += $shipping_total;
    }

    //create payment
    if ($api_doc_type == 8 || $api_doc_type == 9) {
        $_payment_method = $order_meta['_payment_method'][0];
        $_payment_type = "3";
        $creditcompany = "";
        if (strtolower($_payment_method) == "cod")
            $_payment_type = "1";
        else if (strtolower($_payment_method) == "cheque")
            $_payment_type = "2";
        else if (strtolower($_payment_method) == "bacs") {
            $_payment_type = "4";
            $bacs_accounts = new WC_Gateway_BACS();
            if (!empty($bacs_accounts)) {
                $accounts = $bacs_accounts->account_details;
                if (!empty($accounts) && sizeof($accounts) > 0)
                    $creditcompany = $accounts[0]['sort_code'];
            }
        }

        $acc_it_rcptdetials[] = array(
            "type" => $_payment_type,
            "creditcompany" => $creditcompany, //$order->get_total(),
            "cheque_num" => $order_id,
            "bank" => "", //$account['bank_name'],
            "branch" => "", //$account['bic'],
            "cheque_acct" => "", //$account['iban'],
            "cheque_date" => $docDate,
            "sum" => $order->get_total(),
            "bank_refnum" => "", //$account['bic'],
            "dep_date" => "",
            "id" => "1",
            "currency_sign" => $order_meta["_order_currency"][0], 
        );
    }

    if ($_prices_include_tax == "yes") {
        $total_taxable = $total_taxable - $order_meta['_order_tax'][0];
    }

    $acc_it_data = array(
        "doctype" => $api_doc_type,
        "account" => $api_account_id,
        "company" => $order_meta['_billing_first_name'][0] . ' ' . $order_meta['_billing_last_name'][0],
        "address" => $order_meta['_billing_address_1'][0],
        "city" => $order_meta['_shipping_city'][0],
        "zip" => $order_meta['_shipping_postcode'][0],
        "phone" => $order_meta['_billing_phone'][0],
        "issue_date" => $docDate,
        "due_date" => $docDate,
        "refnum" => $order_id,
        "sub_total" => $total_taxable, //$sub_total,
        "novat_total" => $total_notaxable, //$sub_total,
        "vat" => $order_meta['_order_tax'][0],
        "total" => $total_taxable + $total_notaxable + (isset($order_meta['_order_tax']) && sizeof($order_meta['_order_tax']) > 0 ? $order_meta['_order_tax'][0] : 0) + (isset($order_meta['_order_shipping_tax'][0]) && sizeof($order_meta['_order_shipping_tax']) > 0 ? $order_meta['_order_shipping_tax'][0] : 0 ), ///$order->get_total(),
        "src_tax" => "0", //$order_meta['_order_tax'][0],
        "issue_time" => date('H:i:s'),
        "total_discount_percent" => "0", //$total_discount_percent,
        "total_discount" => "0", //$total_discount,
        "total_no_discount" => "0", //$total_no_discount,
        "docdetials" => $acc_it_docdetials,
        "rcptdetials" => $acc_it_rcptdetials,
        "price_include_vat" => $_prices_include_tax == "yes" ? "1" : "0",
        "manage_tax" => $acc_it_manage_tax,
        "billing_email" => $order_meta['_billing_email'][0],
        "email_client" => $acc_email_client,
        "currency_sign" => $order_meta["_order_currency"][0],
        'update_inventory' => $acc_it_update_inventory,
    );

    $acc_stat = $accit->putData($acc_it_data);
    if (!empty($acc_stat) && $acc_stat && $acc_stat["data"] > 0) {
        update_post_meta($order_id, "acc_status", "1");
        update_post_meta($order_id, "acc_docnum", isset($acc_stat["docnum"]) && $acc_stat["docnum"] > 0 ?  $acc_stat["docnum"] : 0 );
        update_post_meta($order_id, "acc_doctype", isset($acc_stat["doctype"]) && $acc_stat["doctype"] > 0 ?  $acc_stat["doctype"] : 0 );
        add_action('admin_notices', 'success_message');
    }
}
