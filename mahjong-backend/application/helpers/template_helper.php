<?php
function output($s, $data) {
	$CI = &get_instance();
	$seg1 = $CI->uri->segment(1);
	$seg2 = $CI->uri->segment(2);
	$seg3 = $CI->uri->segment(3);

	$uri = "$seg1/$seg2";
	$path = BASEPATH."../application/controllers/".$seg1;
	if (is_dir($path)) {
		$uri = "/$seg1/$seg2/$seg3";
	}
	$CI->load->model("admin/admin_model");
	$nav = $CI->admin_model->get_menu_nav($uri);
	$header = array(
		"page_title" => end($nav),
		"page_nav" => $nav
	);
	$CI->load->view("admin/_header.phtml", $header);
	$CI->load->view($s, $data);
	$footer = array();
	$CI->load->view("admin/_footer.phtml", $footer);
}	

function create_page_links($url,$cur_page,$total_rows,$params) {
	$CI = &get_instance();
    $CI->load->library('pagination');
	if ( $params ) {
		$url=$url."?".http_build_query($params);
	}
	$cur_page = intval($cur_page);
    $config['base_url'] = $url;
    $config['total_rows'] = $total_rows;
    $config['cur_page'] = $cur_page;
    $config['per_page'] = GM_PAGE_SIZE;
    $config['first_link'] = '首页';
    $config['prev_link'] = '上一页';
    $config['next_link'] = '下一页';
    $config['last_link'] = '尾页';
    $config['page_query_string'] = true;
    $CI->pagination->initialize($config);
	return $CI->pagination->create_links();
}
?>
