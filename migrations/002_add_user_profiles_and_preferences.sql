ALTER TABLE usuario
    ADD COLUMN perfil VARCHAR(20) NOT NULL DEFAULT 'usuario' AFTER senha,
    ADD COLUMN tema VARCHAR(10) NOT NULL DEFAULT 'light' AFTER perfil,
    ADD COLUMN cor_tema VARCHAR(7) NOT NULL DEFAULT '#246bfd' AFTER tema;

UPDATE usuario
SET perfil = 'admin', tema = 'light', cor_tema = '#246bfd'
WHERE email = 'admin@admin.com';

INSERT INTO usuario (nome, email, senha, perfil, tema, cor_tema, ativo) VALUES
    ('Usuário Teste', 'usuario@usuario.com', '$2y$10$Rsiw./pWJCDBGhVhrU1kCObNkKbD24vrme4EbYoGpVvBCpTe2quDG', 'usuario', 'light', '#246bfd', 1)
ON DUPLICATE KEY UPDATE
    nome = VALUES(nome),
    senha = VALUES(senha),
    perfil = VALUES(perfil),
    tema = VALUES(tema),
    cor_tema = VALUES(cor_tema),
    ativo = VALUES(ativo);
