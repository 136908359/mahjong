<?php
class Filter {
	public function __construct() {
	}
	public function auth() {
		$inst = & get_instance();
		$seg1 = $inst->uri->segment(1);
		$seg2 = $inst->uri->segment(2);
		$seg3 = $inst->uri->segment(3);
		
		$forbid = array("v1"); // 禁止的目录
		if (in_array($seg1,$forbid) == false) {
			return;
		}
		$path = BASEPATH."../application/controllers/".$seg1;
		$uri = "$seg1/$seg2";
		if (is_dir($path)) {
			$uri = "/$seg1/$seg2/$seg3";
		}
		// 判断会话是否过期
		$inst->load->library("session");
		$account = $inst->session->userdata("account");
		if (empty($account)) {
			redirect("/public/login");
		}
				
		$inst->load->model('admin/admin_model');
		$is_allow = $inst->admin_model->access($account,$uri);
		if( $is_allow){//有权限
			$link = $inst->uri->uri_string();
			$data = $inst->input->get();
			if ( !$data ) {
				$data = $inst->input->post();
			}
			$inst->admin_model->add_operate_log($account,$uri,$link,json_encode($data));
		} else {
			die('404 Not Found');
		}
	}
}
?>
