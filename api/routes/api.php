<?php

/** @var \Laravel\Lumen\Routing\Router $router */

// 健康检查
$router->get('/', function () {
    return response()->json(['message' => 'KowloonJudo API', 'version' => '1.0.0']);
});

/*
|--------------------------------------------------------------------------
| 公开接口（无需认证）
|--------------------------------------------------------------------------
*/
$router->group(['prefix' => 'api'], function () use ($router) {

    // 小程序 - 微信登录
    $router->post('/auth/wx-login', 'Auth\WxAuthController@login');

    // 管理端 - 账号登录
    $router->post('/admin/login', 'Admin\AdminAuthController@login');

    // 微信支付回调（微信服务器直接回调，无需 JWT）
    $router->post('/competition/pay/notify', 'CompetitionController@payNotify');

    // 小程序获取最新比赛规则（公开）
    $router->get('/competition/rule', 'CompetitionController@getLatestRule');

    // 小程序获取最新公告（公开）
    $router->get('/announcement/latest', 'AnnouncementController@latest');

    // 费用设置（公开，前端需读取）
    $router->get('/settings/fees', 'Admin\FeeSettingController@publicIndex');
});

/*
|--------------------------------------------------------------------------
| 小程序端接口（需要用户 JWT）
|--------------------------------------------------------------------------
*/
$router->group(['prefix' => 'api', 'middleware' => 'jwt'], function () use ($router) {

    // 文件上传
    $router->post('/upload/avatar', 'UploadController@avatar');

    // 用户信息
    $router->get('/user/info',  'UserController@info');
    $router->post('/user/info', 'UserController@update');

    // 报名 & 支付
    $router->post('/competition/register',           'CompetitionController@register');
    $router->post('/competition/pay/create',         'CompetitionController@createPayOrder');
    $router->get('/competition/pay/query',            'CompetitionController@queryPayResult');
    $router->post('/competition/cancel',             'CompetitionController@cancelOrder');
    $router->post('/competition/refund',             'CompetitionController@requestRefund');
    $router->get('/competition/orders',              'CompetitionController@myOrders');
    $router->get('/competition/order-detail',        'CompetitionController@orderDetail');
});

/*
|--------------------------------------------------------------------------
| 管理端接口（需要管理员 JWT）
|--------------------------------------------------------------------------
*/
$router->group(['prefix' => 'api/admin', 'middleware' => 'admin-jwt'], function () use ($router) {

    // 管理员身份
    $router->get('/profile', 'Admin\AdminAuthController@profile');
    $router->post('/logout', 'Admin\AdminAuthController@logout');

    // 图片上传
    $router->post('/upload', 'UploadController@image');

    // 仪表盘统计
    $router->get('/dashboard/stats', 'Admin\DashboardController@stats');

    // 用户管理
    $router->get('/users',         'Admin\UserManageController@index');
    $router->get('/users/{id}',    'Admin\UserManageController@show');
    $router->put('/users/{id}',    'Admin\UserManageController@update');
    $router->delete('/users/{id}', 'Admin\UserManageController@destroy');

    // 比赛规则 CRUD
    $router->get('/competition/rules',       'Admin\CompetitionRuleController@ruleIndex');
    $router->get('/competition/rules/{id}',  'Admin\CompetitionRuleController@ruleShow');
    $router->post('/competition/rules',      'Admin\CompetitionRuleController@ruleStore');
    $router->put('/competition/rules/{id}',  'Admin\CompetitionRuleController@ruleUpdate');
    $router->delete('/competition/rules/{id}','Admin\CompetitionRuleController@ruleDestroy');

    // 报名记录
    $router->get('/competition/registrations',               'Admin\CompetitionRuleController@registrationIndex');
    $router->get('/competition/registrations/export',        'Admin\CompetitionRuleController@registrationExport');
    $router->put('/competition/registrations/{id}/confirm',  'Admin\CompetitionRuleController@registrationConfirm');
    $router->put('/competition/registrations/batch-confirm', 'Admin\CompetitionRuleController@registrationBatchConfirm');
    $router->put('/competition/registrations/{id}/refund',   'Admin\CompetitionRuleController@registrationRefund');
    $router->put('/competition/registrations/{id}',          'Admin\CompetitionRuleController@registrationUpdate');
    $router->delete('/competition/registrations/{id}',       'Admin\CompetitionRuleController@registrationDestroy');

    // 费用设置
    $router->get('/settings/fees',  'Admin\FeeSettingController@index');
    $router->put('/settings/fees',  'Admin\FeeSettingController@update');

    // 公告管理 CRUD
    $router->get('/announcements',        'Admin\AnnouncementController@index');
    $router->get('/announcements/{id}',   'Admin\AnnouncementController@show');
    $router->post('/announcements',       'Admin\AnnouncementController@store');
    $router->put('/announcements/{id}',   'Admin\AnnouncementController@update');
    $router->delete('/announcements/{id}','Admin\AnnouncementController@destroy');
});
