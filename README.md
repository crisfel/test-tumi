# PayIn Platform

Componente **PayIn** para plataformas financieras: procesamiento transaccional de ingresos de fondos construido con **PHP 8.3**, **Laravel 11**, **Domain-Driven Design** y **Arquitectura Hexagonal (Ports & Adapters)**.

> Diseñado para demostrar calidad de ingeniería empresarial: dominio 100% independiente del framework, proveedores de pago intercambiables sin tocar una sola clase del dominio, y una API versionada, documentada y testeada.

---

## Tabla de contenidos

1. [Arquitectura implementada](#1-arquitectura-implementada)
2. [Decisiones técnicas](#2-decisiones-técnicas)
3. [Patrones de diseño utilizados](#3-patrones-de-diseño-utilizados)
4. [Principios SOLID aplicados](#4-principios-solid-aplicados)
5. [Estructura del proyecto](#5-estructura-del-proyecto)
6. [Modelo del dominio](#6-modelo-del-dominio)
7. [Endpoints](#7-endpoints)
8. [Cómo ejecutar el proyecto](#8-cómo-ejecutar-el-proyecto)
9. [Cómo ejecutar pruebas y calidad](#9-cómo-ejecutar-pruebas-y-calidad)
10. [Cómo agregar un nuevo proveedor](#10-cómo-agregar-un-nuevo-proveedor)
11. [Cómo agregar un nuevo método de pago](#11-cómo-agregar-un-nuevo-método-de-pago)
12. [Seguridad](#12-seguridad)
13. [Riesgos identificados](#13-riesgos-identificados)
14. [Suposiciones realizadas](#14-suposiciones-realizadas)
15. [Posibles mejoras futuras](#15-posibles-mejoras-futuras)

---

## 1. Arquitectura implementada

**Arquitectura Hexagonal (Ports & Adapters)** con cuatro capas en `src/PayIn/` y namespace PSR-4 propio:

```
┌────────────────────────────────────────────────────────────────────┐
│                         INFRASTRUCTURE                              │
│  HTTP (Controllers · FormRequests · Resources · Middleware)         │
│  Persistencia Eloquent (Models · Mappers · Repositories)            │
│  Adapters de proveedores (FakePay · SandboxPay · Registry)          │
│  Servicios (Clock · TransactionManager · EventBus · Logger)         │
└──────────────┬──────────────────────────────┬──────────────────────┘
               │ implementa                    │ implementa
               ▼                               ▼
┌───────────────────────────┐   ┌──────────────────────────────┐
│      APPLICATION           │   │       PORTS (contratos)      │
│  Commands · DTOs · Result  │──►│  PaymentGateway · Registry  │
│  Orquestador · Queries     │   │  Clock · EventBus · Logger  │
│  Casos de uso              │   │  TransactionManager         │
└──────────────┬─────────────┘   └──────────────────────────────┘
               │ depende de
               ▼
┌───────────────────────────┐   ┌──────────────────────────────┐
│         DOMAIN             │   │       CONTRACTS              │
│  Aggregates · Entities     │──►│  ClientRepository ·         │
│  Value Objects · Events    │   │  AccountRepository ·        │
│  State Machine · Validator │   │  PaymentMethodRepository ·  │
│  Factories                 │   │  PaymentProviderRepository· │
└──────────────┬─────────────┘   │  PayInRepository            │
               │                 └──────────────────────────────┘
               ▼
        ┌─────────────┐
        │   SHARED     │  Uuid · TypedId · ValueObject · Result · DomainException
        └─────────────┘
```

**Reglas de dependencia (una sola dirección):**

- `Infrastructure → Application → Domain → Shared`
- El **dominio no conoce Laravel** (ni Eloquent, ni Facades, ni config): puede reutilizarse fuera del framework. Verificado por PHPStan (nivel 9) y por diseño: `src/PayIn/Domain` solo importa `PayIn\Shared` y la librería `symfony/uid`.
- La aplicación define los **puertos** (interfaces) que la infraestructura implementa como **adapters**.
- Los **Controllers** y **Models Eloquent** contienen cero lógica de negocio.

Ver [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md) para los diagramas de componentes, flujo, ER y secuencia.

## 2. Decisiones técnicas

| Decisión | Justificación |
|---|---|
| **Montos en unidades menores enteras** (`BIGINT` cents) | Evita errores de punto flotante en operaciones financieras; alineado con ISO 4217. El VO `Money` encapsula monto+moneda como indivisibles. |
| **UUID v7 para identificadores** (`symfony/uid`) | Ordenables cronológicamente → índices B-tree eficientes. IDs tipados por concepto (`ClientId`, `AccountId`, `TransactionId`...) para type-safety en compilación. |
| **`transactions` como núcleo financiero reutilizable** | Un futuro `Payout`/`Refund` reutiliza la misma tabla sin tocar el dominio; `pay_ins` guarda solo lo específico (sin duplicación). |
| **Call al proveedor FUERA de la transacción** | Evita mantener locks de BD mientras el proveedor responde (latencia variable, timeouts). |
| **Locking optimista (`version` column, compare-and-set)** | El repositorio recuerda la versión cargada del aggregate y falla con `PAYIN_CONCURRENCY_CONFLICT` si otro proceso la modificó. El dominio permanece limpio (la versión es un concepto de persistencia). |
| **Idempotencia por `reference`** | La unicidad en BD + chequeo de aplicación previenen dobles cobros en reintentos (409 Conflict). |
| **Proveedores vía Strategy + Registry por contrato** | Agregar un proveedor = nueva clase que implemente `PaymentGateway` + registro en config. **Cero condicionales** (`if provider == ...`). |
| **Result Pattern para respuestas de proveedores** | `ChargeResult` (Success/Rejected/Timeout/Error) como valores, no excepciones de control de flujo. |
| **Validación en dos frentes** | HTTP (`FormRequest`) para sintaxis/formatos + **Domain Service** (`PayInValidator`) para invariantes de negocio (pertenencia, moneda, actividad). Los datos inválidos nunca llegan al dominio. |
| **Regla `email` del framework NO utilizada** | Las versiones 11.x de Laravel presentan la vulnerabilidad CVE-2026-48019 (inyección CRLF en la regla de email) sin fix publicado; el dominio valida emails con su propio VO. Ver [Riesgos](#13-riesgos-identificados). |
| **Estado `PROCESSING` agregado** | El enunciado pide estados mínimos `CREATED/VALIDATED/PROCESSED/FAILED`; se agrega `PROCESSING` (operación en vuelo) porque el flujo async/colas lo exige. |
| **PHPUnit en lugar de Pest** | PHPUnit ya estaba instalado y locked; Pest no aporta valor diferencial suficiente para justificar el cambio de framework de testing. |
| **Logging estructurado** | Canal `payin` con contexto enriquecido (`payin_id`, `provider`, `outcome`, `latency_ms`, `correlation_id`); JSON formatter en producción. |
| **Correlación de peticiones** | Middleware `X-Correlation-Id` → traza completa request → orquestador → proveedor en los logs. |

## 3. Patrones de diseño utilizados

Cada patrón resuelve un problema real; no se incluyeron por demostración:

| Patrón | Ubicación | Problema que resuelve |
|---|---|---|
| **Ports & Adapters** | Toda la arquitectura | Desacoplar dominio/infraestructura; dominio reutilizable fuera de Laravel |
| **Repository** | `Domain/Contracts/*` ↔ `Infrastructure/.../Repositories/*` | Persistencia intercambiable sin acoplar el dominio |
| **Aggregate Factory** | `PayIn::create()` / `reconstitute()` | Construcción del aggregate con invariantes y eventos |
| **Strategy** | `PaymentGateway` + `FakePayProvider`/`SandboxPayProvider` | Algoritmos de cobro intercambiables por contrato |
| **Registry** | `ProviderRegistry` | Resolución de adapters por código de proveedor sin condicionales |
| **Adapter** | Cada proveedor | Absorbe la heterogeneidad de formatos/respuestas de cada pasarela |
| **State** | `PayInStatus` (enum con mapa de transiciones) + `transitionTo()` | Invariantes de transición; estados terminales; imposible saltar estados |
| **Result Pattern** | `Result`, `ChargeResult` | Errores como valores para resultados esperados (rechazo, timeout) |
| **Mapper** | `PayInMapper` y amigos | Traduce aggregates ↔ representación persistente (Eloquent nunca filtra al dominio) |
| **Command + DTO** | `ProcessPayInCommand`, `ChargeRequest` | Entrada inmutable, validada y tipada |
| **Domain Events** | `PayInCreated`...`PayInFailed` | Desacople del efecto secundario (logging) y habilitación de EDA futuro |
| **Unit of Work** | `TransactionManager` (port) | Atomicidad del caso de uso; el orquestador no conoce detalles de BD |
| **Dependency Injection** | `PayInServiceProvider` | Wiring centralizado de ports → adapters |

## 4. Principios SOLID aplicados

| Principio | Evidencia |
|---|---|
| **S**ingle Responsibility | `ProcessPayInService` orquesta; `PayInValidator` valida; `PayInMapper` mapea; `PayInEventLogger` observa; `PayInExceptionRenderer` traduce errores. Cada clase tiene una única razón de cambio. |
| **O**pen/Closed | Nuevos proveedores se agregan implementando `PaymentGateway` y registrándose en `config/payin.php` → `ProviderRegistry`. No existe ningún `if ($provider === ...)`. La máquina de estados se extiende en `PayInStatus` sin modificar el aggregate. |
| **L**iskov Substitution | `FakePayProvider` y `SandboxPayProvider` son sustituibles en el orquestador: mismo contrato, mismas garantías (nunca lanzan por respuestas de negocio). Verificado por test `test_same_contract_yields_normalized_result`. |
| **I**nterface Segregation | Puertos pequeños y específicos: `Clock`, `TransactionManager`, `EventBus`, `Logger`, `PaymentGateway`; los repositorios del dominio por aggregate (`ClientRepository` ≠ `PayInRepository`). |
| **D**ependency Inversion | El dominio define los contratos; la infraestructura los implementa y se inyecta vía constructor (`PayInServiceProvider`). El orquestador recibe puertos, nunca implementaciones concretas. |

## 5. Estructura del proyecto

```
├── src/PayIn/                       # Componente (namespace PSR-4 PayIn\)
│   ├── Domain/                      # ── Capa de dominio (sin Laravel) ──
│   │   ├── Client/                  #   Aggregate Client + ClientId
│   │   ├── Account/                 #   Aggregate Account + AccountId
│   │   ├── PaymentMethod/           #   Aggregate PaymentMethod + tipo
│   │   ├── PaymentProvider/         #   Aggregate PaymentProvider + ProviderCode
│   │   ├── PayIn/                   #   Aggregate PayIn, Transaction, PayInStatus
│   │   │   └── Events/              #   PayInCreated/Validated/Processing/Processed/Failed
│   │   ├── Contracts/               #   Repositorios + PayInSearchCriteria
│   │   └── Exceptions/              #   Jerarquía de excepciones del dominio
│   │   ├── Money.php · Currency.php · Email.php
│   ├── Application/                 # ── Capa de aplicación ──
│   │   ├── Command/                 #   ProcessPayInCommand
│   │   ├── Dto/                     #   ChargeRequest, ProcessPayInResponse, PayInPage...
│   │   ├── UseCase/                 #   ProcessPayInService (Orquestador), Query, List
│   │   ├── Port/                    #   Clock, TransactionManager, EventBus, Logger, PaymentGateway, Registry
│   │   ├── Result/                  #   ChargeResult, ChargeOutcome
│   │   └── Exception/               #   Not founds, idempotencia, concurrencia, procesamiento
│   ├── Infrastructure/              # ── Capa de infraestructura (Laravel) ──
│   │   ├── Persistence/Eloquent/    #   Models, Mappers, Repositories, Factories
│   │   ├── PaymentProviders/        #   FakePayProvider, SandboxPayProvider, ProviderRegistry
│   │   ├── Http/                    #   Api/V1/Controllers, FormRequests, Resources, Middleware, Exceptions, OpenApi
│   │   ├── Observers/               #   PayInEventLogger
│   │   ├── Services/                #   SystemClock, LaravelTransactionManager, LaravelEventBus, LaravelLogger
│   │   └── Providers/               #   PayInServiceProvider (wiring)
│   └── Shared/                      # ── Kernel compartido ──
│       ├── Uuid/                    #   Uuid (v7), TypedId
│       └── Kernel/                  #   ValueObject, DomainEvent, Result/Error, DomainException
├── app/                             # Skeleton Laravel (HTTP base)
├── bootstrap/app.php                # Routing + middleware + excepciones
├── config/payin.php                 # Configuración del componente
├── config/l5-swagger.php            # Documentación OpenAPI
├── database/
│   ├── migrations/                  # 6 migraciones normalizadas
│   └── seeders/                     # PaymentProviderSeeder + DemoSeeder
├── docker/                          # Dockerfile multi-stage + nginx + php.ini
├── docker-compose.yml               # php-fpm · nginx · mysql:8 · redis
├── tests/
│   ├── Unit/PayIn/                  # Dominio + Aplicación + Adapters
│   ├── Repositories/PayIn/          # Persistencia (SQLite :memory:)
│   ├── Feature/PayIn/               # API end-to-end
│   └── Support/                     # PayInFixtures
├── pint.json · phpstan.neon · rector.php · .editorconfig
```

## 6. Modelo del dominio

**Aggregates y reglas clave:**

| Aggregate | Estado | Reglas clave |
|---|---|---|
| `Client` | id, nombre, email | email validado y normalizado |
| `Account` | id, clientId, moneda, saldo | saldo en cents; `credit()` sólo en su moneda |
| `PaymentMethod` | id, accountId, providerId, tipo, token, activo | token opaco (nunca PAN); pertenece a una cuenta |
| `PaymentProvider` | id, code, nombre, activo, config | catálogo persistido; clave de resolución del Registry |
| `PayIn` | compone `Transaction` + accountId + paymentMethodId + fees | **máquina de estados**: ver abajo |

**Máquina de estados (Patrón State):**

```
            ┌────────┐
            │CREATED │──┐
            └───┬────┘  │
        ┌──────┤       │
        │      │        │
        ▼      ▼        ▼
   VALIDATED  FAILED    │
        │               │
        ▼               │
   PROCESSING ──────► FAILED
        │
        ├──────► PROCESSED (terminal)
        └──────► FAILED   (terminal)
```

Cada estado declara sus transiciones permitidas en `PayInStatus::transitions()`; cualquier transición inválida lanza `InvalidStateTransitionException` (409 en la API).

## 7. Endpoints

| Método | Ruta | Descripción |
|---|---|---|
| `POST` | `/api/v1/clients` | Registra un cliente (name + email único) |
| `POST` | `/api/v1/accounts` | Abre una cuenta (una por cliente y moneda) |
| `GET` | `/api/v1/accounts/{id}` | Consulta una cuenta por UUID |
| `GET` | `/api/v1/accounts?client_id={uuid}` | Lista las cuentas de un cliente (paginado) |
| `POST` | `/api/v1/payment-methods` | Registra un método de pago (token único por cuenta) |
| `GET` | `/api/v1/payment-methods/{id}` | Consulta un método de pago por UUID |
| `GET` | `/api/v1/payment-methods?account_id={uuid}` | Lista los métodos de una cuenta (paginado) |
| `POST` | `/api/v1/payins` | Crea y procesa un PayIn (orquestación completa) |
| `GET` | `/api/v1/payins/{id}` | Consulta por UUID |
| `GET` | `/api/v1/payins` | Listado paginado con filtros (`status`, `from`, `to`, `limit`, `offset`) |
| `GET` | `/api/documentation` | UI Swagger/OpenAPI (spec en `/docs`) |

**Ejemplo de petición (crear cliente):**

```json
{
  "name": "Carlos Rodríguez",
  "email": "carlos.rodriguez@example.com"
}
```

**Ejemplo de petición (abrir cuenta):**

```json
{
  "client_id": "019f0000-0000-7000-8000-000000000001",
  "currency": "COP"
}
```

**Ejemplo de petición (registrar método de pago):**

```json
{
  "account_id": "019f0000-0000-7000-8000-000000000002",
  "provider_code": "fakepay",
  "type": "card",
  "token": "tok_card_visa_4242",
  "details_masked": "**** 4242"
}
```

**Ejemplo de petición:**

```json
{
  "client_id": "019f0000-0000-7000-8000-000000000001",
  "account_id": "019f0000-0000-7000-8000-000000000002",
  "payment_method_id": "019f0000-0000-7000-8000-000000000003",
  "amount": 25000,
  "currency": "COP",
  "reference": "order-2026-0001"
}
```

**Envelope de respuesta (éxito):**

```json
{
  "data": {
    "id": "019f...",
    "client_id": "019f...",
    "account_id": "019f...",
    "payment_method_id": "019f...",
    "amount": 25000,
    "currency": "COP",
    "status": "processed",
    "reference": "order-2026-0001",
    "provider_id": "019f...",
    "provider_transaction_id": "FP-019F...",
    "error_code": null,
    "error_message": null,
    "created_at": "2026-08-06T12:54:23Z",
    "processed_at": "2026-08-06T12:54:23Z"
  }
}
```

**Envelope de error (homogéneo):**

```json
{
  "errors": [
    { "code": "REFERENCE_ALREADY_USED", "message": "La referencia ...", "meta": { "reference": "..." } }
  ]
}
```

**Códigos HTTP:** `201` creado · `200` consulta · `404` no existe · `409` conflicto (referencia duplicada, estado inválido, concurrencia) · `422` datos inválidos · `502` fallo inesperado del proveedor · `405` método no permitido.

> Nota: si el proveedor **rechaza** la operación, el PayIn se crea con `status: "failed"` y `error_code` correspondiente (la creación es exitosa; el cobro falló).

## 8. Cómo ejecutar el proyecto

**Requisitos:** Docker Desktop (con Compose).

```bash
# 1. Levantar el stack (php-fpm, nginx, mysql:8, redis)
docker compose up -d

# 2. Instalar dependencias (PHP 8.3 dentro del contenedor)
docker compose run --rm php composer install

# 3. Migrar y sembrar
docker compose run --rm php php artisan migrate --seed

# 4. Generar la documentación Swagger
docker compose run --rm php php artisan l5-swagger:generate

# 5. ¡Listo! API en http://localhost:8080/api/v1/payins
```

**Datos de demostración sembrados** (`DemoSeeder`):

| Concepto | Valor |
|---|---|
| Cliente | `ana.garcia@example.com` |
| Cuenta COP + método tarjeta (FakePay) | consultar con `php artisan tinker` o SQL |
| Cuenta USD + método wallet (SandboxPay) | idem |

**Comportamiento de los proveedores ficticios** (variables de entorno en `.env`):

```
PAYIN_FAKEPAY_BEHAVIOR=success      # success | rejected | timeout | error
PAYIN_FAKEPAY_LATENCY_MS=0
PAYIN_SANDBOXPAY_BEHAVIOR=success
PAYIN_SANDBOXPAY_LATENCY_MS=0
```

## 9. Cómo ejecutar pruebas y calidad

```bash
# Suite completa de pruebas (136 tests)
docker compose run --rm php vendor/bin/phpunit

# Solo una capa
docker compose run --rm php vendor/bin/phpunit tests/Unit/PayIn
docker compose run --rm php vendor/bin/phpunit tests/Repositories/PayIn
docker compose run --rm php vendor/bin/phpunit tests/Feature/PayIn

# Calidad
docker compose run --rm php vendor/bin/pint --test     # estilo PSR-12
docker compose run --rm php vendor/bin/phpstan analyse # análisis estático nivel 9
docker compose run --rm php vendor/bin/rector process --dry-run  # refactorings
```

**Cobertura:** Unit (dominio/aplicación/adapters), Repositories (round-trip sobre SQLite en memoria) y Feature (API end-to-end sobre SQLite; en CI se ejecutaría sobre MySQL).

## 10. Cómo agregar un nuevo proveedor

Agregar un proveedor **no modifica ninguna clase del dominio ni del orquestador** (Open/Closed). Pasos:

```php
// 1. Crear el adapter en src/PayIn/Infrastructure/PaymentProviders/
final class NewPayProvider implements PaymentGateway
{
    public function charge(ChargeRequest $request): ChargeResult
    {
        // llamar a la API real del proveedor...
        return ChargeResult::success('NP-' . uniqid(), 'approved', [...]);
        // o bien: ChargeResult::rejected/timeout/error(...)
    }
}
```

```php
// 2. Registrar en config/payin.php (configuración y resolución)
'providers' => ['newpay' => ['behavior' => 'success', 'latency_ms' => 0]],
'gateways'  => ['newpay' => NewPayProvider::class],
```

```php
// 3. Registrar el proveedor en el catálogo (seeder o migración de datos)
PaymentProviderModel::query()->updateOrCreate(
    ['code' => 'newpay'],
    ['id' => ProviderId::generate()->toString(), 'name' => 'NewPay', 'is_active' => true],
);
```

Listo: el orquestador resuelve el adapter automáticamente por el `code` del método de pago. El contrato exige devolver siempre un `ChargeResult` (nunca excepciones de control de flujo) y absorber el formato propio del proveedor dentro del adapter.

## 11. Cómo agregar un nuevo método de pago

```php
// 1. Agregar el tipo al enum del dominio (punto de extensión documentado)
enum PaymentMethodType: string { case CARD = 'card'; /* ... */ case NEW_TYPE = 'new_type'; }
```

```php
// 2. Migración: ampliar el CHECK/ENUM de payment_methods.type
// 3. Crear el método de pago (vía seeder o flujo de registro futuro)
PaymentMethodModel::query()->create([...'type' => 'new_type', 'token' => 'tok_...', ...]);
```

El dominio, el orquestador y los proveedores no requieren cambios: el adapter decide cómo cobrar según el tipo.

## 12. Seguridad

- **Todas las entradas se validan** en `FormRequest` (UUID, montos enteros positivos, monedas del catálogo, referencias con patrón estricto) → nunca llegan datos inválidos al dominio.
- **Sin Mass Assignment:** los modelos usan `$fillable` explícito.
- **DTOs inmutables** (`ProcessPayInCommand`, `ChargeRequest`) como única puerta de entrada al dominio.
- **No se exponen modelos internos:** la API devuelve `PayInResource` (campos seleccionados); el token del método de pago jamás se serializa.
- **Sin stack traces:** `PayInExceptionRenderer` mapea la jerarquía de excepciones a códigos estables; las desconocidas devuelven 500 genérico.
- **No se confía en el cliente:** proveedor resuelto desde el método de pago persistido (no desde la petición); referencia idempotente con unicidad en BD.
- **UUID en URLs** (`whereUuid`) y rate limiting (`throttle:30,1`) en escritura.
- **Advisory CVE-2026-48019 (CRLF en regla `email`):** no se usa la regla del framework; el dominio valida con su propio `Email` VO. Los advisories de Composer sobre Laravel 11 se ignoran explícitamente en `composer.json` (documentados en [Riesgos](#13-riesgos-identificados)) porque el requisito fija Laravel 11.

## 13. Riesgos identificados

| Riesgo | Mitigación / estado |
|---|---|
| **Advisories de seguridad en Laravel 11.x** (signed URLs, CRLF en regla `email` — fix solo en 12.x) | No se usan URLs firmadas ni la regla `email`; `composer.json` documenta los IDs ignorados (`PKSA-m5cs-t1y6-qpcs`, `PKSA-3r5d-mb8f-1qw9`, `PKSA-mdq4-51ck-6kdq`). **Se recomienda planificar la migración a Laravel 12+ cuando el negocio lo permita.** |
| **Dual-write entre proveedor y BD** (el proveedor cobra, la BD falla) | El PayIn queda persistido en `VALIDATED` (nunca inconsistente). Mitigación completa: patrón Outbox + reconciliación (ver mejoras). |
| **Concurrencia sobre el saldo de la cuenta** | El abono ocurre en transacción aislada; para alta concurrencia se documenta `SELECT ... FOR UPDATE` o colas serializadas. |
| **SQLite en tests vs MySQL en producción** | CI debe ejecutar la suite con MySQL (paridad real); local usa SQLite por velocidad. |
| **Timeout del proveedor → FAILED definitivo** | Diseño v1: el cliente reintenta con nueva referencia. Evolución: cola de reintentos con backoff + webhooks. |
| **`PROCESSING` sin persistencia intermedia** | La transición intermedia se registra en memoria y el estado final se persiste; una reconciliación futura detectaría `VALIDATED` huérfanos. |

## 14. Suposiciones realizadas

- Sin autenticación en la API (el enunciado no la exige; evolución documentada: Sanctum/OAuth2).
- Proveedores ficticios (FakePay, SandboxPay) con comportamiento configurable; sin integraciones reales.
- Monedas soportadas: `COP, USD, EUR, MXN` (enum extensible).
- Montos en unidades menores enteras (cents) con exponente 2.
- Comisiones (`fees`) siempre en cero en v1 (el VO y el esquema ya lo soportan).
- El cliente, la cuenta y el método de pago ya existen (creación de clientes fuera de alcance).
- Documentación en español.

## 15. Posibles mejoras futuras

- **Autenticación API** (Sanctum + tokens, OAuth2 para integradores).
- **Colas y eventos asíncronos**: procesamiento `PROCESSING` persistido + workers; reintentos con backoff exponencial; webhooks de estado.
- **Patrón Outbox** para consistencia entre BD y eventos/proveedor.
- **Reconciliación programada** (comando `payins:reconcile`) contra los reportes del proveedor.
- **CQRS** (separar lectura/escritura; materializar vistas de consulta).
- **Multitenancy** y gestión de clientes/onboarding.
- **Estrategia de pagos por método**: PSE requiere redirección (flujo async obligatorio).
- **CI/CD completo** (GitHub Actions: Pint, PHPStan nivel 9, Rector, PHPUnit sobre MySQL, build Docker) — pendiente por decisión de alcance.
- **Telemetría** (OpenTelemetry) y paneles de observabilidad.
- **Migración de Laravel 11 → 12** para resolver los advisories de seguridad.

---

*Componente construido con arquitectura hexagonal, DDD, SOLID y patrones de diseño. Documentación técnica de diagramas: [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md).*
