<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/auth.php';

if (!usuarioAutenticado()) {
    responderErroTarefas('Sua sessão expirou. Faça login novamente.', 401);
}

$arquivoTarefas = __DIR__ . '/dados/tarefas.json';
$usuarioId = (string) $_SESSION['usuario_id'];
$metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$acao = $_GET['acao'] ?? '';
$estados = carregarEstadosTarefas();
$estado = $estados[$usuarioId] ?? estadoTarefasPadrao();

if ($metodo === 'GET' && $acao === 'listar') {
    echo json_encode($estado, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($metodo === 'POST' && $acao === 'migrar') {
    $entrada = json_decode(file_get_contents('php://input'), true);
    if (!is_array($entrada)) {
        responderErroTarefas('Dados de migração inválidos.', 400);
    }

    $tarefasPorId = [];
    foreach (array_merge(
        normalizarTarefas($estado['tarefas'] ?? []),
        normalizarTarefas($entrada['tarefas'] ?? [])
    ) as $tarefa) {
        $tarefasPorId[$tarefa['id']] = $tarefa;
    }
    $estado['tarefas'] = array_values($tarefasPorId);
    if (isset($entrada['lembrete']) && is_array($entrada['lembrete'])) {
        $estado['lembrete'] = normalizarLembrete($entrada['lembrete']);
    }
    $estado['migracaoLocalConcluida'] = true;
    $estados[$usuarioId] = $estado;

    if (!salvarEstadosTarefas($estados)) {
        responderErroTarefas('Não foi possível salvar as tarefas no servidor.', 500);
    }

    echo json_encode($estado, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($metodo === 'PUT' && $acao === 'salvar') {
    $entrada = json_decode(file_get_contents('php://input'), true);
    if (!is_array($entrada) || !isset($entrada['tarefas']) || !is_array($entrada['tarefas'])) {
        responderErroTarefas('Dados das tarefas inválidos.', 400);
    }

    $estado['tarefas'] = normalizarTarefas($entrada['tarefas']);
    $estado['lembrete'] = normalizarLembrete($entrada['lembrete'] ?? []);
    $estado['migracaoLocalConcluida'] = true;
    $estados[$usuarioId] = $estado;

    if (!salvarEstadosTarefas($estados)) {
        responderErroTarefas('Não foi possível salvar as tarefas no servidor.', 500);
    }

    echo json_encode($estado, JSON_UNESCAPED_UNICODE);
    exit;
}

responderErroTarefas('Ação não reconhecida.', 400);

function responderErroTarefas(string $mensagem, int $status): void {
    http_response_code($status);
    echo json_encode(['erro' => $mensagem], JSON_UNESCAPED_UNICODE);
    exit;
}

function estadoTarefasPadrao(): array {
    return [
        'tarefas' => [],
        'lembrete' => ['ativo' => false, 'proximoAlerta' => null],
        'migracaoLocalConcluida' => false
    ];
}

function carregarEstadosTarefas(): array {
    global $arquivoTarefas;
    if (!is_file($arquivoTarefas)) {
        return [];
    }

    $conteudo = @file_get_contents($arquivoTarefas);
    $estados = $conteudo === false ? null : json_decode($conteudo, true);
    return is_array($estados) ? $estados : [];
}

function salvarEstadosTarefas(array $estados): bool {
    global $arquivoTarefas;
    $conteudo = json_encode($estados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    return $conteudo !== false && file_put_contents($arquivoTarefas, $conteudo, LOCK_EX) !== false;
}

function normalizarTarefas($tarefas): array {
    if (!is_array($tarefas)) {
        return [];
    }

    $resultado = [];
    foreach ($tarefas as $tarefa) {
        if (!is_array($tarefa)) {
            continue;
        }

        $id = trim((string) ($tarefa['id'] ?? ''));
        $titulo = trim((string) ($tarefa['titulo'] ?? ''));
        if ($id === '' || $titulo === '') {
            continue;
        }

        $resultado[] = [
            'id' => $id,
            'titulo' => $titulo,
            'detalhes' => trim((string) ($tarefa['detalhes'] ?? '')),
            'concluida' => (bool) ($tarefa['concluida'] ?? false),
            'criadaEm' => (string) ($tarefa['criadaEm'] ?? date('c'))
        ];
    }

    return $resultado;
}

function normalizarLembrete($lembrete): array {
    if (!is_array($lembrete)) {
        $lembrete = [];
    }

    $proximoAlerta = $lembrete['proximoAlerta'] ?? null;
    return [
        'ativo' => (bool) ($lembrete['ativo'] ?? false),
        'proximoAlerta' => is_numeric($proximoAlerta) ? (int) $proximoAlerta : null
    ];
}