#!/usr/bin/env bash
# setup-laravel.sh - Interactive Laravel Configuration CLI

set -e

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
RED='\033[0;31m'
NC='\033[0m'

log()     { echo -e "${GREEN}✓${NC} $1"; }
info()    { echo -e "${BLUE}→${NC} $1"; }
warn()    { echo -e "${YELLOW}!${NC} $1"; }
error()   { echo -e "${RED}✗${NC} $1"; exit 1; }
section() { echo -e "\n${BLUE}── $1 ──────────────────────────────────${NC}"; }

# Function to prompt Yes/No
ask() {
  local prompt="$1"
  local default="$2"
  local reply
  
  if [ "$default" = "Y" ]; then
    prompt="$prompt [Y/n]"
  else
    prompt="$prompt [y/N]"
  fi
  
  while true; do
    echo -e -n "${YELLOW}?${NC} $prompt "
    read -r reply </dev/tty
    
    if [ -z "$reply" ]; then
      reply=$default
    fi
    
    case "$reply" in
      Y*|y*) return 0 ;;
      N*|n*) return 1 ;;
    esac
  done
}

# Ensure we are in a Laravel project
if [ ! -f "artisan" ]; then
    error "This script must be run from the root of a Laravel project."
fi

section "Laravel Setup CLI"
info "Select the components you want to install and configure for this project."

INSTALL_SAIL=false
INSTALL_TELESCOPE=false
INSTALL_BOOST=true
INSTALL_SANCTUM=false
INSTALL_ACTIVITYLOG=false
SETUP_QUEUES=false

info "Laravel Boost will be installed automatically (Required)."
if ask "Install Laravel Sail (Docker environment)?" "Y"; then INSTALL_SAIL=true; fi
if ask "Install Laravel Telescope (Debug assistant)?" "Y"; then INSTALL_TELESCOPE=true; fi
if ask "Install Laravel Sanctum (API Authentication)?" "N"; then INSTALL_SANCTUM=true; fi
if ask "Install Spatie Activitylog (User activity logging)?" "N"; then INSTALL_ACTIVITYLOG=true; fi
if ask "Setup Database Queues/Jobs?" "N"; then SETUP_QUEUES=true; fi

section "Installing Packages"

# Helper to inject to routes/console.php
inject_schedule() {
    local code="$1"
    local console_file="routes/console.php"
    
    if [ ! -f "$console_file" ]; then
        warn "routes/console.php not found. Creating it..."
        echo "<?php" > "$console_file"
        echo "" >> "$console_file"
        echo "use Illuminate\Support\Facades\Schedule;" >> "$console_file"
    fi
    
    # Ensure Schedule facade is imported if not present
    if ! grep -q "use Illuminate\\\\Support\\\\Facades\\\\Schedule;" "$console_file" && ! grep -q "use Illuminate\Support\Facades\Schedule;" "$console_file"; then
        sed -i '' 's/<?php/<?php\n\nuse Illuminate\\Support\\Facades\\Schedule;/' "$console_file" 2>/dev/null || true
    fi
    
    if ! grep -q "$code" "$console_file"; then
        echo "" >> "$console_file"
        echo "$code" >> "$console_file"
        log "Injected schedule into routes/console.php"
    fi
}

if $INSTALL_SAIL; then
    info "Installing Laravel Sail..."
    composer require laravel/sail --dev
    php artisan sail:install --with=mysql,redis,meilisearch,mailpit,selenium || true
    log "Sail installed."
fi

if $INSTALL_TELESCOPE; then
    info "Installing Laravel Telescope..."
    composer require laravel/telescope --dev
    php artisan telescope:install || true
    php artisan migrate || warn "Migration failed (is database running?). Run manually later."
    inject_schedule "Schedule::command('telescope:prune --hours=48')->daily();"
    log "Telescope installed and scheduled."
fi

if $INSTALL_BOOST; then
    info "Installing Laravel Boost..."
    composer require laravel/boost --dev
    php artisan boost:install || true
    log "Laravel Boost installed."
fi

if $INSTALL_SANCTUM; then
    info "Installing Laravel Sanctum..."
    composer require laravel/sanctum
    php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider" || true
    php artisan migrate || warn "Migration failed. Run manually later."
    log "Sanctum installed."
fi

if $INSTALL_ACTIVITYLOG; then
    info "Installing Spatie Activitylog..."
    composer require spatie/laravel-activitylog
    php artisan vendor:publish --provider="Spatie\Activitylog\ActivitylogServiceProvider" --tag="activitylog-migrations" || true
    php artisan migrate || warn "Migration failed. Run manually later."
    inject_schedule "Schedule::command('activitylog:clean --force')->daily();"
    log "Activitylog installed and scheduled."
fi

if $SETUP_QUEUES; then
    info "Setting up Database Queues..."
    php artisan queue:table || true
    php artisan queue:batches-table || true
    php artisan queue:failed-table || true
    php artisan migrate || warn "Migration failed. Run manually later."
    inject_schedule "Schedule::command('queue:prune-failed --hours=360')->daily();"
    log "Queues configured and scheduled."
fi

section "Setup Complete!"
info "Your Laravel project is ready to go."
