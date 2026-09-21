-- BUILDING

INSERT INTO building VALUES (1, 'Green Valley Apartments', 'Kochi', 'Residential', 5);
INSERT INTO building VALUES (2, 'City Mall', 'Thrissur', 'Commercial', 3);
INSERT INTO building VALUES (3, 'Sunrise Hospital', 'Ernakulam', 'Hospital', 6);
INSERT INTO building VALUES (4, 'Lakeside Residency', 'Kottayam', 'Residential', 4);
INSERT INTO building VALUES (5, 'Metro Tower', 'Kochi', 'Commercial', 8);
INSERT INTO building VALUES (6, 'St. Marys School', 'Aluva', 'Educational', 3);
INSERT INTO building VALUES (7, 'Central Hospital', 'Kozhikode', 'Hospital', 7);
INSERT INTO building VALUES (8, 'Tech Park', 'Thiruvananthapuram', 'Commercial', 10);
INSERT INTO building VALUES (9, 'Greenwood Apartments', 'Kannur', 'Residential', 6);
INSERT INTO building VALUES (10, 'City Library', 'Kollam', 'Public', 2);

-- INSPECTOR

INSERT INTO inspector VALUES (101, 'Arun Kumar', '9876543210');
INSERT INTO inspector VALUES (102, 'Anjali Thomas', '9876543211');
INSERT INTO inspector VALUES (103, 'Rahul Mathew', '9876543212');
INSERT INTO inspector VALUES (104, 'Vivek Nair', '9876543213');
INSERT INTO inspector VALUES (105, 'Meera Joseph', '9876543214');
INSERT INTO inspector VALUES (106, 'Sanjay Kumar', '9876543215');
INSERT INTO inspector VALUES (107, 'Priya Menon', '9876543216');
INSERT INTO inspector VALUES (108, 'Akhil Thomas', '9876543217');
INSERT INTO inspector VALUES (109, 'Neha Varghese', '9876543218');
INSERT INTO inspector VALUES (110, 'Rohan Mathew', '9876543219');

-- INSPECTION

INSERT INTO inspection VALUES (1001, 1, 101, DATE '2026-09-10', 'PASS');
INSERT INTO inspection VALUES (1002, 2, 102, DATE '2026-09-12', 'FAIL');
INSERT INTO inspection VALUES (1003, 3, 103, DATE '2026-09-15', 'FAIL');
INSERT INTO inspection VALUES (1004, 4, 104, DATE '2026-09-16', 'PASS');
INSERT INTO inspection VALUES (1005, 5, 105, DATE '2026-09-17', 'FAIL');
INSERT INTO inspection VALUES (1006, 6, 106, DATE '2026-09-18', 'PASS');
INSERT INTO inspection VALUES (1007, 7, 107, DATE '2026-09-19', 'FAIL');
INSERT INTO inspection VALUES (1008, 8, 108, DATE '2026-09-20', 'FAIL');
INSERT INTO inspection VALUES (1009, 9, 109, DATE '2026-09-21', 'PASS');
INSERT INTO inspection VALUES (1010, 10, 110, DATE '2026-09-21', 'FAIL');

-- VIOLATION

INSERT INTO violation VALUES (2001, 1002, 'Blocked Emergency Exit', 'HIGH', 'OPEN');
INSERT INTO violation VALUES (2002, 1002, 'Missing Fire Extinguisher', 'CRITICAL', 'IN_PROGRESS');
INSERT INTO violation VALUES (2003, 1003, 'Faulty Fire Alarm', 'HIGH', 'OPEN');
INSERT INTO violation VALUES (2004, 1003, 'Improper Wiring', 'MEDIUM', 'COMPLETED');
INSERT INTO violation VALUES (2005, 1005, 'Blocked Fire Exit', 'HIGH', 'OPEN');
INSERT INTO violation VALUES (2006, 1005, 'Expired Fire Extinguisher', 'MEDIUM', 'OPEN');
INSERT INTO violation VALUES (2007, 1007, 'Faulty Smoke Detector', 'HIGH', 'IN_PROGRESS');
INSERT INTO violation VALUES (2008, 1008, 'Missing Emergency Lighting', 'CRITICAL', 'OPEN');
INSERT INTO violation VALUES (2009, 1010, 'Improper Fire Alarm Wiring', 'MEDIUM', 'COMPLETED');
INSERT INTO violation VALUES (2010, 1010, 'Blocked Emergency Route', 'HIGH', 'OPEN');

-- CORRECTIVE ACTION

INSERT INTO corrective_action VALUES (3001, 2001, 'Clear the emergency exit', 'IN_PROGRESS');
INSERT INTO corrective_action VALUES (3002, 2002, 'Install a new fire extinguisher', 'IN_PROGRESS');
INSERT INTO corrective_action VALUES (3003, 2003, 'Repair the fire alarm system', 'PENDING');
INSERT INTO corrective_action VALUES (3004, 2004, 'Replace damaged wiring', 'COMPLETED');
INSERT INTO corrective_action VALUES (3005, 2005, 'Clear the blocked fire exit', 'IN_PROGRESS');
INSERT INTO corrective_action VALUES (3006, 2006, 'Replace the expired fire extinguisher', 'PENDING');
INSERT INTO corrective_action VALUES (3007, 2007, 'Replace the faulty smoke detector', 'IN_PROGRESS');
INSERT INTO corrective_action VALUES (3008, 2008, 'Install emergency lighting', 'PENDING');
INSERT INTO corrective_action VALUES (3009, 2009, 'Repair the fire alarm wiring', 'COMPLETED');
INSERT INTO corrective_action VALUES (3010, 2010, 'Clear the emergency route', 'IN_PROGRESS');

COMMIT;
