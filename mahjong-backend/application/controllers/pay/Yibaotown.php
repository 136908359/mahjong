<? header("content-Type: text/html; charset=UTF-8");?>
<?php

require_once APPPATH.'third_party/uuid.php';
require_once APPPATH.'third_party/access.php';

/* *
 *功能：亿宝通网银支付接口
 *版本：3.0
 *日期：2017-04-19
 *说明：
 *以下代码只是为了方便商户测试而提供的样例代码，商户可以根据自己网站的需要，按照技术文档编写,
 *并非一定要使用该代码。该代码仅供学习和研究接口使用，仅为提供一个参考。
 **/
 
///////////////////////////  初始化接口参数  //////////////////////////////
/**
接口参数请参考亿宝通网银支付文档，除了sign参数，其他参数都要在这里初始化
*/
	$merchant_private_key = '-----BEGIN RSA PRIVATE KEY-----
MIICXAIBAAKBgQDckrQ+DbvIVk6kIdmc8+uKpfO6yUR1yOw+1kmftAraxtHHtLgS
GaLEZ4t+k4c3oLwI3KuIVZyosTmz/Om9Fg9ijggTLRSljr+IUHyaMwgQg1k2ghkR
rxHoThkAe+QwtrQhFzbWJV0Laon4DIX7dmuMZ78c1gWSP8xtyiUHdE+c0QIDAQAB
AoGACODm3HCVFHVU6QprxgOTgZs4elZLqSoTSFw7zm/i1/eUziMaHbBmet1oIgoy
MS0JJJotVWmMysWHexU9G11d9Rh9Dv4pAtJEkUJ8nMjPAmMZp55IQBIJJvj6HW6L
pUUXSvxMpptpQYgtB4CDFkJqmvwmOYWCNDZKqdULgPzpaAECQQDvDXxGB5ZWV4N/
I/7WH1UYC8+5U2lXoBOEJ4z0lLeAxdhzWfP6SJOGa1OZ2gYjjwG4Kvg/Mtpcpw48
DHGuCPLBAkEA7DXWDnM/bHsVy4kzACfjY+xJ3xrTygf0zPFpXzldlDPCXzEHbUbW
2r/D7zfR2qNYJm+vxmajp3n7oPyiJ9z+EQJAZb/wsIIUPGX9g4VXt94YQybr4K8f
PHvXMr3+4i/Wt4n+qoKUNWjk2iceq3LAgCwjiDdJ+OR1S1CT331Qeco3QQJAW6fQ
Na82jwt7u4yzQ34219EaIP4x7BUGQnfyYUbLLvSemX1W2mpAeIUsrChGv8XeMJvp
4tx06EmHAELHURyJYQJBAIB/r/scvVXwWWsA/L2IMeuf4fbqFTBdlqj71Mjvsw0n
177Kem4lQMvyYuaNnBNLP/+dRb6qMpX+dgllmsl3wsA=
-----END RSA PRIVATE KEY-----';

	//merchant_public_key,商户公钥，按照说明文档上传此密钥到亿宝通商家后台，位置为"支付设置"->"公钥管理"->"设置商户公钥"，代码中不使用到此变量
	//demo提供的merchant_public_key已经上传到测试商家号后台
	$merchant_public_key = '-----BEGIN PUBLIC KEY-----
MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDckrQ+DbvIVk6kIdmc8+uKpfO6
yUR1yOw+1kmftAraxtHHtLgSGaLEZ4t+k4c3oLwI3KuIVZyosTmz/Om9Fg9ijggT
LRSljr+IUHyaMwgQg1k2ghkRrxHoThkAe+QwtrQhFzbWJV0Laon4DIX7dmuMZ78c
1gWSP8xtyiUHdE+c0QIDAQAB
-----END PUBLIC KEY-----';
	
/**
1)ddbill_public_key，亿宝通公钥，每个商家对应一个固定的亿宝通公钥（不是使用工具生成的密钥merchant_public_key，不要混淆），
即为亿宝通商家后台"公钥管理"->"亿宝通公钥"里的绿色字符串内容,复制出来之后调成4行（换行位置任意，前面三行对齐），
并加上注释"-----BEGIN PUBLIC KEY-----"和"-----END PUBLIC KEY-----"
2)demo提供的ddbill_public_key是测试商户号388003002444的智付公钥，请自行复制对应商户号的智付公钥进行调整和替换。
3）使用亿宝通公钥验证时需要调用openssl_verify函数进行验证,需要在php_ini文件里打开php_openssl插件
*/
	$plate_public_key = '-----BEGIN PUBLIC KEY-----
MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQC1nCfWgC4flrMzfqbCd8yFf/+d
DpS5O3tILQuH/Ul6Lue5SDbe2Et7MsLLML7TTRfEfJmeomNjkU4sX0WzrodEXfB8
BKhKaj1MZv5/m8AmAvaClIomo9LxC1gWFRe20y31SKV1L0v2iXam89+jJ0Qco7cX
H960VfNVgJcngIkUvwIDAQAB 
-----END PUBLIC KEY-----';


class Yibaotown extends CI_Controller {
	function __construct() {
		parent::__construct();
        $this->load->helper('url');
        $this->load->helper('errcode');
        $this->load->model('pay_model');
        $this->load->model('gm_model');
        $this->load->model('config_model');
	}

	public function success() {
		echo "<h1>支付成功</h1>";
	}

	public function h5() {
		$err = access();
		if ( $err != RETURN_SUCCESS ) {
			exit(err_string($err));
		}

		$uid = $this->input->get("uid");
		$item_id = $this->input->get("itemId");
		$chan_id = $this->input->get("chanId");
		$rmb = $this->config_model->get("Shop",$item_id,"ShopsPrice");
		$title = $this->config_model->get("Shop",$item_id,"ShopTitle");
		if ( !$uid || !$item_id ) {
			exit(err_string(err_code(1001,"参数无效")));
		}

		$merchant_code = "385113001112";//商户号，388003002444是测试商户号，线上发布时要更换商家自己的商户号！
		$service_type ="h5_wx";	
		$interface_version ="V3.0";
		$sign_type ="RSA-S";
		$input_charset = "UTF-8";
		$notify_url ="http://test.bestmeide.com/yibaotown/notify";
		$order_no = create_uuid();	
		$order_time = date( 'Y-m-d H:i:s' );	
		$order_amount = "0.1";
		$product_name =$title;	
		//以下参数为可选参数，如有需要，可参考文档设定参数值
		
		$return_url ="";	
		$pay_type = "";
		$redo_flag = "";	
		$product_code = "";	
		$product_desc = "";	
		$product_num = "";
		$show_url = "";	
		$client_ip = GM_IP ;	
		$bank_code = "";	
		$extend_param = "";
		$extra_return_param = "";	

		/////////////////////////////   参数组装  /////////////////////////////////
		/**
			除了sign_type参数，其他非空参数都要参与组装，组装顺序是按照a~z的顺序，下划线"_"优先于字母	
		*/
		
		$signStr= "";
		if($bank_code != ""){
			$signStr = $signStr."bank_code=".$bank_code."&";
		}
		if($client_ip != ""){
			$signStr = $signStr."client_ip=".$client_ip."&";
		}
		if($extend_param != ""){
			$signStr = $signStr."extend_param=".$extend_param."&";
		}
		if($extra_return_param != ""){
			$signStr = $signStr."extra_return_param=".$extra_return_param."&";
		}
		
		$signStr = $signStr."input_charset=".$input_charset."&";	
		$signStr = $signStr."interface_version=".$interface_version."&";	
		$signStr = $signStr."merchant_code=".$merchant_code."&";	
		$signStr = $signStr."notify_url=".$notify_url."&";		
		$signStr = $signStr."order_amount=".$order_amount."&";		
		$signStr = $signStr."order_no=".$order_no."&";		
		$signStr = $signStr."order_time=".$order_time."&";	
    	
		if($pay_type != ""){
			$signStr = $signStr."pay_type=".$pay_type."&";
		}
		if($product_code != ""){
			$signStr = $signStr."product_code=".$product_code."&";
		}	
		if($product_desc != ""){
			$signStr = $signStr."product_desc=".$product_desc."&";
		}
		$signStr = $signStr."product_name=".$product_name."&";
		if($product_num != ""){
			$signStr = $signStr."product_num=".$product_num."&";
		}	
		if($redo_flag != ""){
			$signStr = $signStr."redo_flag=".$redo_flag."&";
		}
		if($return_url != ""){
			$signStr = $signStr."return_url=".$return_url."&";
		}		
		$signStr = $signStr."service_type=".$service_type;
		if($show_url != ""){	
			$signStr = $signStr."&show_url=".$show_url;
		}
		//echo $signStr."<br>";  
			
		/////////////////////////////   获取sign值（RSA-S加密）  /////////////////////////////////
		global $merchant_private_key;
		$private_key= openssl_get_privatekey($merchant_private_key);
		openssl_sign($signStr,$sign_info,$private_key,OPENSSL_ALGO_MD5);
		$sign = base64_encode($sign_info);
		// echo $sign;
		$pay_sdk = 'yibaotown';
		$order = array();
		$order['buy_uid'] = $uid;//uid
		$order['item_id'] = $item_id;//物品id
		$order['item_num'] = 1;//数量
		$order['rmb'] = $rmb;//金额
		$order['chan_id'] = $chan_id;//渠道号
		$order['create_time'] = date('Y-m-d H:i:s',time());
		$order['notify_time'] = date('Y-m-d H:i:s',time());
		$order['game'] = 'mahjong';
		$order['pay_sdk'] = $pay_sdk;//支付方式
		$order['order_id'] = $order_no;
		$this->pay_model->add_new_order($order);

		$queryList = array(
			"sign" => $sign,
			"merchant_code" => $merchant_code,
			"bank_code" => $bank_code,
			"order_no" => $order_no,
			"order_amount" => $order_amount,
			"service_type" => $service_type,
			"input_charset"=> $input_charset,
			"notify_url" => $notify_url,
			"interface_version" => $interface_version,
			"sign_type" => $sign_type,
			"order_time" => $order_time,
			"product_name" => $product_name,
			"client_ip" => $client_ip,
			"extend_param" => $extend_param,
			"extra_return_param" => $extra_return_param,
			"pay_type" => $pay_type,
			"product_code" => $product_code,
			"product_desc" => $product_desc,
			"return_url" => $return_url,
			"show_url" => $show_url,
			"redo_flag" => $redo_flag,
		);
		// print_r($queryList);
		// exit;
    	
		$url = "https://pay.yibaotown.com/gateway?input_charset=UTF-8";
		$ch = curl_init(); 
		curl_setopt($ch,CURLOPT_URL, $url);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch,CURLOPT_POST, true);
		curl_setopt($ch,CURLOPT_POSTFIELDS, http_build_query($queryList)); 
		curl_setopt($ch,CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch,CURLOPT_SSL_VERIFYPEER, 0);
		$result = curl_exec($ch); 
		curl_close($ch); 
		echo $result;
	}
	public function notify() {
		// log_message("error",json_encode($this->input->post()));
		$merchant_code	= $_POST["merchant_code"];	
		$interface_version = $_POST["interface_version"];
		$sign_type = $_POST["sign_type"];
		$dinpaySign = base64_decode($_POST["sign"]);
		$notify_type = $_POST["notify_type"];
		$notify_id = $_POST["notify_id"];
		$order_no = $_POST["order_no"];
		$order_time = $_POST["order_time"];	
		$order_amount = $_POST["order_amount"];
		$trade_status = $_POST["trade_status"];
		$trade_time = $_POST["trade_time"];
		$trade_no = $_POST["trade_no"];
		$bank_seq_no = $_POST["bank_seq_no"];
		// $extra_return_param = $_POST["extra_return_param"];
		/////////////////////////////   参数组装  /////////////////////////////////
		/**	
			除了sign_type dinpaySign参数，其他非空参数都要参与组装，组装顺序是按照a~z的顺序，下划线"_"优先于字母	
		*/
		$signStr = "";
		if($bank_seq_no != ""){
			$signStr = $signStr."bank_seq_no=".$bank_seq_no."&";
		}
		/*if($extra_return_param != ""){
			$signStr = $signStr."extra_return_param=".$extra_return_param."&";
		}*/
		$signStr = $signStr."interface_version=".$interface_version."&";	
		$signStr = $signStr."merchant_code=".$merchant_code."&";
		$signStr = $signStr."notify_id=".$notify_id."&";
		$signStr = $signStr."notify_type=".$notify_type."&";
    	$signStr = $signStr."order_amount=".$order_amount."&";	
    	$signStr = $signStr."order_no=".$order_no."&";	
    	$signStr = $signStr."order_time=".$order_time."&";	
    	$signStr = $signStr."trade_no=".$trade_no."&";	
    	$signStr = $signStr."trade_status=".$trade_status."&";
		$signStr = $signStr."trade_time=".$trade_time;
		//echo $signStr;
		/////////////////////////////   RSA-S验证  /////////////////////////////////
		global $plate_public_key;
		$public_key = openssl_get_publickey($plate_public_key);
		$flag = openssl_verify($signStr,$dinpaySign,$public_key,OPENSSL_ALGO_MD5);	
		///////////////////////////   响应“SUCCESS” /////////////////////////////
		if( !$flag ){		
			exit("Verification Error"); 
		}

		$pay_sdk = 'yibaotown';
		$this->pay_model->finish_order($pay_sdk,$order_no);
		echo "SUCCESS";	
	}
}
?>
