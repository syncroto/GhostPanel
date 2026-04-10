#!/usr/bin/env bash
# =============================================================================
#  GPanel — Instalador Automático
#  Uso: curl -fsSL https://raw.githubusercontent.com/syncroto/GhostPanel/refs/heads/main/install.sh | sudo bash
#       curl -fsSL https://raw.githubusercontent.com/syncroto/GhostPanel/refs/heads/main/install.sh | sudo bash -s -- --port 8442
# =============================================================================
set -euo pipefail

# --------------------------------------------------------------------------- #
#  Configurações padrão
# --------------------------------------------------------------------------- #
GPANEL_DIR="/gpanel"
GPANEL_REPO="https://github.com/syncroto/GhostPanel.git"  # ajuste o repo real
GPANEL_PORT=8442
GPANEL_USER="gpanel"
PHP_VERSION="8.2"
NODE_VERSION="20"

# Cores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
BOLD='\033[1m'
NC='\033[0m'

# --------------------------------------------------------------------------- #
#  Utilitários de saída
# --------------------------------------------------------------------------- #
log()     { echo -e "${GREEN}[✓]${NC} $*"; }
info()    { echo -e "${BLUE}[i]${NC} $*"; }
warn()    { echo -e "${YELLOW}[!]${NC} $*"; }
error()   { echo -e "${RED}[✗]${NC} $*" >&2; }
header()  { echo -e "\n${BOLD}${BLUE}══════════════════════════════════════${NC}"; echo -e "${BOLD} $*${NC}"; echo -e "${BOLD}${BLUE}══════════════════════════════════════${NC}\n"; }
ask()     { echo -en "${YELLOW}[?]${NC} $* "; }

# --------------------------------------------------------------------------- #
#  Parse de argumentos
# --------------------------------------------------------------------------- #
while [[ $# -gt 0 ]]; do
  case $1 in
    --port)   GPANEL_PORT="$2"; shift 2 ;;
    --dir)    GPANEL_DIR="$2";  shift 2 ;;
    --repo)   GPANEL_REPO="$2"; shift 2 ;;
    --no-interactive) NO_INTERACTIVE=1; shift ;;
    *) warn "Argumento desconhecido: $1"; shift ;;
  esac
done

NO_INTERACTIVE=${NO_INTERACTIVE:-1}

# Permite Composer rodar como root sem warnings/bloqueios
export COMPOSER_ALLOW_SUPERUSER=1

# --------------------------------------------------------------------------- #
#  1. Verificações iniciais
# --------------------------------------------------------------------------- #
check_root() {
  if [[ $EUID -ne 0 ]]; then
    error "Este script deve ser executado como root."
    error "Use: curl -fsSL https://raw.githubusercontent.com/syncroto/GhostPanel/refs/heads/main/install.sh | sudo bash"
    exit 1
  fi
}

check_os() {
  if [[ ! -f /etc/os-release ]]; then
    error "Não foi possível detectar o sistema operacional."
    exit 1
  fi

  source /etc/os-release

  case "$ID" in
    ubuntu)
      if [[ "$VERSION_ID" != "22.04" && "$VERSION_ID" != "24.04" ]]; then
        warn "Ubuntu $VERSION_ID detectado. Recomendado: Ubuntu 22.04 ou 24.04."
        warn "Continuando mesmo assim, mas podem ocorrer problemas."
      else
        log "Sistema operacional: Ubuntu $VERSION_ID ✓"
      fi
      PKG_MANAGER="apt-get"
      ;;
    debian)
      if [[ "$VERSION_ID" != "11" && "$VERSION_ID" != "12" ]]; then
        warn "Debian $VERSION_ID detectado. Recomendado: Debian 11 ou 12."
      else
        log "Sistema operacional: Debian $VERSION_ID ✓"
      fi
      PKG_MANAGER="apt-get"
      ;;
    *)
      error "Sistema operacional não suportado: $ID"
      error "GPanel suporta: Ubuntu 22.04/24.04 e Debian 11/12"
      exit 1
      ;;
  esac
}

check_resources() {
  local ram_kb
  ram_kb=$(grep MemTotal /proc/meminfo | awk '{print $2}')
  local ram_mb=$((ram_kb / 1024))

  if [[ $ram_mb -lt 768 ]]; then
    error "RAM insuficiente: ${ram_mb}MB. Mínimo recomendado: 1GB."
    exit 1
  fi
  log "RAM disponível: ${ram_mb}MB ✓"

  local disk_gb
  disk_gb=$(df -BG / | awk 'NR==2 {print $4}' | tr -d 'G')

  if [[ $disk_gb -lt 5 ]]; then
    error "Espaço em disco insuficiente: ${disk_gb}GB. Mínimo: 5GB."
    exit 1
  fi
  log "Disco disponível: ${disk_gb}GB ✓"
}

check_port() {
  if ss -tlnp | grep -q ":${GPANEL_PORT} "; then
    error "Porta $GPANEL_PORT já está em uso."
    error "Use --port OUTRA_PORTA para especificar uma porta diferente."
    exit 1
  fi
  log "Porta $GPANEL_PORT disponível ✓"
}

# --------------------------------------------------------------------------- #
#  2. Seleção interativa de stacks
# --------------------------------------------------------------------------- #

