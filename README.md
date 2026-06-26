# Sistema de Controle de Treinamentos Institucionais

Aplicação em PHP puro para organizar treinamentos, participantes, certificados e contatos internos. O projeto usa MySQL com PDO e Docker Compose, sem frameworks.

## Tecnologias

- PHP 8.2 com Apache
- MySQL 8
- HTML, CSS e JavaScript
- PDO e prepared statements
- Docker Compose

## Estrutura

```text
api/         Endpoints JSON protegidos por sessão e perfil
database/    Conexão PDO e executor de migrations
docker/      Configuração do Apache e entrypoint
migrations/  Esquema, dados iniciais e evolução do banco
public/      Páginas web, layout, CSS e JavaScript
```

## Como executar

Na raiz do projeto, execute:

```bash
docker compose up -d --build
```

Depois, abra [http://localhost:8082](http://localhost:8082).

Para acompanhar a inicialização e as migrations:

```bash
docker compose logs -f php
```

Se você tiver um volume de uma versão anterior incompatível e quiser reiniciar os dados locais, execute uma única vez:

```bash
docker compose down -v
docker compose up -d --build
```

## Usuários de teste

| Perfil | E-mail | Senha |
| --- | --- | --- |
| Administrador | `admin@admin.com` | `admin` |
| Usuário comum | `usuario@usuario.com` | `usuario` |

As senhas são armazenadas com `password_hash` e validadas com `password_verify`.

## Funcionalidades

- Login, sessão protegida, logout e proteção CSRF
- Perfis de Administrador e Usuário
- Dashboard com indicadores, progresso médio e próximos treinamentos
- Gestão de treinamentos, pessoas, participantes e certificados para administradores
- Consulta de treinamentos, participantes, certificados e relatórios para usuários comuns
- Contatos recebidos disponíveis somente para administradores
- Área administrativa para gerenciar usuários, áreas, tipos, locais e status
- Meu perfil com nome, e-mail, cargo, telefone, avatar padrão, upload e remoção de foto
- Configurações pessoais de tema claro/escuro e cor principal
- Layout responsivo com sidebar, topbar e menu de perfil
- APIs JSON com autorização compatível com os perfis

## Permissões

O administrador pode alterar cadastros, emitir certificados, acompanhar contatos recebidos e acessar a tela de pessoas. O usuário comum possui acesso de consulta e às próprias configurações; tentativas de alteração ou acesso administrativo são bloqueadas no servidor.

## Banco de dados no DBeaver

- Host: `localhost`
- Porta: `3309`
- Banco: `controle_treinamentos`
- Usuário: `controle_treinamentos`
- Senha: `controle_treinamentos`
