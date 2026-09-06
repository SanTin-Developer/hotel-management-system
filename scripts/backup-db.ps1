param(
    [string]$Container = "hotel_postgres",
    [string]$DbUser = "hotel_user",
    [string]$DbName = "hotel_management",
    [string]$OutDir = (Join-Path $PSScriptRoot "..\backups"),
    [int]$Keep = 14
)

$ErrorActionPreference = "Stop"

$running = docker ps --format "{{.Names}}" --filter "name=^$Container$"
if (-not $running) {
    Write-Error "Container '$Container' is not running. Start it with 'docker compose up -d' first."
}

$resolved = [System.IO.Path]::GetFullPath($OutDir)
[System.IO.Directory]::CreateDirectory($resolved) | Out-Null

$stamp = Get-Date -Format "yyyyMMdd_HHmmss"
$remote = "/tmp/hotel_backup_$stamp.dump"

Write-Host "Dumping database '$DbName' from container '$Container' ..." -ForegroundColor Cyan
docker exec $Container pg_dump -U $DbUser -d $DbName --format=custom --no-owner --file=$remote
if ($LASTEXITCODE -ne 0) {
    Write-Error "pg_dump failed (exit code $LASTEXITCODE)."
}

$local = Join-Path $resolved "$($DbName)_$stamp.dump"
Write-Host "Copying backup out of the container ..." -ForegroundColor Cyan
docker cp "$Container`:$remote" $local
if ($LASTEXITCODE -ne 0) {
    docker exec $Container rm -f $remote
    Write-Error "docker cp failed (exit code $LASTEXITCODE)."
}

docker exec $Container rm -f $remote

$size = (Get-Item -LiteralPath $local).Length
Write-Host "Backup created: $local ($([math]::Round($size / 1KB, 1)) KB)" -ForegroundColor Green

Get-ChildItem -LiteralPath $resolved -Filter "$($DbName)_*.dump" |
    Sort-Object LastWriteTime -Descending |
    Select-Object -Skip $Keep |
    ForEach-Object {
        Write-Host "Pruning old backup: $($_.Name)" -ForegroundColor DarkYellow
        Remove-Item $_.FullName
    }