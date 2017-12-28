<?php

require_once APPPATH.'third_party/uuid.php';
class Test extends CI_Controller{
    public function __construct(){
        parent::__construct();

		if ( ENVIRONMENT != "development" ) {
			exit("Not Found 404");
		}

		$this->load->model("gm_model");
    }
	public function items() {
		$uid = $this->input->get("uid");
		$s = $this->input->get("items");
		$items = array();
		$str = str_replace("*",";",$s);
		$all = explode(";",$str);
		for($i=0; 2*$i+1<count($all); $i++) {
			$id=intval($all[2*$i]);
			$num=intval($all[2*$i+1]);
			$items []= array("Id"=>$id, "Num"=>$num);
		}
		$this->gm_model->request('/AddItems',array("UId"=>intval($uid),"GUID"=>create_uuid(),"Way"=>"test","Items"=>$items));
	}
	public function config() {
		$this->load->model("config_model");		
		echo $this->config_model->get("config","MarketingAgentRate","Value");
	}
}
