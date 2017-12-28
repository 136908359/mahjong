<?php

require_once APPPATH.'third_party/uuid.php';
class Plate extends CI_Controller{
    public function __construct(){
        parent::__construct();
		$this->load->helper("url");
		$this->load->helper("errcode");
		
		$sign = $this->input->get_request_header("PlateSign");
		$requestId = $this->input->get_request_header("PlateRequest");
		if ( $sign == GM_SIGN && $requestId ) {
			$ssid = $requestId;
		} else {
			$uid = $this->uri->segment(3);
			if ( !$uid ) {	
				$uid = $this->input->get("uid");
			}
			$uid = intval($uid);		
			if ( !$uid ) {
				exit("404 Not Found");
			}
			$token = $this->uri->segment(4);
			if ( !$token ) {
				$token = $this->input->get("token");
			}
			$this->load->model("gm_model");
			$ret = $this->gm_model->request("/GetToken", array("UId"=>$uid));
			if (strcmp($token,$ret->String)) {
				exit("无效的会话");
			}
			$ssid = $token;
		}
		
		session_id($ssid);
		$this->load->library("session");

		$this->load->model("plate_model");
		$this->load->model("weixin_model");
		$this->load->model("share_model");
		$this->load->model("pay_model");
		$this->load->model("marketing_model");
    }
    
    // 创建玩家
	// open_id是游戏内玩家open_id，区别微信的open_id
    public function create_user($uid,$token,$open_id,$chan_id){
    	// 通过玩家open_id绑定分享人
    	$this->share_model->bind_by_openid($uid,$open_id);  	

		// TODO
   	}

	// 登陆信息
	public function get_info($uid=0, $token='') {
		if ( !$uid ) {
			$uid = $this->input->get("uid");
		}
		$verify_info = $this->plate_model->get_verify_info($uid);
		$data = array("Verify"=>$verify_info);

		$marketing_info = $this->marketing_model->get_user_info($uid);
		$parent_uid = @$marketing_info["parent_uid"];
		if ( $parent_uid ) {
			$parent = $this->marketing_model->get_user_info($parent_uid);
			$data["Marketing"] = array("parent_uid"=>intval($parent_uid),"parent_code"=>$parent["code"]);
		}
		echo json_encode($data);
	}

	// 个人信息
	public function get_person_info ($uid, $token) {
		$info = $this->plate_model->get_person_info($uid);
		echo json_encode($info);
	}
	
	// 游戏录像数据
	public function av ($uid, $token, $replay_id) {
		header("Content-Encoding: gzip");
		$data = $this->plate_model->get_av($replay_id);
		echo $data;
	}

