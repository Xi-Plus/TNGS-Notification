<?php
require(__DIR__.'/config/config.php');
if (!in_array(PHP_SAPI, $C["allowsapi"])) {
	exit("No permission");
}

require(__DIR__.'/function/log.php');

$sth = $G["db"]->prepare("SELECT * FROM `{$C['DBTBprefix']}news` WHERE `tgmessage` = 0 ORDER BY `time` DESC");
$sth->execute();
$row = $sth->fetchAll(PDO::FETCH_ASSOC);

if (count($row) == 0) {
	exit("No new\n");
}

$message="";
foreach ($row as $temp) {
	$message .= "#".$temp["idx"]."\n".date("m/d", strtotime($temp["date"]))." ".$temp["department"]."：".$temp["text"]."\n".$temp["url"]."\n\n";
}

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.telegram.org/bot".$C['TGtoken']."/sendMessage");
curl_setopt($ch, CURLOPT_POST, true);
$post = array(
	"chat_id" => $C["TGchatid"],
	"disable_web_page_preview" => true,
	"text" => $message
);
curl_setopt($ch,CURLOPT_POSTFIELDS, http_build_query($post));
curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
$res = curl_exec($ch);
curl_close($ch);

$res = json_decode($res, true);
if (!isset($res["ok"])) {
	WriteLog("[tgmsg][error] res=".json_encode($res));
} else {
	$sth = $G["db"]->prepare("UPDATE `{$C['DBTBprefix']}news` SET `tgmessage` = '1' WHERE `hash` = :hash");
	foreach ($row as $temp) {
		$sth->bindValue(":hash", $temp["hash"]);
		$sth->execute();
	}
}
