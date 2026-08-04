#!/usr/bin/env bash
#
# Remotox installer for Ubuntu on AWS Lightsail.
#
# Run as the default `ubuntu` user. Two ways to use it:
#
#   A. Clone first, then run from inside the checkout (recommended):
#        git clone -b main https://github.com/demigod412/remot.git ~/remotox
#        cd ~/remotox && bash install.sh
#      It detects that it is already inside the repo and skips cloning.
#
#   B. Fetch just this script and let it clone for you:
#        curl -fsSL https://raw.githubusercontent.com/demigod412/remot/main/install.sh -o install.sh
#        bash install.sh
#
# Override the branch if the code is not on main:
#        BRANCH=apply-fixes bash install.sh
#
# It is safe to re-run: every step checks before acting, and it will not overwrite an
# existing .env or an existing certificate without asking.
#
# WHAT IT DOES NOT DO
#   - It does not open the firewall for you. Lightsail's firewall lives in the AWS
#     console, not in the OS, so ports 80 and 443 must be opened there.
#   - It does not point DNS at the server. Do that in Cloudflare first, proxied
#     (orange cloud), or the origin certificate request will succeed while the site
#     stays unreachable.
#   - It does not send email. It configures SMTP, then asks you to test it from the
#     admin panel, because a send that fails silently is how members end up locked
#     out of accounts whose passwords only ever existed in an email.

set -euo pipefail

REPO_URL="${REPO_URL:-https://github.com/demigod412/remot.git}"
BRANCH="${BRANCH:-main}"
APP_DIR="${APP_DIR:-$HOME/remotox}"

# ── output helpers ──────────────────────────────────────────────────────────────
c_reset=$'\033[0m'; c_dim=$'\033[2m'; c_ok=$'\033[32m'; c_warn=$'\033[33m'; c_err=$'\033[31m'; c_hl=$'\033[36m'
step() { printf '\n%s==>%s %s\n' "$c_hl" "$c_reset" "$1"; }
ok()   { printf '%s  ok%s %s\n' "$c_ok" "$c_reset" "$1"; }
warn() { printf '%s  !!%s %s\n' "$c_warn" "$c_reset" "$1"; }
die()  { printf '\n%serror:%s %s\n' "$c_err" "$c_reset" "$1" >&2; exit 1; }
ask()  { # ask VAR "Prompt" ["default"]
  local __var=$1 __prompt=$2 __default=${3:-} __input=""
  if [ -n "$__default" ]; then
    read -r -p "$__prompt [$__default]: " __input || true
    __input=${__input:-$__default}
  else
    while [ -z "$__input" ]; do read -r -p "$__prompt: " __input || true; done
  fi
  printf -v "$__var" '%s' "$__input"
}
ask_secret() {
  local __var=$1 __prompt=$2 __input=""
  while [ -z "$__input" ]; do read -r -s -p "$__prompt: " __input || true; echo; done
  printf -v "$__var" '%s' "$__input"
}
yesno() { # yesno "Question" default_y_or_n
  local __ans=""
  read -r -p "$1 [$2]: " __ans || true
  __ans=${__ans:-$2}
  [[ "$__ans" =~ ^[Yy] ]]
}

[ "$(id -u)" -eq 0 ] && die "Run as the 'ubuntu' user, not root. It will use sudo where needed."
command -v apt-get >/dev/null 2>&1 || die "This installer expects Ubuntu."

# ── 1. packages ─────────────────────────────────────────────────────────────────
step "Installing system packages"
sudo apt-get update -qq
sudo apt-get install -y -qq ca-certificates curl git openssl jq >/dev/null
ok "base packages"

# ── 2. docker ───────────────────────────────────────────────────────────────────
step "Installing Docker"
if command -v docker >/dev/null 2>&1 && docker compose version >/dev/null 2>&1; then
  ok "docker already present"
else
  sudo install -m 0755 -d /etc/apt/keyrings
  curl -fsSL https://download.docker.com/linux/ubuntu/gpg \
    | sudo gpg --dearmor -o /etc/apt/keyrings/docker.gpg --yes
  sudo chmod a+r /etc/apt/keyrings/docker.gpg
  echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] \
