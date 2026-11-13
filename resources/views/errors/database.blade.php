{{--
    Página mostrada cuando no hay conexión con la base de datos.
    No usa sesión, CSRF, auth ni Vite: debe renderizarse aunque todo falle.
--}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Sistema no disponible · {{ config('app.name') }}</title>
    <style>
        :root { color-scheme: dark; }
        * { box-sizing: border-box; }
        body {
            margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 16px;
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            background: radial-gradient(ellipse at top, #0f2a2a 0%, #0b1220 55%);
            color: #e2e8f0;
        }
        .card {
            width: 100%; max-width: 440px; padding: 32px 28px; text-align: center;
            border: 1px solid rgba(255,255,255,.08); border-radius: 16px;
            background: rgba(255,255,255,.04);
        }
        .icon {
            width: 56px; height: 56px; margin: 0 auto 18px; display: grid; place-items: center;
            border-radius: 14px; background: rgba(251,191,36,.12); color: #fbbf24;
        }
        h1 { margin: 0 0 8px; font-size: 20px; }
        p { margin: 0 0 6px; font-size: 14px; line-height: 1.55; color: #94a3b8; }
        .btn {
            display: inline-block; margin-top: 20px; padding: 10px 18px; border-radius: 10px;
            background: #00d4aa; color: #04201b; font-weight: 600; font-size: 14px; text-decoration: none;
        }
        .btn:hover { background: #19e0b9; }
        .estado { margin-top: 14px; font-size: 12px; color: #64748b; }
        .debug {
            margin-top: 20px; padding: 10px 12px; border-radius: 10px; text-align: left;
            background: rgba(244,63,94,.08); color: #fda4af; font-size: 12px; line-height: 1.5;
        }
    </style>
</head>
<body>
    <main class="card">
        <div class="icon" aria-hidden="true">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/><path d="M3 12c0 1.66 4 3 9 3s9-1.34 9-3"/>
            </svg>
        </div>
        <h1>Sistema no disponible por el momento</h1>
        <p>No pudimos conectar con la base de datos. Tus datos están a salvo.</p>
        <p>Intenta nuevamente en unos minutos.</p>

        <a class="btn" href="{{ url('/login') }}">Ir al inicio de sesión</a>
        <div class="estado" id="estado">Reintentando automáticamente…</div>

        @if ($codigo)
            @php
                $causas = [
                    1130 => 'El servidor rechazó el host. Suele ser la tabla de usuarios de MariaDB dañada (mysql.global_priv).',
                    1045 => 'Usuario o contraseña incorrectos: revisa DB_USERNAME y DB_PASSWORD en el .env.',
                    1044 => 'El usuario no tiene acceso a la base de datos indicada en DB_DATABASE.',
                    1049 => 'La base de datos indicada en DB_DATABASE no existe.',
                    2002 => 'MySQL/MariaDB está apagado o no escucha en DB_HOST:DB_PORT.',
                    2003 => 'MySQL/MariaDB está apagado o no escucha en DB_HOST:DB_PORT.',
                    2005 => 'No se reconoce el host indicado en DB_HOST.',
                    2006 => 'El servidor cerró la conexión (posible caída o reinicio).',
                    2013 => 'Se perdió la conexión con el servidor (posible caída o reinicio).',
                    1040 => 'El servidor alcanzó el máximo de conexiones.',
                ];
            @endphp
            <div class="debug">
                <strong>Solo visible con APP_DEBUG=true</strong><br>
                Error {{ $codigo }}: {{ $causas[$codigo] ?? 'Error de conexión con la base de datos.' }}
            </div>
        @endif
    </main>

    <script>
        // Comprueba cada 15 s si el sistema volvió; en cuanto el login
        // responde bien, redirige allí.
        (function () {
            var estado = document.getElementById('estado');
            var segundos = 15;

            function comprobar() {
                fetch(@json(url('/login')), { method: 'GET', cache: 'no-store', credentials: 'same-origin' })
                    .then(function (r) {
                        if (r.ok) { window.location.href = @json(url('/login')); }
                        else { programar(); }
                    })
                    .catch(programar);
            }

            function programar() {
                var restante = segundos;
                estado.textContent = 'Reintentando en ' + restante + ' s…';
                var t = setInterval(function () {
                    restante--;
                    if (restante <= 0) {
                        clearInterval(t);
                        estado.textContent = 'Comprobando conexión…';
                        comprobar();
                    } else {
                        estado.textContent = 'Reintentando en ' + restante + ' s…';
                    }
                }, 1000);
            }

            programar();
        })();
    </script>
</body>
</html>
