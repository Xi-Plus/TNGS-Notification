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

$C['/last_limit'] = 20;
$C['/link_limit'] = 5;