# Detecta automaticamente se há terminal disponível para input.
# curl | bash consome o stdin do pipe — sem /dev/tty não há como ler input.
is_interactive() {
  [[ $NO_INTERACTIVE -eq 0 ]] && [ -t 0 ] || { [ -e /dev/tty ] && exec 0</dev/tty 2>/dev/null; }
}

prompt_yes_no() {
  local question="$1"
  local default="${2:-Y}"
  local answer

  if [[ $NO_INTERACTIVE -eq 1 ]]; then
    echo "$default"
    return
  fi

  ask "$question [Y/n]:"
  # Lê do /dev/tty para funcionar mesmo com curl | bash
  read -r answer </dev/tty
  answer="${answer:-$default}"
  echo "${answer^^}"
}

select_stacks() {
  if [[ $NO_INTERACTIVE -eq 1 ]]; then
    INSTALL_NGINX=Y; INSTALL_PHP=Y; INSTALL_MYSQL=Y
    INSTALL_POSTGRESQL=N; INSTALL_REDIS=Y
    INSTALL_NODEJS=Y; INSTALL_PYTHON=N
    INSTALL_FTP=N; INSTALL_AUTO_BACKUP=N
    return
  fi

  # Se /dev/tty não estiver disponível (ex: CI, pipe sem terminal),
  # instala tudo automaticamente com defaults seguros
  if ! </dev/tty >/dev/null 2>&1; then
    warn "Nenhum terminal interativo detectado — instalando stack padrão automaticamente."
    INSTALL_NGINX=Y; INSTALL_PHP=Y; INSTALL_MYSQL=Y
    INSTALL_POSTGRESQL=N; INSTALL_REDIS=Y
    INSTALL_NODEJS=Y; INSTALL_PYTHON=N
    INSTALL_FTP=N; INSTALL_AUTO_BACKUP=N
    info "Stack padrão: Nginx + PHP 8.2 + MySQL + Redis + Node.js"
    info "Para personalizar: curl ... | sudo bash -s -- --no-interactive (edite as vars no topo)"
    return
  fi

  header "Seleção de Componentes"
  echo "  Escolha quais componentes instalar:"
  echo ""

  INSTALL_NGINX=$(prompt_yes_no    "  Instalar Nginx?")
  INSTALL_PHP=$(prompt_yes_no      "  Instalar PHP ($PHP_VERSION)?")
  INSTALL_MYSQL=$(prompt_yes_no    "  Instalar MySQL 8?")
  INSTALL_POSTGRESQL=$(prompt_yes_no "  Instalar PostgreSQL?" "N")
  INSTALL_REDIS=$(prompt_yes_no    "  Instalar Redis?")
  INSTALL_NODEJS=$(prompt_yes_no   "  Instalar Node.js $NODE_VERSION (via NVM)?")
  INSTALL_PYTHON=$(prompt_yes_no   "  Instalar Python 3 + venv?" "N")
  INSTALL_FTP=$(prompt_yes_no      "  Instalar FTP (vsftpd)?" "N")
  INSTALL_AUTO_BACKUP=$(prompt_yes_no "  Ativar backup automático?" "N")

  echo ""
  info "Resumo do que será instalado:"
  [[ "$INSTALL_NGINX" == "Y" ]]      && echo "    • Nginx"
  [[ "$INSTALL_PHP" == "Y" ]]        && echo "    • PHP $PHP_VERSION + extensões"
  [[ "$INSTALL_MYSQL" == "Y" ]]      && echo "    • MySQL 8"
  [[ "$INSTALL_POSTGRESQL" == "Y" ]] && echo "    • PostgreSQL"
  [[ "$INSTALL_REDIS" == "Y" ]]      && echo "    • Redis"
  [[ "$INSTALL_NODEJS" == "Y" ]]     && echo "    • Node.js $NODE_VERSION (NVM)"
  [[ "$INSTALL_PYTHON" == "Y" ]]     && echo "    • Python 3 + venv"
  [[ "$INSTALL_FTP" == "Y" ]]        && echo "    • vsftpd (FTP)"
  [[ "$INSTALL_AUTO_BACKUP" == "Y" ]]&& echo "    • Backup automático"
  echo ""

  ask "Confirma a instalação? [Y/n]:"
  read -r confirm </dev/tty
  confirm="${confirm:-Y}"
  if [[ "${confirm^^}" != "Y" ]]; then
    info "Instalação cancelada pelo usuário."
    exit 0
  fi
}

# --------------------------------------------------------------------------- #
#  3. Instalação de dependências base
# --------------------------------------------------------------------------- #
install_base_deps() {
  header "Dependências Base"

  $PKG_MANAGER update -qq
  $PKG_MANAGER install -y -qq \
    curl wget git unzip zip tar \
    software-properties-common \
    gnupg2 ca-certificates lsb-release \
    apt-transport-https \
    supervisor \
    ufw \
    jq \
    openssl \
    cron \
    build-essential \
    python3-dev \
    make \
    g++

  log "Dependências base instaladas ✓"
}

# --------------------------------------------------------------------------- #
#  4. Nginx
# --------------------------------------------------------------------------- #
install_nginx() {
  [[ "$INSTALL_NGINX" != "Y" ]] && return

  header "Nginx"

  if command -v nginx &>/dev/null; then
    log "Nginx já instalado: $(nginx -v 2>&1 | head -1)"
    return
  fi

  $PKG_MANAGER install -y -qq nginx
  systemctl enable nginx
  systemctl start nginx

  log "Nginx instalado ✓"
}

