<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once APPPATH.'third_party/uuid.php';
require_once APPPATH.'third_party/access.php';

class Order extends Access_Controller {
	function __construct() {
		parent::__construct();
	}

	// 创建订单
	public function add () {
		$data = $this->input->get();
		if (empty($data) == true) {
			$data = $this->input->post();
		}
		/*
		$data = array();
		$data['uid'] = 1;
		$data['item_id'] = 1000;
		$data['item_num'] = 1;
		$data['rmb'] = 6;
		$data['chan_id'] = 'wx';
		$data['ts'] = time();
		$data['pay_sdk'] = 'sdk';
		$data['sign'] = 'sign';
		$data['token'] = 'token';
		*/
		
		$this->load->model('pay_model');
		$keys = array('uid','item_id','rmb','chan_id','ts','pay_sdk');
		foreach ($keys as $key) {
			if (empty($data[$key]) == true) {
				$msg = array("Code"=>1001,"Msg"=>"无效的$key");
				die(json_encode($msg,JSON_UNESCAPED_UNICODE));
			}
		}

		$order = array();
		$params = $data;
		$order_id = create_uuid();
		$order['buy_uid'] = $params['uid'];//uid
		$order['item_id'] = $params['item_id'];//物品id
		$order['item_num'] = 1;//数量
		$order['rmb'] = $params['rmb'];//金额
		$order['chan_id'] = $params['chan_id'];//渠道号
		$order['create_time'] = date('Y-m-d H:i:s',time());
		$order['notify_time'] = date('Y-m-d H:i:s',time());
		$order['game'] = 'mahjong';
		$order['pay_sdk'] = $params['pay_sdk'];//支付方式
		$order['order_id'] = $order_id;

		$msg = array("Code"=>4,"Msg"=>"系统繁忙");
		if ($this->pay_model->add_new_order($order) == true) {
			$msg = array("Code"=>0,"order_id"=>$order_id);
		}
		echo json_encode($msg,JSON_UNESCAPED_UNICODE);
	}
}
