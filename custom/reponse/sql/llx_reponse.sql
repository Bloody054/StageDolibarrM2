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

CREATE TABLE IF NOT EXISTS `llx_reponse`
(
    `rowid`          int(11) AUTO_INCREMENT,
    `ref`            varchar(255)      NULL,
    `fk_questionnaire`        int(11) DEFAULT 0,
    `fk_soc`        int(11) DEFAULT 0,
    `fk_projet`        int(11) DEFAULT 0,
    `datec`          datetime          NULL,
    `note_private`   text              NULL,
    `envoi_ar`       int(11) DEFAULT 0 NOT NULL,
    `is_draft`       int(11) DEFAULT 0,
    `user_author_id` int(11) DEFAULT 0,
    `entity`         int(11) DEFAULT 0,
    `active`         int(11) DEFAULT 1,
    `tms`            timestamp         NOT NULL,
    PRIMARY KEY (`rowid`)
) ENGINE = innodb
  DEFAULT CHARSET = utf8;

ALTER TABLE llx_reponse ADD COLUMN  `fk_soc`        int(11) DEFAULT 0;
ALTER TABLE llx_reponse ADD COLUMN  `fk_projet`        int(11) DEFAULT 0;
ALTER TABLE llx_reponse ADD COLUMN  `origin_id`        int(11) DEFAULT 0;
ALTER TABLE llx_reponse ADD COLUMN  `origin`        varchar(255)      NULL;