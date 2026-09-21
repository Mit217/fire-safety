-- 1. Count all violations for a building

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
    JOIN violation v ON i.inspection_id = v.inspection_id
    WHERE i.building_id = p_building_id;

    RETURN v_count;
END;
/

-- 2. Count unresolved violations for a building

CREATE OR REPLACE FUNCTION get_open_violation_count (
    p_building_id NUMBER
)
RETURN NUMBER
IS
    v_open_count NUMBER;
BEGIN
    SELECT COUNT(v.violation_id)
    INTO v_open_count
    FROM inspection i
    JOIN violation v ON i.inspection_id = v.inspection_id
    WHERE i.building_id = p_building_id
      AND v.status <> 'COMPLETED';

    RETURN v_open_count;
END;
/

-- 3. Determine the current risk level of a building

CREATE OR REPLACE FUNCTION get_building_risk_level (
    p_building_id NUMBER
)
RETURN VARCHAR2
IS
    v_critical_count NUMBER := 0;
    v_high_count NUMBER := 0;
    v_total_active NUMBER := 0;
BEGIN
    SELECT COUNT(v.violation_id)
    INTO v_critical_count
    FROM inspection i
    JOIN violation v ON i.inspection_id = v.inspection_id
    WHERE i.building_id = p_building_id
      AND v.severity = 'CRITICAL'
      AND v.status <> 'COMPLETED';

    IF v_critical_count > 0 THEN
        RETURN 'CRITICAL';
    END IF;

    SELECT COUNT(v.violation_id)
    INTO v_high_count
    FROM inspection i
    JOIN violation v ON i.inspection_id = v.inspection_id
    WHERE i.building_id = p_building_id
      AND v.severity = 'HIGH'
      AND v.status <> 'COMPLETED';

    IF v_high_count > 0 THEN
        RETURN 'HIGH';
    END IF;

    SELECT COUNT(v.violation_id)
    INTO v_total_active
    FROM inspection i
    JOIN violation v ON i.inspection_id = v.inspection_id
    WHERE i.building_id = p_building_id
      AND v.status <> 'COMPLETED';

    IF v_total_active > 0 THEN
        RETURN 'MODERATE';
    ELSE
        RETURN 'LOW';
    END IF;
END;
/
