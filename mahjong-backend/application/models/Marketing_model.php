<?php

#苍天啊，大地啊
#代理系统2.0，这已经是我写的第第第第X个推广系统了
#主要差异在于用户绑定上级后，商城价格会有折扣
# V1.0.0 钟祥代理 成为代理时，会指定一个上级代理，返利直接给上级代理返利
# V1.2.0 江湖代理 成为代理后，会给自己返利大头

class Marketing_Model extends CI_Model {
	function __construct() {
		parent::__construct();
		$this->load->database();
		$this->load->helper('errcode');
		$this->load->model('user_model');
		$this->load->model('config_model');
	}
	
	public function get_version() {
		if ( defined("MARKETING_VERSION") ) {
			return MARKETING_VERSION;
		}
		$version = $this->config_model->get("web","MarketingVersion","Version");
		if ( $version ) {
			return $version;
		}
		return "1.0.0";
	}

	public function get_level_name($level) {
		$levels = $this->get_level_list();
		$level = intval($level);
		return $levels[$level];
	}

	public function get_level_list() {
		return array('游客','三级','二级','一级');	
	}
	
	public function add_user($user) {
		$uid = $user['uid'];
		$level = $user['level'];
		$wx = $user['wx'];
		$phone = $user['phone'];
		$name = $user['name'];
		$agent_uid = $user['agent_uid'];
		$this->db->query("insert marketing_user(uid,agent_uid,level,wx,phone,name) values(?,?,?,?,?,?) on duplicate key update level=?,wx=?,phone=?,name=?,agent_uid=?",array($uid,$agent_uid,$level,$wx,$phone,$name,$level,$wx,$phone,$name,$agent_uid));

		// 分配推广码
		$row = $this->db->query("select r.code from gm_rand_code r left join marketing_user_code u on r.code=u.code where uid is null order by r.id limit 1")->row_array();
		$code = $row['code'];
		$this->db->query("insert ignore marketing_user_code(uid,code) values(?,?)",array($uid,$code));
	}

	public function get_uid_by_code($code) {
		$row = $this->db->query("select uid from marketing_user_code where code=?",array($code))->row_array();
		if ( !$row ) {
			return 0;
		}
		return $row['uid'];
	}

	public function bind_code($uid, $code) {
		$info = $this->get_user_info($uid);
		if ( @$info['parent_uid'] ) {
			return err_code(1001,"已绑定");
		}

		$parent_uid = $this->get_uid_by_code($code);
		if ( !$parent_uid || $uid == $parent_uid) {
			return err_code(1002,"推广码无效");
		}
		$parent_info = $this->get_user_info($parent_uid);	
		if ( !$parent_info || !$parent_info['level'] ) {
			return err_code(1003,"对方未成为代理");
		}
		
		$level = $parent_info['level'];
		$s = $this->config_model->get("config","MarketingUserRate","Value"); 
		$user_rates = explode(",",$s);

		$s = $this->config_model->get("config","MarketingAgentRate","Value"); 
		$agent_rates = explode(",",$s);

		$grandpa_uid = $parent_info['parent_uid'];
		if ( $parent_info['agent_uid']>0 && $parent_info['level']>0 ) {
			$grandpa_uid = $parent_info['agent_uid'];
		}
		$relation = array("uid"=>$uid,"parent_uid"=>$parent_uid,"parent_rate"=>$user_rates[$level]);		
		if ( $grandpa_uid ) {
			$relation['grandpa_uid'] = $grandpa_uid;
			$relation['grandpa_rate'] = $agent_rates[$level];
		}
		// 建立绑定关系
		$this->db->insert("marketing_relation",$relation);
		return err_code(0,'SUCCESS');
	}

