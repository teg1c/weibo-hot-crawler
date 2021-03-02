#!/usr/bin/env php
<?php

use GuzzleHttp\Client;
use QL\QueryList;

date_default_timezone_set('PRC');
ini_set('date.timezone', 'Asia/Shanghai');
require_once __DIR__ . '/vendor/autoload.php';
$url = "https://s.weibo.com/top/summary";
$regular = '<a href="(\/weibo\?q=[^"]+)".*?>(.+)<\/a>';
$time = date('Y-m-d H:i:s');
$readMeHead = <<< Eof
# Weibo Hot Crawler \n
\n
微博热榜爬虫，利用 Github Action 的调度脚本更新 BY PHP \n\n
## 微博今日热榜 更新于 {$time} \n
Eof;
$client = new Client([
    'timeout' => 10
]);
$response = $client->get($url);
$html = $response->getBody()->getContents();

$table = QueryList::html($html)->find('table:eq(0)');
$result = $table->find('tr:gt(1)')->map(function ($row) use (&$readMeHead) {
    $regular = '@<a href="(\/weibo\?q=[^"]+)".*?>(.+)<\/a>@';
    preg_match($regular, $row->find('td')->html(), $matches);
    if (empty($matches)) {
        return [];
    }
    $readMeHead .= "1. [" . $matches[2] . "](https://s.weibo.com/" . $matches[1] . ")\n\n";
    return [
        'title' => $matches[2],
        'url' => $matches[1],
    ];
})->filter()->values();
$content = $result->all();
file_put_contents("README.MD", $readMeHead);

