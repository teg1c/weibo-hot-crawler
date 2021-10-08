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
    'timeout' => 10,
    'allow_redirects' => true,
]);
$response = $client->get($url, [
        'headers'=>[
                'User-Agent'=>'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/92.0.4515.159 Safari/537.36',
            'Cookie'=>'SUB=_2AkMWPIzuf8NxqwJRmPgSxGrlZI1xwwvEieKgYH01JRMxHRl-yT8Xqn0HtRB6PbyiAY7W0wZkwFc1nXHJxUddZr9bpaPQ; SUBP=0033WrSXqPxfM72-Ws9jqgMF55529P9D9W5p6Ee5xHahOckML_sA0l2c; _s_tentry=passport.weibo.com; Apache=6467906874370.302.1633682394000; SINAGLOBAL=6467906874370.302.1633682394000; ULV=1633682394006:1:1:1:6467906874370.302.1633682394000:; WBStorage=6ff1c79b|undefined'
        ]
]);
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
