<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once APPPATH.'third_party/uuid.php';

class game5543 extends CI_Controller {
	function __construct()
	{
		parent::__construct();
        $this->load->helper('url');
        $this->load->model('pay_model');
        $this->load->model('gm_model');
	}
		
	public function notify() {
		$appId = "56";
		// log_message("error","test");
		$data = $_POST;
		$raw = json_encode($data);
		// log_message("error",$raw);
		// log_message("error",base64_encode($raw));
		if (empty($data)) {
			echo 'Invalid Request';
			// log_message("error","invalid request");
			return;
		}
		if ($data["appId"] != $appId) {
			echo 'Invaild Argument(appId)';
			// log_message("error","invalid appid");
			return;
		}
		$sign = $data["sign"];
		unset($data["sign"]);
		// $sign = "4ddd63c773022ff94912598f5bd645d5";
		if ($sign != $this->signature($data)) {
			// echo $this->signature($data);
			echo 'Invalid Argument(sign)';
			// log_message("error","invalid sign");
			return;
		}
		echo "OK";
		// log_message("error","ok");
		$order_id = $data["cpCode"];
		$rmb = floatval($data["money"]);
		$pay_sdk = '5543';

		/*
		$ext = json_decode($data["extInfo"]);
		$uid = intval($ext->uid);
		$chan_id = $ext->chanid;
		$item_id = intval($ext->itemid);
		*/
		$ext = explode("___",$data["extInfo"]);
		$item_id = intval($ext[0]);
		$chan_id = $exit[1];
		$uid = intval($ext[2]);
		// $rmb = 6;

		$order = $this->pay_model->get_order($pay_sdk,$order_id);
		// 订单已存在
		if (empty($order) == false) {
			// echo 'Existed Order';
			return;
		}
		
		// 创建订单
		$order = array();
		$order['buy_uid'] = $uid;// uid
		$order['item_id'] = $item_id;// 物品id
		$order['item_num'] = 1;// 数量
		$order['rmb'] = $rmb;// 金额
		$order['chan_id'] = $chan_id;// 渠道号
		$order['create_time'] = date('Y-m-d H:i:s',time());
		$order['notify_time'] = date('Y-m-d H:i:s',time());
		$order['game'] = 'mahjong';
		$order['pay_sdk'] = $pay_sdk;// 支付方式
		$order['order_id'] = $order_id;
		$this->pay_model->add_new_order($order);
		$this->pay_model->finish_order($pay_sdk, $order_id);
	}
	protected function signature($data) {
		$appKey = "5543ermj453ere";
		// $appKey = "abcdef";
		ksort($data);
		$s = http_build_query($data,"","&");
		$s = $s.$appKey;
		$sign = md5($s);
		return $sign;
	}
	
	public function test() {
		$a = array(
			"appid" => "123456",
			"sparams1" => "p1",
			"fparams2" => "p2",
			"wparams3" => "p3",
			"aparams4" => "p4",
	 	);
		$ext = explode("___","2401___test___1001");
		$item_id = intval($ext[0]);
		$chan_id = $ext[1];
		$uid = intval($ext[2]);
		echo "$item_id<br>$chan_id<br>$uid<br>";
		echo $this->signature($a);
	}
	
}

/* End of file welcome.php */
/* Location: ./system/application/controllers/welcome.php */
