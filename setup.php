<?php
/**
 * Instalação e Configuração Inicial do Banco de Dados
 */

require_once __DIR__ . '/config.php';

$lock_file = __DIR__ . '/.setup_lock';
$error = '';
$success = '';

// Verifica se o setup já foi executado
if (file_exists($lock_file)) {
    // Se o banco já existe, redireciona para o login
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'setup') {
        $admin_user = trim($_POST['admin_user'] ?? '');
        $admin_pass = $_POST['admin_pass'] ?? '';
        $admin_name = trim($_POST['admin_name'] ?? '');
        
        if (empty($admin_user) || empty($admin_pass) || empty($admin_name)) {
            $error = 'Por favor, preencha todos os campos do administrador.';
        } else {
            try {
                // 1. Tenta conectar ao servidor MySQL (sem selecionar banco de dados ainda)
                $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=utf8mb4";
                $options = [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ];
                $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
                
                // 2. Executa o schema.sql
                $schema_file = __DIR__ . '/schema.sql';
                if (!file_exists($schema_file)) {
                    throw new Exception('O arquivo schema.sql não foi encontrado no servidor.');
                }
                
                $sql = file_get_contents($schema_file);
                
                // Executa as queries
                $pdo->exec($sql);
                
                // 3. Conecta agora no banco de dados criado
                $pdo->exec("USE `" . DB_NAME . "`");
                
                // 4. Verifica se o administrador já existe
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM `admins` WHERE `username` = ?");
                $stmt->execute([$admin_user]);
                if ($stmt->fetchColumn() == 0) {
                    // Criptografa a senha usando password_hash padrão do PHP (seguro para o painel web)
                    $hashed_pass = password_hash($admin_pass, PASSWORD_DEFAULT);
                    
                    $stmt = $pdo->prepare("INSERT INTO `admins` (`username`, `password`, `name`) VALUES (?, ?, ?)");
                    $stmt->execute([$admin_user, $hashed_pass, $admin_name]);
                }
                
                // 5. Cria a pasta de storage de simulação de backups se ela não existir
                $storage_path = FTP_BASE_DIR_LOCAL_PREFIX;
                if (!file_exists($storage_path)) {
                    mkdir($storage_path, 0777, true);
                }
                
                // 6. Grava o arquivo lock para evitar re-execução
                file_put_contents($lock_file, date('Y-m-d H:i:s') . " - Setup completo.");
                
                $success = 'Sistema configurado com sucesso! Redirecionando para o login...';
                
                // Redireciona em 3 segundos
                header('refresh:3;url=login.php');
                
            } catch (PDOException $e) {
                $error = 'Erro de Banco de Dados: ' . $e->getMessage() . '. Verifique o arquivo config.php.';
            } catch (Exception $e) {
                $error = 'Erro Geral: ' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalação do Sistema - <?php echo APP_NAME; ?></title>
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
        .setup-card {
            background: var(--bg-card);
            backdrop-filter: blur(16px);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            padding: 3rem;
            width: 100%;
            max-width: 550px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
            animation: slideUp 0.6s ease-out;
        }
        .setup-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .setup-logo {
            display: inline-flex;
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--primary), var(--info));
            border-radius: 14px;
            align-items: center;
            justify-content: center;
            box-shadow: 0 0 20px var(--primary-glow);
            margin-bottom: 1.5rem;
        }
        .setup-logo svg {
            width: 28px;
            height: 28px;
            fill: white;
        }
        .setup-header h1 {
            font-size: 1.85rem;
            color: white;
            margin-bottom: 0.5rem;
        }
        .setup-header p {
            color: var(--text-secondary);
            font-size: 0.95rem;
        }
        .db-info-pill {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1.5rem;
            font-size: 0.85rem;
        }
        .db-info-pill table {
            width: 100%;
            border-collapse: collapse;
        }
        .db-info-pill td {
            padding: 0.25rem 0;
            border: none;
        }
        .db-info-pill td.label {
            color: var(--text-muted);
            font-weight: 500;
            width: 35%;
        }
        .db-info-pill td.value {
            color: var(--text-primary);
            font-family: monospace;
        }
    </style>
</head>
<body>

<div class="setup-card">
    <div class="setup-header">
        <div class="setup-logo">
            <svg viewBox="0 0 24 24"><path d="M12,15.5A2.5,2.5 0 0,1 9.5,13A2.5,2.5 0 0,1 12,10.5A2.5,2.5 0 0,1 14.5,13A2.5,2.5 0 0,1 12,15.5M19.43,12.97C19.47,12.65 19.5,12.33 19.5,12C19.5,11.67 19.47,11.34 19.43,11L21.54,9.37C21.73,9.22 21.78,8.95 21.66,8.73L19.66,5.27C19.54,5.05 19.27,4.96 19.05,5.05L16.56,6.05C16.04,5.66 15.47,5.34 14.86,5.08L14.47,2.42C14.43,2.18 14.22,2 13.97,2H9.97C9.72,2 9.51,2.18 9.47,2.42L9.08,5.08C8.47,5.34 7.9,5.66 7.38,6.05L4.89,5.05C4.67,4.96 4.4,5.05 4.27,5.27L2.27,8.73C2.15,8.95 2.2,9.22 2.39,9.37L4.5,11C4.46,11.34 4.43,11.67 4.43,12C4.43,12.33 4.46,12.65 4.5,13L2.39,14.63C2.2,14.78 2.15,15.05 2.27,15.27L4.27,18.73C4.4,18.95 4.67,19.04 4.89,18.95L7.38,17.95C7.9,18.34 8.47,18.66 9.08,18.92L9.47,21.58C9.51,21.82 9.72,22 9.97,22H13.97C14.22,22 14.43,21.82 14.47,21.58L14.86,18.92C15.47,18.66 16.04,18.34 16.56,17.95L19.05,18.95C19.27,19.04 19.54,18.95 19.66,18.73L21.66,15.27C21.78,15.05 21.73,14.78 21.54,14.63L19.43,12.97Z"/></svg>
        </div>
        <h1>Configuração do Sistema</h1>
        <p>Inicialize o banco de dados MySQL/MariaDB e crie o usuário do painel.</p>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>

    <?php if (!$success): ?>
        <div class="db-info-pill">
            <h3>Parâmetros de Banco de Dados Detectados</h3>
            <table>
                <tr>
                    <td class="label">Host do MySQL:</td>
                    <td class="value"><?php echo DB_HOST . ':' . DB_PORT; ?></td>
                </tr>
                <tr>
                    <td class="label">Banco de Dados:</td>
                    <td class="value"><?php echo DB_NAME; ?></td>
                </tr>
                <tr>
                    <td class="label">Usuário:</td>
                    <td class="value"><?php echo DB_USER; ?></td>
                </tr>
            </table>
            <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.5rem; line-height: 1.3;">
                Nota: Se esses parâmetros estiverem incorretos, altere-os no arquivo <a href="file:///c:/wamp64/www/backup/config.php" style="color: var(--primary);">config.php</a> antes de prosseguir.
            </p>
        </div>

        <form action="" method="POST">
            <input type="hidden" name="action" value="setup">
            
            <h3 style="font-size: 1rem; color: white; margin-bottom: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">Conta Administradora</h3>
            
            <div class="form-group">
                <label for="admin_name">Nome do Administrador</label>
                <input type="text" id="admin_name" name="admin_name" class="form-control" placeholder="Ex: Admin" required>
            </div>
            
            <div class="form-group">
                <label for="admin_user">Usuário (Login)</label>
                <input type="text" id="admin_user" name="admin_user" class="form-control" placeholder="Ex: admin" required>
            </div>
            
            <div class="form-group">
                <label for="admin_pass">Senha do Administrador</label>
                <input type="password" id="admin_pass" name="admin_pass" class="form-control" placeholder="Digite uma senha forte" required>
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; margin-top: 1rem; padding: 0.85rem;">
                Instalar e Configurar
            </button>
        </form>
    <?php endif; ?>
</div>

</body>
</html>
