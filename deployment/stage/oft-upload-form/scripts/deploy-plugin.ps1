$ErrorActionPreference = 'Stop'

$source = Split-Path -Parent $PSScriptRoot
$deploymentRoot = Join-Path $source 'deployment'
$stageRoot = Join-Path $deploymentRoot 'stage'
$stagePluginRoot = Join-Path $stageRoot 'oft-upload-form'
$zipPath = Join-Path $deploymentRoot 'oft-upload-form.zip'
$jsonSource = Join-Path $source 'includes\updater\examples\info.json'
$jsonPath = Join-Path $deploymentRoot 'oft-upload-form.json'

if (-not (Test-Path -LiteralPath $jsonSource)) {
	throw "Missing updater metadata template: $jsonSource"
}

if (Test-Path -LiteralPath $deploymentRoot) {
	Remove-Item -LiteralPath $deploymentRoot -Recurse -Force
}

New-Item -ItemType Directory -Path $stagePluginRoot -Force | Out-Null

$robocopyArgs = @(
	$source
	$stagePluginRoot
	'/MIR'
	'/XD', '.git', '.vscode', 'deployment'
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
	throw "Deployment packaging failed with robocopy exit code $exitCode."
}

Compress-Archive -Path $stagePluginRoot -DestinationPath $zipPath -Force
Copy-Item -LiteralPath $jsonSource -Destination $jsonPath -Force

Write-Host "Deployment package created:"
Write-Host "ZIP:  $zipPath"
Write-Host "JSON: $jsonPath"