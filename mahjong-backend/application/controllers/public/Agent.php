<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Agent extends CI_Controller {
	private $customer_service_weixin;
	function __construct() {
		parent::__construct();

		$this->load->library("session");
		$uid = $this->session->userdata("agent_uid");
		if ( !$uid ) {
			exit("请重新登陆");
		}

		$this->load->model("share_model");
		$this->load->model("agent_model");
		$info = $this->agent_model->get_agent_info($uid);

		$this->load->model("config_model");
		$this->customer_service_weixin = $this->config_model->get("web","CustomerServiceWeixin","Value");
		if ( defined("CUSTOMER_SERVCIE_WEIXIN") ) {
			$this->customer_service_weixin = CUSTOMER_SERVICE_WEIXIN;
		}
		if ( !$info ) {
			$wx = $this->customer_service_weixin;
			exit("请联系客服微信：$wx");
		}
	}

	private function view($title, $tpl, $data) {
		$share = $this->session->userdata("share_uid");
		$this->load->view("/agent/header.phtml", array("title"=>$title,'share'=>$share));	
		$this->load->view($tpl, $data);	
		$this->load->view("/agent/footer.phtml", array());	
	}

	function index() {
		$this->load->helper('url');
		redirect('/public/agent/add_card');
	}

	function get_info() {
		$uid = $this->session->userdata("agent_uid");

		$data = $this->agent_model->get_agent_info($uid);
		$this->view("个人中心", "/agent/get_info.phtml", $data);
	}
	function add_card() {
		$card = $this->input->post("card");
		$add_card_method = $this->input->post("add_card_method");

		$agent_uid = $this->session->userdata("agent_uid");
		$agent_info = $this->agent_model->get_agent_info($agent_uid);
		$data = array(
			'agent_card'=>$agent_info['agent_card'],
			'high_agent'=>$agent_info['high_agent'],
		);
		if ( !$card ) {
			$this->view("发放房卡", "/agent/add_card.phtml", $data);
			return;
		}

		$card = intval($card);
		$game_card = 0;
		$agent_card = 0;
		if ($add_card_method == "发给代理") {
			$agent_card = $card;
		} else {
			$game_card = $card;
		}
		
		if ( !$agent_info['high_agent'] ) {
			$agent_card = 0;
		}

		$other_uid = $this->input->post("other_uid");
		$msg = $this->agent_model->add_card($agent_uid,$other_uid,$game_card,$agent_card);
		$data["tip"] = $msg;
		if ($msg == "SUCCESS") {
			$data["tip"] = "发放成功";
		}
		$data['agent_card'] -= $card;
		$this->view("发放房卡", "/agent/add_card.phtml", $data);
	}
	function get_card_log() {
		$page = $this->input->get("page");
		$uid = $this->session->userdata("agent_uid");

		$total = $this->agent_model->get_total_card_log($uid);
		$total = ceil($total/16);

		$page = intval($page);
		if ($page < 1 || $page > $total) {
			$page = 1;
		}

		$last = $this->agent_model->get_card_log($uid,$page,16);
		$data = array(
			"last"	=>	$last,
			"uid"	=>	$uid,
			"cur_page"	=>	$page,
			"total_page"	=>	$total,
		);

		$this->view("历史纪录", "/agent/get_card_log.phtml", $data);
	}
	function user($other_uid) {
		$uid = $this->session->userdata("agent_uid");
		$info = $this->agent_model->get_agent_info($uid);

		$this->load->model('user_model');
		$user_info = $this->user_model->get_user_info($other_uid);
		
		$data = array();
		if ( !$user_info ) {
			$data['msg'] = '用户不存在';
		} else {
			$agent_info = $this->agent_model->get_agent_info($other_uid);
			$data['nickname'] = $user_info['nickname'];
			$data['score_card'] = $user_info['score_card'];
			if ( $info['high_agent'] && $agent_info ) {
				$data['agent_card'] = $agent_info['agent_card'];
			}
		}
		echo json_encode($data);
	}
}
