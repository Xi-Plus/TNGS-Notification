<?php
date_default_timezone_set("Asia/Taipei");
require(__DIR__.'/config/config.php');
require(__DIR__.'/curl.php');

$method = $_SERVER['REQUEST_METHOD'];
if ($method == 'GET' && $_GET['hub_mode'] == 'subscribe' &&  $_GET['hub_verify_token'] == $C['FBWHtoken']) {
	echo $_GET['hub_challenge'];
} else if ($method == 'POST') {
	$inputJSON = file_get_contents('php://input');
	$input = json_decode($inputJSON, true);
	function SendMessage($tmid, $message, $token) {
		global $C;
		$post = array(
			"message" => $message,
			"access_token" => $C['FBpagetoken']
		);
		$res = cURL($C['FBAPI'].$tmid."/messages", $post);
		$res = json_encode($res, true);
		if (isset($res["error"])) {
			file_put_contents("log/SendMessageFail-".date("Y-m-d-H-i-s").".log", json_encode($res));
		}
	}
	$res = cURL($C['FBAPI']."me/conversations?fields=participants,updated_time&access_token=".$C['FBpagetoken']);
	$updated_time = file_get_contents("updated_time.txt");
	$newesttime = $updated_time;
	while (true) {
		$res = json_decode($res, true);
		if (count($res["data"]) == 0) {
			break;
		}
		foreach ($res["data"] as $data) {
			if ($data["updated_time"] <= $updated_time) {
				break 2;
			}
			if ($data["updated_time"] > $newesttime) {
				$newesttime = $data["updated_time"];
			}
			foreach ($data["participants"]["data"] as $participants) {
				if ($participants["id"] != $C['FBpageid']) {
					$sth = $G["db"]->prepare("INSERT INTO `{$C['DBTBprefix']}user` (`uid`, `tmid`, `name`) VALUES (:uid, :tmid, :name)");
					$sth->bindValue(":uid", $participants["id"]);
					$sth->bindValue(":tmid", $data["id"]);
					$sth->bindValue(":name", $participants["name"]);
					$res= $sth->execute();
					// file_put_contents("log/insert-".date("Y-m-d-H-i-s").".log", $participants["id"]."\n".$data["id"]."\n".$participants["name"]."\n".json_encode($res)."\n");
					break;
				}
			}
		}
		$res = cURL($res["paging"]["next"]);
	}
	file_put_contents("updated_time.txt", $newesttime);
	foreach ($input['entry'] as $entry) {
		foreach ($entry['messaging'] as $messaging) {
			$mmid = "m_".$messaging['message']['mid'];
			$res = cURL($C['FBAPI'].$mmid."?fields=from&access_token=".$C['FBpagetoken']);
			$res = json_decode($res, true);
			$uid = $res["from"]["id"];

			$sth = $G["db"]->prepare("SELECT * FROM `{$C['DBTBprefix']}user` WHERE `uid` = :uid");
			$sth->bindValue(":uid", $uid);
			$sth->execute();
			$row = $sth->fetch(PDO::FETCH_ASSOC);
			if ($row === false) {
				file_put_contents("log/tmidNotFound-".date("Y-m-d-H-i-s").".log", json_encode($messaging));
				continue;
			}
			$tmid = $row["tmid"];
			$name = $row["name"];
			if (!isset($messaging['message']['text'])) {
				SendMessage($tmid, "僅接受文字訊息");
				continue;
			}
			$message = $messaging['message']['text'];
			SendMessage($tmid, "嗨！".$name."。收到訊息：".$message);
		}
	}
}
