-- ============================================================================
-- Copyright (C) 2019 Mikael Carlavan  <contact@mika-carl.fr>
--
-- This program is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation; either version 2 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program. If not, see <http://www.gnu.org/licenses/>.
--
-- ============================================================================

CREATE TABLE IF NOT EXISTS `llx_questionnaire`
(
    `rowid`          int(11) AUTO_INCREMENT,
    `ref`            varchar(255) NULL,
    `title`          varchar(255) NULL,
    `active`         int(11)    DEFAULT 1,
    `selected`        tinyint(1) DEFAULT 0,
    `fk_email`      int(11) DEFAULT 0,
    `fk_confirmation_email_model`      int(11) DEFAULT 0,
    `fk_notification_email_model`      int(11) DEFAULT 0,
    `fk_notification_usergroup`      int(11) DEFAULT 0,
    `progressbar`        tinyint(1) DEFAULT 0, 
    `fk_name`      int(11) DEFAULT 0,
    `fk_date`      int(11) DEFAULT 0,
    `fk_location`      int(11) DEFAULT 0,
    `user_author_id` int(11)    DEFAULT 0,
    `datec`          datetime     NULL,
    `entity`         int(11)    DEFAULT 0,
    `tms`            timestamp    NOT NULL,
    PRIMARY KEY (`rowid`)
) ENGINE = innodb
  DEFAULT CHARSET = utf8;

ALTER TABLE llx_questionnaire ADD `fk_confirmation_email_model` int(11) DEFAULT 0;
ALTER TABLE llx_questionnaire ADD `fk_notification_usergroup` int(11) DEFAULT 0;
ALTER TABLE `llx_questionnaire` ADD `progressbar` int(11)    DEFAULT 0;
ALTER TABLE `llx_questionnaire` ADD `progressbar_duration` VARCHAR(4) DEFAULT 0 NULL;
ALTER TABLE `llx_questionnaire` ADD `background` VARCHAR(7) NULL;
ALTER TABLE `llx_questionnaire` ADD `coloraccent` VARCHAR(7) NULL;
ALTER TABLE `llx_questionnaire` ADD `buttonbackground` VARCHAR(7) NULL;
ALTER TABLE `llx_questionnaire` ADD `buttonbackgroundhover` VARCHAR(7) NULL;
ALTER TABLE `llx_questionnaire` ADD `footerdescription` TINYTEXT NULL;
ALTER TABLE `llx_questionnaire` ADD `customcss` TINYTEXT NULL;
ALTER TABLE `llx_questionnaire` ADD `aftersubmission` VARCHAR(255) NULL;
ALTER TABLE `llx_questionnaire` ADD `aftersubmissioncustompage` TINYTEXT NULL;
ALTER TABLE `llx_questionnaire` ADD `customconfirmmessage` TINYTEXT NULL;
ALTER TABLE `llx_questionnaire` ADD `needtobeconnected` int(11)    DEFAULT 0;
ALTER TABLE `llx_questionnaire` ADD `coloraccent` VARCHAR(7) NULL AFTER `customcss`; 