	// 申请提现
	public function draw_cash($uid, $rmb) {
		// 提现次数限制
		$row = $this->db->query("select 1 from marketing_draw_cash where uid=? and apply_time between ? and ?", array($uid, date('Y-m-d'),date('Y-m-d',time()+24*60*60)))->row_array();
		if ( $row ) {
			return "今儿个已申请，明天再来吧";
		}

		$info = $this->get_user_info($uid);
		$balance = floatval($info['balance']);

		if ($rmb <= 0 || $rmb > $balance) {
			return "余额不足";
		}
		$this->db->query('update marketing_user set balance=balance+(?) where uid=?',array(-$rmb,$uid));
		$this->db->insert('marketing_draw_cash',array('uid'=>$uid,'apply_rmb'=>$rmb));
		return "SUCCESS";
	}

	public function get_user_info($uid) {
		$user = array();
		$row = $this->db->query("select * from marketing_user where uid=?",array($uid))->row_array();
		if ( $row ) {
			$row['level_name'] = $this->get_level_name($row['level']);
		}
		$user = array_merge($user,(array)$row);
		// 申请的流水
		$row = $this->db->query("select sum(apply_rmb) as apply_rmb from marketing_draw_cash where uid=? and status = 0", array($uid))->row_array();
		$user = array_merge($user, (array)$row);

		// 推广码
		$row = $this->db->query("select code from marketing_user_code where uid=?",array($uid))->row_array();
		$user = array_merge($user,(array)$row);

		// 上级
		$row = $this->db->query("select * from marketing_relation where uid=?",array($uid))->row_array();
		if ( $row ) {
			$row['bind_time'] = $row['create_time'];
			unset($row['create_time']);
		}
		$user = array_merge($user,(array)$row);

		// 下级代理人数
		$row = $this->db->query("select count(*) as agent_lv2 from marketing_user where agent_uid=?", array($uid))->row_array();
		$user = array_merge($user, (array)$row);
		// 二级会员人数
		$row = $this->db->query("select count(*) as users_lv2 from marketing_relation where parent_uid=?", array($uid))->row_array();
		$user = array_merge($user, (array)$row);
		// 二级会员人数
		$row = $this->db->query("select count(*) as users_lv3 from marketing_relation where grandpa_uid=?", array($uid))->row_array();
		$user = array_merge($user, (array)$row);

		// 累计收益
		$row = $this->db->query("select sum(parent_rebate) as rebate_lv2 from marketing_pay_log where parent_uid=?", array($uid))->row_array();
		$user = array_merge($user, (array)$row);
		$row = $this->db->query("select sum(grandpa_rebate) as rebate_lv3 from marketing_pay_log where grandpa_uid=?", array($uid))->row_array();
		$user = array_merge($user, (array)$row);
		$user['total_rebate'] = floatval(@$user['rebate_lv2'])+floatval(@$user['rebate_lv3']);

		// 累计充值
		$row = $this->db->query("select sum(rmb) as pay_lv2 from marketing_pay_log where parent_uid=?", array($uid))->row_array();
		$user = array_merge($user, (array)$row);
		$row = $this->db->query("select sum(rmb) as pay_lv3 from marketing_pay_log where grandpa_uid=?", array($uid))->row_array();
		$user = array_merge($user, (array)$row);

		// 本月收益
		$month_rebate = 0;
		$row = $this->db->query("select sum(parent_rebate) as rebate from marketing_pay_log where parent_uid=? and create_time>=?", array($uid, date('Y-m-01')))->row_array();
		$month_rebate += floatval(@$row['rebate']);
		$row = $this->db->query("select sum(grandpa_rebate) as rebate from marketing_pay_log where grandpa_uid=? and create_time>=?", array($uid, date('Y-m-01')))->row_array();
		$month_rebate += floatval(@$row['rebate']);
		$user['month_rebate'] = $month_rebate;

		// 提现中
		$apply_rebate = 0;
		$row = $this->db->query("select sum(apply_rmb) as apply_rebate from marketing_draw_cash where uid=? and status=0", array($uid))->row_array();
		$user = array_merge($user, (array)$row);
		$row = $this->db->query("select * from marketing_user where uid=?",array($uid))->row_array();
		$user = array_merge($user, (array)$row);
		return $user;
	}

