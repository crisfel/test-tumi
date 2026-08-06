-- ============================================================================
-- PayIn Platform — Esquema relacional normalizado (MySQL 8)
-- ============================================================================
-- Entregable de referencia del modelo de datos. Las migraciones Laravel
-- (database/migrations) son la fuente de verdad en ejecución; este script
-- documenta el DDL completo del modelo final.
--
-- Entidades: clients, accounts, payment_providers, payment_methods,
--            transactions, pay_ins
-- ============================================================================

CREATE TABLE clients (
    id          CHAR(36)     NOT NULL,
    name        VARCHAR(100) NOT NULL,
    email       VARCHAR(255) NOT NULL,
    created_at  TIMESTAMP    NULL,
    updated_at  TIMESTAMP    NULL,
    PRIMARY KEY (id),
    UNIQUE KEY clients_email_unique (email)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE payment_providers (
    id              CHAR(36)     NOT NULL,
    code            VARCHAR(32)  NOT NULL,
    name            VARCHAR(100) NOT NULL,
    is_active       TINYINT(1)   NOT NULL DEFAULT 1,
    supported_types JSON         NULL COMMENT 'Matriz de capacidades: tipos de método que el proveedor puede procesar',
    configuration   JSON         NULL,
    created_at      TIMESTAMP    NULL,
    updated_at      TIMESTAMP    NULL,
    PRIMARY KEY (id),
    UNIQUE KEY payment_providers_code_unique (code)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE accounts (
    id          CHAR(36)  NOT NULL,
    client_id   CHAR(36)  NOT NULL,
    currency    CHAR(3)   NOT NULL,
    balance     BIGINT    NOT NULL DEFAULT 0 COMMENT 'Saldo en unidades menores (enteras)',
    created_at  TIMESTAMP NULL,
    updated_at  TIMESTAMP NULL,
    PRIMARY KEY (id),
    UNIQUE KEY accounts_client_id_currency_unique (client_id, currency),
    CONSTRAINT accounts_client_id_foreign
        FOREIGN KEY (client_id) REFERENCES clients (id) ON DELETE CASCADE,
    CONSTRAINT accounts_balance_check CHECK (balance >= 0)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE payment_methods (
    id              CHAR(36)     NOT NULL,
    provider_id     CHAR(36)     NOT NULL COMMENT 'Proveedor que tokenizó el instrumento',
    type            ENUM('card', 'bank_transfer', 'wallet', 'pse', 'cash') NOT NULL,
    token           VARCHAR(255) NOT NULL COMMENT 'Token opaco (nunca el PAN)',
    details_masked  VARCHAR(255) NOT NULL,
    is_active       TINYINT(1)   NOT NULL DEFAULT 1,
    created_at      TIMESTAMP    NULL,
    updated_at      TIMESTAMP    NULL,
    PRIMARY KEY (id),
    UNIQUE KEY payment_methods_provider_id_token_unique (provider_id, token),
    KEY payment_methods_provider_id_index (provider_id),
    CONSTRAINT payment_methods_provider_id_foreign
        FOREIGN KEY (provider_id) REFERENCES payment_providers (id) ON DELETE RESTRICT
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE transactions (
    id                      CHAR(36)     NOT NULL,
    type                    ENUM('payin') NOT NULL,
    client_id               CHAR(36)     NOT NULL COMMENT 'Quién paga (originador)',
    amount                  BIGINT       NOT NULL COMMENT 'Unidades menores (enteras)',
    currency                CHAR(3)      NOT NULL,
    status                  ENUM('created', 'validated', 'processing', 'processed', 'failed') NOT NULL,
    reference               VARCHAR(64)  NULL COMMENT 'Idempotencia',
    provider_id             CHAR(36)     NULL COMMENT 'Proveedor que ejecutó el cobro',
    provider_transaction_id VARCHAR(64)  NULL,
    provider_response       JSON         NULL,
    error_code              VARCHAR(64)  NULL,
    error_message           VARCHAR(500) NULL,
    created_at              TIMESTAMP    NOT NULL,
    processed_at            TIMESTAMP    NULL,
    updated_at              TIMESTAMP    NULL,
    version                 BIGINT       NOT NULL DEFAULT 1 COMMENT 'Locking optimista',
    PRIMARY KEY (id),
    UNIQUE KEY transactions_reference_unique (reference),
    KEY transactions_status_created_at_index (status, created_at),
    KEY transactions_client_id_index (client_id),
    KEY transactions_provider_id_index (provider_id),
    CONSTRAINT transactions_client_id_foreign
        FOREIGN KEY (client_id) REFERENCES clients (id) ON DELETE RESTRICT,
    CONSTRAINT transactions_provider_id_foreign
        FOREIGN KEY (provider_id) REFERENCES payment_providers (id) ON DELETE RESTRICT,
    CONSTRAINT transactions_amount_check CHECK (amount >= 0)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE pay_ins (
    transaction_id   CHAR(36) NOT NULL,
    account_id       CHAR(36) NOT NULL COMMENT 'Cuenta destino (de quien sea)',
    payment_method_id CHAR(36) NOT NULL COMMENT 'Instrumento usado',
    fees             BIGINT   NOT NULL DEFAULT 0 COMMENT 'Unidades menores (enteras)',
    PRIMARY KEY (transaction_id),
    KEY pay_ins_account_id_index (account_id),
    KEY pay_ins_payment_method_id_index (payment_method_id),
    CONSTRAINT pay_ins_transaction_id_foreign
        FOREIGN KEY (transaction_id) REFERENCES transactions (id) ON DELETE CASCADE,
    CONSTRAINT pay_ins_account_id_foreign
        FOREIGN KEY (account_id) REFERENCES accounts (id) ON DELETE RESTRICT,
    CONSTRAINT pay_ins_payment_method_id_foreign
        FOREIGN KEY (payment_method_id) REFERENCES payment_methods (id) ON DELETE RESTRICT
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
