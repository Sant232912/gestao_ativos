<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Usuários</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .usuarios-container {
            max-width: 1000px;
            margin-left: 280px;
            padding: 30px;
        }

        .usuarios-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .usuarios-header h1 {
            margin: 0;
            color: #1a365d;
        }

        .btn-novo {
            background: linear-gradient(135deg, #16a34a 0%, #15803d 100%);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-novo:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(22, 163, 74, 0.3);
        }

        .usuarios-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .usuarios-table thead {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .usuarios-table th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
        }

        .usuarios-table td {
            padding: 15px;
            border-top: 1px solid #e2e8f0;
        }

        .usuarios-table tbody tr:hover {
            background: #f7fafc;
        }

        .badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-admin {
            background: #fef3c7;
            color: #92400e;
        }

        .badge-usuario {
            background: #dbeafe;
            color: #0c4a6e;
        }

        .badge-ativo {
            background: #dcfce7;
            color: #166534;
        }

        .badge-inativo {
            background: #fee2e2;
            color: #991b1b;
        }

        .acoes {
            display: flex;
            gap: 8px;
        }

        .btn-sm {
            padding: 6px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-edit {
            background: #3b82f6;
            color: white;
        }

        .btn-edit:hover {
            background: #2563eb;
        }

        .btn-delete {
            background: #ef4444;
            color: white;
        }

        .btn-delete:hover {
            background: #dc2626;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 2000;
            align-items: center;
            justify-content: center;
        }

        .modal.ativo {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: 15px;
            padding: 30px;
            max-width: 500px;
            width: 95%;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: slideInUp 0.3s ease;
        }

        .modal-header {
            font-size: 20px;
            font-weight: 600;
            color: #1a365d;
            margin-bottom: 20px;
        }

        .modal-buttons {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 20px;
        }

        .btn-cancelar {
            background: #e2e8f0;
            color: #2d3748;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-cancelar:hover {
            background: #cbd5e0;
        }

        .btn-salvar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-salvar:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }

        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 768px) {
            .usuarios-container {
                margin-left: 0;
                padding: 20px;
            }

            .usuarios-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo">📡 Gestão TI</div>
        <div class="menu">
            <a href="dashboard.php">📊 Dashboard</a>
            <a href="colaboradores.php">👥 Colaboradores</a>
            <a href="ativos.php">💻 Ativos</a>
            <a href="estoque.php">📦 Estoque</a>
            <a href="chamados.php">🎫 Chamados</a>
            <a href="relatorios.php">📈 Relatórios</a>
            <a href="configuracoes.php">⚙ Configurações</a>
            <a href="usuarios.php" class="active">👤 Usuários</a>
            <hr style="border: 1px solid rgba(255,255,255,0.1); margin: 20px 0;">
            <a href="logout.php" style="color: #dc2626;">🚪 Logout</a>
        </div>
    </div>

    <div class="usuarios-container">
        <?php
        require_once __DIR__ . '/auth.php';
        
        // Exigir admin
        exigirAdmin();

        $usuarioAtual = obterUsuarioAtual();

        // Processar ações
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $acao = $_POST['acao'] ?? '';

            if ($acao === 'criar') {
                $resultado = criarUsuario(
                    $_POST['nome'] ?? '',
                    $_POST['email'] ?? '',
                    $_POST['senha'] ?? '',
                    $_POST['perfil'] ?? 'usuario'
                );
            } elseif ($acao === 'deletar') {
                $resultado = deletarUsuario($_POST['usuario_id'] ?? '');
            }
        }
        ?>

        <div class="usuarios-header">
            <h1>👤 Gerenciar Usuários</h1>
            <button class="btn-novo" onclick="abrirModalNovo()">+ Novo Usuário</button>
        </div>

        <?php if (isset($resultado)): ?>
            <div style="background: <?php echo $resultado['sucesso'] ? '#dcfce7' : '#fee2e2'; ?>; color: <?php echo $resultado['sucesso'] ? '#166534' : '#991b1b'; ?>; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid <?php echo $resultado['sucesso'] ? '#16a34a' : '#dc2626'; ?>;">
                <?php echo htmlspecialchars($resultado['mensagem'] ?? $resultado['erro']); ?>
            </div>
        <?php endif; ?>

        <table class="usuarios-table">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Email</th>
                    <th>Perfil</th>
                    <th>Status</th>
                    <th>Data Criação</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (listarUsuarios() as $usuario): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($usuario['nome']); ?></td>
                        <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                        <td>
                            <span class="badge <?php echo $usuario['perfil'] === 'admin' ? 'badge-admin' : 'badge-usuario'; ?>">
                                <?php echo $usuario['perfil'] === 'admin' ? '👑 Admin' : '👤 Usuário'; ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge <?php echo $usuario['ativo'] ? 'badge-ativo' : 'badge-inativo'; ?>">
                                <?php echo $usuario['ativo'] ? '✅ Ativo' : '❌ Inativo'; ?>
                            </span>
                        </td>
                        <td><?php echo date('d/m/Y H:i', strtotime($usuario['dataCriacao'])); ?></td>
                        <td>
                            <div class="acoes">
                                <button class="btn-sm btn-edit" onclick="editarUsuario('<?php echo htmlspecialchars($usuario['id']); ?>', '<?php echo htmlspecialchars($usuario['nome']); ?>', '<?php echo htmlspecialchars($usuario['email']); ?>', '<?php echo htmlspecialchars($usuario['perfil']); ?>')">✏️ Editar</button>
                                <?php if ($usuario['id'] !== $usuarioAtual['id']): ?>
                                    <button class="btn-sm btn-delete" onclick="confirmarDelecao('<?php echo htmlspecialchars($usuario['id']); ?>', '<?php echo htmlspecialchars($usuario['nome']); ?>')">🗑️ Deletar</button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Modal Novo Usuário -->
    <div id="modalNovo" class="modal">
        <div class="modal-content">
            <div class="modal-header">➕ Novo Usuário</div>
            <form method="POST">
                <input type="hidden" name="acao" value="criar">
                
                <div class="form-group">
                    <label>Nome Completo:</label>
                    <input type="text" name="nome" required>
                </div>
                
                <div class="form-group">
                    <label>Email:</label>
                    <input type="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label>Senha:</label>
                    <input type="password" name="senha" required minlength="6">
                </div>
                
                <div class="form-group">
                    <label>Perfil:</label>
                    <select name="perfil" required style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 6px;">
                        <option value="usuario">👤 Usuário</option>
                        <option value="admin">👑 Administrador</option>
                    </select>
                </div>
                
                <div class="modal-buttons">
                    <button type="button" class="btn-cancelar" onclick="fecharModal('modalNovo')">Cancelar</button>
                    <button type="submit" class="btn-salvar">Criar Usuário</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Deletar -->
    <div id="modalDeletar" class="modal">
        <div class="modal-content">
            <div class="modal-header">🗑️ Confirmar Exclusão</div>
            <p>Tem certeza que deseja deletar o usuário <strong id="nomeDeletar"></strong>?</p>
            <p style="color: #dc2626; font-size: 12px;">⚠️ Esta ação não pode ser desfeita.</p>
            
            <form method="POST" id="formDeletar">
                <input type="hidden" name="acao" value="deletar">
                <input type="hidden" name="usuario_id" id="usuarioDeletarId">
                
                <div class="modal-buttons">
                    <button type="button" class="btn-cancelar" onclick="fecharModal('modalDeletar')">Cancelar</button>
                    <button type="submit" class="btn-delete">Deletar Usuário</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function abrirModalNovo() {
            document.getElementById('modalNovo').classList.add('ativo');
        }

        function fecharModal(id) {
            document.getElementById(id).classList.remove('ativo');
        }

        function confirmarDelecao(usuarioId, nome) {
            document.getElementById('nomeDeletar').textContent = nome;
            document.getElementById('usuarioDeletarId').value = usuarioId;
            document.getElementById('modalDeletar').classList.add('ativo');
        }

        function editarUsuario(id, nome, email, perfil) {
            alert('Edição de usuário ainda em desenvolvimento.\nNome: ' + nome + '\nEmail: ' + email);
        }

        // Fechar modal ao clicar fora
        document.addEventListener('click', function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.classList.remove('ativo');
            }
        });
    </script>
</body>
</html>
