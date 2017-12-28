<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Operation extends CI_Controller {

	function __construct() {
		parent::__construct();
        $this->load->helper('url');
        $this->load->helper('template');
        $this->load->model("admin/operation_model");
	}
	
	/* 
	 * 渠道管理 
	 */	
	//获取渠道列表
	public function get_chans() {	
		$data = array();	
		$chan_list = $this->operation_model->get_chans();
		$data['chan_list'] = $chan_list;
		output('operation/get_chans.phtml',$data);
	}
	
	//添加渠道
	public function add_chans() {	
		$data = array();
		if (empty($this->input->post())) {
			$chan_list = $this->operation_model->get_chans();
			$data['chan_list'] = $chan_list;
			output("operation/add_chans.phtml", $data);	
			return;
		}
		$post_data = $this->input->post();
		$result = $this->operation_model->add_chans($post_data);
		if (!$result) {
			exit('操作失败！');
		}
		redirect("/v1/operation/get_chans");
	}
	
	//修改渠道
	public function edit_chans($id) {
		$data = array();
		if (empty($this->input->post())) {
			$chanMsg = $this->operation_model->get_chan($id);//当前渠道数据
			
			$data['chanMsg'] = $chanMsg;
			output("operation/edit_chans.phtml", $data);
			return;
		}
		//获取提交的数据
		$post_data = $this->input->post();
		$result = $this->operation_model->edit_chans($post_data,$id);
		if (!$result) {
			exit('操作失败！');
		}
		redirect("/v1/operation/get_chans");
	}
	
	//获取当前用户所有子渠道
	public function chanChilds($chan_id = -1, $str=''){
		$chanMsg = $this->operation_model->child_chans($chan_id);
		if($chanMsg){
			$str1='';
			foreach ($chanMsg as $v){
				$str1 = $v['chan'].'&';
				$str .= $this->chanChilds($v['id'],$str1);
			}
		}
		
		return $str;
	} 
	
	
	
	
	
	
	
	

	
	
	
	
	
	
	
	
	
	

}

/* End of file welcome.php */
/* Location: ./system/application/controllers/welcome.php */
