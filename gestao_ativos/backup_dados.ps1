$ErrorActionPreference = 'Stop'

$dadosPath = Join-Path $PSScriptRoot 'dados'
$oneDrivePath = Get-ChildItem $env:USERPROFILE -Directory -ErrorAction Stop |
    Where-Object { $_.Name.StartsWith('OneDrive - Telegest', [StringComparison]::OrdinalIgnoreCase) } |
    Select-Object -First 1 -ExpandProperty FullName
$backupPath = Join-Path $oneDrivePath 'Backups\GestaoAtivos'
$temporarioPath = Join-Path $env:TEMP 'GestaoAtivosBackups'

if (-not (Test-Path $dadosPath -PathType Container)) {
    throw "Pasta de dados nao encontrada: $dadosPath"
}
if (-not (Test-Path $oneDrivePath -PathType Container)) {
    throw "Pasta corporativa OneDrive indisponivel: $oneDrivePath"
}

Get-ChildItem $dadosPath -Recurse -File -Filter '*.json' | ForEach-Object {
    Get-Content $_.FullName -Raw | ConvertFrom-Json -ErrorAction Stop | Out-Null
}

New-Item -ItemType Directory -Path $backupPath -Force | Out-Null
New-Item -ItemType Directory -Path $temporarioPath -Force | Out-Null

$identificador = Get-Date -Format 'yyyyMMdd_HHmmss_fff'
$arquivoTemporario = Join-Path $temporarioPath "gestao_ativos_$identificador.zip"
$arquivoFinal = Join-Path $backupPath "gestao_ativos_$identificador.zip"

Compress-Archive `
    -Path (Join-Path $dadosPath '*') `
    -DestinationPath $arquivoTemporario `
    -CompressionLevel Optimal

Add-Type -AssemblyName System.IO.Compression.FileSystem
$arquivoZip = [System.IO.Compression.ZipFile]::OpenRead($arquivoTemporario)
try {
    if ($arquivoZip.Entries.Count -eq 0) {
        throw 'O arquivo de backup foi criado sem conteúdo.'
    }
} finally {
    $arquivoZip.Dispose()
}

Move-Item -LiteralPath $arquivoTemporario -Destination $arquivoFinal
Write-Output "Backup validado: $arquivoFinal"