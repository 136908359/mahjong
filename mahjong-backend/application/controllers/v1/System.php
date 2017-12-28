<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once APPPATH.'third_party/uuid.php';
class System extends CI_Controller {
	function __construct() {
		parent::__construct();
        $this->load->helper('url');
        $this->load->helper('template');
        $this->load->model("admin/system_model");
        $this->load->model('gm_model');
		$this->load->model("share_model");
        $this->load->library('session');
        $this->load->library('pagination');
	}
	/* 邮件系统 */
	public function mail( ){		
		$data = array(
			"items" => $this->gm_model->get_items(),
		);
		if ( ! $this->input->post() ) {
			$uid = $this->input->get('uid');
			if ( $uid ) {
				$data['uid'] = $uid;
			}
			output("system/mail.phtml", $data);
			return;
		}						

		$title = $this->input->post("title");
		$award = $this->input->post("award");

		$items = array();
		$str = str_replace("*",";",$award);
		$all = explode(";",$str);
		for($i=0; 2*$i+1<count($all); $i++) {
			$id=intval($all[2*$i]);
			$num=intval($all[2*$i+1]);
			$items []= array("Id"=>$id, "Num"=>$num);
		}
		if ( !$title ) {
			$title = '系统邮件';
		}
		//邮件内容
		$content = $this->input->post("content");
		if( !$content ){
			$content='邮件内容空空如何';			
		}

		$mass_mail = $this->input->post("mass_mail");
		if ( $mass_mail ) {
			/* 群发邮件 */
			$mail = array("Title"=>$title,"Content"=>$content,"Items"=>$items);
			$this->gm_model->request('/MassMail',$mail);
		} else {
			/* 分组邮件 */
			$uid_str = $this->input->post("uid_str");
			$uid_list = explode(";", $uid_str);
			
			$recv_list = array();
			foreach ( $uid_list as $uid ) {
				$uid = intval($uid);
				if ( $uid ) {
					$recv_list []= $uid;
				}
			}
			if ( count($recv_list) > 0 )  {
				$mail = array("RecvIds"=>$recv_list,"Title"=>$title,"Content"=>$content,"Items"=>$items);	
				$this->gm_model->request('/SendGroupMail',$mail);
			}
		}
		output("system/mail.phtml", $data);
	}
	
	
	/* 游戏广播 */
	public function get_broadcast(){
		$data = array();
		$this->load->library("session");
		$broadcast_list = $this->system_model->get_broadcast_list();
		$data['broadcast_list'] = $broadcast_list;
		output('system/get_broadcast.phtml',$data);
	}
	
	public function add_broadcast(){
		$data = array();
		if (empty($this->input->post())) {
			output("system/add_broadcast.phtml", $data);
			return;
		}
		
		$data = $this->input->post();		
		$data['interval'] = intval($data['interval']);
		$rs_id = $this->system_model->add_broadcast($data);
		//通知游戏服
		if($rs_id){
			$arr=array(
					"Id" => intval($rs_id),
					"Content" => $data['content'],
					"StartTime" => strtotime($data['start_time']),
					"EndTime" => strtotime($data['end_time']),
					"Interval" => $data['interval']
			);
			$this->gm_model->request('/Broadcast',$arr);
		}
		
		redirect("v1/system/get_broadcast");		
	}
	
	//修改广播
	public function edit_broadcast($id){
		$data = array();
		if (empty($this->input->post())) {
			$data['broadcast'] = $this->system_model->get_broadcast_by_id($id);
			output("system/edit_broadcast.phtml", $data);
			return;
		}
		
		//更新数据库
		$data = $this->input->post();
		$data['interval'] = intval($data['interval']);
		$rs = $this->system_model->edit_broadcast($data,$id);
		//通知游戏服
		if($rs){
			$arr=array(
					"Id" => intval($id),
					"Content" => $data['content'],
					"StartTime" => strtotime($data['start_time']),
					"EndTime" => strtotime($data['end_time']),
					"Interval" => $data['interval']
			);
			$this->gm_model->request('/Broadcast',$arr);
		}
				
		redirect("v1/system/get_broadcast");
	}
	
	// TODO 2017-02-23 废弃
	public function generate_share_code() {
		$data = array();
		if (empty($this->input->post())) {
			output("system/generate_share_code.phtml", $data);	
			return;
		}
		set_time_limit(0);
		$num = $this->input->post('num');
		$this->load->model("system_model");
		$max = 10000;
		for ($k=0; $k*$max<=$num+$max; $k++) {
			$codes = array();
			for($i=0; $i+$k*$max<$num && $i<$max; $i++) {
				$code = '';
				for($j=0; $j<6; $j++) {
					$n = rand(ord('A'),ord('Z'));
					$code = $code.chr($n);
				}
				array_push($codes, $code);
			}
			$this->system_model->add_share_code($codes);
		}
		output("system/generate_share_code.phtml", $data);	
	}

