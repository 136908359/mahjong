<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once APPPATH.'third_party/uuid.php';

class cypay extends CI_Controller {
	function __construct() {
		parent::__construct();
        $this->load->helper('url');
        $this->load->model('pay_model');
        $this->load->model('gm_model');
	}

	public function notify() {
		// APP ID
		$app_id = "1483068048709351";
		$app_secret = "zsfbddffFPMvlikStBDLL9m8qhpNF5vC";
		//公钥字符串
		$public_key = "-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA5SCpdyndz+nd9NUBSTKA
Klo9AdFxH0FaaoOHE+fI7yLPktyeIfNJr1fkbolyO11WHxnyK0dZbUupq3skjDJV
sr89QXMWm+cj54RcBOObfUU9zoZ/9lDGyoCaKPesADuWCf5zz89Km9hByV6ZuFcN
BlyRBmQDqqASfVgltMpXyDgt9ErPDdn+I+6y6OOO1N0tLT3U/skOh6zoU/zfyVtV
hSNm1+2Vv3IWqQb7YMwB1AUPke8VFejTXxE/pq3rKkQ0IO2qNOYmJTOs+V3r1/OO
Llxq4Bd82sFCZKf9VlZweOfisj4sBKJ+of6meV2LOm5zN/Xc1281wE3eln1aeWCZ
JQIDAQAB
-----END PUBLIC KEY-----";
		//公钥字符串转化资源
		$pu_key = openssl_pkey_get_public($public_key);
		//获取到回调数据
		$encrypted =  file_get_contents("php://input"); 
		// log_message("error", "recv");
		// log_message("error", $encrypted);
		// log_message("error", base64_encode($encrypted));
		// log_message("error", "recv ok");
		//解密密码，为空（不可更改）
		$decrypted = ""; 
		//rsa解密  
		openssl_public_decrypt(base64_decode($encrypted),$decrypted,$pu_key);//私钥加密的内容通过公钥可用解密出来  
		//回调数据
		// echo $decrypted; 
		// log_message("error", "here");
		// log_message("error", $decrypted);
		$obj = json_decode($decrypted);
		if (empty($obj->orderId) == true) {
			die("参数无效");
		}
		$pay_sdk = 'cypay';
		$order_id = $obj->orderId;
		$rmb = floatval($obj->orderAtm);
		$order = $this->pay_model->get_order($pay_sdk,$order_id);
		if (empty($order) == false) {
			exit("订单无效");
		}

		$details = json_decode($obj->orderdetail);
		if (empty($details) == true) {
			die("参数无效");
		}

		echo 'CYSuccess';
		$uid = intval($details->uid);
		$item_id = intval($details->item_id);
		$chan_id = $details->chan_id;
		// 创建订单
		$order = array();
		$order['buy_uid'] = $uid;
		$order['item_id'] = $item_id;
		$order['item_num'] = 1;
		$order['pay_sdk'] = $pay_sdk;
		$order['rmb'] = $rmb;
		$order['chan_id'] = $chan_id;
		$order['create_time'] = date('Y-m-d H:i:s');
		$order['game'] = 'mahjong';
		$order['order_id'] = $order_id;
		$this->pay_model->add_new_order($order);
	
		$this->pay_model->finish_order($pay_sdk, $order_id);
	}
}

/* End of file welcome.php */
/* Location: ./system/application/controllers/welcome.php */
