<?php

class Weixin_Model extends CI_Model{
	// private $mch_appid = "wx63c4744d94153864"; // 公众号APPID
	private $mch_appid; // 微信开放平台APPID
	private $mchid; // 商户ID
	private $api_key;

	function __construct() {
		parent::__construct();
		$this->load->database();
		$this->load->model("config_model");
		$this->mch_appid = $this->config_model->get("web","WxOpenAppId","Value");
		$this->mchid = $this->config_model->get("web","WxMerchantId","Value");
		$this->api_key = $this->config_model->get("web","WxMerchantSecret","Value");
		if ( defined("WX_OPEN_APP_ID") ) {
			$this->mch_appid = WX_OPEN_APP_ID;
			$this->mchid = WX_MCH_ID;
			$this->app_key = WX_MCH_API_KEY;
		}
		if ( !$this->mch_appid ) {
			exit("微信商户平台相关参数未定义");
		}
	}

	private function signature($params) {
		ksort($params);
		$s = "";
		foreach ($params as $k=>$v) {
			if ( $s ) {
				$s = "$s&$k=$v";
			} else {
				$s = "$k=$v";
			}
		}
		$s = $s."&key=".$this->api_key;
		$sign = md5($s);
		return strtoupper($sign);
	}
	
	/*
	 *  微信支付企业到账，存入微信零钱
	 *  openid 微信登陆时得到的openid
	 *  amount 到账金额，单位：元
	 *  user_name 用户真实姓名
	 *  desc 备注信息
	 */
	public function give_user_money($openid,$user_name,$amount,$desc) {
		require_once APPPATH.'third_party/uuid.php';

		$url = "https://api.mch.weixin.qq.com/mmpaymkttransfers/promotion/transfers";
		// $nonce_str = "6ab6450a2f2728cbb531fc92dc9f0862";
		// $trade_no = "D7DFFA2D-0D2C-477F-8BFC-404087B936AD";
		$nonce_str = time()+"-"+rand(0,65536);
		$trade_no = create_uuid();
		$trade_no = str_replace("-", "",$trade_no);
		$args = array(
			"mch_appid" => $this->mch_appid,
			"mchid"     => $this->mchid,
			"nonce_str" => $nonce_str,
			"openid"    => $openid,
			"amount"    => intval($amount*100),
			"desc"      => $desc,

			"check_name"       => "FORCE_CHECK",
			"re_user_name"     => $user_name,
			"partner_trade_no" => $trade_no,
			"spbill_create_ip" => GM_IP,
		);
		$sign = $this->signature($args);
		$args['sign'] = $sign;
		$xml = $this->array2xml($args);
		$s =$this->curl_post_ssl($url,$xml,10);
		$xml = simplexml_load_string($s);
		if ( !$xml ) {
			return "系统错误";
		}
		if ($xml->result_code == "SUCCESS") {
			return "SUCCESS";
		}
		return $xml->err_code_des;
	}

	public function array2xml($data) {
		$xml = "";
		foreach ($data as $key=>$val) {
			if (is_numeric($val)) {
				$xml = $xml."<$key>$val</$key>";
			} else {
				$xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
				// $xml = $xml."<$key>$val</$key>";
			}
		}
		return "<xml>{$xml}</xml>";
	}

	public function curl_post_ssl($url, $vars, $second=30,$headers=array()) {
		$ch = curl_init();
		//超时时间
		curl_setopt($ch,CURLOPT_TIMEOUT,$second);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER, 1);
		//这里设置代理，如果有的话
		//curl_setopt($ch,CURLOPT_PROXY, '10.206.30.98');
		//curl_setopt($ch,CURLOPT_PROXYPORT, 8080);
		curl_setopt($ch,CURLOPT_URL,$url);
		curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
		curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,false);
		
		//以下两种方式需选择一种
		
		$path = APPPATH.'third_party';
		// $path = getcwd();
		//第一种方法，cert 与 key 分别属于两个.pem文件
		//默认格式为PEM，可以注释
		// curl_setopt($ch,CURLOPT_SSLCERTTYPE,'PEM');
		curl_setopt($ch,CURLOPT_SSLCERT,$path.'/apiclient_cert.pem');
		//默认格式为PEM，可以注释
		// curl_setopt($ch,CURLOPT_SSLKEYTYPE,'PEM');
		curl_setopt($ch,CURLOPT_SSLKEY,$path.'/apiclient_key.pem');
		curl_setopt($ch,CURLOPT_CAINFO,$path.'/rootca.pem');
		
		//第二种方式，两个文件合成一个.pem文件
		// curl_setopt($ch,CURLOPT_SSLCERT,getcwd().'/all.pem');
 
		if( count($headers) >= 1 ){
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		}
 
		curl_setopt($ch,CURLOPT_POST, 1);
		curl_setopt($ch,CURLOPT_POSTFIELDS,$vars);
		$data = curl_exec($ch);
		curl_close($ch);
		return $data;
	}
}
