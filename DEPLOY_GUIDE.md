# 🚀 Guia de Deploy - Melhorias de Segurança

## 📦 Arquivos Novos Criados

### Arquivos de Infraestrutura
- `api/db_config.php` - Configuração centralizada do banco
- `api/session_manager.php` - Gerenciamento de sessões seguras

### APIs de Verificação de Email
- `api/enviar_codigo_verificacao.php` - Envia código por email
- `api/verificar_codigo_email.php` - Valida código

### Documentação
- `SECURITY_IMPROVEMENTS.md` - Detalhes das melhorias
- `DEPLOY_GUIDE.md` - Este arquivo

## 📤 Como Fazer Deploy

### Opção 1: Upload via FTP (cPanel)
```bash
1. Conecte-se via FTP ao servidor
2. Navegue até /public_html/quiz/
3. Faça upload dos seguintes arquivos:

   api/db_config.php (NOVO)
   api/session_manager.php (NOVO)
   api/enviar_codigo_verificacao.php (NOVO)
   api/verificar_codigo_email.php (NOVO)
   api/quiz.php (MODIFICADO)
   api/responder.php (MODIFICADO)
   api/salvar_resultado.php (MODIFICADO)
   api/colaborador_login.php (MODIFICADO)
   public/app.js (MODIFICADO)
```

### Opção 2: Upload via SSH/rsync
```bash
# Da sua máquina local:
cd "C:\Users\SAMSUNG\Desktop\quiz"

# Upload dos novos arquivos
scp api/db_config.php root@31.97.169.36:/caminho/para/quiz/api/
scp api/session_manager.php root@31.97.169.36:/caminho/para/quiz/api/
scp api/enviar_codigo_verificacao.php root@31.97.169.36:/caminho/para/quiz/api/
scp api/verificar_codigo_email.php root@31.97.169.36:/caminho/para/quiz/api/

# Upload dos arquivos modificados
scp api/quiz.php root@31.97.169.36:/caminho/para/quiz/api/
scp api/responder.php root@31.97.169.36:/caminho/para/quiz/api/
scp api/salvar_resultado.php root@31.97.169.36:/caminho/para/quiz/api/
scp api/colaborador_login.php root@31.97.169.36:/caminho/para/quiz/api/
scp public/app.js root@31.97.169.36:/caminho/para/quiz/public/
```

## 🗄️ Configuração do Banco de Dados

As tabelas serão criadas automaticamente na primeira execução, mas você pode criá-las manualmente:

```sql
-- Tabela de sessões
CREATE TABLE IF NOT EXISTS quiz_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    token VARCHAR(64) NOT NULL UNIQUE,
    colaborador_id INT NOT NULL,
    email VARCHAR(200) NOT NULL,
    quiz_data TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,
    used TINYINT DEFAULT 0,
    INDEX idx_token (token),
    INDEX idx_colaborador (colaborador_id),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabela de verificação de email
CREATE TABLE IF NOT EXISTS quiz_email_verification (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(200) NOT NULL,
    verification_code VARCHAR(6) NOT NULL,
    colaborador_id INT NOT NULL,
    verified TINYINT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,
    INDEX idx_email (email),
    INDEX idx_code (verification_code),
    INDEX idx_colaborador (colaborador_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

## 📧 Configuração de Email

### Para Desenvolvimento/Testes
O sistema já está configurado para funcionar em desenvolvimento. Quando o email não puder ser enviado, o código de verificação é retornado na resposta da API (apenas para testes).

### Para Produção
Configure o SMTP no servidor ou use um serviço de email:

#### Opção 1: Configurar PHP mail() no servidor
```bash
# No servidor, edite php.ini
sendmail_path = /usr/sbin/sendmail -t -i
```

#### Opção 2: Usar PHPMailer (Recomendado)
```bash
# Instale via Composer
composer require phpmailer/phpmailer

# Edite api/enviar_codigo_verificacao.php para usar PHPMailer
```

#### Opção 3: Usar serviço de email (SendGrid, Mailgun, etc)
Recomendado para produção. Exemplo com SendGrid:
```php
// api/enviar_codigo_verificacao.php
// Substitua a função mail() por:
use SendGrid\Mail\Mail;

