<?php

class Share_Model extends CI_Model {
	function __construct() {
		parent::__construct();
		$this->load->database();
		$this->load->model('user_model');
	}
	public function get_rank_name($rank) {
		$ranks = $this->get_rank_list();
		$rank = intval($rank);
		return $ranks[$rank];
	}
	public function get_rank_list() {
		return array('游客','二级','一级');	
	}
	
	// 推广系统
	// 2016-02-13 推广码改为UID，废弃
	public function add_code($codes) {
		$this->db->trans_start();
		foreach ($codes as $k=>$code) {
			$this->db->query('insert ignore into share_code(user_code) values(?)',array($code));
		}
		$this->db->trans_complete();
	}

	// 2017-02-13 推广码改为UID
	// 2017-06-22 增加推广员门槛
	public function create_member($uid,$wx,$name,$phone,$rank) {
		// 增加推广成员
		$this->db->query('insert into share_member(uid,wx,name,phone,rank,create_time) values(?,?,?,?,?,?) on duplicate key update wx=?,name=?,phone=?,rank=?', array($uid,$wx,$name,$phone,$rank,date('Y-m-d H:i:s'),$wx,$name,$phone,$rank));
	}

	// 更新推广员
	public function update_member($data) {
		$uid = intval($data['uid']);
		$row = $this->db->query('select * from share_member where uid=?', array($uid))->row_array();
		$keys = array("balance");
		foreach ($keys as $key) {
			if (isset($data[$key]) == true) {
				$data[$key] += intval($row[$key]);
			}
		}
		$this->db->update('share_member', $data, "uid = $uid");
	}

	public function get_uid_by_code($code) {
		return $code;
	}

	// 2017-02-13 推广码改为UID
	public function get_bind_info($uid) {
		$data = array();
		// 当前绑定状态
		$row = $this->db->query("select parent_uid from share_relation where uid=?", array($uid))->row_array();
		if ( $row ) {
			$data['parent_uid'] = $row['parent_uid'];
			$data['parent_code'] = $row['parent_uid'];
		}

		// 推广码已存在
		$data["code"] = $uid;
		return $data;
	}

	public function bind_code($uid, $code) {
		$bind_info = $this->get_bind_info($uid);
		if ( empty($bind_info['parent_code']) == false ) {
			return "已绑定";
		}

		if ( $bind_info['code'] == $code ) {
			return "推广码无效";
		}
		
		$parent_uid = $this->get_uid_by_code($code);
		$mj = $this->load->database('mahjong', TRUE);
		$row = $mj->query("select 1 from user_info where uid=?", array($parent_uid))->row_array();
		if ( !$row ) {
			return "推广码无效";
		}
		
		// 判断上级是否是推广员
		$parent = $this->get_member($parent_uid);
		if ( !$parent || !$parent['rank'] ) {
			return "对方未成为代理";
		}
		
		$parents = array($parent_uid);
		$current_uid = $uid;
		for ($i=0; $i<6; $i++) {
			$info = $this->get_bind_info($current_uid);
			if ( empty($info['parent_uid']) ) {
				break;
			}

			$current_uid = $info['parent_uid'];
			if ( $uid == $current_uid ) {
				return "推广码无效";
			}
		 	array_push($parents, $current_uid);
		}

		// 二级
		if ( empty($parents[0]) == false ) {
			$parent_uid = $parents[0];
			$this->db->query("update share_month_data set users_lv2=users_lv2+1 where uid=?",array($parent_uid));
		}
		
		// 三级
		if ( empty($parents[1]) == false ) {
			$grandpa_uid = $parents[1];
			$this->db->query("update share_month_data set users_lv3=users_lv3+1 where uid=?",array($grandpa_uid));
		}
		
		// 建立绑定关系
		$this->db->replace("share_relation",array("uid"=>$uid,"parent_uid"=>$parent_uid,"create_time"=>date('Y-m-d H:i:s')));		
		
		return 'SUCCESS';
	}

