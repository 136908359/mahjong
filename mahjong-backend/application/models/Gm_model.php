<?php

class GM_model extends CI_Model
{
	function __construct()
	{
		// Call the Model constructor
		parent::__construct();
		$this->load->database ();
	}

	/* 物品ID参照表  */
	public function get_items(){
		$items = array(
			array(1000,'金币'),
			array(1004,'房卡'),
			array(1001,'钻石'),
			array(1002,'兑换券'),
			array(1003,'话费券'),
			array(2000,'周卡'),
			array(2001,'月卡'),
			array(2002,'VIP铜卡'),
			array(2003,'VIP银卡'),
			array(2004,'VIP金卡'),
			array(2005,'喇叭'),
			array(4000,'经验值'),
			array(4001,'VIP'),
		);
		return $items;
	}

	function request($uri, $data){
		$js = json_encode($data);
		$ch = curl_init ();
		curl_setopt ( $ch, CURLOPT_URL, GAME_ADDR.$uri);
		$header = array ();  
		$header [] = "Sign:".GM_SIGN;
		curl_setopt ( $ch, CURLOPT_HTTPHEADER, $header );
		curl_setopt ( $ch, CURLOPT_POST, 1 );
		curl_setopt ( $ch, CURLOPT_HEADER, 0 );
		curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt($ch, CURLOPT_POSTFIELDS,$js);
		$s = curl_exec ( $ch );
		curl_close ( $ch );
		$ret = json_decode($s);
		return $ret;
	}

	function compare_version($version1,$version2) {
		$a1 = explode(".",$version1,3);
		$a2 = explode(".",$version2,3);

		$w1="V"; $w2 = "V";
		for ($i=0; $i<3; $i++) {
			$w1 .= sprintf("%010s",$a1[$i]);
			$w2 .= sprintf("%010s",$a2[$i]);
		}
		return strcmp($w1,$w2);
	}

	function get_lastest_version($chan_id,$version) {
		// 指定版本
		$row = $this->db->query("select * from gm_client_version where chan_id=? and version=? limit 1",array($chan_id,$version))->row_array();
		// 版本存在
		if ( $row ) {
			return $row;
		}
		// 所有版本
		$rows = $this->db->query("select * from gm_client_version where chan_id=?",array($chan_id))->result_array();

		$last =  array("version"=>"1.0.0");
		if ($rows) {
			foreach ( $rows as $row ) {
				if ($this->compare_version($last['version'],$row['version']) < 0) {
					$last = $row;
				}
			}
		}
		return $last;
	}

	function get_config($name) {
		$row = $this->db->query("select json from gm_config where name=?", array($name))->row_array();
		return @$row['json'];
	}

	function save_config($name, $data) {
		$this->db->replace("gm_config", array("name"=>$name,"json"=>$data));
	}
}

?>
