# return -2=inconsistency in DB-very bad, 0=failed, 1=success

DELIMITER //

DROP PROCEDURE IF EXISTS unDelegate //

CREATE PROCEDURE unDelegate
(IN p_issue_id int,
 IN p_user_id bigint,
 OUT r_steps_to_top int,
 OUT r_result int)
BEGIN
  DECLARE v_user_id,v_delegate_to,v_d2 bigint;
  DECLARE v_strength,v_counter int default 0;
  SET r_result=0;
  DROP TEMPORARY TABLE IF EXISTS temp_results;
  CREATE TEMPORARY TABLE temp_results(idfrom bigint, idto bigint) engine=memory;
  SELECT user_id,delegate_to,strength FROM VTdelegate WHERE issue_id=p_issue_id AND user_id=p_user_id INTO v_user_id,v_delegate_to,v_strength FOR UPDATE;
  INSERT INTO temp_results VALUES (v_user_id,v_delegate_to);
  IF v_delegate_to IS NOT NULL THEN
    SET r_result=1;
    WHILE v_delegate_to IS NOT NULL DO
      UPDATE VTdelegate SET strength=strength-v_strength WHERE issue_id=p_issue_id AND user_id=v_delegate_to;
      SET v_d2=v_delegate_to;
      SET v_delegate_to=NULL;
      SET v_user_id=NULL;
      SELECT user_id,delegate_to FROM VTdelegate WHERE issue_id=p_issue_id AND user_id=v_d2 INTO v_user_id,v_delegate_to FOR UPDATE;
      IF v_user_id IS NULL THEN
        SET r_result=-2;
      END IF;
      INSERT INTO temp_results VALUES (v_user_id,v_delegate_to);
      SET v_counter=v_counter+1;
      IF v_counter>10000 THEN
        SET v_delegate_to=NULL;
        SET r_result=-10;
      END IF;
    END WHILE;
    UPDATE VTdelegate SET delegate_to=NULL WHERE issue_id=p_issue_id AND user_id=p_user_id;
    SET r_steps_to_top=v_counter;
  END IF;
  DELETE FROM VTpending WHERE from_user_id=p_user_id AND issue_id=p_issue_id;
END//
