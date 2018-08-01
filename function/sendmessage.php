<?php
function SendMessage($tmid, $message) {
	global $C, $G;
	$post = array(
		"message" => $message,
		"access_token" => $C['FBpagetoken']
	);
	$res = cURL($C['FBAPI'].$tmid."/messages", $post);
	$res = json_decode($res, true);
	if (isset($res["error"])) {
		WriteLog("[smsg][error] res=".json_encode($res)." tmid=".$tmid." msg=".$message);
		if ($res["error"]["code"] === 230) {
			$sth = $G["db"]->prepare("UPDATE `{$C['DBTBprefix']}user` SET `fbmessage` = 0 WHERE `tmid` = :tmid");
			$sth->bindValue(":tmid", $tmid);
			$sth->execute();
			WriteLog("[fbmsg][info][unfollow] tmid=".$tmid);
		}
		return $res["error"];
	}
	return true;
}

function SendTGMessage($chat_id, $message) {
	global $C, $G;
	$post = array(
		"chat_id" => $chat_id,
		"disable_web_page_preview" => true,
		"text" => $message
	);
	$res = cURL("https://api.telegram.org/bot".$C['TGtoken']."/sendMessage", $post);
	$res = json_decode($res, true);
	if (!isset($res["ok"])) {
		WriteLog("[tgmsg][error] res=".json_encode($res));
		return false;
	}
	return true;
}
