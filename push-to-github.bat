@echo off
cd /d "%~dp0"
echo ========================================================
echo   Pushing Ashish Traders Fireworks Updates to GitHub
echo ========================================================
echo.
git push origin main
if %errorlevel% neq 0 (
    echo.
    echo [ERROR] Push nahi hua. Agar token expire ho gaya hai to naya token banayein.
) else (
    echo.
    echo [SUCCESS] Sabhi updates GitHub par push ho gayi hain!
)
echo.
pause
