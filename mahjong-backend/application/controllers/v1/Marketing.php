<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Marketing extends CI_Controller {
	function __construct() {
		parent::__construct();
        $this->load->helper('url');
        $this->load->helper('template');
        $this->load->library('pagination');
        $this->load->model("marketing_model");
	}
	
	// 增加推广员
	public function add_user( ) {
		$post = $this->input->post();
		$version = $this->marketing_model->get_version();
		$data = array('version'=>$version,'levels'=>$this->marketing_model->get_level_list());
		if ( !$post ) {
			$uid = $this->input->get('uid');
			if ( $uid ) {
				$user = $this->marketing_model->get_user_info($uid);
				$data = array_merge($data, $user);
			}
			output('marketing/add_user.phtml', $data);
			return;
		}
		$uid = $this->input->post('uid');
		$this->marketing_model->add_user($post);
		redirect("v1/marketing/user_list?uid_list=$uid");		
	}
	
	// 推广员名单
	public function user_list( ) {
		$args = $this->input->get();
		$s = @$args['uid_list'];
		$agent_uid = @$args['agent_uid'];
		$parent_uid = @$args['parent_uid'];
		$grandpa_uid = @$args['grandpa_uid'];
		$page = intval(@$args['per_page']);
		unset($args['per_page']);

		$uid_list = array();
		if ( $s ) {
			$uid_list = (array)explode(";",$s);
		}
		$data = array();
		$data = $this->marketing_model->get_user_list($uid_list,$agent_uid,$parent_uid,$grandpa_uid,$page);
		$data['pages'] = create_page_links('/v1/marketing/user_list',$page,@$data['total_rows'],$args);

		$data = array_merge($data,$args);
		$data['levels'] = $this->marketing_model->get_level_list();
		output('marketing/user_list.phtml', $data);
	}

	// 提现清单
	public function draw_cash_list( ) {
		$args = $this->input->get();
		$uid = @$args['uid'];
		$start_time = @$args['start_time'];
		$end_time = @$args['end_time'];
		$page = intval(@$args['per_page']);
		unset($args['per_page']);

		$data = $this->marketing_model->draw_cash_list($uid,$start_time,$end_time,$page);
		$data['pages'] = create_page_links('/v1/marketing/draw_cash_list',$page,$data['total_rows'],$args);

		$data = array_merge($data,$args);
		output('marketing/draw_cash_list.phtml', $data);
	}

	// 审核推广员提现
	public function check_draw_cash() {
		$args = $this->input->get();
		
		$id = $args["id"];
		$code = $args['status'];
		$msg = $this->marketing_model->check_draw_cash($id, $code==1);
		echo $msg;
	}
	/* 日常数据 */
	public function daily(){			
		// 默认查询最近三个月数据
		$start_time = $this->input->get('start_time');
		$end_time = $this->input->get('end_time');
		$page = $this->input->get('per_page');
		if ( !$end_time ) {
			$end_time = date('Y-m-d');
		}
		if ( !$start_time ) {
			$start_time = date('Y-m-d',strtotime("$end_time -3 month"));
		}

		$page = intval($page);
		$params = array(
			'start_time' => $start_time,
			'end_time'   => $end_time,
		);		
		$data = $this->marketing_model->get_daily($start_time,$end_time,$page);
		$data = array_merge($data,$params);
		$total_rows = ceil((strtotime($end_time)-strtotime($start_time))/(24*60*60))+1;
		$data['pages'] = create_page_links('/v1/marketing/daily',$page,$total_rows,$params);
		output('marketing/daily.phtml',$data);
	}
}
?>
