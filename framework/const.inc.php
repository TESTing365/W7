<?php
/**
 * 验证规则.
 *
 * [WeEngine System] Copyright (c) 2014 W7.CC
 */
defined('IN_IA') or exit('Access Denied');

!defined('CLOUD_API_DOMAIN') && define('CLOUD_API_DOMAIN', 'https://openapi.w7.cc');
define('REGULAR_EMAIL', '/\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*/i');
define('REGULAR_MOBILE', '/1[3456789][0-9]{9}/');
define('REGULAR_USERNAME', '/^[\x{4e00}-\x{9fa5}a-z\d_\.]{1,30}$/iu');
/*
 * 模板引用相关
 */
//导入全局变量，并直接显示模板页内容。
define('TEMPLATE_DISPLAY', 0);
//导入全局变量，并返回模板页内容的字符串
define('TEMPLATE_FETCH', 1);
//返回模板编译文件的包含路径
define('TEMPLATE_INCLUDEPATH', 2);

//订阅号
define('ACCOUNT_SUBSCRIPTION', 1);
//订阅号-认证
define('ACCOUNT_SUBSCRIPTION_VERIFY', 3);
//服务号
define('ACCOUNT_SERVICE', 2);
//服务号-认证 认证媒体/政府订阅号
define('ACCOUNT_SERVICE_VERIFY', 4);
//正常接入公众号
define('ACCOUNT_TYPE_OFFCIAL_NORMAL', 1);
//授权接入公众号
define('ACCOUNT_TYPE_OFFCIAL_AUTH', 3);
//正常接入小程序
define('ACCOUNT_TYPE_APP_NORMAL', 4);
//正常接入PC
define('ACCOUNT_TYPE_WEBAPP_NORMAL', 5);
//正常接入APP
define('ACCOUNT_TYPE_PHONEAPP_NORMAL', 6);
//授权接入小程序
define('ACCOUNT_TYPE_APP_AUTH', 7);
//正常接入企业小程序
define('ACCOUNT_TYPE_WXAPP_WORK', 8);

//支付宝小程序
define('ACCOUNT_TYPE_ALIAPP_NORMAL', 11);
//百度小程序
define('ACCOUNT_TYPE_BAIDUAPP_NORMAL', 12);
//字节跳动小程序
define('ACCOUNT_TYPE_TOUTIAOAPP_NORMAL', 13);

//公众号
define('ACCOUNT_TYPE_SIGN', 'account');
//小程序
define('WXAPP_TYPE_SIGN', 'wxapp');
//PC
define('WEBAPP_TYPE_SIGN', 'webapp');
//APP
define('PHONEAPP_TYPE_SIGN', 'phoneapp');
//欢迎页
define('WELCOMESYSTEM_TYPE_SIGN', 'welcome');

//支付宝小程序
define('ALIAPP_TYPE_SIGN', 'aliapp');
//百度小程序
define('BAIDUAPP_TYPE_SIGN', 'baiduapp');
//字节跳动小程序
define('TOUTIAOAPP_TYPE_SIGN', 'toutiaoapp');

//授权登录接入
define('ACCOUNT_OAUTH_LOGIN', 3);
//api接入
define('ACCOUNT_NORMAL_LOGIN', 1);

//系统线上操作
define('ACCOUNT_OPERATE_ONLINE', 1);
//管理员操作
define('ACCOUNT_OPERATE_MANAGER', 2);
//店员操作
define('ACCOUNT_OPERATE_CLERK', 3);

//店员
define('ACCOUNT_MANAGE_NAME_CLERK', 'clerk');
//操作员
define('ACCOUNT_MANAGE_TYPE_OPERATOR', 1);
define('ACCOUNT_MANAGE_NAME_OPERATOR', 'operator');
//管理员
define('ACCOUNT_MANAGE_TYPE_MANAGER', 2);
define('ACCOUNT_MANAGE_NAME_MANAGER', 'manager');
//所有者
define('ACCOUNT_MANAGE_TYPE_OWNER', 3);
define('ACCOUNT_MANAGE_NAME_OWNER', 'owner');
//创始人
define('ACCOUNT_MANAGE_NAME_FOUNDER', 'founder');
define('ACCOUNT_MANAGE_GROUP_FOUNDER', 1);
//副创始人
define('ACCOUNT_MANAGE_TYPE_VICE_FOUNDER', 4);
define('ACCOUNT_MANAGE_NAME_VICE_FOUNDER', 'vice_founder');
define('ACCOUNT_MANAGE_GROUP_VICE_FOUNDER', 2);
//普通用户
define('ACCOUNT_MANAGE_GROUP_GENERAL', 0);
//未绑定用户
define('ACCOUNT_MANAGE_NAME_UNBIND_USER', 'unbind_user');
//admin创建用户，用户组 owner_uid=0
define('ACCOUNT_NO_OWNER_UID', 0);
//到期用户
define('ACCOUNT_MANAGE_NAME_EXPIRED', 'expired');
//普通用户角色
define('ACCOUNT_MANAGE_NAME_GENERAL_RULE', 'role1');
//副创始人用户角色
define('ACCOUNT_MANAGE_NAME_VICE_FOUNDER_RULE', 'role2');

