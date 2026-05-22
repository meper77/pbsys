#!/bin/bash
# Simple aliases - add to your .bashrc or run this file to set shortcuts

alias dev-start='bash /c/Users/User.J1-ALPHA-PENS/pbsys/dev.sh start'
alias dev-stop='bash /c/Users/User.J1-ALPHA-PENS/pbsys/dev.sh stop'
alias dev-restart='bash /c/Users/User.J1-ALPHA-PENS/pbsys/dev.sh restart'
alias dev-status='bash /c/Users/User.J1-ALPHA-PENS/pbsys/dev.sh status'
alias dev-logs='cd /c/Users/User.J1-ALPHA-PENS/pbsys/.dev-logs && ls -ltr'

echo "Dev shortcuts loaded:"
echo "  dev-start    - Start all services"
echo "  dev-stop     - Stop all services"
echo "  dev-restart  - Restart all services"
echo "  dev-status   - Show service status"
echo "  dev-logs     - View logs directory"
