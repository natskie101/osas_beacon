# ==========================================================================
# BEACON OSAS Portal - local development server
# Serves the portal over http://127.0.0.1 so localStorage, sessions and
# downloads behave exactly as they would on a real web host.
#
#   Run:  powershell -ExecutionPolicy Bypass -File tools\serve.ps1
#   Stop: Ctrl+C
# ==========================================================================
param(
  [int]$Port = 8080,
  [string]$Root = (Split-Path -Parent $PSScriptRoot)
)

$types = @{
  '.html' = 'text/html; charset=utf-8'
  '.js'   = 'application/javascript; charset=utf-8'
  '.css'  = 'text/css; charset=utf-8'
  '.json' = 'application/json; charset=utf-8'
  '.svg'  = 'image/svg+xml'
  '.png'  = 'image/png'
  '.ico'  = 'image/x-icon'
  '.sql'  = 'text/plain; charset=utf-8'
  '.md'   = 'text/plain; charset=utf-8'
}

$listener = New-Object System.Net.HttpListener
$listener.Prefixes.Add("http://127.0.0.1:$Port/")
try {
  $listener.Start()
} catch {
  Write-Host "Could not start on port $Port - is another server already running?" -ForegroundColor Red
  exit 1
}
Write-Host "BEACON OSAS Portal running:" -ForegroundColor Green
Write-Host "  http://127.0.0.1:$Port/   (root: $Root)"
Write-Host "Press Ctrl+C to stop."

$rootFull = [IO.Path]::GetFullPath($Root)

while ($listener.IsListening) {
  $ctx = $null
  try {
    $ctx = $listener.GetContext()
    $localPath = $ctx.Request.Url.LocalPath
    if ($localPath -eq '/') { $localPath = '/index.html' }
    $relative = [Uri]::UnescapeDataString($localPath).TrimStart('/', '\')
    $file = [IO.Path]::GetFullPath((Join-Path $rootFull $relative))

    if ($file.StartsWith($rootFull, [StringComparison]::OrdinalIgnoreCase) -and (Test-Path $file -PathType Leaf)) {
      $bytes = [IO.File]::ReadAllBytes($file)
      $ext = [IO.Path]::GetExtension($file).ToLower()
      $ctx.Response.ContentType = $types[$ext]
      if (-not $ctx.Response.ContentType) { $ctx.Response.ContentType = 'application/octet-stream' }
      $ctx.Response.ContentLength64 = $bytes.Length
      $ctx.Response.OutputStream.Write($bytes, 0, $bytes.Length)
    } else {
      $msg = [Text.Encoding]::UTF8.GetBytes("404 Not Found")
      $ctx.Response.StatusCode = 404
      $ctx.Response.OutputStream.Write($msg, 0, $msg.Length)
    }
  } catch {
    Write-Warning $_.Exception.Message
  } finally {
    if ($ctx -ne $null) { try { $ctx.Response.Close() } catch { } }
  }
}
