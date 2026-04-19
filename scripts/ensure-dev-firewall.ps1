# Adds inbound allow rules for Smart Attendance (Laravel :8000, Caddy HTTPS :9443).
# Run elevated once (launcher may start this with UAC). Safe to run again (idempotent).

$ErrorActionPreference = 'Stop'
try {
    $ports = @(8000, 9443)
    foreach ($port in $ports) {
        $name = "Smart Attendance dev TCP $port"
        if (Get-NetFirewallRule -DisplayName $name -ErrorAction SilentlyContinue) {
            continue
        }
        New-NetFirewallRule -DisplayName $name -Direction Inbound -Action Allow -Protocol TCP -LocalPort $port -Profile Any | Out-Null
    }
    Write-Host "Firewall rules for TCP 8000 and 9443 are in place." -ForegroundColor Green
    exit 0
} catch {
    Write-Host $_.Exception.Message -ForegroundColor Red
    exit 1
}
