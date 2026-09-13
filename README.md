# Project-Based Employee Time & Payroll Management System

A Laravel-based web application for managing employees, projects, tasks, time worked, timesheet approvals, and payroll.

The application follows the business flow:

**Employee → Project → Task → Time Worked → Approval → Payroll**

The primary objective is to ensure that payroll is generated from **approved time worked against project tasks**, rather than from a fixed monthly salary.

---

## 1. Technology Stack

| Layer               | Technology                                  |
| ------------------- | ------------------------------------------- |
| Language            | PHP 8.3+                                    |
| Framework           | Laravel 13                                  |
| Admin / UI          | Filament 5                                  |
| Frontend            | Tailwind CSS 4                              |
| Interactive UI      | Livewire / Filament                         |
| Database            | MySQL 8                                     |
| ORM                 | Eloquent                                    |
| Authentication      | Laravel Authentication                      |
| Roles & Permissions | Spatie Laravel Permission + Filament Shield |
| Authorization       | Policies + Permissions                      |
| Validation          | Filament validation + Laravel validation    |
| Business Logic      | Service Layer                               |
| Database Integrity  | Foreign Keys + Indexes + Unique Constraints |
| Transactions        | Laravel `DB::transaction()`                 |
| Testing             | Pest                                        |
| Version Control     | Git / GitHub                                |

---

## 2. Project Objective

This application implements a working MVP for:

* Employee management
* Department and designation management
* Project management
* Project membership
* Task and subtask management
* Task assignment
* Employee timesheets
* Timesheet submission
* Timesheet approval/rejection
* Payroll generation
* Payroll calculation
* Payroll finalization
* Payroll history
* Dashboard and operational reporting

The application focuses on **business correctness, authorization, database integrity and end-to-end workflow** rather than implementing unnecessary enterprise features.

---

## 3. Core Business Flow

```text
Employee
   ↓
Project
   ↓
Task
   ↓
Time Entry
   ↓
Employee submits
   ↓
Project Manager approves/rejects
   ↓
Approved Time
   ↓
Payroll Calculation
   ↓
Payroll Finalization
```

For example:

```text
Hourly Rate       = ₹250
Worked Hours      = 32
Approved Hours    = 30

Payroll:
30 × ₹250 = ₹7,500
```

Only the **approved 30 hours** are considered for payroll.

---

## 4. Features

### 4.1 Employee Management

Administrators can manage employees including:

* Employee ID
* First name
* Last name
* Department
* Designation
* Joining date
* Manager
* Hourly rate
* Active/inactive status
* Associated login account

Employees can be deactivated without physically deleting historical records.

Soft deletion is used where appropriate so historical payroll and time records remain protected.

---

## 5. Department & Designation Management

Departments and designations are maintained as separate entities.

Example:

```text
Department:
- Development
- QA
- HR
- Management

Designation:
- Software Developer
- Senior Developer
- QA Engineer
- Project Manager
```

Employees reference these records through foreign keys.

This avoids storing repeated department/designation strings in employee records and keeps the database normalized.

---

## 6. Project Management

Projects contain:

* Project code
* Project name
* Description
* Project manager
* Start date
* End date
* Status
* Optional budget

Supported project statuses:

```text
Planning
Active
Completed
Cancelled
```

Employees can be assigned as project members.

Project membership is maintained separately through the `project_members` table.

This allows the application to determine whether an employee is actually allowed to work on a project.

---

## 7. Task Management

Tasks belong to projects.

A task supports:

* Title
* Description
* Priority
* Status
* Due date
* Estimated hours
* Project
* Parent task
* Assigned employees

Supported priorities:

```text
Low
Medium
High
Urgent
```

Supported statuses:

```text
To Do
In Progress
Completed
Cancelled
```

### Subtasks

Tasks can optionally have a parent task.

For example:

```text
Project: E-Commerce Application

Task:
  Develop Checkout

Subtasks:
  - Create Cart API
  - Create Address API
  - Implement Payment Flow
  - Add Order Confirmation
```

---

## 8. Task Assignment

