# Pull Live Server Logs Script
# Downloads error logs from production server to local livelog folder

param(
    [string]$ServerHost = "75.119.140.177",
    [string]$ServerUser = "root",
    [string]$RemotePath = "/usr/share/nginx/html/safari/safarichat/storage/logs",
    [string]$LocalPath = "storage/livelog",
    [switch]$Verbose
)

# Color output functions
function Write-Success { param($Message) Write-Host $Message -ForegroundColor Green }
function Write-Info { param($Message) Write-Host $Message -ForegroundColor Cyan }
function Write-Warning { param($Message) Write-Host $Message -ForegroundColor Yellow }
function Write-Error { param($Message) Write-Host $Message -ForegroundColor Red }

# Script header
Write-Host "`n========================================" -ForegroundColor Cyan
Write-Host "   Live Server Log Retrieval Script" -ForegroundColor Cyan
Write-Host "========================================`n" -ForegroundColor Cyan

# Get script directory and project root
$scriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$projectRoot = Split-Path -Parent $scriptDir
Set-Location $projectRoot

Write-Info "Project root: $projectRoot"

# Ensure local directory exists
$fullLocalPath = Join-Path $projectRoot $LocalPath
if (-not (Test-Path $fullLocalPath)) {
    Write-Info "Creating local log directory: $fullLocalPath"
    New-Item -ItemType Directory -Path $fullLocalPath -Force | Out-Null
}

Write-Success "✓ Local directory ready: $fullLocalPath`n"

# Check if OpenSSH client is available
$scpCommand = Get-Command scp -ErrorAction SilentlyContinue
if (-not $scpCommand) {
    Write-Error "✗ SCP command not found!"
    Write-Warning "`nOpenSSH client is required. Please install it:"
    Write-Host "  1. Open Settings - Apps - Optional Features"
    Write-Host "  2. Click 'Add a feature'"
    Write-Host "  3. Search for 'OpenSSH Client' and install it"
    Write-Host "`nOr use Windows Terminal/PowerShell as Administrator:"
    Write-Host "  Add-WindowsCapability -Online -Name OpenSSH.Client*"
    exit 1
}

Write-Success "✓ OpenSSH client found"

# Ensure SSH agent is running and key is loaded — prevents per-connection passphrase prompts
Write-Info "`nChecking SSH agent..."
try {
    $agentService = Get-Service -Name ssh-agent -ErrorAction SilentlyContinue
    if ($agentService -and $agentService.Status -ne 'Running') {
        Start-Service ssh-agent -ErrorAction SilentlyContinue
        Write-Host "  SSH agent started" -ForegroundColor Gray
    }
    # Check if key is already loaded in agent
    $loadedKeys = & ssh-add -l 2>&1
    $keyPath = "$env:USERPROFILE\.ssh\id_rsa"
    if ($loadedKeys -match "no identities" -or $loadedKeys -match "Could not") {
        Write-Host "  Adding SSH key to agent (enter passphrase once)..." -ForegroundColor Yellow
        & ssh-add $keyPath
    } else {
        Write-Success "  ✓ SSH key already loaded in agent — no passphrase needed"
    }
} catch {
    Write-Warning "  Could not configure SSH agent: $($_.Exception.Message)"
    Write-Host "  Continuing — you may be prompted for passphrase" -ForegroundColor Gray
}

# Display connection info
Write-Info "`nConnection Details:"
Write-Host "  Server: $ServerUser@$ServerHost" -ForegroundColor White
Write-Host "  Remote Path: $RemotePath" -ForegroundColor White
Write-Host "  Local Path: $fullLocalPath" -ForegroundColor White
Write-Host ""

# Prepare SCP command
$remoteLocation = "${ServerUser}@${ServerHost}:${RemotePath}/*"
$timestamp = Get-Date -Format "yyyy-MM-dd_HH-mm-ss"

Write-Info "Starting log download..."
Write-Host "Command: scp -r $remoteLocation $fullLocalPath" -ForegroundColor Gray
Write-Host ""

try {
    # Execute SCP command
    # -r for recursive (to get all files in logs directory)
    # -p to preserve modification times and modes
    # -C to enable compression
    
    $scpArgs = @(
        "-r",           # Recursive
        "-p",           # Preserve times
        "-C",           # Compression
        $remoteLocation,
        $fullLocalPath
    )
    
    if ($Verbose) {
        $scpArgs = @("-v") + $scpArgs
    }
    
    # Run SCP
    & scp $scpArgs
    
    if ($LASTEXITCODE -eq 0) {
        Write-Host ""
        Write-Success "✓ Logs successfully downloaded!"
        
        # List downloaded files
        Write-Info "`nDownloaded files:"
        Get-ChildItem $fullLocalPath -File | ForEach-Object {
            $size = if ($_.Length -gt 1MB) {
                "{0:N2} MB" -f ($_.Length / 1MB)
            } elseif ($_.Length -gt 1KB) {
                "{0:N2} KB" -f ($_.Length / 1KB)
            } else {
                "$($_.Length) bytes"
            }
            Write-Host "  • $($_.Name)" -ForegroundColor White -NoNewline
            Write-Host " ($size)" -ForegroundColor Gray
        }
        
        # Create backup with timestamp
        Write-Info "`nCreating timestamped backup..."
        $backupDir = Join-Path $fullLocalPath "backups"
        if (-not (Test-Path $backupDir)) {
            New-Item -ItemType Directory -Path $backupDir -Force | Out-Null
        }
        
        Get-ChildItem $fullLocalPath -File -Filter "*.log" | ForEach-Object {
            $backupName = "$($_.BaseName)_${timestamp}$($_.Extension)"
            Copy-Item $_.FullName -Destination (Join-Path $backupDir $backupName)
        }
        Write-Success "✓ Backup created in: $backupDir"
        
        # Run deduplication if available
        $dedupeScript = Join-Path $scriptDir "deduplicate-log.ps1"
        if (Test-Path $dedupeScript) {
            Write-Info "`nRunning log deduplication..."
            & $dedupeScript
        }
        
        Write-Host "`n" -NoNewline
        Write-Success "========================================"
        Write-Success "   Log retrieval completed successfully!"
        Write-Success "========================================`n"
        
    } else {
        Write-Error "✗ SCP command failed with exit code: $LASTEXITCODE"
        Write-Warning "`nTroubleshooting tips:"
        Write-Host "  1. Verify SSH access: ssh $ServerUser@$ServerHost"
        Write-Host "  2. Check if remote path exists"
        Write-Host "  3. Verify SSH key is configured or enter password when prompted"
        Write-Host "  4. Check firewall settings"
        exit 1
    }
    
} catch {
    Write-Error "✗ Error occurred: $($_.Exception.Message)"
    Write-Host $_.ScriptStackTrace -ForegroundColor Red
    exit 1
}

# Summary
Write-Host ""
Write-Info "Next steps:"
Write-Host "  • Review logs in: $fullLocalPath"
Write-Host "  • Check deduplicated logs if generated"
Write-Host "  • Analyze errors for production issues"
Write-Host ""
