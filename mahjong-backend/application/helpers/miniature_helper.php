<?php
/******************************
'类名：Miniature
'名称：图片缩略类
'描述：支持gif,jpeg,jpg,png
'使用一个类生成图片的缩略图,类的源码见下文 ：
'Miniature.Class.php
'******************************/

//class Miniature{
	/*
	0:不显示错误
	1:显示错误
	2:显示错误和错误原型
	3:显示错误，错误原型，停止程序继续执行
	*/
	//var $Debug;//错误显示级别，分别有0,1,2,3级别
	
	//var $Image;//图片对象
	//var $ImageType;//图片类型，1 = GIF，2 = JPG，3 = PNG
	//var $ImageWidth;//宽度
	//var $ImageHeight;//高度
	
	/*私有变量声明，类内部使用，不公开*/
	//var $fontFile;//字体文件
	function __construct()
	{
	   $this->Debug=3;
	   $this->ImageType=2;
	}
	
	//读取图片
	function ReadImage($img){
	  //取得图片信息
	  $imgInfo=@getimagesize($img);
	   if(!$imgInfo){
	   		$this->ShowError("读取图片错误","ReadImage(\"$img\")");
	   }
	  
	   $this->ImageType=$imgInfo[2];//取得图片类型
	   switch(strtolower($this->ImageType)){
	    case 1:
	     $this->Image=@imagecreatefromgif($img);
	       break;
	    case 2:
	     $this->Image=@imagecreatefromjpeg($img);
	       break;
	    case 3:
	     $this->Image=@imagecreatefrompng($img);
	       break;
	    default:
	     $this->Image=@imagecreatefromjpeg($img);
	   }
	   if(!$this->Image)
	   {$this->ShowError("读取图片错误","ReadImage(\"$img\")");}
	
	   $this->ImageWidth=imagesx($this->Image);//取得图片宽度
	   $this->ImageHeight=imagesy($this->Image);//取得图片高度
	}
	
	//剪切图片
	function CutImage($imgX,$imgY,$imgWidth,$imgHeight){
	   $newImg=$this->createImage($imgWidth,$imgHeight);
	   if(!$newImg){
	   		$this->ShowError("创建图片错误","CutImage($imgX,$imgY,$imgWidth,$imgHeight)");
	   }
	   @imagecopyresampled(
	    $newImg,$this->Image,
	    0,0,$imgX,$imgY,
	    $imgWidth,$imgHeight,$imgWidth,$imgHeight
	    ) or $this->ShowError("剪切图片错误","CutImage($imgX,$imgY,$imgWidth,$imgHeight)");
	
	   $this->Image=$newImg;
	
	   //重新取得图片宽度和高度
	   $this->ImageWidth=imagesx($this->Image);//取得图片宽度
	   $this->ImageHeight=imagesy($this->Image);//取得图片高度
	}
	
	//长和宽都按统一的百分比缩小
	//$nPer:百分比数
	function ResizedByPer($nWidthPer,$nHeightPer){
	   $w=$this->ImageWidth*($nWidthPer/$this->ImageWidth);//计算宽百份数
	   $h=$this->ImageHeight*($nHeightPer/$this->ImageHeight);//计算高百份数
	   $newImg=$this->createImage($w,$h);
	   if(!$newImg)
	   {$this->ShowError("创建图片错误","ResizedByPer($nPer)");}
	
	   @imagecopyresampled(
	    $newImg,$this->Image,
	    0,0,0,0,
	    $w,$h,$this->ImageWidth,$this->ImageHeight
	    ) or $this->ShowError("缩小图片错误","ResizedByPer($nPer)");
	
	   $this->Image=$newImg;
	   //重新取得图片宽度和高度
	   $this->ImageWidth=imagesx($this->Image);//取得图片宽度
	   $this->ImageHeight=imagesy($this->Image);//取得图片高度
	}
	
	//按固定的长和宽缩小(此方法可能会出现图片变形情况)
	function ResizedByWH($nWidth,$nHeight){
	   $newImg=$this->createImage($nWidth,$nHeight);
	   if(!$newImg){
	   		$this->ShowError("创建图片错误","ResizedByWH($nWidth,$nHeight)");
	   }
	
	   @imagecopyresampled(
	    $newImg,$this->Image,
	    0,0,0,0,
	    $nWidth,$nHeight,$this->ImageWidth,$this->ImageHeight
	    ) or $this->ShowError("缩小图片错误","ResizedByWH($nWidth,$nHeight)");
	
	   $this->Image=$newImg;
	   //重新取得图片宽度和高度
	   $this->ImageWidth=imagesx($this->Image);//取得图片宽度
	   $this->ImageHeight=imagesy($this->Image);//取得图片高度
	}
	
	//按长和宽进行智能缩小，而不会使图片缩小后出现变形情况
	function ResizeImage($nWidth,$nHeight){
	   //计算宽和高的比例
	   $p1=$nWidth/$nHeight;
	   $p2=$this->ImageWidth/$this->ImageHeight;
	
	   $w=0;$h=0;
	   if($p1 < $p2){
	    //按宽度来计算新图片的宽和高
	    $w=$nWidth;
	    $h=$nWidth*(1/$p2);
	   }else{
	    //按高度来计算新图片的宽和高
	    $h=$nHeight;
	    $w=$nHeight*$p2;
	   }
	
	   $nWidth=$w;
	   $nHeight=$h;
	   $newImg=$this->createImage($nWidth,$nHeight);
	   if(!$newImg){
	   		$this->ShowError("创建图片错误","ResizeImage($nWidth,$nHeight)");
	   }
	
	   @imagecopyresampled(
	    $newImg,$this->Image,
	    0,0,0,0,
	    $nWidth,$nHeight,$this->ImageWidth,$this->ImageHeight
	    ) or $this->ShowError("缩小图片错误","ResizeImage($nWidth,$nHeight)");
	
	   $this->Image=$newImg;
	   //重新取得图片宽度和高度
	   $this->ImageWidth=imagesx($this->Image);//取得图片宽度
	   $this->ImageHeight=imagesy($this->Image);//取得图片高度
	}
	
	//按一定的角度旋转图片
	function RotateImage($nAngle){
	   $w=$this->ImageWidth;
	   $h=$this->ImageHeight;
	
	  $newImg=@imagecreatetruecolor($w,$h);
	   if(!$newImg){
	   		$this->ShowError("创建图片错误","RotateImage($nAngle)");
	   }
	   //重新处理图片,否则在旋转gif,png时会有问题,偶也不知道原理-_-!
	   @imagecopyresampled(
	    $newImg,$this->Image,
	    0,0,0,0,
	    $w,$h,$w,$h
	    ) or $this->ShowError("旋转图片错误","RotateImage($nAngle)");
	   $this->Image=$newImg;
	
	   if($nAngle==90 ||$nAngle==270){
	   		$newImg=@imagecreatetruecolor($h,$w);
	   }else{
	   		$newImg=@imagecreatetruecolor($w,$h);
	   }
	   if(!$newImg){
	   		$this->ShowError("创建图片错误","RotateImage($nAngle)");
	   }
	
	   switch($nAngle){
	    case 90:
	     for($i=1;$i<=$w;$i++){//从1开始 
	        for($j=1;$j<=$h;$j++){//从1开始
	       		imagesetpixel($newImg,$h-$j-1,$i,imagecolorat($this->Image,$i,$j));    
	     	}
	     }
	     break;
	    case 180:
	     for($i=1;$i<=$w;$i++){//从1开始
	        for($j=1;$j<=$h;$j++){//从1开始
	       		imagesetpixel($newImg,$i,$h-$j-1,imagecolorat($this->Image,$i,$j));    
	      	}
	     }
	     break;
	    case 270:
	     for($i=1;$i<=$w;$i++){//从1开始
	        for($j=1;$j<=$h;$j++){//从1开始
		       imagesetpixel($newImg,$j,$w-$i-1,imagecolorat($this->Image,$i,$j));    
		    }
	     }
	     break;
	   }
	
	   $this->Image=$newImg;
	   //重新取得图片宽度和高度
	   $this->ImageWidth=imagesx($this->Image);//取得图片宽度
	   $this->ImageHeight=imagesy($this->Image);//取得图片高度
	}
	
	//按水平翻转
	function TurnL(){
	   $w=$this->ImageWidth;
	   $h=$this->ImageHeight;
	  $newImg=@imagecreatetruecolor($w,$h);
	   if(!$newImg){
	   		$this->ShowError("创建图片错误","TurnL()");
	   }
	
	   @imagecopyresampled(
	    $newImg,$this->Image,
	    0,0,$w-1,0,
	    $w,$h,-$w,$h
	    ) or $this->ShowError("水平翻转图片错误","TurnL()");
	
	   $this->Image=$newImg;
	   //重新取得图片宽度和高度
	   $this->ImageWidth=imagesx($this->Image);//取得图片宽度
	   $this->ImageHeight=imagesy($this->Image);//取得图片高度
	}
	
	//按垂直翻转
	function TurnV(){
	   $w=$this->ImageWidth;
	   $h=$this->ImageHeight;
	   $newImg=@imagecreatetruecolor($w,$h);
	   if(!$newImg){
	   		$this->ShowError("创建图片错误","TurnV()");
	   }
	
	   @imagecopyresampled(
	    $newImg,$this->Image,
	    0,0,0,$h-1,
	    $w,$h,$w,-$h
	    ) or $this->ShowError("垂直翻转图片错误","TurnV()");
	
	   $this->Image=$newImg;
	   //重新取得图片宽度和高度
	   $this->ImageWidth=imagesx($this->Image);//取得图片宽度
	   $this->ImageHeight=imagesy($this->Image);//取得图片高度
	}
	
	//添加边框
	//$x,$y:起始x,y点
	//$nWidth,$nHeight:长和宽
	//$nBorderWidth:边框大小,单位:px
	//$arrColor:边框颜色,数组
	function CreateBorder($x,$y,$nWidth,$nHeight,$nBorderWidth,$arrColor){
	   $borderColor=imagecolorallocate(
	    $this->Image,
	    $arrColor[0],
	    $arrColor[1],
	    $arrColor[2]
	    );//可以在此改变边框颜色
	   imagesetthickness($this->Image,$nBorderWidth);//设置边框宽度
	        imagerectangle($this->Image,$x,$y,$nWidth,$nHeight,$borderColor);
	}
	
	//设置透明图片
	//$num:其值从 0 到 127。0 表示完全不透明，127 表示完全透明。
	function SetAlpha($num){}
	
	//加载字体文件
	function LoadFont($_fontFile)
	{
	   $this->fontFile=$_fontFile;
	}
	
	//往图片添加字体
	//$text:字符
	//$fontSize:字体大小
	//$posX,$posY:字体在图象的(x,y)点
	//$arrFontColor:字体颜色，可以是数组array(r,g,b),也可以为十六进制颜色值如：#FF0000
	//$angle:字体角度，默认为0
	function AddText($text,$fontSize,$posX,$posY,$arrFontColor,$angle=0){
	     //将16进制转为数组array(r,g,b)
	   if(strlen($arrFontColor)==7)
	   {
	    $r=hexdec(substr($arrFontColor,1,2));
	            $g=hexdec(substr($arrFontColor,3,2));
	            $b=hexdec(substr($arrFontColor,5));
	    $arrFontColor=array($r,$g,$b);
	   }
	   $c=$arrFontColor;
	   $fontColor=imagecolorallocate($this->Image,$c[0],$c[1],$c[2]);
	   //进行编码转换,php4.x需要开启iconv库
	   if(function_exists("iconv"))$text=iconv('GB2312','UTF-8',$text);
	
	   if(!$this->fontFile){//如果不加载字体，则使用默认字体
	   		$f=@imagestring($this->Image,$fontSize,$posX,$posY,$text,$fontColor);
	   }
	   else{//如果加载字体则使用改字体显示
		   $f=@imagettftext(
		     $this->Image,
		     $fontSize,
		     $angle,
		     $posX,
		     $posY,
		     $fontColor,
		     $this->fontFile,
		     $text
		   );
	   }
	
	   if(!$f){
		    $this->ShowError(
		    "添加字体错误","AddText(\"$text\",$fontSize,$posX,$poxY,$arrFontColor,$angle)"
		    );
	   }
	}
	
	//创建图片,私有方法,类内部使用
	function createImage($nWidth,$nHeight){
	   if($this->ImageType==1 || $this->ImageType==3){//如果是gif图片
	   		$im=@imagecreate($nWidth,$nHeight);
	   }
	   else{//如果是其他图片
	   		$im=@imagecreatetruecolor($nWidth,$nHeight);
	   }
	   //使背景图象透明,这个很重要,不设置会使有透明背景的gif,png产生黑色图片
	   @imagecolorallocatealpha($im,255,255,255,127);
	   return $im;
	}
	
	//保存图片
	function SaveImage($imagePath,$imageName){
	   //文件全路径
	   $p=$imagePath.$imageName;
	   $filename = '';
	   switch($this->ImageType){
	    case 1:
	    $filename = $imageName.".gif";
	    $im=@imagegif($this->Image,$p.".gif");
	       break;
	    case 2:
	    $filename = $imageName.".jpg";
	    $im=@imagejpeg($this->Image,$p.".jpg",100);
	       break;
	    case 3:
	    $filename = $imageName.".png";
	    imagesavealpha ($this->Image, true);
	    $im=@imagepng($this->Image,$p.".png");
	       break;
	    case 4:
	    $filename = $imageName.".jpg";
	    $im=@imagejpeg($this->Image,$p.".jpg",100);
	       break;
	    default:
	    $filename = $imageName.".jpg";
	    $im=@imagejpeg($this->Image,$p.".jpg",100);
	   }
	
	   if(!$im){
	    	$this->ShowError("保存图片错误","SaveImage(\"$imagePath\",\"$imageName\")");
	   }
	   return $filename;
	}
	
	//直接输出(在浏览器端)图片
	function ShowImage(){
	   switch(strtolower($this->ImageType)){
		    case 1:
		    $im=@imagegif($this->Image);break;
		    case 2:
		    $im=@imagejpeg($this->Image,"",100);break;
		    case 3:
		     imagesavealpha ($this->Image, true);
		    $im=@imagepng($this->Image);break;
		    default:
		    $im=@imagejpeg($this->Image,"",100);break;
	   }
	   if(!$im){
	    	$this->ShowError("显示图片错误","ShowImage()");
	   }
	}
	
	//销毁图片
	function DestroyImage(){
	   @imagedestroy($this->Image);
	}
	
	//显示错误
	function ShowError($errorMsg,$oriMethod){
		$cssstyle="style=\"";
	    $cssstyle.="font:bold 12px 150%,'Arial';border:1px solid #CC3366;";
	    $cssstyle.="width:50%;color:#990066;padding:2px;\"";
	    $str="\n<ul ".$cssstyle.">\n";
	
	   if($this->Debug==0)return;
	   if($this->Debug>0){
	    $str.="<li>描述：".$errorMsg."</li>\n";
	   }
	   if($this->Debug>1){
	    $str.="<li>原型：".$oriMethod."</li>\n";
	   }
	   $str.="</ul>\n";
	   echo $str;
	   if($this->Debug==3)exit;
	}
	/*
	 * @补充的方法，上传图片文件的方法
	 * @param $file 是要上传的文件提交上来的数组对象
	 * @return 返回一个上传文件后要保存的路径字符串
	*/
	function upLoadFile($files,$path){
		//允许上传的文件类型
		$uptypes=array(
	    'image/jpg', 
	    'image/jpeg',
	    'image/png',
	    'image/pjpeg',
	    'image/gif',
	    'image/bmp',
	    'image/x-png'
		);
		
		if($_SERVER['REQUEST_METHOD'] == 'POST'){
	    //检查文件大小
		    if($files['size'] > 2097152){
		        return '上传文件图片不得大于2M';
		        exit;
		    }
		    //检查上传文件类型
		    $ftype = $files['type'];
		    if(!in_array($ftype,$uptypes)){
		        return '上传文件不/符合图片类型';
		        exit;
		    }
		    //取得上传图片的信息
		    $fname = $files['tmp_name'];
		    $image_info = getimagesize($fname);
		    //取得上传图片的扩展名
		    $name = $files['name'];
		    //pathinfo返回文件路径的信息 包括以下的数组单元：dirname，basename 和 extension。
		    $str_name = pathinfo($name);
		    $extname = strtolower($str_name['extension']);
		    //上传路径
		    $file_name = date("YmdHis").rand(1000,9999).".".$extname;
		    $str_file = $path.$file_name;
            
	    	//创建上传的目录
			if(!file_exists($path)) { 
			        mkdir($path); 
			}
            
		    if(!move_uploaded_file($files['tmp_name'],$str_file)){
		        return "上传文件失败";
		        exit;
		    }
		    return $file_name;
		}
	}
	/*
	 * @补充的方法，上传flash文件的方法
	 * @param $file 是要上传的文件提交上来的数组对象
	 * @return 返回一个上传文件后要保存的路径字符串
	*/
	function upLoadflash($flashs,$path){
		//允许上传的文件类型
		$uptypes=array(
	    'application/octet-stream',
	    'application/x-shockwave-flash',
	    'application/pdf',
        'application/swf'
		);
		
		if($_SERVER['REQUEST_METHOD'] == 'POST'){
	    //检查文件大小
		    if($flashs['size'] > 12097152){
		        echo '上传文件flash不得大于12M';
		        exit;
		    }
		    //检查上传文件类型
		    $ftype = $flashs['type'];
			//print_r($ftype);die;
		    if(!in_array($ftype,$uptypes)){
		        echo '上传文件不符合flash类型';
		        exit;
		    }
		    //取得上传图片的信息
		    $fname = $flashs['tmp_name'];
		    $image_info = getimagesize($fname);
		    //取得上传图片的扩展名
		    $name = $flashs['name'];
		    //pathinfo返回文件路径的信息 包括以下的数组单元：dirname，basename 和 extension。
		    $str_name = pathinfo($name);
		    $extname = strtolower($str_name['extension']);
		    //上传路径
		    $file_name = date("YmdHis").rand(1000,9999).".".$extname;
		    $str_file = $path.$file_name;

	    	//创建上传的目录
			if(!file_exists($path)) { 
			        mkdir($path); 
			}
		    if(!move_uploaded_file($flashs['tmp_name'],$str_file)){
		        echo "上传文件失败";
		        exit;
		    }
		    return $file_name;
		}
	}
