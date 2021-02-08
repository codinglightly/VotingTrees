#TODO:test
#return values -1=already exists,0=failed,1=success but pending failed, 2=success

DELIMITER //

DROP PROCEDURE IF EXISTS takeCare //

CREATE PROCEDURE takeCare
(IN p_issue_id int,
 IN p_user_id bigint,
 OUT r_steps_to_top int,
 OUT r_result int)
BEGIN
  DECLARE v_user,v_delegate_to bigint;
  DECLARE v_counter, v_sum int default 0;
  SET r_result=0;
  SELECT user_id FROM VTdelegate WHERE issue_id=p_issue_id AND user_id=p_user_id INTO v_user;
  IF v_user IS NOT NULL THEN
    SET r_result=-1;
  ELSE
    INSERT INTO VTdelegate (user_id,issue_id,strength,updated) VALUES (p_user_id,p_issue_id,1,NOW());
    SELECT count(*), sum(VTdelegate.strength) FROM VTpending, VTdelegate 
      WHERE VTpending.to_user_id=p_user_id AND VTpending.issue_id=p_issue_id
        AND VTdelegate.issue_id=p_issue_id AND VTpending.from_user_id=VTdelegate.user_id
	AND VTdelegate.delegate_to IS NULL INTO v_counter,v_sum;
    IF v_counter>0 THEN
      UPDATE VTdelegate SET delegate_to=p_user_id 
        WHERE issue_id=p_issue_id AND delegate_to IS NULL
	  AND user_id IN 
	    (SELECT from_user_id FROM VTpending
	       WHERE VTpending.to_user_id=p_user_id AND VTpending.issue_id=p_issue_id);
      DELETE FROM VTpending WHERE issue_id=p_issue_id AND to_user_id=p_user_id;
      UPDATE VTdelegate SET strength=strength+v_sum WHERE user_id=p_user_id AND issue_id=p_issue_id;
    END IF;
  END IF;
END//