# --------------------------------------------------------------------------- #
#  5. PHP
# --------------------------------------------------------------------------- #
install_php() {
  [[ "$INSTALL_PHP" != "Y" ]] && return

  header "PHP $PHP_VERSION"

  if command -v php &>/dev/null && php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;" 2>/dev/null | grep -q "^${PHP_VERSION}"; then
    log "PHP $PHP_VERSION já instalado ✓"
  else
    # Adiciona repositório ondrej/php
    add-apt-repository -y ppa:ondrej/php 2>/dev/null || true
    $PKG_MANAGER update -qq

    $PKG_MANAGER install -y -qq \
      php${PHP_VERSION} \
      php${PHP_VERSION}-fpm \
      php${PHP_VERSION}-cli \
      php${PHP_VERSION}-common \
      php${PHP_VERSION}-mysql \
      php${PHP_VERSION}-pgsql \
      php${PHP_VERSION}-sqlite3 \
      php${PHP_VERSION}-xml \
      php${PHP_VERSION}-curl \
      php${PHP_VERSION}-mbstring \
      php${PHP_VERSION}-zip \
      php${PHP_VERSION}-gd \
      php${PHP_VERSION}-bcmath \
      php${PHP_VERSION}-intl \
      php${PHP_VERSION}-readline \
      php${PHP_VERSION}-redis

    systemctl enable php${PHP_VERSION}-fpm
    systemctl start php${PHP_VERSION}-fpm

    # Configura limites de upload (padrão do PHP é 2M — insuficiente para file manager)
    local PHP_INI="/etc/php/${PHP_VERSION}/fpm/php.ini"
    if [[ -f "$PHP_INI" ]]; then
      sed -i 's/^upload_max_filesize\s*=.*/upload_max_filesize = 100M/' "$PHP_INI"
      sed -i 's/^post_max_size\s*=.*/post_max_size = 100M/'           "$PHP_INI"
      sed -i 's/^max_execution_time\s*=.*/max_execution_time = 300/'   "$PHP_INI"
      systemctl restart php${PHP_VERSION}-fpm
      log "PHP upload limits configurados (100M) ✓"
    fi

    log "PHP $PHP_VERSION instalado ✓"
  fi

  # Composer
  if ! command -v composer &>/dev/null; then
    info "Instalando Composer..."
    EXPECTED_CHECKSUM="$(php -r 'copy("https://composer.github.io/installer.sig", "php://stdout");')"
    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
    ACTUAL_CHECKSUM="$(php -r "echo hash_file('sha384', 'composer-setup.php');")"

    if [[ "$EXPECTED_CHECKSUM" != "$ACTUAL_CHECKSUM" ]]; then
      error "Checksum do Composer inválido!"
      rm -f composer-setup.php
      exit 1
    fi

    php composer-setup.php --quiet --install-dir=/usr/local/bin --filename=composer
    rm -f composer-setup.php

    # Configura Composer para rodar como root sem avisos
    export COMPOSER_ALLOW_SUPERUSER=1

    log "Composer instalado ✓"
  else
    log "Composer já disponível ✓"
  fi
}

