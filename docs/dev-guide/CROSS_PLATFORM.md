# Cross-Platform Setup Guide

This document provides setup instructions for running the WP QR Trackr development environment across different operating systems and architectures.

## Platform Compatibility Matrix

| Platform | Architecture | Docker Support | Script Support | Status |
|----------|-------------|----------------|----------------|---------|
| macOS | Intel (x86_64) | ✅ Native | ✅ bash | Excellent |
| macOS | Apple Silicon (ARM64) | ✅ Native | ✅ bash | Excellent |
| Windows 10/11 | Intel/AMD64 | ✅ WSL2/Hyper-V | ⚠️ Requires WSL/Git Bash | Good |
| Ubuntu/Debian | AMD64 | ✅ Native | ✅ bash | Excellent |
| Ubuntu/Debian | ARM64 | ✅ Native | ✅ bash | Excellent |
| CentOS/RHEL | AMD64 | ✅ Native | ✅ bash | Excellent |
| Arch Linux | AMD64 | ✅ Native | ✅ bash | Excellent |

## Prerequisites

### All Platforms
- **Docker Desktop** (Windows/macOS) or **Docker Engine** (Linux)
- **Docker Compose** v2.x+
- **Git**
- **curl** (usually pre-installed)

### Platform-Specific Requirements

#### macOS
```bash
# Install Docker Desktop from https://docker.com
# Or via Homebrew:
brew install --cask docker

# Start Docker Desktop from Applications
```

#### Windows
```powershell
# Option 1: WSL2 + Docker Desktop (Recommended)
# 1. Install WSL2: https://docs.microsoft.com/en-us/windows/wsl/install
# 2. Install Docker Desktop with WSL2 backend
# 3. Enable WSL2 integration in Docker Desktop settings

# Option 2: Hyper-V + Docker Desktop
# Install Docker Desktop with Hyper-V backend
```

#### Ubuntu/Debian
```bash
# Install Docker Engine
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh

# Add user to docker group
sudo usermod -aG docker $USER
newgrp docker

# Install Docker Compose
sudo apt-get update
sudo apt-get install docker-compose-plugin
```

#### CentOS/RHEL/Fedora
```bash
# Install Docker Engine
sudo dnf install -y docker docker-compose
sudo systemctl enable --now docker
sudo usermod -aG docker $USER
```

## Setup Instructions by Platform

### macOS (Intel & Apple Silicon)

Both Intel and Apple Silicon Macs work identically:

```bash
# Clone the repository
git clone https://github.com/your-org/wp-qr-trackr.git
cd wp-qr-trackr

# Start both environments
docker-compose -f docker-compose.dev.yml -p wpqrdev up -d
docker-compose -f docker-compose.yml -p wpqrnonprod up -d

# Auto-setup WordPress with trackr:trackr credentials
./scripts/setup-wordpress.sh dev
./scripts/setup-wordpress.sh nonprod

# Access environments
open http://localhost:8080  # Dev environment
open http://localhost:8081  # Nonprod environment
```

### Windows

#### Option 1: WSL2 (Recommended)
```bash
# Open WSL2 Ubuntu terminal
wsl

# Navigate to project (assuming cloned in Windows)
cd /mnt/c/path/to/wp-qr-trackr

# Start environments
docker-compose -f docker-compose.dev.yml -p wpqrdev up -d
docker-compose -f docker-compose.yml -p wpqrnonprod up -d

# Setup WordPress
./scripts/setup-wordpress.sh dev
./scripts/setup-wordpress.sh nonprod
```

#### Option 2: PowerShell
```powershell
# Clone the repository
git clone https://github.com/your-org/wp-qr-trackr.git
cd wp-qr-trackr

# Start environments
docker-compose -f docker-compose.dev.yml -p wpqrdev up -d
docker-compose -f docker-compose.yml -p wpqrnonprod up -d

# Setup WordPress using PowerShell script
.\scripts\setup-wordpress.ps1 dev
.\scripts\setup-wordpress.ps1 nonprod

# Access environments
start http://localhost:8080  # Dev environment
start http://localhost:8081  # Nonprod environment
```

#### Option 3: Git Bash
```bash
# Open Git Bash terminal
# Navigate to project
cd /c/path/to/wp-qr-trackr

# Start environments (same as macOS/Linux)
docker-compose -f docker-compose.dev.yml -p wpqrdev up -d
docker-compose -f docker-compose.yml -p wpqrnonprod up -d

# Setup WordPress
./scripts/setup-wordpress.sh dev
./scripts/setup-wordpress.sh nonprod
```

### Linux (Ubuntu/Debian/CentOS/Fedora/Arch)

