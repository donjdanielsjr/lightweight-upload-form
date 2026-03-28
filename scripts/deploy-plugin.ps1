param(
	[ValidateSet('stable', 'beta')]
	[string]$Track = 'beta'
)

$ErrorActionPreference = 'Stop'

$source = Split-Path -Parent $PSScriptRoot
$deploymentRoot = Join-Path $source 'deployment'
$stageRoot = Join-Path $deploymentRoot 'stage'
$configPath = Join-Path $source 'deployment.config.json'
$mainPluginFile = Join-Path $source 'oft-upload-form.php'
$readmePath = Join-Path $source 'readme.txt'
$versionPattern = '[0-9A-Za-z][0-9A-Za-z\.\-\+]*'

function Set-FileContentIfChanged {
	param([string]$Path,[string]$Content)
	$current = Get-Content -LiteralPath $Path -Raw
	if ($current -cne $Content) {
		$encoding = New-Object System.Text.UTF8Encoding($false)
		[System.IO.File]::WriteAllText($Path, $Content, $encoding)
	}
}

function ConvertTo-HtmlList {
	param([string[]]$Items)
	$listItems = foreach ($item in $Items) { '<li>' + [System.Security.SecurityElement]::Escape($item) + '</li>' }
	return '<ul>' + ($listItems -join '') + '</ul>'
}

function Get-ReadmeChangelogEntry {
	param([string]$Version,[string[]]$Items)
	$lines = @("= $Version =", '')
	foreach ($item in $Items) { $lines += "* $item" }
	return ($lines -join [Environment]::NewLine)
}

function Get-HtmlChangelogEntry {
	param([string]$Version,[string[]]$Items)
	return '<h4>' + [System.Security.SecurityElement]::Escape($Version) + '</h4>' + (ConvertTo-HtmlList -Items $Items)
}

function Normalize-History {
	param([array]$History,[string]$CurrentVersion,[string[]]$CurrentNotes,[string]$ConfigPath)
	$normalized = @()
	foreach ($entry in @($History)) {
		if (-not $entry.PSObject.Properties.Name.Contains('version')) {
			throw "Each release_history entry in $ConfigPath must include a version."
		}
		$entryVersion = [string]$entry.version
		$entryNotes = @($entry.notes | Where-Object { $_ -and $_.Trim() })
		if ([string]::IsNullOrWhiteSpace($entryVersion) -or $entryNotes.Count -eq 0) {
			throw "Each release_history entry in $ConfigPath must include a version and at least one note."
		}
		$normalized += [pscustomobject]@{ version = $entryVersion; notes = $entryNotes }
	}
	$currentEntry = $normalized | Where-Object { $_.version -eq $CurrentVersion } | Select-Object -First 1
	if (-not $currentEntry) {
		$normalized = ,([pscustomobject]@{ version = $CurrentVersion; notes = $CurrentNotes }) + $normalized
		$currentEntry = $normalized[0]
	}
	$currentEntry.notes = $CurrentNotes
	return $normalized
}

function Get-UpdatedPluginFileContent {
	param([string]$Content,[string]$Version,[string]$Requires,[string]$RequiresPhp,[string]$VersionPattern)
	$updated = [regex]::Replace($Content, "(?m)^(\s*\*\s*Version:\s*)$VersionPattern", '${1}' + $Version, 1)
	$updated = [regex]::Replace($updated, '(?m)^(\s*\*\s*Requires at least:\s*).+$', '${1}' + $Requires, 1)
	$updated = [regex]::Replace($updated, '(?m)^(\s*\*\s*Requires PHP:\s*).+$', '${1}' + $RequiresPhp, 1)
	$updated = [regex]::Replace($updated, "define\(\s*'OFTUF_VERSION'\s*,\s*'$VersionPattern'\s*\);", "define( 'OFTUF_VERSION', '$Version' );", 1)
	return $updated
}

