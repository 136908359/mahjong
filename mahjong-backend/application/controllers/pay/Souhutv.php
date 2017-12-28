<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once APPPATH.'third_party/uuid.php';
require_once APPPATH.'third_party/access.php';

class Souhutv extends CI_Controller {
	private $app_id;
	private $app_secret;
	function __construct() {
		parent::__construct();
        $this->load->helper('url');
        $this->load->helper('errcode');
        $this->load->model('pay_model');
        $this->load->model('gm_model');
        $this->load->model('config_model');
	
		$this->app_id = $this->config_model->get("web","PaySouhutvAppId","Value");
		$this->app_secret = $this->config_model->get("web","PaySouhutvAppSecret","Value");
		if ( defined("PAY_SOUHUTV_APP_ID") ) {
			$this->app_id = PAY_SOUHUTV_APP_ID;
			$this->app_secret = PAY_SOUHUTV_APP_SECRET;
		}
		if ( !$this->app_id ) {
			exit("SouhuTV支付相关参数未配置");
		}
	}
	public function alipaywap() {
		$err = access();
		if ( $err != "SUCCESS" ) {
			exit(err_string($err));
		}
		$appId = $this->app_id; 
		$uid =  $this->input->get("uid");
		$item_id =  $this->input->get("itemId");
		$ret = $this->gm_model->request("/GetShopItemDetail", array("ItemId"=>intval($item_id)));
		$item = json_decode($ret->String);
		 
		$orderId = create_uuid(); 
		$amt = $item->ShopsPrice;
		$amt = intval($amt)*100; 
		$channelId=$this->input->get("chanId"); 
		$orderDesc=$item->ShopTitle; 
		$orderTime=time(); 


		if ( !$amt || !$channelId || !$orderDesc || !$uid || !$item_id) {
			exit(err_string("参数有误"));
		}
		 
		$param = array( 
			'appId'=>$appId, 
			'amt'=>$amt, 
			'orderTime'=>$orderTime, 
			'channelId'=>$channelId, 
			'orderId'=>$orderId, 
			'orderDesc'=>$orderDesc, 
			'callback_url'=>'',
			// 'payType'=>'unified.trade.pay', 
			'payType'=>'pay.alipay.wap', 
			'openId'=>'',
		); 
		$param['sign'] = $this->signature($param); 
		$queryList = http_build_query($param, null, '&'); 
		$url = 'https://pay.souhutv.net/order.php'; 
		$ch = curl_init(); 
		$opts = array(); 
		$opts[CURLOPT_RETURNTRANSFER] = true; 
		$opts[CURLOPT_TIMEOUT] = 30; 
		$opts[CURLOPT_POST] = 1; 
		$opts[CURLOPT_POSTFIELDS] = $queryList; 
		$opts[CURLOPT_USERAGENT] = 'pay-1.0'; 
		$opts[CURLOPT_URL] = $url; 
		curl_setopt_array($ch, $opts); 
		$result = curl_exec($ch); 
		curl_close($ch); 

		$errMsg = "服务器出错了"; 
		$params = array();
		if (false !== $result) { 
			$obj = json_decode($result); 
	   		if($obj && $obj->code == 0){ 
				$errMsg = "SUCCESS";
				$params["PayUrl"] = $obj->payUrl;
	   		}
	   	}
		echo err_string($errMsg,$params);

		$pay_sdk = 'souhutv';
		if ( $errMsg == "SUCCESS" ) {
			$order = array();
			$order['buy_uid'] = $uid;//uid
			$order['item_id'] = $item_id;//物品id
			$order['item_num'] = 1;//数量
			$order['rmb'] = $amt/100;//金额
			$order['chan_id'] = $channelId;//渠道号
			$order['create_time'] = date('Y-m-d H:i:s',time());
			$order['notify_time'] = date('Y-m-d H:i:s',time());
			$order['game'] = 'mahjong';
			$order['pay_sdk'] = $pay_sdk;//支付方式
			$order['order_id'] = $orderId;
			$this->pay_model->add_new_order($order);
		}
	}
	public function notify() {
		$data = $this->input->post();
		if ( !$data || empty($data['sign']) ) {
			exit('retry');
		}
		$sign = $data['sign'];
		$sign1 = $this->signature($data);
		if ( !strcasecmp($sign,$sign1) ) {
			exit('sign_error');
		}
		$appId = $data["appId"];
		$order_id = $data["orderId"];
		if ( $appId != $this->app_id ) {
			exit("appid_error");
		}

		$pay_sdk = 'souhutv';
		if ( $data["status"] == 1 ) {
			$this->pay_model->finish_order($pay_sdk,$order_id);
		}
		echo 'success';
	}
	private function signature($param) {
		$signParam = array(); 
		foreach ($param as $key=>$value) {
			array_push($signParam,$key);
		}
		sort($signParam); 
		$signStr = ''; 
		$i = 0; 
		foreach($signParam as $key){ 
			$signStr .= $key. "=" .$param[$key]; 
		} 
		$appSecret = $this->app_secret; 
		$signStr .= $appSecret; 
		$sign = md5($signStr);     
		return $sign; 
	}
}

