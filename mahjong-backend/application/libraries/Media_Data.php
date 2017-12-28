<?php
/*
	获取并格式化富媒体信息
	
	输入参数
	$media_dir      富媒体地址
	$type     = 0   处理富媒体尺寸，0->不处理，1->自动等比缩放（哪个比率高，以哪个为标准缩放），2->以宽为标准，3->只以宽为标准，4->以高为标准，5->只以高为标准
	$force    = 0   是否强制等比处理，0->不强制，1->强制
	$c_width  = 120 处理高度
	$c_height = 60  理宽度
	
	输出参数
	$data[error]    是否存在，0->存在，1->不存在
	$data[width]    宽
	$data[height]   高
*/
	function media_data($media_dir,$type=0,$force=0,$c_width=120,$c_height=60)
	{
		if(path_type($media_dir))
		{
			$media_dir = $media_dir;
			$isfile = is_exists_file($media_dir);
		}
		else
		{
			$media_dir = str_replace("\\","/",$media_dir);
			$isfile = is_exists_file($media_dir);
		}
		if($isfile)
		{
			$error=0;
			list($w, $h) = getimagesize($media_dir);
			$w_percent=$w/$c_width;
			$h_percent=$h/$c_height;
			if($type==0)
			{
				$width = $w;
				$height = $h;
			}
			elseif($type==1)
			{
				if($w_percent>=$h_percent && ($w_percent>1 || $force==1))
				{
					$width=$c_width;
					$height=$h/$w_percent;
				}
				elseif($h_percent>=$w_percent && ($h_percent>1 || $force==1))
				{
					$width=$w/$h_percent;
					$height=$c_height;
				}
				else
				{
					$width = $w;
					$height = $h;
				}			
			}
			elseif($type==2)
			{
				if($w_percent>1 || $force==1)
				{
					$width=$c_width;
					$height=$h/$w_percent;
				}
				elseif($h_percent>1 || $force==1)
				{
					$width=$w/$h_percent;
					$height=$c_height;
				}
				else
				{
					$width = $w;
					$height = $h;
				}			
			}
			elseif($type==3)
			{
				if($w_percent>1 || $force==1)
				{
					$width=$c_width;
					$height=$h/$w_percent;
				}
				else
				{
					$width = $w;
					$height = $h;
				}			
			}
			elseif($type==4)
			{
				if($h_percent>1 || $force==1)
				{
					$width=$w/$h_percent;
					$height=$c_height;
				}
				elseif($w_percent>1 || $force==1)
				{
					$width=$c_width;
					$height=$h/$w_percent;
				}
				else
				{
					$width = $w;
					$height = $h;
				}			
			}
			elseif($type==5)
			{
				if($h_percent>1 || $force==1)
				{
					$width=$w/$h_percent;
					$height=$c_height;
				}
				else
				{
					$width = $w;
					$height = $h;
				}			
			}
		}
		else
		{
			$error = 1;
		}
		$data['width']  = @$width;
		$data['height'] = @$height;
		$data['error']  = $error;
		return $data;
	}

/*
	格式化大小
	
	输入参数
	$size     = array(0 => 'width', 1 => 'height')
	$type     = 0   处理富媒体尺寸，0->不处理，1->自动等比缩放（哪个比率高，以哪个为标准缩放），2->以宽为标准，3->只以宽为标准，4->以高为标准，5->只以高为标准
	$force    = 0   是否强制等比处理，0->不强制，1->强制
	$c_width  = 120 处理高度
	$c_height = 60  理宽度
	
	输出参数
	$data[width]    宽
	$data[height]   高
*/	
	function format_size($size, $type = 0, $force = 0, $c_width = 120, $c_height = 60)
	{
		$w = $size[0];
		$h = $size[1];
		$w_percent=$w/$c_width;
		$h_percent=$h/$c_height;
		if($type==0)
		{
			$width = $w;
			$height = $h;
		}
		elseif($type==1)
		{
			if($w_percent>=$h_percent && ($w_percent>1 || $force==1))
			{
				$width=$c_width;
				$height=$h/$w_percent;
			}
			elseif($h_percent>=$w_percent && ($h_percent>1 || $force==1))
			{
				$width=$w/$h_percent;
				$height=$c_height;
			}
			else
			{
				$width = $w;
				$height = $h;
			}			
		}
		elseif($type==2)
		{
			if($w_percent>1 || $force==1)
			{
				$width=$c_width;
				$height=$h/$w_percent;
			}
			elseif($h_percent>1 || $force==1)
			{
				$width=$w/$h_percent;
				$height=$c_height;
			}
			else
			{
				$width = $w;
				$height = $h;
			}			
		}
		elseif($type==3)
		{
			if($w_percent>1 || $force==1)
			{
				$width=$c_width;
				$height=$h/$w_percent;
			}
			else
			{
				$width = $w;
				$height = $h;
			}			
		}
		elseif($type==4)
		{
			if($h_percent>1 || $force==1)
			{
				$width=$w/$h_percent;
				$height=$c_height;
			}
			elseif($w_percent>1 || $force==1)
			{
				$width=$c_width;
				$height=$h/$w_percent;
			}
			else
			{
				$width = $w;
				$height = $h;
			}			
		}
		elseif($type==5)
		{
			if($h_percent>1 || $force==1)
			{
				$width=$w/$h_percent;
				$height=$c_height;
			}
			else
			{
				$width = $w;
				$height = $h;
			}			
		}
		return array('width' => $width, 'height' => $height);
	}

