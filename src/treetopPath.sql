# TODO:test
# return -3 = cycle to self or bad delegate record (TODO: check this)

DELIMITER //

DROP PROCEDURE IF EXISTS treetopPath //

CREATE PROCEDURE treetopPath
(IN p_issue_id int,
 IN p_user_id bigint,
 OUT r_steps_to_top int,
 OUT r_result int)
BEGIN
  DECLARE v_user_id,v_delegate_to,v_euser_id bigint;
  DECLARE v_strength, v_cycle, v_counter int default 0;
  SET r_result=0;
  DROP TEMPORARY TABLE IF EXISTS temp_results;
  # TODO: add an autoincrement column?
  CREATE TEMPORARY TABLE temp_results(user_id bigint, delegate_to bigint, strength int) engine=memory;
  SELECT user_id,delegate_to,strength FROM VTdelegate WHERE issue_id=p_issue_id AND user_id=p_user_id INTO v_user_id,v_delegate_to,v_strength;
  IF (v_user_id IS NOT NULL) THEN
    WHILE v_user_id IS NOT NULL AND v_counter<1000 DO
      INSERT INTO temp_results VALUES (v_user_id,v_delegate_to,v_strength);
      IF v_delegate_to = p_user_id THEN
        SET v_cycle=1;
	SET r_result=-1;
        SET v_user_id=NULL;
      ELSEIF v_delegate_to IS NOT NULL THEN
        SET v_euser_id=v_user_id;
        SELECT user_id,delegate_to,strength FROM VTdelegate WHERE issue_id=p_issue_id AND user_id=v_delegate_to INTO v_user_id,v_delegate_to,v_strength;
        SET v_counter=v_counter+1;
        IF v_euser_id=v_user_id THEN
          SET r_result=-3;
          SET v_user_id=NULL;
        END IF;
      ELSE
        SET v_user_id=NULL;
	SET r_result=1;
      END IF;
    END WHILE;
  ELSE
    SET r_result=-2;
  END IF;
  SET r_steps_to_top=v_counter;
END//
