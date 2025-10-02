/*
 Navicat Premium Dump SQL

 Source Server         : Remote_oouthsal
 Source Server Type    : MySQL
 Source Server Version : 80037 (8.0.37)
 Source Host           : 208.115.219.166:3306
 Source Schema         : oouthsal_salary3

 Target Server Type    : MySQL
 Target Server Version : 80037 (8.0.37)
 File Encoding         : 65001

 Date: 02/10/2025 13:45:17
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for staff_status
-- ----------------------------
DROP TABLE IF EXISTS `staff_status`;
CREATE TABLE `staff_status` (
  `STATUSCD` varchar(2) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `STATUS` varchar(15) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- ----------------------------
-- Records of staff_status
-- ----------------------------
BEGIN;
INSERT INTO `staff_status` (`STATUSCD`, `STATUS`) VALUES ('A', 'ACTIVE');
INSERT INTO `staff_status` (`STATUSCD`, `STATUS`) VALUES ('D', 'DISMISSED');
INSERT INTO `staff_status` (`STATUSCD`, `STATUS`) VALUES ('T', 'TERMINATION');
INSERT INTO `staff_status` (`STATUSCD`, `STATUS`) VALUES ('R', 'RESIGNATION');
INSERT INTO `staff_status` (`STATUSCD`, `STATUS`) VALUES ('S', 'SUSPENSION');
INSERT INTO `staff_status` (`STATUSCD`, `STATUS`) VALUES ('DE', 'DEATH');
COMMIT;

SET FOREIGN_KEY_CHECKS = 1;
