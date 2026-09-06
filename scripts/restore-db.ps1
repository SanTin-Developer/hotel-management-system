param(
    [Parameter(Mandatory = $true)][string]$File,
    [string]$Container = "hotel_postgres",
    [string]$DbUser = "hotel_user",
    [string]$DbName = "hotel_management",
    [switch]$Force
)

$ErrorActionPreference = "Stop"

if (-not (Test-Path -LiteralPath $File)) {
    Write-Error "Backup file not found: $File"
}

$running = docker ps --format "{{.Names}}" --filter "name=^$Container$"
if (-not $running) {
    Write-Error "Container '$Container' is not running. Start it with 'docker compose up -d' first."
}

if (-not $Force) {
    $answer = Read-Host "This will OVERWRITE all data in '$DbName'. Restore from '$File'? [y/N]"
    if ($answer -ne "y" -and $answer -ne "Y") {
        Write-Host "Aborted."
        exit 1
    }
}

$remote = "/tmp/hotel_restore.dump"
Write-Host "Copying backup into the container ..." -ForegroundColor Cyan
docker cp $File "$Container`:$remote"
if ($LASTEXITCODE -ne 0) {
    Write-Error "docker cp failed (exit code $LASTEXITCODE)."
}

Write-Host "Restoring into '$DbName' ..." -ForegroundColor Cyan
docker exec $Container pg_restore -U $DbUser -d $DbName --clean --if-exists --no-owner --no-privileges $remote
$code = $LASTEXITCODE
docker exec $Container rm -f $remote

if ($code -ne 0) {
    Write-Error "Restore finished with errors (exit code $code)."
}

Write-Host "Restore completed for '$DbName'." -ForegroundColor Green