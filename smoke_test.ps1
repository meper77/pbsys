Param(
    [string]$Host="neovtrack.uitm.edu.my",
    [string]$IP="10.0.26.208",
    [string]$Email,
    [string]$Password
)

Write-Output "=== Checking /search_api.php on $Host ==="
curl.exe --silent --show-error --location --write-out "`nHTTP_CODE:%{http_code}`n" "http://$Host/search_api.php"

Write-Output "=== Checking /searchCar.php on $Host ==="
curl.exe --silent --show-error --location --write-out "`nHTTP_CODE:%{http_code}`n" "http://$Host/searchCar.php"

if ($Email -and $Password) {
    Write-Output "=== Attempting admin login ==="
    curl.exe --silent --show-error --location --cookie-jar cookie.txt -d "email_Admin=$Email&password_Admin=$Password" "http://$Host/login_admin_api.php" --write-out "`nHTTP_CODE:%{http_code}`n"
    Write-Output "=== If login succeeded, calling /search_api.php with cookie ==="
    curl.exe --silent --show-error --location --cookie cookie.txt "http://$Host/search_api.php" --write-out "`nHTTP_CODE:%{http_code}`n"
} else {
    Write-Output "Admin credentials not provided; skip login test. Provide -Email and -Password to test login."
}

Write-Output "=== Check IP with Host header ==="
curl.exe --silent --show-error --location -H "Host: $Host" --write-out "`nHTTP_CODE:%{http_code}`n" "http://$IP/search_api.php"
