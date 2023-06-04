<?php
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

add_action('wp_ajax_my_action', 'my_ajax_action_function');

function my_ajax_action_function(){

    $reponse = array();
    $env = $_POST['env'];
    $prefix = $_POST['company'];
    $api_code = $_POST['appkey'];
    $type = $_POST['type'];
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

    //Don't forget to always exit in the ajax function.
    exit();

}

function woo_tracker_settings_details() {

     $status = null;

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
</style>
<p></p>
<div class="container">
    <div class="col-md-8 col-md-push-1">
        <div class="form-area">
            <img class="img-responsive center-block" src="<?php echo AddFile::addFiles('assets/images', 'icon', 'png', true); ?>" alt="logo">
             <h3>AccountIT - Advanced Accounting System</h3>
             <small>Version: <?php echo VERSION; ?></small>
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
                        <option value="7" <?php selected( get_option('acc_it_doc_type'), 7 ); ?>>Order</option>
                        <option value="12" <?php selected( get_option('acc_it_doc_type'), 12 ); ?>>Pro forma invoice</option>
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
            <?php if(!empty($status)): ?>
                <br>
                <div class="alert alert-success" role="alert"> <strong>Well done!</strong> Successfully Saved </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php
}
