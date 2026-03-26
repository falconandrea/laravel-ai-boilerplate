#!/usr/bin/env bash
# setup-skills.sh — AI agent skill setup for Laravel projects
# Usage: bash setup-skills.sh [--agent antigravity|opencode|all]

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

PROJECT_TYPE="laravel"
AGENT_FLAGS=""

while [[ $# -gt 0 ]]; do
  case "$1" in
    --agent)
      shift
      case "$1" in
        antigravity) AGENT_FLAGS="-a antigravity" ;;
        opencode)    AGENT_FLAGS="-a opencode" ;;
        all)         AGENT_FLAGS="-a antigravity -a opencode" ;;
        *)           error "Agent not recognized: $1. Use antigravity, opencode or all." ;;
      esac
      shift ;;
    -h|--help)
      echo "Usage: bash setup-skills.sh [--agent antigravity|opencode|all]"
      exit 0 ;;
    *) error "Unrecognized argument: $1. Use --help for instructions." ;;
  esac
done

if [[ -z "$AGENT_FLAGS" ]]; then
  echo ""
  echo "  Agent target:"
  echo "  1) Antigravity"
  echo "  2) OpenCode"
  echo "  3) Both (recommended)"
  echo ""
  read -rp "  Choice [1-3]: " agentchoice
  case "$agentchoice" in
    1) AGENT_FLAGS="-a antigravity" ;;
    2) AGENT_FLAGS="-a opencode" ;;
    3) AGENT_FLAGS="-a antigravity -a opencode" ;;
    *) error "Invalid choice." ;;
  esac
fi

echo ""
info "Project: Laravel | Agent: ${AGENT_FLAGS/-a /}"
echo ""

if ! command -v npx &>/dev/null; then
  error "npx not found. Please install Node.js before continuing."
fi

install_skill() {
  local repo="$1"
  local skill_flags="$2"
  info "Installing from ${repo} ${skill_flags}..."
  # shellcheck disable=SC2086
  npx --yes skills add "$repo" $skill_flags $AGENT_FLAGS || true
  log "OK: ${repo}"
}

section "Common skills"
install_skill "obra/superpowers" \
  "--skill systematic-debugging test-driven-development verification-before-completion"
install_skill "zackkorman/skills" "--skill security-review"
install_skill "anthropics/skills" "--skill webapp-testing"

section "Laravel / PHP skills"
install_skill "jpcaparas/superpowers-laravel" ""
install_skill "jeffallan/claude-skills" "--skill php-pro"
install_skill "vercel-labs/agent-skills" "--skill web-design-guidelines"

if [[ -f "composer.json" ]] && grep -q "laravel/boost" composer.json 2>/dev/null; then
  section "Laravel Boost detected"
  warn "Make sure you have already run: php artisan boost:install"
else
  warn "Laravel Boost not found. You can install it with:"
  warn "  composer require laravel/boost --dev && php artisan boost:install"
fi

section "Tech stack context"
info "Laravel projects use Laravel Boost for context — no TECH_STACK template needed."
info "TECH_STACK.md will be filled during /setup or by Boost."


section "Setup complete"
echo ""
echo "  Skills installed for: Laravel"
echo ""
echo "  Next steps:"
echo "  1) Install Laravel Boost: composer require laravel/boost --dev && php artisan boost:install"
echo "  2) Run /setup to generate project context (incl. TECH_STACK.md)"
echo "  3) Verify skills: npx skills list"
echo ""
