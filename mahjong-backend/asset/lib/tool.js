function exportToExcel(link,table) {
        var uri = ""; 
		table = document.getElementById(table)
		for (var i = 0; i < table.rows.length; i++) {    //遍历Table的所有Row
			for (var j = 0; j < table.rows[i].cells.length; j++) {   //遍历Row中的每一列
				txt = table.rows[i].cells[j].innerText;   //获取Table中单元格的内容
				txt = txt.replace(',','');
				uri += txt+",";
			}
			uri += "\n";
		}
		base64 = function (s) { return window.btoa(unescape(encodeURIComponent(s))) },
		link.href = "data:application/csv;base64,"+base64("\ufeff"+uri);  	
     }
