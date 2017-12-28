<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Welcome extends CI_Controller {

	function __construct() {
		parent::__construct();
        $this->load->helper('url');
	}
	
	function index() {	
		$data = array();
		$this->load->library("session");
		$this->load->model("admin/admin_model");
		$account = $this->session->userdata("account"); 
		if ($account == NULL) {
			redirect("/public/login");
		}
		$member = $this->admin_model->get_member($account);
		$menu_tree = $this->admin_model->get_menu_tree($account);
		$data['account'] = $account;
		$data['menu_tree'] = $menu_tree;
		$this->load->view('admin/welcome.phtml',$data);
	}

	public function main() {
		echo GM_HOST;
	}
}

/* End of file welcome.php */
/* Location: ./system/application/controllers/welcome.php */
