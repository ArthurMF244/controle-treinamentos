<?php

declare(strict_types=1);

require_once __DIR__ . '/_layout.php';
requireLogin();

function saveProfilePhoto(array $file): ?string
{
    $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);

    if ($error === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($error !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Não foi possível enviar a foto. Tente novamente.');
    }

    $size = (int) ($file['size'] ?? 0);
    if ($size < 1 || $size > 2 * 1024 * 1024) {
        throw new InvalidArgumentException('A foto deve ter no máximo 2 MB.');
    }

    $originalName = (string) ($file['name'] ?? '');
    $originalExtension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
    if (!in_array($originalExtension, $allowedExtensions, true)) {
        throw new InvalidArgumentException('Envie uma imagem JPG, JPEG, PNG ou WEBP.');
    }

    $temporaryFile = (string) ($file['tmp_name'] ?? '');
    if ($temporaryFile === '' || !is_uploaded_file($temporaryFile)) {
        throw new RuntimeException('Arquivo de foto inválido.');
    }

    $mimeType = (new finfo(FILEINFO_MIME_TYPE))->file($temporaryFile);
    $extensionsByMime = [
        'image/jpeg' => ['jpg', 'jpeg'],
        'image/png' => ['png'],
        'image/webp' => ['webp'],
    ];
    if (!isset($extensionsByMime[$mimeType]) || !in_array($originalExtension, $extensionsByMime[$mimeType], true)) {
        throw new InvalidArgumentException('A extensão da imagem não combina com o tipo do arquivo.');
    }

    $directory = __DIR__ . '/uploads/perfis';
    if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
        throw new RuntimeException('Não foi possível preparar a pasta de fotos.');
    }

    $savedExtension = $mimeType === 'image/jpeg' ? 'jpg' : $originalExtension;
    $filename = 'perfil-' . bin2hex(random_bytes(16)) . '.' . $savedExtension;
    $destination = $directory . '/' . $filename;
    if (!move_uploaded_file($temporaryFile, $destination)) {
        throw new RuntimeException('Não foi possível salvar a foto enviada.');
    }

    return 'uploads/perfis/' . $filename;
}

function deleteProfilePhoto(?string $photo): void
{
    $photo = profilePhotoPath($photo);
    if ($photo === null) {
        return;
    }

    $file = __DIR__ . '/' . $photo;
    if (is_file($file)) {
        unlink($file);
    }
}

function refreshProfileSession(int $userId): void
{
    $fresh = db()->prepare('SELECT id, nome, email, perfil, tema, cor_tema, foto, cargo, telefone FROM usuario WHERE id = ? AND ativo = 1 LIMIT 1');
    $fresh->execute([$userId]);
    if ($updatedUser = $fresh->fetch()) {
        storeUserInSession($updatedUser);
    }
}

$user = currentUser() ?? [];
$userId = (int) ($user['id'] ?? 0);
$newPhoto = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf('perfil.php');

    $action = (string) ($_POST['action'] ?? 'save_profile');

    if ($action === 'remove_photo') {
        try {
            $currentPhoto = profilePhotoPath($user['foto'] ?? null);

            $stmt = db()->prepare('UPDATE usuario SET foto = NULL WHERE id = ? AND ativo = 1');
            $stmt->execute([$userId]);

            if ($currentPhoto !== null) {
                deleteProfilePhoto($currentPhoto);
            }

            refreshProfileSession($userId);
            setFlash('success', 'Foto removida. O avatar padrão foi restaurado.');
        } catch (Throwable $e) {
            setFlash('error', 'Não foi possível remover a foto agora.');
        }

        redirect('perfil.php');
    }

    $nome = trim((string) ($_POST['nome'] ?? ''));
    $email = filter_var(trim((string) ($_POST['email'] ?? '')), FILTER_VALIDATE_EMAIL);
    $cargo = trim((string) ($_POST['cargo'] ?? ''));
    $telefone = trim((string) ($_POST['telefone'] ?? ''));

    if ($nome === '' || strlen($nome) > 150 || $email === false || strlen($cargo) > 120 || strlen($telefone) > 30) {
        setFlash('error', 'Revise nome, e-mail, cargo e telefone antes de salvar.');
    } else {
        try {
            $newPhoto = saveProfilePhoto($_FILES['foto'] ?? []);
            $photo = $newPhoto ?? profilePhotoPath($user['foto'] ?? null);

            $stmt = db()->prepare('UPDATE usuario SET nome = ?, email = ?, cargo = ?, telefone = ?, foto = ? WHERE id = ? AND ativo = 1');
            $stmt->execute([$nome, $email, $cargo ?: null, $telefone ?: null, $photo, $userId]);

            if ($newPhoto !== null && $newPhoto !== profilePhotoPath($user['foto'] ?? null)) {
                deleteProfilePhoto($user['foto'] ?? null);
            }

            refreshProfileSession($userId);
            setFlash('success', $newPhoto !== null ? 'Perfil atualizado e nova foto aplicada com sucesso.' : 'Perfil atualizado com sucesso.');
        } catch (PDOException $e) {
            if ($newPhoto !== null) {
                deleteProfilePhoto($newPhoto);
            }
            setFlash('error', (string) $e->getCode() === '23000' ? 'Este e-mail já está em uso.' : 'Não foi possível atualizar o perfil.');
        } catch (Throwable $e) {
            if ($newPhoto !== null) {
                deleteProfilePhoto($newPhoto);
            }
            setFlash('error', $e instanceof InvalidArgumentException ? $e->getMessage() : 'Não foi possível atualizar o perfil.');
        }
    }

    redirect('perfil.php');
}

