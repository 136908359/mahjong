<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once APPPATH.'third_party/uuid.php';
require_once APPPATH.'third_party/access.php';

class Beifubao extends CI_Controller {
	private $para_id;
	private $app_id;
	private $app_name;
	private $app_secret;
	function __construct() {
		parent::__construct();
        $this->load->helper('url');
        $this->load->helper('errcode');
        $this->load->model('pay_model');
        $this->load->model('gm_model');
        $this->load->model('config_model');

		$this->para_id = $this->config_model->get("web","PayBeifubaoParaId","Value");
		$this->app_id = $this->config_model->get("web","PayBeifubaoAppId","Value");
		$this->app_name = $this->config_model->get("web","PayBeifubaoAppName","Value");
		$this->app_secret = $this->config_model->get("web","PayBeifubaoAppSecret","Value");
		if ( defined("PAY_BEIFUBAO_APP_ID") ) {
			$this->para_id = PAY_BEIFUBAO_PARA_ID;
			$this->app_id = PAY_BEIFUBAO_APP_ID;
			$this->app_name = PAY_BEIFUBAO_APP_NAME;
			$this->app_secret = PAY_BEIFUBAO_APP_SECRET;
		}
		if ( !$this->para_id ) {
			exit("贝付宝支付相关参数未设置");
		}
	}
	public function wxwap() {
		$err = access();
		if ( $err != RETURN_SUCCESS ) {
			exit(err_string($err));
		}
		$order_id = create_uuid(); 
		$data = $this->input->get();
		$para_id = $this->para_id;
		$app_id = $this->app_id;

		$uid = $this->input->get("uid");
		$item_id = $this->input->get("itemId");
		$chan_id = $this->input->get("chanId");
		$rmb = $this->config_model->get("Shop",$item_id,"ShopsPrice");
		$title = $this->config_model->get("Shop",$item_id,"ShopTitle");

		if ( !$uid || !$item_id ) {
			die(err_string(err_code(1001,"参数无效")));
		}

		$data = array();
		$data['para_id'] = $para_id;
		$data['app_id'] = $app_id;
		$data['total_fee'] = 100.0*$rmb;
		$data['order_no'] =	$order_id; 
		$data["notify_url"] = GM_HOST."/pay/beifubao/notify";
		$data["returnurl"] = "";
		$data["attach"] = "";
		$data['body'] = $title;
		$data['type'] = 2;
		$data["device_id"] = "1";
		$data["child_para_id"] = "1";
		$data["mch_create_ip"] = $this->input->ip_address();
		$data["mch_app_id"] = "http://www.52duobaoleyuan.cn/";
		$data["mch_app_name"] = $this->app_name;
		$data["userIdentity"] = $uid;
		$data["child_para_id"] = "unknow";

		$appSecret = $this->app_secret; 
		$signStr = $para_id.$app_id.$order_id.$data["total_fee"].$appSecret; 
		$data['sign'] = md5($signStr); 
		$s = http_build_query($data, null, '&'); 
		$url = "http://pay.payfubao.com/sdk_transform/wx_wap_api";

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_TIMEOUT, 200);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
 		curl_setopt($ch, CURLOPT_POST, 1);
 		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $s);
		$result = curl_exec($ch);

		$params = array();
		$errcode = err_code(1001,"系统出错了");
		if (false !== $result) { 
			$obj = json_decode($result); 
	   		if($obj && $obj->status == 0){ 
				$errcode = err_code(0);
				$params["PayUrl"] = $obj->pay_url;
	   		}
	   	}
		echo err_string($errcode,$params);

		$pay_sdk = 'beifubao';
		if ( $errcode["Code"] == 0 ) {
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
			$order['order_id'] = $order_id;
			$this->pay_model->add_new_order($order);
		}
	}
	public function notify() {
		$order_id = $this->input->get("orderno");
		$fee = $this->input->get("fee");
		$sign = $this->input->get("sign");
		$app_id = $this->input->get("app_id");
		log_message("error",http_build_query($this->input->get(),null,"&"));
		/*
		$s = '{"pay_type":"0","wxno":"0004751509703948950452386559","app_id":"11343","orderno":"993E6719-55BC-49D4-B5DE-2AD5F1117E6A","fee":"100","token":"71c57391a8a7a9f9416def0a0bab63fd","sign":"ad5b956bcc5385a9d8d431f078ead50e","attach":""}';
		$obj = json_decode($s);
		$order_id = $obj->orderno;
		$fee = $obj->fee;
		$sign = $obj->sign;
		$app_id = $obj->app_id;
		*/
		if ( !$sign || $app_id != $this->app_id ) {
			$err = err_string(err_code(1002,"参数有误"));
			die($err);
		}
		$appSecret = $this->app_secret; 
		$signStr = $order_id.$fee.$appSecret; 
		$sign1 = md5($signStr);
		if ( strcasecmp($sign,$sign1) ) {
			$err = err_string(err_code(1003,"签名错误"));
			die($err);
		}

		$pay_sdk = 'beifubao';
		$this->pay_model->finish_order($pay_sdk,$order_id);
		echo "ok";
	}
}

