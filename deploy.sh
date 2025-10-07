#!/bin/bash

# Script de deploy para fotosquiz.in9automacao.com.br
# Executa na VPS: ssh root@31.97.169.36

set -e  # Parar execução em caso de erro

# Cores para output
GREEN='\033[0;32m'
BLUE='\033[0;34m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo -e "${BLUE}🚀 Iniciando deploy do Quiz de Colaboradores...${NC}"

# Variáveis
DOMAIN="fotosquiz.in9automacao.com.br"
APP_DIR="/var/www/quiz-colaboradores"
REPO_URL="https://github.com/ArthurGueler-dev/quiz-colaboradores"
PORT=3002

# 1. Remover aplicação anterior do domínio
echo -e "${BLUE}📦 Removendo aplicação anterior...${NC}"
if [ -d "$APP_DIR" ]; then
    cd "$APP_DIR"
    pm2 delete quiz-colaboradores 2>/dev/null || true
    cd /var/www
    rm -rf "$APP_DIR"
fi

# 2. Clonar repositório
echo -e "${BLUE}📥 Clonando repositório...${NC}"
git clone "$REPO_URL" "$APP_DIR"
cd "$APP_DIR"

# 3. Instalar dependências
echo -e "${BLUE}📦 Instalando dependências...${NC}"
npm install --production

# 4. Criar diretório para banco de dados
echo -e "${BLUE}💾 Preparando banco de dados...${NC}"
mkdir -p db

# 5. Configurar variável de ambiente
export PORT=$PORT

# 6. Iniciar com PM2
echo -e "${BLUE}🔄 Iniciando aplicação com PM2...${NC}"
pm2 start ecosystem.config.js
pm2 save

# 7. Configurar Nginx
echo -e "${BLUE}🌐 Configurando Nginx...${NC}"
cat > /etc/nginx/sites-available/$DOMAIN << 'EOF'
server {
    listen 80;
    listen [::]:80;
    server_name fotosquiz.in9automacao.com.br;

    location / {
        proxy_pass http://localhost:3002;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host $host;
        proxy_cache_bypass $http_upgrade;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
EOF

# 8. Ativar site no Nginx
ln -sf /etc/nginx/sites-available/$DOMAIN /etc/nginx/sites-enabled/
nginx -t && systemctl reload nginx

# 9. Configurar SSL com Let's Encrypt
echo -e "${BLUE}🔒 Configurando SSL...${NC}"
if command -v certbot &> /dev/null; then
    certbot --nginx -d $DOMAIN --non-interactive --agree-tos --email admin@in9automacao.com.br || echo "Certbot falhou ou já configurado"
else
    echo -e "${RED}⚠️  Certbot não encontrado. Instale com: apt install certbot python3-certbot-nginx${NC}"
fi

# 10. Verificar status
echo -e "${GREEN}✅ Deploy concluído!${NC}"
echo -e "${GREEN}🌐 Aplicação disponível em: https://$DOMAIN${NC}"
echo ""
pm2 status