renderHeader('Meu perfil', 'perfil');

$currentPhoto = profilePhotoPath($user['foto'] ?? null);
$defaultAvatar = 'assets/images/default-user.svg';
$defaultAvatarExists = is_file(__DIR__ . '/' . $defaultAvatar);
$avatarSrc = $currentPhoto ?? ($defaultAvatarExists ? $defaultAvatar : null);
$profileTitle = profileLabel($user);
$cargo = trim((string) ($user['cargo'] ?? ''));
?>
<header class="page-header profile-page-header">
    <div>
        <span class="eyebrow">Conta</span>
        <h1>Meu perfil</h1>
        <p>Gerencie sua foto, dados de acesso e informações profissionais.</p>
    </div>
</header>

<section class="panel profile-panel">
    <form method="post" class="profile-form" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">

        <div class="profile-hero">
            <div class="profile-photo-column">
                <button class="profile-photo-button" type="button" data-profile-photo-trigger aria-label="Trocar foto do perfil">
                    <?php if ($avatarSrc !== null): ?>
                        <img class="profile-photo-preview" src="<?= e($avatarSrc) ?>" alt="Foto de <?= e($user['nome'] ?? 'usuário') ?>" data-profile-photo-preview>
                    <?php else: ?>
                        <span class="profile-photo-preview profile-photo-initials" data-profile-photo-preview><?= e(userInitials($user['nome'] ?? null)) ?></span>
                    <?php endif; ?>
                    <span class="profile-photo-overlay"><i class="fa-solid fa-camera"></i> Trocar foto</span>
                </button>
                <input class="file-input-hidden" id="foto" type="file" name="foto" accept="image/jpeg,image/png,image/webp" data-profile-photo-input>

                <div class="profile-photo-actions">
                    <button class="btn secondary small" type="button" data-profile-photo-trigger>
                        <i class="fa-solid fa-arrow-up-from-bracket"></i> Trocar foto
                    </button>
                    <?php if ($currentPhoto !== null): ?>
                        <button class="btn danger small" type="submit" name="action" value="remove_photo" formnovalidate data-confirm="Remover sua foto atual?">
                            <i class="fa-regular fa-trash-can"></i> Remover foto
                        </button>
                    <?php endif; ?>
                </div>
                <small class="field-hint" data-profile-photo-feedback>JPG, PNG ou WEBP, até 2 MB.</small>
            </div>

            <div class="profile-hero-copy">
                <span class="badge"><?= e($profileTitle) ?></span>
                <h2><?= e($user['nome'] ?? '') ?></h2>
                <p><?= e($cargo !== '' ? $cargo : 'Complete seu cargo para deixar o perfil mais informativo.') ?></p>
            </div>
        </div>

        <div class="profile-section">
            <div class="profile-section-title">
                <span class="section-icon"><i class="fa-regular fa-id-card"></i></span>
                <div>
                    <h3>Dados da conta</h3>
                    <p>Informações principais usadas para identificar seu acesso.</p>
                </div>
            </div>
            <div class="profile-grid">
                <label>Nome
                    <input name="nome" maxlength="150" value="<?= e($user['nome'] ?? '') ?>" placeholder="Seu nome completo" required>
                </label>
                <label>E-mail
                    <input type="email" name="email" maxlength="150" value="<?= e($user['email'] ?? '') ?>" placeholder="voce@empresa.com" required>
                </label>
                <label>Perfil
                    <input value="<?= e($profileTitle) ?>" readonly>
                    <small class="field-hint">Seu perfil de acesso é definido pela administração.</small>
                </label>
            </div>
        </div>

        <div class="profile-section">
            <div class="profile-section-title">
                <span class="section-icon"><i class="fa-solid fa-briefcase"></i></span>
                <div>
                    <h3>Dados profissionais</h3>
                    <p>Use estes dados para deixar os relatórios e contatos mais claros.</p>
                </div>
            </div>
            <div class="profile-grid">
                <label>Cargo
                    <input name="cargo" maxlength="120" value="<?= e($user['cargo'] ?? '') ?>" placeholder="Ex.: Analista de RH">
                </label>
                <label>Telefone
                    <input name="telefone" maxlength="30" value="<?= e($user['telefone'] ?? '') ?>" placeholder="(00) 00000-0000">
                </label>
            </div>
        </div>

        <div class="profile-actions">
            <button class="btn primary" type="submit" name="action" value="save_profile">
                <i class="fa-solid fa-floppy-disk"></i> Salvar perfil
            </button>
        </div>
    </form>
</section>
<?php renderFooter(); ?>
