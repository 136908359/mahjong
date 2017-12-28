<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Activity extends CI_Controller {

	function __construct() {
		parent::__construct();
        $this->load->helper(array('url','form'));
        $this->load->helper('template');
        $this->load->model("admin/system_model");
        $this->load->model('gm_model');
        $this->load->library("session");
	}
	
	/*
	 * 添加活动类型
     */
	public function add_actype(){
		$data = array();
		if (empty($this->input->post())) {
			output("activity/add_actype.phtml",$data);
			return;
		}
		$postData = $this->input->post();
		if($postData['title'] == '' || empty($postData['title'])){
			exit("请输入活动id");
		}
		if($postData['title'] == '' || empty($postData['title'])){
			exit("请输入活动类型");
		}
		$postData['create_time'] = date('Y-m-d H:i:s');
		$result = $this->system_model->add_actype($postData);
		if(!$result){
			exit('操作失败');
		}
		redirect("/v1/activity/get_activity");
	}
	
	/* 活动列表 */
	public function get_activity(){		
		$data = array();
		$activity_list = $this->system_model->get_activity_list();
		$data['activity_list'] = $activity_list;
		output('activity/get_activity.phtml',$data);
	}
	
	public function add_activity(){
		$data = array();
		if (empty($this->input->post())) {
			$acTypes = $this->system_model->get_activity_type();
			$data['acTypes'] = $acTypes;
			output("activity/add_activity.phtml", $data);
			return;
		}
		/* 获取数据 */
		$title = $this->input->post('title');
		$start_time = $this->input->post('start_time');
		$end_time = $this->input->post('end_time');
		$style = intval($this->input->post('style'));
		//文件路径
		$image = $this->do_upload();
		$rmb = intval($this->input->post('rmb'));
		$ShopId = intval($this->input->post('ShopId'));
		$Rank = intval($this->input->post('Rank'));
		$params = json_encode(array('ShopId'=>$ShopId,'Rank'=>$Rank));
		//赠送物品
		$str = $this->input->post('items');
		$str1 = str_replace('*',';',$str);
		$arr = explode(';',$str1);
		$item = array();
		for($i=0; 2*$i+1<count($arr); $i++) {
			$id=intval($arr[2*$i]);
			$num=intval($arr[2*$i+1]);
			$item []= array("Id"=>$id, "Num"=>$num);
		}
		$items = json_encode($item);
		$activity=array(
				'title'=>$title,
				'start_time'=>$start_time,
				'end_time'=>$end_time,
				'style'=>$style,
				'image'=>$image,
				'rmb'=>$rmb,
				'items'=>$items,
				'params'=>$params
		);		
		$rs_id = $this->system_model->add_activity($activity);
		//通知游戏服
		if($rs_id){
			$send_arr=array(
					"Id" => intval($rs_id),
					"Title" => $title,
					"StartTime" => strtotime($start_time),
					"EndTime" => strtotime($end_time),
					"Style" => intval($style),
					"RMB" => intval($rmb),
					"Items" => $item,
					"Params" => $params,
					"Image" => $image
			);
			$this->gm_model->request('/UpdateActivity',$send_arr);
		}else{
			echo "操作失败！";
		}
		
		redirect("/v1/activity/get_activity");
	}
	
	public function edit_activity($id) {
		$data = array();
		if (empty($this->input->post())) {
			$activity = $this->system_model->get_activity_by_id($id);
			//商品ID			
			$params = json_decode($activity['params'],true);
			$ShopId = $params['ShopId'];
			//排序
			$Rank = isset($params['Rank'])?$params['Rank']:0;
			//赠送物品
			$items = json_decode($activity['items'],true);
			$str='';
			foreach($items as $v){
				$str.= $v['Id'].'*'.$v['Num'].';';
			}
			$str = rtrim($str,';');
			//活动形式
			$acTypes = $this->system_model->get_activity_type();
			
			$data['acTypes'] = $acTypes;						
			$data['str'] = $str;
			$data['ShopId'] = $ShopId;
			$data['Rank'] = $Rank;
			$data['activity'] = $activity;
			output("activity/edit_activity.phtml", $data);
			return;
		}
		
		//修改活动
		$data=$this->input->post();
		//商品id
		$ShopId = intval($data['ShopId']);
		$Rank = intval($data['Rank']);
		$params = json_encode(array('ShopId'=>$ShopId,'Rank'=>$Rank));
		//赠送物品
		$str = str_replace('*',';',$data['items']);
		$arr = explode(';',$str);
		$item = array();
		for($i=0; 2*$i+1<count($arr); $i++) {
			$pid=intval($arr[2*$i]);
			$num=intval($arr[2*$i+1]);
			$item []= array("Id"=>$pid, "Num"=>$num);
		}
		$items = json_encode($item);
		$activity=array(
				'title'=>$data['title'],
				'start_time'=>$data['start_time'],
				'end_time'=>$data['end_time'],
				'style'=>$data['style'],
				'rmb'=>$data['rmb'],
				'items'=>$items,
				'params'=>$params
		);
		//是否更改图片
		if($_FILES['userfile']['name'] != ''){
			$image = $this->do_upload();
			$activity['image'] = $image;
		}
		
		$rs = $this->system_model->edit_activity($activity, $id);
		//通知游戏服
		if($rs){
			$new_data = $this->system_model->get_activity_by_id($id);
			$send_arr=array(
					"Id" => intval($id),
					"Title" => $data['title'],
					"StartTime" => strtotime($data['start_time']),
					"EndTime" => strtotime($data['end_time']),
					"Style" => intval($data['style']),
					"RMB" => intval($data['rmb']),
					"Items" => $item,
					"Params" => $params,
					"Image" => $new_data['image']
			);
			$this->gm_model->request('/UpdateActivity',$send_arr);
		}else{
			echo '操作失败！';
		}
		
		redirect("/v1/activity/get_activity");
	}
	
	
	/* 文件上传类  */
	public function do_upload()
	{
		$upload_path = 'asset/uploads/';
		$config['upload_path']      = $upload_path;
		$config['allowed_types']    = 'gif|jpg|png';
		$config['max_size']    		= 512;
		// $config['max_width']        = 1024;
		// $config['max_height']       = 768;
		$config['file_name']		= time().rand(0,9).rand(0,9);
		
		$this->load->library('upload', $config);
		$this->upload->initialize($config);
	
		if ( ! $this->upload->do_upload('userfile'))
		{
			$error = $this->upload->display_errors();
			echo $error;
		}
		else
		{
			$data = $this->upload->data();
			$url = GM_HOST.'/'.$upload_path.$data['file_name'];
			return $url;
		}
	}
	
	
	
	
	
	
}

/* End of file welcome.php */
/* Location: ./system/application/controllers/welcome.php */