//系统卡券
define('SYSTEM_COUPON', 1);
//微信卡券
define('WECHAT_COUPON', 2);
//卡券类型
define('COUPON_TYPE_DISCOUNT', '1'); //折扣券
define('COUPON_TYPE_CASH', '2'); //代金券
define('COUPON_TYPE_GROUPON', '3'); //团购券
define('COUPON_TYPE_GIFT', '4'); //礼品券
define('COUPON_TYPE_GENERAL', '5'); //优惠券
define('COUPON_TYPE_MEMBER', '6'); //会员卡
define('COUPON_TYPE_SCENIC', '7'); //景点票
define('COUPON_TYPE_MOVIE', '8'); //电影票
define('COUPON_TYPE_BOARDINGPASS', '9'); //飞机票
define('COUPON_TYPE_MEETING', '10'); //会议票
define('COUPON_TYPE_BUS', '11'); //汽车票

define('ATTACH_FTP', 1); //远程附件类型：ftp
define('ATTACH_OSS', 2); //远程附件类型：阿里云
define('ATTACH_QINIU', 3); //远程附件类型：七牛
define('ATTACH_COS', 4); //远程附件类型：腾讯云对象存储
define('ATTACH_TCB', 5); //远程附件类型：腾讯云托管对象存储

define('ATTACH_TYPE_IMAGE', 1);
define('ATTACH_TYPE_VOICE', 2);
define('ATTACH_TYPE_VEDIO', 3);
define('ATTACH_TYPE_NEWS', 4);

define('ATTACHMENT_IMAGE', 'image');

define('ATTACH_SAVE_TYPE_FIXED', 1);
define('ATTACH_SAVE_TYPE_TEMP', 2);

define('STATUS_OFF', 0); //关闭状态
define('STATUS_ON', 1); //开启状态
define('STATUS_SUCCESS', 0); //ajax返回成功状态，增强语义

define('CACHE_EXPIRE_SHORT', 30);
define('CACHE_EXPIRE_MIDDLE', 300);
define('CACHE_EXPIRE_LONG', 3600);
define('CACHE_KEY_LENGTH', 100); //缓存键的最大长度

//模块类型:模块，模板
define('APPLICATION_TYPE_MODULE', 1);
define('APPLICATION_TYPE_TEMPLATES', 2);
//系统模块
define('MODULE_SYSTEM', 1);
define('MODULE_NONSYSTEM', 0);
//非系统模块
//模块是否支持微信小程序
define('MODULE_SUPPORT_WXAPP', 2);
define('MODULE_NONSUPPORT_WXAPP', 1);
//模块是否支持公众号应用
define('MODULE_SUPPORT_ACCOUNT', 2);
define('MODULE_NONSUPPORT_ACCOUNT', 1);
//是否支持pc 1不支持  2支持
define('MODULE_NOSUPPORT_WEBAPP', 1);
define('MODULE_SUPPORT_WEBAPP', 2);
//是否支持app 1不支持  2支持
define('MODULE_NOSUPPORT_PHONEAPP', 1);
define('MODULE_SUPPORT_PHONEAPP', 2);
//是否支持系统首页 1不支持  2支持
define('MODULE_SUPPORT_SYSTEMWELCOME', 2);
define('MODULE_NONSUPPORT_SYSTEMWELCOME', 1);
//是否支持安卓 不支持1 支持2
define('MODULE_NOSUPPORT_ANDROID', 1);
define('MODULE_SUPPORT_ANDROID', 2);
//是否支持ios 不支持1 支持2
define('MODULE_NOSUPPORT_IOS', 1);
define('MODULE_SUPPORT_IOS', 2);
// 是否支持支付宝小程序 不支持1 支持2
define('MODULE_SUPPORT_ALIAPP', 2);
define('MODULE_NOSUPPORT_ALIAPP', 1);
// 是否支持百度小程序 不支持1 支持2
define('MODULE_SUPPORT_BAIDUAPP', 2);
define('MODULE_NOSUPPORT_BAIDUAPP', 1);
// 是否支持字节跳动小程序 不支持1 支持2
define('MODULE_SUPPORT_TOUTIAOAPP', 2);
define('MODULE_NOSUPPORT_TOUTIAOAPP', 1);

