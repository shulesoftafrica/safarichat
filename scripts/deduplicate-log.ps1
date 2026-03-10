# Deduplicate Laravel Log File
# This script removes duplicate log entries while preserving unique errors

$logFile = "storage/livelog/laravel.log"
$outputFile = "storage/livelog/laravel-deduplicated.log"
$statsFile = "storage/livelog/deduplication-stats.txt"

Write-Host "Starting log deduplication..." -ForegroundColor Cyan
Write-Host "Input file: $logFile" -ForegroundColor Yellow
Write-Host "Output file: $outputFile" -ForegroundColor Yellow

# Initialize counters
$totalLines = 0
$uniqueEntries = @{}
$duplicateCount = 0
$currentEntry = ""
$currentKey = ""

# Read and process the log file
Write-Host "`nProcessing log entries..." -ForegroundColor Cyan

Get-Content $logFile | ForEach-Object {
    $totalLines++
    
    if ($totalLines % 10000 -eq 0) {
        Write-Host "Processed $totalLines lines..." -ForegroundColor Gray
    }
    
    $line = $_
    
    # Check if this is a new log entry (starts with [timestamp])
    if ($line -match '^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] (\w+\.\w+): (.+)$') {
        # If we have a previous entry, check if it's unique
        if ($currentEntry -ne "") {
            if (-not $uniqueEntries.ContainsKey($currentKey)) {
                $uniqueEntries[$currentKey] = $currentEntry
            } else {
                $duplicateCount++
            }
        }
        
        # Start new entry
        $timestamp = $matches[1]
        $level = $matches[2]
        $message = $matches[3]
        
        # Create a unique key excluding timestamp but including level and message pattern
        # Remove JSON data and timestamps from the key for better deduplication
        $keyMessage = $message -replace '\{.*?\}$', '' # Remove JSON at end
        $keyMessage = $keyMessage -replace '\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}', 'TIMESTAMP' # Replace timestamps
        $keyMessage = $keyMessage -replace '\d+', 'NUMBER' # Replace numbers
        $keyMessage = $keyMessage -replace '"[^"]*"', 'STRING' # Replace quoted strings
        
        $currentKey = "$level|$keyMessage"
        $currentEntry = $line
    } else {
        # This is a continuation of the previous entry (stack trace, etc.)
        $currentEntry += "`n$line"
    }
}

# Don't forget the last entry
if ($currentEntry -ne "") {
    if (-not $uniqueEntries.ContainsKey($currentKey)) {
        $uniqueEntries[$currentKey] = $currentEntry
    } else {
        $duplicateCount++
    }
}

# Write unique entries to output file
Write-Host "`nWriting deduplicated log..." -ForegroundColor Cyan
$uniqueEntries.Values | Sort-Object | Out-File -FilePath $outputFile -Encoding UTF8

# Calculate statistics
$uniqueCount = $uniqueEntries.Count
$deduplicationRate = [math]::Round(($duplicateCount / $totalLines) * 100, 2)
$originalSize = (Get-Item $logFile).Length
$newSize = (Get-Item $outputFile).Length
$spaceSaved = $originalSize - $newSize
$spaceSavedMB = [math]::Round($spaceSaved / 1MB, 2)
$compressionRate = [math]::Round(($spaceSaved / $originalSize) * 100, 2)

# Create statistics report
$stats = @"
=== Log Deduplication Statistics ===
Date: $(Get-Date -Format "yyyy-MM-dd HH:mm:ss")

INPUT FILE: $logFile
OUTPUT FILE: $outputFile

PROCESSING RESULTS:
- Total lines processed: $totalLines
- Unique log patterns: $uniqueCount
- Duplicate entries removed: $duplicateCount
- Deduplication rate: $deduplicationRate%

FILE SIZE:
- Original size: $([math]::Round($originalSize / 1MB, 2)) MB ($originalSize bytes)
- Deduplicated size: $([math]::Round($newSize / 1MB, 2)) MB ($newSize bytes)
- Space saved: $spaceSavedMB MB ($spaceSaved bytes)
- Compression rate: $compressionRate%

LOG PATTERN BREAKDOWN:
"@

# Add breakdown by log level
$levelStats = @{}
$uniqueEntries.Keys | ForEach-Object {
    $level = ($_ -split '\|')[0]
    if (-not $levelStats.ContainsKey($level)) {
        $levelStats[$level] = 0
    }
    $levelStats[$level]++
}

$stats += "`n"
$levelStats.GetEnumerator() | Sort-Object Name | ForEach-Object {
    $stats += "- $($_.Key): $($_.Value) unique patterns`n"
}

# Save statistics
$stats | Out-File -FilePath $statsFile -Encoding UTF8

# Display results
Write-Host "`n=== DEDUPLICATION COMPLETE ===" -ForegroundColor Green
Write-Host "Total lines processed: $totalLines" -ForegroundColor White
Write-Host "Unique log patterns: $uniqueCount" -ForegroundColor Green
Write-Host "Duplicates removed: $duplicateCount ($deduplicationRate%)" -ForegroundColor Yellow
Write-Host "Space saved: $spaceSavedMB MB ($compressionRate% compression)" -ForegroundColor Cyan
Write-Host "`nOutput files created:" -ForegroundColor Cyan
Write-Host "  - Deduplicated log: $outputFile" -ForegroundColor White
Write-Host "  - Statistics: $statsFile" -ForegroundColor White
Write-Host "`nLog level breakdown:" -ForegroundColor Cyan
$levelStats.GetEnumerator() | Sort-Object Name | ForEach-Object {
    Write-Host "  - $($_.Key): $($_.Value) unique patterns" -ForegroundColor White
}