All Linux distributions work identically:

```bash
# Clone the repository
git clone https://github.com/your-org/wp-qr-trackr.git
cd wp-qr-trackr

# Start environments
docker-compose -f docker-compose.dev.yml -p wpqrdev up -d
docker-compose -f docker-compose.yml -p wpqrnonprod up -d

# Setup WordPress
./scripts/setup-wordpress.sh dev
./scripts/setup-wordpress.sh nonprod

# Access environments
xdg-open http://localhost:8080  # Dev environment (GUI systems)
xdg-open http://localhost:8081  # Nonprod environment (GUI systems)
```

## Multi-Architecture Support

### ARM64/Apple Silicon Notes
- All Docker images are multi-architecture and work natively
- No Rosetta emulation required
- Performance is excellent on Apple Silicon
- No special configuration needed

### Intel/AMD64 Systems
- Standard Docker images work natively
- Maximum performance on Intel/AMD processors
- Wide compatibility across all platforms

## File Permissions & Ownership

### macOS & Linux
- Docker handles file permissions automatically
- Volume mounts work seamlessly
- No additional configuration required

### Windows
- WSL2: File permissions handled by WSL
- Native Windows: Docker Desktop manages permissions
- Git should be configured with `core.autocrlf=input`

```bash
# Configure Git for proper line endings
git config --global core.autocrlf input
git config --global core.eol lf
```

## Common Issues & Solutions

### Port Conflicts
If ports 8080 or 8081 are in use:

```bash
# Check what's using the ports
# macOS/Linux:
lsof -i :8080
lsof -i :8081

# Windows (PowerShell):
netstat -an | findstr :8080
netstat -an | findstr :8081

# Kill conflicting processes or change ports in docker-compose files
```

### Docker Permission Issues (Linux)
```bash
# Add user to docker group
sudo usermod -aG docker $USER
newgrp docker

# Or use sudo for docker commands
sudo docker-compose up -d
```

### Memory Issues
```bash
# Increase Docker memory allocation in Docker Desktop settings
# Recommended: 4GB+ for development environments
```

### Windows Line Ending Issues
```bash
# If scripts fail due to line endings, convert them:
dos2unix scripts/*.sh

# Or configure Git properly:
git config --global core.autocrlf input
git config --global core.eol lf
```

## Performance Optimization

### macOS
- Use Docker Desktop with VirtioFS for better volume performance
- Allocate 4GB+ RAM to Docker in preferences
- Enable "Use Rosetta for x86/amd64 emulation" if needed

### Windows
- Use WSL2 backend for best performance
- Store project files in WSL2 filesystem (not /mnt/c/)
- Allocate sufficient memory in Docker Desktop settings

### Linux
- Native Docker performance is optimal
- Ensure sufficient disk space for volumes
- Consider using tmpfs for temporary files if needed

## Verification

After setup, verify all components are working:

```bash
# Check container status
docker ps

# Test environments
curl -I http://localhost:8080
curl -I http://localhost:8081

# Check WordPress admin access
curl -I http://localhost:8080/wp-admin
curl -I http://localhost:8081/wp-admin
```

Expected response: `HTTP/1.1 200 OK` or `HTTP/1.1 302 Found`

## Troubleshooting

### Container Issues
```bash
# View container logs
docker logs wpqrdev-wordpress-dev-1
docker logs wpqrnonprod-wordpress-nonprod-1

# Restart containers
docker-compose -f docker-compose.dev.yml -p wpqrdev restart
docker-compose -f docker-compose.yml -p wpqrnonprod restart

# Clean restart
docker-compose -f docker-compose.dev.yml -p wpqrdev down
docker-compose -f docker-compose.yml -p wpqrnonprod down
docker-compose -f docker-compose.dev.yml -p wpqrdev up -d
docker-compose -f docker-compose.yml -p wpqrnonprod up -d
```

### WordPress Issues
```bash
# Re-run setup scripts
./scripts/setup-wordpress.sh dev
./scripts/setup-wordpress.sh nonprod

# Windows PowerShell:
.\scripts\setup-wordpress.ps1 dev
.\scripts\setup-wordpress.ps1 nonprod
```

## Contributing

When contributing from different platforms:

1. Ensure Docker Desktop/Engine is updated to latest version
2. Test changes on your platform before submitting PRs
3. Use the automated setup scripts for consistency
4. Report platform-specific issues with full environment details

## Support

For platform-specific issues:
- macOS: Check Docker Desktop logs and settings
- Windows: Verify WSL2 integration or Hyper-V configuration
- Linux: Ensure docker group membership and service status
- All platforms: Verify Docker Compose v2.x+ is installed 