define('MODULE_SUPPORT_WXAPP_NAME', 'wxapp_support');
define('MODULE_SUPPORT_ACCOUNT_NAME', 'account_support');
define('MODULE_SUPPORT_WEBAPP_NAME', 'webapp_support');
define('MODULE_SUPPORT_PHONEAPP_NAME', 'phoneapp_support');
define('MODULE_SUPPORT_SYSTEMWELCOME_NAME', 'welcome_support');
define('MODULE_SUPPORT_ALIAPP_NAME', 'aliapp_support');
define('MODULE_SUPPORT_BAIDUAPP_NAME', 'baiduapp_support');
define('MODULE_SUPPORT_TOUTIAOAPP_NAME', 'toutiaoapp_support');

//模块安装来源
//本地安装
define('MODULE_LOCAL_INSTALL', '1');
//本地未安装
define('MODULE_LOCAL_UNINSTALL', '2');
//线上安装
define('MODULE_CLOUD_INSTALL', '3');
//线上未安装
define('MODULE_CLOUD_UNINSTALL', '4');
//线上未安装-正常
define('MODULE_CLOUD_UNINSTALL_NORMAL', '1');
//线上未安装-检测无废弃表
define('MODULE_CLOUD_UNINSTALL_CHECKED', '2');
//线上未安装-检测忽略
define('MODULE_CLOUD_UNINSTALL_IGNORE', '3');
//模块卸载类型
//停用已安装
define('MODULE_RECYCLE_INSTALL_DISABLED', '1');
//忽略未安装
define('MODULE_RECYCLE_UNINSTALL_IGNORE', '2');

//权限类型
define('PERMISSION_ACCOUNT', 'system');
define('PERMISSION_WXAPP', 'wxapp');
define('PERMISSION_WEBAPP', 'webapp');
define('PERMISSION_PHONEAPP', 'phoneapp');
define('PERMISSION_ALIAPP', 'aliapp');
define('PERMISSION_BAIDUAPP', 'baiduapp');
define('PERMISSION_TOUTIAOAPP', 'toutiaoapp');
define('PERMISSION_SYSTEM', 'site');
define('PERMISSION_MODULES', 'modules');

//微信支付类型
define('PAYMENT_WECHAT_TYPE_NORMAL', 1); //微信支付
define('PAYMENT_WECHAT_TYPE_BORROW', 2); //借用支付
define('PAYMENT_WECHAT_TYPE_SERVICE', 3); //服务商支付
define('PAYMENT_WECHAT_TYPE_CLOSE', 4);

//平台给粉丝发消息的类型
define('FANS_CHATS_FROM_SYSTEM', 1);

//小程序数据常规分析常量
define('WXAPP_STATISTICS_DAILYVISITTREND', 2);
//DIY小程序
define('WXAPP_DIY', 1);
//选择模版小程序
define('WXAPP_TEMPLATE', 2);
//跳转模块小程序
define('WXAPP_MODULE', 3);
//网页小程序类型
define('WXAPP_CREATE_MODULE', 1);
// 打包多个模块小程序
define('WXAPP_CREATE_MUTI_MODULE', 2);
//普通小程序类型
define('WXAPP_CREATE_DEFAULT', 0);

define('MATERIAL_LOCAL', 'local'); //服务器素材类型
define('MATERIAL_WEXIN', 'perm'); //微信素材类型

//自定义菜单之默认菜单
define('MENU_CURRENTSELF', 1);
//自定义菜单之默认菜单的历史记录
define('MENU_HISTORY', 2);
//自定义菜单之个性化菜单
define('MENU_CONDITIONAL', 3);

//用户状态
//注册审核用户
define('USER_STATUS_CHECK', 1);
//正常用户
define('USER_STATUS_NORMAL', 2);
//禁用用户
define('USER_STATUS_BAN', 3);

//用户类型
//普通用户
define('USER_TYPE_COMMON', 1);
//应用操作员
define('USER_TYPE_CLERK', 3);

