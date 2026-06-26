<?php

declare(strict_types=1);

require_once __DIR__ . '/_admin.php';
requireAdmin();

$pdo = db();
$currentUserId = (int) (currentUser()['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf('admin_usuarios.php');

    $action = (string) ($_POST['action'] ?? 'salvar');

    if ($action === 'alternar_status') {
        $id = positiveInt($_POST['id'] ?? null);
        if (!$id) {
            setFlash('error', 'Usuário inválido.');
        } elseif ($id === $currentUserId) {
            setFlash('error', 'Para evitar perda de acesso, não é possível inativar o próprio usuário.');
        } else {
            $stmt = $pdo->prepare('UPDATE usuario SET ativo = IF(ativo = 1, 0, 1) WHERE id = ?');
            $stmt->execute([$id]);
            setFlash('success', 'Status do usuário atualizado.');
        }

        redirect('admin_usuarios.php');
    }

    $id = positiveInt($_POST['id'] ?? null);
    $nome = trim((string) ($_POST['nome'] ?? ''));
    $email = filter_var(trim((string) ($_POST['email'] ?? '')), FILTER_VALIDATE_EMAIL);
    $perfil = (string) ($_POST['perfil'] ?? 'usuario') === 'admin' ? 'admin' : 'usuario';
    $cargo = trim((string) ($_POST['cargo'] ?? ''));
    $telefone = trim((string) ($_POST['telefone'] ?? ''));
    $ativo = (string) ($_POST['ativo'] ?? '1') === '1' ? 1 : 0;
    $senha = (string) ($_POST['senha'] ?? '');
    $confirmarSenha = (string) ($_POST['confirmar_senha'] ?? '');

    $editingSelf = $id !== null && $id === $currentUserId;

    if ($nome === '' || strlen($nome) > 150 || $email === false || strlen((string) $email) > 150 || strlen($cargo) > 120 || strlen($telefone) > 30) {
        setFlash('error', 'Revise nome, e-mail, cargo e telefone antes de salvar.');
        redirect($id ? "admin_usuarios.php?editar={$id}" : 'admin_usuarios.php');
    }

    if (!$id && $senha === '') {
        setFlash('error', 'A senha é obrigatória ao cadastrar um usuário.');
        redirect('admin_usuarios.php');
    }

    if ($senha !== '' && $senha !== $confirmarSenha) {
        setFlash('error', 'A senha e a confirmação não conferem.');
        redirect($id ? "admin_usuarios.php?editar={$id}" : 'admin_usuarios.php');
    }

    if ($editingSelf && (!$ativo || $perfil !== 'admin')) {
        setFlash('error', 'Para evitar perda de acesso, mantenha seu próprio usuário ativo e como Admin.');
        redirect("admin_usuarios.php?editar={$id}");
    }

    $duplicate = $id
        ? $pdo->prepare('SELECT COUNT(*) FROM usuario WHERE email = ? AND id <> ?')
        : $pdo->prepare('SELECT COUNT(*) FROM usuario WHERE email = ?');
    $duplicate->execute($id ? [$email, $id] : [$email]);

    if ((int) $duplicate->fetchColumn() > 0) {
        setFlash('error', 'Este e-mail já está em uso por outro usuário.');
        redirect($id ? "admin_usuarios.php?editar={$id}" : 'admin_usuarios.php');
    }

    try {
        if ($id) {
            if ($senha !== '') {
                $stmt = $pdo->prepare('UPDATE usuario SET nome = ?, email = ?, senha = ?, perfil = ?, cargo = ?, telefone = ?, ativo = ? WHERE id = ?');
                $stmt->execute([$nome, $email, password_hash($senha, PASSWORD_DEFAULT), $perfil, $cargo ?: null, $telefone ?: null, $ativo, $id]);
            } else {
                $stmt = $pdo->prepare('UPDATE usuario SET nome = ?, email = ?, perfil = ?, cargo = ?, telefone = ?, ativo = ? WHERE id = ?');
                $stmt->execute([$nome, $email, $perfil, $cargo ?: null, $telefone ?: null, $ativo, $id]);
            }

            refreshCurrentAdminSession($id);
            setFlash('success', 'Usuário atualizado com sucesso.');
        } else {
            $stmt = $pdo->prepare('INSERT INTO usuario (nome, email, senha, perfil, cargo, telefone, ativo) VALUES (?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([$nome, $email, password_hash($senha, PASSWORD_DEFAULT), $perfil, $cargo ?: null, $telefone ?: null, $ativo]);
            setFlash('success', 'Usuário cadastrado com sucesso.');
        }
    } catch (PDOException $e) {
        setFlash('error', adminDuplicateMessage($e, 'Este e-mail já está em uso por outro usuário.'));
        redirect($id ? "admin_usuarios.php?editar={$id}" : 'admin_usuarios.php');
    }

    redirect('admin_usuarios.php');
}

$editingId = positiveInt($_GET['editar'] ?? null);
$editingUser = null;
if ($editingId) {
    $stmt = $pdo->prepare('SELECT id, nome, email, perfil, cargo, telefone, ativo FROM usuario WHERE id = ? LIMIT 1');
    $stmt->execute([$editingId]);
    $editingUser = $stmt->fetch();

    if (!$editingUser) {
        setFlash('error', 'Usuário não encontrado.');
        redirect('admin_usuarios.php');
    }
}

$users = $pdo->query('SELECT id, nome, email, perfil, cargo, telefone, ativo, created_at FROM usuario ORDER BY ativo DESC, nome')->fetchAll();

renderHeader('Usuários', 'admin');
?>
<header class="page-header">
    <div>
        <span class="eyebrow">Administração</span>
        <h1>Usuários</h1>
        <p>Cadastre, edite, ative/inative, altere perfis e redefina senhas.</p>
    </div>
    <a class="btn secondary" href="admin.php"><i class="fa-solid fa-arrow-left"></i> Administração</a>
</header>

<section class="panel">
    <div class="panel-header">
        <div>
            <h2><?= $editingUser ? 'Editar usuário' : 'Novo usuário' ?></h2>
            <p><?= $editingUser ? 'Preencha a senha somente se quiser redefini-la.' : 'A senha é obrigatória no cadastro.' ?></p>
        </div>
        <?php if ($editingUser): ?><a class="text-link" href="admin_usuarios.php">Cadastrar novo</a><?php endif; ?>
    </div>
    <form method="post" class="data-form">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
        <input type="hidden" name="action" value="salvar">
        <?php if ($editingUser): ?><input type="hidden" name="id" value="<?= (int) $editingUser['id'] ?>"><?php endif; ?>

        <label>Nome *
            <input name="nome" maxlength="150" value="<?= e($editingUser['nome'] ?? '') ?>" placeholder="Nome completo" required>
        </label>
        <label>E-mail *
            <input type="email" name="email" maxlength="150" value="<?= e($editingUser['email'] ?? '') ?>" placeholder="usuario@empresa.com" required>
        </label>
        <label>Perfil *
            <select name="perfil" required>
                <option value="usuario"<?= selected($editingUser['perfil'] ?? 'usuario', 'usuario') ?>>Usuário</option>
                <option value="admin"<?= selected($editingUser['perfil'] ?? 'usuario', 'admin') ?>>Admin</option>
            </select>
        </label>
        <label>Ativo *
            <select name="ativo" required>
                <option value="1"<?= selected((string) ($editingUser['ativo'] ?? '1'), '1') ?>>Ativo</option>
                <option value="0"<?= selected((string) ($editingUser['ativo'] ?? '1'), '0') ?>>Inativo</option>
            </select>
        </label>
        <label>Cargo
            <input name="cargo" maxlength="120" value="<?= e($editingUser['cargo'] ?? '') ?>" placeholder="Ex.: Analista de RH">
        </label>
        <label>Telefone
            <input name="telefone" maxlength="30" value="<?= e($editingUser['telefone'] ?? '') ?>" placeholder="(00) 00000-0000">
        </label>
        <label>Senha <?= $editingUser ? '' : '*' ?>
            <input type="password" name="senha" autocomplete="new-password"<?= $editingUser ? '' : ' required' ?> placeholder="<?= $editingUser ? 'Deixe em branco para manter' : 'Senha inicial' ?>">
        </label>
        <label>Confirmar senha <?= $editingUser ? '' : '*' ?>
            <input type="password" name="confirmar_senha" autocomplete="new-password"<?= $editingUser ? '' : ' required' ?> placeholder="Repita a senha">
        </label>

        <div class="form-actions full-row">
            <button class="btn primary" type="submit"><i class="fa-solid fa-floppy-disk"></i> Salvar usuário</button>
            <?php if ($editingUser): ?><a class="btn secondary" href="admin_usuarios.php">Cancelar edição</a><?php endif; ?>
        </div>
    </form>
</section>

<section class="panel">
    <div class="panel-header">
        <div>
            <h2>Usuários cadastrados</h2>
            <p>Inative usuários sem remover o histórico do sistema.</p>
        </div>
    </div>
    <?php if (!$users): ?>
        <div class="empty-state"><i class="fa-regular fa-user"></i><strong>Nenhum usuário cadastrado</strong></div>
    <?php else: ?>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr><th>Usuário</th><th>Perfil</th><th>Cargo / telefone</th><th>Status</th><th>Criado em</th><th>Ações</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><strong><?= e($user['nome']) ?></strong><br><small><?= e($user['email']) ?></small></td>
                            <td><?= adminProfileBadge($user['perfil']) ?></td>
                            <td><?= e($user['cargo'] ?: '-') ?><br><small><?= e($user['telefone'] ?: '-') ?></small></td>
                            <td><?= adminActiveBadge($user['ativo']) ?></td>
                            <td><?= e(formatDateTime($user['created_at'])) ?></td>
                            <td>
                                <div class="actions">
                                    <a class="btn secondary small" href="admin_usuarios.php?editar=<?= (int) $user['id'] ?>"><i class="fa-solid fa-pen"></i> Editar / senha</a>
                                    <form method="post" class="inline-form">
                                        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                        <input type="hidden" name="action" value="alternar_status">
                                        <input type="hidden" name="id" value="<?= (int) $user['id'] ?>">
                                        <button class="btn <?= (int) $user['ativo'] === 1 ? 'danger' : 'secondary' ?> small" type="submit" data-confirm="<?= (int) $user['ativo'] === 1 ? 'Inativar este usuário?' : 'Ativar este usuário?' ?>"<?= (int) $user['id'] === $currentUserId ? ' disabled' : '' ?>>
                                            <i class="fa-solid <?= (int) $user['ativo'] === 1 ? 'fa-ban' : 'fa-check' ?>"></i>
                                            <?= (int) $user['ativo'] === 1 ? 'Inativar' : 'Ativar' ?>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
<?php renderFooter(); ?>
