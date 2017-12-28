<?php
class Admin_Model extends CI_Model {
	function __construct() {
		parent::__construct();
		$this->load->database();
	}
	/*
	*  判断账号是否具有权限 
	*/
	public function access($account,$uri) {
		$sql = "select 1 from gm_member left join gm_group on gm_member.group_id=gm_group.id left join gm_group_menu on gm_group.id=gm_group_menu.group_id left join gm_menu on gm_group_menu.menu_id = gm_menu.id where gm_member.account=? and gm_menu.uri=? and gm_member.`status`='normal' and gm_group_menu.`status`='normal'";
		$row = $this->db->query($sql,array($account, $uri))->row_array();
		if (empty($row)) {
			return false;
		}
		return true;
	}
	public function get_account() {
        $this->load->library('session');
		return $this->session->userdata('account');
	}
	//获取渠道列表
	public function get_chan_list() {
		$sql = "select * from gm_channel";
		return $this->db->query($sql)->result_array();
	}
	public function get_chan_by_id($id) {
		return $this->db->query("select * from gm_channel where id=?",array($id))->row_array();
	}

	public function add_chan($data) {
		$this->db->replace('gm_channel',$data);
	}
	/*
	*  管理员
	*/
	public function get_member_list() {
		$sql = "select m.account,g.title as group_title,m.`coment`,m.`status`,c.`chan_id`,m.create_time from gm_member m left join gm_group g on m.group_id=g.id left join gm_channel c on m.chan_id=c.chan_id";
		return $this->db->query($sql,array())->result_array();
	}

	public function get_member($account) {
		$sql = "select account,passwd,group_id,`coment`,`status`,`chan_id`,create_time from gm_member where account=?";
		return $this->db->query($sql,array($account))->row_array();
	}
	
	public function add_member($member) {
		$sql = "insert into gm_member(account,passwd,group_id,`coment`,`status`,chan_id,create_time) values(?,?,?,?,?,?,now())";
		$account = $member['account'];
		$passwd = md5($member['passwd']);
		$group_id = $member['group_id'];
		$status = $member['status'];
		$chan_id = $member['chan_id'];
		$coment = $member['coment'];
		$this->db->query($sql,array($account, $passwd, $group_id, $coment, $status, $chan_id));
	}

	public function edit_member($member,$method='') {
		$account = $member['account'];
		$passwd = md5($member['passwd']);
		$group_id = $member['group_id'];
		$status = $member['status'];
		$chan_id = $member['chan_id'];
		$coment = $member['coment'];
		
		if($method == 'pwd'){
			$sql = "update gm_member set `passwd`=?,group_id=?,`coment`=?,`status`=?,chan_id=? where account=?";
			$this->db->query($sql,array($passwd, $group_id, $coment, $status, $chan_id, $account));			
		}else{
			$sql = "update gm_member set group_id=?,`coment`=?,`status`=?,chan_id=? where account=?";
			$this->db->query($sql,array($group_id, $coment, $status, $chan_id, $account));
		}
		
	}
		
	/********************************************************************
	*  用户组管理
	********************************************************************/
	/*
	*  创建用户组
	*/
	public function get_group_list() {
		$sql = "select id,title,`coment` from gm_group";
		return $this->db->query($sql,array())->result_array();
	}
	public function get_group($group_id) {
		$sql = "select id,title,`coment` from gm_group where id=?";
		$group = $this->db->query($sql,array($group_id))->row_array();
		$sql = "select g.menu_id as id,m.title from gm_group_menu g left join gm_menu m on g.menu_id=m.id where g.group_id=? and g.`status`='normal'";
		$menus = $this->db->query($sql,array($group_id))->result_array();

		$a = array();
		foreach ($menus as $k=>$m) {
			$a[$m['id']] = $m;
		}
		$group['menus'] = $a;
		return $group;
	}

	public function add_group($group) {
		$title = $group['title'];
		$coment = $group['coment'];
		$sql = "insert into gm_group(title,coment,`status`) values(?,?,'normal')";
		$this->db->query($sql,array($title, $coment));
	}
	public function edit_group($group) {
		$group_id = $group['id'];
		$menus = $group['menus'];
		$title = $group['title'];
		$coment = $group['coment'];
		$this->db->trans_start();
		$sql = "update gm_group set `title`= ?,`coment`= ? where id=?";
		$this->db->query($sql, array($title,$coment,$group_id));	

		$sql = "update gm_group_menu set `status`='forbid' where group_id=?";
		$this->db->query($sql,array($group_id));
		
		$sql = "insert into gm_group_menu(group_id,menu_id,`status`) values(?,?,'normal') on duplicate key update `status`='normal'";
		foreach ($menus as $menu_id) {
			$this->db->query($sql,array($group_id,$menu_id));
		} 
		$this->db->trans_complete();
	}
	
