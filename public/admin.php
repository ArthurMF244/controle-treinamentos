<?php

declare(strict_types=1);

require_once __DIR__ . '/_admin.php';
requireAdmin();

$pdo = db();
$counts = [
    'usuarios' => (int) $pdo->query('SELECT COUNT(*) FROM usuario')->fetchColumn(),
    'areas' => (int) $pdo->query('SELECT COUNT(*) FROM area')->fetchColumn(),
    'tipos' => (int) $pdo->query('SELECT COUNT(*) FROM tipo_treinamento')->fetchColumn(),
    'locais' => (int) $pdo->query('SELECT COUNT(*) FROM local_treinamento')->fetchColumn(),
    'status' => (int) $pdo->query('SELECT (SELECT COUNT(*) FROM status_treinamento) + (SELECT COUNT(*) FROM status_participacao) + (SELECT COUNT(*) FROM status_certificado)')->fetchColumn(),
];

$cards = [
    [
        'title' => 'Usuários',
        'description' => 'Cadastre, edite, ative/inative e redefina senhas dos usuários do sistema.',
        'href' => 'admin_usuarios.php',
        'icon' => 'fa-users-gear',
        'count' => $counts['usuarios'],
    ],
    [
        'title' => 'Áreas/Setores',
        'description' => 'Gerencie áreas usadas nos cadastros de pessoas e participantes.',
        'href' => 'admin_areas.php',
        'icon' => 'fa-sitemap',
        'count' => $counts['areas'],
    ],
    [
        'title' => 'Tipos de treinamento',
        'description' => 'Mantenha categorias para organizar os treinamentos institucionais.',
        'href' => 'admin_tipos.php',
        'icon' => 'fa-tags',
        'count' => $counts['tipos'],
    ],
    [
        'title' => 'Locais de treinamento',
        'description' => 'Configure salas, auditórios e ambientes virtuais com capacidade.',
        'href' => 'admin_locais.php',
        'icon' => 'fa-location-dot',
        'count' => $counts['locais'],
    ],
    [
        'title' => 'Status do sistema',
        'description' => 'Edite status de treinamentos, participações e certificados.',
        'href' => 'admin_status.php',
        'icon' => 'fa-list-check',
        'count' => $counts['status'],
    ],
];

renderHeader('Administração', 'admin');
?>
<header class="page-header">
    <div>
        <span class="eyebrow">Área administrativa</span>
        <h1>Administração</h1>
        <p>Gerencie usuários, cadastros auxiliares e parâmetros do sistema.</p>
    </div>
</header>

<section class="admin-cards">
    <?php foreach ($cards as $card): ?>
        <article class="admin-card">
            <span class="admin-card-icon"><i class="fa-solid <?= e($card['icon']) ?>"></i></span>
            <div class="admin-card-body">
                <div>
                    <h2><?= e($card['title']) ?></h2>
                    <p><?= e($card['description']) ?></p>
                </div>
                <div class="admin-card-footer">
                    <span class="admin-card-count"><?= (int) $card['count'] ?> registro<?= (int) $card['count'] === 1 ? '' : 's' ?></span>
                    <a class="btn primary small" href="<?= e($card['href']) ?>">Gerenciar <i class="fa-solid fa-arrow-right"></i></a>
                </div>
            </div>
        </article>
    <?php endforeach; ?>
</section>
<?php renderFooter(); ?>
