<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?> - Iniciar Sesión</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
            width: 100%;
            max-width: 400px;
        }
        .login-header {
            background: #fff;
            padding: 30px 20px 20px;
            text-align: center;
        }
        .login-header i {
            font-size: 3rem;
            color: #28a745;
            margin-bottom: 15px;
        }
        .login-body {
            padding: 20px 30px 40px;
        }
        .form-control {
            border-radius: 8px;
            padding: 12px;
            background: #f8f9fa;
        }
        .btn-login {
            border-radius: 8px;
            padding: 12px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s;
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="login-header">
        <i class="fa-solid fa-futbol"></i>
        <h4 class="fw-bold mb-0"><?= htmlspecialchars($app_league_display_name ?? 'LIGA') ?></h4>
        <p class="text-muted small">Panel de Administración</p>
    </div>
    
    <div class="login-body">
        <?php if(!empty($error)): ?>
            <div class="alert alert-danger py-2 small rounded-3">
                <i class="fa-solid fa-circle-exclamation me-1"></i> <?= $error ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?= BASE_URL ?>login">
            <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect ?? '') ?>">
            <div class="mb-3">
                <label class="form-label text-muted small fw-bold">Usuario</label>
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="fa-solid fa-user text-muted"></i></span>
                    <input type="text" name="username" class="form-control" placeholder="Usuario" required autocomplete="username">
                </div>
            </div>
            <div class="mb-4">
                <label class="form-label text-muted small fw-bold">Contraseña</label>
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="fa-solid fa-lock text-muted"></i></span>
                    <input type="password" name="password" class="form-control" placeholder="••••••••" required autocomplete="current-password">
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-login w-100 mt-2">
                Ingresar al Sistema
            </button>
        </form>
    </div>
</div>

</body>
</html>
