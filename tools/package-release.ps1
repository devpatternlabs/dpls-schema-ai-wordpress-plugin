# PowerShell Production Release Packaging Script
# Uses PHP ZipArchive to ensure standard RFC-compliant Unix forward slashes (/) in ZIP entries.
$rootDir = "C:\xampp\htdocs\DPLSplugin"
$phpBin = "C:\xampp\php\php.exe"
$phpScript = "$rootDir\tools\package-release.php"

if (Test-Path $phpBin) {
    & $phpBin $phpScript
} else {
    php $phpScript
}
