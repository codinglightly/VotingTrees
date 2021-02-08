# return values 0=failed,1=success,2=success, undelegate was called
# TODO: test if unDelegate produces strength<>1 => means DB consistency error !!!

DELIMITER //

DROP PROCEDURE IF EXISTS unTakeCare //

CREATE PROCEDURE unTakeCare
(IN p_issue_id int,
 IN p_user bigint,
 OUT r_result int)
BEGIN
  DECLARE v_user,v_delegate_to bigint;
  DECLARE v_result int default 0;
  DECLARE v_steps_to_top bigint;
  SET r_result=0;
  SELECT user_id, delegate_to FROM VTdelegate WHERE issue_id=p_issue_id AND user_id=p_user into v_user,v_delegate_to;
  IF v_user IS NOT NULL THEN
    SET r_result=1;
    SET v_result=1;
    IF v_delegate_to IS NOT NULL THEN
      CALL unDelegate(p_issue_id, p_user, v_steps_to_top, v_result);
      SET r_result=2;
    END IF;
    IF v_result=1 THEN
      DELETE FROM VTdelegate WHERE issue_id=p_issue_id AND user_id=p_user;
      INSERT IGNORE INTO VTpending (issue_id,from_user_id,to_user_id,updated)
        SELECT issue_id, user_id, delegate_to, NOW() FROM VTdelegate WHERE delegate_to=p_user AND issue_id=p_issue_id;
      UPDATE VTdelegate SET delegate_to=NULL, updated=NOW() WHERE delegate_to=p_user AND issue_id=p_issue_id;
    END IF;
  END IF;
  DELETE FROM VTpending WHERE issue_id=p_issue_id AND from_user_id=p_user;
END//
