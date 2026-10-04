<?php

require_once 'auth.php';

if (usuarioAutenticado()) {
    header('Location: dashboard.php');
    exit;
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $resultado = fazerLogin(
        $_POST['email'],
        $_POST['senha']
    );

    if ($resultado['sucesso']) {
        header('Location: dashboard.php');
        exit;
    }

    $erro = $resultado['erro'];
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Gestão TI - Login</title>

<style>

:root{
    --sidebar:#0f172a;
    --primary:#2563eb;
    --background:#f0f4f8;
}

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

body{
    min-height:100vh;
    display:flex;
    justify-content:center;
    align-items:center;
    background:linear-gradient(135deg,#f0f4f8,#e9f0f7);
    font-family:'Segoe UI',sans-serif;
}

.login-card{
    width:100%;
    max-width:420px;
    background:white;
    padding:40px;
    border-radius:20px;
    box-shadow:0 10px 30px rgba(0,0,0,.1);
}

.logo{
    text-align:center;
    font-size:32px;
    margin-bottom:10px;
}

h1{
    text-align:center;
    color:#0f172a;
    margin-bottom:30px;
}

input{
    width:100%;
    padding:14px;
    margin-bottom:15px;
    border:2px solid rgba(37,99,235,.2);
    border-radius:10px;
}

input:focus{
    outline:none;
    border-color:#2563eb;
}

button{
    width:100%;
    padding:14px;
    border:none;
    border-radius:10px;
    color:white;
    background:linear-gradient(135deg,#2563eb,#1d4ed8);
    cursor:pointer;
    font-weight:600;
}

.erro{
    background:#fee2e2;
    color:#dc2626;
    padding:10px;
    border-radius:8px;
    margin-bottom:15px;
}

</style>
</head>

<body>

<div class="login-card">

    <div class="logo">📡</div>

    <h1>Gestão TI</h1>

    <?php if(!empty($erro)): ?>
        <div class="erro">
            <?= htmlspecialchars($erro) ?>
        </div>
    <?php endif; ?>

    <form method="POST">

        <input
            type="email"
            name="email"
            placeholder="E-mail"
            required
        >

        <input
            type="password"
            name="senha"
            placeholder="Senha"
            required
        >

        <button type="submit">
            Entrar
        </button>

    </form>

</div>

</body>
</html>