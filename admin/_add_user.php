<?php

$query = "SELECT * FROM channels WHERE id IN (".implode(',',$InputChannel).")";
$result = $mysqli->query($query);
while($channel = $result->fetch_array()) {		
	
	$chat_id = $channel["chatid"];
	$editBanned = file_get_contents($apiServer."channels.editBanned/?data[channel]=$chat_id&data[user_id]=$ItemDesc&data[banned_rights][until_date]=0&data[banned_rights][view_messages]=0&data[banned_rights][_]=chatBannedRights");
	
	$joinName = urlencode($channel["name"].": ".$channel["url"]);
	$joinUrl  = $channel["url"];
	time.sleep(1);
	//$sendMessageUrl = file_get_contents("https://api.telegram.org/bot".$apitoken."/sendMessage?chat_id=$userid&text=$joinName: $joinUrl&disable_web_page_preview=1&disable_notification=1");
	$sendMessageUrl = file_get_contents($apiServer."sendMessage/?data[peer]=$userid&data[message]=$joinName&data[no_webpage]=1&data[silent]=1");
	
}
?>