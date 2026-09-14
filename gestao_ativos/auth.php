<?php
/**
 * SISTEMA DE AUTENTICAÇÃO
 * Gerencia login, sessões e proteção de páginas
 */

session_start();

// Arquivo de usuários
define('USUARIOS_FILE', __DIR__ . '/dados/usuarios.json');

// Criar diretório se não existir
if (!is_dir(__DIR__ . '/dados')) {
    mkdir(__DIR__ . '/dados', 0777, true);
}

// Criar arquivo de usuários se não existir
if (!file_exists(USUARIOS_FILE)) {
    $usuariosInicial = [
        [
            'id' => uniqid(),
            'nome' => 'Administrador',
            'email' => 'admin@gestao.local',
            'senha' => password_hash('admin123', PASSWORD_BCRYPT),
            'perfil' => 'admin',
            'ativo' => true,
            'dataCriacao' => date('Y-m-d H:i:s')
        ]
    ];
    file_put_contents(USUARIOS_FILE, json_encode($usuariosInicial, JSON_PRETTY_PRINT));
}

/**
 * Fazer login
 */
function fazerLogin($email, $senha) {
    $usuarios = json_decode(file_get_contents(USUARIOS_FILE), true);
    
    foreach ($usuarios as $usuario) {
        if ($usuario['email'] === $email && $usuario['ativo']) {
            if (password_verify($senha, $usuario['senha'])) {
                // Login bem-sucedido
                $_SESSION['usuario_id'] = $usuario['id'];
                $_SESSION['usuario_nome'] = $usuario['nome'];
                $_SESSION['usuario_email'] = $usuario['email'];
                $_SESSION['usuario_perfil'] = $usuario['perfil'];
                $_SESSION['login_time'] = time();
                
                return [
                    'sucesso' => true,
                    'mensagem' => 'Login realizado com sucesso'
                ];
            }
        }
    }
    
    return [
        'sucesso' => false,
        'erro' => 'Email ou senha incorretos'
    ];
}

/**
 * Fazer logout
 */
function fazerLogout() {
    $_SESSION = [];
    session_destroy();
}

/**
 * Verificar se usuário está autenticado
 */
function usuarioAutenticado() {
    return isset($_SESSION['usuario_id']) && !empty($_SESSION['usuario_id']);
}

/**
 * Verificar se usuário é admin
 */
function usuarioAdmin() {
    return usuarioAutenticado() && $_SESSION['usuario_perfil'] === 'admin';
}

/**
 * Redirecionar se não autenticado
 */
function exigirAutenticacao() {
    if (!usuarioAutenticado()) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Redirecionar se não é admin
 */
function exigirAdmin() {
    if (!usuarioAdmin()) {
        header('Location: acesso_negado.php');
        exit;
    }
}

/**
 * Obter usuário atual
 */
function obterUsuarioAtual() {
    if (!usuarioAutenticado()) {
        return null;
    }
    
    $usuarios = json_decode(file_get_contents(USUARIOS_FILE), true);
    foreach ($usuarios as $usuario) {
        if ($usuario['id'] === $_SESSION['usuario_id']) {
            return $usuario;
        }
    }
    return null;
}

/**
 * Criar novo usuário
 */
function criarUsuario($nome, $email, $senha, $perfil = 'usuario') {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return [
            'sucesso' => false,
            'erro' => 'Email inválido'
        ];
    }
    
    if (strlen($senha) < 6) {
        return [
            'sucesso' => false,
            'erro' => 'Senha deve ter no mínimo 6 caracteres'
        ];
    }
    
    $usuarios = json_decode(file_get_contents(USUARIOS_FILE), true);
    
    // Verificar se email já existe
    foreach ($usuarios as $usuario) {
        if ($usuario['email'] === $email) {
            return [
                'sucesso' => false,
                'erro' => 'Email já cadastrado'
            ];
        }
    }
    
    // Criar novo usuário
    $novoUsuario = [
        'id' => uniqid(),
        'nome' => $nome,
        'email' => $email,
        'senha' => password_hash($senha, PASSWORD_BCRYPT),
        'perfil' => $perfil,
        'ativo' => true,
        'dataCriacao' => date('Y-m-d H:i:s')
    ];
    
    $usuarios[] = $novoUsuario;
    
    if (file_put_contents(USUARIOS_FILE, json_encode($usuarios, JSON_PRETTY_PRINT))) {
        return [
            'sucesso' => true,
            'mensagem' => 'Usuário criado com sucesso',
            'usuario' => $novoUsuario
        ];
    }
    
    return [
        'sucesso' => false,
        'erro' => 'Erro ao criar usuário'
    ];
}

/**
 * Listar todos os usuários (apenas para admin)
 */
function listarUsuarios() {
    if (!usuarioAdmin()) {
        return [];
    }
    
    $usuarios = json_decode(file_get_contents(USUARIOS_FILE), true);
    
    // Remover senhas da lista
    foreach ($usuarios as &$usuario) {
        unset($usuario['senha']);
    }
    
    return $usuarios;
}

/**
 * Atualizar usuário
 */
function atualizarUsuario($usuarioId, $dados) {
    if (!usuarioAdmin() && $usuarioId !== $_SESSION['usuario_id']) {
        return [
            'sucesso' => false,
            'erro' => 'Sem permissão'
        ];
    }
    
    $usuarios = json_decode(file_get_contents(USUARIOS_FILE), true);
    
    foreach ($usuarios as &$usuario) {
        if ($usuario['id'] === $usuarioId) {
            if (isset($dados['nome'])) {
                $usuario['nome'] = $dados['nome'];
            }
            if (isset($dados['email'])) {
                $usuario['email'] = $dados['email'];
            }
            if (isset($dados['senha']) && !empty($dados['senha'])) {
                $usuario['senha'] = password_hash($dados['senha'], PASSWORD_BCRYPT);
            }
            if (isset($dados['perfil']) && usuarioAdmin()) {
                $usuario['perfil'] = $dados['perfil'];
            }
            if (isset($dados['ativo']) && usuarioAdmin()) {
                $usuario['ativo'] = $dados['ativo'];
            }
            
            break;
        }
    }
    
    if (file_put_contents(USUARIOS_FILE, json_encode($usuarios, JSON_PRETTY_PRINT))) {
        return [
            'sucesso' => true,
            'mensagem' => 'Usuário atualizado com sucesso'
        ];
    }
    
    return [
        'sucesso' => false,
        'erro' => 'Erro ao atualizar usuário'
    ];
}

/**
 * Deletar usuário
 */
function deletarUsuario($usuarioId) {
    if (!usuarioAdmin()) {
        return [
            'sucesso' => false,
            'erro' => 'Sem permissão'
        ];
    }
    
    if ($usuarioId === $_SESSION['usuario_id']) {
        return [
            'sucesso' => false,
            'erro' => 'Não é possível deletar sua própria conta'
        ];
    }
    
    $usuarios = json_decode(file_get_contents(USUARIOS_FILE), true);
    $usuarios = array_filter($usuarios, function($usuario) use ($usuarioId) {
        return $usuario['id'] !== $usuarioId;
    });
    
    if (file_put_contents(USUARIOS_FILE, json_encode(array_values($usuarios), JSON_PRETTY_PRINT))) {
        return [
            'sucesso' => true,
            'mensagem' => 'Usuário deletado com sucesso'
        ];
    }
    
    return [
        'sucesso' => false,
        'erro' => 'Erro ao deletar usuário'
    ];
}

?>