	// 二级、三级奖励
	public function get_rebate($uid) {
		$data = array();
		foreach(array("parent","grandpa") as $s) {
			$last = $this->db->query("select buy_uid,cards,{$s}_rebate from share_pay_log where {$s}_uid=? and buy_time >= ? order by id desc limit 10", array($uid,date('Y-m-01')))->result_array();
			$total = $this->db->query("select count(*) as total_person,sum(cards) as total_card,sum({$s}_rebate) as total_rebate from share_pay_log where {$s}_uid=? and buy_time >= ?", array($uid,date('Y-m-01')))->row_array();
			$rows = array_merge(array('last'=>$last),$total);
			$data[$s] = $rows;
		}
		return $data;
	}

	// 最近的二级、三级奖励
	public function get_last_rebate($uid, $page, $page_num) {
		$data = $this->db->query("select * from share_pay_log where (parent_uid=? or grandpa_uid=?) order by id desc limit ?,?", array($uid,$uid,($page-1)*$page_num,$page_num))->result_array();
		
		if (empty($data) == false) {
			foreach ($data as $k=>$row) {
				$uid = $row['buy_uid'];
				$user = $this->user_model->get_user_info($uid);
				$row['nickname'] = $user['nickname'];

				$data[$k] = $row;
			}
		}
		return $data;
	}

	// 最近的二级、三级奖励
	public function get_total_rebate($uid) {
		$row = $this->db->query("select count(*) as total from share_pay_log where (parent_uid=? or grandpa_uid=?)", array($uid,$uid))->row_array();
		return $row['total'];
	}


	// 更新个人信息
	public function update_person_info($uid, $data) {
		$this->db->where("uid",$uid);
		return $this->db->update("share_member", $data);
	}

	// 申请提现
	public function draw_cash($uid, $rmb) {
		// 提现次数限制
		$row = $this->db->query("select 1 from share_draw_cash where uid=? and apply_time between ? and ?", array($uid, date('Y-m-d'),date('Y-m-d',time()+24*60*60)))->row_array();
		if (empty($row) == false) {
			return "今儿个已申请，明天再来吧";
		}

		$row = $this->get_member($uid);
		$balance = floatval($row['balance']);

		if ($rmb <= 0 || $rmb > $balance) {
			return "余额不足";
		}
		$this->db->query('update share_member set balance=balance+(?) where uid=?',array(-$rmb,$uid));
		$this->db->insert('share_draw_cash',array('uid'=>$uid,'apply_rmb'=>$rmb,'apply_time'=>date('Y-m-d H:i:s')));
		return "SUCCESS";
	}

	// 更新推广员月报
	public function update_month_data($data) {
		$uid = intval($data['uid']);
		$month = date('Y-m');
		$this->db->query("insert ignore into share_month_data(uid,month) values (?,?)", array($uid, $month));

		$row = $this->db->query("select rank from share_member where uid = $uid")->row_array();
		$data = array_merge($data, (array)$row);
		
		$row = $this->db->query("select * from share_month_data where uid=? and month=?", array($uid, $month))->row_array();
		$keys = array("cards", "pay_rmb", "users_lv2", "users_lv3", "pay_lv2", "pay_lv3", "rebate_lv2", "rebate_lv3", "interest");
		foreach ($keys as $key) {
			if (isset($data[$key]) == true) {
				$data[$key] += $row[$key];
			}
		}
		$this->db->update("share_month_data", $data, "uid = $uid and month = '$month'");
	}