function Get-UpdatedReadmeContent {
	param([string]$Content,[string]$StableVersion,[string]$Requires,[string]$Tested,[string]$RequiresPhp,[array]$History)
	$updated = [regex]::Replace($Content, '(?m)^(Requires at least:\s*).+$', '${1}' + $Requires, 1)
	$updated = [regex]::Replace($Content, '(?m)^(Tested up to:\s*).+$', '${1}' + $Tested, 1)
	$updated = [regex]::Replace($Content, '(?m)^(Requires PHP:\s*).+$', '${1}' + $RequiresPhp, 1)
	$updated = [regex]::Replace($Content, "(?m)^(Stable tag:\s*)$versionPattern$", '${1}' + $StableVersion, 1)
	$entries = foreach ($entry in $History) { Get-ReadmeChangelogEntry -Version $entry.version -Items $entry.notes }
	$block = ($entries -join ([Environment]::NewLine + [Environment]::NewLine)) + [Environment]::NewLine
	return [regex]::Replace($updated, '(?ms)(== Changelog ==\r?\n\r?\n).*$','${1}' + $block,1)
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

$history = Normalize-History -History $config.release_history -CurrentVersion ([string]$config.version) -CurrentNotes $releaseNotes -ConfigPath $configPath

if ($pluginFileContent -notmatch "Version:\s*$versionPattern") {
	throw "Could not read plugin version from $mainPluginFile"
}

if ($pluginFileContent -notmatch "define\(\s*'OFTUF_VERSION'\s*,\s*'$versionPattern'\s*\);") {
	throw "Could not read OFTUF_VERSION from $mainPluginFile"
}

$updatedPluginFileContent = Get-UpdatedPluginFileContent -Content $pluginFileContent -Version ([string]$config.version) -Requires ([string]$config.requires) -RequiresPhp ([string]$config.requires_php) -VersionPattern $versionPattern
Set-FileContentIfChanged -Path $mainPluginFile -Content $updatedPluginFileContent

$stableTagVersion = if ('stable' -eq $Track) { [string]$config.version } else { $readmeContent | Select-String -Pattern "(?m)^Stable tag:\s*($versionPattern)$" | ForEach-Object { $_.Matches[0].Groups[1].Value } | Select-Object -First 1 }
if ([string]::IsNullOrWhiteSpace($stableTagVersion)) {
	$stableTagVersion = [string]$config.version
}

$updatedReadmeContent = Get-UpdatedReadmeContent -Content $readmeContent -StableVersion $stableTagVersion -Requires ([string]$config.requires) -Tested ([string]$config.tested) -RequiresPhp ([string]$config.requires_php) -History $history
Set-FileContentIfChanged -Path $readmePath -Content $updatedReadmeContent

if (-not (Test-Path -LiteralPath $deploymentRoot)) {
	New-Item -ItemType Directory -Path $deploymentRoot -Force | Out-Null
}

$trackRoot = Join-Path $deploymentRoot $Track
$trackStageRoot = Join-Path $stageRoot $Track
$stagePluginRoot = Join-Path $trackStageRoot $config.slug

if (Test-Path -LiteralPath $trackRoot) {
	Remove-Item -LiteralPath $trackRoot -Recurse -Force
}

if (Test-Path -LiteralPath $trackStageRoot) {
	Remove-Item -LiteralPath $trackStageRoot -Recurse -Force
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
if ($LASTEXITCODE -ge 8) {
	throw "Deployment packaging failed for $Track with robocopy exit code $LASTEXITCODE."
}

$stagePluginFile = Join-Path $stagePluginRoot 'oft-upload-form.php'
$stageReadmePath = Join-Path $stagePluginRoot 'readme.txt'
$stagePluginContent = Get-Content $stagePluginFile -Raw
$stagePluginContent = Get-UpdatedPluginFileContent -Content $stagePluginContent -Version ([string]$config.version) -Requires ([string]$config.requires) -RequiresPhp ([string]$config.requires_php) -VersionPattern $versionPattern
Set-FileContentIfChanged -Path $stagePluginFile -Content $stagePluginContent

$stageReadmeContent = Get-Content $stageReadmePath -Raw
$stageReadmeContent = Get-UpdatedReadmeContent -Content $stageReadmeContent -StableVersion $stableTagVersion -Requires ([string]$config.requires) -Tested ([string]$config.tested) -RequiresPhp ([string]$config.requires_php) -History $history
Set-FileContentIfChanged -Path $stageReadmePath -Content $stageReadmeContent

$trackUrlBase = 'https://onefeaturetrap.com/plugin-downloads/' + $config.slug + '/' + $Track
$jsonSections = [ordered]@{
	description  = $config.sections.description
	installation = $config.sections.installation
	changelog    = (($history | ForEach-Object { Get-HtmlChangelogEntry -Version $_.version -Items $_.notes }) -join '')
}
$jsonConfig = [ordered]@{
	name          = $config.name
	slug          = $config.slug
	version       = $config.version
	channel       = $Track
	requires      = $config.requires
	tested        = $config.tested
	requires_php  = $config.requires_php
	last_updated  = $config.last_updated
	homepage      = $config.homepage
	download_url  = $trackUrlBase + '/plugin.zip'
	sections      = $jsonSections
	banners       = $config.banners
	icons         = $config.icons
}

New-Item -ItemType Directory -Path $trackRoot -Force | Out-Null
$zipPath = Join-Path $trackRoot 'plugin.zip'
$jsonPath = Join-Path $trackRoot 'metadata.json'

& tar -a -c -f $zipPath -C $trackStageRoot $config.slug
if ($LASTEXITCODE -ne 0) {
	throw "ZIP packaging failed for $Track with tar exit code $LASTEXITCODE."
}

$json = ($jsonConfig | ConvertTo-Json -Depth 10).Replace('\u003c', '<').Replace('\u003e', '>').Replace('\u0026', '&')
[System.IO.File]::WriteAllBytes($jsonPath, [System.Text.Encoding]::UTF8.GetBytes($json + [Environment]::NewLine))

if (Test-Path -LiteralPath $trackStageRoot) {
	Remove-Item -LiteralPath $trackStageRoot -Recurse -Force
}

if (Test-Path -LiteralPath $stageRoot) {
	$remainingStageItems = Get-ChildItem -LiteralPath $stageRoot -Force -ErrorAction SilentlyContinue
	if (-not $remainingStageItems) {
		Remove-Item -LiteralPath $stageRoot -Force
	}
}

Write-Host "Deployment package created:"
Write-Host "Track:   $Track"
Write-Host "ZIP:     $zipPath"
Write-Host "JSON:    $jsonPath"
Write-Host "Version: $($config.version)"
