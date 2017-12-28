<?php
/*
	字符串补位
	
	输入参数
	$object             被补对象
	$maxlen             总长
	$recovery_str = '0' 补入对象
	$azimuth       = 0  方位，0->前，1->后

	输出参数
	return              补位后字符串
*/
	function recovery_string($object,$maxlen,$recovery_str='0',$azimuth=0)
	{
		$recovery = "";
		for($i = 0;$i < $maxlen;$i++)
		{
			$recovery .= "$recovery_str";
		}
		if($azimuth==0)
		{
			$recovery_object = $recovery.$object;
			$out_object = substr($recovery_object,-$maxlen);
		}
		else
		{
			$recovery_object = $object.$recovery;
			$out_object = substr($recovery_object,0,$maxlen);
		}
		return $out_object;
	}
	
	function format_money($number,$maxlen = 2)
	{
		$number_array = explode(".",$number);
		if(!isset($number_array[1]))
		{
			return $number_array[0].".".str_repeat("0",$maxlen);
		}
		else
		{
			$number_array[1] = rtrim($number_array[1],'0');
			$number_array[1] = recovery_string($number_array[1],$maxlen,'0',1);
			return $number_array[0].'.'.$number_array[1];
		}
	}
?>