<?php
	function page($totle_num, $this_page, $num4page = 10, $show_page = 5, $max_page = 0)
	{
		$data['totle_num'] = $totle_num;
		$data['totle_page'] = $totle_page = ($max_page > ceil($totle_num / $num4page) || $max_page == 0)?ceil($totle_num / $num4page):$max_page;
		$data['totle_page'] = $totle_page = $totle_num == 0?1:$data['totle_page'];
		$data['this_page'] = $this_page = (intval($this_page) >= 1 && intval($this_page) <= $totle_page)? intval($this_page):( intval($this_page) < 1?1:$totle_page);
		$data['this_page'] = $this_page = $totle_num == 0?1:$data['this_page'];
		$data['next_page'] = $next_page = ($this_page + 1) > $totle_page?false:($this_page + 1);
		$data['previous_page'] = $previous_page = ($this_page - 1) < 1?false:($this_page - 1);
		
		$show_page_start = ($this_page - floor($show_page/2)) > 0?($this_page - floor($show_page/2)):1;
		$show_page_start = (($show_page_start + $show_page) > $totle_page)?$totle_page - $show_page:$show_page_start;
		$show_page_start = $show_page_start <= 0?1:$show_page_start;
		for($i = 1;$i <= $totle_page;$i++)
		{
			if($i == 1)
			{
				$show_page_array[] = $i;
			}
			elseif($i == $totle_page)
			{
				$show_page_array[] = $i;
			}
			elseif($i < $show_page_start)
			{
				$show_page_array[] = 'left_ellipsis';
			}
			elseif($i >= $show_page_start && $i < $show_page_start + $show_page)
			{
				$show_page_array[] = $i;
			}
			elseif($i > $show_page_start + $show_page)
			{
				$show_page_array[] = 'right_ellipsis';
			}
		}
		$data['show_page_array'] = $show_page_array = array_unique($show_page_array);
		return $data;
	}
	
	function show_page($totle_num, $this_page, $num4page = 10, $show_page = 5, $max_page = 0, $page_url_query = 'page', $is_goto = false, $is_advanced = false)
	{
		$page_data = page($totle_num, $this_page, $num4page, $show_page, $max_page);

		$show_page_str4totle = $is_advanced?'<div>(总计:'.$page_data['totle_num'].')</div>':'';
		$show_page_str4page = "";
		foreach($page_data['show_page_array'] as $value)
		{
			if($value == $page_data['this_page'])
			{
				$show_page_str4page .= '<div>'.$value.'</div>';
			}
			elseif($value == 'left_ellipsis' || $value == 'right_ellipsis')
			{
				$show_page_str4page .= '<div>...</div>';
			}
			else
			{
				$show_page_str4page .= '<div><a href="" onclick="change_url_query([\''.$page_url_query.'\'],[\''.$value.'\'])" class="change_url_query">'.$value.'</a></div>';
			}
		}
		
		$show_page_str4previous_page = $page_data['previous_page']?'<div onclick="change_url_query([\''.$page_url_query.'\'],[\''.$page_data['previous_page'].'\'])" class="change_url_query previous_page"></div>':'';
		$show_page_str4next_page = $page_data['next_page']?'<div onclick="change_url_query([\''.$page_url_query.'\'],[\''.$page_data['next_page'].'\'])" class="change_url_query next_page"></div>':'';
		
		$show_page_str4goto_page = ($is_advanced && $is_goto)?'<div><input type="text" onkeydown="if(event.keyCode==13)change_url_query([\''.$page_url_query.'\'],[this.value])" size="3" class="gotopage"></div>':'';
		return $show_page_str4totle.$show_page_str4previous_page.$show_page_str4page.$show_page_str4next_page.$show_page_str4goto_page;
	}
?>