<?php
require(__DIR__.'/config/config.php');
if (!in_array(PHP_SAPI, $C["allowsapi"])) {
	exit("No permission");
}

date_default_timezone_set("Asia/Taipei");
require(__DIR__.'/function/curl.php');
require(__DIR__.'/function/log.php');
require(__DIR__.'/function/sendmessage.php');

$sth = $G["db"]->prepare("SELECT * FROM `{$C['DBTBprefix']}input` ORDER BY `time` ASC");
$res = $sth->execute();
$row = $sth->fetchAll(PDO::FETCH_ASSOC);
foreach ($row as $data) {
	$sth = $G["db"]->prepare("DELETE FROM `{$C['DBTBprefix']}input` WHERE `hash` = :hash");
	$sth->bindValue(":hash", $data["hash"]);
	$res = $sth->execute();
}
function GetTmid() {
	global $C, $G;
	$res = cURL($C['FBAPI']."me/conversations?fields=participants,updated_time&access_token=".$C['FBpagetoken']);
	$updated_time = file_get_contents("data/updated_time.txt");
	$newesttime = $updated_time;
	while (true) {
		if ($res === false) {
			WriteLog("[follow][error][getuid]");
			break;
		}
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
					$res = $sth->execute();
					break;
				}
			}
		}
		$res = cURL($res["paging"]["next"]);
	}
	file_put_contents("data/updated_time.txt", $newesttime);
}
foreach ($row as $data) {
	$input = json_decode($data["input"], true);
	foreach ($input['entry'] as $entry) {
		foreach ($entry['messaging'] as $messaging) {
			$sid = $messaging['sender']['id'];
			$sth = $G["db"]->prepare("SELECT * FROM `{$C['DBTBprefix']}user` WHERE `sid` = :sid");
			$sth->bindValue(":sid", $sid);
			$sth->execute();
			$row = $sth->fetch(PDO::FETCH_ASSOC);
			if ($row === false) {
				GetTmid();
				$mmid = "m_".$messaging['message']['mid'];
				$res = cURL($C['FBAPI'].$mmid."?fields=from&access_token=".$C['FBpagetoken']);
				$res = json_decode($res, true);
				$uid = $res["from"]["id"];
				$sthsid = $G["db"]->prepare("UPDATE `{$C['DBTBprefix']}user` SET `sid` = :sid WHERE `uid` = :uid");
				$sthsid->bindValue(":sid", $sid);
				$sthsid->bindValue(":uid", $uid);
				$sthsid->execute();

				$sth->execute();
				$row = $sth->fetch(PDO::FETCH_ASSOC);
				if ($row === false) {
					WriteLog("[follow][error][uid404] sid=".$sid);
					continue;
				} else {
					WriteLog("[follow][info][newuser] sid=".$sid." uid=".$uid);
				}
			}
			$tmid = $row["tmid"];
			$sth = $G["db"]->prepare("UPDATE `{$C['DBTBprefix']}user` SET `lastread` = :lastread WHERE `tmid` = :tmid");
			$sth->bindValue(":lastread", "2038-01-19 03:04:17");
			$sth->bindValue(":tmid", $tmid);
			$res = $sth->execute();
			if ($res === false) {
				WriteLog("[follow][error][updlr] tmid=".$tmid);
			}
			if (isset($messaging['read'])) {
				continue;
			}
			if (isset($messaging['message']['sticker_id'])) {
				SendMessage($tmid, "感謝您的支持");
				continue;
			}
			if (!isset($messaging['message']['text'])) {
				SendMessage($tmid, "僅接受文字訊息");
				continue;
			}
			$msg = $messaging['message']['text'];
			if ($msg[0] === "/") {
				SendMessage($tmid, "提醒您，命令已經不需要再加上 / 前綴，而且將在不久後不支援包含斜線的命令");
			}
			$msg = str_replace("\n", " ", $msg);
			$msg = preg_replace("/\s+/", " ", $msg);
			$cmd = explode(" ", $msg);
			$cmd[0] = strtolower($cmd[0]);
			switch ($cmd[0]) {
				case 'start':
				case '/start':
					if (isset($cmd[1])) {
						SendMessage($tmid, "參數個數錯誤\n".
							"此命令不需要參數");
						continue;
					}
					$sth = $G["db"]->prepare("UPDATE `{$C['DBTBprefix']}user` SET `fbmessage` = '1' WHERE `tmid` = :tmid");
					$sth->bindValue(":tmid", $tmid);
					$res = $sth->execute();
					if ($res) {
						SendMessage($tmid, "已啟用訊息通知\n".
							"欲取消請輸入 stop");
					} else {
						WriteLog("[follow][error][start][upduse] uid=".$uid);
						SendMessage($tmid, "命令失敗");
					}
					break;
				
				case 'stop':
				case '/stop':
					if (isset($cmd[1])) {
						SendMessage($tmid, "參數個數錯誤\n".
							"此命令不需要參數");
						continue;
					}
					$sth = $G["db"]->prepare("UPDATE `{$C['DBTBprefix']}user` SET `fbmessage` = '0' WHERE `tmid` = :tmid");
					$sth->bindValue(":tmid", $tmid);
					$res = $sth->execute();
					if ($res) {
						SendMessage($tmid, "已停用訊息通知\n".
							"欲重新啟用請輸入 start");
					} else {
						WriteLog("[follow][error][stop][upduse] uid=".$uid);
						SendMessage($tmid, "命令失敗");
					}
					break;
				
				case 'last':
				case '/last':
					$a = 0;
					$b = 1;
					if (isset($cmd[1]) && !isset($cmd[2])) {
						if (!ctype_digit($cmd[1])) {
							SendMessage($tmid, "第1個參數錯誤\n".
								"回傳筆數應為一個整數，例如 last 3 顯示最後3筆通知");
							continue;
						}
						$b = (int)$cmd[1];
						if ($b < 1) {
							SendMessage($tmid, "第1個參數錯誤\n".
								"回傳筆數應該大於等於1");
							continue;
						}
						if ($b > $C['last_limit']) {
							SendMessage($tmid, "第1個參數錯誤\n".
								"回傳筆數至多".$C['last_limit']."筆");
							continue;
						}
					}
					if (isset($cmd[2])) {
						if (!ctype_digit($cmd[1])) {
							SendMessage($tmid, "第1個參數錯誤\n".
								"忽略筆數應為一個整數，例如 last 10 3 忽略最後10筆通知");
							continue;
						}
						if (!ctype_digit($cmd[2])) {
							SendMessage($tmid, "第2個參數錯誤\n".
								"回傳筆數應為一個整數，例如 last 10 3 顯示3筆通知");
							continue;
						}
						$a = (int)$cmd[1];
						if ($a < 0) {
							SendMessage($tmid, "第1個參數錯誤\n".
								"忽略筆數應該大於等於0，例如 last 10 3 忽略最後10筆通知");
							continue;
						}
						$b = (int)$cmd[2];
						if ($b < 1) {
							SendMessage($tmid, "第2個參數錯誤\n".
								"回傳筆數應該大於等於1，例如 last 10 3 顯示3筆通知");
							continue;
						}
						if ($b > $C['last_limit']) {
							SendMessage($tmid, "第2個參數錯誤\n".
								"回傳筆數至多".$C['last_limit']."筆");
							continue;
						}
					}
					if (isset($cmd[3])) {
						SendMessage($tmid, "參數個數錯誤\n".
							"不使用參數顯示最後一筆通知\n".
							"1個參數設定顯示筆數，例如 last 3 顯示最後3筆通知\n".
							"2個參數設定忽略及顯示筆數，例如 last 10 3 略過最後10筆，顯示3筆通知");
						continue;
					}
					$sth = $G["db"]->prepare("SELECT * FROM `{$C['DBTBprefix']}news` ORDER BY `time` DESC LIMIT {$a},{$b}");
					$res = $sth->execute();
					$row = $sth->fetchAll(PDO::FETCH_ASSOC);
					if ($res) {
						if (count($row) == 0) {
							SendMessage($tmid, "查無任何通知");
						} else {
							if ($a == 0) {
								SendMessage($tmid, "顯示最後".$b."筆訊息");
							} else {
								SendMessage($tmid, "忽略最後".$a."筆，顯示".$b."筆訊息");
							}
							foreach (array_reverse($row) as $temp) {
								$msg = $C['Pagename']." #".$temp["idx"]."\n".date("m/d", strtotime($temp["date"]))." ".$temp["department"]." ".$temp["type"]."：".$temp["text"];
								SendMessage($tmid, $msg);
							}
							SendMessage($tmid, "顯示更舊".$b."筆輸入 last ".($a+$b)." ".$b);
						}
					} else {
						WriteLog("[follow][error][last][selnew] uid=".$uid);
						SendMessage($tmid, "命令失敗");
					}
					break;

				case 'link':
				case '/link':
					if (isset($cmd[1])) {
						if (preg_match("/^\d+$/", $cmd[1]) == 0) {
							SendMessage($tmid, "第1個參數錯誤\n".
								"必須是正整數為通知的編號");
							continue;
						}
						$n = (int)$cmd[1];
						if ($n == 0) {
							SendMessage($tmid, "第1個參數錯誤\n".
								"必須是正整數為通知的編號");
							continue;
						}
						$sth = $G["db"]->prepare("SELECT * FROM `{$C['DBTBprefix']}news` WHERE `idx` = :idx");
						$sth->bindValue(":idx", $n);
					} else {
						$sth = $G["db"]->prepare("SELECT * FROM `{$C['DBTBprefix']}news` ORDER BY `time` DESC LIMIT 1");
					}
					$res = $sth->execute();
					$news = $sth->fetch(PDO::FETCH_ASSOC);
					if ($res) {
						if ($news === false) {
							SendMessage($tmid, "找不到此編號");
						} else {
							$msg = $C['Pagename']." #".$news["idx"]."\n".$news["url"];
							SendMessage($tmid, $msg);
						}
					} else {
						WriteLog("[follow][error][start][selnew] uid=".$uid);
						SendMessage($tmid, "命令失敗");
					}
					break;

				case 'archive':
				case '/archive':
					if (!isset($cmd[1])) {
						SendMessage($tmid, "參數不足\n".
							"此命令必須給出一個參數為通知的編號");
						continue;
					}
					if (isset($cmd[2])) {
						SendMessage($tmid, "參數個數錯誤\n".
							"此命令必須給出一個參數為通知的編號");
						continue;
					}
					if (!ctype_digit($cmd[1])) {
						SendMessage($tmid, "第1個參數錯誤\n".
							"編號必須是一個整數");
						continue;
					}
					$idx = (int)$cmd[1];
					$sth = $G["db"]->prepare("SELECT * FROM `{$C['DBTBprefix']}news` WHERE `idx` = :idx ORDER BY `time` DESC");
					$sth->bindValue(":idx", $idx);
					$res = $sth->execute();
					$news = $sth->fetch(PDO::FETCH_ASSOC);
					if ($res) {
						if ($news === false) {
							SendMessage($tmid, "找不到此編號");
						} else {
							$msg = $C['Pagename']." #".$idx."\n";
							$isarchive = false;
							if ($C['archive']['archive.org']) {
								$msg .= "http://web.archive.org/web/*/".$news["url"]."\n\n";
								$isarchive = true;
							}
							if ($C['archive']['archive.is']) {
								$msg .= "http://archive.is/search/?q=".urlencode($news["url"])."\n\n";
								$isarchive = true;
							}
							if ($isarchive) {
								SendMessage($tmid, $msg);
							} else {
								SendMessage($tmid, "沒有提供存檔服務");
							}
						}
					} else {
						WriteLog("[follow][error][archive][selnew] uid=".$uid);
						SendMessage($tmid, "命令失敗");
					}
					break;

				case 'show':
				case '/show':
					if (!isset($cmd[1])) {
						SendMessage($tmid, "參數不足\n".
							"此命令必須給出一個參數為通知的編號");
						continue;
					}
					if (isset($cmd[2])) {
						SendMessage($tmid, "參數個數錯誤\n".
							"此命令必須給出一個參數為通知的編號");
						continue;
					}
					if (!ctype_digit($cmd[1])) {
						SendMessage($tmid, "第1個參數錯誤\n".
							"編號必須是一個整數");
						continue;
					}
					$idx = (int)$cmd[1];
					$sth = $G["db"]->prepare("SELECT * FROM `{$C['DBTBprefix']}news` WHERE `idx` = :idx ORDER BY `time` DESC");
					$sth->bindValue(":idx", $idx);
					$res = $sth->execute();
					$news = $sth->fetch(PDO::FETCH_ASSOC);
					if ($res) {
						if ($news === false) {
							SendMessage($tmid, "找不到此編號");
						} else {
							$content = file_get_contents($news["url"]);
							if ($content === false) {
								SendMessage($tmid, "抓取網頁失敗，請稍後再試一次\n".
									"請直接自行點選連結查看\n".
									$news["url"]);
								continue;
							}
							$content = iconv("Big5", "UTF-8//IGNORE", $content);
							$content = str_replace("\r\n", "\n", $content);
							$p1 = strpos($content, "<center><hr width=95%></center><font color=#005555>");
							$p2 = strpos($content, "<center><hr width=99%>");
							if ($p1 !== false && $p2 !== false) {
								$content = substr($content, $p1, $p2-$p1);
								$content = html_entity_decode($content);
								$content = str_replace(array("<br>", "<BR>", "<p>", "<P>"), "\n", $content);
								$content = strip_tags($content);
								$content = preg_replace("/\n{3,}/", "\n\n", $content);
								$content = preg_replace("/^\n+/", "", $content);
								$content = preg_replace("/\n+$/", "", $content);
								SendMessage($tmid, $C['Pagename']." #".$news["idx"]."\n".
									date("m/d", strtotime($news["date"]))." ".$news["department"]." ".$news["type"]."：".$news["text"]."\n".
									"----------------------------------------\n".
									$content);
							} else {
								SendMessage($tmid, "解析網頁失敗，請將此問題報告到 Messenger @xiplus.dev\n\n".
									"請直接自行點選連結查看\n".
									$news["url"]);
							}
						}
					} else {
						WriteLog("[follow][error][show][selnew] uid=".$uid);
						SendMessage($tmid, "命令失敗");
					}
					break;

				case 'report':
				case '/report':
					SendMessage($tmid, "請將錯誤報告及意見回饋傳送至 https://m.me/xiplus.dev");
					break;
				
				case 'tg':
				case '/tg':
				case 'telegram':
				case '/telegram':
					SendMessage($tmid, "Telegram頻道連結： https://t.me/TNGS_Notification");
					break;
				
				case 'help':
				case '/help':
					if (isset($cmd[2])) {
						$msg = "參數過多\n".
							"必須給出一個參數為命令的名稱";
					} else if (isset($cmd[1])) {
						switch ($cmd[1]) {
							case 'start':
								$msg = "start 啟用訊息通知";
								break;
							
							case 'stop':
								$msg = "stop 停用訊息通知";
								break;
							
							case 'last':
								$msg = "last 顯示最後一筆通知\n".
									"last [count] 顯示最後[count]筆通知\n".
									"last [offset] [count] 略過最後[offset]筆，顯示[count]筆通知\n".
									"([count]至多".$C['last_limit'].")\n\n".
									"範例：\n".
									"last 顯示最後一筆\n".
									"last 3 顯示最後3筆\n".
									"last 5 3 顯示最後6~8筆";
								break;
							
							case 'link':
								$msg = "link 顯示最後一筆通知的連結\n".
									 "link [id] 顯示編號[id]的連結\n\n".
									"範例：\n".
									"link 顯示最後一筆\n".
									"link 12345 顯示編號1234";
								break;
							
							case 'archive':
								$msg = "archive [id] 顯示編號[id]的存檔連結\n\n".
									"範例：\n".
									"archive 12345 顯示編號12345";
								break;
							
							case 'show':
								$msg = "show [id] 顯示編號[id]的內文\n\n".
									"範例：\n".
									"show 12345 顯示編號12345";
								break;
							
							case 'help':
								$msg = "help 顯示所有命令";
								break;
							
							default:
								$msg = "查無此命令";
								break;
						}
					} else {
						$msg = "可用命令\n".
						"tg 取得Telegram頻道連結\n".
						"start 啟用訊息通知\n".
						"stop 停用訊息通知\n".
						"last 顯示舊通知\n".
						"link 顯示通知的連結\n".
						"archive 顯示通知的存檔連結\n".
						"show 顯示通知的內文\n".
						"report 錯誤報告或回饋意見\n".
						"help 顯示所有命令\n\n".
						"help [命令] 顯示命令的詳細用法\n".
						"範例： help link";
					}
					SendMessage($tmid, $msg);
					break;
				
				default:
					SendMessage($tmid, "無法辨識的命令\n".
						"本粉專由機器人自動運作\n".
						"啟用訊息通知輸入 start\n".
						"顯示所有命令輸入 help\n".
						"錯誤報告或回饋意見輸入 report");
					break;
			}
		}
	}
}