	// 配置
	public function config() {
		$data = array();
		if (empty($this->input->post())) {
			$data['config'] = $this->gm_model->get_config("gm");
			output("system/config.phtml", $data);	
			return;
		}
		$config = $this->input->post("config");
		$this->gm_model->save_config("gm", $config);
		$data['config'] = $config;
		// JSON数据解析有误
		if (json_decode($config) == null) {
			echo '<script>alert("JSON数据解析有误");</script>';
		} else {
			echo '<script>alert("已生效");</script>';
			$args = array(
				"ServerList" => array("hall"),
				"Name" => "FUNC_UpdateConfig",
				"Data" => $config,
			);
			$this->gm_model->request("/Route", $args);
		}
		output("system/config.phtml", $data);	
	}

	// TODO 分页
	public function get_draw_cash($id=null,$page_num=GM_PAGE_SIZE) {
		$data = array();

		$data['list'] = $this->share_model->get_draw_cash($id,$page_num);
		output("system/get_draw_cash.phtml", $data);	
	}

	// 审核推广员提现
	public function check_draw_cash() {
		$args = $this->input->post();
		/*
		if ( !$args ) {
			exit("404 Not Found");
		}
		*/

		$id = $args["id"];
		$msg = $this->share_model->check_draw_cash($id, true);
		echo $msg;
	}

	
	// 反馈
	public function update_advise() {
		$id = $this->input->get("id");
		$result = $this->input->get("result");
		$this->load->model('system_model');
		$this->system_model->update_advise($id,$result);
		echo 'SUCCESS';
	}

	// 反馈
	public function get_last_advise() {
		$page = $this->input->get("per_page");
		$page = intval($page);

		$this->load->model('system_model');
		$total = $this->system_model->get_total_advise();
		$advises = $this->system_model->get_last_advise($page,16);
        $url = "/v1/system/get_last_advise?";

		$page = floor(intval($page)/16)+1;
		$config['base_url'] = $url;
        $config['total_rows'] = $total;
        $config['cur_page'] = $page;
        $config['per_page'] = 16;
        $config['first_link'] = '首页';
        $config['prev_link'] = '上一页';
        $config['next_link'] = '下一页';
        $config['last_link'] = '尾页';
        $config['page_query_string'] = true;

        $this->pagination->initialize($config);
		$pages = $this->pagination->create_links();
		
		$data = array(
			'pages'         => $pages,
			"advises"       =>$advises,
		);
		output("system/advise.phtml",$data);
	}

	public function add_chan($id=null){
		$post = $this->input->post();

		if ( $post ) {
			$this->admin_model->add_chan($post);
			redirect('/v1/system/chan_list');
		}
		$data = array();
		if ( $id ) {
			$data = $this->admin_model->get_chan_by_id($id);	
		}
					
		output("system/add_chan.phtml",$data);		
	}

	/* 游戏广播 */
	public function chan_list(){
		$page = $this->input->get("per_page");
		$page = intval($page);

		$params = array();
		$data['rows'] = $this->admin_model->get_chan_list();
		output('system/chan_list.phtml',$data);
	}

