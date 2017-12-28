<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Share extends CI_Controller {
	private $need_money = 50; // 最低提现要求

	function __construct() {
		parent::__construct();

		$this->load->library("session");
		$uid = $this->session->userdata("uid");
		if ( !$uid ) {
			exit("请重新登陆");
		}

		$this->load->helper("url");
		$this->load->model("share_model");
		$this->load->model("agent_model");

		$share_uid = $this->session->userdata("share_uid");
		$member = $this->share_model->get_member($uid);

		if ( $member && empty($member['rank']) == false) {
			$this->session->set_userdata('agent_uid',$uid);
		} else {
			$this->session->unset_userdata('agent_uid');
		}

		if ( !$share_uid ) {
			// 推广员存在
			if ( $member ) {
				$this->session->set_userdata("share_uid",$uid);
			} else {
				redirect('/m/share_about.html');
			}
		}
	}

	function view($title, $tpl, $data) {
		$agent = $this->session->userdata('agent_uid');
		$this->load->view("/share/header.phtml", array("title"=>$title,"agent"=>$agent));	
		$this->load->view($tpl, $data);	
		$this->load->view("/share/footer.phtml", array());	
	}

	function index() {
		redirect("/public/share/get_info");
	}

			
	// 个人中心
	function get_info() {
		$uid = $this->session->userdata('share_uid');
		$data = $this->share_model->get_member($uid);
		$data['need_money'] = $this->need_money;

		$this->view("个人中心", "/share/get_info.phtml", $data);
	}
	
	// 更新个人信息
	function update_info() {
		$data = $this->input->post();
		if (empty($data) == true) {
			$this->view("完善信息","/share/update_info.phtml", array());
			return;
		}

		$uid = $this->session->userdata('share_uid');
		$name = $this->input->post('name');
		$wx = $this->input->post('wx');
		$phone = $this->input->post('phone');
		$vcode = $this->input->post('vcode');

		// 验证码
		$vcode1 = $this->session->userdata("vcode");
		$expire_time = $this->session->userdata("vcode_expire_time");
		
		if (empty($expire_time) || $expire_time + 300 < time() || $vcode != $vcode1) {
			// die("验证码无效或已过期");
		}

		$data = array(
			"name"  => $name,
			"wx" => $wx,
			"phone" => $phone,
		);
		$this->share_model->update_person_info($uid,$data);

		$this->load->helper("url");
		redirect("/public/share/get_info");
	}

	// 申请提现
	function draw_cash() {
		$uid = $this->session->userdata('share_uid');
		$rmb = $this->input->post('money');
		if ($rmb < $this->need_money) {
			die("请刷新页面再试");
		}
	
		$msg = $this->share_model->draw_cash($uid, floatval($rmb));
		echo $msg;
	}

	// 返利
	function get_last_rebate($page=1) {
		$uid = $this->session->userdata('share_uid');
		$total = $this->share_model->get_total_rebate($uid);

		$total = ceil($total/16);
		$page = intval($page);
		if ($page < 1 || $page > $total) {
			$page = 1;
		}

		$last = $this->share_model->get_last_rebate($uid,$page,16);
		$data = array(
			"last"	=>	$last,
			"uid"	=>	$uid,
			"cur_page"	=>	$page,
			"total_page"	=>	$total,
		);
		$this->view("最近返利","/share/get_last_rebate.phtml", $data);
	}

	// 下级
	function get_last_share($page=1) {
		$uid = $this->session->userdata('share_uid');
		$total = $this->share_model->get_total_share($uid);
		$total = ceil($total/16);

		$page = intval($page);
		if ($page < 1 || $page > $total) {
			$page = 1;
		}
		$last = $this->share_model->get_last_share($uid,$page,16);

		$data = array(
			"last"	=>	$last,
			"uid"	=>	$uid,
			"cur_page"	=>	$page,
			"total_page"	=>	$total,
		);
		$this->view("我的推广","/share/get_last_share.phtml", $data);
	}

	function sms() {
		require_once APPPATH.'third_party/netease_im.php';

		// 验证码
		$phone = $this->input->post("phone");
		$vcode = $this->session->userdata("vcode");
		$expire_time = $this->session->userdata("vcode_expire_time");
		
		if (!empty($expire_time) && $expire_time + 300 <= time()) {
			die("已发送");
		}

		$AppKey = '9864f02f0d10ada6dc1c3c6563efbf07';
		$AppSecret = 'd04d67b0d427';
		$p = new ServerAPI($AppKey,$AppSecret,'curl');
		$a = $p->sendSmsCode($phone,3049390,'');
		if ($a["code"] != "200") {
			die("发送失败");
		}

		echo "SUCCESS";
		$this->session->set_userdata("vcode", $a["obj"]);
		$this->session->set_userdata("vcode_expire_time", time()+300);
	}
}