https://download.docker.com/linux/ubuntu $(. /etc/os-release && echo "$VERSION_CODENAME") stable" \
    | sudo tee /etc/apt/sources.list.d/docker.list >/dev/null
  sudo apt-get update -qq
  sudo apt-get install -y -qq docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin >/dev/null
  ok "docker installed"
fi
sudo usermod -aG docker "$USER" || true

# Compose and the asset build both want more RAM than a 2 GB box has spare.
step "Ensuring swap exists"
if [ "$(swapon --show | wc -l)" -eq 0 ]; then
  sudo fallocate -l 2G /swapfile
  sudo chmod 600 /swapfile
  sudo mkswap /swapfile >/dev/null
  sudo swapon /swapfile
  grep -q '/swapfile' /etc/fstab || echo '/swapfile none swap sw 0 0' | sudo tee -a /etc/fstab >/dev/null
  ok "2 GB swap added — the Vite build needs it on a small instance"
else
  ok "swap already configured"
fi

# ── 3. source ───────────────────────────────────────────────────────────────────
step "Locating the application"

# If this script is sitting inside the repo it is meant to install, use that checkout
# rather than cloning a second copy somewhere else. Running from a clone is the
# obvious thing to do, and quietly ending up with two copies — one deployed, one being
# edited — is a very unpleasant way to spend an afternoon.
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

if [ -d "$SCRIPT_DIR/.git" ] && [ -f "$SCRIPT_DIR/backend/artisan" ]; then
  APP_DIR="$SCRIPT_DIR"
  ok "running inside the repo at $APP_DIR — not cloning"
  cd "$APP_DIR"
  CURRENT_BRANCH="$(git rev-parse --abbrev-ref HEAD 2>/dev/null || echo '?')"
  echo "${c_dim}  On branch $CURRENT_BRANCH. Pull the latest yourself if you need to.${c_reset}"
elif [ -d "$APP_DIR/.git" ]; then
  git -C "$APP_DIR" fetch --quiet origin "$BRANCH"
  git -C "$APP_DIR" checkout --quiet "$BRANCH"
  git -C "$APP_DIR" pull --quiet --ff-only origin "$BRANCH" \
    || warn "could not fast-forward; leaving the checkout as is"
  ok "updated $APP_DIR"
  cd "$APP_DIR"
else
  git clone --quiet --branch "$BRANCH" "$REPO_URL" "$APP_DIR" \
    || die "clone failed — is '$BRANCH' a branch that exists on $REPO_URL?"
  ok "cloned into $APP_DIR"
  cd "$APP_DIR"
fi

[ -f backend/artisan ] || die "backend/artisan missing in $APP_DIR"
[ -f docker-compose.prod.yml ] || die "docker-compose.prod.yml missing — this checkout predates the production overlay"

# ── 4. answers ──────────────────────────────────────────────────────────────────
ENV_FILE="backend/.env"
if [ -f "$ENV_FILE" ]; then
  warn "$ENV_FILE already exists."
  if yesno "  Keep it and skip all configuration questions?" "Y"; then
    SKIP_CONFIG=1
  else
    cp "$ENV_FILE" "$ENV_FILE.bak.$(date +%s)"
    ok "existing .env backed up"
    SKIP_CONFIG=0
  fi
else
  SKIP_CONFIG=0
fi

