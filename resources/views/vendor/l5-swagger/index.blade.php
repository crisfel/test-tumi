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

    /* ---------- Panel de guía rápida (demo) ---------- */
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
    .guide-panel ol, .guide-panel ul { margin: 8px 0; padding-left: 22px; }
    .guide-panel li { margin: 6px 0; }
    .guide-panel .steps b { color: #0b5cad; }
    .guide-panel .flow { text-align: center; margin: 10px 0; }
    .guide-panel .flow pre { display: inline-block; text-align: left; }
    .guide-panel .state-pill { display: inline-block; padding: 2px 10px; border-radius: 999px; font-size: 12px; font-weight: 600; }
    .state-pill.created { background: #e3f2fd; color: #0b5cad; }
    .state-pill.validated { background: #fff3cd; color: #856404; }
    .state-pill.processing { background: #fce4ec; color: #ad1457; }
    .state-pill.processed { background: #d4edda; color: #155724; }
    .state-pill.failed { background: #f8d7da; color: #721c24; }
    .guide-panel .tip { border-left: 4px solid #ffb300; background: #fff8e1; padding: 10px 14px; border-radius: 0 6px 6px 0; margin: 12px 0; font-size: 13.5px; }
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
    #dark-mode .guide-panel .tip { background: #3d3626; border-color: #ffb300; color: #e7e7e7; }
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
    <h2>&#129514; Guía rápida: transfiere dinero de Ana a Pedro</h2>
    <p class="subtitle">Sigue los pasos en orden. <b>No necesitas escribir nada</b>: todos los IDs ya están sembrados en la base de datos y los ejemplos de esta documentación los usan tal cual.</p>

    <h3>1&deg; Quién es quién (datos de la demo)</h3>
    <table>
        <thead>
        <tr><th>Personaje</th><th>Qué es</th><th>ID (cópialo)</th></tr>
        </thead>
        <tbody>
        <tr><td><b>Ana García</b></td><td>Cliente que <b>paga</b> (de su cuenta sale el dinero)</td><td><code>019fd715-ebf8-7223-ada8-b3c168a28e22</code></td></tr>
        <tr><td>Cuenta COP de Ana</td><td>Cuenta <b>ORIGEN</b> &mdash; saldo <b>$100.000</b> (se <b>debita</b>)</td><td><code>019fd715-ec1a-7a7e-ab6f-f497aa52abe4</code></td></tr>
        <tr><td><b>Pedro Pérez</b></td><td>Cliente que <b>recibe</b> (a su cuenta llega el dinero)</td><td><code>019fd715-ed01-7000-8000-000000000001</code></td></tr>
        <tr><td>Cuenta COP de Pedro</td><td>Cuenta <b>DESTINO</b> &mdash; saldo <b>$0</b> (se <b>acredita</b>)</td><td><code>019fd715-ec22-700c-8cba-ea026d0fd9a9</code></td></tr>
        <tr><td>Tarjeta Visa (FakePay)</td><td>Método de pago</td><td><code>019fd715-ec43-784b-97dd-9b2fe70bfe69</code></td></tr>
        <tr><td>Efectivo (<code>cash</code>)</td><td>Método de pago con éxito inmediato</td><td><code>019fd715-ec52-7000-8000-000000000003</code></td></tr>
        </tbody>
    </table>

    <h3>2&deg; Cómo fluye el dinero</h3>
    <div class="flow">
<pre>
Ana (paga)                     PayIn &mdash; POST /api/v1/payins                  Pedro (recibe)
&#9484;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9488;        &#9484;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9488;        &#9484;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9488;
&#9474; Cuenta COP de Ana   &#9474;  -25000  &#9474;   amount: 25000   &#9474;  +25000  &#9474; Cuenta COP de Pedro &#9474;
&#9474; saldo 100.000        &#9474; &#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472; &#9474;   currency: COP    &#9474; &#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472; &#9474; saldo 0             &#9474;
&#9492;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9498;        &#9492;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9498;        &#9492;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9472;&#9498;
</pre>
    </div>

    <h3>3&deg; Paso a paso (en Swagger, botón "Try it out")</h3>
    <ol class="steps">
        <li>Abre la operación <b>POST /api/v1/payins</b> y haz clic en <b>"Try it out"</b>.</li>
        <li>Pega este JSON y haz clic en <b>"Execute"</b>:
<pre><code>{
  "client_id": "019fd715-ebf8-7223-ada8-b3c168a28e22",
  "origin_account_id": "019fd715-ec1a-7a7e-ab6f-f497aa52abe4",
  "account_id": "019fd715-ec22-700c-8cba-ea026d0fd9a9",
  "payment_method_id": "019fd715-ec43-784b-97dd-9b2fe70bfe69",
  "amount": 25000,
  "currency": "COP",
  "reference": "order-2026-0001"
}</code></pre></li>
        <li>Mira la respuesta: c&oacute;digo <b>201</b>, <code>"status": "processed"</code> y el <code>"id"</code> del PayIn. &#9989;</li>
        <li><b>&iquest;Baj&oacute; el dinero de Ana?</b> Abre <code>GET /api/v1/accounts/{id_cuenta_ana}</code> &rarr; <code>"balance": 75000</code> (antes $100.000).</li>
        <li><b>&iquest;Subi&oacute; el de Pedro?</b> Abre <code>GET /api/v1/accounts/{id_cuenta_pedro}</code> &rarr; <code>"balance": 25000</code> (antes $0).</li>
        <li><b>Mira el extracto de cada cuenta:</b> <code>GET /api/v1/accounts/{id}/movements</code> &rarr; ver&aacute;s un movimiento <code>"type": "debit"</code> en Ana y uno <code>"type": "credit"</code> en Pedro, cada uno con su <code>balance_after</code> y su <code>pay_in_id</code>.</li>
        <li><b>Consulta la transacci&oacute;n:</b> copia el <code>"id"</code> del paso 3 en <code>GET /api/v1/payins/{id}</code>.</li>
        <li><b>Historial y estados:</b> <code>GET /api/v1/payins?client_id={id_ana}</code> (historial de Ana) y <code>GET /api/v1/payins?status=processed</code>.</li>
    </ol>

    <h3>4&deg; Los estados del PayIn</h3>
    <p>
        Cada PayIn recorre <span class="state-pill created">CREATED</span> &rarr;
        <span class="state-pill validated">VALIDATED</span> &rarr;
        <span class="state-pill processing">PROCESSING</span> &rarr;
        <span class="state-pill processed">PROCESSED</span> (o <span class="state-pill failed">FAILED</span>).
        Como la API es <b>s&iacute;ncrona</b>, esos pasos son instant&aacute;neos y solo queda persistido el estado final.
    </p>
    <ul>
        <li><code>GET /api/v1/payins?status=processed</code> &rarr; los exitosos (paso 2).</li>
        <li><b>&iquest;Quieres ver un <span class="state-pill failed">FAILED</span>?</b> Pon <code>PAYIN_FAKEPAY_BEHAVIOR=rejected</code> en tu <code>.env</code>, reinicia el contenedor (<code>docker compose restart php</code>) y crea otro PayIn con la tarjeta Visa &rarr; la respuesta ser&aacute; <code>201</code> con <code>"status": "failed"</code> y <code>"error_code": "PROVIDER_REJECTED"</code>. Los saldos <b>no</b> cambian.</li>
        <li>Luego lo ves con <code>GET /api/v1/payins?status=failed</code>.</li>
    </ul>

    <div class="tip">
        <b>Ojo con la referencia:</b> si ejecutas el mismo <code>reference</code> dos veces, la segunda devuelve <code>409 REFERENCE_ALREADY_USED</code>. Es la protecci&oacute;n contra dobles cobros (idempotencia).
    </div>
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
</body>
</html>
