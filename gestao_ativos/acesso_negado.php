<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acesso Negado</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .acesso-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 50px 40px;
            width: 100%;
            max-width: 500px;
            text-align: center;
            animation: slideInUp 0.5s ease;
        }

        .icone {
            font-size: 64px;
            margin-bottom: 20px;
        }

        h1 {
            color: #1a365d;
            font-size: 32px;
            margin: 0 0 15px 0;
        }

        p {
            color: #718096;
            font-size: 16px;
            line-height: 1.6;
            margin: 0 0 30px 0;
        }

        .btn-group {
            display: flex;
            gap: 10px;
            justify-content: center;
        }

        a {
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: #e2e8f0;
            color: #2d3748;
        }

        .btn-secondary:hover {
            background: #cbd5e0;
        }

        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 600px) {
            .acesso-container {
                padding: 30px 20px;
            }

            h1 {
                font-size: 24px;
            }

            .btn-group {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="acesso-container">
        <div class="icone">🚫</div>
        <h1>Acesso Negado</h1>
        <p>Você não tem permissão para acessar esta página. Este recurso é restrito a administradores.</p>
        
        <div class="btn-group">
            <a href="dashboard.php" class="btn-primary">← Voltar ao Dashboard</a>
            <a href="logout.php" class="btn-secondary">Sair</a>
        </div>
    </div>
</body>
</html>
