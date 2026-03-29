@echo off
chcp 65001 >nul
cd /d "D:\Projects\snack-git\script" // check_timeout.py 脚本目录
python check_timeout.py
pause
