<?php

$C['archive']['archive.org'] = false;
$C['archive']['archive.is'] = true;

$C['FBpageid'] = 'page_id';
$C['FBpagetoken'] = 'page_token';
$C['FBWHtoken'] = 'Webhooks_token';
$C['FBAPI'] = 'https://graph.facebook.com/v2.8/';

$C["DBhost"] = 'localhost';
$C['DBname'] = 'dbname';
$C['DBuser'] = 'user';
$C['DBpass'] = 'pass';
$C['DBTBprefix'] = 'tngs_notification_';

$C['fetch'] = 'http://www.tngs.tn.edu.tw/tngs/board/';

$G["db"] = new PDO ('mysql:host='.$C["DBhost"].';dbname='.$C["DBname"].';charset=utf8', $C["DBuser"], $C["DBpass"]);

$C['/last_limit'] = 5;

$M["nottext"] = "僅接受文字訊息";
$M["notcommand"] = "本粉專由機器人自動運作\n".
	"啟用訊息通知輸入 /start\n".
	"顯示所有命令輸入 /help";
$M["/start"] = "已啟用訊息通知";
$M["/start_too_many_arg"] = "參數個數錯誤\n".
	"此指令不需要參數";
$M["/stop"] = "已停用訊息通知";
$M["/stop_too_many_arg"] = "參數個數錯誤\n".
	"此指令不需要參數";
$M["/help"] = "可用命令\n".
	"/start 啟用訊息通知\n".
	"/stop 停用訊息通知\n".
	"/last 顯示最後一筆通知\n".
	"/last N 顯示最後N筆通知 (N至多".$C['/last_limit'].")\n".
	"/last A B 略過最後A筆，顯示B筆通知 (B至多".$C['/last_limit'].")\n".
	"/link N 顯示編號N的原始連結\n".
	"/archive N 顯示編號N的存檔連結\n".
	"/help 顯示所有命令";
$M["fail"] = "指令失敗";
$M["/last1_arg1_notnum"] = "第1個參數錯誤\n".
	"回傳筆數應為一個整數，例如 /last 3 顯示最後3筆通知";
$M["/last1_arg1_less_than_1"] = "第1個參數錯誤\n".
	"回傳筆數應該大於等於1";
$M["/last1_arg1_limit_exceeded"] = "第1個參數錯誤\n".
	"回傳筆數至多".$C['/last_limit']."筆";
$M["/last2_arg1_notnum"] = "第1個參數錯誤\n".
	"忽略筆數應為一個整數，例如 /last 10 3 忽略最後10筆通知";
$M["/last_arg2_notnum"] = "第2個參數錯誤\n".
	"回傳筆數應為一個整數，例如 /last 10 3 顯示3筆通知";
$M["/last_arg1_less_than_0"] = "第1個參數錯誤\n".
	"忽略筆數應該大於等於0，例如 /last 10 3 忽略最後10筆通知";
$M["/last_arg2_less_than_1"] = "第2個參數錯誤\n".
	"回傳筆數應該大於等於1，例如 /last 10 3 顯示3筆通知";
$M["/last2_arg2_limit_exceeded"] = "第2個參數錯誤\n".
	"回傳筆數至多".$C['/last_limit']."筆";
$M["/last_too_many_arg"] = "參數個數錯誤\n".
	"不使用參數顯示最後一筆通知\n".
	"1個參數設定顯示筆數，例如 /last 3 顯示最後3筆通知\n".
	"2個參數設定忽略及顯示筆數，例如 /last 10 3 略過最後10筆，顯示3筆通知";
$M["/last_no_result"] = "查無任何通知";
$M["/link_arg1_not_given"] = "參數不足\n".
	"此指令必須給出一個參數為通知的編號";
$M["/link_arg1_not_num"] = "第1個參數錯誤\n".
	"編號必須是一個整數";
$M["/link_too_many_arg"] = "參數個數錯誤\n".
	"此指令必須給出一個參數為通知的編號";
$M["/link_not_found"] = "找不到此編號";
$M["/archive_arg1_not_given"] = "參數不足\n".
	"此指令必須給出一個參數為通知的編號";
$M["/archive_arg1_not_num"] = "第1個參數錯誤\n".
	"編號必須是一個整數";
$M["/archive_too_many_arg"] = "參數個數錯誤\n".
	"此指令必須給出一個參數為通知的編號";
$M["/archive_not_found"] = "找不到此編號";
$M["/archive_not_available"] = "沒有提供存檔服務";
$M["wrongcommand"] = "無法辨識命令\n".
	"輸入 /help 取得可用命令";
