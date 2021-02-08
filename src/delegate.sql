# lock tables before calling, unlock afterwards

# TODO: what about pending?, test
# r_result 1=success,   2=success and pending deleted, 3=to pending, 4=to pending and pending deleted,
#         -2=reserved, -3=user_id doesnt care,         -4=already delegated
# p_ parameters
# v_ variables
# r_ results

DELIMITER //

DROP PROCEDURE IF EXISTS delegate //

CREATE PROCEDURE delegate
(IN p_issue_id int,
 IN p_user_id bigint,
 IN p_to_user_id bigint,
 OUT r_steps_to_top int,
 OUT r_result int)
BEGIN
  DECLARE v_user_id,v_delegate_to bigint;
  DECLARE v_strength,v_cycle, v_counter, v_affected int default 0;
  SET r_result=0;
  # chech if user takes care or if he is already delegating
  SELECT user_id,delegate_to,strength FROM VTdelegate WHERE issue_id=p_issue_id AND user_id=p_user_id INTO v_user_id,v_delegate_to,v_strength FOR UPDATE;
  IF v_user_id IS NULL THEN
  # user doesn't really care
    SET r_result=-3;
  ELSEIF v_delegate_to IS NOT NULL THEN
    SET r_result=-4;
  ELSE
    SET v_user_id=NULL;
    SET v_delegate_to=NULL;
    DROP TEMPORARY TABLE IF EXISTS temp_path;
    CREATE TEMPORARY TABLE temp_path(idfrom bigint, idto bigint) engine=memory;
    SELECT user_id,delegate_to FROM VTdelegate WHERE issue_id=p_issue_id AND user_id=p_to_user_id INTO v_user_id,v_delegate_to FOR UPDATE;
    IF (v_user_id is not null) THEN
      WHILE v_user_id is not null DO
      #TODO: temp table vs cycle test
        INSERT INTO temp_path VALUES (v_user_id,v_delegate_to);
        SET v_counter=v_counter+1;
          IF v_counter>10000 THEN
            SET r_result=-10;
            SET v_user_id=NULL;
          END IF;
        IF v_delegate_to = p_user_id THEN
          SET v_cycle=1;
          SET r_result=-1;
          SET v_user_id=NULL;
        ELSEIF v_delegate_to IS NOT NULL THEN
          SELECT user_id,delegate_to FROM VTdelegate WHERE issue_id=p_issue_id AND user_id=v_delegate_to INTO v_user_id,v_delegate_to FOR UPDATE;
          SET v_counter=v_counter+1;
        ELSE
          SET v_user_id=NULL;
          SET r_result=1;
        END IF;
      END WHILE;
      IF v_cycle=0 THEN
        DELETE FROM VTpending WHERE issue_id=p_issue_id AND from_user_id=p_user_id;
        SELECT row_count() INTO v_affected;
	IF v_affected>0 THEN
  	  SET r_result=2;
	END IF;
        UPDATE VTdelegate SET delegate_to=p_to_user_id WHERE issue_id=p_issue_id AND user_id=p_user_id;
        UPDATE VTdelegate SET strength=strength+v_strength WHERE issue_id=p_issue_id AND user_id IN (SELECT idfrom FROM temp_path);
      END IF;
    ELSE
      SET r_result=3;
      DELETE FROM VTpending WHERE issue_id=p_issue_id AND from_user_id=p_user_id;
      SELECT row_count() INTO v_affected;
      IF v_affected>0 THEN
        SET r_result=4;
      END IF;
      INSERT INTO VTpending (issue_id, from_user_id, to_user_id, updated) VALUES (p_issue_id, p_user_id, p_to_user_id, NOW());
    END IF;
    SET r_steps_to_top=v_counter;
  END IF;
END//