Employees can only be assigned to tasks belonging to projects where they are project members.

The application therefore does not rely only on an employee ID received from the browser.

The server verifies the assignment and project membership before allowing time to be recorded.

This prevents scenarios such as:

```text
Employee A
     ↓
Attempts to record time
     ↓
Task assigned only to Employee B
     ↓
Request rejected
```

---

## 9. Time Tracking / Timesheets

The system uses manual time entries rather than requiring a live start/stop timer.

A time entry contains:

* Employee
* Project
* Task
* Work date
* Start time
* End time
* Break minutes
* Working minutes
* Entry type
* Status
* Submission information
* Approval information
* Rejection information

Entry types:

```text
Regular
Overtime
```

Statuses:

```text
Draft
Submitted
Approved
Rejected
Cancelled
```

---

## 10. Working Hours Calculation

Working minutes are calculated from:

```text
End Time - Start Time - Break
```

Example:

```text
Start       = 09:00
End         = 18:00
Break       = 60 minutes

Total       = 9 hours
Break       = 1 hour

Working     = 8 hours
```

Therefore:

```text
working_minutes = 480
```

The application stores working minutes as an integer value.

This avoids relying on floating-point hour calculations for the primary time representation.

---

## 11. Overlapping Time Entries

Employees cannot claim the same working period against multiple tasks.

Example:

```text
Task A
09:00 → 12:00

Task B
11:00 → 14:00
```

These entries overlap between:

```text
11:00 → 12:00
```

The system rejects conflicting time entries for the same employee.

This protects payroll integrity and prevents double-counting of working time.

---

## 12. Timesheet Lifecycle

A normal timesheet follows:

```text
Draft
  ↓
Submitted
  ↓
Approved
```

Or:

```text
Draft
  ↓
Submitted
  ↓
Rejected
  ↓
Edited
  ↓
Resubmitted
  ↓
Approved
```

Cancelled entries are not eligible for payroll.

---

## 13. Timesheet Approval

Project managers can approve or reject submitted time entries for their assigned projects.

Administrators have elevated authorization.

An employee cannot approve their own timesheet.

Approval actions are stored in a separate approval-history table.

The history records:

* Time entry
* Approver
* Action
* Rejection reason
* Action timestamp

This provides an audit trail of important approval decisions.

---

## 14. Rejection and Resubmission

If a timesheet is rejected, the approver must provide a rejection reason.

Example:

```text
Rejected:
"Recorded 8 hours but task activity indicates approximately
6 hours. Please correct the entry."
```

The employee can then edit the rejected entry and resubmit it.

The previous rejection remains available in the approval history.

---

## 15. Payroll

Payroll is generated for:

* Employee
* Payroll period

The payroll calculation considers only approved time entries.

Example:

```text
Payroll Period:
01 Sep 2026 → 30 Sep 2026

Employee:
Rahul

Approved Regular:
160 hours

Approved Overtime:
10 hours

Hourly Rate:
₹250

OT Multiplier:
1.5
```

---

## 16. Payroll Calculation

### Regular Pay

```text
Regular Pay =
Approved Regular Hours × Hourly Rate
```

Example:

```text
160 × ₹250
= ₹40,000
```

### Overtime Pay

```text
Overtime Pay =
Approved Overtime Hours × Hourly Rate × OT Multiplier
```

Example:

```text
10 × ₹250 × 1.5
= ₹3,750
```

### Gross Pay

```text
Gross Pay =
Regular Pay
+ Overtime Pay
+ Adjustments
```

### Net Pay

```text
Net Pay =
Gross Pay
- Deductions
```

---

## 17. Payroll Example

Given:

```text
Employee             = EMP001
Hourly Rate           = ₹250
Regular Approved      = 32 hours
Overtime Approved     = 4 hours
OT Multiplier         = 1.5
Deductions            = ₹500
```

Calculation:

```text
Regular Pay
32 × ₹250
= ₹8,000
```

```text
Overtime Pay
4 × ₹250 × 1.5
= ₹1,500
```

```text
Gross Pay
₹8,000 + ₹1,500
= ₹9,500
```

