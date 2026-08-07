<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $documentationTitle }}</title>
    <link rel="stylesheet" type="text/css" href="{{ l5_swagger_asset($documentation, 'swagger-ui.css') }}">
    <link rel="icon" type="image/png" href="{{ l5_swagger_asset($documentation, 'favicon-32x32.png') }}" sizes="32x32"/>
    <link rel="icon" type="image/png" href="{{ l5_swagger_asset($documentation, 'favicon-16x16.png') }}" sizes="16x16"/>
    <style>
    html
    {
        box-sizing: border-box;
        overflow: -moz-scrollbars-vertical;
        overflow-y: scroll;
    }
    *,
    *:before,
    *:after
    {
        box-sizing: inherit;
    }

    body {
      margin:0;
      background: #fafafa;
    }

    /* ---------- Panel de datos de prueba (demo) ---------- */
    .guide-panel {
      max-width: 1460px;
      margin: 16px auto;
      padding: 20px 24px;
      border: 1px solid #d9dee6;
      border-radius: 10px;
      background: #ffffff;
      color: #303030;
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
      line-height: 1.55;
    }
    .guide-panel h2 { margin: 0 0 4px; font-size: 22px; color: #0b5cad; }
    .guide-panel .subtitle { margin: 0 0 16px; color: #5f6368; font-size: 14px; }
    .guide-panel h3 { margin: 20px 0 8px; font-size: 15px; color: #0b5cad; text-transform: uppercase; letter-spacing: .4px; }
    .guide-panel table { border-collapse: collapse; width: 100%; margin: 8px 0 4px; font-size: 13.5px; }
    .guide-panel th, .guide-panel td { border: 1px solid #d9dee6; padding: 7px 10px; text-align: left; vertical-align: top; }
    .guide-panel th { background: #f2f6fb; }
    .guide-panel code { background: #f0f2f5; border: 1px solid #e0e3e8; border-radius: 4px; padding: 1px 5px; font-size: 12.5px; font-family: SFMono-Regular, Consolas, monospace; word-break: break-all; }
    .guide-panel pre { background: #0d1b2a; color: #dbe9ff; border-radius: 8px; padding: 14px 16px; overflow-x: auto; font-size: 13px; }
    .guide-panel pre code { background: transparent; border: 0; color: #dbe9ff; }
    .guide-panel .mermaid { display: flex; justify-content: center; margin: 14px 0 6px; }
    #dark-mode .guide-panel {
      background: #272727;
      border-color: #444;
      color: #e7e7e7;
    }
    #dark-mode .guide-panel h2, #dark-mode .guide-panel h3 { color: #7db9f5; }
    #dark-mode .guide-panel .subtitle { color: #b0b0b0; }
    #dark-mode .guide-panel th { background: #343434; color: #e7e7e7; }
    #dark-mode .guide-panel th, #dark-mode .guide-panel td { border-color: #444; }
    #dark-mode .guide-panel code { background: #343434; border-color: #4a4a4a; color: #e7e7e7; }
    @media (max-width: 768px) { .guide-panel { margin: 8px; padding: 14px; } }
    </style>
    @if(config('l5-swagger.defaults.ui.display.dark_mode'))
        <style>
            body#dark-mode,
            #dark-mode .scheme-container {
                background: #1b1b1b;
            }
            #dark-mode .scheme-container,
            #dark-mode .opblock .opblock-section-header{
                box-shadow: 0 1px 2px 0 rgba(255, 255, 255, 0.15);
            }
            #dark-mode .operation-filter-input,
            #dark-mode .dialog-ux .modal-ux,
            #dark-mode input[type=email],
            #dark-mode input[type=file],
            #dark-mode input[type=password],
            #dark-mode input[type=search],
            #dark-mode input[type=text],
            #dark-mode textarea{
                background: #343434;
                color: #e7e7e7;
            }
            #dark-mode .title,
            #dark-mode li,
            #dark-mode p,
            #dark-mode table,
            #dark-mode label,
            #dark-mode .opblock-tag,
            #dark-mode .opblock .opblock-summary-operation-id,
            #dark-mode .opblock .opblock-summary-path,
            #dark-mode .opblock .opblock-summary-path__deprecated,
            #dark-mode h1,
            #dark-mode h2,
            #dark-mode h3,
            #dark-mode h4,
            #dark-mode h5,
            #dark-mode .btn,
            #dark-mode .tab li,
            #dark-mode .parameter__name,
            #dark-mode .parameter__type,
            #dark-mode .prop-format,
            #dark-mode .loading-container .loading:after{
                color: #e7e7e7;
            }
            #dark-mode .opblock-description-wrapper p,
            #dark-mode .opblock-external-docs-wrapper p,
            #dark-mode .opblock-title_normal p,
            #dark-mode .response-col_status,
            #dark-mode table thead tr td,
            #dark-mode table thead tr th,
            #dark-mode .response-col_links,
            #dark-mode .swagger-ui{
                color: wheat;
            }
            #dark-mode .parameter__extension,
            #dark-mode .parameter__in,
            #dark-mode .model-title{
                color: #949494;
            }
            #dark-mode table thead tr td,
            #dark-mode table thead tr th{
                border-color: rgba(120,120,120,.2);
            }
            #dark-mode .opblock .opblock-section-header{
                background: transparent;
            }
            #dark-mode .opblock.opblock-post{
                background: rgba(73,204,144,.25);
            }
            #dark-mode .opblock.opblock-get{
                background: rgba(97,175,254,.25);
            }
            #dark-mode .opblock.opblock-put{
                background: rgba(252,161,48,.25);
            }
            #dark-mode .opblock.opblock-delete{
                background: rgba(249,62,62,.25);
            }
            #dark-mode .loading-container .loading:before{
                border-color: rgba(255,255,255,10%);
                border-top-color: rgba(255,255,255,.6);
            }
            #dark-mode svg:not(:root){
                fill: #e7e7e7;
            }
            #dark-mode .opblock-summary-description {
                color: #fafafa;
            }
        </style>
    @endif
