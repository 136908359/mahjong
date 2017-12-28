<?php

class Pay_Model extends CI_Model
{
	private $mahjong;
	private $manage;
	function __construct()
	{
		// Call the Model constructor
		parent::__construct();
		$this->load->database("mahjong", TRUE);
		$this->load->database("manage", TRUE);
	}
	
	// 更新订单信息
	public function update_order($data,$pay_sdk,$order_id){
		$db = $this->load->database ( 'manage', TRUE );
		$where = array(
				'order_id' => $order_id,
				'pay_sdk' => $pay_sdk
		);
		return $this->db->update('charge_order',$data,$where);
	}
	
	// 查询订单信息
	function get_order($pay_sdk,$order_id) {
		$db = $this->load->database ( 'manage', TRUE );
		$rs = $db->query("select order_id,chan_id,buy_uid,item_id,item_num,rmb,pay_sdk,notify_time,create_time,result from charge_order where order_id=? and pay_sdk=? limit 1",array($order_id,$pay_sdk));
		$rows = $rs->result_array();
		if (empty($rows)) {
			return array();
		}
		return $rows[0];
	}

	// 已完成并发放奖励的订单
	function count_perfect_order_by_uid($uid) {
		$db = $this->load->database ( 'manage', TRUE );
		$rs = $db->query("select count(*) as num from charge_order where buy_uid=? and result=3",array($uid));
		$row = $rs->row_array();
		return $row['num'];
	}

	function add_order($data) {
		$db = $this->load->database ( 'manage', TRUE );
		$data['first_pay'] = 0;
		$data['create_time'] = date('Y-m-d H:i:s', time());

		// $this->load->model('admin/admin_model');
		// $data['chan_id'] = $this->admin_model->get_long_chan_id($data['chan_id']);
		$db->insert('charge_order', $data);
		if ($db->affected_rows() != 1) {
			return false;
		}
		return true;
	}

	function add_new_order($data) {
		$data['result'] = 1; // 新订单
		return $this->add_order($data);
	}

	function add_good_order($data) {
		$data['result'] = 2; // 新订单
		return $this->add_order($data);
	}

	function finish_order($pay_sdk, $order_id) {
		$db = $this->load->database ( 'manage', TRUE );
		$this->load->model("share_model");

		$first = 0;
		$notify_time = date('Y-m-d H:i:s');
		$order = $this->get_order($pay_sdk, $order_id);
		if ( !$order || $order["result"] == ORDER_FINISH) {
			return;
		}

		$uid = intval($order['buy_uid']);
		$rmb = floatval($order['rmb']);
		$item_id = intval($order['item_id']); // 商品ID
		if  ($this->count_perfect_order_by_uid($uid) == 0) {
			$first = 1;
		}
		
		$db->query("update charge_order set first_pay=?,result=3,notify_time=? where pay_sdk=? and order_id=?",array($first, $notify_time, $pay_sdk, $order_id));
		if ($db->affected_rows() != 1) {
			return;
		}

		// OK
		$this->load->model("gm_model");
		$this->gm_model->request('/Pay', array(
			"OrderId" => $order_id,
			"UId" => $uid,
			"RMB" => $rmb,
			"ItemId" => $item_id,
			"ItemNum" => 1
		));

		/////////////////////////////////////////////////////////////////
		// 推广系统返利
		$request = $this->gm_model->request('/GetShopItem', array("ItemId"=>$item_id));
		$items = explode(",", str_replace("*",",",$request->String));

		$item_num = 0;
		for($i=0; 2*$i+1<count($items); $i++) {
			if (intval($items[2*$i]) == 1004) {
				$item_num += intval($items[2*$i+1]);
			}
		}

		if ($item_num > 0) {
			// 更新推广员当月充值金额
			$this->share_model->update_month_data( array (
				"uid" => $uid,
				"pay_rmb" => $rmb,
				"cards" => $item_num,
			));

			// 增加分享日志
			$pay_log = array(
				"buy_uid" => $uid,
				"rmb" => $rmb,
				"cards" => $item_num,
				"buy_time" => date('Y-m-d H:i:s'),
			);

			$last_rank = 0; $last_uid = $uid;
			$rates = array(0.2,0.1);
			$parents = array();
			for ($i=0; $i<2; $i++) {
				// 上一级推广员
				$data = $db->query("select parent_uid from share_relation where uid=?",array($last_uid))->row_array();
				// 上级推广不存在
				if ( !$data ) {
					break;
				}

				$last_uid = $data['parent_uid'];
				$member = $this->share_model->get_member($last_uid);
				if ( !$member ) {
					break;
				}
				if ( $last_rank > 0 && $last_rank >= $member['rank'] ) {
					break;
				}
				$last_rank = $member['rank'];
				$rebate = $rmb * $rates[$i];
				array_push($parents, array('rebate'=>$rebate,'uid'=>$last_uid));
				$db->query("update share_member set balance=balance+(?) where uid=?",array($rebate,$last_uid));
			}

			// 二级奖励
			if ( empty($parents[0]) == false ) {
				$parent = $parents[0]['uid'];
				$rebate = $parents[0]['rebate'];
				$pay_log['parent_uid'] = $parent;
				$pay_log['parent_rebate'] = $rebate;
				
				$this->share_model->update_month_data( array (
					"uid" => $parent,
					"pay_lv2" => $rmb,
					"rebate_lv2" => $rebate,
				));
			}

			// 三级奖励
			if ( empty($parents[1]) == false ) {
				$grandpa = $parents[1]['uid'];
				$rebate = $parents[1]['rebate'];
				$data['grandpa_uid'] = $parents[1];
				$data['grandpa_rebate'] = $rebate;

				$this->share_model->update_month_data( array (
					"uid" => $grandpa,
					"pay_lv3" => $rmb,
					"rebate_lv3" => $rebate,
				));
			}
			$db->insert('share_pay_log',$pay_log);
		}
		///////////////////////////////////////////////////////////////////////
		$this->load->model("marketing_model");
		$this->marketing_model->pay_ok($uid,$rmb);
	}
}

?>