```text
Net Pay
₹9,500 - ₹500
= ₹9,000
```

Expected result:

```text
Regular Pay    ₹8,000
Overtime Pay   ₹1,500
Gross Pay      ₹9,500
Deductions     ₹500
Net Pay        ₹9,000
```

---

## 18. Payroll and Unapproved Time

The following time entries are excluded:

```text
Draft
Submitted
Rejected
Cancelled
```

Only:

```text
Approved
```

entries are eligible for payroll.

Therefore:

```text
Worked:          40 hours
Approved:        32 hours
Rejected:         4 hours
Pending:          4 hours

Payroll Hours:   32 hours
```

The system does not calculate payroll from raw submitted hours.

---

## 19. Payroll Rate Snapshot

A critical requirement is preserving historical payroll values.

Suppose:

```text
January Rate = ₹250/hour
```

January payroll is finalized.

Later:

```text
Current Rate = ₹300/hour
```

The finalized January payroll must still use:

```text
₹250/hour
```

The system therefore stores the applicable hourly rate against each finalized payroll time entry.

Historical payroll does not depend on the employee's current hourly rate after finalization.

---

## 20. Payroll Finalization

Payroll calculation and payroll finalization are separate concepts.

A draft payroll can be calculated and recalculated.

Once finalized:

```text
DRAFT
  ↓
CALCULATED
  ↓
FINALIZED
```

A finalized payroll becomes historical data.

Finalization is performed inside a database transaction.

Conceptually:

```php
DB::transaction(function () {
    // Lock payroll
    // Find eligible approved entries
    // Create payroll time-entry snapshots
    // Calculate totals
    // Mark payroll finalized
});
```

This prevents partially finalized payroll records.

---

## 21. Payroll Time Entry Snapshot

When payroll is finalized, the system records which time entries were included.

The payroll time-entry record contains:

```text
Payroll
Time Entry
Working Minutes
Entry Type
Hourly Rate
Amount
```

This provides historical traceability.

For example:

```text
Payroll #15
   ├── Time Entry #101 → 480 minutes → ₹2,000
   ├── Time Entry #102 → 360 minutes → ₹1,500
   └── Time Entry #103 → 120 minutes → ₹750
```

This makes it possible to identify exactly which approved work contributed to the payroll.

---

## 22. Duplicate Payroll Protection

The application prevents duplicate payroll periods for the same employee.

For example, the following is invalid:

```text
Employee: EMP001

Payroll 1:
01 Sep → 30 Sep

Payroll 2:
01 Sep → 30 Sep
```

The application also protects against overlapping payroll periods.

This prevents the same approved time from being paid multiple times.

---

## 23. Payroll With No Approved Hours

If an employee has no eligible approved hours for a payroll period, payroll finalization is rejected.

Example:

```text
Draft        = 10 hours
Submitted    = 5 hours
Rejected     = 3 hours
Cancelled    = 2 hours
Approved     = 0 hours
```

Payable hours:

```text
0
```

The system does not create a misleading finalized payroll containing zero eligible work.

---

## 24. Roles and Access Control

The system uses role-based permissions together with record-level authorization.

### Admin

Administrators can manage:

* Employees
* Departments
* Designations
* Projects
* Project members
* Tasks
* Task assignments
* Time entries
* Payroll
* Rates
* System configuration
* Reports

---

### Project Manager

Project managers can:

* Manage assigned projects
* Manage tasks for assigned projects
* Assign project members
* Assign tasks
* View project time entries
* Approve submitted timesheets
* Reject submitted timesheets
* View project-related payroll information where permitted

A project manager cannot automatically approve timesheets belonging to another project.

---

### Employee

Employees can:

* View assigned projects
* View assigned tasks
* Enter their own time
* Edit draft/rejected entries
* Submit time entries
* View their own timesheets
* View applicable payroll information

Employees cannot approve their own time.

---

## 25. Authorization Architecture

Authorization is implemented at multiple levels.

```text
Filament UI
    ↓
Permission / Role
    ↓
Policy
    ↓
Service
    ↓
Database
```