</head>

<body @if(config('l5-swagger.defaults.ui.display.dark_mode')) id="dark-mode" @endif>

<div class="guide-panel">
    <h2>&#129514; Datos de prueba</h2>
    <p class="subtitle">IDs ya sembrados en la base de datos. P&eacute;galos tal cual en <b>POST /v1/payins</b> con el bot&oacute;n <b>"Try it out"</b>.</p>

    <div class="mermaid">
flowchart LR
    E["&#128100; Usuario que env&iacute;a dinero&lt;br&gt;cuenta ORIGEN COP (saldo $100.000)"] -->|"d&eacute;bito &minus;$25.000"| P["POST /v1/payins&lt;br&gt;amount 25.000 COP"]
    P -->|"cr&eacute;dito +$25.000"| R["&#128100; Usuario que recibe&lt;br&gt;cuenta DESTINO COP (saldo $0)"]
    </div>

    <h3>Qui&eacute;n es qui&eacute;n</h3>
    <table>
        <thead>
        <tr><th>Rol</th><th>Campo del JSON</th><th>ID (c&oacute;pialo)</th></tr>
        </thead>
        <tbody>
        <tr><td><b>Usuario que env&iacute;a dinero</b> (cliente)</td><td><code>client_id</code></td><td><code>019fd715-ebf8-7223-ada8-b3c168a28e22</code></td></tr>
        <tr><td><b>Usuario que env&iacute;a dinero</b> (cuenta origen, se debita)</td><td><code>origin_account_id</code></td><td><code>019fd715-ec1a-7a7e-ab6f-f497aa52abe4</code></td></tr>
        <tr><td><b>Usuario que recibe</b> (cliente)</td><td>&mdash;</td><td><code>019fd715-ed01-7000-8000-000000000001</code></td></tr>
        <tr><td><b>Usuario que recibe</b> (cuenta destino, se acredita)</td><td><code>account_id</code></td><td><code>019fd715-ec22-700c-8cba-ea026d0fd9a9</code></td></tr>
        <tr><td>M&eacute;todo de pago: tarjeta (FakePay)</td><td><code>payment_method_id</code></td><td><code>019fd715-ec43-784b-97dd-9b2fe70bfe69</code></td></tr>
        <tr><td>M&eacute;todo de pago: PSE (SandboxPay)</td><td><code>payment_method_id</code></td><td><code>019fd715-ec50-7000-8000-000000000001</code></td></tr>
        <tr><td>M&eacute;todo de pago: Wallet (SandboxPay)</td><td><code>payment_method_id</code></td><td><code>019fd715-ec51-7000-8000-000000000002</code></td></tr>
        <tr><td>M&eacute;todo de pago: Efectivo (<code>cash</code>)</td><td><code>payment_method_id</code></td><td><code>019fd715-ec52-7000-8000-000000000003</code></td></tr>
        </tbody>
    </table>

    <h3>Ejemplo de transferencia</h3>
    <pre><code>{
  "client_id": "019fd715-ebf8-7223-ada8-b3c168a28e22",
  "origin_account_id": "019fd715-ec1a-7a7e-ab6f-f497aa52abe4",
  "account_id": "019fd715-ec22-700c-8cba-ea026d0fd9a9",
  "payment_method_id": "019fd715-ec43-784b-97dd-9b2fe70bfe69",
  "amount": 25000,
  "currency": "COP",
  "reference": "order-2026-0001"
}</code></pre>
    <p>Despu&eacute;s de la transferencia, <b>consulta las dos cuentas</b>:
        <b>primero</b> la del que env&iacute;a (se debit&oacute;) &rarr; <code>GET /v1/accounts/019fd715-ec1a-7a7e-ab6f-f497aa52abe4</code> &rarr; <code>balance: 75000</code>;
        <b>despu&eacute;s</b> la del que recibe (se acredit&oacute;) &rarr; <code>GET /v1/accounts/019fd715-ec22-700c-8cba-ea026d0fd9a9</code> &rarr; <code>balance: 25000</code>.</p>
