#!/bin/bash

# Script para atualizar a aplicação na VPS
# Execute na VPS: bash /var/www/quiz-colaboradores/update.sh

set -e

echo "🔄 Atualizando aplicação Quiz Colaboradores..."

cd /var/www/quiz-colaboradores

# 1. Fazer backup do stash
echo "📦 Salvando alterações locais..."
git stash

# 2. Puxar atualizações
echo "📥 Baixando atualizações..."
git pull origin main

# 3. IMPORTANTE: Reinstalar dependências (better-sqlite3 precisa ser recompilado)
echo "🔨 Reinstalando dependências..."
rm -rf node_modules package-lock.json
npm install --production

# 4. Reiniciar aplicação
echo "🔄 Reiniciando aplicação..."
pm2 restart quiz-colaboradores

# 5. Verificar status
echo ""
echo "✅ Atualização concluída!"
pm2 list | grep quiz-colaboradores

echo ""
echo "🌐 Aplicação disponível em: https://fotosquiz.in9automacao.com.br"
