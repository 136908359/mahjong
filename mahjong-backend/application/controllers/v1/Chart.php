<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Chart extends CI_Controller {
	function __construct() {
		parent::__construct();
        $this->load->helper('url');
        $this->load->helper('template');

        $this->load->model("admin/data_model");
        $this->load->library('session');
	}
	
	/* 
	 * 在线
	 */
	public function get_online_by_day() {
		$expect_day = $this->input->get('day') ;
		$expect_game = $this->input->get('game') ;
		if ( !$expect_day ) {
			$expect_day = date('Y-m-d');
		}
		if ( !$expect_game ) {
			$expect_game = '全部';
		}
		// 将数据按照10分钟对齐
		$games = array();
		// 当前游戏各场次在线人数
		$game_online = $this->data_model->get_online_by_day($expect_day);
		foreach ( $game_online as $point ) {
			$game_name = $point['game_name'];
			$room_name = $point['room_name'];
			if ( !$game_name ) { // 无效的空数据
				continue;
			}
			if ( empty($games[$game_name][$room_name]) ) {
				$games[$game_name][$room_name] = array_fill(0, 6*24, 0);
			}
			$room = $games[$game_name][$room_name];

			$ts = strtotime( $point['deadline'] );
			$hour = intval( date('H', $ts ) );
			$min = intval( date('i', $ts ) );
			$room[$hour*6+floor( $min/10 )] = intval($point['num']);
			$games[$game_name][$room_name] = $room;
		}

		$game_list = array();
		foreach ( $games as $game_name => $rooms ) {
				$game_list []= $game_name;
		}

		$samples = array();
		$legends = array();
		if ( $expect_game == "全部" ) {
			foreach ( $games as $game_name => $rooms ) {
				$legends []= $game_name;
				$samples[$game_name] = array_fill(0,6*24,0);
				foreach ( $rooms as $room_name=>$room ) {
					foreach ( $room as $k=>$n ) {
						$samples[$game_name][$k] += $n;
					}
				}
			}
		} else {
			foreach ( $games as $game_name => $rooms ) {
				if ( $expect_game == $game_name ) {
					foreach ( $rooms as $room_name=>$room ) {
						$legends []= $room_name;
						$samples[$room_name] = $room;
					}
				}
			}
		}
		
		$data = array();
		$data['expect_day'] = $expect_day;
		$data['expect_game'] = $expect_game;
		$data['game_list'] = $game_list;
		$data['legends'] = json_encode($legends);
		$data['samples'] = json_encode($samples);
		output('chart/get_online_by_day.phtml', $data);
	}
}