$email = new Mail();
$email->setFrom("noreply@in9automacao.com.br", "Quiz IN9");
$email->addTo($colaborador['email'], $colaborador['nome']);
$email->setSubject("Código de Verificação");
$email->addContent("text/plain", $message);

$sendgrid = new \SendGrid('SUA_API_KEY');
$sendgrid->send($email);
```

## ✅ Checklist de Deploy

- [ ] 1. Fazer backup do banco de dados
- [ ] 2. Fazer backup dos arquivos atuais
- [ ] 3. Fazer upload dos novos arquivos
- [ ] 4. Fazer upload dos arquivos modificados
- [ ] 5. Verificar permissões dos arquivos (644 para .php)
- [ ] 6. Criar tabelas no banco (ou deixar criar automaticamente)
- [ ] 7. Configurar envio de email (opcional, mas recomendado)
- [ ] 8. Testar login com usuário existente
- [ ] 9. Testar sistema de quiz completo
- [ ] 10. Verificar que respostas NÃO aparecem no frontend
- [ ] 11. Testar que manipulação de resultados é bloqueada
- [ ] 12. Configurar `display_errors = 0` em produção

## 🧪 Como Testar Após Deploy

### 1. Teste de Login
```bash
curl -X POST https://floripa.in9automacao.com.br/colaborador_login.php \
  -H "Content-Type: application/json" \
  -d '{"email":"seu@email.com","senha":"suasenha"}'
```

Deve retornar:
```json
{
  "success": true,
  "token": "abc123...",
  "participante": { ... }
}
```

### 2. Teste de Quiz (com token)
```bash
curl -X GET https://floripa.in9automacao.com.br/quiz.php \
  -H "Authorization: Bearer SEU_TOKEN"
```

Deve retornar perguntas com IDs únicos (não revelam resposta).

### 3. Teste de Segurança
Tente manipular resultado (deve falhar):
```bash
curl -X POST https://floripa.in9automacao.com.br/salvar_resultado.php \
  -H "Content-Type: application/json" \
  -d '{"colaborador_id":999,"acertos":15,"total":15}'
```

Deve retornar:
```json
{"success":false,"error":"Token não fornecido"}
```

## 🔧 Solução de Problemas

### Erro: "Token não fornecido"
- Verifique se o frontend está enviando o header `Authorization: Bearer TOKEN`
- Limpe cache do navegador

### Erro: "Sessão inválida ou expirada"
- Sessões expiram em 2 horas
- Faça login novamente
- Verifique se a tabela `quiz_sessions` foi criada

### Erro: "Email não enviado"
- Em desenvolvimento, o código ainda é retornado na resposta
- Configure SMTP ou use serviço de email para produção

### Erro: "Call to undefined function"
- Verifique se todos os arquivos foram enviados corretamente
- Confirme que `require_once` está apontando para os caminhos corretos

### Frontend não carrega perguntas
- Abra DevTools → Network
- Verifique se há erro 500 nas APIs
- Verifique logs do servidor PHP

## 🔐 Configurações de Segurança Adicionais

### 1. Desabilitar erros em produção
```php
// No início de cada arquivo PHP em produção:
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);
```

### 2. Configurar HTTPS
```bash
# Force HTTPS no .htaccess
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

### 3. Limitar rate de requisições
```php
// Adicione no início das APIs críticas:
session_start();
if (!isset($_SESSION['last_request'])) {
    $_SESSION['last_request'] = time();
    $_SESSION['request_count'] = 1;
} else {
    $elapsed = time() - $_SESSION['last_request'];
    if ($elapsed < 60) {
        $_SESSION['request_count']++;
        if ($_SESSION['request_count'] > 10) {
            http_response_code(429);
            die('{"error":"Too many requests"}');
        }
    } else {
        $_SESSION['request_count'] = 1;
        $_SESSION['last_request'] = time();
    }
}
```

## 📞 Suporte

Se encontrar problemas:
1. Verifique `SECURITY_IMPROVEMENTS.md` para detalhes técnicos
2. Consulte logs do servidor PHP
3. Teste cada endpoint individualmente com curl

---

**Data de Criação:** 2025-10-09
**Versão:** 1.0
