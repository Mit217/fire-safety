CREATE OR REPLACE TRIGGER trg_violation_fail
AFTER INSERT ON violation
FOR EACH ROW
BEGIN
    UPDATE inspection
    SET status = 'FAIL'
    WHERE inspection_id = :NEW.inspection_id;
END;
/