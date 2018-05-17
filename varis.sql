-- phpMyAdmin SQL Dump
-- version 4.7.4
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: 2018-05-17 02:17:49
-- 服务器版本： 5.7.19
-- PHP Version: 7.0.23

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `varis`
--

-- --------------------------------------------------------

--
-- 表的结构 `vr_devices`
--

CREATE TABLE `vr_devices` (
  `imei` varchar(50) NOT NULL DEFAULT '' COMMENT 'IMEI',
  `devType` varchar(50) NOT NULL DEFAULT '' COMMENT '设备类型',
  `devName` varchar(50) DEFAULT '' COMMENT '设备名称',
  `userName` varchar(50) DEFAULT '' COMMENT '用户名称',
  `userPwd` varchar(50) DEFAULT '' COMMENT '用户密码',
  `site` varchar(250) DEFAULT '' COMMENT '地址',
  `note` varchar(250) DEFAULT '' COMMENT '备注',
  `regDate` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '注册日期',
  `devID` varchar(4) DEFAULT '' COMMENT '设备ID',
  `devState` int(11) NOT NULL DEFAULT '0' COMMENT '设备状态',
  `enableDate` int(11) UNSIGNED DEFAULT '0' COMMENT '启用日期',
  `checkDate` int(11) UNSIGNED DEFAULT '0' COMMENT '审核日期',
  `disableDate` int(11) UNSIGNED DEFAULT '0' COMMENT '锁定日期',
  `devUserID` varchar(50) DEFAULT '' COMMENT '绑定用户ID',
  `devInfos` text COMMENT '设备内容',
  `devInInfos` text COMMENT '待下载内容',
  `devOutInfos` text COMMENT '待删除内容'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='设备表' ROW_FORMAT=COMPACT;

--
-- 转存表中的数据 `vr_devices`
--

INSERT INTO `vr_devices` (`imei`, `devType`, `devName`, `userName`, `userPwd`, `site`, `note`, `regDate`, `devID`, `devState`, `enableDate`, `checkDate`, `disableDate`, `devUserID`, `devInfos`, `devInInfos`, `devOutInfos`) VALUES
('b633050d0cdd815a', 'varisVRdev', 'my', 'my', 'admin', 'www', 'www', 1523861206, '', 136, 0, 0, 0, '1869043752', NULL, ',8536079412,0623754819,8496072351', NULL),
('e30c3e90a21d793a', 'varisVRdev', 'my', 'my', 'admin', 'www', 'www', 1524882642, '', 137, 0, 0, 0, '1869043752', NULL, ',4109625387', NULL);

-- --------------------------------------------------------

--
-- 表的结构 `vr_infos`
--

CREATE TABLE `vr_infos` (
  `infoID` varchar(50) NOT NULL DEFAULT '' COMMENT '内容ID',
  `infoName` varchar(50) NOT NULL DEFAULT '' COMMENT '内容名称',
  `infoType` varchar(50) NOT NULL DEFAULT '' COMMENT '内容类型',
  `infoHref` varchar(200) NOT NULL DEFAULT '' COMMENT '内容地址'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='内容表' ROW_FORMAT=COMPACT;

--
-- 转存表中的数据 `vr_infos`
--

INSERT INTO `vr_infos` (`infoID`, `infoName`, `infoType`, `infoHref`) VALUES
('8536079412', '宣传片', 'mp4', 'http://www.vrfeng.me:180/video.mp4'),
('2631054987', '一', 'jpg', 'http://www.vrfeng.me:180/pic/1.jpg'),
('0623754819', '二', 'jpg', 'http://www.vrfeng.me:180/pic/2.jpg'),
('8496072351', '三', 'jpg', 'http://www.vrfeng.me:180/pic/3.jpg'),
('4109625387', '四', 'jpg', 'http://www.vrfeng.me:180/pic/4.jpg'),
('8150379624', '五', 'jpg', 'http://www.vrfeng.me:180/pic/5.jpg');

-- --------------------------------------------------------

--
-- 表的结构 `vr_user`
--

CREATE TABLE `vr_user` (
  `userID` varchar(50) NOT NULL DEFAULT '' COMMENT '用户ID',
  `userName` varchar(50) NOT NULL DEFAULT '' COMMENT '用户名',
  `userPwd` varchar(50) NOT NULL DEFAULT '' COMMENT '用户密码',
  `userEmail` varchar(50) NOT NULL DEFAULT '' COMMENT '用户邮箱',
  `userDevices` varchar(50) DEFAULT '' COMMENT '用户绑定设备',
  `userInfos` varchar(50) DEFAULT '' COMMENT '用户绑定内容'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='用户表' ROW_FORMAT=COMPACT;

--
-- 转存表中的数据 `vr_user`
--

INSERT INTO `vr_user` (`userID`, `userName`, `userPwd`, `userEmail`, `userDevices`, `userInfos`) VALUES
('1869043752', 'my', 'admin', '972739823@qq.com', ',b633050d0cdd815a,e30c3e90a21d793a', ',8536079412,0623754819,8496072351,4109625387');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
