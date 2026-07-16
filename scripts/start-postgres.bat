@echo off
REM Start the portable Postgres bundled under .pgsql
set ROOT=%~dp0
"%ROOT%.pgsql\pgsql\bin\pg_ctl.exe" -D "%ROOT%.pgsql\data-local" -l "%ROOT%.pgsql\data-local\server.log" start
"%ROOT%.pgsql\pgsql\bin\pg_ctl.exe" status -D "%ROOT%.pgsql\data-local"
