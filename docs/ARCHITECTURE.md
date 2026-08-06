# Documentación de Arquitectura — Componente PayIn

Diagramas técnicos del componente PayIn (Arquitectura Hexagonal, DDD, Laravel 11 como infraestructura).

---

## 1. Arquitectura Hexagonal

```mermaid
flowchart TB
    subgraph UI["Capa de presentación / API"]
        HTTP["HTTP Controllers<br/>(delgados)"] --> FR["FormRequests<br/>(validación)"]
        HTTP --> RES["Resources<br/>(serialización)"]
    end

    subgraph INFRA["INFRASTRUCTURE — Adapters"]
        HTTP
        ELQ["Eloquent<br/>Models · Mappers · Repositories"]
        GATE["PaymentGateways<br/>FakePay · SandboxPay"]
        REG["ProviderRegistry"]
        SVC["Services<br/>SystemClock · TxManager · EventBus · Logger"]
        OBS["Observers<br/>PayInEventLogger"]
    end

    subgraph APP["APPLICATION — Use Cases (ports consumidos)"]
        ORQ["ProcessPayInService<br/>(Orquestador)"]
        QRY["QueryPayInService"]
        LST["ListPayInsService"]
    end

    subgraph DOM["DOMAIN — Núcleo"]
        AGG["Aggregates<br/>PayIn · Client · Account<br/>PaymentMethod · PaymentProvider"]
        VO["Value Objects<br/>Money · Currency · Email · Ids · Reference"]
        ST["State Machine<br/>PayInStatus"]
        EVT["Domain Events"]
        VAL["PayInValidator<br/>(Domain Service)"]
        CON["Contracts<br/>(interfaces de repositorios)"]
    end

    SH["SHARED KERNEL<br/>Uuid · TypedId · ValueObject · Result"]

    HTTP --> ORQ
    ORQ --> CON
    ELQ --> CON
    GATE -->|"implementa PaymentGateway"| ORQ
    REG -->|"resuelve"| GATE
    ORQ --> SVC
    OBS --> EVT
    DOM --> SH
    APP --> DOM
    INFRA --> APP
```

## 2. Diagrama de Componentes

```mermaid
flowchart LR
    subgraph Cliente
        API["POST /api/v1/payins"]
    end

    subgraph Laravel["Laravel (Infrastructure)"]
        NX["nginx + php-fpm"]
        CTL["PayInController"]
        FRM["StorePayInRequest"]
        MID["Middleware<br/>ForceJson · CorrelationId · throttle"]
        EXC["PayInExceptionRenderer"]
        SP["PayInServiceProvider<br/>(wiring de contratos)"]
        LGR["LaravelLogger"]
        LEB["LaravelEventBus"]
        LTM["LaravelTransactionManager"]
        SC["SystemClock"]
        R1["EloquentClientRepository"]
        R2["EloquentAccountRepository"]
        R3["EloquentPaymentMethodRepository"]
        R4["EloquentPaymentProviderRepository"]
        R5["EloquentPayInRepository"]
        M["Mappers"]
        OBS["PayInEventLogger"]
    end

    subgraph App["Application"]
        ORQ["ProcessPayInService"]
        QRY["QueryPayInService"]
        LST["ListPayInsService"]
    end

    subgraph Dom["Domain"]
        PAYIN["PayIn (aggregate)"]
        VAL["PayInValidator"]
        REP["Contracts (interfaces)"]
    end

    subgraph Prov["Payment Providers"]
        FP["FakePayProvider"]
        SP2["SandboxPayProvider"]
        REG2["ProviderRegistry"]
    end

    API --> NX --> CTL
    CTL --> FRM --> ORQ
    ORQ --> VAL --> PAYIN
    ORQ --> REP
    REP --> R1 & R2 & R3 & R4 & R5
    R1 & R2 & R3 & R4 & R5 --> M --> DB[(MySQL)]
    ORQ --> REG2 --> FP & SP2
    ORQ --> LTM & LEB & LGR & SC
    LEB --> OBS --> LGR
    EXC -. errores .-> API
```

## 3. Diagrama de Flujo (Orquestador)

```mermaid
flowchart TD
    A["POST /api/v1/payins"] --> B["FormRequest: validación<br/>(UUID, montos, moneda, referencia)"]
    B -->|inválido| ERR422["422 VALIDATION_ERROR"]
    B -->|válido| C["ProcessPayInCommand (DTO inmutable)"]
    C --> D["assertReferenceIsFree()"]
    D -->|ya existe| ERR409["409 REFERENCE_ALREADY_USED"]
    D --> E["TX A (comienza)"]
    E --> F["Cargar aggregates:<br/>Client · Account · PaymentMethod · Provider"]
    F -->|no existe| ERR404["404 CLIENT/ACCOUNT/METHOD/PROVIDER_NOT_FOUND"]
    F --> G["PayInFactory::create → CREATED"]
    G --> H["PayInValidator::validate()"]
    H -->|invariante rota| ERR422B["422 (ej. CURRENCY_MISMATCH)"]
    H --> I["save(PayIn CREATED)"]
    I --> J["markValidated() → VALIDATED"]
    J --> K["save(PayIn VALIDATED)"]
    K --> L["TX A (commits)"]
    L --> M["dispatch(PayInCreated, PayInValidated)"]
    M --> N["ProviderRegistry->resolve(provider)"]
    N -->|sin adapter| ERR502["502 PROVIDER_GATEWAY_NOT_FOUND"]
    N --> O["gateway->charge(ChargeRequest)<br/>— fuera de transacción —"]
    O -->|excepción inesperada| P["save(Failed PROVIDER_UNEXPECTED_ERROR)"]
    O -->|ChargeResult| Q["TX B (comienza)"]
    P --> ERR502B["502 PAYIN_PROCESSING_ERROR"]
    Q --> R{"¿resultado exitoso?"}
    R -->|sí| S["markProcessed(provider refs)"]
    S --> T["Account::credit(amount) + save"]
    R -->|rechazo/timeout/error| U["markFailed(errorCode, mensaje)"]
    T --> V["save(PayIn PROCESSED)"]
    U --> W["save(PayIn FAILED)"]
    V --> X["TX B (commits)"]
    W --> X
    X --> Y["dispatch(PayInProcessed | PayInFailed)"]
    Y --> Z["201 + PayInResource"]
```

