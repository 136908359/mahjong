<?php
	function message_format4ajax($message){
		return str_ireplace(array('<br />'),array("\n"),$message);
	}
	
?>