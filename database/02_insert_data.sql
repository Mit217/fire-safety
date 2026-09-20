-- BUILDING

INSERT INTO building VALUES
(1, 'Green Valley Apartments', 'Kochi', 'Residential', 5);

INSERT INTO building VALUES
(2, 'City Mall', 'Thrissur', 'Commercial', 3);

INSERT INTO building VALUES
(3, 'Sunrise Hospital', 'Ernakulam', 'Hospital', 6);


-- INSPECTOR

INSERT INTO inspector VALUES
(101, 'Arun Kumar', '9876543210');

INSERT INTO inspector VALUES
(102, 'Anjali Thomas', '9876543211');

INSERT INTO inspector VALUES
(103, 'Rahul Mathew', '9876543212');


-- INSPECTION

INSERT INTO inspection VALUES
(1001, 1, 101, DATE '2026-09-10', 'PASS');

INSERT INTO inspection VALUES
(1002, 2, 102, DATE '2026-09-12', 'FAIL');

INSERT INTO inspection VALUES
(1003, 3, 103, DATE '2026-09-15', 'FAIL');


-- VIOLATION

INSERT INTO violation VALUES
(2001, 1002, 'Blocked Emergency Exit', 'HIGH', 'OPEN');

INSERT INTO violation VALUES
(2002, 1002, 'Missing Fire Extinguisher', 'CRITICAL', 'IN_PROGRESS');

INSERT INTO violation VALUES
(2003, 1003, 'Faulty Fire Alarm', 'HIGH', 'OPEN');

INSERT INTO violation VALUES
(2004, 1003, 'Improper Wiring', 'MEDIUM', 'COMPLETED');


-- CORRECTIVE ACTION

INSERT INTO corrective_action VALUES
(3001, 2001, 'Clear the emergency exit', 'IN_PROGRESS');

INSERT INTO corrective_action VALUES
(3002, 2002, 'Install a new fire extinguisher', 'IN_PROGRESS');

INSERT INTO corrective_action VALUES
(3003, 2003, 'Repair the fire alarm system', 'PENDING');

INSERT INTO corrective_action VALUES
(3004, 2004, 'Replace damaged wiring', 'COMPLETED');


COMMIT;