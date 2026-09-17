@echo off
echo Starting Ashish Traders Fireworks Web Server...
start http://localhost:5500
if exist "%~dp0node.exe" (
    "%~dp0node.exe" "%~dp0server.js"
) else (
    node "%~dp0server.js"
)
pause
