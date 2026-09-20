CREATE TABLE building (
    building_id NUMBER PRIMARY KEY,
    building_name VARCHAR2(100) NOT NULL,
    address VARCHAR2(200),
    building_type VARCHAR2(50),
    floors NUMBER
);

CREATE TABLE inspector (
    inspector_id NUMBER PRIMARY KEY,
    inspector_name VARCHAR2(100) NOT NULL,
    phone VARCHAR2(15)
);

CREATE TABLE inspection (
    inspection_id NUMBER PRIMARY KEY,
    building_id NUMBER NOT NULL,
    inspector_id NUMBER NOT NULL,
    inspection_date DATE NOT NULL,
    status VARCHAR2(20) NOT NULL,

    CONSTRAINT fk_inspection_building
        FOREIGN KEY (building_id)
        REFERENCES building(building_id),

    CONSTRAINT fk_inspection_inspector
        FOREIGN KEY (inspector_id)
        REFERENCES inspector(inspector_id),

    CONSTRAINT chk_inspection_status
        CHECK (status IN ('PASS', 'FAIL', 'PENDING'))
);

CREATE TABLE violation (
    violation_id NUMBER PRIMARY KEY,
    inspection_id NUMBER NOT NULL,
    violation_type VARCHAR2(100) NOT NULL,
    severity VARCHAR2(20) NOT NULL,
    status VARCHAR2(20) NOT NULL,

    CONSTRAINT fk_violation_inspection
        FOREIGN KEY (inspection_id)
        REFERENCES inspection(inspection_id),

    CONSTRAINT chk_violation_severity
        CHECK (severity IN ('LOW', 'MEDIUM', 'HIGH', 'CRITICAL')),

    CONSTRAINT chk_violation_status
        CHECK (status IN ('OPEN', 'IN_PROGRESS', 'COMPLETED'))
);

CREATE TABLE corrective_action (
    action_id NUMBER PRIMARY KEY,
    violation_id NUMBER NOT NULL,
    action_description VARCHAR2(200) NOT NULL,
    status VARCHAR2(20) NOT NULL,

    CONSTRAINT fk_action_violation
        FOREIGN KEY (violation_id)
        REFERENCES violation(violation_id),

    CONSTRAINT chk_action_status
        CHECK (status IN ('PENDING', 'IN_PROGRESS', 'COMPLETED'))
);