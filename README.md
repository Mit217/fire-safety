\# Smart Fire Safety Compliance Database



A simple DBMS project for managing fire safety inspections, violations, and corrective actions for buildings.



\## Project Overview



The Smart Fire Safety Compliance Database stores and manages information about:



\- Buildings

\- Fire safety inspectors

\- Inspections

\- Safety violations

\- Corrective actions



The system uses \*\*Oracle 19c\*\* as the database backend and \*\*PHP\*\* for the basic web interface.



\## Technologies Used



\- Oracle Database 19c

\- PHP

\- OCI8

\- XAMPP

\- HTML

\- CSS

\- Git \& GitHub



\## Database Structure



The database contains five main tables:



\### 1. BUILDING



Stores information about buildings.



\- `building\_id` — Primary Key

\- `building\_name`

\- `address`

\- `building\_type`

\- `floors`



\### 2. INSPECTOR



Stores information about safety inspectors.



\- `inspector\_id` — Primary Key

\- `inspector\_name`

\- `phone`



\### 3. INSPECTION



Stores inspection details.



\- `inspection\_id` — Primary Key

\- `building\_id` — Foreign Key

\- `inspector\_id` — Foreign Key

\- `inspection\_date`

\- `status`



\### 4. VIOLATION



Stores safety violations identified during inspections.



\- `violation\_id` — Primary Key

\- `inspection\_id` — Foreign Key

\- `violation\_type`

\- `severity`

\- `status`



\### 5. CORRECTIVE\_ACTION



Stores actions taken to resolve violations.



\- `action\_id` — Primary Key

\- `violation\_id` — Foreign Key

\- `action\_description`

\- `status`



\## Database Relationships



```text

BUILDING

&#x20;  |

&#x20;  | 1 : M

&#x20;  v

INSPECTION

&#x20;  |

&#x20;  | 1 : M

&#x20;  v

VIOLATION

&#x20;  |

&#x20;  | 1 : M

&#x20;  v

CORRECTIVE\_ACTION



INSPECTOR

&#x20;  |

&#x20;  | 1 : M

&#x20;  v

INSPECTION

