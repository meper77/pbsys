# deploy_check.ps1
# Usage: Open PowerShell in repo root and run: .\deploy_check.ps1
# Adjust Host and IP variables if needed.
Param(
    [string]$Host = "neovtrack.uitm.edu.my",
    [string]$IP   = "10.0.26.208"
)

Write-Output "=== Checking http://$Host/search_api.php ==="
curl.exe --silent --show-error --location --write-out "`nHTTP_CODE:%{http_code}`n" "http://$Host/search_api.php"

Write-Output "=== Checking http://$Host/searchCar.php ==="
curl.exe --silent --show-error --location --write-out "`nHTTP_CODE:%{http_code}`n" "http://$Host/searchCar.php"

Write-Output "=== Checking http://$IP/search_api.php with Host header ==="
curl.exe --silent --show-error --location -H "Host: $Host" --write-out "`nHTTP_CODE:%{http_code}`n" "http://$IP/search_api.php"

Write-Output "=== Checking http://$IP/searchCar.php with Host header ==="
curl.exe --silent --show-error --location -H "Host: $Host" --write-out "`nHTTP_CODE:%{http_code}`n" "http://$IP/searchCar.php"
