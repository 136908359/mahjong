<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Share extends CI_Controller {
	function __construct() {
		parent::__construct();
        $this->load->helper('url');
        $this->load->helper('template');

        $this->load->model("share_model");
        $this->load->model("agent_model");

        $this->load->library('pagination');
	}
	
	public function get_last_member() {	
		$page = $this->input->get("per_page");
		$uid_str = $this->input->get("uid_str");
		$start_time = $this->input->get("start_time");
		$end_time = $this->input->get("end_time");
		
		$page = floor(intval($page)/16)+1;

		$uid_list = array();
		if ( $uid_str ) {
			$uid_list = explode(";", $uid_str);
		}
		if ( !$start_time ) {
			$start_time = date('Y-m-01 00:00:00');
		}
		if ( !$end_time ) {
			$end_time = date('Y-m-d H:i:s');
		}

		$total = $this->share_model->get_total_member($uid_list,$start_time,$end_time);
		$members = $this->share_model->get_last_member($uid_list,$start_time,$end_time,$page,16);
        $url = "/v1/share/get_last_member?";
		if ( $this->input->get() ) {
			$url .= http_build_query(array("uid_str"=>$uid_str,"start_time"=>$start_time,"end_time"=>$end_time));
		}

		$config['base_url'] = $url;
        $config['total_rows'] = $total;
        $config['cur_page'] = $page;
        $config['per_page'] = 16;
        $config['first_link'] = '首页';
        $config['prev_link'] = '上一页';
        $config['next_link'] = '下一页';
        $config['last_link'] = '尾页';
        $config['page_query_string'] = true;

        $this->pagination->initialize($config);
		$pages = $this->pagination->create_links();
		
		$data = array(
			'pages'         => $pages,
			"members"       =>$members,
			'uid_str'       => $uid_str,
			'start_time'    => $start_time,
			'end_time'      => $end_time,
		);
		output("data/get_last_share_member.phtml",$data);
	}
	// 个人中心
	function get_user_info() {
		$uid = $this->input->get('uid');
		$data = $this->share_model->get_member($uid);
		output("data/get_share_info.phtml",$data);
	}
	
	// 返利
	function get_last_rebate() {
		$uid = $this->input->get('uid');
		$page = $this->input->get("per_page");
		$total = $this->share_model->get_total_rebate($uid);

		$page = floor(intval($page)/16)+1;
		$last = $this->share_model->get_last_rebate($uid,$page,16);
		
        $url = "/v1/share/get_last_rebate?uid=$uid";
		$config['base_url'] = $url;
        $config['total_rows'] = $total;
        $config['cur_page'] = $page;
        $config['per_page'] = 16;
        $config['first_link'] = '首页';
        $config['prev_link'] = '上一页';
        $config['next_link'] = '下一页';
        $config['last_link'] = '尾页';
        $config['page_query_string'] = true;

        $this->pagination->initialize($config);
		$pages = $this->pagination->create_links();
		$data = array(
			"last"	=>	$last,
			"uid"	=>	$uid,
			"pages" =>  $pages,
		);

		output("data/get_last_share_rebate.phtml",$data);
	}

	// 下级
	function get_last_share() {
		$uid = $this->input->get('uid');
		$page = $this->input->get("per_page");
		$total = $this->share_model->get_total_share($uid);

		$page = floor(intval($page)/16)+1;
		$last = $this->share_model->get_last_share($uid,$page,16);

		$url = "/v1/share/get_last_share?uid=$uid";
		$config['base_url'] = $url;
        $config['total_rows'] = $total;
        $config['cur_page'] = $page;
        $config['per_page'] = 16;
        $config['first_link'] = '首页';
        $config['prev_link'] = '上一页';
        $config['next_link'] = '下一页';
        $config['last_link'] = '尾页';
        $config['page_query_string'] = true;

        $this->pagination->initialize($config);
		$pages = $this->pagination->create_links();
		$data = array(
			"last"	=>	$last,
			"uid"	=>	$uid,
			"pages"	=>	$pages,
		);
		output("data/get_last_share.phtml",$data);
	}

	// 增加推广员
	public function add_member($uid=0) {
		$data = array('ranks'=>$this->share_model->get_rank_list());
		$args = $this->input->post();
		if ( !$args ) {
			if ($uid) {
				$member = $this->share_model->get_member($uid);
				$data = array_merge($data, $member);
			}
			output('data/add_share_member.phtml', $data);
			return;
		}
			
		$uid = $args['uid'];
		$wx = $args['wx'];
		$name = $args['name'];
		$phone = $args['phone'];
		$rank = $args['rank'];
		$this->share_model->create_member($uid,$wx,$name,$phone,$rank);
		
		// 高级推广员
		$level = 0;
		if ($rank > 1) {
			$level = 100;
		}
		$this->agent_model->create_agent($uid,$level);
		redirect("v1/share/add_member/$uid");		
	}
}
?>
