#Requires -RunAsAdministrator
<#
    Allow Herd/PHP to send SMTP mail through Aliyun DirectMail.
    Run this script as Administrator (right-click → Run with PowerShell).
#    
$phpPaths = @(
    "$env:USERPROFILE\.config\herd\bin\php84\php-cgi.exe",
    "$env:USERPROFILE\.config\herd\bin\php84\php.exe",
    "$env:USERPROFILE\.config\herd\bin\php82\php-cgi.exe",
    "$env:USERPROFILE\.config\herd\bin\php82\php.exe"
)

$ports = @(465, 587)

foreach ($php in $phpPaths) {
    if (-Not (Test-Path $php)) { continue }

    $nameBase = "Herd PHP SMTP - $(Split-Path $php -Leaf)"

    foreach ($port in $ports) {
        $ruleName = "$nameBase port $port"
        # Remove old rule with same name to avoid duplicates
        netsh advfirewall firewall delete rule name="$ruleName" > $null 2>&1

        netsh advfirewall firewall add rule `
            name="$ruleName" `
            dir=out `
            action=allow `
            program="$php" `
            protocol=tcp `
            localport=any `
            remoteport=$port `
            profile=any `
            enable=yes

        Write-Host "Created rule: $ruleName for $php"
    }
}

Write-Host "Done. Restart Herd/PHP-FPM/Nginx and try sending mail again."
