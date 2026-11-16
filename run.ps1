Write-Host "===================================================" -ForegroundColor Green
Write-Host "  WhatJobs.com Recreation - Standalone Server" -ForegroundColor Green
Write-Host "  No XAMPP or MySQL setup required!" -ForegroundColor Green
Write-Host "===================================================" -ForegroundColor Green
Write-Host ""
Write-Host "Starting PHP Built-in Server on http://localhost:8000 ..." -ForegroundColor Cyan
Write-Host ""
Start-Process "http://localhost:8000"
php -S localhost:8000

