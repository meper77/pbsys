# foundation.md
<!-- ALL OF THE BELOW IS COMPULSORY -->
Foundation of architecture of NEO-V.TRACK for Vehicle Tracking & Report for Polis Bantuan UiTM Segamat Campus.

## architecture
<!-- YOU ONLY ABLE TO CHECKLIST. THIS IS DRAFT PLANNING OR CORRECTION THERE IS MAY HAVE MISSING OR COMPLETED PIECES. DONT GUESS BUT ASK WHEN DONT KNOW TO COMPELTE IT. IT MUST WORKING AS INTENDED. BUILD ON LIVE, NOT LOCAL EXCEPT .APK -->

### [login](./login.md)

- [x] Dont preserve SMTP and replace it with below:
- [] fully sign in with UiTM google auth <!-- code complete (button+callback+JWT verify); awaits OAuth Client ID + trusted HTTPS on live (user's final step) -->
- [x] only allowedlist staff email can sign in as admin or users e.g example@uitm.edu.my , seed for 2023818464@student.uitm.edu.my (Developer) (full-access)
- [x] 2023818464@student.uitm.edu.my (Developer) can bypass login


### [profile](./profile.md)

- [x] profile, full name, position, uitm email, phone number, logout

### [home](./home.md)

- [x] Welcome to NEO V-TRACK, [logged name]
- [x] total number of each type and sum of all type metrics monthly for a year
- [x] total number of each type by vehicles in stacked bar charts monthly for a year
- [x] each metrics redirect to its own pages
- [] replace white bg to []() (HOLD FIRST - keep white bg for now)

### [search](./search.md)

- [x] the search should like search on [do-report](#do-report) and auto suggest
- [x] remove export when searching
- [x] previous and next entries hover color consistentcy

### [staff](./staff.md)

- [x] the search should like search on [do-report](#do-report) and auto suggest
- [x] select to delete
- [x] complete .xlsx [STAF 2026](./assets/STAF%202026.xlsx)
- [x] consistent .xlsx format or template
- [x] download able clean .xlsx template
- [x] can import the table with selected month
- [x] can export the table with selected month
- [x] can print the selected month
- [x] can generate and print statistical chart in selected month, year or all years
- [x] replace current table, have nine columns
- [x] table can sort by month, year or all years

| Bil. | NO KENDERAAN | JENIS KENDERAAN | MODEL KENDERAAN | TARIKH AMBIL | NO PEKERJA | NAMA | NO TELEFON | NO SIRI |
| :----: | :----: | :----: | :----: |:----: | :----: | :----: | :----: | :----: |
| NUMBER | NO PLAT | VEHICLE TYPE | CARS MODEL | CALENDAR | ID | FULL NAME | PHONE | RECYCLE INCREMENT NUMBER |

- [x] rules: all column uppercase
- [x] recycle increment number reset per year
- [x] register, fill any blank will auto suggest and auto fill the rest of the blank
- [x] update, fill any blank will auto suggest and auto fill the rest of the blank
- [x] unique created of combination all column for a row, a user have many vehicle and a vehicle can have many user
- [x] vehicle type two only (KERETA | MOTOSIKAL)
- [x] remove active/inactive & current data

### [student](./student.md)

- [x] the search should like search on [do-report](#do-report) and auto suggest
- [x] select to delete
- [x] complete .xlsx [PELAJAR 2026](./assets/PELAJAR%202026.xlsx)
- [x] consistent .xlsx format or template
- [x] download able clean .xlsx template
- [x] can import the table with selected month
- [x] can export the table with selected month
- [x] can print the selected month
- [x] can generate and print statistical chart in selected month, year or all years
- [x] replace current table, have nine columns
- [x] table can sort by month, year or all years

| Bil. | NO KENDERAAN | JENIS KENDERAAN | MODEL KENDERAAN | TARIKH AMBIL | NO PELAJAR | NAMA | NO TELEFON | NO SIRI |
| :----: | :----: | :----: | :----: |:----: | :----: | :----: | :----: | :----: |
| NUMBER | NO PLAT | VEHICLE TYPE | CARS MODEL | CALENDAR | ID | FULL NAME | PHONE | RECYCLE INCREMENT NUMBER |

- [x] rules: all column uppercase
- [x] register, fill any blank will auto suggest and auto fill the rest of the blank
- [x] update, fill any blank will auto suggest and auto fill the rest of the blank
- [x] unique created of combination all column for a row, a user have many vehicle and a vehicle can have many user
- [x] vehicle type two only (KERETA | MOTOSIKAL)
- [x] remove active/inactive & current data

### [visitor](./visitor.md)
<!-- HOLD FIRST TO CONFIRM THE TABLE-->
- [] the search should like search on [do-report](#do-report) and auto suggest
- [x] select to delete
- [] complete .xlsx []()
- [] consistent .xlsx format or template
- [] download able clean .xlsx template
- [] can import the table with selected month
- [] can export the table with selected month
- [] can print the selected month
- [] can generate and print statistical chart in selected month, year or all years
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

- [x] the search should like search on [do-report](#do-report) and auto suggest
- [x] select to delete
- [x] complete .xlsx [CONTRACTOR 2026](./assets/KONTRAK%202026.xlsx)
- [x] consistent .xlsx format or template
- [x] download able clean .xlsx template
- [x] can import the table with selected month
- [x] can export the table with selected month
- [x] can print the selected month
- [x] can generate and print statistical chart in selected month, year or all years
- [x] replace current table, have 12 columns
- [x] table can sort by month, year or all years

| Bil. | NO SIRI | NAMA | NO. IC | NO KENDERAAN | KENDERAAN | MODEL KENDERAAN | SYARIKAT | NO TELEFON |TARIKH KELUAR PELEKAT | EMAIL | CATATAN |
| :----: | :----: | :----: | :----: |:----: | :----: | :----: | :----: | :----: | :----: | :----: | :----: |
| NUMBER | RECYCLE INCREMENT NUMBER | FULL NAME | ID | PLAT NUMBER | VEHICLE TYPE | VEHICLE MODEL | COMPANY | PHONE | DATE | EMAIL | NOTE |

- [x] rules: all column uppercase
- [x] register, fill any blank will auto suggest and auto fill the rest of the blank
- [x] update, fill any blank will auto suggest and auto fill the rest of the blank
- [x] unique created of combination all column for a row, a user have many vehicle and a vehicle can have many user
- [x] vehicle type two only (KERETA | MOTOSIKAL)
- [x] remove active/inactive & current data

### [alumni](./pesara.md)

- [] the search should like search on [do-report](#do-report) and auto suggest
- [x] select to delete
- [] complete .xlsx [PESARA 2026](./assets/PESARA%202026.xlsx)
- [] consistent .xlsx format or template
- [] download able clean .xlsx template
- [] can import the table with selected month
- [] can export the table with selected month
- [] can print the selected month
- [] can generate and print statistical chart in selected month, year or all years
- [] replace current table, have 10 columns
- [] table can sort by month, year or all years

| Bil. | NO SIRI PELEKAT | NO KENDERAAN | JENIS KENDERAAN | MODEL KENDERAAN | TARIKH AMBIL PELEKAT | NAMA | NO. KP | NO. TELEFON | CATATAN |
| :----: | :----: | :----: | :----: |:----: | :----: | :----: | :----: | :----: | :----: |
| NUMBER | RECYCLE INCREMENT NUMBER | PLAT NUMBER | VEHICLE TYPE | VEHICLE MODEL | DATE | FULL NAME | ID | PHONE | NOTE |

- [] rules: all column uppercase
- [] register, fill any blank will auto suggest and auto fill the rest of the blank
- [] update, fill any blank will auto suggest and auto fill the rest of the blank
- [] unique created of combination all column for a row, a user have many vehicle and a vehicle can have many user
- [] vehicle type two only (KERETA | MOTOSIKAL)
- [] remove active/inactive & current data

### [users](./users.md)

- [x] allowedlist staff email can sign in as admin or users
- [x] admin add allowed staff email to sign in as users
- [x] admin control users access via permission control
- [x] replace current table

| NO. | FULL NAME | POSITION | LAST ONLINE | PERMISSION CONTROL |
| :----: | :----: | :----: | :----: |:----: |
| NUMBER | FULL NAME | POSITION | TIME | CHECKBOX |

- [x] remove auto delete user when inactive after a year
- [x] replace with select to delete like on [staff](#staff)


### [admin](./admin.md)

- [x] allowedlist staff email can sign in as admin or users
- [x] admin add allowed staff email to sign in as admin
- [x] replace current table

| NO. | FULL NAME | POSITION | LAST ONLINE |
| :----: | :----: | :----: | :----: |
| NUMBER | FULL NAME | POSITION | TIME |

- [x] when there is an account only it cant be delete
- [x] replace with select to delete like on [staff](#staff)
 
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

- [x] remove import pages because have dedicated import/export


### [language](./language.md)

- [x] multi-language English & Malay
- [x] Professional words for both

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
- [] commit, push, & deploy with CI/CD (local runnner)
