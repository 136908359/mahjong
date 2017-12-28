<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');	

class User_Auth extends CI_Controller {
	function __construct() {
		parent::__construct();

		$this->load->helper("url");
		$this->load->helper("errcode");
		$this->load->library("session");
		$uid = $this->uri->segment(4);
		if ( !$uid ) {	
			$uid = $this->input->get("uid");
		}
		$uid = intval($uid);
		if ($uid == 0) {
			exit("404 Not Found");
		}
		$this->load->model("gm_model");
		$data = $this->gm_model->request("/GetToken", array("UId"=>$uid));

		$token = $this->uri->segment(5);
		if ( !$token ) {
			$token = $this->input->get("token");
		}
		if ( !$token || $token != $data->String) {
			exit("无效的会话");
		}

		$this->load->model("agent_model");
		$this->load->model("marketing_model");
	}

	// 代理入口
	function agent($uid=0, $token=null) {
		$uid = intval($uid);
		$user = $this->user_model->get_user_info($uid);
		if ( !$user ) {
			exit('404 Not Found');	
		}
		$this->session->set_userdata("agent_uid", $uid);
		redirect("/public/agent/add_card");
	}
	// 新代理V2入口
	function marketing() {
		$uid = $this->input->get("uid");
		$user = $this->marketing_model->get_user_info($uid);
		if ( isset($user['level']) && $user['level'] > 0 ) {
			$this->session->set_userdata("marketing_uid", $uid);
		}
		redirect("/public/marketing/index");
	}
	// 代理V2绑定推广码
	function bind_marketing_code() {
		$uid = $this->input->get("uid");
		$code = $this->input->get("code");
		$errcode = $this->marketing_model->bind_code($uid, $code);
		$params = array();
		if ( $errcode["Code"] == 0 ) {
			$parent_uid = $this->marketing_model->get_uid_by_code($code);
			$params = array("parent_uid"=>$parent_uid,"parent_code"=>$code);

			$this->load->model("config_model");
			$s = $this->config_model->get("config","MarketingBindCode","Value");
			$items = $this->config_model->parse_items($s);
			$args = array(
				"UId"=>intval($uid),
				"GUID"=>create_uuid(),
				"Way"=>"bind_marketing_code",
				"Items"=>$items,
			);
			$this->gm_model->request('/AddItems',$args);
		}
		echo err_string($errcode,$params);
	}
}

/* End of file welcome.php */
/* Location: ./share/application/controllers/welcome.php */
