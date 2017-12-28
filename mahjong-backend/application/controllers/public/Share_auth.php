<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');	

class Share_Auth extends CI_Controller {
	function __construct() {
		parent::__construct();

		$this->load->helper("url");
		$this->load->library("session");
		$uid = $this->uri->segment(4);
		$uid = intval($uid);
		if ($uid == 0) {
			exit("404 Not Found");
		}
		$this->load->model("gm_model");
		$data = $this->gm_model->request("/GetToken", array("UId"=>$uid));

		$token = $this->uri->segment(5);
		if ($token != $data->String) {
			exit("无效的会话");
		}

		$this->load->model("share_model");
	}

	// 验证入口
	function index($uid=0, $token='#') {
		$uid = intval($uid);
		$this->session->set_userdata("uid", $uid);
		redirect("/public/share/index");
	}

	// 获取分享码
	// 2017-02-17 推广码改为UID，该接口废弃
	function get_code($uid=0, $token='#') {
		$data = $this->share_model->get_bind_info($uid);
		echo json_encode($data);
	}

	// 绑定推广码
	// 2017-02-13 推广码改为UID
	function bind_code($uid=0, $token='#',$code='0') {
		// 推广码转化成大写
		// $code = strtoupper($code);
		$msg = $this->share_model->bind_code($uid, $code);
		echo $msg;
	}

	// 最近的分享记录
	function get_last_rebate($uid=0, $token='#') {
		$data = $this->share_model->get_last_rebate($uid);
		$data = array("last"=>$data);
		echo json_encode($data);
	}
}

/* End of file welcome.php */
/* Location: ./share/application/controllers/welcome.php */
