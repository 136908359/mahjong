<?php
class User_Model extends CI_Model {
	function __construct() {
		parent::__construct();
		$this->load->database();
		$this->mahjong = $this->load->database('mahjong',TRUE);
	}
	function get_user_info($uid) {
		$user = $this->mahjong->query("select * from user_info where uid=?",array($uid))->row_array();	
		if ( $user ) {
			$row = $this->db->query("select sum(rmb) as total_pay from charge_order where buy_uid=? and result=3",array( $uid ))->row_array();	
			$user = array_merge($user,(array)$row);
			// 微信的openid
			$row = $this->mahjong->query("select open_id as wx_open_id from user_weixin where union_id=?",array($user['open_id']))->row_array();
			$user = array_merge($user,(array)$row);
		}

		return $user;
	}
}
