<?php
header('Content-Type: application/json');

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/github_sync.php';
exigirAutenticacao();

$arquivo = __DIR__ . '/dados/chamados.json';

// Criar diretório se não existir
if (!is_dir(__DIR__ . '/dados')) {
    mkdir(__DIR__ . '/dados', 0777, true);
}

// Garantir que o arquivo existe
if (!file_exists($arquivo)) {
    file_put_contents($arquivo, json_encode([]));
}

$metodo = $_SERVER['REQUEST_METHOD'];
$acao = $_GET['acao'] ?? '';

if ($metodo === 'OPTIONS') {
    http_response_code(204);
    exit;
}

switch ($metodo) {
    case 'GET':
        if ($acao === 'listar') {
            listarChamados();
        } else {
            responderErro('Ação não reconhecida', 400);
        }
        break;
    
    case 'POST':
        if ($acao === 'adicionar') {
            adicionarChamado();
        } elseif ($acao === 'importar') {
            importarChamados();
        } else {
            responderErro('Ação não reconhecida', 400);
        }
        break;
    
    case 'PUT':
        if ($acao === 'atualizar') {
            atualizarChamado();
        } else {
            responderErro('Ação não reconhecida', 400);
        }
        break;
    
    case 'DELETE':
        if ($acao === 'deletar') {
            deletarChamado();
        } else {
            responderErro('Ação não reconhecida', 400);
        }
        break;
    
    default:
        http_response_code(400);
        echo json_encode(['erro' => 'Ação não reconhecida']);
}

function responderErro(string $mensagem, int $status): void {
    http_response_code($status);
    echo json_encode(['erro' => $mensagem], JSON_UNESCAPED_UNICODE);
    exit;
}

function listarChamados() {
    global $arquivo;
    $chamados = json_decode(file_get_contents($arquivo), true);
    echo json_encode($chamados ?? []);
}

function adicionarChamado() {
    global $arquivo;
    $dados = json_decode(file_get_contents('php://input'), true);
    if (!is_array($dados)) {
        responderErro('JSON inválido', 400);
    }
    
    if (empty($dados['numeroChamado']) || empty($dados['solicitante'])) {
        http_response_code(400);
        echo json_encode(['erro' => 'Dados incompletos']);
        return;
    }
    
    $chamados = json_decode(file_get_contents($arquivo), true) ?? [];
    $dados['id'] = uniqid();
    $chamados[] = $dados;
    
    if (file_put_contents($arquivo, json_encode($chamados, JSON_PRETTY_PRINT))) {
        // Sincronizar com GitHub
        sincronizarComGitHub("Novo chamado adicionado: {$dados['numeroChamado']}", 'chamados');
        
        echo json_encode(['sucesso' => true, 'mensagem' => 'Chamado adicionado']);
    } else {
        http_response_code(500);
        echo json_encode(['erro' => 'Erro ao salvar']);
    }
}

function importarChamados() {
    global $arquivo;
    $dados = json_decode(file_get_contents('php://input'), true);
    
    if (empty($dados) || !is_array($dados)) {
        http_response_code(400);
        echo json_encode(['erro' => 'Dados inválidos']);
        return;
    }
    
    $chamados = json_decode(file_get_contents($arquivo), true) ?? [];
    $totalAdicionado = 0;
    
    foreach ($dados as $chamado) {
        if (empty($chamado['numeroChamado']) || empty($chamado['solicitante'])) {
            continue;
        }
        $chamado['id'] = uniqid();
        $chamados[] = $chamado;
        $totalAdicionado++;
    }
    
    if (file_put_contents($arquivo, json_encode($chamados, JSON_PRETTY_PRINT))) {
        // Sincronizar com GitHub
        sincronizarComGitHub("Importação em massa: $totalAdicionado chamados adicionados", 'chamados');
        
        echo json_encode(['sucesso' => true, 'total' => $totalAdicionado, 'mensagem' => $totalAdicionado . ' chamados importados']);
    } else {
        http_response_code(500);
        echo json_encode(['erro' => 'Erro ao salvar']);
    }
}

function atualizarChamado() {
    global $arquivo;
    $dados = json_decode(file_get_contents('php://input'), true);
    if (empty($dados['id'])) {
        http_response_code(400);
        echo json_encode(['erro' => 'ID não fornecido']);
        return;
    }
    
    $chamados = json_decode(file_get_contents($arquivo), true) ?? [];
    
    $encontrado = false;
    foreach ($chamados as &$chamado) {
        if ($chamado['id'] === $dados['id']) {
            $chamado = array_merge($chamado, $dados);
            $encontrado = true;
            break;
        }
    }
    unset($chamado);

    if (!$encontrado) {
        responderErro('Chamado não encontrado', 404);
    }
    
    if (file_put_contents($arquivo, json_encode($chamados, JSON_PRETTY_PRINT))) {
        // Sincronizar com GitHub
        sincronizarComGitHub("Chamado atualizado: " . ($dados['numeroChamado'] ?? $dados['id']), 'chamados');
        
        echo json_encode(['sucesso' => true, 'mensagem' => 'Chamado atualizado']);
    } else {
        http_response_code(500);
        echo json_encode(['erro' => 'Erro ao salvar']);
    }
}

function deletarChamado() {
    global $arquivo;
    $dados = json_decode(file_get_contents('php://input'), true);
    
    if (empty($dados['id'])) {
        http_response_code(400);
        echo json_encode(['erro' => 'ID não fornecido']);
        return;
    }
    
    $chamados = json_decode(file_get_contents($arquivo), true) ?? [];
    $quantidadeAntes = count($chamados);
    $chamados = array_filter($chamados, function($chamado) use ($dados) {
        return $chamado['id'] !== $dados['id'];
    });
    if (count($chamados) === $quantidadeAntes) {
        responderErro('Chamado não encontrado', 404);
    }
    
    if (file_put_contents($arquivo, json_encode(array_values($chamados), JSON_PRETTY_PRINT))) {
        // Sincronizar com GitHub
        sincronizarComGitHub("Chamado deletado: {$dados['id']}", 'chamados');
        
        echo json_encode(['sucesso' => true, 'mensagem' => 'Chamado deletado']);
    } else {
        http_response_code(500);
        echo json_encode(['erro' => 'Erro ao salvar']);
    }
}
?>
