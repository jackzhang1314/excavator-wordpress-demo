$ErrorActionPreference = 'Stop'
Set-Location $PSScriptRoot
$node = Get-Command node -ErrorAction SilentlyContinue
$usable = $false
if ($node) {
    & $node.Source -e 'const [a,b]=process.versions.node.split(".").map(Number); process.exit(a>22||(a===22&&b>=13)?0:1)'
    $usable = $LASTEXITCODE -eq 0
}
if (-not $usable -or $env:DEMO_USE_PORTABLE_NODE -eq '1') {
    $arch = if ($env:PROCESSOR_ARCHITECTURE -eq 'ARM64') { 'arm64' } elseif ($env:PROCESSOR_ARCHITECTURE -eq 'AMD64') { 'x64' } else { throw 'This launcher supports Windows x64 and ARM64.' }
    $hash = if ($arch -eq 'arm64') { 'b12919e609b4fa1176ba8a155b49f761419a0c7cc97b42e6be09874a3f760ab6' } else { 'bb819d6eb8f5bfda294bbc83a7e4ec6539da67c4233d54b0d655b9248b15e29d' }
    $directory = Join-Path $PSScriptRoot ".tools/node-v22.20.0-win-$arch"
    if (-not (Test-Path "$directory/node.exe")) {
        New-Item -ItemType Directory -Force .tools | Out-Null
        $archive = Join-Path $PSScriptRoot ".tools/node-v22.20.0-win-$arch.zip"
        Write-Host 'Preparing a project-local Node.js runtime (no global installation).'
        Invoke-WebRequest -Uri "https://nodejs.org/dist/v22.20.0/node-v22.20.0-win-$arch.zip" -OutFile $archive -UseBasicParsing
        if ((Get-FileHash -Algorithm SHA256 $archive).Hash.ToLower() -ne $hash) { throw 'Node.js checksum mismatch. Download stopped.' }
        Expand-Archive -Path $archive -DestinationPath .tools -Force
        Remove-Item $archive
    }
    $env:Path = "$directory;$env:Path"
}
New-Item -ItemType Directory -Force .tools | Out-Null
$lockHash = (Get-FileHash -Algorithm SHA256 package-lock.json).Hash.ToLower()
$savedHash = if (Test-Path .tools/installed-lock.sha256) { (Get-Content .tools/installed-lock.sha256 -Raw).Trim() } else { '' }
if (-not (Test-Path node_modules/@wp-playground/wordpress) -or $savedHash -ne $lockHash) {
    & npm.cmd ci --ignore-scripts --no-audit --no-fund
    if ($LASTEXITCODE -ne 0) { throw 'Dependency installation failed. Check the network and try again.' }
    Set-Content -NoNewline -Path .tools/installed-lock.sha256 -Value $lockHash
}
& node scripts/start.mjs @args
exit $LASTEXITCODE
