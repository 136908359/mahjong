<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Main extends CI_Controller {

	function __construct() {
		parent::__construct();
        $this->load->helper('url');
        $this->load->helper('template');
        $this->load->model("admin/admin_model");
	}
	
	/* 菜单列表  */
	public function get_menu_list() {	
		$data = array();
		$this->load->library("session");		
		$menu_list = $this->admin_model->get_menu_list();
		$data['menu_list'] = $menu_list;
		output('admin/get_menu_list.phtml',$data);
	}

	public function add_menu() {	
		$data = array();
		if (empty($this->input->post())) {
			$data['menus'] = $this->admin_model->get_menu_list();
			output("admin/add_menu.phtml", $data);	
			return;
		}
		$title = $this->input->post("title");
		$parent_id = $this->input->post("parent_id");
		$uri = $this->input->post("uri");
		$show = $this->input->post("show");
		$coment = $this->input->post("coment");
		$menu = array(
			"title" => $title,
			"parent_id" => $parent_id,
			"uri" => $uri,
			"show" => $show,
			"coment" => $coment
		);
		$this->admin_model->add_menu($menu);
		redirect("/v1/main/get_menu_list");
	}

	public function edit_menu($id) {	
		$data = array();
		if (empty($this->input->post())) {
			$data['menu'] = $this->admin_model->get_menu_by_id($id);
			$data['menus'] = $this->admin_model->get_menu_list();
			output("admin/edit_menu.phtml", $data);	
			return;
		}
		$title = $this->input->post("title");
		$parent_id = $this->input->post("parent_id");
		$uri = $this->input->post("uri");
		$show = $this->input->post("show");
		$coment = $this->input->post("coment");
		$menu = array(
			"id" => $id,
			"title" => $title,
			"parent_id" => $parent_id,
			"uri" => $uri,
			"show" => $show,
			"coment" => $coment
		);
		$this->admin_model->edit_menu($menu);
		redirect("/v1/main/get_menu_list");
	}
		
	/* 管理员列表  */
	public function get_member_list() {	
		$data = array();
		$this->load->library("session");
		$menu_list = $this->admin_model->get_member_list();
		$data['member_list'] = $menu_list;
		output('admin/get_member_list.phtml',$data);
	}

	public function add_member() {	
		$data = array();
		if (empty($this->input->post())) {
			$group_list = $this->admin_model->get_group_list();
			$chan_list = $this->admin_model->get_chan_list();//渠道列表
			$data['chan_list'] = $chan_list;
			$data['group_list'] = $group_list;
			output("admin/add_member.phtml", $data);	
			return;
		}
		$account = $this->input->post("account");
		$passwd = $this->input->post("passwd");
		$group_id = $this->input->post("group_id");
		$chan_id = $this->input->post("chan_id");
		$status = $this->input->post("status");
		$coment = $this->input->post("coment");
		$member = array(
			"account" => $account,
			"group_id" => $group_id,
			"passwd" => $passwd,
			"status" => $status,
			"chan_id" => $chan_id,
			"coment" => $coment
		);
		$this->admin_model->add_member($member);
		redirect("/v1/main/get_member_list");
	}

	public function edit_member($account) {	
		$data = array();
		if (empty($this->input->post())) {
			$member = $this->admin_model->get_member($account);
			$group_list = $this->admin_model->get_group_list();
			$chan_list = $this->admin_model->get_chan_list();//渠道列表
			$data = array_merge($data, $member);
			$data['group_list'] = $group_list;
			$data['chan_list'] = $chan_list;
			output("admin/edit_member.phtml", $data);	
			return;
		}
		$passwd = $this->input->post("passwd");
		$group_id = $this->input->post("group_id");
		$chan_id = $this->input->post("chan_id");
		$status = $this->input->post("status");
		$coment = $this->input->post("coment");
		$member = array(
			"account" => $account,
			"passwd" => $passwd,
			"group_id" => $group_id,
			"status" => $status,
			"chan_id" => $chan_id,
			"coment" => $coment
		);
		//如果没更改密码
		if(empty($passwd)){
			unset($member['passwd']);
			$this->admin_model->edit_member($member);
		}else{
			$this->admin_model->edit_member($member,'pwd');
		}				
		redirect("/v1/main/get_member_list");
	}
	
		
	/*
	*   分组
	*/
	public function get_group_list() {	
		$data = array();
		$this->load->library("session");
		$group_list = $this->admin_model->get_group_list();
		$data['group_list'] = $group_list;
		output('admin/get_group_list.phtml',$data);
	}
	
	public function add_group() {	
		$data = array();
		if (empty($this->input->post())) {
			output("admin/add_group.phtml", $data);	
			return;
		}
		$title = $this->input->post("title");
		$coment = $this->input->post("coment");
		$group = array(
			"title" => $title,
			"coment" => $coment
		);
		$this->admin_model->add_group($group);
		redirect("/v1/main/get_group_list");
	}

	public function edit_group($group_id) {	
		$data = array();
		if (empty($this->input->post())) {
			$group = $this->admin_model->get_group($group_id);
			$menu_list = $this->admin_model->get_menu_list();
			$data = array_merge($data, $group);
			$data['menu_list'] = $menu_list;
			output("admin/edit_group.phtml", $data);	
			return;
		}
		$title = $this->input->post("title");
		$coment = $this->input->post("coment");
		$menus = $this->input->post("menu_list");
		$group = array(
			"id" => $group_id,
			"title" => $title,
			"coment" => $coment,
			"menus" => $menus
		);
		$this->admin_model->edit_group($group);
		redirect("/v1/main/get_group_list");
	}
	
	public function operate_log(){
		$get = $this->input->get();
		$account = @$get['account'];
		$start_time = @$get['start_time'];
		$end_time = @$get['end_time'];
		$page = @$get['per_page'];
		unset($get['per_page']);

		if ( !$end_time ) {
			$end_time = date('Y-m-d H:i:s');
		}
		if ( !$start_time ) {
			$start_time = date('Y-m-01 00:00:00');
		}

		$data = $this->admin_model->get_operate_log($account,$start_time,$end_time,$page);
		$data = array_merge($data,$get);
		$data['pages'] = create_page_links('/v1/main/operate_log',$page,$data['total_rows'],$get);
		output('admin/operate_log.phtml',$data);
	}
}

/* End of file welcome.php */
/* Location: ./system/application/controllers/welcome.php */
