<?php
session_start();
if (isset($_SESSION['usuario_dni'])) {
    header('Location: index.php');
    exit;
}

function normalizar_texto($texto) {
    $texto = mb_strtoupper(trim($texto), 'UTF-8');
    $buscar  = ['Á', 'É', 'Í', 'Ó', 'Ú', 'Ü'];
    $reempl  = ['A', 'E', 'I', 'O', 'U', 'U'];
    return str_replace($buscar, $reempl, $texto);
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dni_ingresado = str_pad(preg_replace('/\D/', '', $_POST['usuario'] ?? ''), 8, '0', STR_PAD_LEFT);
    $pass_ingresada = normalizar_texto($_POST['password'] ?? '');

    $ruta_json = __DIR__ . '/personal_lista.json';

    if (!file_exists($ruta_json)) {
        $error = 'Base de personal no disponible en el servidor.';
    } else {
        $data = json_decode(file_get_contents($ruta_json), true);
        $personal = $data['personal'] ?? [];
        $autorizado = null;

        foreach ($personal as $persona) {
            $dni_bd = str_pad(trim($persona['DNI'] ?? ''), 8, '0', STR_PAD_LEFT);
            if ($dni_bd === $dni_ingresado) {
                $linea = strtoupper(trim($persona['LINEA'] ?? ''));
                if ($linea !== 'STAFF') {
                    $error = 'Acceso denegado: solo el personal con línea STAFF está autorizado.';
                    break;
                }

                // Extraer el primer apellido (primera palabra de NOMBRE)
                $nombre_completo = trim($persona['NOMBRE'] ?? '');
                $partes = preg_split('/\s+/', $nombre_completo);
                $primer_apellido = normalizar_texto($partes[0] ?? '');

                $clave_esperada = $dni_bd . $primer_apellido;

                if ($pass_ingresada === $clave_esperada && $primer_apellido !== '') {
                    $autorizado = $persona;
                } else {
                    $error = 'Contraseña incorrecta. Recuerda: DNI + Primer Apellido (ej. 76728017ALCEDO).';
                }
                break;
            }
        }

        if ($autorizado) {
            session_regenerate_id(true);
            $_SESSION['usuario_dni'] = $dni_ingresado;
            $_SESSION['nombre']      = $autorizado['NOMBRE'];
            $_SESSION['cargo']       = $autorizado['CARGO'] ?? 'STAFF';
            header('Location: index.php');
            exit;
        } elseif ($error === '') {
            $error = 'El número de DNI ingresado no figura en la base de personal.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Acceso STAFF | Portal de Certificados ISEM</title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
      background: #0f172a;
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
      padding: 1rem;
    }
    .login-card {
      background: #ffffff;
      width: 100%;
      max-width: 410px;
      padding: 2rem;
      border-radius: 12px;
      box-shadow: 0 10px 25px rgba(0,0,0,0.35);
    }
    .login-card h1 { font-size: 1.35rem; color: #0f172a; margin-bottom: 0.3rem; }
    .login-card p { font-size: 0.875rem; color: #64748b; margin-bottom: 1.5rem; }
    .field { margin-bottom: 1rem; }
    .field label { display: block; font-size: 0.85rem; font-weight: 600; color: #1e293b; margin-bottom: 0.35rem; }
    .field input {
      width: 100%;
      padding: 0.7rem 0.85rem;
      border: 1px solid #cbd5e1;
      border-radius: 8px;
      font-size: 0.95rem;
    }
    .field input:focus { outline: none; border-color: #2563eb; }
    .hint { font-size: 0.78rem; color: #64748b; margin-top: 0.3rem; }
    .btn {
      width: 100%;
      padding: 0.75rem;
      background: #2563eb;
      color: #fff;
      border: none;
      border-radius: 8px;
      font-size: 0.95rem;
      font-weight: 600;
      cursor: pointer;
      margin-top: 0.5rem;
    }
    .btn:hover { background: #1d4ed8; }
    .alert {
      background: #fee2e2;
      color: #991b1b;
      padding: 0.75rem;
      border-radius: 8px;
      font-size: 0.85rem;
      margin-bottom: 1rem;
      line-height: 1.4;
    }
  </style>
</head>
<body>
  <div class="login-card">
    <h1>Consulta Certificados ISEM</h1>
    <p>Mantenimiento y Supervisión S.A. — Acceso exclusivo personal STAFF</p>
    <?php if ($error): ?>
      <div class="alert"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="POST" autocomplete="off">
      <div class="field">
        <label for="usuario">Usuario (Número de DNI)</label>
        <input type="text" id="usuario" name="usuario" maxlength="8" placeholder="Ej. 76728017" required autofocus />
      </div>
      <div class="field">
        <label for="password">Contraseña</label>
        <input type="password" id="password" name="password" placeholder="DNI + Primer Apellido" required />
        <div class="hint">Formato: DNI seguido de tu primer apellido sin espacios (ej. 76728017ALCEDO)</div>
      </div>
      <button class="btn" type="submit">Ingresar</button>
    </form>
  </div>
</body>
</html>