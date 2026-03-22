$ErrorActionPreference = 'Stop'

$source = Split-Path -Parent $PSScriptRoot
$destination = 'C:\Users\donjd\Local Sites\wp-solutions\app\public\wp-content\plugins\lightweight-upload-form'

if (-not (Test-Path -LiteralPath $destination)) {
	New-Item -ItemType Directory -Path $destination -Force | Out-Null
}

$robocopyArgs = @(
	$source
	$destination
	'/MIR'
	'/XD', '.git', '.vscode'
	'/XF', '.gitignore'
	'/R:2'
	'/W:1'
	'/NFL'
	'/NDL'
	'/NJH'
	'/NJS'
)

& robocopy @robocopyArgs
$exitCode = $LASTEXITCODE

if ($exitCode -ge 8) {
	throw "Plugin deployment failed with robocopy exit code $exitCode."
}

Write-Host "Plugin deployed to $destination"
