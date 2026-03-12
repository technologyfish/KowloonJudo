<?php

/**
 * Lumen - PHP 内置开发服务器 路由脚本
 *
 * 用法：php -S localhost:8000 server.php
 *
 * 如果请求的是静态文件（CSS / JS / 图片等），直接返回 false 让内置服务器处理；
 * 否则交给 public/index.php（即 Lumen 框架）来路由。
 */

$uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)
);

// 如果请求的是 public 目录下真实存在的静态文件，则直接返回
if ($uri !== '/' && file_exists(__DIR__ . '/public' . $uri)) {
    return false;
}

// 所有其他请求都交给 Lumen 入口文件处理
$_SERVER['SCRIPT_NAME'] = '/index.php';
require_once __DIR__ . '/public/index.php';
