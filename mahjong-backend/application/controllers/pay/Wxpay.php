<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once APPPATH.'third_party/wxpay/notify.php';
require_once APPPATH.'third_party/wxpay/lib/WxPay.Api.php';
require_once APPPATH.'third_party/wxpay/example/WxPay.JsApiPay.php';
require_once APPPATH.'third_party/wxpay/example/log.php';

class Wxpay extends CI_Controller {

	function __construct(){
		parent::__construct();
        $this->load->helper('url');
        $this->load->model('pay_model');
        $this->load->model('gm_model');
	}
	
	public function index(){
		$data = array();
		//初始化日志
		$logHandler= new CLogFileHandler(APPPATH.'third_party/wxpay/logs/'.date('Y-m-d').'.log');
		$log = Log::Init($logHandler, 15);
		
		//①、获取用户openid 和 unionid
		$tools = new JsApiPay();
		$openId = $tools->GetOpenid();
		$unionid = $tools->data['unionid'];
				
		$attach = array(
				'unionid' => $unionid,
				'item_id' => 8106
		);
		$attach = json_encode($attach);
		//②、统一下单
		$input = new WxPayUnifiedOrder();
		$input->SetBody("首充488元，成为推广员");
		$input->SetAttach($attach);
		$input->SetOut_trade_no(WxPayConfig::MCHID.date("YmdHis"));
		$input->SetTotal_fee("1");
		$input->SetTime_start(date("YmdHis"));
		$input->SetTime_expire(date("YmdHis", time() + 600));
		$input->SetGoods_tag("test");
		$input->SetNotify_url("http://sundaymj.weilanhd.com/pay/wxpay/notify");
		$input->SetTrade_type("JSAPI");
		$input->SetOpenid($openId);
		$order = WxPayApi::unifiedOrder($input);
// 		echo '<font color="#f00"><b>统一下单支付单信息</b></font><br/>';
// 		$this->printf_info($order);
		$data['jsApiParameters'] = $tools->GetJsApiParameters($order);
		
		$this->load->view('pay/test.phtml', $data);		
		
		//获取共享收货地址js函数参数
		//$editAddress = $tools->GetEditAddressParameters();
		
		//③、在支持成功回调通知中处理成功之后的事宜，见 notify.php
		/**
		 * 注意：
		 * 1、当你的回调地址不可访问的时候，回调通知会失败，可以通过查询订单来确认支付是否成功
		 * 2、jsapi支付时需要填入用户openid，WxPay.JsApiPay.php中有获取openid流程 （文档可以参考微信公众平台“网页授权接口”，
		 * 参考http://mp.weixin.qq.com/wiki/17/c0f37d5704f0b64713d5d2c37b468d75.html）
		 */
	}
	
	public function notify(){
		// $json_str = '{"appid":"wxe6934b849448cebb","attach":"{\"unionid\":\"oghNsv-dgCmWz_dt92uwlWT8owAo\",\"item_id\":1028}","bank_type":"CFT","cash_fee":"1","fee_type":"CNY","is_subscribe":"Y",
		// "mch_id":"1404306702","nonce_str":"90ist6ips3afhs6k2qenf4errt55l4ib","openid":"oD4WqxLre2nINu-w9qxNUdZjPSYE","out_trade_no":"140430670220170314174705","result_code":"SUCCESS",
		// "return_code":"SUCCESS","sign":"817307890EDD21744BA333C5E2896449","time_end":"20170314174902","total_fee":"1","trade_type":"JSAPI","transaction_id":"4005392001201703143357663449"}';
		// $json_arr = json_decode($json_str,true);
		// print_r($json_arr);
		// exit;
		
		Log::DEBUG("begin notify");
		$notify = new PayNotifyCallBack();
		$notify->Handle(false);
	}
	
	//打印输出数组信息
	function printf_info($data){
		foreach($data as $key=>$value){
			echo "<font color='#00ff55;'>$key</font> : $value <br/>";
		}
	}
	
	
}

/* End of file welcome.php */
/* Location: ./system/application/controllers/welcome.php */
