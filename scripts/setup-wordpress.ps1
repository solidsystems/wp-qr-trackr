# WordPress Auto-Setup Script for Windows PowerShell
# Automatically installs WordPress with trackr:trackr credentials

param(
    [Parameter(Position=0)]
    [ValidateSet("dev", "nonprod")]
    [string]$Environment = "dev"
)

$ErrorActionPreference = "Stop"

# Environment configuration
switch ($Environment) {
    "dev" {
        $SiteUrl = "http://localhost:8080"
        $ContainerPrefix = "wpqrdev"
        $WpContainer = "wordpress-dev"
    }
    "nonprod" {
        $SiteUrl = "http://localhost:8081"
        $ContainerPrefix = "wpqrnonprod"
        $WpContainer = "wordpress-nonprod"
    }
    default {
        Write-Host "Usage: .\setup-wordpress.ps1 [dev|nonprod]"
        Write-Host "Environment must be 'dev' or 'nonprod'"
        exit 1
    }
}

Write-Host "🚀 Setting up WordPress for $Environment environment..." -ForegroundColor Green
Write-Host "📍 Site URL: $SiteUrl"

# Function to run WP-CLI commands
function Invoke-WpCli {
    param([string]$Command)
    
    $dockerCmd = @"
cd /var/www/html
if [ ! -f /tmp/wp-cli.phar ]; then
    curl -L -o /tmp/wp-cli.phar https://github.com/wp-cli/wp-cli/releases/download/v2.8.1/wp-cli-2.8.1.phar
    chmod +x /tmp/wp-cli.phar
fi
php -d memory_limit=1G /tmp/wp-cli.phar --allow-root $Command
"@
    
    docker compose -p $ContainerPrefix exec $WpContainer bash -c $dockerCmd
}

# Function to check WordPress accessibility
function Test-WordPressReady {
    try {
        $response = Invoke-WebRequest -Uri $SiteUrl -UseBasicParsing -TimeoutSec 5
        return $response.StatusCode -in @(200, 301, 302)
    }
    catch {
        return $false
    }
}

# Wait for containers
Write-Host "⏳ Waiting for containers to be ready..."
Start-Sleep -Seconds 10

# Wait for WordPress container
Write-Host "🔍 Waiting for WordPress container to be accessible..."
for ($i = 1; $i -le 12; $i++) {
    if (Test-WordPressReady) {
        Write-Host "✅ WordPress container is accessible!" -ForegroundColor Green
        break
    }
    Write-Host "⏳ Waiting for WordPress... (attempt $i/12)"
    Start-Sleep -Seconds 5
}

Start-Sleep -Seconds 5

# Check if WordPress is installed
Write-Host "🔍 Checking WordPress installation status..."
$isInstalled = $false
try {
    Invoke-WpCli "core is-installed" | Out-Null
    $isInstalled = $true
    Write-Host "✅ WordPress is already installed!" -ForegroundColor Green
}
catch {
    Write-Host "📦 Installing WordPress with trackr:trackr credentials..." -ForegroundColor Yellow
    
    # Create wp-config.php if needed
    try {
        Invoke-WpCli "config path" | Out-Null
    }
    catch {
        Write-Host "⚙️ Creating WordPress configuration..."
        Invoke-WpCli "config create --dbname=wpdb --dbuser=wpuser --dbpass=wppass --dbhost=db-$Environment`:3306 --force"
    }
    
    # Install WordPress
    Write-Host "📦 Installing WordPress..."
    $title = if ($Environment -eq "dev") { "QR Trackr Development" } else { "QR Trackr Nonprod" }
    Invoke-WpCli "core install --url='$SiteUrl' --title='$title' --admin_user='trackr' --admin_password='trackr' --admin_email='admin@example.com' --skip-email"
    Write-Host "✅ WordPress installed successfully!" -ForegroundColor Green
}

# Configure WordPress
Write-Host "🔗 Setting permalink structure..."
try { Invoke-WpCli "rewrite structure '/%postname%/'" } catch { Write-Host "⚠️ Warning: Could not set permalink structure" -ForegroundColor Yellow }

Write-Host "🔄 Flushing rewrite rules..."
try { Invoke-WpCli "rewrite flush --hard" } catch { Write-Host "⚠️ Warning: Could not flush rewrite rules" -ForegroundColor Yellow }

if (-not $isInstalled) {
    Write-Host "⚙️ Configuring WordPress for development..."
    
    if ($Environment -eq "dev") {
        try { Invoke-WpCli "config set WP_DEBUG true --raw --type=constant" } catch {}
        try { Invoke-WpCli "config set WP_DEBUG_LOG true --raw --type=constant" } catch {}
        try { Invoke-WpCli "config set WP_DEBUG_DISPLAY false --raw --type=constant" } catch {}
    }
    
    try { Invoke-WpCli "option update timezone_string 'America/New_York'" } catch {}
    try { Invoke-WpCli "option update blogdescription 'QR Code Generation and Tracking'" } catch {}
}

# Activate plugin for dev
if ($Environment -eq "dev") {
    Write-Host "🔌 Activating QR Trackr plugin..."
    try {
        $plugins = Invoke-WpCli "plugin list"
        if ($plugins -match "wp-qr-trackr") {
            Invoke-WpCli "plugin activate wp-qr-trackr"
        }
        else {
            Write-Host "⚠️ QR Trackr plugin not found - check plugin mount" -ForegroundColor Yellow
        }
    }
    catch {
        Write-Host "⚠️ Could not activate QR Trackr plugin" -ForegroundColor Yellow
    }
}

# Success message
Write-Host ""
Write-Host "🎉 WordPress setup complete!" -ForegroundColor Green
Write-Host "==============================" -ForegroundColor Green
Write-Host "📱 Site URL: $SiteUrl"
Write-Host "👤 Username: trackr"
Write-Host "🔑 Password: trackr"
Write-Host "📧 Email: admin@example.com"
Write-Host ""
Write-Host "🔗 Quick Access URLs:"
Write-Host "🏠 Site: $SiteUrl"
Write-Host "🔧 Admin: $SiteUrl/wp-admin"
Write-Host "⚙️ Plugins: $SiteUrl/wp-admin/plugins.php"

if ($Environment -eq "dev") {
    Write-Host "🔌 QR Trackr Plugin: $SiteUrl/wp-admin/admin.php?page=qr-codes"
}

Write-Host ""
Write-Host "✅ WordPress is ready to use! No browser setup required." -ForegroundColor Green 