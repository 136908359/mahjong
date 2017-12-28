<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once APPPATH.'third_party/uuid.php';
class Test extends CI_Controller {

	function __construct()
	{
		parent::__construct();
        $this->load->helper('url');
        $this->load->model('pay_model');
        $this->load->model('gm_model');

		if (ENVIRONMENT == 'production') {
			exit('404 Not Found');
		}
	}

	public function notify($uid,$item_id,$rmb) {//支付验证测试
		$uid = intval($uid);
		$rmb = floatval($rmb);
		$item_id = intval($item_id);
		$order_id = create_uuid();
		$item_num = 1;
		$pay_sdk = 'test';
		$chan_id = 'test';

		$this->load->model('pay_model');
		// 增加订单
		$order = array();
		$order['buy_uid'] = $uid;//uid
		$order['item_id'] = $item_id;//物品id
		$order['item_num'] = $item_num;//数量
		$order['rmb'] = $rmb;//金额
		$order['chan_id'] = $chan_id;//渠道号
		$order['create_time'] = date('Y-m-d H:i:s',time());
		$order['notify_time'] = date('Y-m-d H:i:s',time());
		$order['result'] = 3; // 新订单
		$order['game'] = 'mahjong';
		$order['pay_sdk'] = $pay_sdk;//支付方式
		$uuid = create_uuid();
		$order['order_id'] = $order_id;
		$ret = $this->pay_model->add_new_order($order);

		$this->pay_model->finish_order($pay_sdk, $order_id);
	}
}

/* End of file welcome.php */
/* Location: ./system/application/controllers/welcome.php */