//我的账户链接类型
define('PERSONAL_BASE_TYPE', 1);
define('PERSONAL_AUTH_TYPE', 2);
define('PERSONAL_LIST_TYPE', 3);
define('PERSONAL_BIND_TYPE', 4);
define('PERSONAL_LIMIT_TYPE', 5);

//文章分类 一级分类和二级分类为0
define('ARTICLE_PCATE', 0);
define('ARTICLE_CCATE', 0);

//用户注册方式来源
//qq注册
define('USER_REGISTER_TYPE_QQ', 1);
//微信注册
define('USER_REGISTER_TYPE_WECHAT', 2);
//手机注册
define('USER_REGISTER_TYPE_MOBILE', 3);
//微信开放平台 unionid
define('USER_REGISTER_TYPE_OPEN_WECHAT', 4);
//微擎商城手机绑定
define('USER_REGISTER_TYPE_CONSOLE', 5);

//消息提醒类型
//提交订单消息类型
define('MESSAGE_ORDER_TYPE', 1);
//订单支付消息类型
define('MESSAGE_ORDER_PAY_TYPE', 9);
//公众号过期消息类型
define('MESSAGE_ACCOUNT_EXPIRE_TYPE', 2);
//小程序过期消息类型
define('MESSAGE_WECHAT_EXPIRE_TYPE', 5);
//pc过期消息类型
define('MESSAGE_WEBAPP_EXPIRE_TYPE', 6);
//注册消息类型
define('MESSAGE_REGISTER_TYPE', 4);
//用户账号到期
define('MESSAGE_USER_EXPIRE_TYPE', 7);
//系统更新消息
define('MESSAGE_SYSTEM_UPGRADE', 10);
//官方动态消息
define('MESSAGE_OFFICIAL_DYNAMICS', 11);

//消息开关是否开启 1开启 2关闭
define('MESSAGE_ENABLE', 1);
define('MESSAGE_DISABLE', 2);

//消息是否读取 1未读  2已读
define('MESSAGE_NOREAD', 1);
define('MESSAGE_READ', 2);

//上传图片uniacid -1
define('FILE_NO_UNIACID', -1);

//模块获取用户授权方式 1.静默授权 2.用户有感知授权
define('OAUTH_TYPE_BASE', 1);
define('OAUTH_TYPE_USERINFO', 2);

//文章评论的父级id
define('ARTICLE_COMMENT_DEFAULT', 0);
//评论未回复
define('ARTICLE_NOCOMMENT', 1);
//评论已回复
define('ARTICLE_COMMENT', 2);
//评论未读
define('ARTICLE_COMMENT_NOREAD', 1);
//评论已读
define('ARTICLE_COMMENT_READ', 2);

//关闭文章评论
define('COMMENT_STATUS_OFF', 0);
//开启文章评论
define('COMMENT_STATUS_ON', 1);

//用户欢迎页
define('WELCOME_DISPLAY_TYPE', 9);
//平台
define('PLATFORM_DISPLAY_TYPE', 6);
//应用
define('MODULE_DISPLAY_TYPE', 7);

// 添加用户时 , 权限选择方式
// 新的权限方式, 应用所有类型权限组分离存储
define('USER_CREATE_PERMISSION_ALL_TYPE', 1);
// 旧的权限方式, 只有用户权限组
define('USER_CREATE_PERMISSION_GROUP_TYPE', 2);

// 用户到期时间类型
// 兼容旧的用户到期时间: 用户权限组为空, 或者用户权限组到期时间为0
define('USER_ENDTIME_GROUP_EMPTY_TYPE', 0);
// 新: 删除用户用户组
define('USER_ENDTIME_GROUP_DELETE_TYPE', 1);
// 新: 添加的用户选择永久用户权限组 或者 修改用户用户组为永久用户组
define('USER_ENDTIME_GROUP_UNLIMIT_TYPE', 2);

define('USERS_OPERATE_TYPE_ACCOUNT', 1);
define('USERS_OPERATE_TYPE_MODULE', 2);

//快速注册小程序版本状态
define('WXAPP_REGISTER_VERSION_STATUS_DEVELOP', 0); //开发版本(体验版)
define('WXAPP_REGISTER_VERSION_STATUS_CHECKFAIL', 1); //审核失败
define('WXAPP_REGISTER_VERSION_STATUS_CHECKING', 2); //审核中
define('WXAPP_REGISTER_VERSION_STATUS_RETRACT', 3); //已撤回
define('WXAPP_REGISTER_VERSION_STATUS_RELEASE', 4); //发布成功
define('WXAPP_REGISTER_VERSION_STATUS_CHECKSUCCESS', 5); //审核通过
