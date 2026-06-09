# foundation.md
<!-- ALL OF THE BELOW IS COMPULSORY -->
Foundation of architecture of NEO-V.TRACK for Vehicle Tracking & Report for Polis Bantuan UiTM Segamat Campus.

## architecture
<!-- YOU ONLY ABLE TO CHECKLIST. THIS IS DRAFT PLANNING OR CORRECTION THERE IS MAY HAVE MISSING OR COMPLETED PIECES. DONT GUESS BUT ASK WHEN DONT KNOW TO COMPELTE IT. IT MUST WORKING AS INTENDED. BUILD ON LIVE, NOT LOCAL EXCEPT .APK -->

### [login](./login.md)

- [] sign in/up with UiTM SMTP (no stored sign in/up locally)
- [] can recovered password via SMTP
- [] allowedlist staff email to can sign in/up as admin e.g example@uitm.edu.my exclude 2023818464@student.uitm.edu.my (Developer) (both full-access)
- [] anyone with uitm email can sign in/up as users (full-access except admins, users, and import pages)

### [profile](./profile.md)

**Details**
- [x] can upload profile picture
- [] name, uitm email, phone number, can request account deletion, no change password (because SMTP)

### [home](./home.md)

- [] Welcome to NEO V-TRACK, [logged name]
- [x] total number of staff/student/visitor/contractor and sum vehicles metrics
- [x] each metrics redirect to its own pages
- [] replace white bg to [animated Polis Bantuan UiTM](./assets/animatedPolisBantuanUiTM.md)

### [search](./search.md)

- [] the search should like search on [new-report](./foundation/new-report.md) and auto suggest
- [] remove export when searching
- [] previous and next entries hover color consistentcy

### [staff](./staff.md)

- [] the search should like search on [new-report](./foundation/new-report.md) and auto suggest
- [x] select to delete
- [] register, fill any blank will auto suggest and auto fill the rest of the blank
- [] update, fill any blank will auto suggest and auto fill the rest of the blank
- [] unique created of combination plate number + owner name + staff number + phone number + vehicle type + status, to accept duplicate plate number to allow a user have many vehicle
- [] in active can select to delete
- [] in active can be register and update (for testing - Developer)

### [student](./student.md)

- [] the search should like search on [new-report](./foundation/new-report.md) and auto suggest
- [x] select to delete
- [] register, fill any blank will auto suggest and auto fill the rest of the blank
- [] update, fill any blank will auto suggest and auto fill the rest of the blank
- [] unique created of combination plate number + owner name + matric numbers + phone number + vehicle type + status, to accept duplicate plate number to allow a user have many vehicle
- [] in active can select to delete
- [] in active can be register and update (for testing - Developer)

### [visitor](./visitor.md)

- [] the search should like search on [new-report](./foundation/new-report.md) and auto suggest
- [x] select to delete
- [] register, fill any blank will auto suggest and auto fill the rest of the blank
- [] update, fill any blank will auto suggest and auto fill the rest of the blank
- [] unique of combination plate number + owner name + phone number, to accept duplicate plate number
- [] unique created of combination plate number + owner name+ phone number + vehicle type + status, to accept duplicate plate number to to allow a user have many vehicle
- [] in active can select to delete
- [] in active can be register and update (for testing - Developer)

### [contractor](./contractor.md)

- [] the search should like search on [new-report](./foundation/new-report.md) and auto suggest
- [x] select to delete
- [] register, fill any blank will auto suggest and auto fill the rest of the blank
- [] update, fill any blank will auto suggest and auto fill the rest of the blank
- [] unique created of combination plate number + owner name + phone number + vehicle type + status, to accept duplicate plate number to allow a user have many vehicle
- [] in active can select to delete
- [] in active can be register and update (for testing - Developer)

### [users](./users.md)

- [] admin can only delete, cant modify user
- [] the table should have email, phone, name, created only
- [] auto delete user when inactive after a year
- [] replace with select to delete like on [staff](./foundation/staff.md)


### [admin](./admin.md)

- [] admin can delete, but cant modify each other
- [] the table should have email, phone, name, created only
- [] allowlist staff email for to able to sign in/up, when there is an account it cant be delete
- [] replace with select to delete like on [staff](./foundation/staff.md)


### [reports](./reports.md)

- [x] search reports, from/to date
- [x] select to delete
- [x] current table

### [do-report](./do-report.md)

- [x] search with auto suggest & auto fill the blank
- [x] reporter details & owner vehicle details
- [x] location auto-detect
- [] issues : Could not get location: Only secure origins are allowed (see: https://goo.gl/Y0ZkNV).. Please allow location access and retry. Currently http


### [import](./import.md)

- [] when import there is match unqiue data the plate number is renew or register for a year
- [] skip the row when already active & exist
- [] update the .xsls template & dropdown the template column 'Type' and 'Category' & remove column 'Brand' 


### [language](./language.md)

- [] multi-language English & Malay
- [] Professional words for both

### [design](./design.md)

- [x] keep current design

## [completing](./completing)
 
- [] finalize/verify/test/diagnose live fullstack web components end-to-end
- [] commit, push, & deploy with CI/CD (local runnner)
- [] native app of the latest live web
- [] finalize/verify/test/diagnose fullstack app components end-to-end
- [] save the .APK locally

## [security](./security.md)

- [] security hardening both live web and app
- [] commit, push, & deploy with CI/CD (local runnner) if needed