//}


/****************************以上类的用法********************************/
//demo:
/*
header("Content-type: image/jpeg");
include("Miniature.php");

$m=new Miniature();
$m->ReadImage("To/3.jpeg","jpeg");//读取图片
$m->CutImage(100,20,200,100);//剪切图片
$m->ResizedByPer(50);//按百分比缩小
$m->ResizedByWH(200,250);//按宽和高缩小
$m->ResizeImage(200,500);//按宽和高智能缩小
$m->RotateImage(270);//按一定角度旋转
$m->TurnL();//水平翻转
$m->TurnV();//垂直翻转

$m->ShowImage();//直接输出图片
$m->SaveImage("To/","40");//保存图片

echo $m->ImageWidth;
echo $m->ImageHeight;

下面是具体应用的例子：

表单提交页面：upload.html

<html>
<head>
<title>waterupload</title>
</head>
<script language="javascript">
    function checkfile(){
        var ofile = document.getElementById('upfile').value;
        if(ofile == ""){
            window.alert("请选择上传文件");
            return false;
        }    
        return true;
    }    
</script>
<body>
<form action="demo_suo.php" enctype="multipart/form-data" method="post" name="upform" onSubmit="return checkfile();">
上传文件:
<input name="upfile" id="upfile" type="file">
<input type="submit" value="上传"><br>
<font color="red" size="2pt">允许上传的文件类型为:<?=implode(',',$uptypes);?></font>
</form>
</body>
</html>

程序代码：demo_suo.php
$uptypes=array(
    'image/jpg', 
    'image/jpeg',
    'image/png',
    'image/pjpeg',
    'image/gif',
    'image/bmp',
    'image/x-png'
);
if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $files = $_FILES['upfile'];
    //检查文件大小
    if($files['size'] > 2097152){
        echo '上传文件图片不得大于2M';
        exit;
    }
    //检查上传文件类型
    $ftype = $files['type'];
    if(!in_array($ftype,$uptypes)){
        echo '上传文件不符合图片类型';
        exit;
    }
    //取得上传图片的信息
    $fname = $files['tmp_name'];
    $image_info = getimagesize($fname);
    //取得上传图片的扩展名
    $name = $files['name'];
    $str_name = pathinfo($name);
    $extname = strtolower($str_name['extension']);
    //上传路径
    $upload_dir = "files/";
    //$str_dir = date("Ym")."/"; 
    //$str_path = $upload_dir.$str_dir; 
    $file_name = date("YmdHis").rand(1000,9999).".".$extname;
    $str_file = $upload_dir.$file_name;
    
    //创建上传的目录
if(!file_exists($upload_dir)) 
    { 
        mkdir($upload_dir); 
    }
       if(!move_uploaded_file($files['tmp_name'],$str_file)){
        echo "上传文件失败";
        exit;
    }
/*
     echo "<font color='red'>已经成功上传文件,文件名为:</font> <font color='blue'>".$str_file."<br>";
    echo "宽度:".$image_info[0];
    echo "长度:".$image_info[1];
    echo "<br> 大小:".$files['size']."bytes<br></font>";
echo "图片预览:<br>";
    echo "<img src=\"".$str_file."\">"; 
   
}*/

