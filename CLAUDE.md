# CLAUDE.md - Contexto do Projeto para Sessoes Futuras

## Visao Geral

EVE Pilot Dashboard - aplicacao Laravel 12 para gerenciar personagens do EVE Online. Integra com EVE SSO (OAuth2), ESI API (dados do jogo) e SDE (dados estaticos). Roda em Docker Compose com 6 servicos.

## Stack

- PHP 8.3, Laravel 12, PostgreSQL 16, Redis 7, Nginx, Docker Compose
- Frontend: Blade + Bootstrap 5.3.3 (via CDN, dark theme)
- Testes: PHPUnit 11.5.3, Mockery, SQLite in-memory
- EVE SSO: SocialiteProviders/Eveonline
- ESI: Client wrapper proprio com cache Redis, ETag, retry, token refresh

## Estrutura de Diretorios

- `eve-pilot-dashboard/` - raiz do projeto
  - `docker-compose.yml`, `setup.sh`, `install-app.sh`
  - `docker/` - Dockerfile, nginx config, php config
  - `app-src/` - fontes de desenvolvimento (subset, copiado para src/ via install-app.sh)
  - `src/` - Laravel completo (montado no container como /var/www)
    - Testes e factories ficam em `src/tests/` e `src/database/factories/`
    - Rodar testes: `docker compose exec app php artisan test`

## Decisoes Tecnicas Importantes

### Socialite EVE Online
- Config em `config/services.php` precisa da chave `eveonline` (para Socialite) E `eve` (para ESI)
- O provider mapeia `$ssoUser->character_id` e `$ssoUser->character_name` (NAO `getId()`/`getName()`)
- Callback usa `stateless()` para evitar InvalidStateException

### ESI Service (app/Services/EsiService.php)
- `retry()` closures usam `?\Throwable` (nullable) com `throw: false`
- Laravel 12 pode passar `null` ao callback de retry
- Cache com ETag: guarda dados + etag separados no Redis
- Token refresh automatico via `ensureValidToken()`

### Character is_main
- Preservado no re-login (`$character ? $character->is_main : !$user->characters()->exists()`)
- Apenas calculado para characters novos

### Paginacao
- Usa `pagination::bootstrap-5` (NAO Tailwind) pois layout usa Bootstrap via CDN
- Dark theme via CSS variables do Bootstrap 5

### Testes
- 65 testes, 129 assertions, 11 arquivos
- SQLite in-memory (configurado em phpunit.xml)
- Factories com states: `expired()`, `alt()`, `bpo()`, `bpc()`
- Socialite mockado com `Mockery::mock('Laravel\Socialite\Contracts\Provider')`
- ESI mockado via `$this->app->instance(EsiService::class, $esiMock)`
- Auth redirect testa com `assertNotEquals(200, ...)` pois app nao tem rota `login` nomeada

## Bugs Ja Corrigidos (para referencia)

1. **MissingConfigException "eveonline"** - Faltava chave `eveonline` em services.php
2. **character_id = 0** - Socialite EVE usa `character_id` property, nao `getId()`
3. **InvalidStateException** - Adicionado `stateless()` ao callback
4. **TypeError retry() \Exception** - Mudado para `\Throwable`
5. **TypeError retry() null** - Mudado para `?\Throwable` + `throw: false`
6. **is_main = false no re-login** - Preservar valor existente do character
7. **Paginacao quebrada** - Trocado de Tailwind para `pagination::bootstrap-5`

## Comandos Frequentes

```bash
# Subir ambiente
docker compose up -d

# Testes
docker compose exec app php artisan test

# Logs
docker compose logs -f app
docker compose logs -f queue

# Tinker
docker compose exec app php artisan tinker

# SDE import
docker compose exec app php artisan eve:import-sde

# Cache clear
docker compose exec app php artisan cache:clear

# Git
cd src && git status
```

## Proximos Passos Sugeridos

- Manufacturing calculator com ME/TE bonuses aplicados
- Market price tracker por regiao
- Real-time notifications com Laravel Reverb
- Admin panel com Filament v4
- API REST para integracao externa
- Deploy em producao
