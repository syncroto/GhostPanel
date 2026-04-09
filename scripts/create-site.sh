#!/usr/bin/env bash
# =============================================================================
#  GPanel — Script de criação de site
#  Chamado pelo CreateSiteJob via CommandService
#  USO: create-site.sh <domain> <type> <php_version> <root_path>
# =============================================================================
set -euo pipefail

DOMAIN="${1:?Domínio obrigatório}"
TYPE="${2:?Tipo obrigatório (php|nodejs|python|wordpress)}"
PHP_VERSION="${3:-8.2}"
ROOT_PATH="${4:-/var/www/sites/${DOMAIN}/public}"
SITE_PATH="/var/www/sites/${DOMAIN}"

# Validação do domínio (apenas chars válidos)
if ! echo "$DOMAIN" | grep -qP '^[a-zA-Z0-9.-]+$'; then
  echo "[ERRO] Domínio inválido: $DOMAIN" >&2
  exit 1
fi

# Validação do tipo
case "$TYPE" in
  php|nodejs|python|wordpress) ;;
  *) echo "[ERRO] Tipo inválido: $TYPE" >&2; exit 1 ;;
esac

log()   { echo "[$(date '+%H:%M:%S')] $*"; }
error() { echo "[ERRO] $*" >&2; }

log "Criando site: $DOMAIN (type=$TYPE, php=$PHP_VERSION)"

# Cria diretórios
mkdir -p "${ROOT_PATH}" "${SITE_PATH}/logs"
chown -R www-data:www-data "${SITE_PATH}"
chmod -R 755 "${SITE_PATH}"

log "Diretórios criados em ${SITE_PATH}"
