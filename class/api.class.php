<?php
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

class AccountAPI {

    public static function GetEnvUrl($env_id) {
        if($env_id == 1)
        {
           return "https://mytest.accountit.co.il/";
        }
        else if($env_id == 2)
        {
            //devserver server
            return "http://accountit.local/accountit/src/";

        }
        return "https://my.accountit.co.il/";
    }
    
    function __construct($username, $appkey, $company_code) {
        $this->username = $username;
        $this->appkey   = $appkey;
        $this->company_code  = $company_code;

        $env = get_option('acc_it_env');
        //production server
        $this->api_url  = "https://my.accountit.co.il/api.php";
        if($env == 1)
        {
            //test server
            $this->api_url  = "https://mytest.accountit.co.il/api.php";
        }
        else if($env == 2)
        {
            //devserver server
            $this->api_url  = "http://accountit.local/accountit/src/api.php";
        }
    }
    
    

    function filterData($data) {
        return json_decode( str_replace(array('jcb(', ')'), "", $data), true );
    }

    function getCurl($url) {
        $user_agent='Mozilla/5.0 (Windows NT 6.1; rv:8.0) Gecko/20100101 Firefox/8.0';

        $options = array(

            CURLOPT_CUSTOMREQUEST  => "GET",        //set request type post or get
            CURLOPT_POST           => false,        //set to GET
            CURLOPT_USERAGENT      => $user_agent, //set user agent
            //CURLOPT_COOKIESESSION  => true,
            CURLOPT_COOKIEFILE     => dirname(__FILE__)."/cookie.txt", //set cookie file
            CURLOPT_COOKIEJAR      => dirname(__FILE__)."/cookie2.txt", //set cookie jar
            CURLOPT_RETURNTRANSFER => true,     // return web page
            CURLOPT_HEADER         => false,    // don't return headers
            CURLOPT_FOLLOWLOCATION => true,     // follow redirects
            CURLOPT_ENCODING       => "",       // handle all encodings
            CURLOPT_AUTOREFERER    => true,     // set referer on redirect
            CURLOPT_CONNECTTIMEOUT => 120,      // timeout on connect
            CURLOPT_TIMEOUT        => 120,      // timeout on response
            CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
        );

        $ch      = curl_init( $url );
        curl_setopt_array( $ch, $options );
        $content = curl_exec( $ch );
        curl_close( $ch );
        return $content;
    }

    function authCheck() {
        //$str = "http://test.accountit.co.il/AccountIT/api.php?action=Login&username=$this->username&appKey=$this->appkey";
        $str = "$this->api_url?action=Login&username=$this->username&company_code=".$this->company_code."&appKey=$this->appkey&version=".VERSION;
        $result = self::getCurl($str);
        return self::filterData($result)['sid'];
    }

    function getData($serial = '9999') {
        //$auth_check = self::authCheck();
        $str = "$this->api_url?action=Get&company_code=".$this->company_code."&appKey=$this->appkey&jsoncallback=jcb&version=".VERSION."&data=Document&num=$serial";
        $result = self::getCurl($str);
        return self::filterData($result);
    }

    function getItemData($serial = '9999') {
        //$auth_check = self::authCheck();
        $str = "$this->api_url?action=Get&data=ItemList&company_code=".$this->company_code."&appKey=$this->appkey&jsoncallback=jcb&version=".VERSION."&num=$serial";
        $result = self::getCurl($str);
        return self::filterData($result);
    }

    function getAccountData($first_account = false) {
        $str = "$this->api_url?action=Get&data=AccountList&company_code=".$this->company_code."&appKey=$this->appkey&jsoncallback=jcb&version=".VERSION."&first_account=".$first_account;
        $result = self::getCurl($str);
        return self::filterData($result);
    }

    function putData($data) {
        //$auth_check = self::authCheck();
        global $woocommerce, $wp_version;
        $pre_str = "$this->api_url?action=New&company_code=".$this->company_code."&appKey=$this->appkey&jsoncallback=jcb&version=".VERSION."&wc_version=".$woocommerce->version."&wp_version=".$wp_version."&data=Document&";//&account=113&";
        $str = http_build_query($data);
        $result = self::getCurl($pre_str.$str);
        return self::filterData($result);
    }

    function __destruct() {
        //$str = "$this->api_url?action=Logout&version=".VERSION;
        //self::getCurl($str);
        if (file_exists(dirname(__FILE__)."/cookie2.txt")) {
          unlink(dirname(__FILE__)."/cookie2.txt");
        }
        if (file_exists(dirname(__FILE__)."/cookie.txt")) {
          unlink(dirname(__FILE__)."/cookie.txt");
        }
    }
}
