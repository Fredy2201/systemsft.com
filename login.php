<?php
session_start();
if (isset($_SESSION['usuario_dni'])) {
    header('Location: index.php');
    exit;
}

function normalizar_texto($texto) {$texto = mb_strtoupper(trim($texto), 'UTF-8');$buscar  = ['Á', 'É', 'Í', 'Ó', 'Ú', 'Ü'];
    $reempl  = ['A', 'E', 'I', 'O', 'U', 'U'];
    return str_replace($buscar, $reempl,$texto);
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dni_ingresado = str_pad(preg_replace('/\D/', '',$_POST['usuario'] ?? ''), 8, '0', STR_PAD_LEFT);
    $pass_ingresada = normalizar_texto($_POST['password'] ?? '');

    $ruta_json = __DIR__ . '/personal_lista.json';

    if (!file_exists($ruta_json)) {$error = 'Base de personal no disponible en el servidor.';
    } else {
        $data = json_decode(file_get_contents($ruta_json), true);$personal = $data['personal'] ?? [];$autorizado = null;

        foreach ($personal as$persona) {
            $dni_bd = str_pad(trim($persona['DNI'] ?? ''), 8, '0', STR_PAD_LEFT);
            if ($dni_bd ===$dni_ingresado) {
                $linea = strtoupper(trim($persona['LINEA'] ?? ''));
                if ($linea !== 'STAFF') {$error = 'Acceso denegado: solo el personal con línea STAFF está autorizado.';
                    break;
                }

                $nombre_completo = trim($persona['NOMBRE'] ?? '');
                $partes = preg_split('/\s+/',$nombre_completo);
                $primer_apellido = normalizar_texto($partes[0] ?? '');

                $clave_esperada = $dni_bd .$primer_apellido;

                if ($pass_ingresada === $clave_esperada &&$primer_apellido !== '') {
                    $autorizado =$persona;
                } else {
                    $error = 'Contraseña incorrecta. Recuerda: DNI + Primer Apellido (ej. 76728017ALCEDO).';
                }
                break;
            }
        }

        if ($autorizado) {
            session_regenerate_id(true);
            $_SESSION['usuario_dni'] =$dni_ingresado;
            $_SESSION['nombre']      =$autorizado['NOMBRE'];
            $_SESSION['cargo']       =$autorizado['CARGO'] ?? 'STAFF';
            header('Location: index.php');
            exit;
        } elseif ($error === '') {$error = 'El número de DNI ingresado no figura en la base de personal.';
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
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <style>
    body {
      background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
      min-height: 100vh;
    }
  </style>
</head>
<body class="d-flex align-items-center justify-content-center p-3">

  <div class="card shadow-lg border-0 rounded-4" style="max-width: 430px; width: 100%;">
    <div class="card-body p-4 p-md-5">
      <div class="text-center mb-4">
        <div class="d-inline-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-circle mb-3" style="width: 60px; height: 60px;">
          <i class="bi bi-shield-lock-fill fs-2"></i>
        </div>
        <h1 class="h4 fw-bold text-dark mb-1">Certificados ISEM</h1>
        <p class="text-secondary small mb-0">Mantenimiento y Supervisión S.A. • Acceso STAFF</p>
      </div>

      <?php if ($error): ?>
        <div class="alert alert-danger d-flex align-items-start gap-2 small py-2 px-3 rounded-3" role="alert">
          <i class="bi bi-exclamation-triangle-fill mt-1"></i>
          <div><?= htmlspecialchars($error) ?></div>
        </div>
      <?php endif; ?>

      <form method="POST" autocomplete="off">
        <div class="mb-3">
          <label for="usuario" class="form-label fw-semibold small text-secondary">Usuario (Número de DNI)</label>
          <div class="input-group">
            <span class="input-group-text bg-light"><i class="bi bi-person-badge text-secondary"></i></span>
            <input type="text" class="form-control" id="usuario" name="usuario" maxlength="8" placeholder="Ej. 76728017" required autofocus />
          </div>
        </div>

        <div class="mb-4">
          <label for="password" class="form-label fw-semibold small text-secondary">Contraseña</label>
          <div class="input-group">
            <span class="input-group-text bg-light"><i class="bi bi-key-fill text-secondary"></i></span>
            <input type="password" class="form-control" id="password" name="password" placeholder="DNI + Primer Apellido" required />
            <button class="btn btn-outline-secondary" type="button" id="togglePass" title="Mostrar/Ocultar">
              <i class="bi bi-eye" id="eyeIcon"></i>
            </button>
          </div>
          <div class="form-text mt-1">
            Formato: <strong>DNI + Primer Apellido</strong> sin espacios (ej. <code>76728017ALCEDO</code>).
          </div>
        </div>

        <button class="btn btn-primary w-100 py-2 fw-semibold rounded-3 shadow-sm" type="submit">
          <i class="bi bi-box-arrow-in-right me-1"></i> Ingresar al Portal
        </button>
      </form>
    </div>
    <div class="card-footer bg-light text-center py-3 border-0 rounded-bottom-4">
      <small class="text-muted">Área de Recursos Humanos • Condorcocha</small>
    </div>
  </div>

  <script>
    const togglePass = document.getElementById('togglePass');
    const passInput = document.getElementById('password');
    const eyeIcon = document.getElementById('eyeIcon');

    togglePass.addEventListener('click', () => {
      const isPassword = passInput.type === 'password';
      passInput.type = isPassword ? 'text' : 'password';
      eyeIcon.className = isPassword ? 'bi bi-eye-slash' : 'bi bi-eye';
    });
  </script>
</body>
</html>