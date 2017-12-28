<?php
class Plate_Model extends CI_Model {
	function __construct() {
		parent::__construct();
		$this->load->database();
		$this->mahjong = $this->load->database('mahjong',TRUE);
	}
	
	// 已实名认证信息
	public function get_verify_info($uid) {
		return $this->db->query("select uid,real_name,idcard,phone from plate_real_name where uid=?", array($uid))->row_array();
	}

	// 实名认证
	public function verify_info($uid, $real_name,$idcard,$phone) {
		$this->db->query("insert ignore into plate_real_name(uid,idcard,real_name,phone) values(?,?,?,?)", array($uid,$idcard,$real_name,$phone));
		if ( $this->db->affected_rows() == 0 ) {
			return "认证失败或已认证";
		}
		return "SUCCESS";
	}

	public function is_phone_bind($phone) {
		$row = $this->db->query("select 1 from plate_real_name where phone = $phone limit 1")->row_array();
		if ( !$row ) {
			return false;
		}
		return true;
	}

	// 个人信息
	public function get_person_info($uid) {
		return $this->db->query("select uid,real_name,phone,address from plate_person_info where uid=?", array($uid))->row_array();
	}

	// 录像数据
	public function get_av($id) {
		$mj = $this->load->database('mahjong', TRUE);
		$row = $mj->query("select replay from game_grade where id=?", array($id))->row_array();
		if (empty($row) == true) {
			die("数据不存在");
		}
		// echo "SUCCESS+";
		return $row['replay'];
	}
	// 回放
	public function get_grade($grade_id) {
		return $this->mahjong->query("select grade_id,uid,sub_id,room_id,host_id,order_id,users,end_time from game_user_grade u left join game_grade g on u.grade_id=g.id where g.id=?",array($grade_id))->row_array();
	}
	public function get_grade_history($uid) {
		$start_time = date("y-m-d",strtotime("-3 days"));
		$grades = $this->mahjong->query("select grade_id,uid,sub_id,room_id,host_id,order_id,users,end_time from game_user_grade u left join game_grade g on u.grade_id=g.id where uid=? and end_time >=? order by grade_id desc",array($uid,$start_time))->result_array();
		return $grades;
	}
	// 4、8、12局回放
	public function get_alone_grades($grade_id) {
		$grade = $this->get_grade($grade_id);
		if ( !$grade ) {
			return array();
		}
		$uid = $grade['uid'];
		$history = $this->get_grade_history($uid);

		$grades = array();
		
		$found = false;
		$exist_times = 0;
		foreach ($history as $grade) {
			if ( $exist_times <= $grade['order_id']) {
				if ( $found == true ) {
					break;
				}
				$grades = array();
			}
			if ( $grade['grade_id'] == $grade_id ) {
				$found = true;
			}
			array_push($grades,$grade);
			$exist_times = $grade['order_id'];
		}
		return $grades;
	}
	// 总回放
	public function get_union_grades($uid) {
		$history = $this->get_grade_history($uid);

		$grades = array();
		$exist_times = 0;
		foreach ($history as $grade) {
			if ( $exist_times <= $grade['order_id']) {
				array_push($grades,$grade);
			}
			$exist_times = $grade['order_id'];
		}
		return $grades;
	}
	// 自己创建房间的记录
	public function get_private_room_history($uid) {
		$start_time = date("y-m-d",strtotime("-30 days"));
		$history = $this->mahjong->query("select id as grade_id,sub_id,room_id,host_id,order_id,users,end_time from game_grade where host_id=? and end_time >=? order by id desc",array($uid,$start_time))->result_array();

		$last = array();
		$exist_times = 0;
		foreach ($history as $grade) {
			if ( $exist_times < $grade['order_id']) {
				array_push($last,$grade);
			}
			$exist_times = $grade['order_id'];
		}
		return $last;
	}
}
