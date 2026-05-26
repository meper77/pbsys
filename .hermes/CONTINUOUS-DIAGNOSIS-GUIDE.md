# Continuous UI/UX Diagnosis Loop Documentation

**Date:** May 27, 2026  
**Project:** NEO V-TRACK (pbsys)  
**Purpose:** Continuous monitoring of UI/UX design consistency and form logic via Playwright

---

## Overview

Three diagnostic tools for real-time UI/UX quality assurance:

1. **Regression Loop** (`regression-loop.js`) — Continuous monitoring with configurable intervals
2. **Quick Loop** (`regression-loop-5.js`) — Demo mode with 5 diagnostic passes
3. **Watchdog** (`ui-ux-watchdog.js`) — Background monitoring, alerts only on issues (watchdog pattern)

---

## Tool 1: Regression Loop (Continuous)

### Purpose
Run UI/UX diagnostics repeatedly at configurable intervals (infinite by default).

### Configuration
```javascript
const LOOP_INTERVAL = 60000;  // 60 seconds between runs
const MAX_LOOPS = 0;          // 0 = infinite, N = max iterations
```

### Usage
```bash
# Run continuous loop (60s interval, infinite)
node scripts/regression-loop.js

# Run with timeout (e.g., 5 minutes)
timeout 300 node scripts/regression-loop.js
```

### Output Example
```
🚀 Starting Continuous UI/UX Diagnosis Loop

   Base URL: http://localhost:8000
   Pages monitored: 3
   Interval: 60000ms (60s)
   Max loops: infinite

═══════════════════════════════════════════════════════════════

[26/05/2026, 3:06:59 pm] Loop #1 ✅
   Issues: 0
   ✓ login                - 0 issues
   ✓ forgot_password      - 0 issues
   ✓ reset_password       - 0 issues

[26/05/2026, 3:07:59 pm] Loop #2 ✅
   Issues: 0
   ✓ login                - 0 issues
   ✓ forgot_password      - 0 issues
   ✓ reset_password       - 0 issues
```

### Metrics Tracked
- Bootstrap classes (should be 0)
- Problematic inline styles (should be 0)
- Missing form labels (should be 0)
- Buttons missing .btn class (should be 0)

---

## Tool 2: Quick Loop (5 Iterations)

### Purpose
Quick verification with live feedback on 5 consecutive runs.

### Usage
```bash
node scripts/regression-loop-5.js
```

### Output Example
```
╔════════════════════════════════════════════════════════════╗
║     Continuous UI/UX Design & Logic Diagnosis Loop       ║
╚════════════════════════════════════════════════════════════╝

Running 5 diagnostic loops...
Monitoring: 3 pages (auth pages)
Interval: 3s between loops

[Loop 1/5] Diagnosing... ✅ PASS
[Loop 2/5] Diagnosing... ✅ PASS
[Loop 3/5] Diagnosing... ✅ PASS
[Loop 4/5] Diagnosing... ✅ PASS
[Loop 5/5] Diagnosing... ✅ PASS

╔════════════════════════════════════════════════════════════╗
║              DIAGNOSTIC SUMMARY                           ║
╚════════════════════════════════════════════════════════════╝

Total Loops:        5
✅ Passed:          5/5
Success Rate:       100%
Total Issues Found: 0

🎉 All diagnostic loops passed!
```

### Execution Time
~20-30 seconds for 5 loops (3s interval between checks)

---

## Tool 3: Watchdog (Background Monitoring)

### Purpose
Silent background monitoring that only alerts when issues are detected.

### Watchdog Pattern
- ✅ **Silent by default** — no output if all checks pass
- 🚨 **Alerts on issues** — only reports when problems detected
- 📝 **Persistent log** — `.hermes/ui-ux-alert.log`

### Usage
```bash
# Start watchdog in background
node scripts/ui-ux-watchdog.js &

# Or with nohup for persistent background
nohup node scripts/ui-ux-watchdog.js > /dev/null 2>&1 &
```

### Configuration
```javascript
const CHECK_INTERVAL = 300000; // 5 minutes between checks
```

### Alert Log
Location: `.hermes/ui-ux-alert.log`

Example:
```
[2026-05-27T15:12:34.567Z] 🔍 UI/UX Watchdog started
[2026-05-27T15:17:34.891Z] 🚨 UI/UX ISSUES DETECTED:
[2026-05-27T15:17:34.891Z]    login: Bootstrap class: container
[2026-05-27T15:17:34.891Z]    forgot_password: Inline style: color:red;text-align:center
```

### Process Management
```bash
# Check running watchdog
ps aux | grep ui-ux-watchdog

# Kill watchdog
pkill -f ui-ux-watchdog

# Monitor logs
tail -f .hermes/ui-ux-alert.log
```

---

## Pages Monitored

All three tools monitor the same 3 pages:

| Page | URL | Purpose |
|------|-----|---------|
| Login | `/auth/login.php` | User authentication |
| Forgot Password | `/auth/forgot_password_smtp.php` | Password reset initiation |
| Reset Password | `/auth/reset_password_token.php?token=test` | Token-based password reset |

---

## Design Issues Detected

### 1. Bootstrap Classes
Detects legacy Bootstrap usage (should be 0):
- `.container`, `.col-*`, `.row`
- `.form-control`, `.btn-primary`
- `.alert-danger`, `.card-body`

**Expected:** Only NEO V-TRACK classes (`.auth-card`, `.btn`, `.field`, `.flash`, etc.)