	public function set_config(){
		$post = $this->input->post();
		if ( !$post ) {
			$js = $this->system_model->get_config("gm");
			$config = json_decode($js,true);
			output('system/set_config.phtml',array('config'=>$config));
			return;
		}

		$config = array();
		$config["ErbagangRate"] = $post["erbagang_rate"]*MAX_RAND_RANGE;
		$config["BairenniuniuRate"] = $post["bairenniuniu_rate"]*MAX_RAND_RANGE;
		$config["BairenzhajinhuaRate"] = $post["bairenzhajinhua_rate"]*MAX_RAND_RANGE;
		$config["ErbagangDealerLimit"] = $post["erbagang_dealer_limit"]*1;
		$config["BairenniuniuDealerLimit"] = $post["bairenniuniu_dealer_limit"]*1;
		$config["BairenzhajinhuaDealerLimit"] = $post["bairenzhajinhua_dealer_limit"]*1;
		$this->system_model->update_config("gm", json_encode($config));

		$route_msg = array(
			"ServerList" => array("ebg","brnn","brzjh"),
			"Name" => "FUNC_UpdateConfig",
		);
		$this->gm_model->request("/Route",$route_msg);

		output('system/set_config.phtml',array('config'=>$config));
	}
	// 客户端版本管理
	public function client_version_list(){
		$data = $this->system_model->get_client_version_list();
		output('system/client_version_list.phtml',array('rows'=>$data));
	}
	public function add_client_version($id=null){
		$client_version = $this->input->post();
		if ( $client_version ) {
			$data = $this->system_model->add_client_version($id,$client_version);
			redirect('/v1/system/client_version_list');
		}
		$client_version = $this->system_model->get_client_version($id);
		output('system/add_client_version.phtml',$client_version);
	}
	//  四门押注配置
	public function set_simenyazhu_config(){
		$post = $this->input->post();
		if ( !$post ) {
			$js = $this->system_model->get_config("gm");
			$config = json_decode($js,true);
			output('system/set_simenyazhu_config.phtml',array('config'=>$config));
			return;
		}

		$config = array();
		$config["SimenyazhuDealerTimes"] = $post["simenyazhu_dealer_times"]*1;
		$config["SimenyazhuSystemTax"] = $post["simenyazhu_system_tax"]*1;
		$this->system_model->update_config("gm",json_encode($config));

		$route_msg = array(
			"ServerList" => array("smyz"),
			"Name" => "FUNC_UpdateConfig",
		);
		$this->gm_model->request("/Route",$route_msg);

		output('system/set_simenyazhu_config.phtml',array('config'=>$config));
	}
	//  ShanKoeMee配置
	public function set_shankoemee_config(){
		$post = $this->input->post();
		if ( !$post ) {
			$js = $this->system_model->get_config("gm");
			$config = json_decode($js,true);
			output('system/set_shankoemee_config.phtml',array('config'=>$config));
			return;
		}

		$config = array();
		$config["ShanKoeMeeDealerTimes"] = $post["shankoemee_dealer_times"]*1;
		$config["ShanKoeMeeDealerTaxRate"] = $post["shankoemee_dealer_tax_rate"]*1;
		$config["ShanKoeMeePlayerTaxRate"] = $post["shankoemee_player_tax_rate"]*1;
		$this->system_model->update_config("gm", json_encode($config));

		$route_msg = array(
			"ServerList" => array("koe"),
			"Name" => "FUNC_UpdateConfig",
		);
		$this->gm_model->request("/Route",$route_msg);

		output('system/set_shankoemee_config.phtml',array('config'=>$config));
	}
	// 配置表清单
    public function config_tables(){
        $rows = $this->system_model->config_table_list();
        output('system/config_tables.phtml',array('rows'=>$rows));
    }       
    // 生效配置表
    public function effect_config_table(){
        $name = $this->input->get("name");
		$data = array("ServerList"=>array("*"),"Name"=>"FUNC_EffectConfigTable","Data"=>json_encode(array("Name"=>$name)));
        $this->gm_model->request("/Route",$data);
										        
        $this->load->helper('errcode');
	    $this->system_model->effect_config_table($name);
        echo err_string("SUCCESS",array("EffectTime"=>date('Y-m-d H:i:s')));
    }       
    // 编辑配置表
    public function set_config_table(){
	    $id = $this->input->get("id");
   		$table = array();
        if ( $id ) {
	        $table = $this->system_model->config_table($id);
		}
		$post = $this->input->post();
		if ( $post ) {
			$this->load->model("config_model");
			$content = $post["content"];
			$this->config_model->parse($content);
			$err = $this->config_model->get_last_error();
			if ( $err ) {
				$post["err"] = $err;
				output('system/set_config_table.phtml',$post);
			} else {
				$this->system_model->set_config_table($post);
				redirect("/v1/system/config_tables");
			}
		} else {
			output('system/set_config_table.phtml',$table);
		}
	}
	// 赠送道具
    public function add_items() {
		$get = $this->input->get();
		$uid = @$get['uid'];
		$this->load->model("config_model");

		$add_item_list = array();
		foreach ( $get as $k => $v ) {
			if ( strpos($k,"item_") == 0 ) {
				$item_id = substr($k,strlen("item_"));
				$item_id = intval($item_id);
				$item_num = intval($v);
				if ( $item_id && $item_num ) {
					$add_item = array("Id"=>$item_id,"Num"=>$item_num);
					array_push($add_item_list,$add_item);
				}
			}
		}
		if ( $uid && $add_item_list ) {
			$this->load->model("gm_model");
			$this->gm_model->request('/AddItems',array("UId"=>intval($uid),"GUID"=>create_uuid(),"Way"=>"gm_deal","Items"=>$add_item_list));
			redirect("/v1/data/user_info?uid=$uid");
			exit;
		}
		$items = $this->config_model->rows("item");
		output('system/add_items.phtml',array("uid"=>$uid,"items"=>$items));
	}
}

/* End of file welcome.php */
/* Location: ./system/application/controllers/welcome.php */