	public function get_member($uid) {
		$member = $this->db->query("select * from share_member where uid=?",array($uid))->row_array();
		if ( !$member ) {
			return null;
		}
		$member['rank'] = intval($member['rank']);
		// 申请的流水
		$row = $this->db->query("select sum(apply_rmb) as apply_rmb from share_draw_cash where uid=? and status = 0", array($uid))->row_array();
		$member = array_merge($member, $row);

		/*$mj = $this->load->database('mahjong', TRUE);
		$row = $mj->query("select open_id,nickname,score_card,icon,create_time as user_create_time from user_info where uid=?", array($uid))->row_array();
		*/
		$this->load->model('user_model');
		$user = $this->user_model->get_user_info($uid);
		unset($user['phone']);
		unset($user['wx']);
		$member = array_merge((array)$member,(array)$user);
		
		$mj = $this->load->database('mahjong', TRUE);
		// 微信的openid
		$row = $mj->query("select open_id as wx_open_id from user_weixin where union_id=?",array($user['open_id']))->row_array();
		$member = array_merge((array)$member,(array)$row);

		// 推广码
		$bind_info = $this->get_bind_info($uid);
		$member['share_code'] = $bind_info['code'];
		$member['parent_uid'] = @$bind_info['parent_uid'];

		// 二级、三级推广人数
		$row = $this->db->query("select count(*) as users_lv2 from share_relation where parent_uid=?", array($uid))->row_array();
		$member = array_merge($member, $row);

		$row = $this->db->query("select count(*) as users_lv3 from share_relation a left join share_relation b on a.parent_uid=b.uid where b.parent_uid=?", array($uid))->row_array();
		$member = array_merge($member, $row);

		// 累计收益
		$total_rebate = 0;
		$row = $this->db->query("select sum(parent_rebate) as rebate from share_pay_log where parent_uid=?", array($uid))->row_array();
		if ( $row ) {
			$total_rebate += floatval($row['rebate']);
			$member['rebate_lv2'] = $row['rebate'];
		}
		$row = $this->db->query("select sum(grandpa_rebate) as rebate from share_pay_log where grandpa_uid=?", array($uid))->row_array();
		if ( $row ) {
			$total_rebate += floatval($row['rebate']);
			$member['rebate_lv3'] = $row['rebate'];
		}
		$member['total_rebate'] = $total_rebate;

		// 本月收益
		$month_rebate = 0;
		$row = $this->db->query("select sum(parent_rebate) as rebate from share_pay_log where parent_uid=? and buy_time>=?", array($uid, date('Y-m-01')))->row_array();
		if ( $row ) {
			$month_rebate += floatval($row['rebate']);
		}
		$row = $this->db->query("select sum(grandpa_rebate) as rebate from share_pay_log where grandpa_uid=? and buy_time>=?", array($uid, date('Y-m-01')))->row_array();
		if ( $row ) {
			$month_rebate += floatval($row['rebate']);
		}
		$member['month_rebate'] = $month_rebate;

		// 提现中
		$apply_rebate = 0;
		$row = $this->db->query("select sum(apply_rmb) as rebate from share_draw_cash where uid=? and status=0", array($uid))->row_array();
		if ( $row ) {
			$apply_rebate += floatval($row['rebate']);
		}
		$member['apply_rebate'] = $apply_rebate;

		return $member;
	}

	// 二级、三级推广
	public function get_last_share($uid,$page,$page_num) {
		$data = $this->db->query("select a.uid,a.parent_uid,b.parent_uid as grandpa_uid,a.create_time from share_relation a left join share_relation b on a.parent_uid=b.uid where a.parent_uid=? or b.parent_uid=? order by a.id desc limit ?,?", array($uid,$uid,($page-1)*$page_num,$page_num))->result_array();
		
		if (empty($data) == false) {
			foreach ($data as $k=>$row) {
				$uid = $row['uid'];
				$user = $this->user_model->get_user_info($uid);
				$row['nickname'] = $user['nickname'];

				$data[$k] = $row;
			}
		}
		return $data;
	}

	public function get_total_share($uid) {
		$row = $this->db->query("select count(*) as total from share_relation a left join share_relation b on a.parent_uid=b.uid where a.parent_uid=? or b.parent_uid=?", array($uid,$uid))->row_array();
		
		return $row['total'];
	}


