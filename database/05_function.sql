CREATE OR REPLACE FUNCTION get_violation_count (
    p_building_id NUMBER
)
RETURN NUMBER
IS
    v_count NUMBER;
BEGIN
    SELECT COUNT(v.violation_id)
    INTO v_count
    FROM inspection i
    JOIN violation v
        ON i.inspection_id = v.inspection_id
    WHERE i.building_id = p_building_id;

    RETURN v_count;
END;
/