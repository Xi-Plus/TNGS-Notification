<?php
require(__DIR__.'/config/config.php');
require(__DIR__.'/log.php');
require(__DIR__.'/curl.php');
require(__DIR__.'/sendmessage.php');

if (!in_array(PHP_SAPI, array("cli", "apache2handler"))) {
	exit("No permission");
}
define("EOL", (PHP_SAPI==="apache2handler"?"<br>\n":PHP_EOL));

$sth = $G["db"]->prepare("SELECT * FROM `{$C['DBTBprefix']}news` WHERE `fbmessage` = 0 ORDER BY `time` ASC");
$sth->execute();
$newss = $sth->fetchAll(PDO::FETCH_ASSOC);
$sth = $G["db"]->prepare("SELECT * FROM `{$C['DBTBprefix']}user` WHERE `fbmessage` = 1");
$sth->execute();
$users = $sth->fetchAll(PDO::FETCH_ASSOC);

$sthmsg = $G["db"]->prepare("INSERT INTO `{$C['DBTBprefix']}msgqueue` (`tmid`, `message`, `time`, `hash`) VALUES (:tmid, :message, :time, :hash)");
$sthok = $G["db"]->prepare("UPDATE `{$C['DBTBprefix']}news` SET `fbmessage` = '1' WHERE `hash` = :hash");
foreach ($newss as $news) {
	$msg = "#".$news["idx"]."\n".date("m/d", strtotime($news["date"]))." ".$news["department"]." ".$news["type"]."：".$news["text"];
	foreach ($users as $user) {
		$hash = md5(json_encode(array("tmid"=>$user["tmid"], "message"=>$msg, "time"=>$news["time"])));
		$sthmsg->bindValue(":tmid", $user["tmid"]);
		$sthmsg->bindValue(":message", $msg);
		$sthmsg->bindValue(":time", $news["time"]);
		$sthmsg->bindValue(":hash", $hash);
		$res = $sthmsg->execute();
		if ($res === false) {
			WriteLog("[fbmsg][error][insque] tmid=".$user["tmid"]." msg=".$msg);
		}
	}
	$sthok->bindValue(":hash", $news["hash"]);
	$res = $sthok->execute();
	if ($res === false) {
		WriteLog("[fbmsg][error][updnew] hash=".$news["hash"]);
	}
}

$sth = $G["db"]->prepare("SELECT * FROM `{$C['DBTBprefix']}msgqueue` ORDER BY `time` ASC");
$sth->execute();
$row = $sth->fetchAll(PDO::FETCH_ASSOC);
$sth = $G["db"]->prepare("DELETE FROM `{$C['DBTBprefix']}msgqueue` WHERE `hash` = :hash");
foreach ($row as $msg) {
	$res = SendMessage($msg["tmid"], $msg["message"]);
	if ($res) {
		$sth->bindValue(":hash", $msg["hash"]);
		$res = $sth->execute();
		if ($res === false) {
			WriteLog("[fbmsg][error][delque] hash=".$msg["hash"]);
		}
	}
}