/*
	获取路径信息
	
	输入参数
	$object  本地路径（LOCAL）或远程路径（URL）
	
	输出参数
	return        路径类型，0->本地路径，1->远程路径
*/	
	function path_type($object)
	{
		$path_data = @parse_url($object);
		if(isset($path_data['host']))
		{
			return 1;//url
		}
		else
		{
			return 0;//locals
		}
		return $data;
	}

/*
	远程文件是否存在
	
	输入参数
	$object  本地路径（LOCAL）或远程路径（URL）
	
	输出参数
	return        true || false
*/		
	function is_exists_file($object)
	{
		if(path_type($object))
		{
			$isfile_data = get_headers($object);
			if(substr_count($isfile_data[0],'OK')>0)
			{
				return true;
			}
			else
			{
				return false;
			}
		}
		else
		{
			return is_file($object);
		}
	}
/*
	格式化相对/绝对URL返回全URL
	
	输入参数
	$html_url           被格式化的URL
	$consult_url        参考URL（全URL）

	输出参数
	return              格式化后的全URL
*/
	function format_url($html_url,$consult_url)
	{
		$html_url_array = parse_url($html_url);
		$consult_url_array = parse_url($consult_url);
		if(@$html_url_array['scheme'])
		{
			return $html_url;
		}
		else
		{
			return 
			@str_replace("\\","/",$consult_url_array['scheme']."://".
			($consult_url_array['username'] && $consult_url_array['password']?$consult_url_array['username'].":".$consult_url_array['password']."":"").
			$consult_url_array['host'].
			($consult_url_array['port']?":".$consult_url_array['port']:"").
			dirname($consult_url_array['path'])."/".$html_url);
		}
	}
/*
	返回任意URL的GET值
	
	输入参数
	$url                被处理的URL
	$query = ''         为空取所有GET值，不为空取指值GET值

	输出参数
	return              GET值/数组，不存在为FALSE
*/	
	function return_query_value($url,$query="")
	{
		$url_array = parse_url($url);
		if($query=='')
		{
			parse_str(@$url_array['query'],$query_array);
			if(is_array($query_array) && count($query_array)>0)
			{
				return $query_array;
			}
			else
			{
				return false;
			}
		}
		else
		{
			parse_str($url_array['query']);
			@eval("\$return_value=$".$query.";");
			if($return_value)
			{
				return $return_value;
			}
			else
			{
				return false;
			}
		}
	}
/*
	把URL中所有的值处理成数组
	
	输入参数
	$url                           被处理对象
	$separator                     分隔符 为空是不分隔，不为空是分隔

	输出参数
	return                         处理后数组（一维）
*/		
	function return_url_value_allpath($url,$separator=""){
		require_once(dirname(__FILE__).'/Media_Data.php');
		$url_array = parse_url($url);
		$hosts = @explode(".",$url_array['host']);
		$pathinfo = @pathinfo($url_array['path']);
		$dirname = @explode("/",str_replace("\\","/",$pathinfo['dirname'])."/");
		
		$return['hosts']     = $hosts;
		$return['dirnames']  = $dirname;
		$return['filenames'] = $pathinfo['filename'];
		$return['querys']    = return_query_value($url);
		if($separator!=""){
			$return = array_map_recursive($return,"explode",array($separator,"callback"=>"callback"),"callback");
		}
		return $return;
	}
?>