# --------------------------------------------------------------------------- #
#  6. MySQL 8
# --------------------------------------------------------------------------- #
install_mysql() {
  [[ "$INSTALL_MYSQL" != "Y" ]] && return

  header "MySQL 8"

  if command -v mysql &>/dev/null; then
    log "MySQL já instalado ✓"
    return
  fi

  $PKG_MANAGER install -y -qq mysql-server

  systemctl enable mysql
  systemctl start mysql

  # Aguarda o MySQL ficar pronto (pode demorar alguns segundos no boot)
  local tries=0
  until mysqladmin ping --silent 2>/dev/null || [[ $tries -ge 15 ]]; do
    sleep 1
    tries=$((tries + 1))
  done

  if ! mysqladmin ping --silent 2>/dev/null; then
    error "MySQL não respondeu após 15 segundos. Verifique: systemctl status mysql"
    exit 1
  fi

  # Gera senha root aleatória (24 chars, sem chars especiais que quebram shell)
  MYSQL_ROOT_PASSWORD=$(openssl rand -base64 24 | tr -d '/+=' | head -c 24)

  # Detecta o plugin de autenticação atual do root
  # Ubuntu 22.04 usa auth_socket, Ubuntu 24.04 pode usar caching_sha2_password
  local current_plugin
  current_plugin=$(mysql -sN -e "SELECT plugin FROM mysql.user WHERE User='root' AND Host='localhost';" 2>/dev/null || echo "unknown")

  info "Plugin de autenticação atual do root: ${current_plugin}"

  if [[ "$current_plugin" == "auth_socket" || "$current_plugin" == "unix_socket" ]]; then
    # Ubuntu 22.04 padrão — root autentica via socket, não precisa de senha
    # Muda para mysql_native_password com a nova senha
    mysql -e "ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY '${MYSQL_ROOT_PASSWORD}';" 2>/dev/null \
      || mysql -e "UPDATE mysql.user SET plugin='mysql_native_password', authentication_string=PASSWORD('${MYSQL_ROOT_PASSWORD}') WHERE User='root' AND Host='localhost'; FLUSH PRIVILEGES;" 2>/dev/null
  elif [[ "$current_plugin" == "caching_sha2_password" ]]; then
    # Ubuntu 24.04 padrão
    mysql -e "ALTER USER 'root'@'localhost' IDENTIFIED WITH caching_sha2_password BY '${MYSQL_ROOT_PASSWORD}';" 2>/dev/null \
      || mysql -e "ALTER USER 'root'@'localhost' IDENTIFIED BY '${MYSQL_ROOT_PASSWORD}';" 2>/dev/null
  else
    # Fallback genérico — tenta os dois métodos
    mysql -e "ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY '${MYSQL_ROOT_PASSWORD}';" 2>/dev/null \
      || mysql -e "ALTER USER 'root'@'localhost' IDENTIFIED BY '${MYSQL_ROOT_PASSWORD}';" 2>/dev/null
  fi

  # Verifica se a senha foi aplicada corretamente
  if ! mysql -u root -p"${MYSQL_ROOT_PASSWORD}" -e "SELECT 1;" &>/dev/null; then
    error "Não foi possível autenticar no MySQL com a nova senha."
    error "Tente: sudo mysql → ALTER USER 'root'@'localhost' IDENTIFIED BY 'suasenha';"
    exit 1
  fi

  # Hardening básico (equivalente ao mysql_secure_installation)
  mysql -u root -p"${MYSQL_ROOT_PASSWORD}" -e "DELETE FROM mysql.user WHERE User='';" 2>/dev/null
  mysql -u root -p"${MYSQL_ROOT_PASSWORD}" -e "DELETE FROM mysql.user WHERE User='root' AND Host NOT IN ('localhost','127.0.0.1','::1');" 2>/dev/null
  mysql -u root -p"${MYSQL_ROOT_PASSWORD}" -e "DROP DATABASE IF EXISTS test;" 2>/dev/null
  mysql -u root -p"${MYSQL_ROOT_PASSWORD}" -e "DELETE FROM mysql.db WHERE Db='test' OR Db='test\\_%';" 2>/dev/null
  mysql -u root -p"${MYSQL_ROOT_PASSWORD}" -e "FLUSH PRIVILEGES;" 2>/dev/null

  # Salva credenciais com permissão restrita
  cat > /root/.gpanel_mysql_root <<EOF
MYSQL_ROOT_PASSWORD=${MYSQL_ROOT_PASSWORD}
MYSQL_ROOT_PLUGIN=${current_plugin}
INSTALLED_AT=$(date '+%Y-%m-%d %H:%M:%S')
EOF
  chmod 600 /root/.gpanel_mysql_root

  log "MySQL 8 instalado e blindado — credenciais salvas em /root/.gpanel_mysql_root ✓"
}

# --------------------------------------------------------------------------- #
#  7. PostgreSQL
# --------------------------------------------------------------------------- #
install_postgresql() {
  [[ "$INSTALL_POSTGRESQL" != "Y" ]] && return

  header "PostgreSQL"

  if command -v psql &>/dev/null; then
    log "PostgreSQL já instalado ✓"
    return
  fi

  $PKG_MANAGER install -y -qq postgresql postgresql-contrib
  systemctl enable postgresql
  systemctl start postgresql

  log "PostgreSQL instalado ✓"
}

# --------------------------------------------------------------------------- #
#  8. Redis
# --------------------------------------------------------------------------- #
install_redis() {
  [[ "$INSTALL_REDIS" != "Y" ]] && return

  header "Redis"

  if command -v redis-server &>/dev/null; then
    log "Redis já instalado ✓"
    return
  fi

  $PKG_MANAGER install -y -qq redis-server
  systemctl enable redis-server
  systemctl start redis-server

  log "Redis instalado ✓"
}

# --------------------------------------------------------------------------- #
#  9. Node.js via NVM
# --------------------------------------------------------------------------- #
install_nodejs() {
  [[ "$INSTALL_NODEJS" != "Y" ]] && return

  header "Node.js $NODE_VERSION"

  if command -v node &>/dev/null; then
    log "Node.js já instalado: $(node --version) ✓"
    return
  fi

  # Instala NVM globalmente (em /opt/nvm para ser acessível ao sistema)
  export NVM_DIR="/opt/nvm"
  mkdir -p "$NVM_DIR"

  curl -fsSL https://raw.githubusercontent.com/nvm-sh/nvm/v0.39.7/install.sh | NVM_DIR="$NVM_DIR" bash

  # Carrega NVM
  source "$NVM_DIR/nvm.sh"

  nvm install "$NODE_VERSION"
  nvm alias default "$NODE_VERSION"
  nvm use default

  # Symlinks globais
  NVM_NODE_BIN="$NVM_DIR/versions/node/$(nvm version default)/bin"
  ln -sf "${NVM_NODE_BIN}/node" /usr/local/bin/node
  ln -sf "${NVM_NODE_BIN}/npm"  /usr/local/bin/npm
  ln -sf "${NVM_NODE_BIN}/npx"  /usr/local/bin/npx

  # Adiciona NVM ao bashrc global
  cat > /etc/profile.d/nvm.sh <<'NVMEOF'
export NVM_DIR="/opt/nvm"
[ -s "$NVM_DIR/nvm.sh" ] && \. "$NVM_DIR/nvm.sh"
NVMEOF

  log "Node.js $(node --version) instalado via NVM ✓"
}

