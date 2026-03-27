$ErrorActionPreference = 'Stop'

$source = Split-Path -Parent $PSScriptRoot
$deploymentRoot = Join-Path $source 'deployment'
$stageRoot = Join-Path $deploymentRoot 'stage'
$stagePluginRoot = Join-Path $stageRoot 'oft-upload-form'
$zipPath = Join-Path $deploymentRoot 'oft-upload-form.zip'
$configPath = Join-Path $source 'deployment.config.json'
$jsonPath = Join-Path $deploymentRoot 'oft-upload-form.json'
$mainPluginFile = Join-Path $source 'oft-upload-form.php'
$readmePath = Join-Path $source 'readme.txt'

function Set-FileContentIfChanged {
	param(
		[Parameter(Mandatory = $true)]
		[string]$Path,

		[Parameter(Mandatory = $true)]
		[string]$Content
	)

	$current = Get-Content -LiteralPath $Path -Raw
	if ($current -cne $Content) {
		$encoding = New-Object System.Text.UTF8Encoding($false)
		[System.IO.File]::WriteAllText($Path, $Content, $encoding)
	}
}

function ConvertTo-HtmlList {
	param(
		[Parameter(Mandatory = $true)]
		[string[]]$Items
	)

	$listItems = foreach ($item in $Items) {
		'<li>' + [System.Security.SecurityElement]::Escape($item) + '</li>'
	}

	return '<ul>' + ($listItems -join '') + '</ul>'
}

function Get-ReadmeChangelogEntry {
	param(
		[Parameter(Mandatory = $true)]
		[string]$Version,

		[Parameter(Mandatory = $true)]
		[string[]]$Items
	)

	$lines = @("= $Version =", '')
	foreach ($item in $Items) {
		$lines += "* $item"
	}

	return ($lines -join [Environment]::NewLine)
}

if (-not (Test-Path -LiteralPath $configPath)) {
	throw "Missing deployment config: $configPath"
}

$config = Get-Content $configPath -Raw | ConvertFrom-Json
$pluginFileContent = Get-Content $mainPluginFile -Raw
$readmeContent = Get-Content $readmePath -Raw

if (-not $config.PSObject.Properties.Name.Contains('release_notes')) {
	throw "Missing release_notes in $configPath"
}

$releaseNotes = @($config.release_notes | Where-Object { $_ -and $_.Trim() })
if ($releaseNotes.Count -eq 0) {
	throw "release_notes in $configPath must contain at least one item."
}

if ($pluginFileContent -notmatch "Version:\s*([0-9]+(?:\.[0-9]+)*)") {
	throw "Could not read plugin version from $mainPluginFile"
}

if ($pluginFileContent -notmatch "define\(\s*'OFTUF_VERSION'\s*,\s*'([0-9]+(?:\.[0-9]+)*)'\s*\);") {
	throw "Could not read OFTUF_VERSION from $mainPluginFile"
}

$updatedPluginFileContent = [regex]::Replace(
	$pluginFileContent,
	"(?m)^(\s*\*\s*Version:\s*)([0-9]+(?:\.[0-9]+)*)",
	'${1}' + $config.version,
	1
)
$updatedPluginFileContent = [regex]::Replace(
	$updatedPluginFileContent,
	"define\(\s*'OFTUF_VERSION'\s*,\s*'([0-9]+(?:\.[0-9]+)*)'\s*\);",
	"define( 'OFTUF_VERSION', '$($config.version)' );",
	1
)
Set-FileContentIfChanged -Path $mainPluginFile -Content $updatedPluginFileContent

$updatedReadmeContent = [regex]::Replace(
	$readmeContent,
	'(?m)^(Stable tag:\s*)([0-9]+(?:\.[0-9]+)*)$',
	'${1}' + $config.version,
	1
)
$newReadmeEntry = Get-ReadmeChangelogEntry -Version $config.version -Items $releaseNotes
$versionPattern = '(?ms)^= ' + [regex]::Escape($config.version) + ' =\r?\n(?:\r?\n)?(?:(?!^= [0-9]+(?:\.[0-9]+)* =).*(?:\r?\n|$))*'
if ([regex]::IsMatch($updatedReadmeContent, $versionPattern)) {
	$updatedReadmeContent = [regex]::Replace(
		$updatedReadmeContent,
		$versionPattern,
		$newReadmeEntry + [Environment]::NewLine + [Environment]::NewLine,
		1
	)
} else {
	$updatedReadmeContent = [regex]::Replace(
		$updatedReadmeContent,
		'(?ms)(== Changelog ==\r?\n\r?\n)',
		'${1}' + $newReadmeEntry + [Environment]::NewLine + [Environment]::NewLine,
		1
	)
}
Set-FileContentIfChanged -Path $readmePath -Content $updatedReadmeContent

if (Test-Path -LiteralPath $deploymentRoot) {
	Remove-Item -LiteralPath $deploymentRoot -Recurse -Force
}

New-Item -ItemType Directory -Path $stagePluginRoot -Force | Out-Null

$robocopyArgs = @(
	$source
	$stagePluginRoot
	'/MIR'
	'/XD', '.git', '.vscode', 'deployment'
	'/XF', '.gitignore', 'deployment.config.json'
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

$jsonSections = [ordered]@{
	description  = $config.sections.description
	installation = $config.sections.installation
	changelog    = '<h4>' + [System.Security.SecurityElement]::Escape($config.version) + '</h4>' + (ConvertTo-HtmlList -Items $releaseNotes)
}

$jsonConfig = [ordered]@{
	name          = $config.name
	slug          = $config.slug
	version       = $config.version
	requires      = $config.requires
	tested        = $config.tested
	requires_php  = $config.requires_php
	last_updated  = $config.last_updated
	homepage      = $config.homepage
	download_url  = $config.download_url
	sections      = $jsonSections
	banners       = $config.banners
	icons         = $config.icons
}

& tar -a -c -f $zipPath -C $stageRoot 'oft-upload-form'
if ($LASTEXITCODE -ne 0) {
	throw "ZIP packaging failed with tar exit code $LASTEXITCODE."
}

$json = $jsonConfig | ConvertTo-Json -Depth 10
$json = $json.Replace('\u003c', '<').Replace('\u003e', '>').Replace('\u0026', '&')
$jsonBytes = [System.Text.Encoding]::UTF8.GetBytes($json + [Environment]::NewLine)
[System.IO.File]::WriteAllBytes($jsonPath, $jsonBytes)

Remove-Item -LiteralPath $stageRoot -Recurse -Force

Write-Host "Deployment package created:"
Write-Host "ZIP:  $zipPath"
Write-Host "JSON: $jsonPath"
Write-Host "Version: $($config.version)"
