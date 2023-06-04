<?php
define( 'VERSION', "1.55");

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );
/**
 * @package Akismet
 */
/*
Plugin Name: Woo AccountIT Connector
Plugin URI: https://accountit.co.il
Description: This plugins sends a mail to the shop admin and the customer a mail of the ordered pdf and also pushes the data to AccountIT database
Version: 1.55
Author: AccountIT
Author URI: https://accountit.co.il
Text Domain: woo-tracker
*/

/*
This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.

Copyright 2021 AccountIT, Inc.
*/

// Make sure we don't expose any info if called directly
if ( !function_exists( 'add_action' ) ) {
        die( 'No script kiddies please!' );
}

define( 'WOO_TRACKER__PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'MAIL_PDF_DIR', ABSPATH . 'wp-content/uploads/mailed_order_to_customer/' );

require_once( WOO_TRACKER__PLUGIN_DIR.'/class/bootfile.class.php' );

AddFile::addFiles('/', 'helpers', 'php');

if(in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))):
    //AddFile::addFiles('includes/tcpdf', 'tcpdf', 'php');
    AddFile::addFiles('includes', 'nusoap', 'php');
    AddFile::addFiles('class', 'api.class', 'php');
    AddFile::addFiles('class', 'trackorder.class', 'php');
    AddFile::addFiles('views', 'settings', 'php');

    add_action( 'admin_menu', 'woo_tracker_settings' );

    function woo_tracker_settings() {
            add_menu_page( 'Woo AccountIT Connector', 'Woo AccountIT Connector', 'manage_options', 'views/settings.php', 'woo_tracker_settings_details', AddFile::addFiles('assets/images', 'icon-small', 'png', true), 100  );
    }
    
    add_filter( 'manage_edit-shop_order_columns', 'webroom_add_order_new_column_header', 20);
    add_action( 'manage_shop_order_posts_custom_column', 'webroom_add_wc_order_admin_list_column_content' );

    function webroom_add_wc_order_admin_list_column_content( $column ) {

        global $post;

        if ( 'acc_invoice_num' === $column ) {

            $order = wc_get_order( $post->ID );
            $order_meta = get_post_meta($post->ID);
            $acc_status = isset($order_meta["acc_docnum"]) && sizeof($order_meta["acc_docnum"]) > 0 ? $order_meta["acc_docnum"][0] : "";
            $acc_doctype = isset($order_meta["acc_doctype"]) && sizeof($order_meta["acc_doctype"]) > 0 ? $order_meta["acc_doctype"][0] : "";
            $acc_docnum = "";
            if($acc_doctype == 9){
                $acc_docnum = "Invoice Receipt #".$acc_status;
            }
            else if($acc_doctype == 8){
                 $acc_docnum = "Receipt #".$acc_status;
            }
            else if($acc_doctype == 3){
                 $acc_docnum = "Invoice #".$acc_status;
            }
                
            echo '<p>' . $acc_docnum . '</p>';

        }
    }

else:
	add_action( 'admin_notices', 'error_message' );
endif;

function my_woocommerce_admin_order_item_headers() {
    // set the column name
    $column_name = 'Test Column';

    // display the column name
    echo '<th>' . $column_name . '</th>';
}

function my_woocommerce_admin_order_item_values($_product, $item, $item_id = null) {
    // get the post meta value from the associated product
    $value = get_post_meta($_product->post->ID, '_custom_field_name', 1);

    // display the value
    echo '<td>' . $value . '</td>';
}

function webroom_add_order_new_column_header( $columns ) {

    $new_columns = array();

    foreach ( $columns as $column_name => $column_info ) {

        $new_columns[ $column_name ] = $column_info;

        if ( 'order_total' === $column_name ) {
            $new_columns['acc_invoice_num'] = __( 'Invoice (Receipt) number', 'my-textdomain' );
        }
    }

    return $new_columns;
}