//给上传图片生成缩略图
// header("Content-type: image/jpeg");
/*
*jpeg/jpg 演示
*/

/*include("Miniature.Class.php");
$m=new Miniature();
$m->ReadImage($str_file);
$m->CutImage(100,20,200,100);//剪切图片
//$m->ResizedByPer(50,50);//按百份比缩小
//$m->ResizedByWH(200,250);//按宽和高固定缩小,会出现图片变形情况
//$m->ResizeImage(200,500);//智能缩小
//$m->RotateImage(90);//旋转
//$m->TurnL();//水平翻转
//$m->TurnV();//垂直翻转
//$m->SetAlpha(15);
$m->LoadFont("arial.ttf");
$w=$m->ImageWidth;
$h=$m->ImageHeight;
//echo $m->ImageHeight;
$m->CreateBorder(0,0,$m->ImageWidth-1,$m->ImageHeight-1,10,array(46,139,87));
//$m->AddText("www.newsunsoft.com",12,$w-150,$h-20,array(46,139,87));
//$m->AddText("By xyx ",9,$w-120,$h-8,array(-46,-139,-87));
$m->ShowImage();//直接显示图片
$m->ImageType=4;//保存成jpg格式
$suo_name=date("YmdHis")."_suo";
$m->SaveImage("files/",$suo_name);//保存图片,"To/":保存路径,"demo_1":图片名称
$m->DestroyImage();//销毁图片
*/
?>