# --------------------------------------------------------------------------- #
#  10. Python 3
# --------------------------------------------------------------------------- #
install_python() {
  [[ "$INSTALL_PYTHON" != "Y" ]] && return

  header "Python 3"

  $PKG_MANAGER install -y -qq python3 python3-pip python3-venv
  log "Python $(python3 --version) instalado ✓"
}

# --------------------------------------------------------------------------- #
#  11. vsftpd
# --------------------------------------------------------------------------- #
install_ftp() {
  [[ "$INSTALL_FTP" != "Y" ]] && return

  header "FTP (vsftpd)"

  $PKG_MANAGER install -y -qq vsftpd

  # Configuração segura com chroot
  cat > /etc/vsftpd.conf <<'FTPEOF'
listen=YES
listen_ipv6=NO
anonymous_enable=NO
local_enable=YES
write_enable=YES
local_umask=022
chroot_local_user=YES
allow_writeable_chroot=NO
secure_chroot_dir=/var/run/vsftpd/empty
pam_service_name=vsftpd
pasv_enable=YES
pasv_min_port=40000
pasv_max_port=50000
user_sub_token=$USER
local_root=/var/www/sites/$USER
userlist_enable=YES
userlist_file=/etc/vsftpd.userlist
userlist_deny=NO
FTPEOF

  touch /etc/vsftpd.userlist
  systemctl enable vsftpd
  systemctl restart vsftpd

  log "vsftpd instalado com chroot ✓"
}

# --------------------------------------------------------------------------- #
#  12. phpMyAdmin
# --------------------------------------------------------------------------- #
install_phpmyadmin() {
  [[ "$INSTALL_MYSQL" != "Y" ]] && return

  header "phpMyAdmin"

  local PMA_VERSION="5.2.1"
  local PMA_DIR="/var/www/phpmyadmin"

  if [[ -d "$PMA_DIR" ]]; then
    log "phpMyAdmin já instalado ✓"
    return
  fi

  info "Baixando phpMyAdmin $PMA_VERSION..."
  wget -q "https://files.phpmyadmin.net/phpMyAdmin/${PMA_VERSION}/phpMyAdmin-${PMA_VERSION}-all-languages.tar.gz" \
    -O /tmp/phpmyadmin.tar.gz

  mkdir -p "$PMA_DIR"
  tar -xzf /tmp/phpmyadmin.tar.gz -C "$PMA_DIR" --strip-components=1
  rm -f /tmp/phpmyadmin.tar.gz

  # Configura phpMyAdmin
  cp "$PMA_DIR/config.sample.inc.php" "$PMA_DIR/config.inc.php"

  # Gera blowfish secret aleatório (32 chars)
  local BLOWFISH_SECRET
  BLOWFISH_SECRET=$(openssl rand -base64 32 | tr -d '/+=' | head -c 32)
  sed -i "s|\$cfg\['blowfish_secret'\] = '';|\$cfg['blowfish_secret'] = '${BLOWFISH_SECRET}';|" "$PMA_DIR/config.inc.php"

  # Permissões
  chown -R www-data:www-data "$PMA_DIR"
  chmod -R 755 "$PMA_DIR"

  # Cria diretório de tmp do phpMyAdmin
  mkdir -p "$PMA_DIR/tmp"
  chown www-data:www-data "$PMA_DIR/tmp"
  chmod 777 "$PMA_DIR/tmp"

  log "phpMyAdmin $PMA_VERSION instalado em $PMA_DIR ✓"
}

