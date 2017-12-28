<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once APPPATH.'third_party/uuid.php';
class Apple extends CI_Controller {
	private $apk_pakcage_name;
	function __construct() {
		parent::__construct();
        $this->load->helper('url');
        $this->load->model('gm_model');
        $this->load->model('pay_model');
        $this->load->model('config_model');
		$this->apk_package_name = $this->config_model->get("web","ApkPackageName","Value");
		if ( defined("APK_PACKAGE_NAME") ) {
			$this->apk_package_name = APK_PACKAGE_NAME;
		}
		if ( !$this->apk_package_name ) {
			exit("苹果支付相关参数未配置");
		}
	}

	public function notify() {
		$ret = $this->check_notify();
		$s = json_encode($ret,JSON_UNESCAPED_UNICODE);
		echo $s;
	}

	private function check_notify() {//支付验证测试
		$uid = 0;
		$token = '';
		$order_id = '';
		$receipt = '';
		$rmb = 0;
		$raw_data =  file_get_contents("php://input");
		$data = json_decode($raw_data, TRUE);
		if(isset($data['uid']) ){
			$uid = intval($data['uid']);
		} else {
			return array("Code"=>1001,"Msg"=>"无效的uid");
		}
		if(isset($data['token']) ){
			$token = $data['token'];
		} else {
			return array("Code"=>1001,"Msg"=>"无效的token");
		}
		if(isset($data['price']) ){
			$rmb = floatval($data['price']);
		} else {
			return array("Code"=>1001,"Msg"=>"无效的price");
		}

		if(isset($data['receipt-data']) ){
			$receipt = $data['receipt-data'];
		} else {
			return array("Code"=>1001,"Msg"=>"无效的receipt-data");
		}

		$receipt = base64_encode($receipt);
		$receipt =json_encode(array('receipt-data' => $receipt));

		$test_url ="https://sandbox.itunes.apple.com/verifyReceipt";
		$url ="https://buy.itunes.apple.com/verifyReceipt";
       
		$response = $this->send_receipt($url,$receipt);
		if ($response->status == 21007) {
			$response = $this->send_receipt($test_url,$receipt);
		}
		if ($response->status != 0) {
			return array("Code"=>1,"Msg"=>"系统繁忙");
		}
		$bid = $response->receipt->bid;
		if (strpos($bid,$this->apk_package_name) === false) {
			return array("Code"=>2,"Msg"=>"服务器出错了");
		}
		$a = explode('_',$response->receipt->product_id);
		$item_id = intval(end($a));
		$item_num = 1;
		$order_id = $response->receipt->transaction_id;
		$pay_sdk = 'apple';
		$chan_id = 'apple';

		$this->load->model('pay_model');
		$order = $this->pay_model->get_order($pay_sdk,$order_id);
		if (empty($order) == false) {
			return array("Code"=>1001,"Msg"=>"订单失效");
		}
		// 增加订单
		$order = array();
		$order['buy_uid'] = $uid;//uid
		$order['item_id'] = $item_id;//物品id
		$order['item_num'] = $item_num;//数量
		$order['rmb'] = $rmb;//金额
		$order['chan_id'] = $chan_id;//渠道号
		$order['create_time'] = date('Y-m-d H:i:s',time());
		$order['notify_time'] = date('Y-m-d H:i:s',time());
		$order['game'] = 'mahjong';
		$order['pay_sdk'] = $pay_sdk;//支付方式
		$uuid = create_uuid();
		$order['order_id'] = $order_id;
		$ret = $this->pay_model->add_new_order($order);
		if ($ret == false) {
			return array("Code"=>4,"Msg"=>"系统繁忙");
		}

		$this->pay_model->finish_order($pay_sdk, $order_id);
		return array("Code"=>0);
	}
	private function send_receipt($url, $receipt) {
		$curl_handle=curl_init();
		curl_setopt($curl_handle,CURLOPT_URL, $url);
		curl_setopt($curl_handle,CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl_handle,CURLOPT_HEADER, 0);
		curl_setopt($curl_handle,CURLOPT_POST, true);
		curl_setopt($curl_handle,CURLOPT_POSTFIELDS, $receipt); 
		curl_setopt($curl_handle,CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($curl_handle,CURLOPT_SSL_VERIFYPEER, 0);
		$response_json =curl_exec($curl_handle);
		$response =json_decode($response_json);
		curl_close($curl_handle);
		return $response;
	}
}

/* End of file welcome.php */
/* Location: ./system/application/controllers/welcome.php */
