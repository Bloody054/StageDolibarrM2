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

CREATE TABLE IF NOT EXISTS `llx_questionnairedet`(
  `rowid`           int(11)  AUTO_INCREMENT,
  `fk_questionnaire`       int(11) DEFAULT 0,
  `fk_cond`       int(11) DEFAULT 0,
  `fk_op_cond`      int(11) DEFAULT 0,
  `val_cond`      varchar(255) NULL,
  `code`           varchar(255) NOT NULL,
  `label`           varchar(255) NOT NULL,
  `type`            varchar(255) NOT NULL,
  `postfill`      varchar(255) NULL,
  `prefill`           varchar(255) NULL,
  `rang`             integer DEFAULT 0,
  `param`			    text NULL,	
  `help`            text NULL,
  `visibility`     int(11) DEFAULT 0,
  `crypted`     int(11) DEFAULT 0,
  `inapp`     int(11) DEFAULT 1,
  `mandatory`     int(11) DEFAULT 0,
  `datec`				  datetime NULL,
  `user_author_id` int(11) DEFAULT 0,
  `tms`					timestamp NOT NULL, 
  PRIMARY KEY (`rowid`)    
)ENGINE=innodb DEFAULT CHARSET=utf8;

ALTER TABLE llx_questionnairedet ADD `postfill` varchar(255) NULL;



