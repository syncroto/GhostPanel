#!/usr/bin/env bash
# =============================================================================
#  GPanel — Hardening PHP contra Web Shells
#  Uso: sudo bash harden-php.sh
#  Pode ser re-executado a qualquer momento (idempotente).
# =============================================================================
set -euo pipefail

PHP_VERSION="${PHP_VERSION:-8.2}"
SITES_ROOT="/var/www/sites"

GREEN='\033[0;32m'; BLUE='\033[0;34m'; YELLOW='\033[1;33m'; NC='\033[0m'
log()  { echo -e "${GREEN}[✓]${NC} $*"; }
info() { echo -e "${BLUE}[i]${NC} $*"; }
warn() { echo -e "${YELLOW}[!]${NC} $*"; }

[[ $EUID -ne 0 ]] && { echo "Execute como root."; exit 1; }

# --------------------------------------------------------------------------- #
#  1. PHP-FPM — desabilita funções perigosas e restringe ambiente
# --------------------------------------------------------------------------- #
info "Aplicando hardening no PHP-FPM ${PHP_VERSION}..."

PHP_INI_DIR="/etc/php/${PHP_VERSION}/fpm/conf.d"
mkdir -p "$PHP_INI_DIR"

cat > "${PHP_INI_DIR}/99-gpanel-hardening.ini" <<'PHPEOF'
; GPanel Security Hardening
; Desabilita funções comumente usadas em web shells

; Funções de execução de comandos do sistema
; NOTA: proc_open, popen, curl_exec, curl_multi_exec, realpath NÃO estão aqui
; pois são usados pelo Laravel (proc_open pelo CommandService, curl pelo Guzzle/HTTP,
; realpath pelo autoloader). Apenas funções sem uso legítimo no painel são bloqueadas.
disable_functions = exec,passthru,shell_exec,system,\
parse_ini_file,show_source,\
pcntl_exec,pcntl_fork,pcntl_signal,pcntl_waitpid,\
posix_kill,posix_setpgid,posix_setsid,posix_setuid,\
dl,link,symlink

; Expõe menos informações sobre o servidor
expose_php          = Off
display_errors      = Off
log_errors          = On
error_log           = /var/log/php/errors.log

; Limita o tempo de execução
max_execution_time  = 60
max_input_time      = 60

; Upload seguro
file_uploads        = On
upload_max_filesize = 64M
post_max_size       = 64M

; Desabilita acesso a URLs remotas em funções de arquivo
allow_url_fopen     = Off
allow_url_include   = Off
PHPEOF

mkdir -p /var/log/php
chown www-data:www-data /var/log/php

systemctl reload php${PHP_VERSION}-fpm 2>/dev/null || true
log "PHP-FPM hardening aplicado ✓"

# --------------------------------------------------------------------------- #
#  2. Nginx — bloqueia execução de PHP em pastas de upload por site
# --------------------------------------------------------------------------- #
info "Configurando bloqueio de execução em pastas de upload..."

NGINX_SNIPPET="/etc/nginx/snippets/gpanel-no-php-exec.conf"

cat > "$NGINX_SNIPPET" <<'NGINXEOF'
# GPanel: bloqueia execução de PHP em pastas que não deveriam tê-la
# Inclua este snippet nos vhosts: include snippets/gpanel-no-php-exec.conf;

# Pastas comuns de upload/assets — nega PHP
location ~* ^/(uploads|files|media|images|assets|static|cache|tmp)/.*\.php$ {
    deny all;
    return 403;
}

# Bloqueia .htaccess (segurança extra)
location ~ /\.ht {
    deny all;
}

# Bloqueia .env, .git, .svn
location ~ /\.(env|git|svn|hg)(/|$) {
    deny all;
    return 403;
}

