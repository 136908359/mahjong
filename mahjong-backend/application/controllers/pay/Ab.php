<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once APPPATH.'third_party/abpay/base.php';

class ab extends CI_Controller {
	private $apple_app_id,$apple_app_platpkey;
	private $android_app_id,$android_app_platpkey;
	function __construct() {
		parent::__construct();
        $this->load->helper('url');
        $this->load->model('gm_model');
        $this->load->model('pay_model');
        $this->load->model('config_model');

		$this->apple_app_id = $this->config_model->get("web","PayAbAppleAppId","Value");
		$this->apple_app_platpkey = $this->config_model->get("web","PayAbAppleAppPlatpkey","Value");
		$this->android_app_id = $this->config_model->get("web","PayAbAndroidAppId","Value");
		$this->andorid_app_platpkey = $this->config_model->get("web","PayAbAndoridAppPlatpkey","Value");
		if ( defined("PAY_AB_ANDROID_APP_ID") ) {
			$this->android_app_id = PAY_AB_ANDROID_APP_ID;
			$this->android_app_platpkey = PAY_AB_ANDROID_APP_PLATPKEY;
			$this->apple_app_id = PAY_AB_APPLE_APP_ID;
			$this->apple_app_platpkey = PAY_AB_APPLE_APP_PLATPKEY;
		}
	}
	
	public function android_notify() {
		$this->notify($this->android_app_id,$this->android_app_platpkey);
	}

	public function apple_notify() {
		$this->notify($this->apple_app_id,$this->apple_app_platpkey);
	}
	
	public function android_notify_pvt() {
		$this->notify($this->android_app_id,$this->android_app_platpkey);
	}

	public function apple_notify_pvt() {
		$this->notify($this->apple_app_id,$this->apple_app_platpkey);
	}


	protected function notify($appid,$platpkey) {
		// log_message("error","test");
		$data = $_POST;
		$raw = json_encode($data);
		// log_message("error",$raw);
		log_message("error",base64_encode($raw));
		if (empty($data)) {
			exit('Invalid Request');
		}
		$transdata = $data["transdata"];
		if (stripos("%22",$transdata)) {
			$data = array_map('urldecode',$data);
		}
		$respData = 'transdata='.$data['transdata'].'&sign='.$data['sign'].'&signtype='.$data['signtype'];
		if (!parseResp($respData,$platpkey,$respJson)) {
			exit('Invalid Sign');
		}
		$transdata = $data['transdata'];
		$obj = json_decode($transdata);
		$result = $obj->result;
		if ($result != 0) {
			exit("Pay Fail");
		}
		echo 'SUCCESS';

		$appid = $obj->appid;
		$uid = intval($obj->appuserid);
		$order_id = $obj->cporderid;
		$cpprivate = $obj->cpprivate;
		$rmb = $obj->money;
		$transid = $obj->transid;
		$transtime = $obj->transtime;
		$waresid = $obj->waresid;
		// $rmb = 6;

		$obj = json_decode($cpprivate);
		$item_id = intval($obj->itemid);
		$chan_id = $obj->chanid;
		$pay_sdk = "abpay";
		$order = $this->pay_model->get_order($pay_sdk,$order_id);
		if (empty($order) == false) {
			exit("订单已存在");
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
}

/* End of file welcome.php */
/* Location: ./system/application/controllers/welcome.php */
