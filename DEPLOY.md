# 🚀 Guia de Deploy - Quiz de Colaboradores

## 📋 Pré-requisitos na VPS

Antes de fazer o deploy, certifique-se de que sua VPS possui:

- **Node.js** (v18 ou superior)
- **PM2** (gerenciador de processos)
- **Nginx** (servidor web/proxy reverso)
- **Git**
- **Certbot** (opcional, para SSL)

### Instalar pré-requisitos (se necessário):

```bash
# Atualizar sistema
apt update && apt upgrade -y

# Instalar Node.js 18+
curl -fsSL https://deb.nodesource.com/setup_18.x | bash -
apt install -y nodejs

# Instalar PM2
npm install -g pm2

# Instalar Nginx
apt install -y nginx

# Instalar Certbot (para SSL)
apt install -y certbot python3-certbot-nginx

# Instalar Git
apt install -y git
```

---

## 🎯 Opção 1: Deploy Automático (Recomendado)

### Passo 1: Fazer commit e push dos arquivos

```bash
# No seu computador (C:\Users\SAMSUNG\Desktop\quiz)
git add .
git commit -m "Adiciona configuração de deploy"
git push origin main
```

### Passo 2: Conectar na VPS e executar

```bash
# Conectar na VPS
ssh root@31.97.169.36

# Baixar o script de deploy
curl -o /tmp/deploy.sh https://raw.githubusercontent.com/ArthurGueler-dev/quiz-colaboradores/main/deploy.sh

# Dar permissão de execução
chmod +x /tmp/deploy.sh

# Executar deploy
bash /tmp/deploy.sh
```

**Pronto!** A aplicação estará rodando em `https://quiz.in9automacao.com.br`

---

## ⚙️ Opção 2: Deploy Manual

### Passo 1: Conectar na VPS

```bash
ssh root@31.97.169.36
```

### Passo 2: Remover aplicação anterior (se existir)

```bash
# Parar processo PM2 anterior (se existir)
pm2 delete all

# Remover diretório anterior
rm -rf /var/www/quiz-colaboradores
```

### Passo 3: Clonar repositório

```bash
cd /var/www
git clone https://github.com/ArthurGueler-dev/quiz-colaboradores
cd quiz-colaboradores
```

### Passo 4: Instalar dependências

```bash
npm install --production
```

### Passo 5: Criar diretórios necessários

```bash
mkdir -p db logs
```

### Passo 6: Iniciar com PM2

```bash
pm2 start ecosystem.config.js
pm2 save
pm2 startup
```

### Passo 7: Configurar Nginx

```bash
# Copiar configuração do Nginx
cp nginx.conf /etc/nginx/sites-available/quiz.in9automacao.com.br

# Criar link simbólico
ln -sf /etc/nginx/sites-available/quiz.in9automacao.com.br /etc/nginx/sites-enabled/

# Remover configuração anterior do domínio (se existir)
rm -f /etc/nginx/sites-enabled/default

# Testar configuração
nginx -t

# Recarregar Nginx
systemctl reload nginx
```

### Passo 8: Configurar SSL (HTTPS)

```bash
certbot --nginx -d quiz.in9automacao.com.br --non-interactive --agree-tos --email admin@in9automacao.com.br
```

---

## 🔍 Verificar Status

```bash
# Ver status do PM2
pm2 status

# Ver logs em tempo real
pm2 logs quiz-colaboradores

# Ver logs do Nginx
tail -f /var/log/nginx/quiz.access.log
tail -f /var/log/nginx/quiz.error.log

# Testar endpoint de health
curl http://localhost:3001/health
```

---

## 🔄 Atualizar Aplicação

Para atualizar após fazer alterações no código:

```bash
# Conectar na VPS
ssh root@31.97.169.36

# Ir para o diretório
cd /var/www/quiz-colaboradores

# Baixar atualizações
git pull origin main

# Instalar novas dependências (se houver)
npm install --production

# Reiniciar aplicação
pm2 restart quiz-colaboradores
```

---

## 🛠️ Comandos Úteis PM2

```bash
# Ver status
pm2 status

# Parar aplicação
pm2 stop quiz-colaboradores

# Reiniciar aplicação
pm2 restart quiz-colaboradores

# Ver logs
pm2 logs quiz-colaboradores

# Monitorar recursos
pm2 monit

# Remover aplicação
pm2 delete quiz-colaboradores
```

---

## 🗄️ Banco de Dados

A aplicação usa **SQLite** e o arquivo do banco será criado automaticamente em:

```
/var/www/quiz-colaboradores/db/quiz.db
```

### Backup do banco:

```bash
# Fazer backup
cp /var/www/quiz-colaboradores/db/quiz.db /var/backups/quiz-$(date +%Y%m%d).db

# Restaurar backup
cp /var/backups/quiz-20250101.db /var/www/quiz-colaboradores/db/quiz.db
pm2 restart quiz-colaboradores
```

---

## 🔐 Segurança

O deploy já inclui:

- ✅ Firewall configurado (porta 80 e 443)
- ✅ SSL/HTTPS via Let's Encrypt
- ✅ Headers de segurança no Nginx
- ✅ PM2 reinicia automaticamente em caso de falha
- ✅ Logs para auditoria

### Recomendações adicionais:

```bash
# Configurar firewall (UFW)
ufw allow 22/tcp
ufw allow 80/tcp
ufw allow 443/tcp
ufw enable

# Desabilitar login root via SSH (após criar usuário)
# Edite /etc/ssh/sshd_config:
# PermitRootLogin no
```

---

## 🐛 Solução de Problemas

### Aplicação não inicia:

```bash
# Ver logs de erro
pm2 logs quiz-colaboradores --err

# Testar manualmente
cd /var/www/quiz-colaboradores
node server.js
```

### Erro 502 Bad Gateway:

```bash
# Verificar se aplicação está rodando
pm2 status

# Verificar porta
netstat -tulpn | grep 3001

# Reiniciar Nginx
systemctl restart nginx
```

### Certificado SSL não funciona:

```bash
# Renovar certificado
certbot renew

# Verificar configuração
nginx -t
```

---

## 📊 Estrutura de Diretórios na VPS

```
/var/www/quiz-colaboradores/
├── db/
│   └── quiz.db              # Banco de dados SQLite
├── logs/
│   ├── out.log              # Logs de saída
│   ├── err.log              # Logs de erro
│   └── combined.log         # Logs combinados
├── node_modules/            # Dependências
├── public/                  # Frontend (HTML/CSS/JS)
├── server.js                # Servidor Express
├── ecosystem.config.js      # Configuração PM2
└── package.json             # Dependências do projeto
```

---

## 📞 Suporte

Se precisar de ajuda:

1. Verifique os logs: `pm2 logs quiz-colaboradores`
2. Teste o health check: `curl http://localhost:3001/health`
3. Verifique o status do Nginx: `systemctl status nginx`
4. Verifique o DNS: `nslookup quiz.in9automacao.com.br`

---

## ✅ Checklist Final

- [ ] Node.js, PM2, Nginx e Git instalados
- [ ] Repositório clonado e dependências instaladas
- [ ] PM2 iniciado e configurado para auto-start
- [ ] Nginx configurado e testado
- [ ] SSL/HTTPS configurado
- [ ] DNS apontando para o IP da VPS (31.97.169.36)
- [ ] Aplicação acessível em https://quiz.in9automacao.com.br
- [ ] Health check funcionando: https://quiz.in9automacao.com.br/health

---

**🎉 Deploy concluído com sucesso!**
