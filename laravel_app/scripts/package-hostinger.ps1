param([string]$OutputDirectory)
$ErrorActionPreference = 'Stop'
$applicationRoot = [System.IO.Path]::GetFullPath((Join-Path $PSScriptRoot '..'))
$projectRoot = [System.IO.Path]::GetFullPath((Join-Path $applicationRoot '..'))
if (-not $OutputDirectory) { $OutputDirectory = Join-Path $projectRoot ('deploy\hostinger-demo-' + (Get-Date -Format 'yyyyMMdd-HHmmss')) }
$releaseRoot = [System.IO.Path]::GetFullPath($OutputDirectory)
if (Test-Path -LiteralPath $releaseRoot) { throw 'Release directory already exists; select a new directory.' }

Push-Location -LiteralPath $applicationRoot
try {
    & php artisan shop:export-demo $releaseRoot
    if ($LASTEXITCODE -ne 0) { throw 'SQL export failed.' }
} finally { Pop-Location }

$appDestination = Join-Path $releaseRoot 'laravel_app'
New-Item -ItemType Directory -Path $appDestination -Force | Out-Null
foreach ($folder in @('app','bootstrap','config','database','resources','routes')) {
    Copy-Item -LiteralPath (Join-Path $applicationRoot $folder) -Destination (Join-Path $appDestination $folder) -Recurse
}
foreach ($file in @('artisan','composer.json','composer.lock','.htaccess','.env.example')) {
    Copy-Item -LiteralPath (Join-Path $applicationRoot $file) -Destination (Join-Path $appDestination $file)
}
# Exported environment contains an isolated encryption key and no local DB/API credentials.
Copy-Item -LiteralPath (Join-Path $releaseRoot '.env.hostinger') -Destination (Join-Path $appDestination '.env')
foreach ($folder in @('storage/framework/cache/data','storage/framework/sessions','storage/framework/views','storage/logs','storage/app/private','bootstrap/cache')) {
    $target = Join-Path $appDestination $folder
    New-Item -ItemType Directory -Path $target -Force | Out-Null
    [System.IO.File]::WriteAllText((Join-Path $target '.gitignore'), "*`n!.gitignore`n")
}
$cacheDirectory = Join-Path $appDestination 'bootstrap\cache'
Get-ChildItem -LiteralPath $cacheDirectory -File | Where-Object Name -ne '.gitignore' | ForEach-Object { Remove-Item -LiteralPath $_.FullName }
Get-ChildItem -LiteralPath (Join-Path $appDestination 'database') -File -Filter '*.sqlite*' | ForEach-Object { Remove-Item -LiteralPath $_.FullName }

$publicSource = Join-Path $projectRoot 'public_html'
$publicDestination = Join-Path $releaseRoot 'public_html'
New-Item -ItemType Directory -Path $publicDestination -Force | Out-Null
Get-ChildItem -LiteralPath $publicSource -Recurse -File -Force | ForEach-Object {
    $relative = $_.FullName.Substring($publicSource.Length + 1)
    $normalized = $relative.Replace('\','/')
    if ($normalized -eq 'hot' -or $normalized.StartsWith('storage/') -or $_.Extension -in @('.sql','.zip','.env','.log') -or ($_.Extension -eq '.php' -and $normalized -ne 'index.php')) { return }
    $destination = Join-Path $publicDestination $relative
    New-Item -ItemType Directory -Path ([System.IO.Path]::GetDirectoryName($destination)) -Force | Out-Null
    Copy-Item -LiteralPath $_.FullName -Destination $destination
}
Copy-Item -LiteralPath (Join-Path $applicationRoot 'docs\HOSTINGER.md') -Destination (Join-Path $releaseRoot 'HOSTINGER.md')
Copy-Item -LiteralPath (Join-Path $applicationRoot 'docs\DATA-AUDIT.md') -Destination (Join-Path $releaseRoot 'DATA-AUDIT.md')
Push-Location -LiteralPath $appDestination
try {
    & composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction --no-ansi
    if ($LASTEXITCODE -ne 0) { throw 'Production dependency installation failed.' }
    & composer check-platform-reqs --no-dev
    if ($LASTEXITCODE -ne 0) { throw 'Production platform check failed.' }
} finally { Pop-Location }
# Host-specific cached files are generated on Hostinger after upload.
Get-ChildItem -LiteralPath $cacheDirectory -File | Where-Object Name -ne '.gitignore' | ForEach-Object { Remove-Item -LiteralPath $_.FullName }
Add-Type -AssemblyName System.IO.Compression.FileSystem
$archivePath = $releaseRoot + '.zip'
[System.IO.Compression.ZipFile]::CreateFromDirectory($releaseRoot, $archivePath, [System.IO.Compression.CompressionLevel]::Optimal, $false)
$checksum = (Get-FileHash -LiteralPath $archivePath -Algorithm SHA256).Hash
[System.IO.File]::WriteAllText($archivePath + '.sha256', $checksum + "`n")
[pscustomobject]@{Archive=$archivePath;SQL=(Join-Path $releaseRoot 'taphoagame-demo.sql');Instructions=(Join-Path $releaseRoot 'HOSTINGER.md');Credentials=(Join-Path $releaseRoot 'ACCESS.txt');SHA256=$checksum} | ConvertTo-Json
