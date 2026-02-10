# EVE Pilot Dashboard - Laravel App (`src/`)

Este diretorio contem a aplicacao Laravel 12 completa, montada no container Docker em `/var/www`.

## Estrutura Principal

```
src/
├── app/
│   ├── Console/Commands/
│   │   └── ImportSde.php               # eve:import-sde - importa SDE do Fuzzwork CSV
│   ├── Http/Controllers/
│   │   ├── Auth/EveAuthController.php  # EVE SSO login (stateless), callback, logout
│   │   ├── DashboardController.php     # Dashboard: wallet, assets, industry jobs
│   │   └── BlueprintController.php     # Lista blueprints + detalhes manufacturing
│   ├── Jobs/
│   │   └── SyncCharacterData.php       # Job background: sync wallet, assets, blueprints
│   ├── Models/
│   │   ├── User.php                    # hasMany characters, mainCharacter()
│   │   ├── Character.php               # character_id, tokens, isTokenExpired()
│   │   ├── CharacterAsset.php          # Assets do personagem (via character_id)
│   │   ├── CharacterBlueprint.php      # Blueprints do personagem, isBpo(), isBpc()
│   │   ├── SdeType.php                 # Tipos de itens (PK: type_id)
│   │   ├── SdeBlueprint.php            # Blueprints SDE (PK: blueprint_type_id)
│   │   ├── SdeBlueprintMaterial.php    # Materiais por blueprint
│   │   └── SdeBlueprintProduct.php     # Produtos por blueprint
│   ├── Providers/
│   │   └── AppServiceProvider.php      # Registra Socialite EVE Online driver
│   └── Services/
│       └── EsiService.php              # ESI API client (cache, ETag, retry, token refresh)
├── config/
│   └── services.php                    # Config eveonline (Socialite) + eve (ESI)
├── database/
│   ├── factories/                      # 8 factories (User, Character, Assets, Blueprints, SDE)
│   └── migrations/                     # Migrations para todas as tabelas
├── resources/views/
│   ├── layouts/app.blade.php           # Layout Bootstrap 5 dark theme
│   ├── welcome.blade.php              # Landing page
│   ├── dashboard/index.blade.php      # Dashboard principal
│   └── blueprints/
│       ├── index.blade.php            # Lista de blueprints (paginacao Bootstrap 5)
│       └── show.blade.php             # Detalhes: materiais, custo, lucro estimado
├── routes/web.php                      # Todas as rotas
└── tests/
    ├── Unit/
    │   ├── Models/                     # CharacterTest, CharacterBlueprintTest, UserTest, SdeBlueprintTest
    │   └── Services/EsiServiceTest.php # Cache, ETag, token refresh, paginacao
    └── Feature/
        ├── Auth/EveAuthControllerTest.php
        ├── Controllers/                # DashboardControllerTest, BlueprintControllerTest
        ├── Jobs/SyncCharacterDataTest.php
        ├── Commands/ImportSdeTest.php
        └── Routes/RouteTest.php
```

## Comandos

```bash
# Testes
php artisan test
php artisan test --testsuite=Unit
php artisan test --testsuite=Feature

# SDE Import
php artisan eve:import-sde

# Cache
php artisan cache:clear
php artisan config:clear
```

## Notas Tecnicas

- **Socialite EVE**: Usa `SocialiteProviders/Eveonline`. O provider mapeia `character_id` e `character_name` (nao `id`/`name` padrao). O callback usa `stateless()` para evitar `InvalidStateException`.
- **ESI retry()**: As closures de retry usam `?\Throwable` (nullable) com `throw: false` pois Laravel 12 pode passar `null` ao callback.
- **Paginacao**: Usa `pagination::bootstrap-5` (nao Tailwind) pois o layout usa Bootstrap via CDN.
- **Character is_main**: Preservado no re-login; calculado apenas para novos characters.
- **Testes**: SQLite in-memory, Mockery para Socialite/ESI, factories com states (`expired()`, `alt()`, `bpo()`, `bpc()`).
