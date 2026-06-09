<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configura tu Contraseña | BarberOS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        :root {
            --bg-body: #0a0a0b;
            --bg-card: rgba(24, 24, 27, 0.7);
            --border-color: rgba(255, 255, 255, 0.08);
            --text-primary: #f4f4f5;
            --text-secondary: #a1a1aa;
            --accent: #eab308;
            --accent-hover: #ca8a04;
            --red: #ef4444;
        }

        body {
            margin: 0;
            padding: 0;
            background-color: var(--bg-body);
            background-image: 
                radial-gradient(circle at 15% 50%, rgba(234, 179, 8, 0.04), transparent 25%),
                radial-gradient(circle at 85% 30%, rgba(234, 179, 8, 0.04), transparent 25%);
            font-family: 'Inter', sans-serif;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }

        .login-container {
            width: 100%;
            max-width: 420px;
            padding: 24px;
            box-sizing: border-box;
            animation: fadeUp 0.6s ease forwards;
            opacity: 0;
        }

        .logo {
            text-align: center;
            font-size: 1.8rem;
            font-weight: 800;
            margin-bottom: 8px;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }
        .logo i { color: var(--accent); }
        
        .subtitle {
            text-align: center;
            color: var(--text-secondary);
            font-size: 0.95rem;
            margin-bottom: 32px;
            line-height: 1.5;
        }

        .login-card {
            background: var(--bg-card);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            padding: 32px 28px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--text-secondary);
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .form-control {
            width: 100%;
            padding: 12px 16px;
            background: rgba(0, 0, 0, 0.2);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            color: var(--text-primary);
            font-size: 0.95rem;
            box-sizing: border-box;
            transition: all 0.3s ease;
        }
        .form-control:focus {
            outline: none;
            border-color: var(--accent);
            background: rgba(0, 0, 0, 0.4);
            box-shadow: 0 0 0 3px rgba(234, 179, 8, 0.1);
        }

        .btn-submit {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, var(--accent) 0%, #d97706 100%);
            color: #000;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(234, 179, 8, 0.3);
        }

        .error-msg {
            color: var(--red);
            font-size: 0.85rem;
            margin-top: 6px;
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

    <div class="login-container">
        <div class="logo">
            <i data-lucide="shield-check" style="width:32px;height:32px;"></i> BarberOS
        </div>
        <p class="subtitle">
            Por seguridad, debes configurar una contraseña nueva para <strong>{{ auth()->user()->email }}</strong>
        </p>

        <div class="login-card">
            <form action="{{ route('password.setup') }}" method="POST">
                @csrf
                
                <div class="form-group">
                    <label class="form-label">Nueva Contraseña</label>
                    <input type="password" name="password" class="form-control" placeholder="Mínimo 8 caracteres" required autofocus>
                    @error('password')
                        <div class="error-msg">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Confirmar Contraseña</label>
                    <input type="password" name="password_confirmation" class="form-control" placeholder="Escribe tu contraseña de nuevo" required>
                </div>

                <button type="submit" class="btn-submit">Guardar Contraseña</button>
            </form>
        </div>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