	public function draw_cash_list ($uid,$start_time,$end_time,$page) {
		$page_size = GM_PAGE_SIZE;
		$where = " where 1=1";
		if ( $uid ) {
			$where .= " and uid=$uid";
		}
		if ( $start_time ) {
			$where .= " and apply_time>='$start_time'";
		}
		if ( $end_time ) {
			$where .= " and apply_time<'$end_time'";
		}
		$sql = "select * from marketing_draw_cash $where order by id desc limit ?,?";
		$rows = $this->db->query($sql,array($page,$page_size))->result_array();

		foreach ((array)$rows as $k=>$row) {
			$uid = $row["uid"];
			$user = $this->get_user_info($uid);
			$rows[$k]["name"] = $user["name"];
		}
		$sql = "select count(*) as total_rows,sum(apply_rmb) as total_rmb from marketing_draw_cash $where";
		$row = $this->db->query($sql)->row_array();

		$data = array('rows'=>$rows);
		$data = array_merge($data, (array)$row);
		return $data;
	}

	public function check_draw_cash ($id, $agree) {
		$row = $this->db->query("select * from marketing_draw_cash where id=?", array($id))->row_array();
		if ( !$row ) {
			return "工单不存在";
		}
		if ( $row["status"] ){
			return "工单已审批";	
		}
		
		$status = 0;
		if ( $agree == false ) {
			$status = 2; // 工单未通过
		} 
		
		if ( $status == 0 ) {
			$uid = $row['uid'];
			$amount = $row['apply_rmb'];
			$date = date('Y-m-d', strtotime($row['apply_time']));
			$desc = "{$date}申请提现{$amount}已发放，感谢您的支持";

			$info = $this->get_user_info($uid);
			$user_name = $info['name'];

			$user_info = $this->user_model->get_user_info($uid);
			if ( empty( $user_info['wx_open_id']) ) {
				return "玩家不存在";
			}
			$wx_open_id = $user_info['wx_open_id'];
			
			$this->load->model("weixin_model");
			$msg = $this->weixin_model->give_user_money($wx_open_id,$user_name,$amount,$desc);
			if ( $msg != "SUCCESS" ) {
				return $msg;
			}

			// OK
			$status = 1;
		}

		$this->db->query("update marketing_draw_cash set status=? where id=?",array($status,$id));
		return "SUCCESS";
	}

