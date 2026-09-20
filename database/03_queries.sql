-- 1. Display all buildings
SELECT * FROM building;


-- 2. Display failed inspections
SELECT *
FROM inspection
WHERE status = 'FAIL';


-- 3. Display HIGH and CRITICAL violations
SELECT violation_type, severity, status
FROM violation
WHERE severity IN ('HIGH', 'CRITICAL');


-- 4. Display buildings with inspection details
SELECT 
    b.building_name,
    i.inspection_date,
    i.status
FROM building b
JOIN inspection i
ON b.building_id = i.building_id;


-- 5. Display inspector and inspection details
SELECT
    b.building_name,
    ins.inspector_name,
    i.inspection_date,
    i.status
FROM building b
JOIN inspection i
ON b.building_id = i.building_id
JOIN inspector ins
ON i.inspector_id = ins.inspector_id;


-- 6. Display violations for each building
SELECT
    b.building_name,
    v.violation_type,
    v.severity,
    v.status
FROM building b
JOIN inspection i
ON b.building_id = i.building_id
JOIN violation v
ON i.inspection_id = v.inspection_id;


-- 7. Count violations for each building
SELECT 
    b.building_name,
    COUNT(v.violation_id) AS total_violations
FROM building b
JOIN inspection i
    ON b.building_id = i.building_id
JOIN violation v
    ON i.inspection_id = v.inspection_id
GROUP BY b.building_name;


-- 8. Count violations by severity
SELECT 
    severity,
    COUNT(*) AS total
FROM violation
GROUP BY severity;


-- 9. Display open violations
SELECT 
    b.building_name,
    v.violation_type,
    v.severity
FROM building b
JOIN inspection i
    ON b.building_id = i.building_id
JOIN violation v
    ON i.inspection_id = v.inspection_id
WHERE v.status = 'OPEN';


-- 10. Display incomplete corrective actions
SELECT
    b.building_name,
    v.violation_type,
    ca.action_description,
    ca.status
FROM building b
JOIN inspection i
    ON b.building_id = i.building_id
JOIN violation v
    ON i.inspection_id = v.inspection_id
JOIN corrective_action ca
    ON v.violation_id = ca.violation_id
WHERE ca.status <> 'COMPLETED';