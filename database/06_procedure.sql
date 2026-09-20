CREATE OR REPLACE PROCEDURE show_open_violations
IS
BEGIN
    FOR rec IN (
        SELECT
            b.building_name,
            v.violation_type,
            v.severity
        FROM building b
        JOIN inspection i
            ON b.building_id = i.building_id
        JOIN violation v
            ON i.inspection_id = v.inspection_id
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