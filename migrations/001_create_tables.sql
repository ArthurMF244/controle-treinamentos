CREATE TABLE IF NOT EXISTS area (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(120) NOT NULL UNIQUE,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pessoa (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    cargo VARCHAR(120) NOT NULL,
    area_id INT NOT NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_pessoa_area FOREIGN KEY (area_id) REFERENCES area(id),
    INDEX idx_pessoa_area (area_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS usuario (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tipo_treinamento (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL UNIQUE,
    descricao TEXT NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS status_treinamento (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(80) NOT NULL UNIQUE,
    descricao TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS local_treinamento (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(120) NOT NULL UNIQUE,
    descricao TEXT NULL,
    capacidade INT NOT NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS treinamento (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(180) NOT NULL,
    descricao TEXT NOT NULL,
    data_inicio DATETIME NOT NULL,
    data_fim DATETIME NOT NULL,
    carga_horaria_minutos INT NOT NULL,
    tipo_treinamento_id INT NOT NULL,
    status_treinamento_id INT NOT NULL,
    local_treinamento_id INT NOT NULL,
    responsavel_pessoa_id INT NULL,
    instrutor VARCHAR(150) NOT NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_treinamento_tipo FOREIGN KEY (tipo_treinamento_id) REFERENCES tipo_treinamento(id),
    CONSTRAINT fk_treinamento_status FOREIGN KEY (status_treinamento_id) REFERENCES status_treinamento(id),
    CONSTRAINT fk_treinamento_local FOREIGN KEY (local_treinamento_id) REFERENCES local_treinamento(id),
    CONSTRAINT fk_treinamento_responsavel FOREIGN KEY (responsavel_pessoa_id) REFERENCES pessoa(id) ON DELETE SET NULL,
    INDEX idx_treinamento_inicio (data_inicio),
    INDEX idx_treinamento_status (status_treinamento_id),
    INDEX idx_treinamento_ativo (ativo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS status_participacao (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(80) NOT NULL UNIQUE,
    descricao TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS treinamento_participante (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pessoa_id INT NOT NULL,
    treinamento_id INT NOT NULL,
    status_participacao_id INT NOT NULL,
    data_inscricao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    progresso TINYINT UNSIGNED NOT NULL DEFAULT 0,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_participante_pessoa FOREIGN KEY (pessoa_id) REFERENCES pessoa(id),
    CONSTRAINT fk_participante_treinamento FOREIGN KEY (treinamento_id) REFERENCES treinamento(id),
    CONSTRAINT fk_participante_status FOREIGN KEY (status_participacao_id) REFERENCES status_participacao(id),
    CONSTRAINT uq_participante_treinamento UNIQUE (pessoa_id, treinamento_id),
    CONSTRAINT chk_participante_progresso CHECK (progresso BETWEEN 0 AND 100),
    INDEX idx_participante_treinamento (treinamento_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS status_certificado (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(80) NOT NULL UNIQUE,
    descricao TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS certificado_treinamento (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pessoa_id INT NOT NULL,
    treinamento_id INT NOT NULL,
    status_certificado_id INT NOT NULL,
    data_emissao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    carga_horaria_minutos INT NOT NULL,
    codigo_validacao VARCHAR(80) NOT NULL UNIQUE,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_certificado_pessoa FOREIGN KEY (pessoa_id) REFERENCES pessoa(id),
    CONSTRAINT fk_certificado_treinamento FOREIGN KEY (treinamento_id) REFERENCES treinamento(id),
    CONSTRAINT fk_certificado_status FOREIGN KEY (status_certificado_id) REFERENCES status_certificado(id),
    CONSTRAINT uq_certificado_participacao UNIQUE (pessoa_id, treinamento_id),
    INDEX idx_certificado_emissao (data_emissao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS contato (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL,
    mensagem TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO area (nome, ativo) VALUES
    ('Recursos Humanos', 1),
    ('Tecnologia da Informação', 1),
    ('Financeiro', 1),
    ('Administrativo', 1)
ON DUPLICATE KEY UPDATE ativo = VALUES(ativo);

INSERT INTO tipo_treinamento (nome, descricao, ativo) VALUES
    ('Integração', 'Apresentação institucional para novas pessoas colaboradoras.', 1),
    ('Obrigatório', 'Treinamento requerido por política interna ou norma.', 1),
    ('Desenvolvimento', 'Capacitação para desenvolvimento profissional.', 1)
ON DUPLICATE KEY UPDATE descricao = VALUES(descricao), ativo = VALUES(ativo);

INSERT INTO status_treinamento (nome, descricao) VALUES
    ('Planejado', 'Treinamento cadastrado e ainda não iniciado.'),
    ('Em andamento', 'Treinamento em execução.'),
    ('Concluído', 'Treinamento finalizado.'),
    ('Cancelado', 'Treinamento cancelado.')
ON DUPLICATE KEY UPDATE descricao = VALUES(descricao);

INSERT INTO status_participacao (nome, descricao) VALUES
    ('Inscrito', 'Participante inscrito no treinamento.'),
    ('Em andamento', 'Participante realizando o treinamento.'),
    ('Concluído', 'Participante concluiu o treinamento.'),
    ('Ausente', 'Participante não compareceu.')
ON DUPLICATE KEY UPDATE descricao = VALUES(descricao);

INSERT INTO status_certificado (nome, descricao) VALUES
    ('Emitido', 'Certificado disponível para validação.'),
    ('Cancelado', 'Certificado cancelado.')
ON DUPLICATE KEY UPDATE descricao = VALUES(descricao);

INSERT INTO local_treinamento (nome, descricao, capacidade, ativo) VALUES
    ('Sala de Treinamentos', 'Sala principal para capacitações presenciais.', 30, 1),
    ('Auditório Central', 'Auditório para eventos institucionais.', 80, 1),
    ('Plataforma Online', 'Ambiente virtual para treinamentos remotos.', 200, 1)
ON DUPLICATE KEY UPDATE descricao = VALUES(descricao), capacidade = VALUES(capacidade), ativo = VALUES(ativo);

INSERT INTO pessoa (nome, email, cargo, area_id, ativo) VALUES
    ('Ana Martins', 'ana.martins@instituicao.com', 'Analista de RH', (SELECT id FROM area WHERE nome = 'Recursos Humanos'), 1),
    ('Bruno Costa', 'bruno.costa@instituicao.com', 'Analista de Sistemas', (SELECT id FROM area WHERE nome = 'Tecnologia da Informação'), 1),
    ('Carla Souza', 'carla.souza@instituicao.com', 'Assistente Financeira', (SELECT id FROM area WHERE nome = 'Financeiro'), 1),
    ('Diego Lima', 'diego.lima@instituicao.com', 'Assistente Administrativo', (SELECT id FROM area WHERE nome = 'Administrativo'), 1)
ON DUPLICATE KEY UPDATE nome = VALUES(nome), cargo = VALUES(cargo), area_id = VALUES(area_id), ativo = VALUES(ativo);

INSERT INTO usuario (nome, email, senha, ativo) VALUES
    ('Administrador', 'admin@admin.com', '$2y$12$89Hv6V.MXBQayoi5SUo4/umfH4LU2P3oP7a3iby6onI/JpDDGFmsC', 1)
ON DUPLICATE KEY UPDATE nome = VALUES(nome), senha = VALUES(senha), ativo = VALUES(ativo);

INSERT INTO treinamento (
    titulo, descricao, data_inicio, data_fim, carga_horaria_minutos,
    tipo_treinamento_id, status_treinamento_id, local_treinamento_id,
    responsavel_pessoa_id, instrutor, ativo
) VALUES
    (
        'Integração Institucional',
        'Apresentação da cultura, políticas e fluxos institucionais.',
        DATE_ADD(CURDATE(), INTERVAL 7 DAY) + INTERVAL 9 HOUR,
        DATE_ADD(CURDATE(), INTERVAL 7 DAY) + INTERVAL 12 HOUR,
        180,
        (SELECT id FROM tipo_treinamento WHERE nome = 'Integração'),
        (SELECT id FROM status_treinamento WHERE nome = 'Planejado'),
        (SELECT id FROM local_treinamento WHERE nome = 'Sala de Treinamentos'),
        (SELECT id FROM pessoa WHERE email = 'ana.martins@instituicao.com'),
        'Ana Martins',
        1
    ),
    (
        'Segurança da Informação',
        'Boas práticas para proteção de dados e uso seguro dos recursos digitais.',
        DATE_ADD(CURDATE(), INTERVAL 14 DAY) + INTERVAL 14 HOUR,
        DATE_ADD(CURDATE(), INTERVAL 14 DAY) + INTERVAL 16 HOUR,
        120,
        (SELECT id FROM tipo_treinamento WHERE nome = 'Obrigatório'),
        (SELECT id FROM status_treinamento WHERE nome = 'Planejado'),
        (SELECT id FROM local_treinamento WHERE nome = 'Plataforma Online'),
        (SELECT id FROM pessoa WHERE email = 'bruno.costa@instituicao.com'),
        'Bruno Costa',
        1
    )
ON DUPLICATE KEY UPDATE descricao = VALUES(descricao), updated_at = CURRENT_TIMESTAMP;
