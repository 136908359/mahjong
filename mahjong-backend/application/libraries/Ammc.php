<?php
class Ammc {
	const KEY = '0391591aafc5db68b08787645b837b4f';
	//const KEY_CURL_URL='95438b756ea1652c64a7f806583678c3';
        const KEY_CURL_URL='e32c36ea311bff152efc73508b6fd8f8';
	//const KEY_SYS_MSG='cf79b00806591e4f8bfd411ef334a948';

	
	/**
	 * rc4加密算法
	 * $pwd 密钥 0391591aafc5db68b08787645b837b4f
	 * $data 要加密的数据
	 * $pwd密钥　$data需加密字符串
	 */
	public 	function rc4($pwd, $data) {
		$key [] = "";
		$box [] = "";
		$cipher = '';
		$pwd_length = strlen ( $pwd );
		$data_length = strlen ( $data );
		for($i = 0; $i < 256; $i ++) {
			$key [$i] = ord ( $pwd [$i % $pwd_length] );
			$box [$i] = $i;
		}
	
		for($j = $i = 0; $i < 256; $i ++) {
			$j = ($j + $box [$i] + $key [$i]) % 256;
			$tmp = $box [$i];
			$box [$i] = $box [$j];
			$box [$j] = $tmp;
		}
	
		for($a = $j = $i = 0; $i < $data_length; $i ++) {
			$a = ($a + 1) % 256;
			$j = ($j + $box [$a]) % 256;
			$tmp = $box [$a];
			$box [$a] = $box [$j];
			$box [$j] = $tmp;
			$k = $box [(($box [$a] + $box [$j]) % 256)];
			$cipher .= chr ( ord ( $data [$i] ) ^ $k );
		}
		return $cipher;
	}
	public function respond($result, $data = array()) {
		$return_back = array ();
		$result_array = array ();
		if (is_array ( $result )) {
			$result_array = $result;
		} else {
			$result_array = array (
					'result' => $result 
			);
		}
		if (empty ( $data )) {
		       $result_str = json_encode ( $result_array );
	               $this->log ( 'log123', $result_str, FILE_APPEND );
			return $this->rc4 ( self::KEY_CURL_URL, $result_str );
		} else {
			$return_array = @array_merge ( $result_array, $data );
			$return_str = json_encode ( $return_array );
			$this->log ( 'log123', $return_str, FILE_APPEND );

			return $this->rc4 ( self::KEY_CURL_URL, $return_str );
		}
	}
	public function get_data($data) {
		$data = $this->rc4 ( self::KEY_CURL_URL, $data );
		$this->log ('log123', $data, FILE_APPEND );
		$result_array = json_decode ( $data, true );
                $this->log ('log123', $result_array, FILE_APPEND );
		return $result_array;
	}
	public function log($filename = 'log', $data, $flags=FILE_APPEND) {
		date_default_timezone_set ( 'Asia/Chongqing' );
                $current_time=date('Y-m-d');
		if (! is_dir ( 'logs/'.date('Ym') )) {
			mkdir ( 'logs/'.date('Ym'), 0755 );
		}
		return file_put_contents ( 'logs/'.date('Ym').'/'.$filename.$current_time,'['.date('H:i:s').']'.$data . "\n", $flags );
	}
	public function microtime_float(){
		list($usec, $sec) = explode(" ", microtime());
		return ((float)$usec + (float)$sec);
	}
	public function exec_url($url,$action,$data){
			$url=$url.'/'.$action;
			$key='';
		/*if($action=='sysmsg'){
			//$key=self::KEY;
			$key=self::KEY_SYS_MSG;
		}else{
			$key=self::KEY_CURL_URL;
		}*/
		if($action=='yee_pay'){
			$key=self::KEY;
		}else{
			$key=self::KEY_CURL_URL;
		}
		$post_data=$this->rc4($key, json_encode($data));
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,$url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch , CURLOPT_POST , 1);
		//设置超时时间
		curl_setopt($ch , CURLOPT_TIMEOUT , 7);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch , CURLOPT_POSTFIELDS , $post_data);
     	
		$result=curl_exec($ch);
     	
		if(curl_error($ch)){
     		$this->log('log',curl_error($ch));
     		return false;
     	}
		curl_close($ch);
     	$result=$this->rc4($key, $result);
     	$result=json_decode($result,true);
		if(!empty($result)){
			if($result['result']!=0){
				$this->log('log','失败[钱包返回结果]'.$result['result']);
				return false;
		}else{
			$this->log('log','成功返回结果'.json_encode($result));
			return $result;
		}
		}
	
	}
}
?>
