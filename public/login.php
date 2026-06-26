<?php

declare(strict_types=1);

require_once __DIR__ . '/_layout.php';

if (currentUser() !== null) {
    redirect('index.php');
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isValidCsrf()) {
        $error = 'Sua sessão expirou. Atualize a página e tente novamente.';
    } else {
        $email = trim((string) ($_POST['email'] ?? ''));
        $senha = (string) ($_POST['senha'] ?? '');

        $stmt = db()->prepare('SELECT id, nome, email, senha, perfil, tema, cor_tema FROM usuario WHERE email = ? AND ativo = 1 LIMIT 1');
        $stmt->execute([$email]);
        $usuario = $stmt->fetch();

        if ($usuario && password_verify($senha, $usuario['senha'])) {
            session_regenerate_id(true);
            storeUserInSession($usuario);
            redirect('index.php');
        }

        $error = 'E-mail ou senha inválidos.';
    }
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acesso | Controle de Treinamentos</title>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="css/app.css">
</head>
<body class="login-page theme-light">
<section class="login-card">
    <div class="login-brand">
        <span class="brand-icon"><i class="fa-solid fa-graduation-cap"></i></span>
        <p>Sistema de Controle de</p>
        <h1>Treinamentos Institucionais</h1>
    </div>
    <div class="login-copy">
        <h2>Boas-vindas</h2>
        <p>Entre para acompanhar os treinamentos da instituição.</p>
    </div>
    <?php if ($error): ?>
        <div class="alert error" role="alert"><?= e($error) ?></div>
    <?php endif; ?>
    <form method="post" class="login-form">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
        <label>
            E-mail
            <span class="input-icon"><i class="fa-regular fa-envelope"></i><input type="email" name="email" value="<?= e($email) ?>" required autofocus></span>
        </label>
        <label>
            Senha
            <span class="input-icon"><i class="fa-solid fa-lock"></i><input type="password" name="senha" required></span>
        </label>
        <button class="btn primary full-button" type="submit">Entrar <i class="fa-solid fa-arrow-right"></i></button>
    </form>
    <p class="login-hint">Admin: <strong>admin@admin.com</strong> / <strong>admin</strong><br>Usuário: <strong>usuario@usuario.com</strong> / <strong>usuario</strong></p>
</section>
</body>
</html>