### 2. Problematic Inline Styles
Detects non-variable inline styles (should be 0):
- `style="color: red;"` ❌
- `style="text-align: center;"` ❌
- `style="var(--accent);"` ✅ (CSS variables allowed)

### 3. Missing Form Labels
Detects form inputs without associated labels:
- Input elements must have `id` + `label[for="id"]`
- Or have `aria-label` attribute

### 4. Buttons Without Class
Detects `<button>` elements missing `.btn` class:
- All buttons must use `.btn` class
- Allows `.btn` + modifier classes (`.btn-primary`, `.btn-ghost`, etc.)

---

## Test Reports

### Report Location
`.hermes/regression-reports/`

### Report Format
```json
{
  "loopNumber": 1,
  "timestamp": "2026-05-27T15:06:59.123Z",
  "totalIssues": 0,
  "pages": [
    {
      "name": "login",
      "url": "/auth/login.php",
      "status": "pass",
      "issues": {
        "bootstrapClasses": [],
        "problematicInlineStyles": [],
        "missingLabels": [],
        "noButtonClass": []
      }
    }
  ]
}
```

### Accessing Reports
```bash
# List all reports
ls -lh .hermes/regression-reports/

# View latest report
tail -1 .hermes/regression-reports/loop-*.json | jq .

# Filter by loop number
grep "Loop #1" .hermes/regression-reports/loop-*.json
```

---

## Integration Examples

### 1. CI/CD Pipeline
```yaml
# .github/workflows/ui-ux-check.yml
on: [push, pull_request]
jobs:
  ui-ux-check:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - run: npm install -D playwright @playwright/test
      - run: npx playwright install
      - run: timeout 60 node scripts/regression-loop-5.js
```

### 2. Scheduled Monitoring (Cron)
```bash
# Run every 5 minutes
*/5 * * * * cd /path/to/pbsys && node scripts/regression-loop.js --max-loops 1 >> .hermes/scheduled-checks.log 2>&1
```

### 3. Development Workflow
```bash
# Before committing
node scripts/regression-loop-5.js

# During development (background)
nohup node scripts/ui-ux-watchdog.js > /dev/null 2>&1 &
```

### 4. Staging Deployment
```bash
# Verify staging after deploy
timeout 300 node scripts/regression-loop.js --interval 60000 --max-loops 5
```

---

## Performance Metrics

### Speed per Loop
- **Single page check:** ~2-3 seconds
- **3 pages (full diagnostic):** ~6-9 seconds
- **Quick loop (5 iterations):** ~20-30 seconds

### Resource Usage
- **Memory:** ~150-200 MB per browser instance
- **CPU:** Moderate during page load, minimal during intervals
- **Disk:** ~5-10 KB per report (JSON)

### Scalability
- Currently monitors 3 pages
- Can scale to 10-20 pages with minimal performance impact
- Add pages via `PAGES_TO_AUDIT` array

---

## Troubleshooting

### Watchdog Not Running
```bash
# Check if process exists
ps aux | grep ui-ux-watchdog

# Restart watchdog
pkill -f ui-ux-watchdog
nohup node scripts/ui-ux-watchdog.js > /dev/null 2>&1 &
```

### False Positives
1. **NEO V-TRACK classes detected as Bootstrap**
   - Solution: Update regex to exclude NEO classes
   - Location: See `neoVtrackClasses` regex in scripts

2. **CSS variable inline styles flagged as problematic**
   - Solution: Regex allows `var(...)` syntax
   - Already handled in current implementation

### Performance Issues
1. **Slow page loads**
   - Increase timeout: `timeout: 10000` (10 seconds)
   - Check network availability

2. **Memory leaks**
   - Restart watchdog daily: `crontab -e`
   - Add: `0 0 * * * pkill -f ui-ux-watchdog && nohup node ... &`

---

## Future Enhancements

Potential improvements for expanded monitoring:

- [ ] Screenshot comparisons (visual regression detection)
- [ ] Cross-browser testing (Firefox, Safari, Edge)
- [ ] Performance metrics (page load time, Core Web Vitals)
- [ ] Accessibility audit (axe-core integration)
- [ ] Color contrast checker
- [ ] Mobile touch target validation
- [ ] Email notifications on issues
- [ ] Slack/Discord integration
- [ ] Historical trend analysis
- [ ] A/B testing support

---

## Git Commits

- `c1a6815` — Add continuous UI/UX diagnosis loop & watchdog monitoring
- `45672d7` — Comprehensive Playwright regression test master report
- `e51ee82` — Add comprehensive Playwright regression test suite (Phase 1-5)

---

## Related Skills

- `ui-ux-audit-playwright` — Initial UI/UX audit methodology
- `test-driven-development` — Testing best practices
- `systematic-debugging` — Issue diagnosis patterns

---

## Summary

Three complementary tools for continuous UI/UX quality assurance:

| Tool | Purpose | Use Case |
|------|---------|----------|
| **Loop (∞)** | Continuous monitoring | Long-term QA, staging validation |
| **Loop (5)** | Quick verification | Pre-commit checks, demos |
| **Watchdog** | Background monitoring | Silent background checks, alerts on issues |

**All tools report:**
- ✅ Bootstrap class usage
- ✅ Inline style violations
- ✅ Form label compliance
- ✅ Button class usage

**Current Status:** 100% pass rate across all diagnostic runs.
