# Sistema de Autenticação do Quiz

## Visão Geral

O quiz agora possui um sistema de autenticação que garante que cada colaborador jogue apenas uma vez.

## Como Funciona

### 1. **Login do Colaborador**
- O colaborador entra com seu **código/username** na tela inicial
- API: `https://floripa.in9automacao.com.br/colaborador_login.php`
- Verifica se o colaborador existe na tabela `Users`
- Verifica se já participou na tabela `quiz_participacoes`

### 2. **Verificação de Participação**
- Se já jogou: mostra mensagem com resultado anterior
- Se não jogou: permite iniciar o quiz

### 3. **Salvamento do Resultado**
- Ao terminar o quiz, o resultado é salvo automaticamente
- API: `https://floripa.in9automacao.com.br/salvar_resultado.php`
- Salva: username, acertos, total, data_participacao

## Banco de Dados

### Tabela `Users` (já existe)
```sql
- Username (VARCHAR) - código do colaborador
- Aplicativos (VARCHAR)
- Password, IsAdmin, IsRoot (para outros usos)
```

### Tabela `quiz_participacoes` (criada automaticamente)
```sql
CREATE TABLE quiz_participacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL,
    acertos INT NOT NULL DEFAULT 0,
    total INT NOT NULL DEFAULT 0,
    data_participacao DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user (username)
);
```

## APIs Criadas

### 1. `/colaborador_login.php`
**Método:** POST
**Body:** `{ "codigo": "username" }`
**Resposta sucesso:**
```json
{
  "success": true,
  "message": "Login ok",
  "participante": {
    "id": "username_timestamp",
    "username": "username",
    "name": "Username",
    "token": "token_gerado"
  },
  "token": "token_gerado"
}
```

**Resposta se já jogou:**
```json
{
  "success": false,
  "error": "Você já participou do quiz",
  "ja_jogou": true,
  "resultado": {
    "acertos": 5,
    "total": 10,
    "data": "2025-10-01 10:30:00"
  }
}
```

### 2. `/salvar_resultado.php`
**Método:** POST
**Body:**
```json
{
  "username": "codigo_colaborador",
  "acertos": 7,
  "total": 10
}
```

**Resposta:**
```json
{
  "success": true,
  "message": "Resultado salvo com sucesso",
  "resultado": {
    "username": "codigo_colaborador",
    "acertos": 7,
    "total": 10
  }
}
```

## Fluxo do Quiz

1. **Tela de Login**
   - Colaborador digita código
   - Sistema verifica se existe e se já jogou

2. **Tela de Instruções**
   - Explica como funciona o quiz
   - Botão "Começar" inicia o quiz

3. **Tela do Quiz**
   - Mostra foto da criança
   - 3 opções de colaboradores adultos
   - Conta acertos automaticamente

4. **Tela de Resultado**
   - Mostra acertos/total
   - Salva automaticamente no banco
   - Botão "Novo participante" volta ao login

## Configuração

### Modo Mock (Teste Local)
No arquivo `app.js`, linha 7:
```javascript
const USE_MOCKS = true;  // Usa dados simulados
```

### Modo Produção (API Real)
```javascript
const USE_MOCKS = false;  // Usa API real
```

## Testando

### Teste 1: Primeiro Acesso
1. Use um username que existe na tabela `Users`
2. Digite o código na tela inicial
3. Deve permitir jogar

### Teste 2: Segundo Acesso (Já Jogou)
1. Use o mesmo username
2. Deve mostrar: "Você já participou do quiz" + resultado anterior

### Teste 3: Username Inválido
1. Use um código que não existe
2. Deve mostrar: "Colaborador não encontrado"

## Hospedagem das APIs

Faça upload dos arquivos PHP para o servidor:
- `api/colaborador_login.php` → `https://floripa.in9automacao.com.br/colaborador_login.php`
- `api/salvar_resultado.php` → `https://floripa.in9automacao.com.br/salvar_resultado.php`

## Segurança

- ✅ Verificação de usuário no banco
- ✅ Proteção contra múltiplas participações (UNIQUE KEY)
- ✅ Validação de dados no backend
- ✅ Headers CORS configurados
- ⚠️ **Importante:** As credenciais do banco estão hardcoded - considere usar variáveis de ambiente

## Próximos Passos (Opcional)

- [ ] Adicionar fotos reais dos colaboradores
- [ ] Criar API para buscar perguntas do banco
- [ ] Dashboard admin para ver todas as participações
- [ ] Sistema de ranking