	public function get_user_list($uid_list,$agent_uid,$parent_uid,$grandpa_uid,$page) {
		$data = array();
		if ( $agent_uid ) {
			$rows = $this->db->query("select uid from marketing_user where agent_uid=? order by id desc limit ?,?",array($agent_uid,$page,GM_PAGE_SIZE))->result_array();
			foreach ( $rows as $row ) {
				$uid = $row['uid'];
				array_push($uid_list,$uid);
			}
			$row = $this->db->query("select count(*) as total_rows from marketing_user where agent_uid=?",array($agent_uid))->row_array();
			$data = array_merge($data,$row);
		} else if ( $parent_uid ) {
			$rows = $this->db->query("select uid from marketing_relation where parent_uid=? order by id desc limit ?,?",array($parent_uid,$page,GM_PAGE_SIZE))->result_array();
			foreach ( $rows as $row ) {
				$uid = $row['uid'];
				array_push($uid_list,$uid);
			}
			$row = $this->db->query("select count(*) as total_rows from marketing_relation where parent_uid=?",array($parent_uid))->row_array();
			$data = array_merge($data,$row);
		} else if ( $grandpa_uid ) {
			$rows = $this->db->query("select uid from marketing_relation where grandpa_uid=? order by id desc limit ?,?",array($grandpa_uid,$page,GM_PAGE_SIZE))->result_array();
			foreach ( $rows as $row ) {
				$uid = $row['uid'];
				array_push($uid_list,$uid);
			}
			$row = $this->db->query("select count(*) as total_rows from marketing_relation where grandpa_uid=?",array($grandpa_uid))->row_array();
			$data = array_merge($data,$row);

		} else if ( !$uid_list ) {
			$rows = $this->db->query("select id,uid from marketing_user order by id desc limit ?,?",array($page,GM_PAGE_SIZE))->result_array();
			foreach ( $rows as $row ) {
				$uid = $row['uid'];
				array_push($uid_list,$uid);
			}
			$row = $this->db->query("select count(*) as total_rows from marketing_user")->row_array();
			$data = array_merge($data,$row);
		}

		$users = array();
		foreach ( $uid_list as $uid ) {
			$user = $this->get_user_info($uid);
			array_push($users,$user);
		}
		$data['rows'] = $users;
		return $data;
	}
	public function pay_ok($uid,$rmb) {
		// 增加分享日志
		$pay = array(
			"buy_uid" => $uid,
			"rmb" => $rmb,
		);
		$data = $this->db->query("select parent_uid,parent_rate,grandpa_uid,grandpa_rate from marketing_relation where uid=?",array($uid))->row_array();
		
		$parent_info = array();
		$grandpa_info = array();
		if ( $data ) {
			$parent_info = $this->get_user_info($data['parent_uid']);
			$grandpa_info = $this->get_user_info($data['grandpa_uid']);
		}
		if ( $this->get_version() == "1.2.0" ) {
			$s = $this->config_model->get("config","MarketingUserRate","Value"); 
			$agent_rates = explode(",",$s);

			$info = $this->get_user_info($uid);
			$pay['grandpa_uid'] = 0;
			if ( $info && isset($info['level']) && $info['level']>0 ) {
				$data['parent_rate'] = $agent_rates[$info['level']];

				$pay['parent_uid'] = $uid;
				if ( isset($parent_info['uid']) ) {
					$pay["grandpa_uid"] = $parent_info['uid'];
				}
			} else if ( $data ) {
				$level = $parent_info['level'];
				$data['parent_rate'] = $agent_rates[$level];

				$pay['parent_uid'] = $data['parent_uid'];
				$pay['grandpa_uid'] = $data['grandpa_uid'];
			}

			if ( $data ) {
				$data['grandpa_rate'] = 0.0;
				if ( $pay["grandpa_uid"] ) {
					$rate = $this->config_model->get("config","MarketingNextAgentRate","Value"); 
					$data['grandpa_rate'] = $rate * $data['parent_rate'];
				}
			}
		} else if ( $data ) {
			$pay['parent_uid'] = $data['parent_uid'];

			if ( $parent_info && $grandpa_info && $parent_info['level'] >= $grandpa_info['level'] ) {
				$data['grandpa_rate'] = 0;
			}
			$pay['grandpa_uid'] = $data['grandpa_uid'];
		}

		if ( $data) {
			$pay['parent_rebate'] = $rmb * $data['parent_rate'];
			$pay['grandpa_rebate'] = $rmb * $data['grandpa_rate'];
			$this->db->insert('marketing_pay_log',$pay);
			$this->db->query("update marketing_user set balance=balance+(?) where uid=?",array($pay['parent_rebate'],$pay['parent_uid']));
			$this->db->query("update marketing_user set balance=balance+(?) where uid=?",array($pay['grandpa_rebate'],$pay['grandpa_uid']));
		}
	}
	// 最近的二级、三级奖励
	public function get_last_rebate($uid, $page) {
		$page_size = GM_PAGE_SIZE;
		$data = array();
		$rows = $this->db->query("select * from marketing_pay_log where (parent_uid=? or grandpa_uid=?) order by id desc limit ?,?", array($uid,$uid,$page,$page_size))->result_array();
		foreach ((array)$rows as $k=>$row) {
			$user = $this->user_model->get_user_info( $row['buy_uid'] );
			$row['nickname'] = $user['nickname'];

			$rows[$k] = $row;
		}
		$data['rows'] = $rows;
		$row = $this->db->query("select count(*) as total_rows from marketing_pay_log where (parent_uid=? or grandpa_uid=?)", array($uid,$uid))->row_array();
		$data = array_merge($data,(array)$row);
		return $data;
	}
	// 会员名单
	public function get_children_list($uid,$page) {
		$data = array();
		$page_size= GM_PAGE_SIZE;
		$rows = $this->db->query("select uid,parent_uid,create_time from marketing_relation where parent_uid=? order by id desc limit ?,?", array($uid,$page,$page_size))->result_array();
		
		foreach ((array)$rows as $k=>$row) {
			$user = $this->user_model->get_user_info( $row['uid'] );
			$row['nickname'] = $user['nickname'];
			
			$r = $this->db->query("select sum(parent_rebate) as total_rebate from marketing_pay_log where buy_uid=? and parent_uid=?",array($row['uid'],$uid))->row_array();
			$row = array_merge($row,(array)$r);

			$rows[$k] = $row;
		}
		$data['rows'] = $rows;
		$row = $this->db->query("select count(*) as total_rows from marketing_relation where parent_uid=?", array($uid))->row_array();
		$data = array_merge($data,(array)$row);
		return $data;
	}
	public function add_children($uid,$children) {
		$info = $this->get_user_info($uid);
		$level = $info['level'];
		// 下级
		$children_levels = array(0,0,1,2,0);
		$children_limits = array(0,0,20,30,0);
		$children_level = $children_levels[$level];
		$row = $this->db->query("select count(*) as total from marketing_user where agent_uid=? and level=?",array($uid,$children_level))->row_array();
		$limit = $children_limits[$level];
		if ( $row['total'] >= $limit) {
			return "等级不够或下级代理数量>=$limit";
		}

		$cuid = $children['uid'];
		$cinfo = $this->get_user_info($cuid);
		if ( $cinfo && $cinfo['level']>0 ) {
			return "对方已成为代理";
		}

		$cinfo = $this->user_model->get_user_info($cuid);
		if ( !$cinfo || $cuid == $uid) {
			return "用户不存在";
		}

		$children['wx'] = '';
		$children['agent_uid'] = $uid;
		$children['level'] = $children_level;
		$this->add_user($children);
		return "SUCCESS";
	}
	// 下级代理名单
	public function get_agent_list($uid,$page) {
		$data = array();
		$page_size= GM_PAGE_SIZE;
		$rows = $this->db->query("select * from marketing_user u left join marketing_user_code uc on u.uid=uc.uid where agent_uid=? order by u.id desc limit ?,?", array($uid,$page,$page_size))->result_array();
		foreach ((array)$rows as &$row) {
			$r = $this->db->query("select sum(grandpa_rebate) as total_rebate from marketing_pay_log where parent_uid=? and grandpa_uid=?",array($row['uid'],$uid))->row_array();
			$row = array_merge($row,(array)$r);
		}

		$data['rows'] = $rows;
		$row = $this->db->query("select count(*) as total_rows from marketing_user where agent_uid=?", array($uid))->row_array();
		$data = array_merge($data,(array)$row);
		return $data;
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
		// 申请提现总额
		$row = $this->db->query("select sum(apply_rmb) as total from marketing_draw_cash where apply_time between ? and ?",array($start_time,$end_time))->row_array();
		$data['total_marketing_apply_rmb'] = $row['total'];
		$rows = $this->db->query("select date(apply_time) as `date`,sum(apply_rmb) as total from marketing_draw_cash where apply_time between ? and ? group by `date`",array($real_start_time,$real_end_time))->result_array();
		foreach ($rows as $row) {
			$data['day'][$row['date']]['marketing_apply_rmb'] = $row['total'];
		}
		// 已发放提现总额
		$row = $this->db->query("select sum(apply_rmb) as total from marketing_draw_cash where status=1 and apply_time between ? and ?",array($start_time,$end_time))->row_array();
		$data['total_marketing_agree_rmb'] = $row['total'];
		$rows = $this->db->query("select date(apply_time) as `date`,sum(apply_rmb) as total from marketing_draw_cash where status=1 and apply_time between ? and ? group by `date`",array($real_start_time,$real_end_time))->result_array();
		foreach ($rows as $row) {
			$data['day'][$row['date']]['marketing_agree_rmb'] = $row['total'];
		}

		return $data;
	}

}
