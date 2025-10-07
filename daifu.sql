-- phpMyAdmin SQL Dump
-- version 4.9.7
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Nov 24, 2023 at 02:06 PM
-- Server version: 5.7.36
-- PHP Version: 7.4.26

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `daifu`
--

-- --------------------------------------------------------

--
-- Table structure for table `pay_admin`
--

DROP TABLE IF EXISTS `pay_admin`;
CREATE TABLE IF NOT EXISTS `pay_admin` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `no` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `role_id` int(11) DEFAULT '0' COMMENT '角色id -1所有权限 0没设置角色',
  `username` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT '账号',
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT '密码',
  `realname` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '用户名称',
  `phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT '电话（电话是唯一）',
  `google_secret_key` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT '谷歌密钥',
  `token` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `auth_key` varchar(6) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `last_login_time` timestamp NULL DEFAULT NULL COMMENT '最后登录时间',
  `login_count` int(11) DEFAULT '0' COMMENT '登录次数',
  `create_time` timestamp NULL DEFAULT NULL,
  `update_time` timestamp NULL DEFAULT NULL,
  `status` tinyint(1) DEFAULT '1' COMMENT '状态：1启用 -1禁用',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `username` (`username`) USING BTREE,
  KEY `token` (`token`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='管理员表' ROW_FORMAT=DYNAMIC;

--
-- Dumping data for table `pay_admin`
--

INSERT INTO `pay_admin` (`id`, `no`, `role_id`, `username`, `password`, `realname`, `phone`, `google_secret_key`, `token`, `auth_key`, `last_login_time`, `login_count`, `create_time`, `update_time`, `status`) VALUES
(11, '45e17f81a40201c6daa6688efab75980', -1, 'jqkadmin', '$2y$10$t9VDVyaLdKIj2Gy3ujiDBuVneuvKUskTepxQCXFrIdS.HY8uJxgRy', 'Admin', '15000000000', NULL, 'hKMGxcgycUrdvMdNMKglwRjs63RfUNtLwVUCqUdhIjZHams76Izdbg8leCQq0Uph', 'JnaI39', '2023-11-21 09:51:05', 126, '2023-11-09 09:32:55', '2023-11-21 09:51:06', 1);

-- --------------------------------------------------------

--
-- Table structure for table `pay_admin_log`
--

DROP TABLE IF EXISTS `pay_admin_log`;
CREATE TABLE IF NOT EXISTS `pay_admin_log` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `admin_id` int(11) UNSIGNED DEFAULT '0' COMMENT '后台管理员id',
  `name` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip` varchar(15) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'ip',
  `url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '链接',
  `params` text COLLATE utf8mb4_unicode_ci COMMENT '参数',
  `create_time` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系统操作记录表' ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `pay_business`
--

DROP TABLE IF EXISTS `pay_business`;
CREATE TABLE IF NOT EXISTS `pay_business` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `no` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `type` tinyint(1) DEFAULT '1' COMMENT '类型：1商户 2卡商 3四方 4四方商户',
  `parent_id` int(11) UNSIGNED DEFAULT '0' COMMENT '上级商户id',
  `card_business_ids` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '卡商ids：四方下级的商户才有',
  `role_id` int(11) DEFAULT '0' COMMENT '角色id -1所有权限 0没设置角色',
  `username` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT '商户账号',
  `password` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '密码',
  `auth_key` varchar(6) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `username_api` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT '商户监控账号',
  `password_api` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '监控登录密码',
  `auth_key_api` varchar(6) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `realname` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '商户名称',
  `phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT '电话（电话是唯一）',
  `secret_key` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '密钥',
  `system_name` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '系统名称',
  `system_logo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '系统LOGO',
  `money` decimal(10,4) DEFAULT '0.0000' COMMENT '余额',
  `total_recharge` decimal(15,2) DEFAULT '0.00' COMMENT '充值总金额',
  `allow_withdraw` decimal(15,2) DEFAULT '0.00' COMMENT '可提现金额',
  `login_ip` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '登录ip白名单',
  `multiple_login` tinyint(1) DEFAULT '1' COMMENT '允许多台电脑同时登录：1开启 -1关闭',
  `random_amount` tinyint(1) DEFAULT '-1' COMMENT '随机金额 -1关闭 1加随机金额 2减随机金额',
  `usdt_rate_type` tinyint(1) DEFAULT '1' COMMENT 'USDT汇率类型 1自动 2手动',
  `usdt_rate` decimal(10,4) UNSIGNED DEFAULT '0.0000' COMMENT 'USDT手动汇率',
  `web_bank_timeout` tinyint(2) UNSIGNED DEFAULT '10' COMMENT '卡卡超时（分钟）',
  `usdt_timeout` tinyint(2) UNSIGNED DEFAULT '5' COMMENT 'Usdt超时（分钟）',
  `rmb_timeout` tinyint(2) UNSIGNED DEFAULT '10' COMMENT '数字人民币超时（分钟）',
  `zfb_timeout` tinyint(2) UNSIGNED DEFAULT '10' COMMENT '支付宝超时（分钟）',
  `alipay_transfer_timeout` tinyint(2) UNSIGNED DEFAULT '10' COMMENT '支付宝转支付宝超时（分钟）',
  `alipay_xiaohebao_timeout` tinyint(2) UNSIGNED DEFAULT '10' COMMENT '支付宝小荷包超时（分钟）',
  `yunshanfu_timeout` tinyint(2) UNSIGNED DEFAULT '10' COMMENT '云闪付超时（分钟）',
  `weixin_timeout` tinyint(2) UNSIGNED DEFAULT '10' COMMENT '微信收款码超时（分钟）',
  `alipay_bank_timeout` tinyint(2) UNSIGNED DEFAULT '10' COMMENT '支付宝转卡超时（分钟）',
  `auth_when_edit_account` tinyint(1) DEFAULT '1' COMMENT '修改收款账号需要谷歌验证码：1开启 -1关闭',
  `remark_when_balance_over` decimal(10,2) UNSIGNED DEFAULT '10000.00' COMMENT '当收款账号余额超过会提示',
  `payment_verify_name` tinyint(1) DEFAULT '-1' COMMENT '付款页面输入实名验证：1是 -1否',
  `sms_content_verify_name` tinyint(1) DEFAULT '-1' COMMENT '监控短信付款人姓名：1是 -1否',
  `google_secret_key` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '谷歌密钥',
  `token` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `last_login_time` timestamp NULL DEFAULT NULL COMMENT '最后登录时间',
  `login_count` int(11) DEFAULT '0' COMMENT '登录次数',
  `remark` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '备注',
  `create_time` timestamp NULL DEFAULT NULL,
  `update_time` timestamp NULL DEFAULT NULL,
  `status` tinyint(1) DEFAULT '1' COMMENT '状态：1启用 -1禁用',
  `verify_status` tinyint(1) DEFAULT NULL COMMENT '认证状态：-1待认证 1已认证 2不通过',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `username` (`username`) USING BTREE,
  KEY `token` (`token`) USING BTREE,
  KEY `username_api` (`username_api`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='商户表' ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `pay_business_log`
--

DROP TABLE IF EXISTS `pay_business_log`;
CREATE TABLE IF NOT EXISTS `pay_business_log` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` int(11) UNSIGNED DEFAULT '0' COMMENT '商户id',
  `name` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip` varchar(15) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'ip',
  `url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '链接',
  `params` text COLLATE utf8mb4_unicode_ci COMMENT '参数',
  `create_time` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `business_id` (`business_id`) USING BTREE,
  KEY `url` (`url`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='商户操作记录表' ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `pay_business_money_log`
--

DROP TABLE IF EXISTS `pay_business_money_log`;
CREATE TABLE IF NOT EXISTS `pay_business_money_log` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` int(11) UNSIGNED DEFAULT '0' COMMENT '商户ID',
  `type` tinyint(1) DEFAULT NULL COMMENT '类型：1充值 2订单费用 3总后台操作',
  `money` decimal(10,4) NOT NULL DEFAULT '0.0000' COMMENT '操作金额',
  `money_before` decimal(10,4) NOT NULL DEFAULT '0.0000' COMMENT '操作前金额',
  `money_after` decimal(10,4) NOT NULL DEFAULT '0.0000' COMMENT '操作后金额',
  `item_id` int(11) UNSIGNED DEFAULT '0' COMMENT '对象id（可以是充值id，订单id，后台员工账号id）',
  `remark` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '备注',
  `create_time` timestamp NULL DEFAULT NULL,
  `status` tinyint(1) DEFAULT '-1' COMMENT '状态：-1未处理 1已处理',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `status` (`status`) USING BTREE,
  KEY `business_id` (`business_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='商户资金变化记录表' ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `pay_business_recharge`
--

DROP TABLE IF EXISTS `pay_business_recharge`;
CREATE TABLE IF NOT EXISTS `pay_business_recharge` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `no` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `business_id` int(11) UNSIGNED DEFAULT '0' COMMENT '商户id',
  `recharge_type` tinyint(1) DEFAULT NULL COMMENT '充值方式：-1后台充值 1Usdt',
  `order_no` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '系统订单号',
  `account_name` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '收款账号名称',
  `account` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '银行卡号/Usdt地址/钱包编号/支付宝收款码',
  `account_sub` varchar(4) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '尾号4位数',
  `post_amount` decimal(10,2) UNSIGNED DEFAULT '0.00' COMMENT '提交金额',
  `pay_amount` decimal(10,2) UNSIGNED DEFAULT '0.00' COMMENT '实付金额',
  `usdt_rate` decimal(10,4) DEFAULT '0.0000' COMMENT 'USDT汇率',
  `usdt_amount` decimal(10,2) DEFAULT '0.00' COMMENT 'USDT金额',
  `usdt_transaction_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'USDT区块链交易id',
  `ip` varchar(15) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'ip',
  `remark` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '商户备注',
  `info` text CHARACTER SET utf8 COLLATE utf8_unicode_ci,
  `expire_time` timestamp NULL DEFAULT NULL COMMENT '过期时间',
  `success_time` timestamp NULL DEFAULT NULL COMMENT '成功时间',
  `create_time` timestamp NULL DEFAULT NULL COMMENT '下单时间',
  `update_time` timestamp NULL DEFAULT NULL,
  `status` tinyint(1) DEFAULT '0' COMMENT '状态：-1未支付 1成功 -2生成订单失败',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `order_no` (`order_no`) USING BTREE,
  KEY `business_id` (`business_id`) USING BTREE,
  KEY `account_sub` (`account_sub`) USING BTREE,
  KEY `success_time` (`success_time`) USING BTREE,
  KEY `create_time` (`create_time`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='充值表' ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `pay_business_withdraw`
--

DROP TABLE IF EXISTS `pay_business_withdraw`;
CREATE TABLE IF NOT EXISTS `pay_business_withdraw` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `no` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `business_id` int(11) UNSIGNED DEFAULT '0' COMMENT '四方id',
  `sub_business_id` int(11) UNSIGNED DEFAULT '0' COMMENT '商户id',
  `type` tinyint(1) DEFAULT NULL COMMENT '提现方式：-1四方操作 1银行卡 2Usdt 3支付宝',
  `name` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '收款账号名称',
  `account` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '银行卡号/Usdt地址/钱包编号/支付宝收款码',
  `bank_name` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '银行名称',
  `amount` decimal(10,2) UNSIGNED DEFAULT '0.00' COMMENT '金额',
  `usdt_rate` decimal(10,4) DEFAULT '0.0000' COMMENT 'USDT汇率',
  `usdt_amount` decimal(10,2) DEFAULT '0.00' COMMENT 'USDT金额',
  `remark` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '审核备注',
  `info` text CHARACTER SET utf8 COLLATE utf8_unicode_ci,
  `create_time` timestamp NULL DEFAULT NULL COMMENT '提交时间',
  `update_time` timestamp NULL DEFAULT NULL COMMENT '审核时间',
  `status` tinyint(1) DEFAULT '-1' COMMENT '状态：-1未审核 1成功 2审核失败',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `business_id` (`business_id`) USING BTREE,
  KEY `sub_business_id` (`sub_business_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='提现表' ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `pay_business_withdraw_log`
--

DROP TABLE IF EXISTS `pay_business_withdraw_log`;
CREATE TABLE IF NOT EXISTS `pay_business_withdraw_log` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` int(11) UNSIGNED DEFAULT '0' COMMENT '四方id',
  `sub_business_id` int(11) UNSIGNED DEFAULT '0' COMMENT '商户id',
  `type` tinyint(1) DEFAULT NULL COMMENT '类型：1订单金额 2商户提现 3四方操作',
  `money` decimal(10,2) DEFAULT '0.00' COMMENT '操作金额',
  `money_before` decimal(10,2) DEFAULT '0.00' COMMENT '操作前金额',
  `money_after` decimal(10,2) DEFAULT '0.00' COMMENT '操作后金额',
  `item_id` int(11) UNSIGNED DEFAULT '0' COMMENT '对象id（可以是提现id，订单id）',
  `remark` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '备注',
  `create_time` timestamp NULL DEFAULT NULL,
  `status` tinyint(1) DEFAULT '-1' COMMENT '状态：-1未处理 1已处理',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `status` (`status`) USING BTREE,
  KEY `business_id` (`business_id`),
  KEY `sub_business_id` (`sub_business_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='商户可提现金额变化记录表' ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `pay_demo_account`
--

DROP TABLE IF EXISTS `pay_demo_account`;
CREATE TABLE IF NOT EXISTS `pay_demo_account` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `no` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `business_id` int(11) UNSIGNED DEFAULT '0' COMMENT '商户id',
  `channel_id` int(11) UNSIGNED DEFAULT '0' COMMENT '通道id',
  `system_bank_id` int(11) UNSIGNED DEFAULT '0' COMMENT '系统银行id（银行卡才有）',
  `account_title` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT '账号标题',
  `account_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT '银行卡开户名/账号名称',
  `account` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT '银行卡号/Usdt地址/钱包编号/支付宝收款码',
  `is_use` tinyint(1) UNSIGNED DEFAULT '0' COMMENT '是否已下单 1是 0否（用于轮询下单）',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态：1开启 -1关闭',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='测试账号表' ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `pay_demo_order`
--

DROP TABLE IF EXISTS `pay_demo_order`;
CREATE TABLE IF NOT EXISTS `pay_demo_order` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `no` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `business_id` int(11) UNSIGNED DEFAULT '0' COMMENT '商户id',
  `channel_id` int(11) UNSIGNED DEFAULT '0' COMMENT '通道id',
  `order_no` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '系统订单号',
  `out_trade_no` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '商户订单号',
  `account_title` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '收款账号标题',
  `account_name` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '收款账号名称',
  `account` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '银行卡号/Usdt地址/钱包编号/支付宝收款码',
  `account_sub` varchar(4) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '尾号4位数',
  `pay_amount` decimal(10,2) UNSIGNED DEFAULT '0.00' COMMENT '实付金额',
  `usdt_rate` decimal(10,4) DEFAULT '0.0000' COMMENT 'USDT汇率',
  `usdt_amount` decimal(10,2) DEFAULT '0.00' COMMENT 'USDT金额',
  `usdt_transaction_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'USDT区块链交易id',
  `fee` decimal(8,4) UNSIGNED DEFAULT '0.0000' COMMENT '费用',
  `pay_ip` varchar(15) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '客户支付ip',
  `remark` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '用户备注',
  `info` text COLLATE utf8mb4_unicode_ci,
  `success_time` timestamp NULL DEFAULT NULL COMMENT '成功时间',
  `create_time` timestamp NULL DEFAULT NULL COMMENT '下单时间',
  `status` tinyint(1) DEFAULT '0' COMMENT '状态：-1未支付 1成功，未回调 2成功，已回调 -2支付失败',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `order_no` (`order_no`) USING BTREE,
  KEY `out_trade_no` (`out_trade_no`) USING BTREE,
  KEY `channel_id` (`channel_id`) USING BTREE,
  KEY `business_id` (`business_id`) USING BTREE,
  KEY `account_sub` (`account_sub`) USING BTREE,
  KEY `success_time` (`success_time`) USING BTREE,
  KEY `create_time` (`create_time`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='测试订单表' ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `pay_demo_param`
--

DROP TABLE IF EXISTS `pay_demo_param`;
CREATE TABLE IF NOT EXISTS `pay_demo_param` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `key` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '参数名称',
  `info` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `key` (`key`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='测试参数表' ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `pay_finance_statistics`
--

DROP TABLE IF EXISTS `pay_finance_statistics`;
CREATE TABLE IF NOT EXISTS `pay_finance_statistics` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` int(11) UNSIGNED DEFAULT '0' COMMENT '商户id',
  `sub_business_id` int(11) UNSIGNED DEFAULT '0' COMMENT '下级商户id',
  `channel_id` int(11) UNSIGNED DEFAULT '0' COMMENT '通道id',
  `total_order` int(11) UNSIGNED DEFAULT '0' COMMENT '总订单',
  `success_order` int(11) UNSIGNED DEFAULT '0' COMMENT '成功订单',
  `success_amount` decimal(10,2) UNSIGNED DEFAULT '0.00' COMMENT '成功金额',
  `allow_withdraw` decimal(10,2) UNSIGNED DEFAULT '0.00' COMMENT '可提现金额',
  `total_fee` decimal(8,4) UNSIGNED DEFAULT '0.0000' COMMENT '总费用',
  `date` timestamp NULL DEFAULT NULL COMMENT '时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `channel_id` (`channel_id`) USING BTREE,
  KEY `business_id` (`business_id`) USING BTREE,
  KEY `date` (`date`) USING BTREE,
  KEY `sub_business_id` (`sub_business_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='交易排行统计表' ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `pay_login_log`
--

DROP TABLE IF EXISTS `pay_login_log`;
CREATE TABLE IF NOT EXISTS `pay_login_log` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(11) UNSIGNED DEFAULT '0' COMMENT '后台账号id/商户id',
  `type` tinyint(1) UNSIGNED DEFAULT NULL COMMENT '类型：1总后台 2商户',
  `ip` varchar(15) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'ip',
  `area` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '地区',
  `create_time` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系统登录记录表' ROW_FORMAT=DYNAMIC;

--
-- Dumping data for table `pay_login_log`
--

INSERT INTO `pay_login_log` (`id`, `user_id`, `type`, `ip`, `area`, `create_time`) VALUES
(1, 11, 1, '::1', 'IANA保留地址', '2023-11-21 09:50:33'),
(2, 11, 1, '::1', 'IANA保留地址', '2023-11-21 09:51:06');

-- --------------------------------------------------------

--
-- Table structure for table `pay_order`
--

DROP TABLE IF EXISTS `pay_order`;
CREATE TABLE IF NOT EXISTS `pay_order` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `no` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `business_id` int(11) UNSIGNED DEFAULT '0' COMMENT '商户id/四方id',
  `sub_business_id` int(11) UNSIGNED DEFAULT '0' COMMENT '下级商户id',
  `card_business_id` int(11) UNSIGNED DEFAULT '0' COMMENT '卡商id',
  `channel_id` int(11) UNSIGNED DEFAULT '0' COMMENT '通道id',
  `channel_account_id` int(11) UNSIGNED DEFAULT '0' COMMENT '通道账号id',
  `order_no` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '系统订单号',
  `out_trade_no` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '商户订单号',
  `account_title` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '收款账号标题',
  `account_name` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '收款账号名称',
  `account` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '银行卡号/Usdt地址/钱包编号/支付宝收款码',
  `account_sub` varchar(4) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '尾号4位数',
  `account_remark` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '账号备注',
  `post_amount` decimal(10,2) UNSIGNED DEFAULT '0.00' COMMENT '提交金额',
  `pay_amount` decimal(10,2) UNSIGNED DEFAULT '0.00' COMMENT '实付金额',
  `usdt_rate` decimal(10,4) DEFAULT '0.0000' COMMENT 'USDT汇率',
  `usdt_amount` decimal(10,2) DEFAULT '0.00' COMMENT 'USDT金额',
  `usdt_transaction_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'USDT区块链交易id',
  `fee` decimal(8,4) UNSIGNED DEFAULT '0.0000' COMMENT '费用',
  `sub_business_channel_rate` decimal(8,3) UNSIGNED DEFAULT '0.000' COMMENT '下级商户费率',
  `rate_amount` decimal(8,2) UNSIGNED DEFAULT '0.00' COMMENT '下级商户费率金额',
  `allow_withdraw` decimal(10,2) UNSIGNED DEFAULT '0.00' COMMENT '可提现金额',
  `ip` varchar(15) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '请求接口ip',
  `pay_ip` varchar(15) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '客户支付ip',
  `payer_name` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '接口付款人姓名',
  `input_payer_name` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '输入付款人姓名',
  `sms_payer_name` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '短信付款人姓名',
  `notify_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '回调地址',
  `attach` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '附加数据',
  `remark` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '用户备注',
  `info` text COLLATE utf8mb4_unicode_ci,
  `pay_data` text COLLATE utf8mb4_unicode_ci,
  `pay_code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '用于核对订单',
  `verify_status` tinyint(1) DEFAULT NULL COMMENT '支付宝转账码：-1未上传 1已上传 2已领取',
  `success_type` tinyint(1) DEFAULT '1' COMMENT '回调类型：1自动回调 2手动回调',
  `notify_log_id` int(11) UNSIGNED DEFAULT '0' COMMENT '监控记录id',
  `notify_num` tinyint(2) UNSIGNED DEFAULT '0' COMMENT '回调次数',
  `expire_time` timestamp NULL DEFAULT NULL COMMENT '过期时间',
  `success_time` timestamp NULL DEFAULT NULL COMMENT '成功时间',
  `last_notify_time` timestamp NULL DEFAULT NULL COMMENT '最后回调时间',
  `create_time` timestamp NULL DEFAULT NULL COMMENT '下单时间',
  `update_time` timestamp NULL DEFAULT NULL,
  `status` tinyint(1) DEFAULT '0' COMMENT '状态：-1未支付 1成功，未回调 2成功，已回调 -2支付失败',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `order_no` (`order_no`) USING BTREE,
  UNIQUE KEY `out_trade_no` (`out_trade_no`) USING BTREE,
  UNIQUE KEY `no` (`no`) USING BTREE,
  KEY `channel_id` (`channel_id`) USING BTREE,
  KEY `business_id` (`business_id`) USING BTREE,
  KEY `success_time` (`success_time`) USING BTREE,
  KEY `create_time` (`create_time`) USING BTREE,
  KEY `status` (`status`) USING BTREE,
  KEY `verify_status` (`verify_status`) USING BTREE,
  KEY `channel_account_id` (`channel_account_id`) USING BTREE,
  KEY `pay_amount` (`pay_amount`) USING BTREE,
  KEY `usdt_transaction_id` (`usdt_transaction_id`) USING BTREE,
  KEY `expire_time` (`expire_time`) USING BTREE,
  KEY `notify_log_id` (`notify_log_id`) USING BTREE,
  KEY `sub_business_id` (`sub_business_id`) USING BTREE,
  KEY `business_id_2` (`business_id`,`channel_id`,`status`) USING BTREE,
  KEY `business_id_3` (`business_id`,`create_time`),
  KEY `account_remark` (`account_remark`),
  KEY `account` (`account`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='订单表' ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `pay_pay_log`
--

DROP TABLE IF EXISTS `pay_pay_log`;
CREATE TABLE IF NOT EXISTS `pay_pay_log` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` int(11) UNSIGNED DEFAULT '0' COMMENT '商户id',
  `ip` varchar(15) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'ip',
  `url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '链接',
  `params` text COLLATE utf8mb4_unicode_ci COMMENT '参数',
  `create_time` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='下单记录表' ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `pay_role`
--

DROP TABLE IF EXISTS `pay_role`;
CREATE TABLE IF NOT EXISTS `pay_role` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `no` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `type` tinyint(1) DEFAULT NULL COMMENT '类型：1代理总权限 2管理员角色',
  `center_id` int(11) UNSIGNED DEFAULT '0' COMMENT '代理ID',
  `name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '角色名称',
  `permission` text COLLATE utf8mb4_unicode_ci COMMENT '权限',
  `create_time` timestamp NULL DEFAULT NULL,
  `update_time` timestamp NULL DEFAULT NULL,
  `status` tinyint(1) DEFAULT NULL COMMENT '状态：1启用 -1禁用',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='角色表' ROW_FORMAT=DYNAMIC;

--
-- Dumping data for table `pay_role`
--

INSERT INTO `pay_role` (`id`, `no`, `type`, `center_id`, `name`, `permission`, `create_time`, `update_time`, `status`) VALUES
(1, 'd8c397eda7374ab779b19a25c1d47611', 2, 0, '客服', '{\"system\":{\"change_password\":[],\"google_auth\":[]},\"order\":{\"list_all\":{\"order:export\":[],\"order:resend_notify\":[]},\"list_notify\":{\"notify_log:export\":[]}},\"business\":{\"list_business\":{\"business:save:add\":[],\"business:save:edit\":[],\"business:save_channel\":[],\"business:login\":[]},\"list_agent\":{\"business_agent:save:add\":[],\"business_agent:save:edit\":[],\"business_agent:save_channel\":[],\"business_agent:login\":[]},\"list_business_card\":{\"business_card:save:edit\":[],\"business_card:login\":[]},\"list_sub_business\":{\"business_sub:save:edit\":[],\"business_sub:save_channel\":[],\"business_sub:login\":[]}},\"channel\":{\"list_all\":[],\"list_system_bank\":{\"system_bank:save:add\":[],\"system_bank:save:edit\":[]},\"list_bank\":[],\"list_alipay_bank\":[],\"list_usdt\":[],\"list_rmb\":[],\"list_alipay_transfer\":[],\"list_alipay_xiaohebao\":[],\"list_alipay_wap\":[],\"list_yunshanfu\":[],\"list_weixin\":[]}}', '2022-10-05 03:24:15', '2023-08-29 08:35:59', 1),
(2, '08ba8b12669fb1139fd088a9ef17ddfd', 2, 0, '财务', '{\"order\":[]}', '2022-10-05 03:43:17', '2023-02-17 11:03:48', -1);

-- --------------------------------------------------------

--
-- Table structure for table `pay_setting`
--

DROP TABLE IF EXISTS `pay_setting`;
CREATE TABLE IF NOT EXISTS `pay_setting` (
  `id` tinyint(1) UNSIGNED NOT NULL AUTO_INCREMENT,
  `recharge_channel` tinyint(1) UNSIGNED DEFAULT '1' COMMENT '充值通道：1Usdt',
  `recharge_account_usdt` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Usdt充值地址',
  `google_auth_admin` tinyint(1) DEFAULT '1' COMMENT '开启谷歌身份验证（总站）：1开启 -1关闭',
  `google_auth_business` tinyint(1) DEFAULT '1' COMMENT '开启谷歌身份验证（商户）：1开启 -1关闭',
  `allow_unbind_admin` tinyint(1) DEFAULT '1' COMMENT '解绑谷歌密钥（总站）：1开启 -1关闭',
  `allow_unbind_business` tinyint(1) DEFAULT '1' COMMENT '解绑谷歌密钥（商户）：1开启 -1关闭',
  `less_money_notify` tinyint(1) DEFAULT '1' COMMENT '商户余额不足提醒 1开启 -1关闭',
  `less_money_can_view_order` tinyint(1) DEFAULT '-1' COMMENT '商户余额不足允许查看订单 1开启 -1关闭',
  `less_money_can_order` tinyint(1) DEFAULT '1' COMMENT '商户余额不足允许下单 1开启 -1关闭',
  `cannot_order_less_than` decimal(10,2) DEFAULT '-200.00' COMMENT '商户余额低于额度不能下单',
  `order_amount_duplicate_check` tinyint(1) DEFAULT '1' COMMENT '订单金额检查重复 1商家通道 2商家收款账号',
  `random_amount` tinyint(1) DEFAULT '1' COMMENT '随机金额 -1关闭 1加随机金额 2减随机金额',
  `random_amount_min` decimal(10,2) UNSIGNED DEFAULT '0.01' COMMENT '随机金额最小',
  `random_amount_max` decimal(10,2) UNSIGNED DEFAULT '0.99' COMMENT '随机金额最大',
  `usdt_rate` decimal(4,2) UNSIGNED DEFAULT '6.95' COMMENT 'USDT汇率',
  `update_usdt_rate_time` timestamp NULL DEFAULT NULL COMMENT '更新Usdt汇率时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系统设置表' ROW_FORMAT=DYNAMIC;

--
-- Dumping data for table `pay_setting`
--

INSERT INTO `pay_setting` (`id`, `recharge_channel`, `recharge_account_usdt`, `google_auth_admin`, `google_auth_business`, `allow_unbind_admin`, `allow_unbind_business`, `less_money_notify`, `less_money_can_view_order`, `less_money_can_order`, `cannot_order_less_than`, `order_amount_duplicate_check`, `random_amount`, `random_amount_min`, `random_amount_max`, `usdt_rate`, `update_usdt_rate_time`) VALUES
(1, 1, 'TXWULVaVXgzPvKVEqQc3kucEQusnZrFj7k', 1, 1, -1, -1, 1, 1, 1, '-100.00', 1, -1, '0.01', '0.20', '7.43', '2023-09-13 18:20:01');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
