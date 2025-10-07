#!/bin/bash

echo "🔒 Configurando SSL para fotosquiz.in9automacao.com.br..."
echo ""

# 1. Verificar se certbot está instalado
if ! command -v certbot &> /dev/null; then
    echo "📦 Instalando Certbot..."
    apt update
    apt install -y certbot python3-certbot-nginx
fi

# 2. Parar Nginx temporariamente para renovar certificado
echo "⏸️  Parando Nginx temporariamente..."
systemctl stop nginx

# 3. Obter certificado SSL
echo "🔐 Obtendo certificado SSL..."
certbot certonly --standalone -d fotosquiz.in9automacao.com.br --non-interactive --agree-tos --email admin@in9automacao.com.br

# 4. Atualizar configuração Nginx com SSL
echo "📝 Atualizando configuração Nginx com SSL..."
cat > /etc/nginx/sites-available/fotosquiz.in9automacao.com.br << 'EOF'
server {
    listen 80;
    listen [::]:80;
    server_name fotosquiz.in9automacao.com.br;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name fotosquiz.in9automacao.com.br;

    ssl_certificate /etc/letsencrypt/live/fotosquiz.in9automacao.com.br/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/fotosquiz.in9automacao.com.br/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;

    access_log /var/log/nginx/fotosquiz.access.log;
    error_log /var/log/nginx/fotosquiz.error.log;

    location / {
        proxy_pass http://localhost:3001;
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

# 5. Testar e iniciar Nginx
echo "✅ Testando configuração..."
nginx -t

if [ $? -eq 0 ]; then
    echo "🚀 Iniciando Nginx..."
    systemctl start nginx
    echo "✅ SSL configurado com sucesso!"
else
    echo "❌ Erro na configuração"
    systemctl start nginx
    exit 1
fi

echo ""
echo "🎉 Pronto! Teste em: https://fotosquiz.in9automacao.com.br"
echo ""
echo "⚠️  IMPORTANTE: Configure o Cloudflare para modo 'Full (strict)' em SSL/TLS > Overview"