### Permissions

Spatie Laravel Permission and Filament Shield are used to manage application capabilities.

Examples:

```text
view employees
create employees
edit employees
delete employees

view projects
create projects
edit projects

view tasks
create tasks
edit tasks

view time entries
create time entries
approve time entries

view payroll
create payroll
finalize payroll
```

### Policies

Policies handle record-specific authorization.

For example:

```text
Can this project manager approve this specific time entry?
```

The answer depends on the project associated with that time entry and its assigned project manager.

This is stronger than checking only:

```text
if user.role === project_manager
```

---

## 26. Server-Side Security

The application does not trust values received from the browser.

For example, a user cannot simply submit:

```text
employee_id = 25
project_id = 100
task_id = 500
hourly_rate = 9999
```

and expect those values to be accepted.

The server verifies:

* Current authenticated user
* Employee ownership
* Project membership
* Task assignment
* Project manager authorization
* Time-entry status
* Payroll state
* Applicable hourly rate
* Payroll period
* Existing payroll records

Payroll values are calculated on the server.

The browser does not determine the final payroll amount.

---

## 27. Database Design

Main database areas:

```text
users
departments
designations
employees

projects
project_members

tasks
task_assignees

time_entries
timesheet_approvals

payrolls
payroll_time_entries

roles
permissions
role_has_permissions
model_has_roles
model_has_permissions
```

The database uses foreign keys, indexes and unique constraints to protect relationships and prevent duplicate records.

---

## 28. Main Relationships

```text
User
  └── Employee

Department
  └── Employees

Designation
  └── Employees

Employee
  ├── Manager
  ├── Projects
  ├── Tasks
  ├── Time Entries
  └── Payrolls

Project
  ├── Project Manager
  ├── Members
  └── Tasks

Task
  ├── Project
  ├── Parent Task
  ├── Subtasks
  ├── Assignees
  └── Time Entries

Time Entry
  ├── Employee
  ├── Project
  ├── Task
  └── Approval History

Payroll
  └── Payroll Time Entries
```

---

## 29. Important Database Constraints

Examples of integrity constraints include:

* Unique employee code
* Unique project code
* Unique employee login association
* Unique project membership
* Unique task assignment
* Foreign keys between related entities
* Indexed lookup fields
* Payroll/time-entry relationships
* Unique payroll time-entry inclusion

These constraints provide a second layer of protection in addition to application validation.

---

## 30. Soft Deletes

Soft deletes are used for entities where historical records must be retained.

For example, deactivating or removing an employee should not destroy:

* Previous time entries
* Payroll records
* Approval history
* Historical relationships

This is particularly important for payroll history.

---

## 31. Architecture

The application follows a layered architecture:

```text
┌──────────────────────────────┐
│       Filament / UI          │
└──────────────┬───────────────┘
               ↓
┌──────────────────────────────┐
│ Roles / Permissions / Shield │
└──────────────┬───────────────┘
               ↓
┌──────────────────────────────┐
│           Policies           │
└──────────────┬───────────────┘
               ↓
┌──────────────────────────────┐
│       Service Layer          │
└──────────────┬───────────────┘
               ↓
┌──────────────────────────────┐
│      Eloquent Models         │
└──────────────┬───────────────┘
               ↓
┌──────────────────────────────┐
│          MySQL 8             │
└──────────────────────────────┘
```

---

## 32. Why a Service Layer?

Important business operations are not placed directly inside Filament resources.

For example, payroll calculation involves:

1. Finding approved time entries
2. Checking the payroll period
3. Separating regular and overtime
4. Applying the hourly rate
5. Applying overtime multiplier
6. Applying adjustments
7. Applying deductions
8. Preventing duplicate inclusion
9. Preserving historical rate information
10. Finalizing inside a transaction

This logic belongs in a service rather than inside a UI form.

The same approach is used for timesheet approval.

---

## 33. Main Services

The application uses dedicated service classes for important workflows.

Examples include:

```text
TimeEntryApprovalService
PayrollService
DashboardService
```

These services contain business operations that require more than simple CRUD.