</div>

<div id="swagger-ui"></div>

<script src="{{ l5_swagger_asset($documentation, 'swagger-ui-bundle.js') }}"></script>
<script src="{{ l5_swagger_asset($documentation, 'swagger-ui-standalone-preset.js') }}"></script>
<script>
    window.onload = function() {
        const urls = [];

        @foreach($urlsToDocs as $title => $url)
            urls.push({name: "{{ $title }}", url: "{{ $url }}"});
        @endforeach

        // Build a system
        const ui = SwaggerUIBundle({
            dom_id: '#swagger-ui',
            urls: urls,
            "urls.primaryName": "{{ $documentationTitle }}",
            operationsSorter: {!! isset($operationsSorter) ? '"' . $operationsSorter . '"' : 'null' !!},
            configUrl: {!! isset($configUrl) ? '"' . $configUrl . '"' : 'null' !!},
            validatorUrl: {!! isset($validatorUrl) ? '"' . $validatorUrl . '"' : 'null' !!},
            oauth2RedirectUrl: "{{ route('l5-swagger.'.$documentation.'.oauth2_callback', [], $useAbsolutePath) }}",

            requestInterceptor: function(request) {
                request.headers['X-CSRF-TOKEN'] = '{{ csrf_token() }}';
                return request;
            },

            presets: [
                SwaggerUIBundle.presets.apis,
                SwaggerUIStandalonePreset
            ],

            plugins: [
                SwaggerUIBundle.plugins.DownloadUrl
            ],

            layout: "StandaloneLayout",
            docExpansion : "{!! config('l5-swagger.defaults.ui.display.doc_expansion', 'none') !!}",
            deepLinking: true,
            filter: {!! config('l5-swagger.defaults.ui.display.filter') ? 'true' : 'false' !!},
            persistAuthorization: "{!! config('l5-swagger.defaults.ui.authorization.persist_authorization') ? 'true' : 'false' !!}",

        })

        window.ui = ui

        @if(in_array('oauth2', array_column(config('l5-swagger.defaults.securityDefinitions.securitySchemes'), 'type')))
        ui.initOAuth({
            usePkceWithAuthorizationCodeGrant: "{!! (bool)config('l5-swagger.defaults.ui.authorization.oauth2.use_pkce_with_authorization_code_grant') !!}"
        })
        @endif
    }
</script>

<script src="https://cdn.jsdelivr.net/npm/mermaid@11/dist/mermaid.min.js"></script>
<script>
    if (window.mermaid) {
        mermaid.initialize({
            startOnLoad: true,
            theme: {{ config('l5-swagger.defaults.ui.display.dark_mode') ? "'dark'" : "'default'" }}
        });
    }
</script>
</body>
</html>
