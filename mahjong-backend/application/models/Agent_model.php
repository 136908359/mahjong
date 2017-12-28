<?php

require_once APPPATH.'third_party/uuid.php';
class Agent_Model extends CI_Model {
	function __construct() {
		parent::__construct();
		$this->load->database();
		$this->load->model('user_model');
	}
	function get_agent_info($uid) {
		$info = $this->db->query("select uid,card as agent_card,`level` as agent_level,create_time as agent_create_time from agent_member where uid=?",array($uid))->row_array();

		if ( $info ) {
			$user = $this->user_model->get_user_info($uid);
			$info = array_merge((array)$info, (array)$user);

			$info['high_agent'] = false;
			$info['agent_level'] = intval($info['agent_level']);
			//  level>=100算高级代理，可以给低级代理发房卡
			if ($info['agent_level'] >= 100) {
				$info['high_agent'] = true;
			}
			$log = $this->db->query("select sum(agent_card) as total_agent_card, sum(game_card) as total_game_card from agent_card_log where uid=?",array($uid))->row_array();
			$info = array_merge($info, $log);
		}
		return $info;
	}
	// 增加房卡
	function create_agent($agent_uid,$level) {
		$this->db->query("insert ignore into agent_member(uid,`level`,create_time) values(?,?,?) on duplicate key update `level`=?",array($agent_uid,$level,date('Y-m-d H:i:s'),$level));
	}

	// 增加房卡
	function add_card($agent_uid,$other_uid,$game_card,$agent_card) {
		if ($agent_uid == $other_uid && $agent_card > 0) {
			return "点错了，给自己发放卡了";
		}
		if ($game_card < 0 || $agent_card < 0 || $game_card + $agent_card == 0) {
			return "房卡数量无效";
		}
		
		$agent = $this->get_agent_info($agent_uid);
		$total_card = intval($agent['agent_card']);
		if ( !$agent || $total_card < $game_card+$agent_card) {
			return "房卡不足";
		}
		$other_agent = $this->get_agent_info($other_uid);
		if ( !$other_agent && $agent_card>0 ) {
			return "对方未成为代理";
		}

		// 扣除房卡
		$this->db->query("update agent_member set card=card-? where uid=?",array($agent_card+$game_card,$agent_uid));
		// 插入日志
		$this->db->query("insert into agent_card_log(uid,agent_uid,game_card,agent_card,balance,create_time) values(?,?,?,?,?,?)",array($other_uid,$agent_uid,$game_card,$agent_card,$total_card-$agent_card-$game_card,date('Y-m-d H:i:s')));
		// 增加房卡
		if ($agent_card > 0) {
			$this->db->query("insert into agent_member(uid,parent_uid,card,create_time) values(?,?,?,?) on duplicate key update card=card+?",array($other_uid,$agent_uid,$agent_card,date('Y-m-d H:i:s'),$agent_card));
		}
		if ($game_card > 0) {
			$this->load->model('gm_model');
			$this->gm_model->request('/AddItems',array("UId"=>intval($other_uid),"GUID"=>create_uuid(),"Way"=>"agent","Items"=>array(array("Id"=>1004,"Num"=>$game_card))));
		}
		return "SUCCESS";
	}
	function get_card_log($agent_uid = null, $page, $page_num) {
		$sql = "select * from agent_card_log where 1=1";
		if ( $agent_uid ) {
			$sql .= " and agent_uid=$agent_uid";
		}
		$start_page = ($page-1) * $page_num;
		$sql .= " order by id desc limit $start_page,$page_num";
		$rows = $this->db->query($sql)->result_array();
		foreach ($rows as $k => $row) {
			$uid = $row['uid'];
			$user = $this->user_model->get_user_info($uid);
			if ($user) {
				$row['nickname'] = $user['nickname'];
			}
			$rows[$k] = $row;
		}
		return $rows;
	}
	function get_total_card_log($agent_uid = null) {
		$sql = "select count(*) as total from agent_card_log where 1=1";
		if ( $agent_uid ) {
			$sql .= " and agent_uid=$agent_uid";
		}
		$row = $this->db->query($sql)->row_array();
		if ($row) {
			return $row['total'];
		}
		return 0;
	}
	}