---

## 34. Time Entry Approval Logic

The approval workflow conceptually performs:

```text
Receive Time Entry
       ↓
Lock Record
       ↓
Check Status = Submitted
       ↓
Check Approver Authorization
       ↓
Approve / Reject
       ↓
Create Approval History
       ↓
Synchronize Payroll
```

An employee attempting to approve their own entry is rejected.

A project manager can approve only entries for their assigned project.

---

## 35. Payroll Calculation Logic

Payroll calculation conceptually performs:

```text
Payroll Period
      ↓
Find Approved Time Entries
      ↓
Separate Regular / Overtime
      ↓
Calculate Regular Amount
      ↓
Calculate Overtime Amount
      ↓
Apply Adjustments
      ↓
Apply Deductions
      ↓
Calculate Gross
      ↓
Calculate Net
```

Money calculations are handled using integer cents internally to reduce floating-point calculation issues.

---

## 36. Testing

The project uses **Pest** for automated tests.

The important business rules are tested rather than only testing simple CRUD screens.

Examples include:

```text
Time entry creation
Time overlap prevention
Task assignment authorization
Project membership authorization
Timesheet submission
Timesheet approval
Timesheet rejection
Rejection reason
Resubmission
Payroll calculation
Overtime calculation
Deductions
Adjustments
Historical rate snapshot
Duplicate payroll prevention
Finalized payroll protection
Exclusion of rejected/pending/cancelled entries
```

Run the complete test suite with:

```bash
php artisan test
```

---

## 37. Demo Data

The application contains seeded demonstration data covering the main workflow.

The demo data includes:

* Employees
* Departments
* Designations
* Project managers
* Projects
* Project members
* Tasks
* Subtasks
* Task assignments
* Approved time entries
* Submitted time entries
* Rejected time entries
* Draft time entries
* Cancelled time entries
* Regular time
* Overtime
* Payroll records
* Payroll approval/history information

This allows the application to be demonstrated without manually creating every record.

---

## 38. Demo Credentials

The seeded demo environment contains the following accounts.

### Administrator

```text
Email: admin@timetrack.test
Password: password
```

### Project Manager

```text
Email: amit.pm@timetrack.test
Password: password
```

### Project Manager

```text
Email: priya.lead@timetrack.test
Password: password
```

### Employee

```text
Email: rahul@timetrack.test
Password: password
```

### Employee

```text
Email: neha@timetrack.test
Password: password
```

These credentials are intended for local/demo evaluation only and should not be used as production credentials.

---

## 39. Requirements

Before installing the project, make sure the environment contains:

```text
PHP 8.3+
Composer
MySQL 8+
Node.js / npm
Git
```

Recommended PHP extensions include the normal Laravel requirements such as:

```text
BCMath
Ctype
Fileinfo
JSON
Mbstring
OpenSSL
PDO
PDO_MySQL
Tokenizer
XML
```

---

## 40. Installation

Clone the repository:

```bash
git clone <https://github.com/Dilipw/TimeTrack>
cd <app>
```

Install PHP dependencies:

```bash
composer install
```

Install frontend dependencies:

```bash
npm install
```

---

## 41. Environment Configuration

Create the environment file:

```bash
cp .env.example .env
```

On Windows PowerShell, you can use:

```powershell
Copy-Item .env.example .env
```

Generate the application key:

```bash
php artisan key:generate
```

Configure the database in `.env`.

Example:

```env
APP_NAME="TimeTrack"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=timetrack
DB_USERNAME=root
DB_PASSWORD=

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
```

Adjust the MySQL credentials according to the local environment.

---

## 42. Database Setup Using SQL Dump

A complete database dump is included in:

```text
database/db_file.sql
```

For evaluation, the database can be restored directly from this SQL file.

**Running Laravel migrations is not required when using the provided `db_file.sql` dump.**

Create an empty MySQL database first:

```sql
CREATE DATABASE timetrack
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;
```

Then import:

```text
database/db_file.sql
```

For example:

```bash
mysql -u root -p timetrack < database/db_file.sql
```

