# EVE Pilot Dashboard

Projeto piloto para testar as conexoes com as APIs do EVE Online (ESI + SSO + SDE).

## O que este projeto testa

| Camada | O que valida | Status |
|--------|-------------|--------|
| EVE SSO OAuth2 v2 | Login, token refresh, JWT | Core |
| ESI API publica | Character info, market prices | Core |
| ESI API autenticada | Wallet, assets, blueprints, industry | Core |
| SDE Import | Types (40k+), blueprints, materials | Core |
| Cache + ETag | Redis cache respeitando Expires header | Core |
| Rate Limiting | Retry automatico, backoff | Core |
| Queue/Jobs | Sync de dados em background | Core |
| Frontend | Dashboard com Blade + Bootstrap | Bonus |

## Stack

- **PHP 8.3** + **Laravel 12**
- **PostgreSQL 16** (app data + SDE)
- **Redis 7** (cache + queue + sessions)
- **Nginx** (reverse proxy)
- **Docker Compose** (tudo containerizado)

## Pre-requisitos

- Docker e Docker Compose instalados
- Uma conta no EVE Online
- Um app registrado no EVE Developer Portal

## Setup Rapido

### 1. Registrar aplicacao no EVE Online

1. Acesse https://developers.eveonline.com/
2. Faca login com sua conta EVE
3. Clique em **Applications** > **Create New Application**
4. Preencha:
   - **Name**: EVE Pilot Dashboard
   - **Description**: Pilot project for testing ESI API
   - **Connection Type**: Authentication & API Access
   - **Callback URL**: `http://localhost:8080/auth/eve/callback`
   - **Scopes** (selecione todos):
     - `esi-wallet.read_character_wallet.v1`
     - `esi-assets.read_assets.v1`
     - `esi-characters.read_blueprints.v1`
     - `esi-industry.read_character_jobs.v1`
     - `esi-skills.read_skills.v1`
5. Salve e copie o **Client ID** e **Secret Key**

### 2. Instalar o projeto

```bash
# Clonar/acessar o diretorio do projeto
cd eve-pilot-dashboard

# Rodar o setup (instala Laravel + dependencias + containers)
./setup.sh
```

### 3. Configurar credenciais EVE

Edite o arquivo `src/.env` e preencha:

```env
EVE_CLIENT_ID=seu_client_id_aqui
EVE_CLIENT_SECRET=seu_secret_key_aqui
EVE_CALLBACK_URL=http://localhost:8080/auth/eve/callback
```

### 4. Instalar arquivos da aplicacao

```bash
# Copiar codigo da app para dentro do Laravel
./install-app.sh

# Criar as tabelas no banco
docker compose exec app php artisan migrate

# Importar dados estaticos do EVE (SDE) - ~2 minutos
docker compose exec app php artisan eve:import-sde
```

### 5. Acessar

Abra http://localhost:8080 no navegador e clique em **Login with EVE Online**.

## Uso

### Login
- Clique em "Login with EVE Online"
- Voce sera redirecionado para o site da CCP
- Autorize o app com sua conta EVE
- Retorna automaticamente ao dashboard

### Dashboard
- Mostra dados do seu personagem (nome, corp, portrait)
- Saldo da wallet em ISK
- Top 20 assets por quantidade
- Industry jobs ativos (manufacturing, research, etc.)

### Blueprints
- Lista todos os blueprints do personagem
- Mostra BPO/BPC, ME%, TE%, runs
- Clique em "Details" para ver materiais e custo estimado

### Sync Manual
- Clique em "Sync Character Data" no dashboard
- Dispara um job em background que sincroniza:
  - Wallet balance
  - Assets (inventario completo)
  - Blueprints

### Import SDE
```bash
# Re-importar SDE (apos patch do EVE)
docker compose exec app php artisan eve:import-sde
```

## Comandos Uteis

```bash
# Ver logs da aplicacao
docker compose logs -f app

# Ver logs do queue worker
docker compose logs -f queue

# Acessar o container PHP
docker compose exec app sh

# Rodar artisan commands
docker compose exec app php artisan <command>

# Limpar caches
docker compose exec app php artisan cache:clear
docker compose exec app php artisan config:clear

# Parar tudo
docker compose down

# Parar e remover volumes (dados do banco)
docker compose down -v

# Rebuild completo
docker compose build --no-cache
docker compose up -d
```

## Estrutura do Projeto

