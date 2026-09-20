# Smart Fire Safety Compliance Database

A simple DBMS project for managing fire safety inspections, violations, and corrective actions for buildings.

## Project Overview

The Smart Fire Safety Compliance Database stores and manages information about:

- Buildings
- Fire safety inspectors
- Inspections
- Safety violations
- Corrective actions

The system uses **Oracle 19c** as the database backend and **PHP** for the basic web interface.

## Technologies Used

- Oracle Database 19c
- PHP
- OCI8
- XAMPP
- HTML
- CSS
- Git & GitHub

## Database Structure

The database contains five main tables:

### 1. BUILDING

Stores information about buildings.

- `building_id` — Primary Key
- `building_name`
- `address`
- `building_type`
- `floors`

### 2. INSPECTOR

Stores information about safety inspectors.

- `inspector_id` — Primary Key
- `inspector_name`
- `phone`

### 3. INSPECTION

Stores inspection details.

- `inspection_id` — Primary Key
- `building_id` — Foreign Key
- `inspector_id` — Foreign Key
- `inspection_date`
- `status`

### 4. VIOLATION

Stores safety violations identified during inspections.

- `violation_id` — Primary Key
- `inspection_id` — Foreign Key
- `violation_type`
- `severity`
- `status`

### 5. CORRECTIVE_ACTION

Stores actions taken to resolve violations.

- `action_id` — Primary Key
- `violation_id` — Foreign Key
- `action_description`
- `status`

## Database Relationships

```text
BUILDING
   |
   | 1 : M
   v
INSPECTION
   |
   | 1 : M
   v
VIOLATION
   |
   | 1 : M
   v
CORRECTIVE_ACTION

INSPECTOR
   |
   | 1 : M
   v
INSPECTION
