# 🔐 Sistema de Autenticação e Segurança

## 📋 Visão Geral

O sistema de Gestão de Ativos agora possui um sistema completo de autenticação com:

- ✅ **Login seguro** com email e senha
- ✅ **Hash de senhas** usando bcrypt (PASSWORD_BCRYPT)
- ✅ **Gerenciamento de sessão** via PHP
- ✅ **Controle de permissões** (Admin vs Usuário)
- ✅ **Proteção de páginas** (redirecionamento automático para login)
- ✅ **Gerenciamento de usuários** no painel admin

---

## 🚀 Como Usar

### 1️⃣ Fazer Login

1. Acesse: **http://localhost/gestao_ativos/login.php**
2. Use as credenciais de demonstração:
   - **Email:** `admin@gestao.local`
   - **Senha:** `admin123`
3. Clique em **"🔓 Fazer Login"**

Você será redirecionado para o **Dashboard**.

### 2️⃣ Após o Login

Após fazer login, você terá acesso a:

- ✅ Dashboard
- ✅ Chamados
- ✅ Colaboradores
- ✅ Ativos
- ✅ Estoque
- ✅ Relatórios
- ✅ Configurações
- ✅ **Gerenciar Usuários** (apenas Admin)

No menu lateral, você verá:
- Seu nome de usuário
- Botão "🚪 Logout" para sair

### 3️⃣ Fazer Logout

Clique em **"🚪 Logout"** no menu lateral para sair.

---

## 👤 Gerenciar Usuários (Admin)

### Acessar Painel de Usuários

1. Faça login como Admin
2. Clique em **"👤 Usuários"** no menu (aparece apenas para Admins)

### Criar Novo Usuário

1. Clique em **"+ Novo Usuário"**
2. Preencha:
   - **Nome Completo**
   - **Email** (único no sistema)
   - **Senha** (mínimo 6 caracteres)
   - **Perfil:** Usuário ou Administrador
3. Clique em **"Criar Usuário"**

### Deletar Usuário

1. Procure o usuário na tabela
2. Clique em **"🗑️ Deletar"**
3. Confirme a exclusão

**Nota:** Você não pode deletar sua própria conta (só pode ser deletada por outro Admin).

---

## 🔒 Segurança

### Proteção de Senhas

- As senhas são criptografadas com **bcrypt** (algoritmo de hash forte)
- Mesmo o administrador não consegue ver senhas
- Se esquecer a senha, será necessário criar uma nova

### Autenticação de Páginas

Todas as páginas exigem login:

```php
<?php
require_once __DIR__ . '/auth.php';
exigirAutenticacao();
?>
```

Se tentar acessar uma página sem estar logado, será redirecionado para **login.php**.

### Controle de Permissões

Para proteger áreas administrativas, use:

```php
exigirAdmin(); // Redireciona se não for Admin
```

### Sessões Seguras

- As sessões são gerenciadas automaticamente pelo PHP
- Expiram quando o navegador é fechado (por padrão)
- Token de sessão é armazenado em cookie seguro

---

## 📁 Arquivos do Sistema de Autenticação

| Arquivo | Função |
|---------|--------|
| `auth.php` | Arquivo principal com todas as funções de autenticação |
| `login.php` | Página de login com formulário |
| `logout.php` | Realiza logout e redireciona para login |
| `acesso_negado.php` | Página exibida quando sem permissão |
| `usuarios.php` | Painel de gerenciamento de usuários (Admin) |
| `dados/usuarios.json` | Armazena dados dos usuários |

---

## 🔑 Funções Principais do auth.php

### `fazerLogin($email, $senha)`
Autentica um usuário e cria a sessão.

**Retorno:**
```php
['sucesso' => true/false, 'mensagem' => '...']
```

### `fazerLogout()`
Encerra a sessão e limpa dados.

### `usuarioAutenticado()`
Verifica se há usuário logado.

```php
if (usuarioAutenticado()) {
    // Usuário está logado
}
```

### `usuarioAdmin()`
Verifica se usuário é administrador.

```php
if (usuarioAdmin()) {
    // Mostrar opções de admin
}
```

### `exigirAutenticacao()`
Redireciona para login se não autenticado.

```php
exigirAutenticacao(); // Coloque no início da página
```

### `exigirAdmin()`
Redireciona para acesso_negado se não for admin.

```php
exigirAdmin(); // Coloque no início de páginas admin
```

### `obterUsuarioAtual()`
Obtém dados do usuário logado.