```
eve-pilot-dashboard/
├── docker-compose.yml          # Orquestracao dos containers
├── setup.sh                    # Script de instalacao inicial
├── install-app.sh              # Instala arquivos da app no Laravel
├── docker/
│   ├── Dockerfile              # PHP 8.3 + extensoes
│   ├── nginx/default.conf      # Configuracao Nginx
│   └── php/local.ini           # Configuracao PHP
├── app-src/                    # Codigo fonte da aplicacao
│   ├── app/
│   │   ├── Console/Commands/
│   │   │   └── ImportSde.php          # Importa SDE do Fuzzwork
│   │   ├── Http/Controllers/
│   │   │   ├── Auth/
│   │   │   │   └── EveAuthController.php  # EVE SSO login/callback
│   │   │   ├── DashboardController.php    # Dashboard principal
│   │   │   └── BlueprintController.php    # Blueprints + custo
│   │   ├── Jobs/
│   │   │   └── SyncCharacterData.php      # Sync em background
│   │   ├── Models/
│   │   │   ├── User.php
│   │   │   ├── Character.php              # Personagem EVE
│   │   │   ├── CharacterAsset.php         # Itens do personagem
│   │   │   ├── CharacterBlueprint.php     # Blueprints do personagem
│   │   │   ├── SdeType.php               # Tipos de itens (SDE)
│   │   │   ├── SdeBlueprint.php           # Blueprints (SDE)
│   │   │   ├── SdeBlueprintMaterial.php   # Materiais (SDE)
│   │   │   └── SdeBlueprintProduct.php    # Produtos (SDE)
│   │   ├── Providers/
│   │   │   └── AppServiceProvider.php     # Registra Socialite EVE
│   │   └── Services/
│   │       └── EsiService.php             # ESI API client wrapper
│   ├── config/
│   │   └── services.php                   # Config EVE SSO + ESI
│   ├── database/migrations/               # Migrations
│   ├── resources/views/                   # Blade templates
│   └── routes/web.php                     # Rotas
└── src/                        # Laravel instalado (gerado pelo setup)
```

## Arquitetura de Conexao

```
Browser (User)
    │
    ▼
┌─────────┐     ┌──────────────┐     ┌──────────────┐
│  Nginx  │────▶│  PHP-FPM     │────▶│  PostgreSQL   │
│  :8080  │     │  Laravel 12  │     │  :5432        │
└─────────┘     └──────┬───────┘     └──────────────┘
                       │
              ┌────────┼────────┐
              ▼        ▼        ▼
        ┌────────┐ ┌───────┐ ┌──────────────┐
        │ Redis  │ │ EVE   │ │ ESI API      │
        │ Cache  │ │ SSO   │ │ REST         │
        │ Queue  │ │ OAuth │ │ (Data)       │
        └────────┘ └───────┘ └──────────────┘
```

## Fluxo de Autenticacao

```
1. User clica "Login with EVE Online"
2. Redirect para login.eveonline.com/v2/oauth/authorize/
3. User autoriza no site da CCP
4. Callback com authorization code
5. Troca code por access_token + refresh_token
6. Busca dados do personagem via ESI
7. Cria/atualiza User + Character no banco
8. Login no Laravel (session)
```

## Fluxo de Dados ESI

```
1. Request ESI endpoint
2. Verifica Redis cache (key + ETag)
3. Se cache valido → retorna cache
4. Se expirado → request ESI com If-None-Match
5. Se 304 → cache ainda valido, retorna
6. Se 200 → salva no Redis com TTL do Expires header
7. Se erro → retorna cache antigo (graceful degradation)
```

## Troubleshooting

### "Invalid redirect URI"
- Verifique se `EVE_CALLBACK_URL` no `.env` bate exatamente com o registrado no Developer Portal
- Deve ser: `http://localhost:8080/auth/eve/callback`

### "SDE import muito lento"
- Normal! Sao ~40.000 tipos de itens
- O import leva ~2-3 minutos na primeira vez

### "Assets/Blueprints vazios"
- Clique em "Sync Character Data" no dashboard
- Aguarde o job processar (veja logs: `docker compose logs -f queue`)

### "Token expired"
- O sistema renova tokens automaticamente
- Se persistir, faca logout e login novamente

### "Rate limited (429)"
- O sistema tem retry automatico com backoff
- Se persistir, aguarde 15 minutos

## Testes Automatizados

O projeto possui uma suite completa de testes (65 testes, 129 assertions):

```bash
# Rodar todos os testes
docker compose exec app php artisan test

# Rodar por suite
docker compose exec app php artisan test --testsuite=Unit
docker compose exec app php artisan test --testsuite=Feature
```

| Suite | Arquivo | Testes |
|-------|---------|--------|
| Unit/Models | CharacterTest | 6 |
| Unit/Models | CharacterBlueprintTest | 6 |
| Unit/Models | UserTest | 3 |
| Unit/Models | SdeBlueprintTest | 3 |
| Unit/Services | EsiServiceTest | 11 |
| Feature/Auth | EveAuthControllerTest | 6 |
| Feature/Controllers | DashboardControllerTest | 5 |
| Feature/Controllers | BlueprintControllerTest | 5 |
| Feature/Jobs | SyncCharacterDataTest | 7 |
| Feature/Commands | ImportSdeTest | 5 |
| Feature/Routes | RouteTest | 5 |
| **Total** | **11 arquivos** | **65 testes** |

Os testes usam SQLite in-memory, Mockery para mocks, e factories para gerar dados.

## Proximos Passos

- [x] Suite de testes automatizados (65 testes)
- [x] EVE SSO login/logout funcionando
- [x] Dashboard com wallet, assets, industry jobs
- [x] Blueprints com paginacao e custo estimado
- [ ] Manufacturing calculator com ME/TE bonuses
- [ ] Market price tracker por regiao
- [ ] Real-time notifications com Laravel Reverb
- [ ] Admin panel com Filament v4
- [ ] API REST para integracao externa
- [ ] Deploy com Docker em producao
