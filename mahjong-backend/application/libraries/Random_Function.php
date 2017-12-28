<?php
/*
	生成多位指定字符串

	输入参数
	$length = 1 位数长度
	
	输出参数
	return      生成后字符串
*/
	function random_string($array,$length=1)
	{
		$string = "";
		for($i=0;$i<$length;$i++)//产生随机英文串的位长
		{
			$rand=$array[rand(0,count($array)-1)];//判断第一个数不能为0
			$string.=$rand;
		}
		return $string;
	}
	
/*
	生成多位英文

	输入参数
	$length = 1 位数长度
	
	输出参数
	return      生成后字符串
*/
	function random_en($length=1)
	{
		$array=array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z');
		return random_string($array,$length);
	}
	
/*
	生成多位数字

	输入参数
	$length = 1 位数长度
	
	输出参数
	return      生成后字符串
*/
	function random_number($length=1)
	{
		$number = "";
		for($i=0;$i<$length;$i++)//产生随机数的位长
		{
			$rand=rand($i==0 && $length!=1?1:0,9);//判断第一个数不能为0
			$number.=$rand;
		}
		return $number;
	}
	
/*
	生成多位中文

	输入参数
	$length = 1 位数长度
	
	输出参数
	return      生成后字符串
*/
	function random_cn($length=1)
	{
		$string = "";
		for($i=0;$i<$length;$i++)//产生随机中文的位长
		{
			$string .= chr(rand(0xB0,0xF7)).chr(rand(0xA1,0xFE));
		}
		return $string;
	}
?>