## 4. Modelo Entidad Relación

```mermaid
erDiagram
    CLIENTS ||--o{ ACCOUNTS : "posee"
    CLIENTS ||--o{ TRANSACTIONS : "origina"
    ACCOUNTS ||--o{ PAY_INS : "recibe fondos (destino)"
    PAYMENT_PROVIDERS ||--o{ PAYMENT_METHODS : "tokeniza"
    PAYMENT_PROVIDERS ||--o{ TRANSACTIONS : "ejecuta"
    PAYMENT_METHODS ||--o{ PAY_INS : "instrumento"
    TRANSACTIONS ||--o| PAY_INS : "es"

    CLIENTS {
        uuid id PK
        string name "100"
        string email UK
        timestamps created_at
        timestamps updated_at
    }

    ACCOUNTS {
        uuid id PK
        uuid client_id FK "UK(client_id, currency)"
        char currency "3"
        bigint balance "minor units >= 0"
        timestamps created_at
        timestamps updated_at
    }

    PAYMENT_PROVIDERS {
        uuid id PK
        string code UK "fakepay | sandboxpay | cash"
        string name "100"
        boolean is_active
        json supported_types "matriz de capacidades"
        json configuration
        timestamps created_at
        timestamps updated_at
    }

    PAYMENT_METHODS {
        uuid id PK "instrumento independiente"
        uuid provider_id FK "token pertenece al proveedor"
        enum type "card | bank_transfer | wallet | pse | cash"
        string token "255, opaco; UK(provider_id, token)"
        string details_masked
        boolean is_active
        timestamps created_at
        timestamps updated_at
    }

    TRANSACTIONS {
        uuid id PK
        enum type "payin"
        uuid client_id FK "quién paga (originador)"
        bigint amount "minor units >= 0"
        char currency "3"
        enum status "created|validated|processing|processed|failed"
        string reference "64, UK nullable (idempotencia)"
        uuid provider_id FK "proveedor enrutado"
        string provider_transaction_id "64"
        json provider_response
        string error_code "64"
        string error_message "500"
        timestamp created_at
        timestamp processed_at
        timestamp updated_at
        bigint version "locking optimista"
    }

    PAY_INS {
        uuid transaction_id PK "FK -> transactions (cascade)"
        uuid account_id FK "cuenta destino (de quien sea)"
        uuid payment_method_id FK "instrumento usado"
        bigint fees "minor units"
    }
```

## 5. Diagrama de Secuencia — Procesamiento PayIn

```mermaid
sequenceDiagram
    autonumber
    participant C as Cliente
    participant API as PayInController
    participant FR as StorePayInRequest
    participant ORQ as ProcessPayInService
    participant DOM as PayIn (aggregate)
    participant REPO as PayInRepository
    participant TX as TransactionManager
    participant VAL as PayInValidator
    participant REG as ProviderRegistry
    participant GW as PaymentGateway (FakePay)
    participant EV as EventBus
    participant OBS as PayInEventLogger

    C->>API: POST /api/v1/payins
    API->>FR: valida payload
    FR-->>API: ProcessPayInCommand
    API->>ORQ: process(command)
    ORQ->>ORQ: assertReferenceIsFree()
    ORQ->>TX: execute(fn)  [TX A]
    TX->>ORQ: begin
    ORQ->>ORQ: cargar Client/Account/Method/Provider
    ORQ->>DOM: PayIn::create() → CREATED
    ORQ->>VAL: validate(payIn, client, account, method, provider)
    VAL-->>ORQ: ok (o excepción → 4xx)
    ORQ->>REPO: save(payIn CREATED)
    ORQ->>DOM: markValidated()
    ORQ->>REPO: save(payIn VALIDATED)
    ORQ->>TX: commit
    ORQ->>EV: dispatch(PayInCreated, PayInValidated)
    EV->>OBS: handle()
    OBS-->>OBS: log "payin.state.changed"
    ORQ->>REG: resolve(provider)
    REG-->>ORQ: FakePayProvider
    ORQ->>GW: charge(ChargeRequest)
    GW-->>ORQ: ChargeResult (Success|Rejected|Timeout|Error)
    ORQ->>TX: execute(fn)  [TX B]
    TX->>ORQ: begin
    ORQ->>DOM: markProcessing()
    alt resultado exitoso
        ORQ->>DOM: markProcessed(provider refs)
        ORQ->>ORQ: Account::credit(amount)
    else rechazo / timeout / error
        ORQ->>DOM: markFailed(errorCode, mensaje)
    end
    ORQ->>REPO: save(payIn final)
    ORQ->>TX: commit
    ORQ->>EV: dispatch(PayInProcessed | PayInFailed)
    EV->>OBS: handle()
    API-->>C: 201 {data: {status, error_code, ...}}
```
