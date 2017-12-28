<?php
class System_Model extends CI_Model {
	function __construct() {
		parent::__construct();
		$this->load->database();
	}
	
	/*
	 * 活动管理
	 */	
	//获取活动形式列表
	public function get_activity_type(){
		$sql = "select id,title,coment from activity_type";
		return $this->db->query($sql,array())->result_array();
	}
	
	//添加活动形式
	public function add_actype($data){
		$result = $this->db->replace('activity_type',$data);
		return $result;
	}
	
	//获取活动列表
	public function get_activity_list(){
		$sql="select a.id,a.`title`,a.start_time,a.end_time,a.params,b.title as actype from activity a left join activity_type b on a.style=b.id order by a.id";
		return $this->db->query($sql,array())->result_array();
	}
	
	//获取当前活动信息
	public function get_activity_by_id($id){
		$sql="select id,`title`,start_time,end_time,style,`image`,rmb,`items`,`params` from activity where id=?";
		return $this->db->query($sql,array($id))->row_array();
	}
	
	//添加活动
	public function add_activity($arr){
		$title = $arr['title'];
		$start_time = $arr['start_time'];
		$end_time = $arr['end_time'];
		$style = $arr['style'];
		$image = $arr['image'];
		$rmb = $arr['rmb'];
		$items = $arr['items'];
		$params = $arr['params'];
		$sql="insert into activity(`title`,start_time,end_time,style,`image`,rmb,`items`,`params`) values(?,?,?,?,?,?,?,?)";
		$this->db->query($sql,array($title, $start_time, $end_time, $style, $image, $rmb, $items, $params));
		return $this->db->insert_id();
	}
	
	//修改活动
	public function edit_activity($arr,$id){
		return $this->db->update('activity', $arr, array('id' => $id));
	}
	
	/*
	 *广播
	 */
	public function get_broadcast_list(){
		$sql = "select id,`content`,start_time,end_time,`interval` from broadcast";
		return $this->db->query($sql,array())->result_array();
	}
	
	public function get_broadcast_by_id($id){
		$sql = "select id,`content`,start_time,end_time,`interval` from broadcast where id=?";
		return $this->db->query($sql,array($id))->row_array();
	}
	
	public function add_broadcast($arr){
		$this->db->insert('broadcast',$arr);
		return $this->db->insert_id();
	}
	
	public function edit_broadcast($arr,$id){
		return $this->db->update('broadcast', $arr, array('id' => $id));
	}

	// 分享个人信息
	public function get_person_info($uid) {
		return $this->db->query("select balance,wx,phone,name from share_member where uid=?", array($uid))->row_array();
	}

	// 更新个人信息
	public function update_person_info($uid, $data) {
		$this->db->where("uid",$uid);
		return $this->db->update("share_member", $data);
	}

	public function get_total_advise() {
		$row = $this->db->query("select count(*) as total from advise")->row_array();
		if ( $row ) {
			return $row['total'];
		}
		return 0;
	}
	public function get_last_advise($page_id,$page_num) {
		return $this->db->query("select * from advise order by id desc limit ?,?",array($page_id,$page_num))->result_array();	
	}
	public function update_advise($id,$result) {
		$this->db->query("update advise set result=? where id=? and result=1",array($result,$id));
	}
	// 更新配置
	public function update_config($name,$json) {
		$config = json_decode($json,true);

		$s = $this->get_config($name);
		$old_config = json_decode($s, true);
		$config = array_replace_recursive($old_config,$config);
		$json = json_encode($config);
		$this->db->query("insert gm_config(name,json) values(?,?) on duplicate key update json=?",array($name,$json,$json));
	}
	public function get_config($name) {
		$row = $this->db->query("select json from gm_config where `name`=?",array($name))->row_array();
		return @$row['json'];
	}
	// 客户端版本管理
	public function get_client_version_list() {
		$rows = $this->db->query("select * from gm_client_version order by id desc")->result_array();
		return $rows;
	}
	public function get_client_version($id) {
		if ( $id == null ) {
			return array();
		}
		$row = $this->db->query("select * from gm_client_version where id=?",array($id))->row_array();
		return $row;
	}
	public function add_client_version($id,$data) {
		if ( $id == null ) {
			$this->db->insert('gm_client_version',$data);
		} else {
			$this->db->update('gm_client_version',$data,"id = $id");
		}
	}
	// 配置表管理
	public function config_table_list() {
		$rows = $this->db->query("select id,name,comment,update_time,effect_time from gm_table order by name")->result_array();
		return $rows;
	}
	public function config_table($id) {
		$row = $this->db->query("select * from gm_table where id=?",array($id))->row_array();
		return $row;
	}
	// 设置配置表
	public function set_config_table($table) {
		$this->db->query("insert gm_table(name,comment,content) values (?,?,?) on duplicate key update name=?,comment=?,content=?",array($table['name'],$table['comment'],$table['content'],$table['name'],$table['comment'],$table['content']));
	}
	// 生效配置表
	public function effect_config_table($name) {
		$this->db->query("update gm_table set effect_time=now() where name=?",array($name));
	}
}
