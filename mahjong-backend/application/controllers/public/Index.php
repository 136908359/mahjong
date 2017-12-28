<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Index extends CI_Controller {
	function __construct()
	{
		parent::__construct();
		header("Content-Type: text/html; charset=UTF-8");
        $this->load->helper('url');
        $this->load->model('gm_model');
	}
	public function check_version() {
		$a = $this->get_lastest_version();

		echo json_encode($a);
	}
	private function get_lastest_version() {
		$raw_data =  file_get_contents('php://input');
		$data = json_decode($raw_data, TRUE);

		$chan_id = '';
		$version = '';
		if (isset($data['chan_id']) == false) {
			return array("type"=>"newest");
		}
		
		$chan_id = $data['chan_id'];
		if (isset($data['version']) == false) {
			return array("type"=>"newest");
		}

		$version = $data['version'];
		$v = explode(".",$version,3);
		if (count($v) != 3) {
			return array("type"=>"newest");
		}

		$lastest = $this->gm_model->get_lastest_version($chan_id,$version);

		// 默认最新版本
		$type = $lastest['update_type'];
		// 当前版本不低于最新版本
		if ($this->gm_model->compare_version($lastest['version'],$version) <= 0) {
			$type = 'newest';
		}
		unset($lastest['update_type']);
		/*
		$v1 = explode(".",$lastest['version'],3);
		if (intval($v1[0])>intval($v[0])) {
			$type = 'force';
		} else if (intval($v1[1])>intval($v[1])) {
			$type = 'force';
		} else if (intval($v1[2])>intval($v[2])) {
			$type = 'tip';
		}
		*/

		$lastest['type'] = $type;
		return $lastest;
	}

	public function upload_icon() {
		$ret = $this->do_upload_icon();
		$s = json_encode($ret);
		echo $s;
	}
	
	private function do_upload_icon() {
		$uid = 0;
		if (isset($_POST['uid'])) {
			$uid = intval($_POST['uid']);
		} else {
			return array("Code"=>1001,"Msg"=>"invaild uid");
		}
		if (isset($_POST['token'])) {
			$token = $_POST['token'];
		} else {
			return array("Code"=>1001,"Msg"=>"nvalid token");
		}
		$url = sprintf("/icons/113x113/%s_%010d.png",date('YmdHis'),$uid);
		$upload_file = BASEPATH."../".$url;
		// $upload_file = "/tmp/11.png";
		if (!move_uploaded_file($_FILES['icon']['tmp_name'],$upload_file)) {
			return array("Code"=>1,"Msg"=>"retry");
		}
		$data = array("UId"=>$uid,"Icon"=>$url);
		$this->gm_model->request("/UpdateIcon",$data);
		return array("Code"=>0,"URL"=>$url);
	}
}
