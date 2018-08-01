<?php
require(__DIR__.'/config/config.php');
if (!in_array(PHP_SAPI, $C["allowsapi"])) {
	exit("No permission");
}

require(__DIR__.'/function/log.php');
require(__DIR__.'/function/curl.php');
require(__DIR__.'/function/sendmessage.php');

$sth = $G["db"]->prepare("SELECT * FROM `{$C['DBTBprefix']}news` WHERE `tgmessage` = 0 ORDER BY `time` ASC");
$sth->execute();
$row = $sth->fetchAll(PDO::FETCH_ASSOC);

if (count($row) == 0) {
	exit("No new\n");
}

foreach ($row as $news) {
	$msg = $C['PagenameTG']." #".$news["idx"]."\n".date("m/d", strtotime($news["date"]))." ".$news["department"]."：".$news["text"]."\n".$news["url"];

	$res = SendTGMessage($C['TGchatid'], $msg);
	if ($res === true) {
		$sth = $G["db"]->prepare("UPDATE `{$C['DBTBprefix']}news` SET `tgmessage` = '1' WHERE `hash` = :hash");
		$sth->bindValue(":hash", $news["hash"]);
		$sth->execute();
	}
}