```php
$usuario = obterUsuarioAtual();
echo $usuario['nome'];
echo $usuario['email'];
echo $usuario['perfil']; // 'admin' ou 'usuario'
```

### `criarUsuario($nome, $email, $senha, $perfil)`
Cria novo usuário (use no painel admin).

```php
$resultado = criarUsuario('João', 'joao@email.com', '123456', 'usuario');
if ($resultado['sucesso']) {
    // Usuário criado com sucesso
}
```

### `listarUsuarios()`
Retorna lista de todos os usuários (apenas para Admin).

```php
$usuarios = listarUsuarios();
foreach ($usuarios as $usuario) {
    echo $usuario['nome'];
}
```

### `deletarUsuario($usuarioId)`
Deleta um usuário (apenas para Admin).

```php
$resultado = deletarUsuario($usuarioId);
```

---

## 🛠️ Como Integrar em Suas Páginas

### Exemplo: Proteger uma Página

```php
<?php
require_once __DIR__ . '/auth.php';
exigirAutenticacao(); // Redireciona para login se não autenticado

$usuarioAtual = obterUsuarioAtual();
?>

<!DOCTYPE html>
<html>
<body>
    <h1>Bem-vindo, <?php echo htmlspecialchars($usuarioAtual['nome']); ?>!</h1>
    
    <?php if (usuarioAdmin()): ?>
        <!-- Conteúdo apenas para Admin -->
        <a href="usuarios.php">Gerenciar Usuários</a>
    <?php endif; ?>
    
    <a href="logout.php">Logout</a>
</body>
</html>
```

### Exemplo: Página Admin-Only

```php
<?php
require_once __DIR__ . '/auth.php';
exigirAdmin(); // Redireciona se não for Admin
?>

<!-- Somente Admins chegam aqui -->
```

---

## 📊 Estrutura de Dados do Usuário

Cada usuário é armazenado em `dados/usuarios.json`:

```json
{
    "id": "507f1f77bcf86cd799439011",
    "nome": "João Silva",
    "email": "joao@email.com",
    "senha": "$2y$10$...", // Hash bcrypt (não é legível)
    "perfil": "admin", // "admin" ou "usuario"
    "ativo": true,
    "dataCriacao": "2024-01-15 10:30:00"
}
```

---

## ⚙️ Configuração Avançada

### Expiração de Sessão

Para configurar expiração de sessão, adicione ao `auth.php`:

```php
// Expirar sessão após 30 minutos de inatividade
$tempo_inativo = 1800; // 30 minutos

if (isset($_SESSION['ultimo_acesso'])) {
    if (time() - $_SESSION['ultimo_acesso'] > $tempo_inativo) {
        fazerLogout();
        header('Location: login.php?sessao_expirada=1');
        exit;
    }
}

$_SESSION['ultimo_acesso'] = time();
```

### Hash Mais Forte

Para aumentar a segurança do bcrypt:

```php
// No arquivo auth.php, altere:
password_hash($senha, PASSWORD_BCRYPT, ['cost' => 12]);
// Padrão usa cost = 10
```

---

## 🐛 Troubleshooting

### ❌ "Email ou senha incorretos"
- Verifique se o email está correto
- Verifique se digitou a senha corretamente
- Observe maiúsculas/minúsculas

### ❌ Não consegue acessar painel de usuários
- Verifique se você é Admin
- Faça logout e login novamente
- Verifique o perfil do usuário em `dados/usuarios.json`

### ❌ Sessão expira rapidamente
- Verifique configurações de PHP em `php.ini`
- Procure por `session.gc_maxlifetime`
- Aumente para `3600` (1 hora)

### ❌ "Erro ao criar usuário"
- Verifique se `dados/` tem permissão de escrita
- Verifique se email não está duplicado
- Verifique se a senha tem pelo menos 6 caracteres

---

## 🚀 Próximos Passos

### Sugestões de Melhoria

1. **Recuperação de Senha**
   - Implementar sistema de reset de senha por email

2. **Auditoria**
   - Registrar quem fez cada ação
   - Histórico de logins

3. **2FA (Autenticação em Duas Etapas)**
   - Adicionar código TOTP ou SMS

4. **Roles Granulares**
   - Criar perfis personalizados com permissões específicas

5. **OAuth/LDAP**
   - Integrar com Azure AD, Google, ou LDAP corporativo

---

**Pronto!** 🎉 Seu sistema agora possui segurança de nível profissional!

Dúvidas? Verifique `dados/usuarios.json` para verificar dados armazenados.