if [ "${SKIP_CONFIG:-0}" -eq 0 ]; then
  step "Configuration"
  echo "${c_dim}  Blank answers are allowed where something can be set later in the admin panel.${c_reset}"

  ask DOMAIN "Domain (no scheme, e.g. remotox.com)"
  ask ADMIN_EMAIL "Admin email address"

  echo
  echo "  Cloudflare Turnstile — dashboard > Turnstile > Add widget."
  echo "${c_dim}  Add both $DOMAIN and 127.0.0.1 to the widget's allowed hostnames.${c_reset}"
  ask TURNSTILE_SITE "  Turnstile site key (blank to skip)" "-"
  if [ "$TURNSTILE_SITE" != "-" ]; then
    ask_secret TURNSTILE_SECRET "  Turnstile secret key"
  else
    TURNSTILE_SITE=""; TURNSTILE_SECRET=""
    warn "Turnstile skipped — the apply form will have no bot protection until you add keys"
  fi

  echo
  echo "  SMTP. Leave the host blank to configure mail in the admin panel instead."
  echo "${c_dim}  Amazon SES starts sandboxed and only delivers to verified addresses until${c_reset}"
  echo "${c_dim}  you request production access.${c_reset}"
  ask MAIL_HOST "  SMTP host (blank to skip)" "-"
  if [ "$MAIL_HOST" != "-" ]; then
    ask MAIL_PORT "  SMTP port" "587"
    ask MAIL_USERNAME "  SMTP username"
    ask_secret MAIL_PASSWORD "  SMTP password"
    ask MAIL_FROM "  From address" "no-reply@$DOMAIN"
    # 587 means STARTTLS (scheme smtp); 465 is implicit TLS (scheme smtps).
    if [ "$MAIL_PORT" = "465" ]; then MAIL_SCHEME="smtps"; else MAIL_SCHEME="smtp"; fi
  else
    MAIL_HOST=""; MAIL_PORT="587"; MAIL_USERNAME=""; MAIL_PASSWORD=""
    MAIL_FROM="no-reply@$DOMAIN"; MAIL_SCHEME="smtp"
    warn "SMTP skipped — set it in Settings > Mail before approving any member"
  fi

  # Generated, not asked. A password nobody typed is a password nobody reuses.
  DB_PASSWORD="$(openssl rand -base64 24 | tr -d '/+=' | cut -c1-28)"
  DB_ROOT_PASSWORD="$(openssl rand -base64 24 | tr -d '/+=' | cut -c1-28)"

  step "Writing $ENV_FILE"
  cp backend/.env.example "$ENV_FILE" 2>/dev/null || : > "$ENV_FILE"

  set_env() { # set_env KEY VALUE
    local k=$1 v=$2
    # Quote the value so passwords containing # or spaces survive.
    if grep -qE "^#?\s*${k}=" "$ENV_FILE"; then
      sudo -n true 2>/dev/null || true
      python3 - "$ENV_FILE" "$k" "$v" <<'PY'
import re, sys
path, key, val = sys.argv[1], sys.argv[2], sys.argv[3]
with open(path) as f: s = f.read()
line = f'{key}="{val}"'
s, n = re.subn(rf'(?m)^#?\s*{re.escape(key)}=.*$', line.replace('\\', '\\\\'), s, count=1)
if n == 0: s = s.rstrip('\n') + '\n' + line + '\n'
with open(path, 'w') as f: f.write(s)
PY
    else
      printf '%s="%s"\n' "$k" "$v" >> "$ENV_FILE"
    fi
  }

  set_env APP_NAME "Remotox"
  set_env APP_ENV "production"
  set_env APP_DEBUG "false"
  set_env APP_URL "https://$DOMAIN"
  set_env LOG_LEVEL "warning"

  set_env DB_CONNECTION "mysql"
  set_env DB_HOST "db"
  set_env DB_PORT "3306"
  set_env DB_DATABASE "laravel"
  set_env DB_USERNAME "laravel"
  set_env DB_PASSWORD "$DB_PASSWORD"
  set_env DB_ROOT_PASSWORD "$DB_ROOT_PASSWORD"

  set_env SESSION_DRIVER "database"
  set_env CACHE_STORE "database"
  set_env QUEUE_CONNECTION "sync"

  set_env MAIL_MAILER "smtp"
  set_env MAIL_HOST "$MAIL_HOST"
  set_env MAIL_PORT "$MAIL_PORT"
  set_env MAIL_USERNAME "$MAIL_USERNAME"
  set_env MAIL_PASSWORD "$MAIL_PASSWORD"
  set_env MAIL_SCHEME "$MAIL_SCHEME"
  set_env MAIL_FROM_ADDRESS "$MAIL_FROM"
  set_env MAIL_FROM_NAME "Remotox"

  set_env TURNSTILE_SITE_KEY "$TURNSTILE_SITE"
  set_env TURNSTILE_SECRET_KEY "$TURNSTILE_SECRET"
  set_env JOBSTATION_TURNSTILE_STRICT "true"

  # Invite-only: the mobile API bypassed every rule it was meant to enforce.
  set_env JOBSTATION_ENABLE_API "false"
  set_env JOBSTATION_ENABLE_USER_GIGS "false"
  set_env JOBSTATION_ENABLE_JOB_BOARD "false"

  chmod 600 "$ENV_FILE"
  ok ".env written (mode 600)"
  echo "${c_dim}  Database passwords were generated and stored only in $ENV_FILE.${c_reset}"
