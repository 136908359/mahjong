<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Test_Tool extends CI_Controller {

	function __construct() {
		parent::__construct();
        $this->load->helper('url');
        $this->load->helper('template');
        $this->load->model("gm_model");
	}
	
	public function index() {	
		$data = array();
		if (empty($this->input->post())) {
			output("admin/test_tool.phtml", $data);	
			return;
		}
		$servers = $this->input->post("servers");
		$name = $this->input->post("cmd_name");
		$msg = $this->input->post("cmd_data");
		$args = array(
			"ServerList" => explode(",",$servers),
			"Name" => $name,
			"Data" => $msg
		);
		$this->gm_model->request("/Route", $args);
		output("admin/test_tool.phtml",$data);
	}

	}
?>
