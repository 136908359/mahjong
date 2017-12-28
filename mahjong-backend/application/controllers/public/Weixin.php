<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Weixin extends CI_Controller {
	private $appId;
	private $appSecret;
	private $url = GM_HOST; // "http://www.bestmeide.com";
	// private $appId = "wx1e0a8cb999f9019d";
	// private $appSecret= "dae18b8990da312858d400eef419bc0d";

	function __construct() {
		parent::__construct();
        $this->load->helper('url');
        $this->load->library('session');
        $this->load->model("share_model");
		$this->load->model("config_model");
		$this->appId = $this->config_model->get("web","WxMPAppId","Value");
		$this->appSecret = $this->config_model->get("web","WxMPAppSecret","Value");
		if ( defined("WEIXIN_MP_APP_ID") ) {
			$this->appId = WEIXIN_MP_APP_ID;
			$this->appSecret = WEIXN_MP_APP_SECRET;
		}
		if ( !$this->appId ) {
			exit("微信公众平台参数未设置");
		}
	}
		
	// 登陆微信推广后台
	public function index(){
		$appid = $this->appId;
		$secret = $this->appSecret;

		$state = json_encode(array("from" => "index"));
		$state = base64_encode($state);
		$state = urlencode($state);

		$uri = urlencode($this->url."/public/weixin/auth");
		$url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid={$appid}&redirect_uri={$uri}&response_type=code&scope=snsapi_userinfo&state={$state}#wechat_redirect";

		redirect($url);
	}
	// 分享自动绑定
	public function share(){
		$appid = $this->appId;
		$secret = $this->appSecret;

		// 没有分享人推广码，直接跳转到下载地址
		$code = $this->input->get('code');
		if ( !$code ) {
			redirect(SHARE_APK_URL);
		}
		$state = json_encode(array("code" => $code,"from"=>"share"));
		$state = base64_encode($state);
		$state = urlencode($state);
		$uri = urlencode($this->url."/public/weixin/auth");
		$url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid={$appid}&redirect_uri={$uri}&response_type=code&scope=snsapi_userinfo&state={$state}#wechat_redirect";

		redirect($url);
	}

	/* 
	 * 自动绑定openid
	 */	
	public function auth(){
		//第二步：通过code换取网页授权access_token，获取openid		
		$code = $this->input->get('code');
		$state = $this->input->get('state');
		if( !$code ){
			redirect(SHARE_APK_URL);
		}

		$appid = $this->appId;
		$secret = $this->appSecret;
		$url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid={$appid}&secret={$secret}&code={$code}&grant_type=authorization_code";
		$obj = $this->request($url);

		if ( !$obj || empty($obj->unionid) ) {
			redirect(SHARE_APK_URL);
		}
		$unionid = $obj->unionid;//此openid为微信unionid		

		$s = urldecode($state);
		$s = base64_decode($s);
		$obj = json_decode($s);

		$from = "share";
		if ( empty($obj->from) == false ) {
			$from = $obj->from;
		}
		$uid = $this->share_model->get_uid_by_openid($unionid);
		// 登陆推广后台
		if ( $from == "index" && $uid ) {
			$this->session->set_userdata("uid", $uid);
			redirect("/public/share");
		} 
		if ( $from == "share" && empty($obj->code) == false ) {
			$share_code = $obj->code;
			// 玩家存在
			if ( $uid) {
				$this->share_model->bind_code($uid, $share_code);
			} else {
				$parent_uid = $this->share_model->get_uid_by_code($share_code);
				if ( $parent_uid ) {
					$this->share_model->bind_weixin_unionid($parent_uid, $unionid);
				}
			}
		}
		redirect(SHARE_APK_URL);
	}

	private function request($url) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);		
		$s = curl_exec($ch);
		curl_close($ch);		
		return json_decode($s);		
	}
}
