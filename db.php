<?php
require_once __DIR__ . '/config.php';

try {
    // Tenta conectar ao banco de dados especificado
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    
    // Verifica se as tabelas necessárias existem. Se não existirem, redireciona ou avisa.
    $current_page = basename($_SERVER['PHP_SELF']);
    if ($current_page !== 'setup.php') {
        try {
            $pdo->query("SELECT 1 FROM `admins` LIMIT 1");
        } catch (PDOException $tb_ex) {
            $lock_file = __DIR__ . '/.setup_lock';
            if (file_exists(__DIR__ . '/setup.php') && !file_exists($lock_file)) {
                header('Location: setup.php');
                exit;
            } else {
                ?>
                <!DOCTYPE html>
                <html lang="pt-BR">
                <head>
                    <meta charset="UTF-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1.0">
                    <title>Tabelas Não Inicializadas - <?php echo APP_NAME; ?></title>
                    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
                    <style>
                        body {
                            background: radial-gradient(circle at top right, #111827, #030712);
                            color: #f3f4f6;
                            font-family: 'Outfit', sans-serif;
                            display: flex;
                            justify-content: center;
                            align-items: center;
                            height: 100vh;
                            margin: 0;
                        }
                        .error-card {
                            background: rgba(17, 24, 39, 0.7);
                            backdrop-filter: blur(12px);
                            border: 1px solid rgba(239, 68, 68, 0.2);
                            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.5), 0 0 20px rgba(239, 68, 68, 0.1);
                            border-radius: 16px;
                            padding: 2.5rem;
                            max-width: 500px;
                            text-align: center;
                        }
                        h1 {
                            color: #ef4444;
                            margin-top: 0;
                            font-size: 2rem;
                            font-weight: 700;
                        }
                        p {
                            color: #9ca3af;
                            line-height: 1.6;
                            font-size: 1.05rem;
                        }
                    </style>
                </head>
                <body>
                    <div class="error-card">
                        <h1>Tabelas Não Inicializadas</h1>
                        <p>O banco de dados <code><?php echo DB_NAME; ?></code> foi conectado com sucesso, mas as tabelas do sistema não foram encontradas.</p>
                        <p>Para corrigir, exclua o arquivo de trava local <code>.setup_lock</code> (se existir) no diretório da aplicação e recarregue a página para acessar o <a href="setup.php" style="color:#6366f1; font-weight:600; text-decoration:none;">setup.php</a>.</p>
                    </div>
                </body>
                </html>
                <?php
                exit;
            }
        }
    }
} catch (PDOException $e) {
    // Se a conexão falhar porque o banco de dados não existe, ou se as tabelas ainda não foram criadas
    // vamos verificar se estamos no script de setup. Se não, redirecionamos ou mostramos um aviso amigável
    $current_page = basename($_SERVER['PHP_SELF']);
    
    if ($current_page !== 'setup.php') {
        // Tenta se conectar apenas ao host para ver se o MySQL está rodando e redirecionar para setup
        try {
            $dsn_no_db = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=utf8mb4";
            $pdo_no_db = new PDO($dsn_no_db, DB_USER, DB_PASS, $options);
            
            // Se o setup existe, redireciona
            if (file_exists(__DIR__ . '/setup.php')) {
                header('Location: setup.php');
                exit;
            }
        } catch (PDOException $ex) {
            // Se nem o host conectar, o MySQL está desligado
        }
        
        // Exibe tela de erro bonita
        ?>
        <!DOCTYPE html>
        <html lang="pt-BR">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Erro de Conexão - <?php echo APP_NAME; ?></title>
            <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
            <style>
                body {
                    background: radial-gradient(circle at top right, #111827, #030712);
                    color: #f3f4f6;
                    font-family: 'Outfit', sans-serif;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    height: 100vh;
                    margin: 0;
                }
                .error-card {
                    background: rgba(17, 24, 39, 0.7);
                    backdrop-filter: blur(12px);
                    border: 1px solid rgba(239, 68, 68, 0.2);
                    box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.5), 0 0 20px rgba(239, 68, 68, 0.1);
                    border-radius: 16px;
                    padding: 2.5rem;
                    max-width: 500px;
                    text-align: center;
                    animation: fadeIn 0.6s ease-out;
                }
                h1 {
                    color: #ef4444;
                    margin-top: 0;
                    font-size: 2rem;
                    font-weight: 700;
                }
                p {
                    color: #9ca3af;
                    line-height: 1.6;
                    font-size: 1.05rem;
                }
                code {
                    display: block;
                    background: rgba(0, 0, 0, 0.3);
                    padding: 1rem;
                    border-radius: 8px;
                    color: #fca5a5;
                    font-family: monospace;
                    text-align: left;
                    margin: 1.5rem 0;
                    font-size: 0.9rem;
                    overflow-x: auto;
                }
                .btn {
                    display: inline-block;
                    background: linear-gradient(135deg, #ef4444, #b91c1c);
                    color: white;
                    text-decoration: none;
                    padding: 0.75rem 1.5rem;
                    border-radius: 8px;
                    font-weight: 600;
                    transition: transform 0.2s, box-shadow 0.2s;
                }
                .btn:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 4px 15px rgba(239, 68, 68, 0.4);
                }
                @keyframes fadeIn {
                    from { opacity: 0; transform: translateY(20px); }
                    to { opacity: 1; transform: translateY(0); }
                }
            </style>
        </head>
        <body>
            <div class="error-card">
                <h1>Conexão Falhou</h1>
                <p>Não foi possível conectar ao banco de dados MySQL. Verifique as credenciais no arquivo <code>config.php</code> e certifique-se de que o servidor de banco de dados está rodando.</p>
                <code>Detalles: <?php echo htmlspecialchars($e->getMessage()); ?></code>
                <a href="setup.php" class="btn">Ir para o Setup</a>
            </div>
        </body>
        </html>
        <?php
        exit;
    } else {
        // Se estiver no setup.php e falhar, apenas joga a exceção para ser capturada lá
        throw $e;
    }
}