# --------------------------------------------------------------------------- #
#  13. Clona e configura o GPanel
# --------------------------------------------------------------------------- #
install_gpanel() {
  header "Instalando GPanel"

  # Remove instalação anterior se existir
  if [[ -d "${GPANEL_DIR}/panel" ]]; then
    warn "Instalação anterior encontrada em ${GPANEL_DIR}. Fazendo backup..."
    mv "${GPANEL_DIR}" "${GPANEL_DIR}.bak.$(date +%Y%m%d_%H%M%S)" || true
  fi

  mkdir -p "${GPANEL_DIR}"

  # Cria usuário do sistema gpanel
  if ! id "$GPANEL_USER" &>/dev/null; then
    useradd --system --shell /bin/bash --create-home \
      --home-dir "${GPANEL_DIR}" "$GPANEL_USER"
    log "Usuário do sistema '$GPANEL_USER' criado ✓"
  fi

  # ── Passo 1: Instala Laravel base via composer create-project ──
  info "Criando projeto Laravel base..."
  COMPOSER_ALLOW_SUPERUSER=1 composer create-project laravel/laravel:^11.0 \
    "${GPANEL_DIR}/panel" --no-interaction --no-scripts --quiet

  if [[ ! -f "${GPANEL_DIR}/panel/artisan" ]]; then
    error "Laravel base não foi criado corretamente."
    exit 1
  fi
  log "Laravel base instalado ✓"

  # ── Passo 2: Sincroniza o repositório GPanel nativamente para suportar updates futuros ──
  info "Baixando customizações GPanel via Git..."
  cd "${GPANEL_DIR}"
  git init -q
  git remote add origin "$GPANEL_REPO"
  git fetch --all -q
  git reset --hard origin/main -q
  git branch -M main -q
  log "Repositório Git conectado e customizações aplicadas ✓"

  cd "${GPANEL_DIR}/panel"

  # ── Passo 3: Cria diretórios necessários ──
  mkdir -p storage/logs \
           storage/framework/cache \
           storage/framework/sessions \
           storage/framework/views \
           bootstrap/cache \
           "${GPANEL_DIR}/storage/logs"

  # ── Passo 4: Instala/atualiza dependências com os pacotes do composer.json customizado ──
  info "Instalando dependências PHP finais..."
  # --no-scripts evita que o composer tente rodar "php artisan package:discover"
  # antes do Laravel estar completamente configurado
  if ! COMPOSER_ALLOW_SUPERUSER=1 composer install \
      --no-dev \
      --no-scripts \
      --optimize-autoloader \
      --no-interaction 2>&1; then
    error "composer install falhou. Verifique o erro acima."
    exit 1
  fi
  log "Dependências PHP instaladas ✓"

  # Roda os scripts do composer manualmente agora que o Laravel está configurado
  info "Registrando pacotes Laravel..."
  COMPOSER_ALLOW_SUPERUSER=1 composer run-script post-autoload-dump --no-interaction 2>/dev/null || \
    php artisan package:discover --ansi 2>/dev/null || true

  # Instala dependências JS do painel
  if [[ -f package.json ]]; then
    info "Compilando assets frontend..."
    if ! npm install 2>&1; then
      warn "npm install falhou — assets serão carregados via CDN."
    else
      npm run build 2>&1 || warn "npm run build falhou — assets serão carregados via CDN."
      log "Assets frontend compilados ✓"
    fi
  fi

  # Configura .env
  if [[ ! -f .env ]]; then
    if [[ -f .env.example ]]; then
      cp .env.example .env
    else
      # Gera um .env mínimo se não houver .env.example
      cat > .env <<ENVEOF
APP_NAME=GPanel
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=http://localhost:${GPANEL_PORT}
LOG_CHANNEL=stack
LOG_LEVEL=info
DB_CONNECTION=sqlite
DB_DATABASE=${GPANEL_DIR}/storage/database.sqlite
QUEUE_CONNECTION=database
CACHE_DRIVER=file
SESSION_DRIVER=file
SESSION_LIFETIME=480
GPANEL_PORT=${GPANEL_PORT}
GPANEL_DIR=${GPANEL_DIR}
ENVEOF
    fi
  fi

  # Gera APP_KEY
  php artisan key:generate --force --no-interaction 2>/dev/null || true
  # Garante que as configs principais estão corretas
  sed -i "s|APP_URL=.*|APP_URL=http://localhost:${GPANEL_PORT}|" .env
  sed -i "s|DB_CONNECTION=.*|DB_CONNECTION=sqlite|" .env
  sed -i "s|DB_DATABASE=.*|DB_DATABASE=${GPANEL_DIR}/storage/database.sqlite|" .env

  # Injeta senha root do MySQL gerada durante a instalação
  if [[ -f /root/.gpanel_mysql_root ]]; then
    local _mysql_pass
    _mysql_pass=$(grep '^MYSQL_ROOT_PASSWORD=' /root/.gpanel_mysql_root | cut -d= -f2-)
    if [[ -n "$_mysql_pass" ]]; then
      grep -q '^MYSQL_ROOT_PASSWORD=' .env \
        && sed -i "s|^MYSQL_ROOT_PASSWORD=.*|MYSQL_ROOT_PASSWORD=${_mysql_pass}|" .env \
        || echo "MYSQL_ROOT_PASSWORD=${_mysql_pass}" >> .env
      log "MYSQL_ROOT_PASSWORD injetado no .env ✓"
    fi
  fi

  # Cria banco SQLite e roda migrations
  touch "${GPANEL_DIR}/storage/database.sqlite"
  info "Rodando migrations..."
  if ! php artisan migrate --force --no-interaction 2>&1; then
    error "php artisan migrate falhou. Verifique o erro acima."
    exit 1
  fi
  log "Banco de dados criado ✓"

  # Caches de produção
  php artisan config:cache  --no-interaction 2>/dev/null || true
  php artisan route:cache   --no-interaction 2>/dev/null || true

  # Permissões
  chown -R "${GPANEL_USER}:www-data" "${GPANEL_DIR}"
  chmod -R 755 "${GPANEL_DIR}"
  chmod -R 775 "${GPANEL_DIR}/panel/storage" \
               "${GPANEL_DIR}/panel/bootstrap/cache" \
               "${GPANEL_DIR}/storage"

  log "GPanel instalado em ${GPANEL_DIR} ✓"
}

# --------------------------------------------------------------------------- #
#  14. Node.js helper (WebSocket)
# --------------------------------------------------------------------------- #
install_node_helper() {
  [[ "$INSTALL_NODEJS" != "Y" ]] && return

  header "Node.js Helper (WebSocket)"

  if [[ ! -d "${GPANEL_DIR}/node-helper" ]]; then
    warn "Diretório node-helper não encontrado — pulando."
    return
  fi

  cd "${GPANEL_DIR}/node-helper"

  if [[ ! -f package.json ]]; then
    warn "package.json do node-helper não encontrado — pulando."
    return
  fi

  if ! npm install 2>&1; then
    warn "npm install do node-helper falhou — terminal web não estará disponível."
    return
  fi

  log "Node.js helper configurado ✓"
}

