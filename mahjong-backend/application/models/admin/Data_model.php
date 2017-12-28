<?php
class Data_Model extends CI_Model {
	function __construct() {
		parent::__construct();
		$this->load->database();
		$this->mahjong = $this->load->database('mahjong',TRUE);

		$this->load->model('admin/admin_model');
	}
	/*
	 *  在线人数
	 */
	public function get_online_by_day($day){
		$start_time = $day;
		$end_time = date('Y-m-d', strtotime("$start_time +1 day"));			
		$sql = "select * from game_online_log where num>0 and deadline between ? and ?";
		return $this->mahjong->query($sql,array($start_time,$end_time))->result_array();
	}
	/* 日常数据 */
	public function get_daily($start_time,$end_time,$cur_page) {
		$page_size = GM_PAGE_SIZE;
		$real_start_time = date('Y-m-d', strtotime("$start_time +$cur_page day"));

		$next_time = date('Y-m-d', strtotime("$real_start_time +$page_size day"));
		$end_time = date('Y-m-d',strtotime("$end_time +1 day"));
		
		$real_end_time = $end_time;
		if ( $end_time > $next_time ) {
			$real_end_time = $next_time;
		}

		$data = array(
			'real_start_time' =>  $real_start_time,
			'real_end_time' =>  $real_end_time,
			'day' => array(),
		);
		// 总注册用户数
		$row = $this->mahjong->query("select count(uid) as total from user_info")->row_array();
		$data['total_register_user'] = $row['total'];
		// 新增
		$row = $this->mahjong->query("select count(uid) as total from user_info where create_time between ? and ?",array($start_time,$end_time))->row_array();
		$data['total_new_user'] = $row['total'];
		$rows = $this->mahjong->query("select date(create_time) as `date`,count(uid) as total from user_info where create_time between ? and ? group by `date`",array($start_time,$end_time))->result_array();
		foreach ($rows as $row) {
			$data['day'][$row['date']]['new_user'] = $row['total'];
		}
		// 牌局总数
		$row = $this->mahjong->query("select sum(total_times) as total from game_day_log where curday between ? and ?",array($start_time,$end_time))->row_array();
		$data['total_play_times'] = $row['total'];
		$rows = $this->mahjong->query("select curday as `date`, sum(total_times) as total from game_day_log where curday between ? and ? group by `date`",array($real_start_time,$real_end_time))->result_array();
		foreach ($rows as $row) {
			$data['day'][$row['date']]['play_times'] = $row['total'];
		}
		// 总活跃人数
		$row = $this->mahjong->query("select count(uid) as total from game_play_log where curday between ? and ?",array($start_time,$end_time))->row_array();
		$data['total_active_user'] = $row['total'];
		$rows = $this->mahjong->query("select curday as `date`,count(uid) as total from game_play_log where curday between ? and ? group by `date`",array($real_start_time,$real_end_time))->result_array();
		foreach ($rows as $row) {
			$data['day'][$row['date']]['active_user'] = $row['total'];
		}
		// 付费人数
		$row = $this->db->query("select count(distinct buy_uid) as total from charge_order where result=? and create_time between ? and ?",array(ORDER_FINISH,$start_time,$end_time))->row_array();
		$data['total_pay_user'] = $row['total'];
		$rows = $this->db->query("select date(create_time) as `date`,count(distinct buy_uid) as total from charge_order where result=? and create_time between ? and ? group by `date`",array(ORDER_FINISH,$real_start_time,$real_end_time))->result_array();
		foreach ($rows as $row) {
			$data['day'][$row['date']]['pay_user'] = $row['total'];
		}
		// 新增付费人数
		$row = $this->db->query("select count(buy_uid) as total from charge_order where first_pay=1 and result=? and create_time between ? and ?",array(ORDER_FINISH,$start_time,$end_time))->row_array();
		$data['total_first_pay_user'] = $row['total'];
		$rows = $this->db->query("select date(create_time) as `date`,count(distinct buy_uid) as total from charge_order where first_pay=1 and result=? and create_time between ? and ? group by `date`",array(ORDER_FINISH,$real_start_time,$real_end_time))->result_array();
		foreach ($rows as $row) {
			$data['day'][$row['date']]['first_pay_user'] = $row['total'];
		}
		// 新增付费金额
		$row = $this->db->query("select sum(rmb) as total from charge_order where first_pay=1 and result=? and create_time between ? and ?",array(ORDER_FINISH,$start_time,$end_time))->row_array();
		$rows = $this->db->query("select date(create_time) as `date`,sum(rmb) as total from charge_order where first_pay=1 and result=? and create_time between ? and ? group by `date`",array(ORDER_FINISH,$real_start_time,$real_end_time))->result_array();
		$data['total_first_pay_rmb'] = $row['total'];
		foreach ($rows as $row) {
			$data['day'][$row['date']]['first_pay_rmb'] = $row['total'];
		}
		// 总付费金额
		$row = $this->db->query("select sum(rmb) as total from charge_order where result=? and create_time between ? and ?",array(ORDER_FINISH,$start_time,$end_time))->row_array();
		$data['total_pay_rmb'] = $row['total'];
		$rows = $this->db->query("select date(create_time) as `date`,sum(rmb) as total from charge_order where result=? and create_time between ? and ? group by `date`",array(ORDER_FINISH,$real_start_time,$real_end_time))->result_array();
		foreach ($rows as $row) {
			$data['day'][$row['date']]['pay_rmb'] = $row['total'];
		}
		
		$data['total_pay_rate'] = 0;
		if ( $data['total_register_user'] > 0 ) {
			$data['total_pay_rate'] = $data['total_pay_user']*100/$data['total_register_user'];
		}
		$data['total_active_arpu'] = 0;
		if ( $data['total_active_user'] > 0 ) {
			$data['total_active_arpu'] = $data['total_pay_rmb']/$data['total_active_user'];
		}
		$data['total_pay_arpu'] = 0;
		if ( $data['total_pay_user'] > 0 ) {
			$data['total_pay_arpu'] = $data['total_pay_rmb']/$data['total_pay_user'];
		}
		foreach ($data['day'] as $k=>$v) {
			if ( $data['total_register_user'] > 0 ) {
				@$data['day'][$k]['pay_rate'] = $v['pay_user']*100/$data['total_register_user'];
			}
			if ( @$v['active_user'] > 0 ) {
				@$data['day'][$k]['active_arpu'] = $v['pay_rmb']/$v['active_user'];
			}
			if ( @$v['pay_user'] > 0 ) {
				@$data['day'][$k]['pay_arpu'] = $v['pay_rmb']/$v['pay_user'];
			}
		}
		return $data;
	}
	// 订单
	public function get_order_list($chan_id,$order_id,$uid,$start_time,$end_time,$page) {
		$where = ' where 1=1';
		$params = array();
		if ( $chan_id ) {
			$where .= " and chan_id =?";
			array_push($params,$chan_id);
		}
		if ( $order_id ) {
			$where .= " and order_id=?";
			array_push($params,$order_id);
		}
		if ( $uid ) {
			$where .= " and buy_uid=?";
			array_push($params,$uid);
		}
		if ( $start_time ) {
			$where .= " and create_time >= ?";
			array_push($params,$start_time);
		}
		if ( $end_time ) {
			$where .= " and create_time < ?";
			array_push($params,$end_time);
		}
		$data = $this->db->query("select count(*) as total_rows,sum(rmb) as total_rmb from charge_order".$where." and result=".ORDER_FINISH,$params)->row_array();
		array_push($params,$page,GM_PAGE_SIZE);
		$rows = $this->db->query("select * from charge_order".$where." limit ?,?",$params)->result_array();
		$data['rows'] = $rows;
		return $data;
	}
	// 渠道数据
	public function get_chan_per_day($start_time,$end_time,$cur_page) {
		$where = ' where 1=1';
		$params = array();
		
		$page_size = GM_PAGE_SIZE;
		$real_start_time = date('Y-m-d', strtotime("$start_time +$cur_page day"));
		$next_time = date('Y-m-d', strtotime("$real_start_time +1 day"));

		$end_time = date('Y-m-d',strtotime("$end_time +1 day"));
		$real_end_time = $end_time;
		if ( $end_time > $next_time ) {
			$real_end_time = $next_time;
		}

		$data = array(
			'real_start_time' =>  $real_start_time,
			'real_end_time' =>  $real_end_time,
			'day' => array(),
		);
		// 注册
		$row = $this->mahjong->query("select count(uid) as total from user_info")->row_array();
		$data['total_register_user'] = $row['total'];
		// 新增
		$row = $this->mahjong->query("select count(uid) as total from user_info where create_time between ? and ?",array($start_time,$end_time))->row_array();
		$data['total_new_user'] = $row['total'];
		$rows = $this->mahjong->query("select chan_id,count(uid) as total from user_info where create_time between ? and ? group by chan_id",array($real_start_time,$real_end_time))->result_array();
		foreach ($rows as $row) {
			$data['day'][$row['chan_id']]['new_user'] = $row['total'];
		}

		// 总活跃人数
		$row = $this->mahjong->query("select count(distinct uid) as total from game_play_log where curday between ? and ?",array($start_time,$end_time))->row_array();
		$data['total_active_user'] = $row['total'];
		foreach ($rows as $row) {
			$data['day'][$row['chan_id']]['active_user'] = $row['total'];
		}
		// 付费人数
		$row = $this->db->query("select count(distinct buy_uid) as total from charge_order where result=? and create_time between ? and ?",array(ORDER_FINISH,$start_time,$end_time))->row_array();
		$data['total_pay_user'] = $row['total'];
		$rows = $this->db->query("select chan_id,count(buy_uid) as total from charge_order where result=? and create_time between ? and ? group by chan_id",array(ORDER_FINISH,$real_start_time,$real_end_time))->result_array();
		foreach ($rows as $row) {
			$data['day'][$row['chan_id']]['pay_user'] = $row['total'];
		}
		// 新增付费人数
		$row = $this->db->query("select count(buy_uid) as total from charge_order where first_pay=1 and result=? and create_time between ? and ?",array(ORDER_FINISH,$start_time,$end_time))->row_array();
		$data['total_first_pay_user'] = $row['total'];
		$rows = $this->db->query("select chan_id,count(buy_uid) as total from charge_order where first_pay=1 and result=? and create_time between ? and ? group by chan_id",array(ORDER_FINISH,$real_start_time,$real_end_time))->result_array();
		foreach ($rows as $row) {
			$data['day'][$row['chan_id']]['first_pay_user'] = $row['total'];
		}
		// 新增付费金额
		$row = $this->db->query("select sum(rmb) as total from charge_order where first_pay=1 and result=? and create_time between ? and ?",array(ORDER_FINISH,$start_time,$end_time))->row_array();
		$rows = $this->db->query("select chan_id,sum(rmb) as total from charge_order where first_pay=1 and result=? and create_time between ? and ? group by chan_id",array(ORDER_FINISH,$real_start_time,$real_end_time))->result_array();
		$data['total_first_pay_rmb'] = $row['total'];
		foreach ($rows as $row) {
			$data['day'][$row['chan_id']]['first_pay_rmb'] = $row['total'];
		}
		// 总付费金额
		$row = $this->db->query("select sum(rmb) as total from charge_order where result=? and create_time between ? and ?",array(ORDER_FINISH,$start_time,$end_time))->row_array();
		$data['total_pay_rmb'] = $row['total'];
		$rows = $this->db->query("select chan_id,sum(rmb) as total from charge_order where result=? and create_time between ? and ? group by `chan_id`",array(ORDER_FINISH,$real_start_time,$real_end_time))->result_array();
		foreach ($rows as $row) {
			$data['day'][$row['chan_id']]['pay_rmb'] = $row['total'];
		}
		$data['total_pay_rate'] = 0;
		if ( $data['total_register_user'] > 0 ) {
			$data['total_pay_rate'] = $data['total_pay_user']*100/$data['total_register_user'];
		}
		$data['total_active_arpu'] = 0;
		if ( $data['total_active_user'] > 0 ) {
			$data['total_active_arpu'] = $data['total_pay_rmb']/$data['total_active_user'];
		}
		$data['total_pay_arpu'] = 0;
		if ( $data['total_pay_user'] > 0 ) {
			$data['total_pay_arpu'] = $data['total_pay_rmb']/$data['total_pay_user'];
		}
		foreach ($data['day'] as $k=>$v) {
			if ( $data['total_register_user'] > 0 ) {
				$data['day'][$k]['pay_rate'] = @$v['pay_user']*100/$data['total_register_user'];
			}
			if ( @$v['active_user'] > 0 ) {
				$data['day'][$k]['active_arpu'] = @$v['pay_rmb']/$v['active_user'];
			}
			if ( @$v['pay_user'] > 0 ) {
				$data['day'][$k]['pay_arpu'] = $v['pay_rmb']/$v['pay_user'];
			}
		}
		return $data;
	}
	public function get_user_info($uid) {
		$user = $this->mahjong->query("select * from user_info where uid=?",array($uid))->row_array();
		// 登陆日志
		$online = $this->mahjong->query("select ip,enter_time from online_log where uid=? order by id limit 1",array($uid))->row_array();
		$user = array_merge((array)$user,(array)$online);
		// 总对局数
		$play = $this->mahjong->query("select sum(total_times) as play_times from game_play_log where uid=?",array($uid))->row_array();
		$user = array_merge((array)$user,(array)$play);
		// 今日对局数
		$play = $this->mahjong->query("select sum(total_times) as today_play_times from game_play_log where uid=? and curday=?",array($uid,date('Y-m-d')))->row_array();
		$user = array_merge($user,$play);
		// 7日对局数
		$play = $this->mahjong->query("select sum(total_times) as day7_play_times from game_play_log where uid=? and curday >= ?",array($uid,date('Y-m-d',strtotime("-6 day"))))->row_array();
		$user = array_merge($user,$play);
		// 充值
		$pay = $this->db->query("select sum(rmb) as pay_rmb from charge_order where buy_uid=? and result=?",array($uid,ORDER_FINISH))->row_array();
		$user = array_merge($user,$pay);
		return $user;
	}
	// 玩家列表
	public function get_user_list($uid_list,$start_time,$end_time,$page) {
		$data = array();
		$data['total_rows'] = 1000; // 统计玩家数量用来分页，不需要准确的值
		if ( !$uid_list ) {
			$page_size = GM_PAGE_SIZE;
			$where = " where 1 = 1";
			if ( $start_time ) {
				$where .= " and create_time>='$start_time'";
			}
			if ( $end_time ) {
				$where .= " and create_time<'$end_time'";
			}
			$rows = $this->mahjong->query("select uid from user_info $where order by uid desc limit ?,?",array($page,$page_size))->result_array();
			$uid_list = array();
			foreach ($rows as $row) {
				array_push($uid_list,$row['uid']);
			}
		}
		$this->load->model('share_model');
		$this->load->model('agent_model');

		$users = array();
		foreach ($uid_list as $uid) {
			$user = $this->get_user_info($uid);
			array_push($users,$user);
		}
		$data['rows'] = $users;
		return $data;
	}
	function get_agent_list($uid_list,$page) {
		$total_rows = 1000;
		$page_size = GM_PAGE_SIZE;
		if ( !$uid_list ) {
			$uid_list = array();
			$rows = $this->db->query("select uid from agent_member limit ?,?",array($page,$page_size))->result_array();
			foreach ($rows as $row) {
				array_push($uid_list,$row['uid']);
			}
		}
		if (count($uid_list) < $page_size) {
			$total_rows = count($uid_list);
		}
		$this->load->model('agent_model');

		$users = array();
		foreach ($uid_list as $uid) {
			$user = $this->agent_model->get_agent_info($uid);
			array_push($users,$user);
		}
		$data = array('total_rows'=>$total_rows,'rows'=>$users);
		return $data;
	}
	function get_agent_card_log($uid_list, $page) {
		$page_size = GM_PAGE_SIZE;
		$where = ' where 1=1';
		if ( $uid_list ) {
			$in = "(".implode(",",$uid_list).")";
			$where = $where." and (agent_uid in $in or uid in $in)";
		}
		$where .= " order by id desc limit $page,$page_size";
		$rows = $this->db->query("select * from agent_card_log".$where)->result_array();
		foreach ($rows as $k=>$row) {
			$uid = $row['uid'];
			$user = $this->get_user_info($uid);
			@$rows[$k]['nickname'] = $user['nickname'];
		}

		$total_rows = 1000;
		// 不足一页
		if ($page_size > count($rows)) {
			$total_rows = count($rows);
		}
		return array('total_rows'=>$total_rows,'rows'=>$rows);
	}
	// 物品日志
	function get_item_log($uid,$way,$start_time,$end_time,$page) {
		$page_size = GM_PAGE_SIZE;
		$where = ' where 1=1';
		if ( $uid ) {
			$where .= " and uid=$uid";
		}
		if ( $way ) {
			$where .= " and way like '%$way'";
		}
		if ( $start_time ) {
			$where .= " and deadline>='$start_time'";
		}
		if ( $end_time ) {
			$where .= " and deadline>='$end_time'";
		}
		$where .= " order by id desc limit $page,$page_size";
		$rows = $this->mahjong->query("select * from item_log".$where)->result_array();
		$total_rows = 1000;
		// 不足一页
		if ($page_size > count($rows)) {
			$total_rows = count($rows);
		}
		$this->load->model("config_model");
		foreach ($rows as &$row) {
			$row['way'] = $this->config_model->get_item_way($row["way"]);
			$row['item_name'] = $this->config_model->get("item",$row['item_id'],"ShopTitle");
		}

		$ways = $this->config_model->rows("item_way");
		return array('total_rows'=>$total_rows,'rows'=>$rows,'ways'=>$ways);
	}
	// 物品产出/消耗
	function get_item_by_day($start_time,$end_time,$cur_page) {
		$page_size = GM_PAGE_SIZE;
		$real_start_time = date('Y-m-d', strtotime("$start_time +$cur_page day"));
		$next_time = date('Y-m-d', strtotime("$real_start_time +$page_size day"));
		$end_time = date('Y-m-d',strtotime("$end_time +1 day"));
		$real_end_time = $end_time;
		if ( $end_time > $next_time ) {
			$real_end_time = $next_time;
		}

		$data = array(
			'real_start_time' =>  $real_start_time,
			'real_end_time' =>  $real_end_time,
			'day' => array(),
		);

		$rows = $this->mahjong->query("select item_id,date(deadline) as `date`,sum(item_num) as `total` from item_log where deadline between ? and ? and item_num>0 and way like 'sys.%' group by item_id,`date`",array($real_start_time,$real_end_time))->result_array();
		foreach ($rows as $row) {
			$date = $row['date'];
			$item_id = $row['item_id'];
			$data['day']["{$date}_product_{$item_id}"] = $row['total'];
		}
		$rows = $this->mahjong->query("select item_id,date(deadline) as `date`,sum(item_num) as `total` from item_log where deadline between ? and ? and item_num<0 and way like 'sys.%' group by item_id,`date`",array($real_start_time,$real_end_time))->result_array();
		foreach ($rows as $row) {
			$date = $row['date'];
			$item_id = $row['item_id'];
			$data['day']["{$date}_consume_{$item_id}"] = $row['total'];
		}

		$data['total_rows'] = ceil((strtotime($end_time)-strtotime($start_time))/(24*60*60))+1;
		return $data;
	}
	// 物品产出与消耗
	function get_item_by_way($item_id,$start_time,$end_time,$cur_page) {
		$page_size = GM_PAGE_SIZE;
		$real_start_time = date('Y-m-d', strtotime("$start_time +$cur_page day"));
		$next_time = date('Y-m-d', strtotime("$real_start_time +$page_size day"));

		$end_time = date('Y-m-d',strtotime("$end_time +1 day"));
		$real_end_time = $end_time;
		if ( $end_time > $next_time ) {
			$real_end_time = $next_time;
		}

		$data = array(
			'real_start_time' =>  $real_start_time,
			'real_end_time' =>  $real_end_time,
			'ways' => array(),
			'day' => array(),
		);

		$this->load->model('config_model');
		$rows = $this->mahjong->query("select way,date(deadline) as `date`,sum(item_num) as `total` from item_log where item_id=? and deadline between ? and ? and way like 'sys.%' group by `date`,way",array($item_id,$real_start_time,$real_end_time))->result_array();
		foreach ($rows as $row) {
			$date = $row['date'];
			$way = $row['way'];

			$way = $this->config_model->get_item_way($way);
			$row['way'] = $way;
			if ( in_array($way, $data['ways']) == false ) {
				array_push($data['ways'],$way);
			}
			$data['day']["{$date}_product_{$way}"] = $row['total'];
		}
		$data['total_rows'] = ceil((strtotime($end_time)-strtotime($start_time))/(24*60*60))+1;
		return $data;
	}
}

