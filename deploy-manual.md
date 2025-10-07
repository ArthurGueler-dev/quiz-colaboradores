# 🚀 Deploy Manual - Passo a Passo

Execute estes comandos na VPS:

## 1. Conectar na VPS
```bash
ssh root@31.97.169.36
```

## 2. Remover aplicação anterior
```bash
# Parar processos PM2
pm2 delete all 2>/dev/null || true

# Remover diretório anterior
rm -rf /var/www/quiz-colaboradores
```

## 3. Clonar repositório
```bash
cd /var/www
git clone https://github.com/ArthurGueler-dev/quiz-colaboradores.git
cd quiz-colaboradores
```

## 4. Instalar dependências
```bash
npm install --production
```

## 5. Criar diretórios necessários
```bash
mkdir -p db logs
```

## 6. Iniciar com PM2
```bash
PORT=3001 pm2 start server.js --name quiz-colaboradores
pm2 save
pm2 startup
```

## 7. Configurar Nginx
```bash
cat > /etc/nginx/sites-available/quiz.in9automacao.com.br << 'EOF'
server {
    listen 80;
    listen [::]:80;
    server_name quiz.in9automacao.com.br;

    access_log /var/log/nginx/quiz.access.log;
    error_log /var/log/nginx/quiz.error.log;

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

# Ativar site
ln -sf /etc/nginx/sites-available/quiz.in9automacao.com.br /etc/nginx/sites-enabled/

# Testar e recarregar
nginx -t && systemctl reload nginx
```

## 8. Configurar SSL (HTTPS)
```bash
certbot --nginx -d quiz.in9automacao.com.br --non-interactive --agree-tos --email admin@in9automacao.com.br
```

## 9. Verificar status
```bash
pm2 status
curl http://localhost:3001/health
```

✅ **Pronto! Acesse:** https://quiz.in9automacao.com.br