# --------------------------------------------------------------------------- #
#  14. Nginx — vhost do painel
# --------------------------------------------------------------------------- #
configure_nginx_panel() {
  header "Nginx — Vhost do Painel"

  cat > /etc/nginx/sites-available/gpanel <<NGINXEOF
server {
    listen ${GPANEL_PORT};
    server_name _;

    root ${GPANEL_DIR}/panel/public;
    index index.php;

    access_log /var/log/nginx/gpanel_access.log;
    error_log  /var/log/nginx/gpanel_error.log;

    # Segurança
    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header X-XSS-Protection "1; mode=block";

    # Limite de upload
    client_max_body_size 100M;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php${PHP_VERSION}-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 300;
    }

    # WebSocket proxy para o Node.js helper
    location /ws/ {
        proxy_pass http://127.0.0.1:3001;
        proxy_http_version 1.1;
        proxy_set_header Upgrade \$http_upgrade;
        proxy_set_header Connection "Upgrade";
        proxy_set_header Host \$host;
        proxy_cache_bypass \$http_upgrade;
    }

    # phpMyAdmin
    location /phpmyadmin {
        alias /var/www/phpmyadmin;
        index index.php;

        location ~ ^/phpmyadmin(.+\.php)$ {
            alias /var/www/phpmyadmin\$1;
            fastcgi_pass unix:/run/php/php${PHP_VERSION}-fpm.sock;
            fastcgi_param SCRIPT_FILENAME /var/www/phpmyadmin\$1;
            include fastcgi_params;
            fastcgi_read_timeout 300;
        }

        location ~* ^/phpmyadmin(.+\.(jpg|jpeg|gif|css|png|js|ico|html|xml|txt))$ {
            alias /var/www/phpmyadmin\$1;
        }
    }

    location ~ /\.ht {
        deny all;
    }
}
NGINXEOF

  ln -sf /etc/nginx/sites-available/gpanel /etc/nginx/sites-enabled/gpanel

  # Remove default do nginx se existir
  rm -f /etc/nginx/sites-enabled/default

  nginx -t && systemctl reload nginx
  log "Nginx configurado para servir o painel na porta $GPANEL_PORT ✓"
}