On Windows, if the MySQL executable is not in PATH, import the SQL file through MySQL Workbench or phpMyAdmin.

After importing the dump, make sure `.env` points to the same database:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=timetrack
DB_USERNAME=root
DB_PASSWORD=
```

---

## 43. Important: SQL Dump vs Migrations

The repository contains Laravel migrations because the application follows Laravel's normal database architecture.

However, for a quick evaluator setup, the provided SQL dump is the recommended path.

### Evaluator setup

```text
Clone project
    ↓
composer install
    ↓
npm install
    ↓
Create .env
    ↓
php artisan key:generate
    ↓
Create MySQL database
    ↓
Import database/db_file.sql
    ↓
npm run build
    ↓
php artisan serve
    ↓
Login
```

There is no requirement to run:

```bash
php artisan migrate
```

when the provided `database/db_file.sql` has already been imported.

This makes the project easier to evaluate quickly.

---

## 44. Frontend Build

For a production-style local build:

```bash
npm run build
```

For development with Vite:

```bash
npm run dev
```

---

## 45. Start the Application

Run:

```bash
php artisan serve
```

The application will normally be available at:

```text
http://127.0.0.1:8000
```

The Filament panel is:

```text
/user
```

Therefore:

```text
http://127.0.0.1:8000/user
```

---

## 46. Recommended First Login

For complete system administration, login using:

```text
admin@timetrack.test
```

```text
password
```

Then the administrator can inspect:

```text
Employees
Projects
Tasks
Time Entries
Approvals
Payroll
Reports / Dashboard
```

---

## 47. Recommended Demo Workflow

To demonstrate the complete business flow:

### Step 1 — Employee

Open employee management and verify:

```text
Employee
Department
Designation
Manager
Hourly Rate
Status
```

### Step 2 — Project

Open projects and verify:

```text
Project
Project Manager
Members
Start Date
Status
Budget
```

### Step 3 — Task

Open a project task and verify:

```text
Task
Assignee
Priority
Status
Estimated Hours
Approved Actual Hours
```

### Step 4 — Time Entry

Create or inspect a time entry:

```text
Employee
Project
Task
Work Date
Start
End
Break
Working Hours
```

### Step 5 — Submit

Submit a draft time entry.

Status becomes:

```text
Submitted
```

### Step 6 — Approval

Login as the assigned project manager.

Approve the submitted time entry.

Status becomes:

```text
Approved
```

### Step 7 — Payroll

Generate or inspect payroll for the employee.

Only approved time should be included.

### Step 8 — Finalize

Finalize payroll.

The system stores the historical time/rate/amount snapshot.

---

## 48. Rejection Workflow Demonstration

A second useful demonstration is:

```text
Employee
   ↓
Create Time Entry
   ↓
Submit
   ↓
Project Manager
   ↓
Reject + Reason
   ↓
Employee edits
   ↓
Resubmit
   ↓
