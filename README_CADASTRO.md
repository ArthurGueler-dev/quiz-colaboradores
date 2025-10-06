# 📝 Sistema de Cadastro e Autenticação

## ✅ O que foi implementado

### **3 Tabelas no Banco de Dados**

1. **`quiz_colaboradores`** - Armazena os colaboradores cadastrados
2. **`quiz_participacoes`** - Registra quem já jogou e seus resultados
3. **`Users`** - Mantida para o painel administrativo (não usada no quiz)

### **3 APIs PHP**

1. **`cadastro_colaborador.php`** - Cadastra novos colaboradores
2. **`colaborador_login.php`** - Autentica e verifica se já jogou
3. **`salvar_resultado.php`** - Salva resultado ao final do quiz

### **Páginas Frontend**

1. **`/`** (index.html) - Tela de login do quiz
2. **`/cadastro.html`** - Tela de cadastro de colaborador
3. **`/admin.html`** - Painel administrativo

---

## 🎯 Fluxo Completo

```
┌─────────────────┐
│  1. CADASTRO    │ → colaborador cria código único
│  /cadastro.html │
└────────┬────────┘
         │
         ↓
┌─────────────────┐
│  2. LOGIN       │ → colaborador digita código
│  / (index.html) │
└────────┬────────┘
         │
         ↓
┌─────────────────┐
│  3. VERIFICAÇÃO │ → já jogou? SIM = bloqueia / NÃO = permite
└────────┬────────┘
         │
         ↓
┌─────────────────┐
│  4. QUIZ        │ → 10 perguntas
└────────┬────────┘
         │
         ↓
┌─────────────────┐
│  5. RESULTADO   │ → salva no banco + bloqueia novas tentativas
└─────────────────┘
```

---

## 📊 Estrutura do Banco

### Tabela `quiz_colaboradores`
```sql
CREATE TABLE quiz_colaboradores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(50) NOT NULL UNIQUE,     -- Código único de acesso
    nome VARCHAR(200) NOT NULL,             -- Nome completo
    email VARCHAR(200),                     -- E-mail (opcional)
    foto_adulto VARCHAR(500),               -- URL da foto adulto
    foto_crianca VARCHAR(500),              -- URL da foto criança
    ativo TINYINT(1) DEFAULT 1,             -- 1=ativo, 0=inativo
    data_cadastro DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

### Tabela `quiz_participacoes`
```sql
CREATE TABLE quiz_participacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,  -- Código do colaborador
    acertos INT NOT NULL DEFAULT 0,         -- Número de acertos
    total INT NOT NULL DEFAULT 0,           -- Total de perguntas
    data_participacao DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

**⚠️ Nota:** Ambas as tabelas são criadas **automaticamente** na primeira requisição.

---

## 🔌 APIs Criadas

### 1. POST `/cadastro_colaborador.php`

**Cadastra um novo colaborador**

**Request:**
```json
{
  "codigo": "joao123",
  "nome": "João Pedro Silva",
  "email": "joao@empresa.com",
  "foto_adulto": "https://...",
  "foto_crianca": "https://..."
}
```

**Response (Sucesso):**
```json
{
  "success": true,
  "message": "Colaborador cadastrado com sucesso",
  "colaborador": {
    "id": 1,
    "codigo": "joao123",
    "nome": "João Pedro Silva",
    "email": "joao@empresa.com"
  }
}
```

**Response (Código já existe):**
```json
{
  "success": false,
  "error": "Este código já está cadastrado"
}
```

---

### 2. POST `/colaborador_login.php`

**Autentica colaborador e verifica se já jogou**

**Request:**
```json
{
  "codigo": "joao123"
}
```

**Response (Sucesso - não jogou):**
```json
{
  "success": true,
  "message": "Login ok",
  "participante": {
    "id": 1,
    "codigo": "joao123",
    "username": "joao123",
    "name": "João Pedro Silva",
    "email": "joao@empresa.com",
    "token": "abc123..."
  },
  "token": "abc123..."
}
```

**Response (Já jogou):**
```json
{
  "success": false,
  "error": "Você já participou do quiz",
  "ja_jogou": true,
  "resultado": {
    "acertos": 7,
    "total": 10,
    "data": "2025-10-01 14:30:00"
  }
}
```

**Response (Não cadastrado):**
```json
{
  "success": false,
  "error": "Colaborador não encontrado. Faça seu cadastro primeiro."
}
```

---

### 3. POST `/salvar_resultado.php`

**Salva resultado do quiz**

**Request:**
```json
{
  "username": "joao123",
  "acertos": 7,
  "total": 10
}
```

**Response:**
```json
{
  "success": true,
  "message": "Resultado salvo com sucesso",
  "resultado": {
    "username": "joao123",
    "acertos": 7,
    "total": 10
  }
}
```

---

## 🚀 Como Usar

### Para Colaboradores:

1. **Acessar:** http://seu-dominio.com/
2. **Clicar em:** "Não tem cadastro? Clique aqui"
3. **Preencher:**
   - Código único (ex: `joao123`)
   - Nome completo
   - E-mail (opcional)
   - URLs das fotos (opcional)
4. **Cadastrar** → redireciona automaticamente para login
5. **Jogar o quiz** (apenas 1 vez!)

### Para Upload no Servidor:

Faça upload dos seguintes arquivos:

```
📁 Servidor (https://floripa.in9automacao.com.br/)
├── cadastro_colaborador.php
├── colaborador_login.php
└── salvar_resultado.php
```

---

## 🧪 Testando Localmente

1. **Inicie o servidor:**
   ```bash
   npx http-server -p 3000 -c-1
   ```

2. **Acesse:**
   - Quiz: http://127.0.0.1:3000/
   - Cadastro: http://127.0.0.1:3000/cadastro.html
   - Admin: http://127.0.0.1:3000/admin.html

3. **Teste o fluxo:**
   - Cadastre um colaborador
   - Faça login com o código
   - Complete o quiz
   - Tente jogar novamente (deve bloquear!)

---

## 🎨 Interface de Cadastro

A tela de cadastro (`/cadastro.html`) possui:

- ✅ Formulário simples e intuitivo
- ✅ Validação de campos obrigatórios
- ✅ Mensagens de sucesso/erro
- ✅ Redirecionamento automático após cadastro
- ✅ Link de voltar para o quiz
- ✅ Visual consistente com o quiz

---

## 🔐 Segurança Implementada

- ✅ Código único por colaborador (UNIQUE KEY)
- ✅ Verificação de participação única (UNIQUE KEY)
- ✅ Validação de campos no backend
- ✅ Status ativo/inativo para colaboradores
- ✅ Proteção contra SQL injection (prepared statements)
- ✅ CORS configurado
- ✅ Tratamento de erros

---

## 📈 Próximos Passos (Opcional)

- [ ] Upload de fotos via formulário
- [ ] Página admin para gerenciar colaboradores
- [ ] Dashboard com ranking geral
- [ ] Exportar resultados para Excel
- [ ] Sistema de notificação por e-mail
- [ ] Limite de tempo para responder