fi

# ── 5. TLS certificate from Cloudflare ──────────────────────────────────────────
step "Origin TLS certificate"
CERT_DIR="docker/nginx/certs"
mkdir -p "$CERT_DIR"

if [ -s "$CERT_DIR/origin.pem" ] && [ -s "$CERT_DIR/origin.key" ]; then
  ok "certificate already present — leaving it alone"
else
  DOMAIN="${DOMAIN:-$(grep -E '^APP_URL=' "$ENV_FILE" | sed -E 's|.*//([^/"]+).*|\1|')}"
  echo "  A certificate is needed for the Cloudflare-to-origin hop."
  echo "  Option 1 requests a Cloudflare Origin CA certificate automatically (needs an"
  echo "  API token with Zone > SSL and Certificates > Edit)."
  echo "  Option 2 generates a self-signed certificate, which works only with"
  echo "  Cloudflare SSL mode 'Full' — NOT 'Full (strict)'."
  echo

  if yesno "  Request a Cloudflare Origin CA certificate now?" "Y"; then
    ask_secret CF_TOKEN "  Cloudflare API token"

    openssl req -new -newkey rsa:2048 -nodes \
      -keyout "$CERT_DIR/origin.key" \
      -out /tmp/origin.csr \
      -subj "/CN=$DOMAIN" >/dev/null 2>&1
    chmod 600 "$CERT_DIR/origin.key"

    CSR_JSON=$(jq -Rs . < /tmp/origin.csr)
    RESP=$(curl -sS -X POST "https://api.cloudflare.com/client/v4/certificates" \
      -H "Authorization: Bearer $CF_TOKEN" \
      -H "Content-Type: application/json" \
      --data "{\"hostnames\":[\"$DOMAIN\",\"*.$DOMAIN\"],\"requested_validity\":5475,\"request_type\":\"origin-rsa\",\"csr\":$CSR_JSON}")
    rm -f /tmp/origin.csr

    if [ "$(printf '%s' "$RESP" | jq -r '.success')" = "true" ]; then
      printf '%s' "$RESP" | jq -r '.result.certificate' > "$CERT_DIR/origin.pem"
      ok "Cloudflare Origin CA certificate installed (15 year validity)"
      echo "${c_dim}  Now set SSL/TLS mode to 'Full (strict)' in the Cloudflare dashboard.${c_reset}"
    else
      warn "Cloudflare refused the request:"
      printf '%s' "$RESP" | jq -r '.errors[]? | "     - \(.message)"' || printf '     %s\n' "$RESP"
      warn "Falling back to a self-signed certificate."
      rm -f "$CERT_DIR/origin.pem"
    fi
  fi

  if [ ! -s "$CERT_DIR/origin.pem" ]; then
    openssl req -x509 -nodes -days 3650 -newkey rsa:2048 \
      -keyout "$CERT_DIR/origin.key" -out "$CERT_DIR/origin.pem" \
      -subj "/CN=$DOMAIN" >/dev/null 2>&1
    chmod 600 "$CERT_DIR/origin.key"
    warn "Self-signed certificate generated. Cloudflare SSL mode must be 'Full', not 'Full (strict)'."
  fi
fi