Project Manager approves
```

This demonstrates that the application handles more than a simple happy-path CRUD workflow.

---

## 49. Important Edge Cases Covered

The application is designed around the following cases:

### Overlapping Time

An employee cannot record overlapping working periods against multiple tasks.

### Unauthorized Task

An employee cannot submit time against a task they are not assigned to.

### Unauthorized Project

An employee cannot submit project time where they are not an active project member.

### Self Approval

An employee cannot approve their own timesheet.

### Rejected Entry

A rejected time entry contains a rejection reason and can be corrected and resubmitted.

### Approved Entry

Approved entries are not treated as freely editable draft records.

### Historical Rate

Changing an employee's current hourly rate does not modify finalized payroll history.

### Duplicate Payroll

The same employee cannot have overlapping payroll periods.

### Pending Time

Submitted/pending time is not included in payroll.

### Rejected Time

Rejected time is not included in payroll.

### Cancelled Time

Cancelled time is not included in payroll.

### Overtime

Regular and overtime hours are calculated separately.

### Closed Projects

Project/task status does not bypass existing approval and payroll rules.

---

## 50. Assumptions

The following assumptions were made to keep the assignment within MVP scope.

### Payroll

This is an **hourly-work-based payroll system**.

It does not implement statutory payroll calculations.

The project does not attempt to calculate:

* Income tax
* PF
* ESI
* Professional tax
* Benefits
* Leave encashment
* Bonuses based on external payroll rules
* Government payroll compliance

These can be added as future modules.

---

### Overtime

Overtime is represented as a separate time-entry type:

```text
regular
overtime
```

The default overtime multiplier is:

```text
1.50
```

The multiplier can be treated as a payroll configuration/input rather than assuming that all organizations use the same overtime policy.

---

### Time Tracking

The MVP uses manual timesheet entry.

A live attendance/start-stop timer is not required for the assignment.

---

### Attendance

Attendance hardware and biometric integration are outside the scope.

---

### Leave

Leave management is outside the scope.

---

### Multi-Currency

The current MVP assumes a single currency and does not implement multi-currency payroll.

---

### Accounting

No accounting software integration is included.

---

### Notifications

Email/SMS/push notification workflows are not required for the core assignment.

---

### API

The application is primarily a server-rendered Filament application.

A public REST API is not required for the assignment.

---

## 51. What Is Intentionally Not Included

To keep the MVP focused, the following are intentionally excluded:

```text
React SPA
Mobile application
AI features
Redis
Queue infrastructure
Biometric attendance
Leave management
Tax engine
PF / ESI calculations
Multi-currency
Accounting integrations
External payroll integrations
Complex attendance rules
Public API
Microservices
```

These features are not required to demonstrate the core business flow.

---

## 52. Why Filament Was Used

Filament provides a strong admin interface on top of Laravel, Livewire and Tailwind.

It allows the application to focus development effort on:

* Business rules
* Authorization
* Validation
* Database design
* Payroll logic
* Timesheet workflow

instead of spending the majority of the assignment implementing basic administrative UI components manually.

---

## 53. Why Laravel

Laravel provides:

* MVC architecture
* Eloquent ORM
* Authentication
* Validation
* Authorization
* Database transactions
* Migrations
* Factories
* Seeders
* Testing support
* Clean application structure

This makes it appropriate for implementing the requested workflow within the assignment timeframe.

---

## 54. Why MySQL

MySQL provides relational data integrity required by the application.

The domain contains strong relationships:

```text
Employee → Project
Project → Task
Task → Time Entry
Time Entry → Approval
Time Entry → Payroll
```

Foreign keys and indexes are therefore important for maintaining consistency and query performance.

---

## 55. Why Service Layer

The application separates UI concerns from business operations.

For example:

```text
Filament Resource
      ↓
PayrollService
      ↓
Eloquent
      ↓
MySQL
```

This allows payroll rules to be reused from different interfaces without duplicating calculation logic.

---

## 56. Business Rule Summary

The most important rules are:

```text
1. Employees can record time only for valid assigned work.

2. Overlapping time entries for the same employee are rejected.

3. Submitted time requires approval.

4. Employees cannot approve their own entries.

5. Project managers can approve only authorized project entries.

6. Rejected entries require a reason.

7. Rejected entries can be corrected and resubmitted.

8. Only approved time is payroll eligible.

9. Regular and overtime time are calculated separately.

10. Payroll is calculated server-side.

11. Duplicate/overlapping payroll periods are prevented.

12. Finalized payroll cannot be recalculated.

13. Finalized payroll stores historical rate and amount snapshots.

14. Payroll finalization runs inside a database transaction.

15. Historical payroll is not affected by later hourly-rate changes.
```

---

## 57. Project Structure

The relevant Laravel structure is approximately:

```text
app/
├── Enums/
│   ├── ApprovalAction.php
│   ├── EmployeeStatus.php
│   ├── ProjectStatus.php
│   ├── TaskPriority.php
│   ├── TaskStatus.php
│   ├── TimeEntryStatus.php
│   └── TimeEntryType.php
│
├── Filament/
│   ├── Resources/
│   └── Widgets/
│
├── Models/
│   ├── Department.php
│   ├── Designation.php
│   ├── Employee.php
│   ├── Payroll.php
│   ├── PayrollTimeEntry.php
│   ├── Project.php
│   ├── ProjectMember.php
│   ├── Task.php
│   ├── TimeEntry.php
│   ├── TimesheetApproval.php
│   └── User.php
│
├── Policies/
│
└── Services/
    ├── DashboardService.php
    ├── PayrollService.php
    └── TimeEntryApprovalService.php

