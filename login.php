<?php
/**
 * Login Administrativo
 */

require_once __DIR__ . '/db.php'; // Isso já inclui config.php e inicia a sessão

// Redireciona se já estiver logado
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Por favor, insira o usuário e a senha.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM `admins` WHERE `username` = ?");
            $stmt->execute([$username]);
            $admin = $stmt->fetch();
            
            if ($admin && password_verify($password, $admin['password'])) {
                // Login com sucesso!
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_user'] = $admin['username'];
                $_SESSION['admin_name'] = $admin['name'];
                
                header('Location: index.php');
                exit;
            } else {
                $error = 'Usuário ou senha incorretos.';
            }
        } catch (PDOException $e) {
            $error = 'Erro no banco de dados: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo APP_NAME; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: radial-gradient(circle at top right, #111827, #030712);
        }
        .login-card {
            animation: slideUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
        .logo-container {
            display: flex;
            justify-content: center;
            margin-bottom: 1.5rem;
        }
        .logo {
            width: 54px;
            height: 54px;
            background: linear-gradient(135deg, var(--primary), var(--info));
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 0 20px var(--primary-glow);
        }
        .logo svg {
            width: 30px;
            height: 30px;
            fill: white;
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="login-header">
        <div class="logo-container">
            <div class="logo">
                <svg viewBox="0 0 24 24"><path d="M12,17A2,2 0 0,0 14,15C14,14.21 13.54,13.53 12.88,13.21L13,11H15V9H13V7H11V9H9V11H11L11.12,13.21C10.46,13.53 10,14.21 10,15A2,2 0 0,0 12,17M20,6H12L10,4H4A2,2 0 0,0 2,6V18A2,2 0 0,0 4,20H20A2,2 0 0,0 22,18V8A2,2 0 0,0 20,6Z"/></svg>
            </div>
        </div>
        <h1 style="font-size: 1.85rem; color: white; margin-bottom: 0.5rem;"><?php echo APP_NAME; ?></h1>
        <p style="color: var(--text-secondary); font-size: 0.95rem;">Painel de Controle de Backups</p>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form action="" method="POST">
        <div class="form-group">
            <label for="username">Usuário</label>
            <input type="text" id="username" name="username" class="form-control" placeholder="admin" required autofocus autocomplete="username">
        </div>
        
        <div class="form-group" style="margin-bottom: 1.75rem;">
            <label for="password">Senha</label>
            <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required autocomplete="current-password">
        </div>
        
        <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; padding: 0.85rem;">
            Acessar Painel
        </button>
    </form>
</div>

</body>
</html>
