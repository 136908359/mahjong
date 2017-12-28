<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Data extends CI_Controller {
	function __construct() {
		parent::__construct();
        $this->load->helper(array('url','form'));
        $this->load->helper('template');
        $this->load->model("admin/data_model");
        $this->load->model('gm_model');
        $this->load->library('pagination');
        $this->load->library('session');
	}

	/* 订单数据 */
	public function get_order(){
		$uid = $this->input->get('uid');
		$chan_id = $this->input->get('chan_id');
		$order_id = $this->input->get('order_id');
		$start_time = $this->input->get('start_time');
		$end_time = $this->input->get('end_time');
		$page = $this->input->get('per_page');

		if ( !$end_time ) {
			$end_time = date('Y-m-d H:i:s');
		}
		if ( !$start_time ) {
			$start_time = date('Y-m-01 00:00:00');
		}

		$page = intval($page);
		$data = $this->data_model->get_order_list($chan_id,$order_id,$uid,$start_time,$end_time,$page);
		$params = array(
			'uid' => $uid,
			'chan_id' => $chan_id,
			'order_id' => $order_id,
			'start_time' => $start_time,
			'end_time' => $end_time,
		);
		$data = array_merge($data,$params);
		$data['pages'] = create_page_links('/v1/data/get_order',$page,$data['total_rows'],$params);
		output('data/get_order.phtml',$data);
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
		$data = $this->data_model->get_daily($start_time,$end_time,$page);
		$data = array_merge($data,$params);
		$total_rows = ceil((strtotime($end_time)-strtotime($start_time))/(24*60*60))+1;
		$data['pages'] = create_page_links('/v1/data/daily',$page,$total_rows,$params);
		output('data/daily.phtml',$data);
	}

	/* 渠道数据 */
	public function chan(){			
		// 默认查询所有渠道最近一天的数据
		$start_time = $this->input->get('start_time');
		$end_time = $this->input->get('end_time');
		$page = $this->input->get('per_page');
		if ( !$end_time ) {
			$end_time = date('Y-m-d');
		}
		if ( !$start_time ) {
			$start_time = date('Y-m-01');
		}

		$page = intval($page);
		$params = array(
			'start_time' => $start_time,
			'end_time'   => $end_time,
		);		
		$data = $this->data_model->get_chan_per_day($start_time,$end_time,ceil($page/GM_PAGE_SIZE));
		$data = array_merge($data,$params);
		$total_rows = ceil((strtotime($end_time)-strtotime($start_time))/(24*60*60))+1;
		$total_rows *= GM_PAGE_SIZE;
		$data['pages'] = create_page_links('/v1/data/chan',$page,$total_rows,$params);
		$data['chans'] = $this->admin_model->get_chan_list();
		output('data/chan.phtml',$data);
	}
	/* 玩家列表 */
	public function user_list(){			
		$uid_str = $this->input->get('uid_list');
		$start_time = $this->input->get('start_time');
		$end_time = $this->input->get('end_time');
		$page = $this->input->get('per_page');
		$uid_list = array();
		if ($uid_str) {
			$uid_list = explode(";",$uid_str);
		} else {
			$uid_str = '';
		}

		$page = intval($page);
		$params = array();		
		$params = array_merge($params,$this->input->get());
		unset($params['per_page']);

		$data = $this->data_model->get_user_list($uid_list,$start_time,$end_time,$page);
		$data = array_merge($data,$params);
		$total_rows = $data['total_rows'];
		$data['pages'] = create_page_links('/v1/data/user_list',$page,$total_rows,$params);
		output('data/user_list.phtml',$data);
	}
	/* 玩家 */
	public function user_info(){			
		$uid = $this->input->get('uid');
		if ( !$uid ) {
			redirect("user_list");
		}
        $this->load->model("share_model");
        $this->load->model("agent_model");
        $this->load->model("marketing_model");

		$data = array('uid'=>$uid);
		$data['user'] = $this->data_model->get_user_info($uid);
		if ( $data['user'] ) {
			$data['share'] = $this->share_model->get_member($uid);
			$data['agent'] = $this->agent_model->get_agent_info($uid);
			$data['marketing'] = $this->marketing_model->get_user_info($uid);
		}
		output('data/user_info.phtml',$data);
	}
	/******************* 代理系统 ***********************/
	/* TODO 代理列表 */
	public function agent_list(){			
		$uid_str = $this->input->get('uid_list');
		$page = $this->input->get('per_page');
		$uid_list = array();
		if ($uid_str) {
			$uid_list = explode(";",$uid_str);
		} else {
			$uid_str = '';
		}

		$page = intval($page);
		$params = array(
			'uid_list' => $uid_str,
		);		
		$data = $this->data_model->get_agent_list($uid_list,$page);
		$data = array_merge($data,$params);
		$total_rows = $data['total_rows'];
		$data['pages'] = create_page_links('/v1/data/agent_list',$page,$total_rows,$params);
		output('data/agent_list.phtml',$data);
	}
	/* TODO 代理房卡流通日志 */
	public function agent_card_log(){			
		$uid_str = $this->input->get('uid_list');
		$page = $this->input->get('per_page');
		$uid_list = array();
		if ($uid_str) {
			$uid_list = explode(";",$uid_str);
		} else {
			$uid_str = '';
		}
		
		$page = intval($page);
		$params = array(
			'uid_list' => $uid_str,
		);		
		$data = $this->data_model->get_agent_card_log($uid_list,$page);
		$data = array_merge($data,$params);
		$total_rows = $data['total_rows'];
		$data['pages'] = create_page_links('/v1/data/agent_card_log',$page,$total_rows,$params);
		output('data/agent_card_log.phtml',$data);
	}
	/****************************************************/
	public function item_log(){			
		$get = $this->input->get();
		$uid = @$get['uid'];
		$way = @$get['way'];
		$start_time = @$get['start_time'];
		$end_time = @$get['end_time'];
		$page = @$get['per_page'];
		
		$page = intval($page);
		$params = $get;
		unset($params["per_page"]);

		$data = $this->data_model->get_item_log($uid,$way,$start_time,$end_time,$page);
		$data = array_merge($data,$params);
		$total_rows = $data['total_rows'];
		$data['pages'] = create_page_links('/v1/data/item_log',$page,$total_rows,$params);
		output('data/item_log.phtml',$data);
	}
	public function item_by_day(){			
		$start_time = $this->input->get('start_time');
		$end_time = $this->input->get('end_time');
		$page = $this->input->get('per_page');

		if ( !$end_time ) {
			$end_time = date('Y-m-d');
		}
		if ( !$start_time ) {
			$start_time = date('Y-m-01');
		}
	
		$page = intval($page);
		$params = array(
			'start_time' => $start_time,
			'end_time' => $end_time,
		);		
		$data = $this->data_model->get_item_by_day($start_time,$end_time,$page);
		$data = array_merge($data,$params);
		$total_rows = $data['total_rows'];

		$data['items'] = $this->gm_model->get_items();
		$data['pages'] = create_page_links('/v1/data/item_by_day',$page,$total_rows,$params);
		output('data/item_by_day.phtml',$data);
	}
	public function item_by_way(){			
		$item_id = $this->input->get('item_id');
		$start_time = $this->input->get('start_time');
		$end_time = $this->input->get('end_time');
		$page = $this->input->get('per_page');
		
		if ( !$end_time ) {
			$end_time = date('Y-m-d');
		}
		if ( !$start_time ) {
			$start_time = date('Y-m-01');
		}
		if ( !$item_id ) {
			$items = $this->gm_model->get_items();
			foreach ($items as $item) {
				$item_id = $item[0];
				break;
			}
		}
	
		$page = intval($page);
		$params = array(
			'item_id' => $item_id,
			'start_time' => $start_time,
			'end_time' => $end_time,
		);		
		$data = $this->data_model->get_item_by_way($item_id,$start_time,$end_time,$page);
		$data = array_merge($data,$params);
		$total_rows = $data['total_rows'];

		$data['items'] = $this->gm_model->get_items();
		$data['pages'] = create_page_links('/v1/data/item_by_way',$page,$total_rows,$params);
		output('data/item_by_way.phtml',$data);
	}

}

/* End of file welcome.php */
/* Location: ./system/application/controllers/welcome.php */