database/
├── factories/
├── migrations/
├── seeders/
│   ├── DatabaseSeeder.php
│   ├── DemoDataSeeder.php
│   └── ShieldSeeder.php
└── db_file.sql

tests/
├── Feature/
└── Pest.php
```

---

## 58. Development Commands

Install dependencies:

```bash
composer install
npm install
```

Clear application caches:

```bash
php artisan optimize:clear
```

Build frontend:

```bash
npm run build
```

Run development server:

```bash
php artisan serve
```

Run tests:

```bash
php artisan test
```

Run only Feature tests:

```bash
php artisan test tests/Feature
```

---

## 59. Database Development

The application includes Laravel migrations for normal development.

For a fresh development environment where the SQL dump is not being used:

```bash
php artisan migrate
```

Demo data can then be seeded through:

```bash
php artisan db:seed
```

For evaluator setup, however, the recommended approach is:

```text
database/db_file.sql
```

because it provides the ready-to-use database structure and demonstration data without requiring migration/seed commands.

---

## 60. Evaluation Setup — Quick Version

For an evaluator who wants to run the project quickly:

```bash
git clone <https://github.com/Dilipw/TimeTrack>
cd <app>

composer install
npm install

cp .env.example .env

php artisan key:generate
```

Create a MySQL database:

```sql
CREATE DATABASE timetrack
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;
```

Configure:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=timetrack
DB_USERNAME=root
DB_PASSWORD=
```

Import:

```text
database/db_file.sql
```

Then:

```bash
npm run build
php artisan serve
```

Open:

```text
http://127.0.0.1:8000/user
```

Login:

```text
admin@timetrack.test
password
```

No migration command is required when using the supplied SQL dump.

---

## 61. Testing Strategy

The test suite focuses primarily on business-critical behavior.

The most important test scenarios are:

```text
Employee authorization
Project membership
Task assignment
Time-entry creation
Overlapping time prevention
Timesheet submission
Timesheet approval
Timesheet rejection
Rejection reason
Resubmission
Regular payroll calculation
Overtime payroll calculation
Adjustment calculation
Deduction calculation
Payroll period validation
Duplicate payroll protection
Approved-entry eligibility
Rejected-entry exclusion
Pending-entry exclusion
Cancelled-entry exclusion
Historical hourly-rate snapshot
Finalized payroll immutability
```

The purpose is to verify that the application implements the required business workflow rather than merely proving that screens can be opened.

---

## 62. Production Considerations

This project is an assignment-focused MVP.

Before production deployment, additional infrastructure and controls would be appropriate, including:

```text
Production environment variables
Strong user passwords
HTTPS
Production database credentials
Database backups
Centralized logging
Monitoring
Error tracking
Queue configuration where required
Email notifications
Rate limiting
Security headers
Regular dependency updates
Automated deployment
Automated database backup
```

These are intentionally outside the core assignment scope.

---

## 63. Final Design Philosophy

The implementation prioritizes:

```text
Correctness
   ↓
Security
   ↓
Database Integrity
   ↓
Business Logic
   ↓
Maintainability
   ↓
UI/UX
```

The system intentionally avoids adding unnecessary technologies when Laravel, Filament, Livewire, Eloquent and MySQL already solve the required problem.

The main focus is a reliable end-to-end workflow:

```text
Employee
   ↓
Project
   ↓
Task
   ↓
Time Entry
   ↓
Approval
   ↓
Approved Hours
   ↓
Payroll
   ↓
Finalized Historical Payroll
```

This directly addresses the technical assignment's primary evaluation criteria: **business logic, database design, code quality, security, error handling, UI/UX, edge-case handling and end-to-end completeness.**