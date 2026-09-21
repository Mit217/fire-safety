-- 1. Display all open violations

CREATE OR REPLACE PROCEDURE show_open_violations
IS
BEGIN
    FOR rec IN (
        SELECT b.building_name, v.violation_type, v.severity
        FROM building b
        JOIN inspection i ON b.building_id = i.building_id
        JOIN violation v ON i.inspection_id = v.inspection_id
        WHERE v.status = 'OPEN'
    )
    LOOP
        DBMS_OUTPUT.PUT_LINE(
            rec.building_name || ' - ' ||
            rec.violation_type || ' - ' ||
            rec.severity
        );
    END LOOP;
END;
/

-- 2. Schedule a follow-up inspection

CREATE OR REPLACE PROCEDURE schedule_followup_inspection (
    p_building_id IN NUMBER,
    p_inspector_id IN NUMBER,
    p_days_later IN NUMBER DEFAULT 14
)
IS
    v_new_id NUMBER;
BEGIN
    SELECT NVL(MAX(inspection_id), 0) + 1
    INTO v_new_id
    FROM inspection;

    INSERT INTO inspection (
        inspection_id, building_id, inspector_id, inspection_date, status
    )
    VALUES (
        v_new_id, p_building_id, p_inspector_id,
        SYSDATE + p_days_later, 'PENDING'
    );

    COMMIT;

    DBMS_OUTPUT.PUT_LINE(
        'Follow-up inspection #' || v_new_id ||
        ' scheduled successfully.'
    );
END;
/

-- 3. Print inspection history for a building

CREATE OR REPLACE PROCEDURE print_building_history (
    p_building_id NUMBER
)
IS
    v_name building.building_name%TYPE;
BEGIN
    SELECT building_name
    INTO v_name
    FROM building
    WHERE building_id = p_building_id;

    DBMS_OUTPUT.PUT_LINE(
        '=== Inspection History for: ' || v_name || ' ==='
    );

    FOR rec IN (
        SELECT inspection_id, inspection_date, status
        FROM inspection
        WHERE building_id = p_building_id
        ORDER BY inspection_date DESC
    )
    LOOP
        DBMS_OUTPUT.PUT_LINE(
            'Inspection ID: ' || rec.inspection_id ||
            ' | Date: ' || TO_CHAR(rec.inspection_date, 'YYYY-MM-DD') ||
            ' | Status: ' || rec.status
        );
    END LOOP;
END;
/
