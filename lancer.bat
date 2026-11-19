@echo off
cd /d "%~dp0"
where python >nul 2>nul
if %errorlevel%==0 (
  start "" http://localhost:5173
  python -m http.server 5173
) else (
  start "" http://localhost:3000
  npx --yes serve . -l 3000
)
