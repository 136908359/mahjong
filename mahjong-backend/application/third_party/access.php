<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once 'uuid.php';

function signature($params, $key) {
	ksort($params);
	$s = http_build_query($params);
	$s = $s."&".$key;
	$sign = md5($s);
	return $sign;
}

function access() {
	$ci = &get_instance();
	$data = $ci->input->get();
	if ( !$data ) {
		$data = $ci->input->post();
	}
	if ( !$data || empty($data['sign'])) {
		return "参数有误";
	}
	$key = PAY_SIGN;
	$sign = $data['sign'];
	unset($data['sign']);
	$sign1 = signature($data, $key);
	if ( strcmp($sign,$sign1) ) {
		return "签名无效";
	}
	return "SUCCESS";
}

class Access_Controller  extends CI_Controller {
	function __construct($key=null) {
		parent::__construct();

		$data = $this->input->get();
		if (empty($data) == true) {
			$data = $this->input->post();
		}
		if (empty($data) == true || empty($data['sign'])) {
			exit("参数有误");
		}
		if (empty($key) == true) {
			$key = PAY_SIGN;
		}
	
		$sign = $data['sign'];
		unset($data['sign']);
		$sign1 = $this->signature($data, $key);
		if (strcmp($sign,$sign1)) {
			exit("签名无效");
		}
	}

	protected function signature($params, $key) {
		return signature($params,$key);
	}
}