	public function bind_weixin_unionid($uid, $unionid) {
		$this->db->query("insert ignore into share_weixin_relation(uid,union_id,create_time) values(?,?,?)",array($uid,$unionid,date('Y-m-d H:i:s')));
	}

	public function get_uid_by_openid ($open_id) {
		$mj = $this->load->database('mahjong', TRUE);
		$row = $mj->query("select open_id from user_weixin where union_id=? limit 1", array($open_id))->row_array();
		$wx_open_id = $open_id;
		if ( $row ) {
			$wx_open_id = $row['open_id'];	
		}
		$row = $mj->query("select uid from user_info where open_id=? or open_id=? limit 1", array($open_id, $wx_open_id))->row_array();
		if ( $row ) {
			return $row['uid'];
		}

		return 0;
	}

	public function get_draw_cash ($id, $page_num) {
		$sql = "select * from share_draw_cash where 1=1";
		if ($id != null) {
			$sql = $sql." and id<$id";
		}
		$sql = $sql." limit $page_num";
		$rows = $this->db->query($sql)->result_array();

		if ($rows) {
			foreach ($rows as $k=>$row) {
				$uid = $row["uid"];
				$member = $this->get_member($uid);
				$rows[$k]["name"] = $member["name"];
			}
		}
		return $rows;
	}

	public function check_draw_cash ($id, $agree) {
		$row = $this->db->query("select * from share_draw_cash where id=?", array($id))->row_array();
		if ( !$row ) {
			return "工单不存在";
		}
		if ( $row["status"] ){
			return "工单已审批";	
		}
		
		$status = 0;
		if ( $agree == false ) {
			$statues = 2; // 工单未通过
		} else {
			$uid = $row['uid'];
			$amount = $row['apply_rmb'];
			$date = date('Y-m-d', strtotime($row['apply_time']));
			$desc = "{$date}申请提现{$amount}已发放，感谢您的支持";

			$member = $this->get_member($uid);
			$user_name = $member['name'];
			$wx_open_id = $member['wx_open_id'];
			if ( !$wx_open_id  ) {
				return "玩家不存在";
			}
			
			$this->load->model("weixin_model");
			$msg = $this->weixin_model->give_user_money($wx_open_id,$user_name,$amount,$desc);
			// OK
			if ( $msg == "SUCCESS" ) {
				$status = 1;
			}
		}

		$this->db->query("update share_draw_cash set status=? where id=?",array($status,$id));
		return $msg;
	}

	// 分享绑定
	public function bind_by_openid($uid,$open_id) {
		$row = $this->db->query("select uid from share_weixin_relation where union_id=?",array($open_id))->row_array();
		if ( $row ) {
			$parent_uid = $row['uid'];
			$info = $this->get_bind_info($parent_uid);
			if ( $info ) {
				$this->bind_code($uid, $info['code']);
			}
		}
	}

	// 推广员清单
	public function get_last_member($uid_list,$start_time,$end_time,$page,$page_num) {
		$sql = "select * from share_member where 1=1";
		if ( $uid_list ) {
			$s = implode(",", $uid_list);
			$sql .= " and uid in ($s)";
		}
		$start_id = ($page-1) * $page_num;
		$sql .= " and create_time between '$start_time' and '$end_time' order by id desc limit $start_id,$page_num";
		$rows = $this->db->query($sql)->result_array();

		$data = array();
		foreach ($rows as $row) {
			$uid = $row['uid'];
			$member = $this->get_member($uid);
			$data []= $member;
		}
		return $data;
	}

	// 推广员总计
	public function get_total_member($uid_list,$start_time,$end_time) {
		$sql = "select count(*) as total from share_member where 1=1";
		if ( $uid_list ) {
			$s = implode(",", $uid_list);
			$sql .= " and uid in ($s)";
		}
		$sql .= " and create_time between '$start_time' and '$end_time'";
		$row = $this->db->query($sql)->row_array();
		if ( $row ) {
			return $row['total'];
		}
		return 0;
	}
}
