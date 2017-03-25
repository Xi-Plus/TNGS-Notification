<?php
require(__DIR__.'/config/config.php');
if (!in_array(PHP_SAPI, $C["allowsapi"])) {
	exit("No permission");
}

require(__DIR__.'/function/log.php');
require(__DIR__.'/function/curl.php');
require(__DIR__.'/function/sendmessage.php');

$sth = $G["db"]->prepare("SELECT * FROM `{$C['DBTBprefix']}news` WHERE `fbmessage` = 0 ORDER BY `time` ASC");
$sth->execute();
$newss = $sth->fetchAll(PDO::FETCH_ASSOC);
$sth = $G["db"]->prepare("SELECT * FROM `{$C['DBTBprefix']}user` WHERE `fbmessage` = 1");
$sth->execute();
$users = $sth->fetchAll(PDO::FETCH_ASSOC);

$sthmsg = $G["db"]->prepare("INSERT INTO `{$C['DBTBprefix']}msgqueue` (`tmid`, `message`, `time`, `hash`) VALUES (:tmid, :message, :time, :hash)");
$sthok = $G["db"]->prepare("UPDATE `{$C['DBTBprefix']}news` SET `fbmessage` = '1' WHERE `hash` = :hash");
$sthdel = $G["db"]->prepare("DELETE FROM `{$C['DBTBprefix']}news` WHERE `hash` = :hash");
foreach ($newss as $news) {
	if ($news["idx"] == 0) {
		$msg = $news["text"];
	} else {
		$msg = "#".$news["idx"]."\n".date("m/d", strtotime($news["date"]))." ".$news["department"]." ".$news["type"]."：".$news["text"];
	}
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
	if ($news["idx"] == 0) {
		$msg = $news["text"];
		$sthdel->bindValue(":hash", $news["hash"]);
		$res = $sthdel->execute();
		if ($res === false) {
			WriteLog("[fbmsg][error][delnew] hash=".$news["hash"]);
		}
	} else {
		$sthok->bindValue(":hash", $news["hash"]);
		$res = $sthok->execute();
		if ($res === false) {
			WriteLog("[fbmsg][error][updnew] hash=".$news["hash"]);
		}
	}
}

$sth = $G["db"]->prepare("SELECT * FROM `{$C['DBTBprefix']}msgqueue` ORDER BY `time` ASC");
$sth->execute();
$row = $sth->fetchAll(PDO::FETCH_ASSOC);

$sthdel = $G["db"]->prepare("DELETE FROM `{$C['DBTBprefix']}msgqueue` WHERE `hash` = :hash");
$sthread = $G["db"]->prepare("UPDATE `{$C['DBTBprefix']}user` SET `lastread` = :lastread WHERE `tmid` = :tmid");
foreach ($row as $msg) {
	$res = SendMessage($msg["tmid"], $msg["message"]);
	$sthread->bindValue(":lastread", date("Y-m-d H:i:s"));
	$sthread->bindValue(":tmid", $msg["tmid"]);
	$sthread->execute();
	if ($res) {
		$sthdel->bindValue(":hash", $msg["hash"]);
		$res = $sthdel->execute();
		if ($res === false) {
			WriteLog("[fbmsg][error][delque] hash=".$msg["hash"]);
		}
	}
}
