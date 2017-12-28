<?php
function err_string($err, $params=array()) {
	$data = array();
	if ( is_array($err) ) {
		$data = $err;
	} else {
		$code = 0;
		if ( $err != "SUCCESS" ) {
			$code = 1000;
		}
		$data = array("Code"=>$code,"Msg"=>$err);
	}
	$data = array_merge($data, (array)$params);
	return json_encode($data,JSON_UNESCAPED_UNICODE);
}

function err_code($code,$msg="SUCCESS") {
	return array("Code"=>$code,"Msg"=>$msg);
}
?>
