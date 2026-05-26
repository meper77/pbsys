# minor-upgrade.md

MINOR UPGRADE OF NEO V-TRACK
DONT PRESERVE THE EXISTING CODE THAT BEING TOLD IN [MINOR UPGRADE](#minor-upgrade) UNLESS CONSIDERED WHEN JUST ADDING
WORK IN WORKTREES

**FLOW**
- DEVELOPMENT ON HESTIA
- SYNC FULLSTACK
- SYNC LOCAL, REPO, HESTIA(SFTP) (CI/CD - LOCAL RUNNER)
- DEPLOY
---

## MINOR UPGRADE

### responsive web design

- fluid grids
- flexible media
- media queries
---

### dashboard

- white plain replace with assets []()
---

### search

- search - click to fill, or keep typing for a new data.
- across pages 
- for instance below element:
```
<input type="text" class="input mono" name="plate_number" id="plateInput" autocomplete="off" required="" placeholder="Type to search…" autofocus="">
```
---

### logic

- solves Field 'brand' doesn't have a default value
- solves a user have many vehicles
- solves a vehicle own by many users
- across pages

---

### report

- **objective**: working & multi-select row to delete only
- delete not working
- solve & use the following delete element
```
<button type="submit" class="btn btn-ghost text-danger" id="bulkDeleteBtn" disabled="">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" data-lucide="trash-2" aria-hidden="true" class="lucide lucide-trash-2"><path d="M10 11v6"></path><path d="M14 11v6"></path><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"></path><path d="M3 6h18"></path><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg> Delete selected
      </button>
```
- remove the following delete and its related element
```
<a href="/admin/delete_report.php?id=0" class="btn btn-quiet text-danger" title="Delete" onclick="return confirm('Padam laporan #0?');"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" data-lucide="trash-2" aria-hidden="true" class="lucide lucide-trash-2"><path d="M10 11v6"></path><path d="M14 11v6"></path><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"></path><path d="M3 6h18"></path><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg></a>
```

### delete

- **objective**: multi-select row to delete
- remove the following delete across pages, database and its related element
```
<a class="btn btn-quiet" href="/vehicles/staff/delete.php?id=86" title="Delete" onclick="return confirm('Delete this record?')"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" data-lucide="trash-2" aria-hidden="true" class="lucide lucide-trash-2"><path d="M10 11v6"></path><path d="M14 11v6"></path><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"></path><path d="M3 6h18"></path><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg></a>
```
- replace with solved multi-select row to delete element
```
<button type="submit" class="btn btn-ghost text-danger" id="bulkDeleteBtn" disabled="">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" data-lucide="trash-2" aria-hidden="true" class="lucide lucide-trash-2"><path d="M10 11v6"></path><path d="M14 11v6"></path><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"></path><path d="M3 6h18"></path><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg> Delete selected
      </button>
```
---

### primary key

- remove IC NUMBER across pages and database

**PRIMARY KEY**:
- staff number, staff
- matric number, student
- phone, visitor & contractor
---

### status

- add new table for across staff, student, visitor, contractor pages
- the pages will have two table (active & inactive)
- inactive after a year 
- can be active again when same PLATE & PHONE being upload or register again
---

### import

- .csv to .xsls
- upload, only accept .xsls
- update the template, referred to latest system
- rules: unique when status active & follow template
---

### users & admin

**admin**:
- can only reset their password via SMTP
- can see admin & user list

**user**:
- can only reset their password via SMTP
- cant see admin & user list
---

### view

**admin**:
- all pages

**user**:
- all pages except users, admin, report, import