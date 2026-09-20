CREATE OR REPLACE VIEW safety_report AS
SELECT
    b.building_name,
    i.inspection_date,
    i.status AS inspection_status,
    v.violation_type,
    v.severity,
    v.status AS violation_status,
    ca.action_description,
    ca.status AS action_status
FROM building b
JOIN inspection i
    ON b.building_id = i.building_id
LEFT JOIN violation v
    ON i.inspection_id = v.inspection_id
LEFT JOIN corrective_action ca
    ON v.violation_id = ca.violation_id;