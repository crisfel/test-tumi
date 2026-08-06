-- ============================================================================
-- PayIn Platform — Datos iniciales (seed de referencia)
-- ============================================================================
-- Catálogo de proveedores con su matriz de capacidades (supported_types) y
-- datos de demostración. Ejecutar DESPUÉS de schema.sql.
-- ============================================================================

-- Proveedores de pago (matriz de capacidades)
INSERT INTO payment_providers (id, code, name, is_active, supported_types, configuration, created_at, updated_at)
VALUES
    ('019fd715-eb24-7683-b8d2-9d83ffdca22d', 'fakepay',    'FakePay',    1, JSON_ARRAY('card'),                                        JSON_OBJECT(), NOW(), NOW()),
    ('019fd715-ebc4-7cba-b4f3-e11ad17a0557', 'sandboxpay', 'SandboxPay', 1, JSON_ARRAY('card', 'bank_transfer', 'wallet', 'pse'),      JSON_OBJECT(), NOW(), NOW()),
    ('019fd715-ec60-7f2a-9d5b-a1c2d3e4f5a6', 'cash',       'Efectivo',   1, JSON_ARRAY('cash'),                                       JSON_OBJECT(), NOW(), NOW());

-- Cliente de demostración
INSERT INTO clients (id, name, email, created_at, updated_at)
VALUES ('019fd715-ebf8-7223-ada8-b3c168a28e22', 'Ana García', 'ana.garcia@example.com', NOW(), NOW());

-- Cuentas (una por moneda)
INSERT INTO accounts (id, client_id, currency, balance, created_at, updated_at)
VALUES
    ('019fd715-ec1a-7a7e-ab6f-f497aa52abe4', '019fd715-ebf8-7223-ada8-b3c168a28e22', 'COP', 0, NOW(), NOW()),
    ('019fd715-ec22-700c-8cba-ea026d0fd9a9', '019fd715-ebf8-7223-ada8-b3c168a28e22', 'USD', 0, NOW(), NOW());

-- Métodos de pago (instrumentos independientes, token único por proveedor)
INSERT INTO payment_methods (id, provider_id, type, token, details_masked, is_active, created_at, updated_at)
VALUES
    ('019fd715-ec43-784b-97dd-9b2fe70bfe69', '019fd715-eb24-7683-b8d2-9d83ffdca22d', 'card',   'tok_card_visa_4242', '**** 4242',          1, NOW(), NOW()),
    ('019fd715-ec4b-7528-a83c-a9f92f214fdc', '019fd715-ebc4-7cba-b4f3-e11ad17a0557', 'pse',    'tok_pse_banco_001',  'Banco Demo S.A.',    1, NOW(), NOW()),
    ('019fd715-ec53-725d-aaf5-2b3932e85082', '019fd715-ebc4-7cba-b4f3-e11ad17a0557', 'wallet', 'tok_wallet_usr_999', 'wallet@ana.example', 1, NOW(), NOW()),
    ('019fd715-ec6a-71b9-a4e8-b5f6c7d8e9f0', '019fd715-ec60-7f2a-9d5b-a1c2d3e4f5a6', 'cash',   'tok_cash_0001',      'Efectivo',           1, NOW(), NOW());
