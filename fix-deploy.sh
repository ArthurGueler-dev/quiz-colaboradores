#!/bin/bash

# Script para corrigir o deploy e diagnosticar problemas

echo "🔍 Diagnosticando problema..."
echo ""

# 1. Verificar se a aplicação está rodando
echo "1️⃣ Verificando PM2:"
pm2 list
echo ""

# 2. Testar se a aplicação responde localmente
echo "2️⃣ Testando aplicação local na porta 3001:"
curl -s http://localhost:3001/health || echo "❌ Aplicação não responde na porta 3001"
echo ""

# 3. Verificar configurações Nginx para o domínio
echo "3️⃣ Verificando configurações Nginx:"
ls -la /etc/nginx/sites-enabled/ | grep fotosquiz
echo ""

# 4. Remover qualquer configuração antiga conflitante
echo "4️⃣ Removendo configurações antigas..."
rm -f /etc/nginx/sites-enabled/quiz.in9automacao.com.br
rm -f /etc/nginx/sites-available/quiz.in9automacao.com.br
rm -f /etc/nginx/sites-enabled/default

# 5. Criar configuração correta
echo "5️⃣ Criando configuração Nginx correta..."
cat > /etc/nginx/sites-available/fotosquiz.in9automacao.com.br << 'EOF'
server {
    listen 80;
    listen [::]:80;
    server_name fotosquiz.in9automacao.com.br;

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

# 6. Ativar configuração
echo "6️⃣ Ativando configuração..."
ln -sf /etc/nginx/sites-available/fotosquiz.in9automacao.com.br /etc/nginx/sites-enabled/

# 7. Testar e recarregar Nginx
echo "7️⃣ Testando configuração Nginx..."
nginx -t

if [ $? -eq 0 ]; then
    echo "✅ Configuração válida, recarregando Nginx..."
    systemctl reload nginx
    echo "✅ Nginx recarregado!"
else
    echo "❌ Erro na configuração do Nginx"
    exit 1
fi

echo ""
echo "8️⃣ Verificando aplicação novamente:"
sleep 2
curl -I http://fotosquiz.in9automacao.com.br

echo ""
echo "✅ Correção concluída!"
echo "🌐 Teste: http://fotosquiz.in9automacao.com.br"
