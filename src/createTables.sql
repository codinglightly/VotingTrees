DELIMITER ;

-- -----------------------------------------------------
-- Table `VTissue`
-- -----------------------------------------------------

DROP TABLE IF EXISTS `VTissue` ;

CREATE TABLE IF NOT EXISTS `VTissue` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'id of issue',
  `name` CHAR(255) NOT NULL COMMENT 'name of the issue',
  `total` BIGINT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`))
ENGINE = MyISAM
COMMENT = 'issues'
PACK_KEYS = 0
ROW_FORMAT = DEFAULT;


-- -----------------------------------------------------
-- Table `VTdelegate`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `VTdelegate` ;

CREATE TABLE IF NOT EXISTS `VTdelegate` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'id of the record',
  `issue_id` INT(11) UNSIGNED NOT NULL COMMENT 'which issue is this about',
  `user_id` BIGINT UNSIGNED NOT NULL COMMENT 'who is delegating? (can not be NULL)',
  `delegate_to` BIGINT UNSIGNED NULL COMMENT 'to whom is he delegating? (NULL if to nobody. In that case the user is at the top of a tree)',
  `treetop` BIGINT UNSIGNED NULL COMMENT 'which user is at the top of the tree? (the one which is delegating to nobody. NULL if this user)',
  `strength` INT(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'what is the strength of vote of this user (default 1 if nobody is delagating to him)',
  `updated` DATETIME NOT NULL COMMENT 'when was the record updated',
  PRIMARY KEY (`id`),
  INDEX `VTdelegate_issue_user` (`issue_id` ASC, `user_id` ASC))
ENGINE = MyISAM
COMMENT = 'Containing delegating information. (who delegates their power to whom, where is the treetop - where the power ends, what is the users strength)'
PACK_KEYS = 0
ROW_FORMAT = DEFAULT;


-- -----------------------------------------------------
-- Table `VTpending`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `VTpending` ;

CREATE TABLE IF NOT EXISTS `VTpending` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `issue_id` INT(11) UNSIGNED NOT NULL,
  `from_user_id` BIGINT UNSIGNED NOT NULL,
  `to_user_id` BIGINT UNSIGNED NOT NULL,
  `updated` DATETIME NULL,
  PRIMARY KEY (`id`),
  INDEX `VTpending_issue_id` (`issue_id` ASC))
ENGINE = MyISAM
COMMENT = 'potential delegations, where user which the voting power is delegated to is not interested in the issue yet'
PACK_KEYS = 0
ROW_FORMAT = DEFAULT;
