<?php
require(__DIR__.'/config/config.php');

if (!in_array(PHP_SAPI, array("cli", "apache2handler"))) {
	exit("No permission");
}
define("EOL", (PHP_SAPI==="apache2handler"?"<br>\n":PHP_EOL));

$sth = $G["db"]->prepare("SELECT * FROM `{$C['DBTBprefix']}news`");
$sth->execute();
$row = $sth->fetchAll(PDO::FETCH_ASSOC);

$old = array();
foreach ($row as $temp) {
	$old[] = $temp["hash"];
}

$html = file_get_contents($C['fetch']);
$html = iconv("BIG5", "UTF-8//IGNORE", $html);
$html = html_entity_decode($html);
$start = strpos($html, "一般訊息");
$html = substr($html, $start);
$html = str_replace(array("\r\n","\t",'<img src=file.png border=0 width=20 alt="有附件">'), "", $html);

$pattern='/<a href=index\.asp\?chid=.*?>(.*?)<\/FONT><BR>.*?<\/a><font size=1 .*?>(.*?)<td.*?><font.*?><a href=\/tngs\/board\/index\.asp\?numid=(\d+?)><font.*?>(.*?)<\/a>.*?<td.*?><font.*?>.*?<td.*?>(\d*?)年(\d*?)月(\d*?)日/';
preg_match_all($pattern, $html ,$match);
$new_cnt = 0;
for ($key=count($match[0])-1; $key >= 0; $key--) {
	$data = array($match[1][$key], $match[2][$key], $match[3][$key], $match[4][$key], $match[5][$key], $match[6][$key], $match[7][$key]);
	echo strip_tags($match[4][$key]);
	$hash = md5(serialize($data));
	if (!in_array($hash, $old)) {
		if ($C['archive']['archive.org']) {
			system("curl -s https://web.archive.org/save/http://www.tngs.tn.edu.tw/tngs/board/index.asp?numid=".$match[3][$key]." > /dev/null 2>&1 &");
			echo " archive.org";
		}
		if ($C['archive']['archive.is']) {
			system("curl -s https://archive.is/submit/ -d 'url=http://www.tngs.tn.edu.tw/tngs/board/index.asp?numid=".$match[3][$key]."&anyway=1 > /dev/null 2>&1 &'");
			echo " archive.is";
		}
		$sth = $G["db"]->prepare("INSERT INTO `{$C['DBTBprefix']}news` (`idx`, `date`, `text`, `department`, `type`, `url`, `hash`) VALUES (:idx, :date, :text, :department, :type, :url, :hash)");
		$sth->bindValue(":idx", $match[3][$key]);
		$sth->bindValue(":date", ($match[5][$key]+1911)."-".$match[6][$key]."-".$match[7][$key]);
		$sth->bindValue(":text", strip_tags($match[4][$key]));
		$sth->bindValue(":department", $match[1][$key]);
		$sth->bindValue(":type", $match[2][$key]);
		$sth->bindValue(":url", "http://www.tngs.tn.edu.tw/tngs/board/index.asp?numid=".$match[3][$key]);
		$sth->bindValue(":hash", $hash);
		$res = $sth->execute();

		$old[] = $hash;
		echo " New".EOL;
		$new_cnt++;
	} else echo " Old".EOL;
}
if ($new_cnt) {
	echo "list archiving";
	if ($C['archive']['archive.org']) {
		system("curl -s https://web.archive.org/save/".$C['fetch']." > /dev/null 2>&1 &");
		echo " archive.org";
	}
	if ($C['archive']['archive.is']) {
		system("curl -s https://archive.is/submit/ -d 'url=".$C['fetch']."&anyway=1' > /dev/null 2>&1 &");
		echo " archive.is";
	}
	echo " done".EOL;
}