# Bloqueia arquivos com extensões perigosas em qualquer pasta
location ~* \.(phtml|php3|php4|php5|php7|phar|cgi|pl|py|sh|bash)$ {
    # Somente nega se vier de pastas não-raiz (para não quebrar o site)
    location ~* ^/(uploads|files|media|images|assets|static|cache|tmp)/.*\.(phtml|php3|php4|php5|php7|phar)$ {
        deny all;
        return 403;
    }
}
NGINXEOF

log "Snippet nginx criado em $NGINX_SNIPPET ✓"

# --------------------------------------------------------------------------- #
#  3. Injeta o snippet nos vhosts existentes que ainda não têm
# --------------------------------------------------------------------------- #
UPDATED=0
for VHOST in /etc/nginx/sites-available/*; do
    [[ "$VHOST" == *"gpanel"* ]] && continue  # pula o vhost do painel
    [[ ! -f "$VHOST" ]] && continue

    if ! grep -q "gpanel-no-php-exec" "$VHOST"; then
        # Insere antes do último }
        python3 - "$VHOST" <<'PYEOF'
import sys
f = sys.argv[1]
with open(f) as fh:
    content = fh.read()
marker = '    location ~ /\\.ht {'
insert = '    include snippets/gpanel-no-php-exec.conf;\n\n'
if marker in content:
    content = content.replace(marker, insert + marker, 1)
else:
    # Insere antes do último }
    idx = content.rfind('}')
    if idx != -1:
        content = content[:idx] + '\n    include snippets/gpanel-no-php-exec.conf;\n' + content[idx:]
with open(f, 'w') as fh:
    fh.write(content)
PYEOF
        UPDATED=$((UPDATED + 1))
        info "  Snippet adicionado: $(basename $VHOST)"
    fi
done

if [[ $UPDATED -gt 0 ]]; then
    nginx -t && systemctl reload nginx
    log "$UPDATED vhost(s) atualizado(s) e nginx recarregado ✓"
else
    log "Todos os vhosts já estão protegidos ✓"
fi

# --------------------------------------------------------------------------- #
#  4. open_basedir por site (restringe PHP ao diretório do site)
# --------------------------------------------------------------------------- #
info "Aplicando open_basedir por site..."
UPDATED_POOLS=0

for SITE_DIR in "$SITES_ROOT"/*/; do
    DOMAIN=$(basename "$SITE_DIR")
    POOL_FILE="/etc/php/${PHP_VERSION}/fpm/pool.d/${DOMAIN}.conf"

    # Só aplica se já existir um pool dedicado
    if [[ -f "$POOL_FILE" ]]; then
        if ! grep -q "open_basedir" "$POOL_FILE"; then
            echo "php_admin_value[open_basedir] = ${SITE_DIR}:/tmp:/usr/share/php" >> "$POOL_FILE"
            UPDATED_POOLS=$((UPDATED_POOLS + 1))
        fi
    fi
done

[[ $UPDATED_POOLS -gt 0 ]] && systemctl reload php${PHP_VERSION}-fpm 2>/dev/null || true
log "open_basedir aplicado em $UPDATED_POOLS pool(s) ✓"

# --------------------------------------------------------------------------- #
#  5. Resumo
# --------------------------------------------------------------------------- #
echo ""
echo -e "${GREEN}  Hardening concluído!${NC}"
echo ""
echo "  O que foi aplicado:"
echo "    • PHP: disable_functions (exec, shell_exec, system, passthru, etc.)"
echo "    • PHP: allow_url_fopen = Off, allow_url_include = Off"
echo "    • Nginx: bloqueia PHP em /uploads/, /files/, /media/, /assets/, etc."
echo "    • Nginx: bloqueia .env, .git, .htaccess"
echo ""
echo -e "  ${YELLOW}Nota: disable_functions pode quebrar plugins que usam exec() legítimo."
echo -e "  Se necessário, remova funções específicas de:${NC}"
echo "    /etc/php/${PHP_VERSION}/fpm/conf.d/99-gpanel-hardening.ini"
echo ""
