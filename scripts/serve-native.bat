@echo off
REM Start the site without Docker on Windows.
REM This wrapper bypasses the PowerShell execution policy and keeps the
REM window open on failure so dependency hints stay readable.
chcp 65001 >nul
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0serve-native.ps1" %*
if errorlevel 1 pause
