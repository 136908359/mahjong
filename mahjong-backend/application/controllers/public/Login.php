<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Login extends CI_Controller {
	function __construct() {
		parent::__construct();
        $this->load->helper('url');        
		$this->load->library("session");
	}
	
	function index() {
		$account = $this->session->userdata("account");
		if (empty($account) == false) {
			redirect('/public/welcome');
		}

		$data = array();
		if (empty($this->input->post())) {
			$data['err_msg'] = '';
		} else {
			$account = $this->input->post("account");
			$passwd = $this->input->post("passwd");
			$post_code = $this->input->post("check_code");
			$post_code = trim($post_code);
			$post_code = strtoupper($post_code);
			$check_code = $this->session->userdata("login_check_code");			
			if ($check_code == NULL || $check_code != $post_code) {
				$data['err_msg'] = '验证码有误';
			} else {
				$this->load->model('admin/admin_model');
				$member = $this->admin_model->get_member($account);
				$origin_passwd = isset($member['passwd'])?$member['passwd']:'';
				if ($origin_passwd == md5($passwd)) {
					$this->session->set_userdata("account", $account);
					redirect("/public/welcome");
				} else {
					$data['err_msg'] = '用户名或密码错误';
				}
			}	
		}
		$this->load->view('admin/login.phtml',$data);
	}
	public function logout(){
		$this->session->unset_userdata("account");
   		redirect('/public/login');
	}
	
	 /*
	  * 生成验证码
	  */	
	function check_code() {
        $this->load->helper('check_code');
        draw_check_code(); // 生成验证码，保存到session.check_code中
		$code = $this->session->userdata("check_code");
		$this->session->set_userdata("login_check_code", $code);
	}
}

/* End of file welcome.php */
/* Location: ./system/application/controllers/welcome.php */
