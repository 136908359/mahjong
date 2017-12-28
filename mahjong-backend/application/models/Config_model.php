<?php

# 配置表解析

class Config_Model extends CI_Model {
	private $last_error;
	private $cache = array();
	function __construct() {
		parent::__construct();
		$this->load->database();
	}
	function get($name,$rowValue,$colValue) {
		$this->last_error = "";
		$info = $this->load_table($name);
		if ( !$info ) {
			return null;
		}

		$table = $info["Table"];
		$rowHeaders = $info["RowHeaders"];
		$colHeaders = $info["ColHeaders"];

		if ( !isset($rowHeaders[$rowValue]) || !isset($colHeaders[$colValue]) ) {
			$this->last_error = "$name [$rowValue:$colValue] not exist";
			return null;
		}
		$rowID = $rowHeaders[$rowValue];
		$colID = $colHeaders[$colValue];
		if ( !isset($table[$rowID][$colID]) ) {
			return null;
		}
		return $table[$rowID][$colID];
	}
	function parse_items($s) {
		$this->last_error = "";
		$items = array();

		$a = explode(";",$s);
		foreach( $a as $k=>$v ) {
			$item = explode("*",$v);
			$items []= array("Id"=>intval($item[0]),"Num"=>intval($item[1]));
		}
		return $items;
	}
	function parse($content) {
		$this->last_error = "";

		$content = str_replace("\r\n","\n",$content);	
		$lines = explode("\n",$content);

		$table = array();
		$rowHeaders = array();
		$colHeaders = array();
		foreach ($lines as $rowID=>$line) {
			$line = trim($line);
			if ( !$line ) {
				continue;
			}
			$cols = explode("\t",$line);
			if ( $rowID == 1 ) {
				foreach ( $cols as $colID => $col ) {
					$colHeaders[$col] = $colID;	
				}
			}
			$colNum = count($colHeaders);
			if ( $rowID > 1 ) {
				$rowHeaders[$cols[0]] = $rowID;
				if ( $colNum != count($cols) ) {
					$this->last_error = "[line:$rowID]$line is invalid";
				}
			}
			$table []= $cols;
		}
		return array(
			"Table"=>$table,
			"RowHeaders"=>$rowHeaders,
			"ColHeaders"=>$colHeaders,
		);
	}
	function get_last_error() {
		$err = $this->last_error;
		$err = str_replace("\t","  ",$err);
		return $err;
	}
	function get_item_way($way) {
		$a = explode(".",$way);
		$way = end($a);
		$title = $this->get("item_way",$way,"Title");
		if ( $title ) {
			$way = $title;
		}
		return $way;
	}
	function load_table($name) {
		$this->last_error = '';
		if ( isset($this->cache[$name]) ) {
			return $this->cache[$name];
		}
		$row = $this->db->query("select content from gm_table where name=?",array($name))->row_array();
		if ( !$row ) {
			$this->last_error = "$name not exist";
			return array();
		}
		$content = $row['content'];
		$info = $this->parse($content);
		$this->cache[$name] = $info;
		return $info;
	}
	function rows($name) {
		$this->last_error = '';
		$info = $this->load_table($name);
		if ( !$info ) {
			return array();
		}

		$data = array();
		foreach ( $info["Table"] as $row_id => $row ) {
			$a = array();
			if ( $row_id > 1 ) {
				foreach ( $info["ColHeaders"] as $col_name=>$col_id ) {
					$a[$col_name] = $row[$col_id];
				}
				array_push($data,$a);
			}
		}
		return $data;
	}
}
