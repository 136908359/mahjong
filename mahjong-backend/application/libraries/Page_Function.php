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
	
	function show_page($totle_num, $this_page, $num4page = 10, $show_page = 5, $max_page = 0, $page_url_query = 'page', $url,$keyword,$sid,$tid,$pid, $is_search = false, $is_goto = false, $is_advanced = false)
	{
		if($keyword || $sid || $tid || $pid){
			if($keyword){
				$k = '&keyword='.$keyword;
			}
			else{
				$k = '';
			}
			
			if($sid){
				$s = '&sid='.$sid;
			}
			else{
				$s = '';
			}
			
			if($tid){
				$t = '&tid='.$tid;
			}
			else{
				$t = '';
			}
			if($pid){
				$p = '&pid='.$pid;
			}
			else{
				$p = '';
			}
			
			$page_data = page($totle_num, $this_page, $num4page, $show_page, $max_page);
			$issearch = "";
			if($is_search == 1){
				$issearch = "s=1&";
			}
			$show_page_str4totle = $is_advanced?'<div>(Total:'.$page_data['totle_num'].')</div>':'';
			$show_page_str4page = "";
			foreach($page_data['show_page_array'] as $value)
			{
				if($value == $page_data['this_page'])
				{
					$show_page_str4page .= '<span class="current">'.$value.'</span>';
				}
				elseif($value == 'left_ellipsis' || $value == 'right_ellipsis')
				{
					$show_page_str4page .= '...';
				}
				else
				{
					$show_page_str4page .= '<a href="'.$url.'?'.$issearch.'page='.$value.$t.$s.$p.$k.'">'.$value.'</a>';
				}
			}
			$divstart = '<div class="multipages">';
			$divend = '</div>';
			
			$home_page = $page_data['previous_page']?'<a href="'.$url.'?'.$issearch.'page=1" class="first"><i class="ico"></i></a>':'<a class="first"><i class="ico"></i></a>';
			$end_page = $page_data['next_page']?'<a href="'.$url.'?'.$issearch.'page='.$page_data['totle_page'].$t.$s.$p.$k.'" class="last"><i class="ico"></i></a>':'<a class="last"><i class="ico"></i></a>';
			
			$show_page_str4previous_page = $page_data['previous_page']?'<a href="'.$url.'?'.$issearch.'page='.$page_data['previous_page'].$t.$s.$p.$k.'" class="prev"><i class="ico"></i></a>':'<a  class="prev"><i class="ico"></i></a>';
			
			$show_page_str4next_page = $page_data['next_page']?'<a href="'.$url.'?'.$issearch.'page='.$page_data['next_page'].$t.$s.$p.$k.'" class="next"><i class="ico"></i></a>':'<a class="next"><i class="ico"></i></a>';
		
		}else{
		
			$page_data = page($totle_num, $this_page, $num4page, $show_page, $max_page);
			$issearch = "";
			if($is_search == 1){
				$issearch = "s=1&";
			}
			$show_page_str4totle = $is_advanced?'<div>(Total:'.$page_data['totle_num'].')</div>':'';
			$show_page_str4page = "";
			foreach($page_data['show_page_array'] as $value)
			{
				if($value == $page_data['this_page'])
				{
					$show_page_str4page .= '<span class="current">'.$value.'</span>';
				}
				elseif($value == 'left_ellipsis' || $value == 'right_ellipsis')
				{
					$show_page_str4page .= '...';
				}
				else
				{
					$show_page_str4page .= '<a href="'.$url.'?'.$issearch.'page='.$value.'">'.$value.'</a>';
				}
			}
			$divstart = '<div class="multipages">';
			$divend = '</div>';
			
			$home_page = $page_data['previous_page']?'<a href="'.$url.'?'.$issearch.'page=1" class="first"><i class="ico"></i></a>':'<a class="first"><i class="ico"></i></a>';
			$end_page = $page_data['next_page']?'<a href="'.$url.'?'.$issearch.'page='.$page_data['totle_page'].'" class="last"><i class="ico"></i></a>':'<a class="last"><i class="ico"></i></a>';
			
			$show_page_str4previous_page = $page_data['previous_page']?'<a href="'.$url.'?'.$issearch.'page='.$page_data['previous_page'].'" class="prev"><i class="ico"></i></a>':'<a  class="prev"><i class="ico"></i></a>';
			
			$show_page_str4next_page = $page_data['next_page']?'<a href="'.$url.'?'.$issearch.'page='.$page_data['next_page'].'" class="next"><i class="ico"></i></a>':'<a class="next"><i class="ico"></i></a>';
		}
		//$show_page_str4goto_page = ($is_advanced && $is_goto)?'<div><input type="text" onkeydown="if(event.keyCode==13)change_url_query([\''.$page_url_query.'\'],[this.value])" size="3" class="gotopage"></div>':'';
		/*return $show_page_str4totle.$show_page_str4previous_page.$show_page_str4page.$show_page_str4next_page.$show_page_str4goto_page;*/
		return $divstart.$home_page.$show_page_str4previous_page.$show_page_str4page.$show_page_str4next_page.$end_page.$divend;
		
	}
	
	function show_default_page($totle_num, $this_page, $num4page = 10, $show_page = 5, $max_page = 0, $page_url_query = 'page', $url,$str, $is_search = false, $is_goto = false, $is_advanced = false)
	{	
			$str['tid'] = '';
			$str['pid'] = '';
			$str['sid'] = '';
			if($str['tid'] || $str['pid'] || $str['sid'] ){
				if($str['tid'] && $str['pid'] && $str['sid'] ){
					$sql_where = "&tid=".$str['tid']."&pid=".$str['pid']."&sid=".$str['sid'];
				}
				else if(!$str['tid'] && $str['pid'] && $str['sid'] ){
					$sql_where = "&pid=".$str['pid']."&sid=".$str['sid'];
				
				}
				else if($str['tid'] && !$str['pid'] && $str['sid'] ){
					$sql_where = "&tid=".$str['tid']."&sid=".$str['sid'];
				
				}
				else if($str['tid'] && $str['pid'] && !$str['sid'] ){
					$sql_where = "&tid=".$str['tid']."&pid=".$str['pid'];
				}
				else if($str['tid'] && !$str['pid'] && !$str['sid'] ){
					$sql_where = "&tid=".$str['tid'];
				}
				else if(!$str['tid'] && $str['pid'] && !$str['sid'] ){
					$sql_where = "&pid=".$str['pid'];
				}
				else if(!$str['tid'] && !$str['pid'] && $str['sid'] ){
					$sql_where = "&sid=".$str['sid'];
				}
				
				$page_data = page($totle_num, $this_page, $num4page, $show_page, $max_page);
				$issearch = "";
				if($is_search == 1){
					$issearch = "s=1&";
				}
				$show_page_str4totle = $is_advanced?'<div>(Total:'.$page_data['totle_num'].')</div>':'';
				$show_page_str4page = "";
				foreach($page_data['show_page_array'] as $value)
				{
					if($value == $page_data['this_page'])
					{
						$show_page_str4page .= '<a class="cpb" href="javascript:">'.$value.'</a>';
					}
					elseif($value == 'left_ellipsis' || $value == 'right_ellipsis')
					{
						$show_page_str4page .= '...';
					}
					else
					{
						$get_pos = strpos($url,'?');
						if ($get_pos && $get_pos!=-1) {
							$show_page_str4page .= '<a href="'.$url.'&'.$issearch.'page='.$value.$sql_where.'" class="anpager">'.$value.'</a>';
						}else{
							$show_page_str4page .= '<a href="'.$url.'?'.$issearch.'page='.$value.$sql_where.'" class="anpager">'.$value.'</a>';
						}
					}
				}
				
				
				$get_pos = strpos($url,'?');
				if ($get_pos && $get_pos!=-1) {
					$home_page = $page_data['previous_page']?'<a href="'.$url.'&'.$issearch.'page=1'.$sql_where.'" id="firstPageNow"  class="anpager">首  页</a>':'<a href="javascript:" class="anpager" id="firstPageNow"  class="anpager">首  页</a>';
					$end_page = $page_data['next_page']?'<a href="'.$url.'&'.$issearch.'page='.$page_data['totle_page'].$sql_where.'" class="anpager" id="lastPage" >尾 页</a>':'<a class="anpager" id="lastPage" >尾 页</a>';
					
					$show_page_str4previous_page = $page_data['previous_page']?'<a href="'.$url.'&'.$issearch.'page='.$page_data['previous_page'].$sql_where.'" id="prevPageNow" class="anpager">上一页</a>':'<a  class="anpager" id="prevPageNow" href="javascript:">上一页</a>';
					
					$show_page_str4next_page = $page_data['next_page']?'<a href="'.$url.'&'.$issearch.'page='.$page_data['next_page'].$sql_where.'"  id="nextPage" class="anpager">下一页</a>':'<a class="anpager"  id="nextPage" href="javascript:">下一页</a>';
				}
				else{
					$home_page = $page_data['previous_page']?'<a href="'.$url.'?'.$issearch.'page=1'.$sql_where.'" id="firstPageNow"  class="anpager">首  页</a>':'<a href="javascript:" class="anpager" id="firstPageNow"  class="anpager">首  页</a>';
					$end_page = $page_data['next_page']?'<a href="'.$url.'?'.$issearch.'page='.$page_data['totle_page'].$sql_where.'" class="anpager" id="lastPage" >尾 页</a>':'<a class="anpager" id="lastPage" >尾 页</a>';
					
					$show_page_str4previous_page = $page_data['previous_page']?'<a href="'.$url.'?'.$issearch.'page='.$page_data['previous_page'].$sql_where.'" id="prevPageNow" class="anpager">上一页</a>':'<a  class="anpager" id="prevPageNow" href="javascript:">上一页</a>';
					
					$show_page_str4next_page = $page_data['next_page']?'<a href="'.$url.'?'.$issearch.'page='.$page_data['next_page'].$sql_where.'"  id="nextPage" class="anpager">下一页</a>':'<a class="anpager"  id="nextPage" href="javascript:">下一页</a>';
				}
			}else{
				$page_data = page($totle_num, $this_page, $num4page, $show_page, $max_page);
				$issearch = "";
				if($is_search == 1){
					$issearch = "s=1&";
				}
				$show_page_str4totle = $is_advanced?'<div>(Total:'.$page_data['totle_num'].')</div>':'';
				$show_page_str4page = "";
				foreach($page_data['show_page_array'] as $value)
				{
					if($value == $page_data['this_page'])
					{
						$show_page_str4page .= '<a class="cpb" href="javascript:">'.$value.'</a>';
					}
					elseif($value == 'left_ellipsis' || $value == 'right_ellipsis')
					{
						$show_page_str4page .= '...';
					}
					else
					{
						$get_pos = strpos($url,'?');
						if ($get_pos && $get_pos!=-1) {
							$show_page_str4page .= '<a href="'.$url.'&'.$issearch.'page='.$value.'" class="anpager">'.$value.'</a>';
						}else{
							$show_page_str4page .= '<a href="'.$url.'?'.$issearch.'page='.$value.'" class="anpager">'.$value.'</a>';
						}
						
					}
				}
				
				
				$get_pos = strpos($url,'?');
				if ($get_pos && $get_pos!=-1) {
					$home_page = $page_data['previous_page']?'<a href="'.$url.'&'.$issearch.'page=1" class="anpager">首  页</a>':'<a href="javascript:" class="anpager" class="anpager">首  页</a>';
					$end_page = $page_data['next_page']?'<a href="'.$url.'&'.$issearch.'page='.$page_data['totle_page'].'" class="anpager">尾 页</a>':'<a class="anpager">尾 页</a>';
						
					$show_page_str4previous_page = $page_data['previous_page']?'<a href="'.$url.'&'.$issearch.'page='.$page_data['previous_page'].'" class="anpager">上一页</a>':'<a  class="anpager" href="javascript:">上一页</a>';
						
					$show_page_str4next_page = $page_data['next_page']?'<a href="'.$url.'&'.$issearch.'page='.$page_data['next_page'].'" class="anpager">下一页</a>':'<a class="anpager" href="javascript:">下一页</a>';
				}
				else{
					$home_page = $page_data['previous_page']?'<a href="'.$url.'?'.$issearch.'page=1" class="anpager">首  页</a>':'<a href="javascript:" class="anpager" class="anpager">首  页</a>';
					$end_page = $page_data['next_page']?'<a href="'.$url.'?'.$issearch.'page='.$page_data['totle_page'].'" class="anpager">尾 页</a>':'<a class="anpager">尾 页</a>';
					
					$show_page_str4previous_page = $page_data['previous_page']?'<a href="'.$url.'?'.$issearch.'page='.$page_data['previous_page'].'" class="anpager">上一页</a>':'<a  class="anpager" href="javascript:">上一页</a>';
					
					$show_page_str4next_page = $page_data['next_page']?'<a href="'.$url.'?'.$issearch.'page='.$page_data['next_page'].'" class="anpager">下一页</a>':'<a class="anpager" href="javascript:">下一页</a>';
				}
			}
			return $home_page.$show_page_str4previous_page.$show_page_str4page.$show_page_str4next_page.$end_page;
		
	}
?>