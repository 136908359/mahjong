<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once APPPATH.'third_party/uuid.php';
require_once APPPATH.'third_party/access.php';

class Tencent extends Access_Controller {
	private $appId,$appKey,$testKey,$sdk;
	public function __construct() {
		parent::__construct ();
		$this->load->model('gm_model');
		$this->load->model('pay_model');
		$this->load->model('config_model');

		$this->appId = $this->config_model->get("web","PayTencentAppId","Value");
		$this->appKey = $this->config_model->get("web","PayTencentAppKey","Value");
		$this->testKey = $this->config_model->get("web","PayTencentAppTestKey","Value");
		if ( defined("PAY_TENCENT_APP_ID") ) {
			$this->appId = PAY_TENCENT_APP_ID;
			$this->appKey = PAY_TENCENT_APP_KEY;
			$this->testKey = PAY_TENCENT_APP_TEST_KEY;
		}
		if ( !$this->appId ) {
			exit("腾讯支付相关参数未设置");
		}
	}
	public function request($uri,$params,$opt) {
		$session_id = $params['session_id'];
		$openId = $params['open_id'];
		$openKey = $params['open_key'];
		$pf = $params['pf'];
		$pfKey = $params['pf_key'];
		$zoneid = $params['zone_id'];

		$host = "ysdk.qq.com";
		$appKey = $this->appKey;
		// 测试环境
		if (empty($appKey)) {
			$appKey = $this->testKey;
			$host = "ysdktest.qq.com";
		}
		$url = "https://".$host.$uri;
		$ts = time();

		$appId = $this->appId;
		$session_type = "";
		if ($session_id == "openid") {
			$session_type = "kp_actoken";
		} else if ($session_id == "hy_gameid") {
			$session_type = "wc_actoken";
		} else {
			exit('404 Not Found');
		}
		
		$curl = curl_init();
		$cookies = array(
			"org_loc" => $uri,
			"session_id" => $session_id,
			"session_type" => $session_type,
		);
		$data = array(
			"appid" => $appId,
			// "format" => $format,
			"openid" => $openId,
			"openkey" => $openKey,
			"pf" => $pf,
			"pfkey" => $pfKey,
			"ts" => $ts,
			// "userip" => $userip,
			"zoneid" => $zoneid,
		);
		$data = array_merge($data,$opt);
		ksort($data);
		$s = "GET"."&".rawurlencode('/v3/r'.$uri)."&".rawurlencode(http_build_query($data));
		$key = $appKey."&";
		$sig = hash_hmac("sha1",$s,$key,TRUE);
		$sig = base64_encode($sig);
		$data['sig'] = $sig;

		$url = $url.'?'.http_build_query($data);
		// echo $url;
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_HEADER, 0);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($curl, CURLOPT_TIMEOUT, 4);
		$s = http_build_query($cookies,'',';');
		curl_setopt($curl, CURLOPT_COOKIE, $s);
		$ret = curl_exec($curl);
		curl_close($curl);
		// echo $ret;
		return json_decode($ret);
	}

	/**
	 * 腾讯支付成功接口
	 */
	public function onNotify() {
		$data = $_POST;
		$order_id = create_uuid();
		$uid = $data['uid'];
		$item_id = intval($data['item_id']);
		$item_num = intval($data['item_num']);
		$chan_id = $data['chan_id'];
		$rmb = floatval($data['rmb']);
		$uid = intval($data['uid']);

		$pay_sdk = $this->sdk;
		$order = $this->pay_model->get_order($pay_sdk,$order_id);
		if (empty($order) == false) {
			exit("订单已存在");
		}

		// 创建订单
		$order = array();
		$order['buy_uid'] = $uid;
		$order['item_id'] = $item_id;
		$order['item_num'] = 1;
		$order['pay_sdk'] = $pay_sdk;
		$order['rmb'] = $rmb;
		$order['chan_id'] = $chan_id;
		$order['create_time'] = date('Y-md-d H:i:s');
		$order['game'] = 'mahjong';
		$order['order_id'] = $order_id;
		$this->pay_model->add_new_order($order);
	
		$data['zone_id'] = 1;
		for($i=0; $i<5; $i++) {
			$obj = $this->request("/mpay/get_balance_m",$data,array());
			if ($obj->ret != 0) {
				exit("充值异常");
			}
			if ($obj->save_amt > 0) {
				break;
			}
			if ($i+1 == 5) {
				exit("充值异常");
			}
			sleep(5);
		}

		$obj = $this->request("/mpay/pay_m",$data,array("amt"=>$rmb,"billno"=>$order_id));
		if ($obj->ret != 0) {
			exit("余额不足");
		}
		// 更新订单状态
		$this->pay_model->finish_order($pay_sdk, $order_id);
		echo "SUCCESS";
	}
	// 正式服充值回调
	public function notify() {
		$this->sdk = 'tencent';
		$this->onNotify();
	}
}
?>

