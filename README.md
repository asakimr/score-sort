# Pelada Manager

Sistema web simples para organizar pelada/futsal, construído com **Slim 4 + PHP 8.2 + SQLite**, com frontend **mobile-first** e adaptado também para **desktop**, usando **TailwindCSS** e **Alpine.js** via CDN.

## Stack

- PHP 8.2+
- Slim Framework 4
- PHP-DI
- SQLite
- TailwindCSS (CDN, MVP)
- Alpine.js (CDN, interações leves)

## Estrutura do projeto

```text
projeto-futsal/
├── bin/
│   └── migrate.php
├── database/
│   └── migrations/
├── public/
│   └── index.php
├── src/
│   ├── Application/
│   ├── Controllers/
│   ├── Repositories/
│   ├── Support/
│   ├── routes/
│   ├── dependencies.php
│   ├── repositories.php
│   └── settings.php
├── storage/
├── views/
│   ├── components/
│   ├── layouts/
│   └── pages/
├── .env.example
├── composer.json
└── README.md
```

## O que já está funcional

### Backend/base
- bootstrap inicial do Slim em `public/index.php`
- container DI com settings, dependências e repositories
- camada simples de renderização de views em PHP
- conexão SQLite centralizada
- script de migração SQL

### CRUD de jogadores
- listagem de jogadores
- cadastro de jogador
- edição de jogador
- ativar/inativar jogador
- validação de nome
- validação de nota entre 0 e 5 em passos de 0,5
- flag de goleiro

### Sessão e presença
- criação de sessão por data
- configuração de máximo por partida
- escolha entre modo `balanced` e `random`
- flag de priorização de goleiros
- marcação de presença de jogadores ativos
- persistência das presenças em `session_attendances`
- resumo da sessão criada
- contadores automáticos de:
  - presentes
  - jogadores por time
  - jogadores em quadra
  - fila de espera
  - faltantes para completar o próximo time

### Frontend / UI
- layout mobile-first
- adaptação para desktop com sidebar e grids maiores
- navegação inferior no mobile
- navegação superior/lateral no desktop
- revisão de contraste dos botões e textos
- componentes reutilizáveis para cards e estados vazios

## Como rodar

### 1. Entrar na pasta do projeto

```bash
cd /home/paulo-cruz/projeto-futsal
```

### 2. Instalar dependências

```bash
composer install
```

### 3. Criar arquivo `.env`

```bash
cp .env.example .env
```

### 4. Rodar as migrations

```bash
php bin/migrate.php
```

### 5. Subir servidor local

```bash
composer start
```

A aplicação ficará disponível em:

```text
http://localhost:8500
```

## Fluxos já testáveis

### Jogadores
- `GET /jogadores`
- `POST /jogadores`
- `GET /jogadores/{id}/editar`
- `POST /jogadores/{id}`
- `POST /jogadores/{id}/toggle-active`

### Sessões / presença
- `GET /peladas/nova`
- `POST /peladas`
- `GET /peladas/{id}`

## Fluxo sugerido para teste

1. abrir `http://localhost:8500/jogadores`
2. cadastrar alguns jogadores
3. marcar alguns como goleiro
4. abrir `http://localhost:8500/peladas/nova`
5. definir data e limite por partida
6. selecionar os presentes
7. salvar a sessão
8. revisar o resumo gerado

## Backup dos jogadores e reset dos testes

Para testar o sistema sem perder o cadastro base dos jogadores, você agora tem dois comandos prontos.

### Gerar backup dos jogadores

```bash
composer backup-players
```

Ou diretamente:

```bash
php bin/backup_players.php
```

O arquivo JSON será salvo em:

```text
storage/backups/players-backup-YYYYmmdd-HHMMSS.json
```

O backup contém:
- data/hora da exportação
- caminho do banco usado
- total de jogadores
- lista completa dos jogadores cadastrados

### Resetar dados de teste e manter só os jogadores

```bash
composer reset-tests
```

Ou diretamente:

```bash
php bin/reset_sessions.php
```

Esse reset apaga apenas dados transacionais de uso do sistema:
- `sessions`
- `session_attendances`
- `teams`
- `team_players`
- `matches`
- `match_transfers`
- `match_events`

O cadastro em `players` é preservado.

### Fluxo recomendado para testes

1. Gere um backup dos jogadores.
2. Faça os testes livremente.
3. Quando quiser limpar o ambiente, rode o reset.
4. Continue com a base de jogadores intacta.

## Próximos passos do produto

- refino visual do campinho/partida
- mais clareza no fluxo de rotação entre vencedor, perdedor e fila
- estatísticas futuras por jogador, gols e assistências

## Observações técnicas

- Neste MVP, Tailwind e Alpine estão via CDN para acelerar desenvolvimento.
- Em produção, o ideal é migrar Tailwind para build local e versionar assets.
- SQLite atende muito bem o escopo inicial.
- A renderização server-side em PHP mantém boa performance e baixa complexidade.
