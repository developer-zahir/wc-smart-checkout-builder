# PowerShell script to package the plugin into a clean, installable WordPress ZIP file
$ErrorActionPreference = "Stop"

$pluginSlug = "wc-smart-checkout-builder"
$rootDir    = $PSScriptRoot
$distDir    = Join-Path $rootDir "dist"
$zipFinal   = Join-Path $distDir "$pluginSlug.zip"

if (-not (Test-Path $distDir)) {
    New-Item -ItemType Directory -Path $distDir -Force | Out-Null
}

# Create clean temp staging directory
$tempBase = Join-Path ([System.IO.Path]::GetTempPath()) ([System.Guid]::NewGuid().ToString())
$stageDir = Join-Path $tempBase $pluginSlug

New-Item -ItemType Directory -Path $stageDir -Force | Out-Null

Write-Host "Packaging $pluginSlug for WordPress..." -ForegroundColor Cyan

# Copy required plugin files and folders into staging directory
$includeItems = @("assets", "includes", "templates", "wc-smart-checkout-builder.php", "README.md")

foreach ($item in $includeItems) {
    $src = Join-Path $rootDir $item
    if (Test-Path $src) {
        Copy-Item -Path $src -Destination $stageDir -Recurse -Force
    }
}

$tempZip = Join-Path ([System.IO.Path]::GetTempPath()) "$([System.Guid]::NewGuid().ToString()).zip"

Add-Type -AssemblyName System.IO.Compression.FileSystem
# Create zip from $tempBase so that the root folder inside the zip is wc-smart-checkout-builder/
[System.IO.Compression.ZipFile]::CreateFromDirectory($tempBase, $tempZip)

Copy-Item -Path $tempZip -Destination $zipFinal -Force
Remove-Item -Force $tempZip -ErrorAction SilentlyContinue

# Clean up temp files
Remove-Item -Recurse -Force $tempBase -ErrorAction SilentlyContinue

$zipSize = (Get-Item $zipFinal).Length / 1KB
Write-Host ("SUCCESS: WordPress Installable ZIP created at {0} ({1:N1} KB)" -f $zipFinal, $zipSize) -ForegroundColor Green
