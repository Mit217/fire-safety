-- 1. Mark an inspection as FAILED when a violation is inserted

CREATE OR REPLACE TRIGGER trg_violation_fail
AFTER INSERT ON violation
FOR EACH ROW
BEGIN
    UPDATE inspection
    SET status = 'FAIL'
    WHERE inspection_id = :NEW.inspection_id;
END;
/

-- 2. Automatically complete a violation when its corrective action is completed

CREATE OR REPLACE TRIGGER trg_auto_complete_violation
AFTER UPDATE OF status ON corrective_action
FOR EACH ROW
BEGIN
    IF :NEW.status = 'COMPLETED'
       AND :OLD.status <> 'COMPLETED' THEN

        UPDATE violation
        SET status = 'COMPLETED'
        WHERE violation_id = :NEW.violation_id;

    END IF;
END;
/
