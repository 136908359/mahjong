<?php

function getsecode(){
	if(function_exists('imagecreate') && function_exists('imagecolorallocate') && function_exists('imagefill') && function_exists('imagesetpixel') && function_exists('imagestring') && function_exists('imagepng') && function_exists('imagedestroy'))
	{
		header("Content-type: image/gif");
		/*$array=array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z','1','2','3','4','5','6','7','8','9','0');*/
		$array=array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','P','Q','R','S','T','U','V','W','X','Y','Z','a','b','c','d','e','f','g','h','i','j','k','m','n','p','q','r','s','t','u','v','w','x','y','2','3','4','5','6','7','8','9');
		//$array=array('1','2','3','4','5','6','7','8','9','0');
		for($i=0;$i<4;$i++)//产生随机英文串的位长
		{
			$rand=$array[rand(0,count($array)-1)];//判断第一个数不能为0
			$string.=$rand;
		}
		$word = $string;
		$words = str_split($word);
		$_SESSION['verificationcode'] = $word;
		
		$width = 80;
		$height = 28;
		$img = imagecreate($width,$height);//设定图片大小
		$backcolor = imagecolorallocate($img,80,150,150);//给图像分配一种填充色，背景色
		$frontcolor = imagecolorallocate($img,255,255,255);//前景色
		imagefill($img,0,0,$backcolor);//填充色
		for($i=0;$i<5;$i++)//加入干扰象素
		{
			imagesetpixel($img,rand(0,$width),rand(0,$height),$frontcolor);//在图像中输出点(图，坐标X，坐标Y，色)
		}
		imagestring($img,5,15,5,$words[0],$frontcolor);
		imagestring($img,5,30,5,$words[1],$frontcolor);
		imagestring($img,5,45,5,$words[2],$frontcolor);
		imagestring($img,5,60,5,$words[3],$frontcolor);//在图像中输出水平字(图，字号，坐标X，坐标Y，字符串，色)
		imagegif($img);//输出gif格式
		imagedestroy($img);//释放图像
	}
	else
	{
		$array=array('1','2','3','4','5','6','7','8','9','0');
		for($i=0;$i<4;$i++)//产生随机英文串的位长
		{
			$rand=$array[rand(0,count($array)-1)];//判断第一个数不能为0
			$string.=$rand;
		}
		$word = $string;
		$words = str_split($word);
		$_SESSION['verificationcode'] = $word;
		header('Content-Type: image/gif');
		$numbers = array
		(
			0 => array('3c','66','66','66','66','66','66','66','66','3c'),
			1 => array('1c','0c','0c','0c','0c','0c','0c','0c','1c','0c'),
			2 => array('7e','60','60','30','18','0c','06','06','66','3c'),
			3 => array('3c','66','06','06','06','1c','06','06','66','3c'),
			4 => array('1e','0c','7e','4c','2c','2c','1c','1c','0c','0c'),
			5 => array('3c','66','06','06','06','7c','60','60','60','7e'),
			6 => array('3c','66','66','66','66','7c','60','60','30','1c'),
			7 => array('30','30','18','18','0c','0c','06','06','66','7e'),
			8 => array('3c','66','66','66','66','3c','66','66','66','3c'),
			9 => array('38','0c','06','06','3e','66','66','66','66','3c')
		);
	
		$data = array();
		for($i=0;$i<20;$i++)
		{
			for($j=0;$j<4;$j++)
			{
				$n=substr($_SESSION['secode'],$j,1);
				$string1 = $numbers[$n][$i];
				array_push($data, $string1);
			}
		}
		for($k=0;$k<40;$k++)
		{
			$string2 = '0';
			array_unshift($data,$string2);
			array_push($data,$string2);
		}
		$image = pack('H*','424D9E000000000000003E000000280000002000000018000000010001000000'.	'0000600000000000000000000000000000000000000000000000FFFFFF00'.implode('',$data));
		echo $image;
	}
}


?>
