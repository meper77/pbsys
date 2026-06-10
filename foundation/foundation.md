# foundation.md
<!-- ALL OF THE BELOW IS COMPULSORY -->
Foundation of architecture of NEO-V.TRACK for Vehicle Tracking & Report for Polis Bantuan UiTM Segamat Campus Intranet.

## architecture
<!-- YOU ONLY ABLE TO CHECKLIST. THIS IS DRAFT PLANNING OR CORRECTION THERE IS MAY HAVE MISSING OR COMPLETED PIECES. DONT GUESS BUT ASK WHEN DONT KNOW TO COMPELTE IT. IT MUST WORKING AS INTENDED. BUILD ON LIVE, NOT LOCAL EXCEPT .APK -->

### [login](./login.md)

- [] sign in/up with UiTM google auth (keep manual login)
- [] only allowedlist staff email can sign in as admin or users e.g example@uitm.edu.my, exclude 2023818464@student.uitm.edu.my (Developer) (both full-access)

### [profile](./profile.md)

- [] profile, full name, position, uitm email, phone number, logout

### [home](./home.md)

- [] Welcome to NEO V-TRACK, [logged name]
- [] total number of each type and sum of all type metrics monthly for a year
- [] total number of each type by vehicles in stacked bar charts monthly for a year
- [x] each metrics redirect to its own pages
- [] replace white bg to []() (HOLD FIRST - keep white bg for now)

### [search](./search.md)

- [] the search should like search on [new-report](./foundation/new-report.md) and auto suggest
- [] remove export when searching
- [] previous and next entries hover color consistentcy

### [staff](./staff.md)

- [] the search should like search on [new-report](./foundation/new-report.md) and auto suggest
- [x] select to delete
- [] can import the table with the following [importStaff.xlsx]() in any month or year
- [] can export the table with the following [exportStaff.xlsx]() in any month or year
- [] can generate statistical chart in any month, year or all years
- [] replace current table, have nine columns
- [] table can sort by month, year or all years

| Bil. | NO KENDERAAN | JENIS KENDERAAN | MODEL KENDERAAN | TARIKH AMBIL | NO PEKERJA | NAMA | NO TELEFON | NO SIRI |
| :----: | :----: | :----: | :----: |:----: | :----: | :----: | :----: | :----: |
| NUMBER | NO PLAT | VEHICLE TYPE | CARS MODEL | CALENDAR | ID | FULL NAME | PHONE | RECYCLE INCREMENT NUMBER |

- [] rules: all column uppercase
- [] recycle increment number reset per year
- [] register, fill any blank will auto suggest and auto fill the rest of the blank
- [] update, fill any blank will auto suggest and auto fill the rest of the blank
- [] unique created of combination all column for a row, a user have many vehicle and a vehicle can have many user
- [] vehicle type two only (KERETA | MOTOSIKAL)
- [] remove active/inactive & current data

### [student](./student.md)

- [] the search should like search on [new-report](./foundation/new-report.md) and auto suggest
- [x] select to delete
- [] can import the table with the following [importStaff.xlsx]() in any month or year
- [] can export the table with the following [exportStaff.xlsx]() in any month or year
- [] can generate statistical chart in any month, year or all years
- [] replace current table, have nine columns
- [] table can sort by month, year or all years

| Bil. | NO KENDERAAN | JENIS KENDERAAN | MODEL KENDERAAN | TARIKH AMBIL | NO PELAJAR | NAMA | NO TELEFON | NO SIRI |
| :----: | :----: | :----: | :----: |:----: | :----: | :----: | :----: | :----: |
| NUMBER | NO PLAT | VEHICLE TYPE | CARS MODEL | CALENDAR | ID | FULL NAME | PHONE | RECYCLE INCREMENT NUMBER |

- [] rules: all column uppercase
- [] register, fill any blank will auto suggest and auto fill the rest of the blank
- [] update, fill any blank will auto suggest and auto fill the rest of the blank
- [] unique created of combination all column for a row, a user have many vehicle and a vehicle can have many user
- [] vehicle type two only (KERETA | MOTOSIKAL)
- [] remove active/inactive & current data

