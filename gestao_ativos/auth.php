<?php
session_start([
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax',
    'use_strict_mode' => true,
    'cookie_secure' => (!empty($_SERVER['HTTPS']) || (isset($_SERVER['SERVER_PORT']) && (string) $_SERVER['SERVER_PORT'] === '443'))
]);

define('USUARIOS_FILE', __DIR__ . '/dados/usuarios.json');

if (!is_dir(__DIR__ . '/dados')) {
    mkdir(__DIR__ . '/dados', 0775, true);
}

if (!file_exists(USUARIOS_FILE)) {
    $usuariosInicial = [[
        'id' => uniqid('', true),
        'nome' => 'Administrador',
        'email' => 'admin@gestao.local',
        'senha' => password_hash('admin123', PASSWORD_BCRYPT),
        'perfil' => 'admin',
        'ativo' => true,
        'dataCriacao' => date('Y-m-d H:i:s')
    ]];
    file_put_contents(USUARIOS_FILE, json_encode($usuariosInicial, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function carregarUsuarios(): array {
    if (!is_file(USUARIOS_FILE)) {
        return [];
    }

    $conteudo = @file_get_contents(USUARIOS_FILE);
    if ($conteudo === false || trim($conteudo) === '') {
        return [];
    }

    $dados = json_decode($conteudo, true);
    return is_array($dados) ? $dados : [];
}

function salvarUsuarios(array $usuarios): bool {
    $diretorio = dirname(USUARIOS_FILE);
    if (!is_dir($diretorio)) {
        mkdir($diretorio, 0775, true);
    }

    return (bool) file_put_contents(
        USUARIOS_FILE,
        json_encode($usuarios, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
        LOCK_EX
    );
}

function fazerLogin(string $email, string $senha): array {
    $emailNormalizado = strtolower(trim($email));
    $usuarios = carregarUsuarios();

    foreach ($usuarios as $usuario) {
        if (!isset($usuario['email'], $usuario['senha'], $usuario['ativo'])) {
            continue;
        }

        $emailUsuario = strtolower(trim((string) $usuario['email']));
        if ($emailUsuario === $emailNormalizado && (bool) $usuario['ativo']) {
            if (password_verify($senha, (string) $usuario['senha'])) {
                session_regenerate_id(true);
                $_SESSION['usuario_id'] = (string) $usuario['id'];
                $_SESSION['usuario_nome'] = (string) $usuario['nome'];
                $_SESSION['usuario_email'] = $emailUsuario;
                $_SESSION['usuario_perfil'] = (string) ($usuario['perfil'] ?? 'usuario');
                $_SESSION['login_time'] = time();

                return ['sucesso' => true, 'mensagem' => 'Login realizado com sucesso'];
            }
        }
    }

    return ['sucesso' => false, 'erro' => 'Email ou senha incorretos'];
}

function fazerLogout() {
    $_SESSION = [];
    if (session_id() !== '') {
        session_destroy();
    }
}

function usuarioAutenticado(): bool {
    return isset($_SESSION['usuario_id']) && !empty($_SESSION['usuario_id']);
}

function usuarioAdmin(): bool {
    return usuarioAutenticado() && ($_SESSION['usuario_perfil'] ?? '') === 'admin';
}

function exigirAutenticacao() {
    if (!usuarioAutenticado()) {
        header('Location: login.php');
        exit;
    }
}

function exigirAdmin() {
    if (!usuarioAdmin()) {
        header('Location: acesso_negado.php');
        exit;
    }
}

function obterUsuarioAtual() {
    if (!usuarioAutenticado()) {
        return null;
    }

    $usuarios = carregarUsuarios();
    foreach ($usuarios as $usuario) {
        if (($usuario['id'] ?? null) === $_SESSION['usuario_id']) {
            return $usuario;
        }
    }

    return null;
}

function criarUsuario(string $nome, string $email, string $senha, string $perfil = 'usuario'): array {
    $nome = trim($nome);
    $email = strtolower(trim($email));

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['sucesso' => false, 'erro' => 'Email inválido'];
    }

    if (strlen($senha) < 6) {
        return ['sucesso' => false, 'erro' => 'Senha deve ter no mínimo 6 caracteres'];
    }

    $usuarios = carregarUsuarios();
    foreach ($usuarios as $usuario) {
        if (strtolower(trim((string) ($usuario['email'] ?? ''))) === $email) {
            return ['sucesso' => false, 'erro' => 'Email já cadastrado'];
        }
    }

    $novoUsuario = [
        'id' => uniqid('', true),
        'nome' => $nome,
        'email' => $email,
        'senha' => password_hash($senha, PASSWORD_BCRYPT),
        'perfil' => $perfil,
        'ativo' => true,
        'dataCriacao' => date('Y-m-d H:i:s')
    ];

    $usuarios[] = $novoUsuario;

    if (salvarUsuarios($usuarios)) {
        return ['sucesso' => true, 'mensagem' => 'Usuário criado com sucesso', 'usuario' => $novoUsuario];
    }

    return ['sucesso' => false, 'erro' => 'Erro ao criar usuário'];
}

function listarUsuarios(): array {
    if (!usuarioAdmin()) {
        return [];
    }

    $usuarios = carregarUsuarios();
    foreach ($usuarios as &$usuario) {
        unset($usuario['senha']);
    }
    unset($usuario);

    return $usuarios;
}

function atualizarUsuario($usuarioId, $dados): array {
    if (!usuarioAdmin() && ($usuarioId !== ($_SESSION['usuario_id'] ?? null))) {
        return ['sucesso' => false, 'erro' => 'Sem permissão'];
    }

    $usuarios = carregarUsuarios();

    foreach ($usuarios as &$usuario) {
        if (($usuario['id'] ?? null) === $usuarioId) {
            if (isset($dados['nome'])) {
                $usuario['nome'] = trim((string) $dados['nome']);
            }
            if (isset($dados['email'])) {
                $usuario['email'] = strtolower(trim((string) $dados['email']));
            }
            if (isset($dados['senha']) && !empty($dados['senha'])) {
                $usuario['senha'] = password_hash((string) $dados['senha'], PASSWORD_BCRYPT);
            }
            if (isset($dados['perfil']) && usuarioAdmin()) {
                $usuario['perfil'] = (string) $dados['perfil'];
            }
            if (isset($dados['ativo']) && usuarioAdmin()) {
                $usuario['ativo'] = (bool) $dados['ativo'];
            }
            break;
        }
    }
    unset($usuario);

    if (salvarUsuarios($usuarios)) {
        return ['sucesso' => true, 'mensagem' => 'Usuário atualizado com sucesso'];
    }

    return ['sucesso' => false, 'erro' => 'Erro ao atualizar usuário'];
}

function deletarUsuario($usuarioId): array {
    if (!usuarioAdmin()) {
        return ['sucesso' => false, 'erro' => 'Sem permissão'];
    }

    if (($usuarioId ?? null) === ($_SESSION['usuario_id'] ?? null)) {
        return ['sucesso' => false, 'erro' => 'Não é possível deletar sua própria conta'];
    }

    $usuarios = carregarUsuarios();
    $usuarios = array_values(array_filter($usuarios, function ($usuario) use ($usuarioId) {
        return ($usuario['id'] ?? null) !== $usuarioId;
    }));

    if (salvarUsuarios($usuarios)) {
        return ['sucesso' => true, 'mensagem' => 'Usuário deletado com sucesso'];
    }

    return ['sucesso' => false, 'erro' => 'Erro ao deletar usuário'];
}