	function sms() {
		// 验证码
		$src = $this->input->get("src");
		$sign = $this->input->get("sign");
		$phone = $this->input->get("phone");
		$send_time = $this->session->userdata("vcode_send_time");
		
		if ($send_time && $send_time > time()) {
			$sec = $send_time-time();
			die(err_string("短信已发送",array("Sec"=>$sec)));
		}

		$vcode = mt_rand(100000,999999);
		if ( !$src ) {
			require_once APPPATH.'third_party/netease_im.php';

			$this->load->model('config_model');
			$AppKey = $this->config_model->get("web","NeteaseIMAppKey","Value");
			$AppSecret = $this->config_model->get("web","NeteaseIMAppSecret","Value");
			$template_id = $this->config_model->get("web","NeteaseIMTemplateId","Value");
			if ( defined("NETEASE_IM_APP_KEY") ) {
				$AppKey = NETEASE_IM_APP_KEY;
				$AppSecret = NETEASE_IM_APP_SECRET;
			}
			if ( !$template_id ) {
				$template_id = 3049390;
			}
			$p = new ServerAPI($AppKey,$AppSecret,'curl');
			$a = $p->sendSmsCode($phone,$template_id,'');
			if ($a["code"] != "200") {
				die(err_string("发送失败"));
			}
			$vcode = $a["obj"];
		} else if ( $src == "253") {
			require_once APPPATH.'third_party/ChuanglanSmsHelper/ChuanglanSmsApi.php';
			$clapi  = new ChuanglanSmsApi();
			$result = $clapi->sendSMS($phone, "【{$sign}】您好，您的验证码是". $vcode);

			if (!is_null(json_decode($result))) {
				$output = json_decode($result,true);
				if ( isset($output['code'])  && $output['code']=='0') {
					// echo '短信发送成功！' ;
				} else {
					die(err_string($output['errorMsg']));
				}
			} else {
				die(err_string($result));
			}
		}

		echo err_string("SUCCESS",array("Sec"=>120));
		$this->session->set_userdata("vcode", $vcode);
		$this->session->set_userdata("vcode_phone", $phone);
		$this->session->set_userdata("vcode_expire_time", time()+600);
		$this->session->set_userdata("vcode_send_time", time()+120);
	}
	// 实名认证
	public function verify_info () {
		$uid = $this->input->get("uid");
		$real_name = $this->input->get("real_name");
		$id_card = $this->input->get("id_card");
		$phone = $this->input->get("phone");
		$vcode = $this->input->get("vcode");
		$real_name = urldecode($real_name);

		if ( $this->plate_model->is_phone_bind($phone) ) {
			die(err_string("手机号已绑定"));
		}

		// 验证码
		$vcode1 = $this->session->userdata("vcode");
		$phone1 = $this->session->userdata("phone");
		$expire_time = $this->session->userdata("vcode_expire_time");
		if ( !$expire_time || $expire_time < time() || $vcode != $vcode1 || $phone != $phone1 ) {
			die(err_string("验证码无效或已过期"));
		}

		$msg = $this->plate_model->verify_info($uid,$real_name,$id_card,$phone);
		if ( $msg == "SUCCESS" ) {
			$this->load->model('gm_model');
			$this->load->model('config_model');
			$s = $this->config_model->get("config","VerifyRealName","Value");
			$items = $this->config_model->parse_items($s);
			$this->gm_model->request('/AddItems',array("UId"=>intval($uid),"GUID"=>create_uuid(),"Way"=>"verify","Items"=>$items));
		}
		echo err_string($msg);
	}
	// 总战绩
	public function get_union_grades () {
		header("Content-Encoding: gzip");
		$uid = $this->input->get("grade_uid");
		$grades = $this->plate_model->get_union_grades($uid);
		$data = array("Grades"=>$grades);
		echo gzencode(json_encode($data));
	}
	// 单独的的战绩，比如4局、8局、16局
	public function get_alone_grades () {
		header("Content-Encoding: gzip");
		$grade_id = $this->input->get("grade_id");
		$grades = $this->plate_model->get_alone_grades($grade_id);
		$data = array("Grades"=>$grades);
		echo gzencode(json_encode($data));
	}
	// 具体某一场的战绩
	public function get_grade () {
		$grade_id = $this->input->get("grade_id");

		$data = array();
		$grade = $this->plate_model->get_grade($grade_id);
		$data["Grade"] = $grade;
		echo json_encode($data);
	}
	public function get_private_room_history () {
		header("Content-Encoding: gzip");
		$uid = $this->input->get("uid");
		$last = $this->plate_model->get_private_room_history($uid);
		$data = array("Last"=>$last);
		echo gzencode(json_encode($data));

	}
	// 校验验证码
	public function check_sms() {
		$vcode = $this->input->get("code");
		$phone = $this->input->get("phone");

		$vcode1 = $this->session->userdata("vcode");
		$phone1 = $this->session->userdata("vcode_phone");
		$expire_time = $this->session->userdata("vcode_expire_time");
		if ( !$expire_time || $expire_time < time() || $vcode != $vcode1 || $phone != $phone1 ) {
			die(err_string(err_code(1101,"验证码无效或已过期")));
		}
		
		echo err_string(err_code(0));
	}
	public function pay_url() {
		$this->load->model('config_model');
		$url = $this->config_model->get("web","PayUrl","Value");
		echo err_string(err_code(0),array("url"=>$url));
	}
}
