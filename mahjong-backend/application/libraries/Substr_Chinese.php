<?php
/*
	中英文截字处理

	输入参数
	$string                       要截的字符串
	$start              = 0       要截字符串的开始位置
	$length             = ''      要截取的长度
	$string_in_charset  = 'UTF-8' 要截字符串的编码
	$string_out_charset = 'UTF-8' 截后字符串编码
	
	输出参数
	return                        截后数据
*/
	function substr_cn($string,$start=0,$length='',$string_in_charset='UTF-8',$string_out_charset='UTF-8')
	{
		$string  = iconv($string_in_charset,"GBK",$string);
		$strleng = strlen($string);
		//字符串长度
		if($length == '')
		{
			$length = ($start>=0?$strleng-$start:abs($start));
			//当$length为空时，算出要截的长度
		}
		elseif($length < 0)
		{
			$length = ($start>=0?$strleng-abs($length)-abs($start):abs($start)-abs($length));
			//当$length为负时，算出要截的长度
		}
		if($length <= 0)
		{
			//当要截长度为0或负时，返回全部字符串
			return iconv("GBK",$string_out_charset,'');
		}
		elseif($length >= ($start>=0?$strleng-$start:abs($start)))
		{
			//当可截长度<=要截长度时,返回可截全部字符串
			$nocnnum = 0;
			//初始非中文字符数为0
			for($i=0; $i<($start>=0?$start:$strleng-abs($start)); $i++)
			{
				if(ord(substr($string,$i,1)) <= 128)
				{
					$nocnnum++;
				}
			}
			//字符串中非可截字符串有$nocnnum个非中文字符
			if((($start>=0?$start:$strleng-abs($start))%2==1 && $nocnnum%2==0) || (($start>=0?$start:$strleng-abs($start))%2==0 && $nocnnum%2==1))
			{
				$start--;
			}
			return iconv("GBK",$string_out_charset,substr($string,$start));
		}
		else
		{
			//当可截长度>要截长度时
			$nocnnum = 0;
			//初始非中文字符数为0
			for($i=0; $i<($start>=0?$start:$strleng-abs($start)); $i++)
			{
				if(ord(substr($string,$i,1)) <= 128)
				{
					$nocnnum++;
				}
			}
			//字符串中非可截字符串有$nocnnum个非中文字符
			if((($start>=0?$start:$strleng-abs($start))%2==1 && $nocnnum%2==0) || (($start>=0?$start:$strleng-abs($start))%2==0 && $nocnnum%2==1))
			{
				$start--;
			}
			$nocnnum = 0;
			//初始非中文字符数为0
			for($j=($start>=0?$start:$strleng-abs($start)); $j<($start>=0?$start:$strleng-abs($start))+$length; $j++)
			{
				if(ord(substr($string,$j,1)) <= 128)
				{
					$nocnnum++;
				}
			}
			//字符串中截后字符串有$nocnnum个非中文字符
			if(($length%2==1 && $nocnnum%2==0) || ($length%2==0 && $nocnnum%2==1))
			{
				//当取到值中最后一个字符为半个中文时，取值长度+1，使中文完整显示。（当取值长度为奇时）
				$length++;
			}
			return iconv("GBK",$string_out_charset,substr($string,$start,$length));
		}
	}

/*
	$string = '一1二2三3四4五5';
	echo substr_cn($string, 2);      // 1二2三3四4五5
	echo substr_cn($string, 0, 2);   // 一
	echo substr_cn($string, 2, 2);   // 1二
	echo substr_cn($string, -2);     // 五5
	echo substr_cn($string, -4, 2);  // 4五
	echo substr_cn($string, -6, -2); // 四4五
*/

	function substr_cn2title($string,$start=0,$length='',$string_in_charset='UTF-8',$string_out_charset='UTF-8')
	{
		$return_string = substr_cn($string,$start,$length,$string_in_charset,$string_out_charset);
		if($return_string != $string){
			return $return_string."...";
		}
		else{
			return $return_string;
		}
	}
?>