<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

define("MARKETING_NEED_MONEY",50);

class Marketing extends CI_Controller {
	private $need_money = MARKETING_NEED_MONEY;
	function __construct() {
		parent::__construct();

		$this->load->helper("url");
		$this->load->library("session");
		$this->load->model("marketing_model");

		$uid = $this->session->userdata("marketing_uid");
		if ( !$uid ) {
			redirect('/m/marketing_about.html');
		}
	}

	function view($title, $tpl, $data) {
		$this->load->view("/m/marketing/header.phtml", array("title"=>$title));	
		$this->load->view($tpl, $data);	
		$this->load->view("/m/marketing/footer.phtml", array());	
	}
	
	function manage_view($title, $tpl, $data) {
		$this->load->view("/m/marketing/manage_header.phtml", array("title"=>$title));	
		$this->load->view($tpl, $data);	
		$this->load->view("/m/marketing/manage_footer.phtml", array());	
	}


	function index() {
		redirect("/public/marketing/info");
	}
			
	// 个人中心
	function info() {
		$uid = $this->session->userdata('marketing_uid');
		$data = $this->marketing_model->get_user_info($uid);
		$data['need_money'] = $this->need_money;

		$this->view("个人中心", "/m/marketing/info.phtml", $data);
	}
	
	// 创建下级
	function add_children() {
		$data = $this->input->post();
		if ( !$data ) {
			$this->manage_view("完善信息","/m/marketing/add_children.phtml", array());
			return;
		}

		$uid = $this->session->userdata('marketing_uid');
		$msg = $this->marketing_model->add_children($uid,$data);
		if ( $msg == "SUCCESS" ) { 
			redirect("/public/marketing/agent");
		} else {
			$this->manage_view("完善信息","/m/marketing/add_children.phtml", array('errmsg'=>$msg));
		}
	}

	// 申请提现
	function draw_cash() {
		$uid = $this->session->userdata('marketing_uid');
		$rmb = $this->input->post('money');
		if ($rmb < $this->need_money) {
			die("请刷新页面再试");
		}
	
		$msg = $this->marketing_model->draw_cash($uid, floatval($rmb));
		echo $msg;
	}

	// 返利
	function rebate() {
		$page = $this->input->get("page");
		$uid = $this->session->userdata('marketing_uid');

		$page = intval($page);
		if ( $page < 0 ) {
			$page = 0;
		}

		$last = $this->marketing_model->get_last_rebate($uid,$page);
		$data = array(
			"uid"	=>	$uid,
			"cur_page"	=>	$page,
			"total_page"	=>	ceil($last['total_rows']/GM_PAGE_SIZE),
		);
		$data = array_merge($data,$last);
		$this->view("最近返利","/m/marketing/rebate.phtml", $data);
	}

	// 下级
	function children() {
		$page = $this->input->get("page");
		$uid = $this->session->userdata('marketing_uid');

		$page = intval($page);
		if ( $page < 0 ) {
			$page = 0;
		}
		$last = $this->marketing_model->get_children_list($uid,$page);

		$data = array(
			"uid"	=>	$uid,
			"cur_page"	=>	$page,
			"total_page"	=>	ceil($last['total_rows']/GM_PAGE_SIZE),
		);
		$data = array_merge($data,$last);
		$this->view("我的推广","/m/marketing/children.phtml", $data);
	}
	// 代理
	function agent() {
		$page = $this->input->get("page");
		$uid = $this->session->userdata('marketing_uid');

		$page = intval($page);
		if ( $page < 0 ) {
			$page = 0;
		}
		$last = $this->marketing_model->get_agent_list($uid,$page);

		$data = array(
			"uid"	=>	$uid,
			"cur_page"	=>	$page,
			"total_page"	=>	ceil($last['total_rows']/GM_PAGE_SIZE),
		);
		$data = array_merge($data,$last);
		$data["levels"] = $this->marketing_model->get_level_list();
		$this->manage_view("我的代理","/m/marketing/agent.phtml", $data);
	}
}