	/********************************************************************
	*  菜单
	*  当前仅支持二级菜单管理
	********************************************************************/
	/*
	*  创建用户可访问菜单
	*/
	public function get_menu_list() {
		$sql = "select id,title,parent_id,uri,`show`,`coment` from gm_menu";
		return $this->db->query($sql,array())->result_array();
	}

	public function get_menu_by_id($id) {
		$sql = "select id,title,parent_id,uri,`show`,`coment` from gm_menu where id=?";
		return $this->db->query($sql,array($id))->row_array();
	}

	public function add_menu($menu) {
		$title = $menu['title'];
		$parent_id = $menu['parent_id'];
		$uri = $menu['uri'];
		$show = $menu['show'];
		$coment = $menu['coment'];
		$sql = "insert into gm_menu(title,parent_id,uri,`show`,`coment`) values(?,?,?,?,?)";
		$this->db->query($sql,array($title, $parent_id, $uri, $show, $coment));
	}

	public function edit_menu($menu) {
		$id = $menu['id'];
		$title = $menu['title'];
		$parent_id = $menu['parent_id'];
		$uri = $menu['uri'];
		$show = $menu['show'];
		$coment = $menu['coment'];
		$sql = "update gm_menu set title=?,parent_id=?,uri=?,`show`=?,`coment`=? where id=?";
		$this->db->query($sql,array($title, $parent_id, $uri, $show, $coment, $id));
	}
	
		
	public function get_menu_nav($uri) {
		// 当前菜单
		$sql = "select id,title,parent_id,uri from gm_menu where uri=?";
		$menu2 = $this->db->query($sql,array($uri))->row_array();
		// 上级菜单
		$sql = "select id,title,parent_id,uri from gm_menu where id=?";
		$menu1 = $this->db->query($sql,array($menu2['parent_id']))->row_array();
		return array($menu1['title'], $menu2['title']);
	}

	public function get_menu_tree($account) {
		$member = $this->get_member($account);
		$group_id = $member['group_id'];
		$menu_tree = array();
		// 第一级菜单
		$sql = "select id,title,parent_id,uri from gm_menu where parent_id=(-1)";
		$menu1 = $this->db->query($sql,array($account))->result_array();
		$menu_tree = array_merge($menu_tree, $menu1);
		// 第二级菜单
		$sql = "select m.id,m.title,m.parent_id,m.uri from gm_group_menu g left join gm_menu m on g.menu_id=m.id where group_id=? and m.show = 1 and g.status = 'normal'";
		$menu2 = $this->db->query($sql,array($group_id))->result_array();
		foreach ($menu_tree as $k=>$m1) {
			$m1['child'] = array();
			foreach ($menu2 as $m2) {
				if ($m1['id'] == $m2['parent_id']) {
					array_push($m1['child'], $m2);
				}
			}
			$menu_tree[$k] = $m1;
			if (empty($m1['child'])) {
				unset($menu_tree[$k]);
			}
		}
		return $menu_tree;
	}

	public function add_operate_log($account,$uri,$link,$data) {
		$this->db->insert("gm_operate_log",array("account"=>$account,"link"=>$link,"uri"=>$uri,"data"=>$data));	
	}
	public function get_operate_log($account,$start_time,$end_time,$page) {
		$where = ' where 1=1';
		if ( $account ) {
			$where .= " and account ='$account'";
		}
		if ( $start_time ) {
			$where .= " and create_time >= '$start_time'";
		}
		if ( $end_time ) {
			$where .= " and create_time < '$end_time'";
		}
		$page_size = GM_PAGE_SIZE;
		$rows = $this->db->query("select g.*,m.title from gm_operate_log g left join gm_menu m on g.uri=m.uri $where order by id desc limit ?,?",array($page*$page_size,$page_size))->result_array();
		$data = array('rows'=>$rows);

		$row = $this->db->query("select count(*) as total_rows from gm_operate_log $where")->row_array();
		$data = array_merge($data,(array)$row);
		return $data;
	}
}
?>