# ── 6. build and boot ───────────────────────────────────────────────────────────
step "Building containers"
DC="docker compose -f docker-compose.yml -f docker-compose.prod.yml"
sg docker -c "$DC build" || die "build failed"
sg docker -c "$DC up -d" || die "could not start containers"
ok "containers running"

step "Waiting for MySQL"
for i in $(seq 1 60); do
  if sg docker -c "$DC exec -T db mysqladmin ping -h127.0.0.1 --silent" >/dev/null 2>&1; then
    ok "database is up"; break
  fi
  [ "$i" -eq 60 ] && die "MySQL did not become ready. Check: $DC logs db"
  sleep 2
done

step "Installing PHP dependencies"
sg docker -c "$DC exec -T app composer install --no-dev --optimize-autoloader --no-interaction" \
  || die "composer install failed"
ok "composer"

step "Building frontend assets"
# Mandatory, not optional: @vite() throws without a manifest, which turns every page
# into a 500 rather than an unstyled page.
sg docker -c "$DC exec -T app npm ci --no-audit --no-fund" || die "npm ci failed"
sg docker -c "$DC exec -T app npm run build" || die "asset build failed — check swap and free memory"
ok "assets built"

step "Application setup"
if ! grep -qE '^APP_KEY="?base64:' "$ENV_FILE"; then
  sg docker -c "$DC exec -T app php artisan key:generate --force"
  ok "app key generated"
fi

sg docker -c "$DC exec -T app php artisan migrate --force" || die "migrations failed"
ok "migrations"

# Idempotent seeders: payment channels, payout methods, notification templates and
# the marketplace records. MarketplaceSeeder is the one that creates
# MEMBERSHIP_APPROVED — without it, approving a member emails nothing at all.
for s in AdminSeeder LanguageSeeder AppSettingSeeder NotificationTemplateSeeder \
         PaymentChannelSeeder PayoutMethodSeeder PluginSeeder ContentSectionSeeder \
         WorkCategorySeeder SkillSeeder CoinPackageSeeder PolicyPageSeeder MarketplaceSeeder; do
  sg docker -c "$DC exec -T app php artisan db:seed --class=$s --force" >/dev/null 2>&1 \
    && ok "seeded $s" || warn "seeder $s reported a problem (may already be applied)"
done

sg docker -c "$DC exec -T app php artisan storage:link" >/dev/null 2>&1 || true
sg docker -c "$DC exec -T app chown -R www-data:www-data storage bootstrap/cache" || true
sg docker -c "$DC exec -T app php artisan optimize:clear" >/dev/null
ok "caches cleared"

# ── 7. what is left for a human ─────────────────────────────────────────────────
cat <<EOF

$c_ok────────────────────────────────────────────────────────────$c_reset
 Installed. https://${DOMAIN:-your-domain}
$c_ok────────────────────────────────────────────────────────────$c_reset

Still to do, in this order:

 1. AWS console > Lightsail > Networking: open ports 80 and 443.
    The OS firewall is not what blocks them.

 2. Cloudflare > DNS: A record for ${DOMAIN:-your-domain} to this server's IP,
    proxy ENABLED (orange cloud).

 3. Cloudflare > SSL/TLS: set mode to "Full (strict)".
    Use plain "Full" if the installer fell back to a self-signed certificate.

 4. Log in at https://${DOMAIN:-your-domain}/admin
    Default credentials come from AdminSeeder — change the password immediately.

 5. Settings > Mail: press "Send test email" and confirm it arrives.
    $c_warn Do this BEFORE approving anyone.$c_reset A member's temporary password is
    hashed on creation, so it exists only in that email. If the email fails, the
    account cannot be logged into until you reset the password by hand.

 6. Settings > General: turn registration OFF. It defaults to on, and until it is
    off the site is not invite-only. Check /dashboard/register to confirm.

 7. Works > Categories: set a commission percent, an application fee and a daily
    application limit on each. They default to zero, which means free applications
    and no platform cut.

 8. Lightsail console: enable automatic snapshots. This box holds the database.

Useful commands:

  cd $APP_DIR
  $DC ps
  $DC logs app --tail 50
  $DC exec app php artisan optimize:clear

EOF