### [visitor](./visitor.md)
<!-- HOLD FIRST TO CONFIRM THE TABLE-->
- [] the search should like search on [new-report](./foundation/new-report.md) and auto suggest
- [x] select to delete
- [] can import the table with the following [importStaff.xlsx]() in any month or year
- [] can export the table with the following [exportStaff.xlsx]() in any month or year
- [] can generate statistical chart in any month, year or all years
- [] replace current table, have nine columns
- [] table can sort by month, year or all years

| Bil. | NO KENDERAAN | JENIS KENDERAAN | MODEL KENDERAAN | TARIKH AMBIL | NO PELAJAR | NAMA | NO TELEFON | NO SIRI |
| :----: | :----: | :----: | :----: |:----: | :----: | :----: | :----: | :----: |
| NUMBER | NO PLAT | VEHICLE TYPE | CARS MODEL | CALENDAR | ID | FULL NAME | PHONE | RECYCLE INCREMENT NUMBER |

- [] rules: all column uppercase
- [] register, fill any blank will auto suggest and auto fill the rest of the blank
- [] update, fill any blank will auto suggest and auto fill the rest of the blank
- [] unique created of combination all column for a row, a user have many vehicle and a vehicle can have many user
- [] vehicle type two only (KERETA | MOTOSIKAL)
- [] remove active/inactive & current data

### [contractor](./contractor.md)
<!-- HOLD FIRST TO CONFIRM THE TABLE-->
- [] the search should like search on [new-report](./foundation/new-report.md) and auto suggest
- [x] select to delete
- [] can import the table with the following [importStaff.xlsx]() in any month or year
- [] can export the table with the following [exportStaff.xlsx]() in any month or year
- [] can generate statistical chart in any month, year or all years
- [] replace current table, have nine columns
- [] table can sort by month, year or all years

| Bil. | NO KENDERAAN | JENIS KENDERAAN | MODEL KENDERAAN | TARIKH AMBIL | NO PELAJAR | NAMA | NO TELEFON | NO SIRI |
| :----: | :----: | :----: | :----: |:----: | :----: | :----: | :----: | :----: |
| NUMBER | NO PLAT | VEHICLE TYPE | CARS MODEL | CALENDAR | ID | FULL NAME | PHONE | RECYCLE INCREMENT NUMBER |

- [] rules: all column uppercase
- [] register, fill any blank will auto suggest and auto fill the rest of the blank
- [] update, fill any blank will auto suggest and auto fill the rest of the blank
- [] unique created of combination all column for a row, a user have many vehicle and a vehicle can have many user
- [] vehicle type two only (KERETA | MOTOSIKAL)
- [] remove active/inactive & current data

### [users](./users.md)

- [] allowedlist staff email can sign in as admin or users
- [] admin add allowed staff email to sign in as users
- [] admin control users access via permission control
- [] replace current table

| NO. | FULL NAME | POSITION | LAST ONLINE | PERMISSION CONTROL |
| :----: | :----: | :----: | :----: |:----: |
| NUMBER | FULL NAME | POSITION | TIME | CHECKBOX |

- [] remove auto delete user when inactive after a year
- [] replace with select to delete like on [staff](./foundation/staff.md)


### [admin](./admin.md)

- [] allowedlist staff email can sign in as admin or users
- [] admin add allowed staff email to sign in as admin
- [] replace current table

| NO. | FULL NAME | POSITION | LAST ONLINE |
| :----: | :----: | :----: | :----: |
| NUMBER | FULL NAME | POSITION | TIME |

- [] when there is an account only it cant be delete
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

- [] remove import pages because have dedicated import/export


### [language](./language.md)

- [] multi-language English & Malay
- [] Professional words for both

### [design](./design.md)

- [x] keep current design

## [completing](./completing)

- [] write pipeline for each arhcitecture component in markdown 
- [] finalize/verify/test/diagnose live fullstack web components end-to-end
- [] commit, push, & deploy with CI/CD (local runnner)
- [] native app of the latest live web
- [] finalize/verify/test/diagnose fullstack app components end-to-end
- [] save the .APK locally

## [security](./security.md)

- [] security hardening both live web and app
- [] commit, push, & deploy with CI/CD (local runnner) if needed