# --------------------------------------------------------------------------- #
#  15. Supervisor
# --------------------------------------------------------------------------- #
configure_supervisor() {
  header "Supervisor"

  # Worker de filas Laravel
  # numprocs=1 para evitar conflito de process_name — pode aumentar depois
  cat > /etc/supervisor/conf.d/gpanel-worker.conf <<SUPEOF
[program:gpanel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php ${GPANEL_DIR}/panel/artisan queue:work --sleep=3 --tries=3 --max-time=3600
directory=${GPANEL_DIR}/panel
user=root
numprocs=1
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
stdout_logfile=${GPANEL_DIR}/storage/logs/worker.log
stderr_logfile=${GPANEL_DIR}/storage/logs/worker_error.log
stopwaitsecs=3600
SUPEOF

  # Node.js helper
  if [[ "$INSTALL_NODEJS" == "Y" ]]; then
    cat > /etc/supervisor/conf.d/gpanel-node.conf <<SUPEOF
[program:gpanel-node]
process_name=%(program_name)s
command=node ${GPANEL_DIR}/node-helper/server.js
directory=${GPANEL_DIR}/node-helper
user=root
autostart=true
autorestart=true
stdout_logfile=${GPANEL_DIR}/storage/logs/node_helper.log
stderr_logfile=${GPANEL_DIR}/storage/logs/node_helper_error.log
environment=GPANEL_DIR="${GPANEL_DIR}",NODE_ENV="production"
SUPEOF
  fi

  # Reinicia o supervisor para pegar as novas configs
  systemctl restart supervisor 2>/dev/null || service supervisor restart 2>/dev/null || true
  sleep 2

  supervisorctl reread
  supervisorctl update
  supervisorctl start gpanel-worker:*

  log "Supervisor configurado ✓"
}

# --------------------------------------------------------------------------- #
#  16. Firewall (UFW)
# --------------------------------------------------------------------------- #
# --------------------------------------------------------------------------- #
#  Sudoers: permite www-data executar comandos privilegiados sem senha
# --------------------------------------------------------------------------- #
configure_sudoers() {
  header "Permissões sudo (www-data)"

  cat > /etc/sudoers.d/gpanel-www-data << 'SUDOEOF'
# GPanel: permite o processo PHP-FPM (www-data) executar comandos de sistema
# necessários para gerenciar nginx, PHP-FPM, UFW, SSL e sites.
www-data ALL=(root) NOPASSWD: ALL
SUDOEOF

  chmod 440 /etc/sudoers.d/gpanel-www-data

  # Valida sintaxe do arquivo sudoers
  if visudo -cf /etc/sudoers.d/gpanel-www-data &>/dev/null; then
    log "Sudoers para www-data configurado ✓"
  else
    warn "Erro na validação do sudoers — verifique /etc/sudoers.d/gpanel-www-data"
  fi
}

# --------------------------------------------------------------------------- #
configure_firewall() {
  header "Firewall (UFW)"

  ufw --force reset
  ufw default deny incoming
  ufw default allow outgoing

  # SSH — sempre aberto
  ufw allow 22/tcp comment "SSH"

  # HTTP/HTTPS
  ufw allow 80/tcp  comment "HTTP"
  ufw allow 443/tcp comment "HTTPS"

  # Painel GPanel
  ufw allow ${GPANEL_PORT}/tcp comment "GPanel"

  # FTP passivo (se instalado)
  if [[ "$INSTALL_FTP" == "Y" ]]; then
    ufw allow 21/tcp    comment "FTP"
    ufw allow 40000:50000/tcp comment "FTP Passivo"
  fi

  ufw --force enable
  log "UFW configurado ✓"
}

# --------------------------------------------------------------------------- #
#  17. CLI global gpanel
# --------------------------------------------------------------------------- #
install_gpanel_cli() {
  header "CLI gpanel"

  cat > /usr/local/bin/gpanel <<CLIEOF
#!/usr/bin/env bash
exec php ${GPANEL_DIR}/panel/artisan "\$@"
CLIEOF

  chmod +x /usr/local/bin/gpanel
  log "Comando 'gpanel' registrado em /usr/local/bin/ ✓"
}

# --------------------------------------------------------------------------- #
#  18. Backup automático
# --------------------------------------------------------------------------- #
configure_backup() {
  [[ "$INSTALL_AUTO_BACKUP" != "Y" ]] && return

  header "Backup Automático"

  echo "0 3 * * * ${GPANEL_USER} php ${GPANEL_DIR}/panel/artisan backup:run --only-files --quiet" \
    > /etc/cron.d/gpanel-backup

  log "Backup diário configurado (03:00 AM) ✓"
}

# --------------------------------------------------------------------------- #
#  19. Systemd services
# --------------------------------------------------------------------------- #
configure_systemd() {
  # Garante que os serviços principais iniciem com o sistema
  systemctl enable supervisor 2>/dev/null || true

  [[ "$INSTALL_NGINX" == "Y" ]]      && systemctl enable nginx
  [[ "$INSTALL_PHP" == "Y" ]]        && systemctl enable php${PHP_VERSION}-fpm
  [[ "$INSTALL_MYSQL" == "Y" ]]      && systemctl enable mysql
  [[ "$INSTALL_POSTGRESQL" == "Y" ]] && systemctl enable postgresql
  [[ "$INSTALL_REDIS" == "Y" ]]      && systemctl enable redis-server

  log "Serviços habilitados no boot ✓"
}

# --------------------------------------------------------------------------- #
#  20. Mensagem final
# --------------------------------------------------------------------------- #
print_success() {
  local server_ip
  server_ip=$(hostname -I | awk '{print $1}' 2>/dev/null || echo "SEU_IP")

  echo ""
  echo -e "${GREEN}${BOLD}"
  echo "  ╔══════════════════════════════════════════╗"
  echo "  ║        GPanel instalado com sucesso!     ║"
  echo "  ╚══════════════════════════════════════════╝"
  echo -e "${NC}"
  echo -e "  ${BOLD}Acesse o painel:${NC}"
  echo -e "  ${BLUE}http://${server_ip}:${GPANEL_PORT}${NC}"
  echo ""
  echo -e "  ${BOLD}Complete a configuração inicial no wizard de setup.${NC}"
  echo ""
  echo -e "  ${BOLD}Comandos úteis:${NC}"
  echo "    gpanel panel:status       — status dos serviços"
  echo "    gpanel admin:create       — criar usuário admin"
  echo "    gpanel logs               — ver logs em tempo real"
  echo ""
  if [[ "$INSTALL_MYSQL" == "Y" ]]; then
    echo -e "  ${YELLOW}Credenciais MySQL root salvas em: /root/.gpanel_mysql_root${NC}"
    echo -e "  ${BLUE}phpMyAdmin: http://${server_ip}:${GPANEL_PORT}/phpmyadmin${NC}"
  fi
  echo ""
}

# --------------------------------------------------------------------------- #
#  MAIN
# --------------------------------------------------------------------------- #
main() {
  clear
  echo -e "${BOLD}${BLUE}"
  echo "   ██████╗ ██████╗  █████╗ ███╗   ██╗███████╗██╗"
  echo "  ██╔════╝ ██╔══██╗██╔══██╗████╗  ██║██╔════╝██║"
  echo "  ██║  ███╗██████╔╝███████║██╔██╗ ██║█████╗  ██║"
  echo "  ██║   ██║██╔═══╝ ██╔══██║██║╚██╗██║██╔══╝  ██║"
  echo "  ╚██████╔╝██║     ██║  ██║██║ ╚████║███████╗███████╗"
  echo "   ╚═════╝ ╚═╝     ╚═╝  ╚═╝╚═╝  ╚═══╝╚══════╝╚══════╝"
  echo -e "${NC}"
  echo -e "  ${BOLD}Painel de Gerenciamento de Servidores${NC}"
  echo -e "  Versão 1.0 "
  echo ""

  check_root
  check_os
  check_resources
  check_port

  select_stacks

  install_base_deps
  install_nginx
  install_php
  install_mysql
  install_postgresql
  install_redis
  install_nodejs
  install_python
  install_ftp
  install_phpmyadmin
  install_gpanel
  install_gpanel_cli
  install_node_helper
  configure_nginx_panel
  configure_supervisor
  configure_sudoers
  configure_firewall
  configure_backup
  configure_systemd
  bash "${GPANEL_DIR}/scripts/harden-php.sh" 2>/dev/null || warn "Hardening PHP falhou — execute manualmente: sudo bash ${GPANEL_DIR}/scripts/harden-php.sh"

  print_success
}

main "$@"
