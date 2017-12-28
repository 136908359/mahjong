<?php 
if ( ! defined('BASEPATH')) exit('No direct script access allowed');
require_once APPPATH.'third_party/uuid.php';

class Privacy extends CI_Controller {

	function __construct()
	{
		parent::__construct();
		header("Content-Type: text/html; charset=UTF-8");
        $this->load->helper('url');
	}
	public function index() {
		$this->load->view('public/privacy.phtml',$data);
	}	
}
