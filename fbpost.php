<?php
require(__DIR__.'/config/config.php');
require(__DIR__.'/log.php');

if (!in_array(PHP_SAPI, array("cli", "apache2handler"))) {
	exit("No permission");
}
define("EOL", (PHP_SAPI==="apache2handler"?"<br>\n":PHP_EOL));

$sth = $G["db"]->prepare("SELECT * FROM `{$C['DBTBprefix']}news` WHERE `fbpost` = 0 ORDER BY `time` DESC");
$sth->execute();
$row = $sth->fetchAll(PDO::FETCH_ASSOC);

if (count($row) == 0) {
	exit("No new".EOL);
}

$message="";
foreach ($row as $temp) {
	$message .= "#".$temp["idx"]."\n".date("m/d", strtotime($temp["date"]))." ".$temp["department"]." ".$temp["type"]."：".$temp["text"]."\n".$temp["url"]."\n\n";
}

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://graph.facebook.com/v2.8/me/feed");
curl_setopt($ch, CURLOPT_POST, true);
$post = array(
	"message" => $message,
	"access_token" => $C['FBpagetoken']
);
curl_setopt($ch,CURLOPT_POSTFIELDS, http_build_query($post));
curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
$res = curl_exec($ch);
curl_close($ch);

$res = json_decode($res, true);
if (isset($res["error"])) {
	WriteLog("[fbpos][error] res=".json_encode($res));
} else {
	WriteLog("[fbpos][info] success");
	$sth = $G["db"]->prepare("UPDATE `{$C['DBTBprefix']}news` SET `fbpost` = '1' WHERE `hash` = :hash");
	foreach ($row as $temp) {
		$sth->bindValue(":hash", $temp["hash"]);
		$sth->execute();
	}
}
