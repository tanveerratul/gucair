@echo off
REM ---------------------------------------------------------------------------
REM GUCAIR local startup helper.
REM Starts XAMPP Apache and MongoDB Windows services, then opens the site.
REM ---------------------------------------------------------------------------

setlocal EnableExtensions

REM Change these two names if your Windows services use different names.
REM You can also override them when running, for example:
REM   START.bat Apache2.4 MongoDB
set "APACHE_SERVICE=Apache2.4"
set "MONGO_SERVICE=MongoDB"
set "SITE_URL=http://localhost/gucair/"

if not "%~1"=="" set "APACHE_SERVICE=%~1"
if not "%~2"=="" set "MONGO_SERVICE=%~2"

REM Starting Windows services needs administrator rights.
net session >nul 2>&1
if errorlevel 1 (
    echo Requesting administrator permission...
    set "GUCAIR_BAT=%~f0"
    set "GUCAIR_ARGS=%*"
    powershell -NoProfile -Command "$p = $env:GUCAIR_BAT; $a = $env:GUCAIR_ARGS; if ($a) { Start-Process -FilePath $p -ArgumentList $a -Verb RunAs } else { Start-Process -FilePath $p -Verb RunAs }"
    exit /b
)

echo.
echo === GUCAIR startup ===
echo.

call :start_service "%APACHE_SERVICE%" "XAMPP Apache"
call :start_service "%MONGO_SERVICE%" "MongoDB"

echo.
echo Waiting for %SITE_URL% ...
set "GUCAIR_URL=%SITE_URL%"
set "SITE_READY=0"
for /L %%N in (1,1,15) do (
    powershell -NoProfile -Command "try { $u = $env:GUCAIR_URL; $r = Invoke-WebRequest -UseBasicParsing -Uri $u -TimeoutSec 2; if ($r.StatusCode -eq 200) { exit 0 } else { exit 1 } } catch { exit 1 }" >nul 2>&1
    if not errorlevel 1 (
        set "SITE_READY=1"
        goto :site_ready
    )
)

:site_ready
if "%SITE_READY%"=="0" (
    echo [WARN] The site did not answer yet. Check Apache and MongoDB, then open the URL manually.
) else (
    echo [OK] The site is responding.
)

echo.
echo Opening %SITE_URL% ...
start "" "%SITE_URL%"

echo.
echo Done. You can close this window.
timeout /t 3 /nobreak >nul
exit /b 0

REM ---------------------------------------------------------------------------
REM Start one Windows service and print a clear result.
REM ---------------------------------------------------------------------------
:start_service
set "SVC_NAME=%~1"
set "SVC_LABEL=%~2"

sc query "%SVC_NAME%" >nul 2>&1
if errorlevel 1 (
    echo [WARN] Service "%SVC_NAME%" was not found.
    echo        Start it from XAMPP Control Panel/Services, or edit the service name in START.bat.
    goto :eof
)

sc query "%SVC_NAME%" | findstr /I "RUNNING" >nul
if not errorlevel 1 (
    echo [OK] %SVC_LABEL% is already running.
    goto :eof
)

echo Starting %SVC_LABEL% ("%SVC_NAME%")...
net start "%SVC_NAME%" >nul
if errorlevel 1 (
    echo [WARN] Could not start %SVC_LABEL%. It may already be starting or need manual attention.
) else (
    echo [OK] %SVC_LABEL% started.
)
goto :eof
