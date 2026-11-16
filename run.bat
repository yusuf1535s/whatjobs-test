@echo off
title WhatJobs Clone Server
echo ===================================================
echo   WhatJobs.com Recreation - Standalone Server
echo   No XAMPP or MySQL setup required!
echo ===================================================
echo.
echo Starting PHP Built-in Server on http://localhost:8000 ...
echo.
start http://localhost:8000
php -S localhost:8000
pause

