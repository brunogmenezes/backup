<?php
/**
 * Script de Simulação e Geração de Dados de Teste
 */

require_once __DIR__ . '/config.php';

echo "<h2>Geração de Dados de Simulação para Teste</h2>";

try {
    // 1. Conecta ao MySQL
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    echo "<p style='color:green;'>✔ Conectado ao banco de dados com sucesso.</p>";
    
    // 2. Limpa dados anteriores se solicitado
    if (isset($_GET['clear'])) {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $pdo->exec("TRUNCATE TABLE `ftp_users`;");
        $pdo->exec("TRUNCATE TABLE `devices`;");
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
        echo "<p style='color:orange;'>⚠ Dados limpos.</p>";
    }
    
    // 3. Cadastra Dispositivos de Teste
    $devices_to_insert = [
        ['name' => 'Borda Router MikroTik', 'ip' => '192.168.88.1', 'model' => 'CCR2004-16G-2S+', 'description' => 'Roteador principal da infraestrutura de borda.', 'status' => 'active'],
        ['name' => 'Switch Core Cisco', 'ip' => '10.0.0.2', 'model' => 'Catalyst 2960-X', 'description' => 'Switch de núcleo da rede local.', 'status' => 'active'],
        ['name' => 'MySQL Prod Server', 'ip' => '10.0.0.150', 'model' => 'Ubuntu Server LTS VM', 'description' => 'Servidor de banco de dados de produção.', 'status' => 'active'],
        ['name' => 'Firewall pfSense', 'ip' => '192.168.1.1', 'model' => 'Netgate SG-5100', 'description' => 'Firewall e Gateway principal da filial.', 'status' => 'inactive']
    ];
    
    $inserted_devices = [];
    foreach ($devices_to_insert as $dev) {
        // Verifica se já existe
        $stmt = $pdo->prepare("SELECT id FROM `devices` WHERE `name` = ?");
        $stmt->execute([$dev['name']]);
        $id = $stmt->fetchColumn();
        
        if (!$id) {
            $stmt = $pdo->prepare("INSERT INTO `devices` (`name`, `ip`, `model`, `description`, `status`) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$dev['name'], $dev['ip'], $dev['model'], $dev['description'], $dev['status']]);
            $id = $pdo->lastInsertId();
            echo "<p>✔ Dispositivo cadastrado: <b>{$dev['name']}</b> (ID: $id)</p>";
        } else {
            echo "<p style='color:gray;'>• Dispositivo já existe: <b>{$dev['name']}</b></p>";
        }
        $inserted_devices[$dev['name']] = $id;
    }
    
    // 4. Cadastra Usuários FTP de Teste
    $ftp_users_to_insert = [
        ['user' => 'rt_borda', 'pass' => md5('senha123'), 'dir' => '/home/ftpusers/rt_borda', 'uid' => 2000, 'gid' => 2000, 'ul' => 2048, 'dl' => 1024, 'status' => 1, 'device' => 'Borda Router MikroTik'],
        ['user' => 'sw_core', 'pass' => md5('senha456'), 'dir' => '/home/ftpusers/sw_core', 'uid' => 2000, 'gid' => 2000, 'ul' => 0, 'dl' => 0, 'status' => 1, 'device' => 'Switch Core Cisco'],
        ['user' => 'db_prod', 'pass' => md5('senha789'), 'dir' => '/home/ftpusers/db_prod', 'uid' => 2001, 'gid' => 2001, 'ul' => 5120, 'dl' => 5120, 'status' => 1, 'device' => 'MySQL Prod Server'],
        ['user' => 'firewall', 'pass' => md5('fwpass'), 'dir' => '/home/ftpusers/firewall', 'uid' => 2000, 'gid' => 2000, 'ul' => 1024, 'dl' => 1024, 'status' => 0, 'device' => 'Firewall pfSense'] // Inativo
    ];
    
    foreach ($ftp_users_to_insert as $ftp) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM `ftp_users` WHERE `User` = ?");
        $stmt->execute([$ftp['user']]);
        
        if ($stmt->fetchColumn() == 0) {
            $device_id = $inserted_devices[$ftp['device']] ?? null;
            $stmt = $pdo->prepare("INSERT INTO `ftp_users` (`User`, `Password`, `Dir`, `Uid`, `Gid`, `ULBandwidth`, `DLBandwidth`, `Status`, `device_id`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$ftp['user'], $ftp['pass'], $ftp['dir'], $ftp['uid'], $ftp['gid'], $ftp['ul'], $ftp['dl'], $ftp['status'], $device_id]);
            echo "<p>✔ Usuário FTP criado: <b>{$ftp['user']}</b> (Diretório: {$ftp['dir']})</p>";
        } else {
            echo "<p style='color:gray;'>• Usuário FTP já existe: <b>{$ftp['user']}</b></p>";
        }
        
        // Cria a pasta física correspondente
        $local_path = get_real_ftp_path($ftp['dir']);
        if (!file_exists($local_path)) {
            mkdir($local_path, 0777, true);
            echo "<p style='color:blue; font-size:0.85rem;'>↳ Diretório local criado: $local_path</p>";
        }
    }
    
    // 5. Cria arquivos de backup simulados (Datas distribuídas nos últimos 7 dias)
    $mock_files = [
        'rt_borda' => [
            ['name' => 'mikrotik-borda-diario-2026-07-15.rsc', 'size' => 124500, 'age_days' => 0], // hoje
            ['name' => 'mikrotik-borda-diario-2026-07-14.rsc', 'size' => 124300, 'age_days' => 1],
            ['name' => 'mikrotik-borda-semanal-2026-07-12.backup', 'size' => 1890000, 'age_days' => 3],
            ['name' => 'mikrotik-borda-diario-2026-07-10.rsc', 'size' => 123900, 'age_days' => 5],
        ],
        'sw_core' => [
            ['name' => 'cisco-switch-running-config-2026-07-15.cfg', 'size' => 45800, 'age_days' => 0],
            ['name' => 'cisco-switch-running-config-2026-07-13.cfg', 'size' => 45600, 'age_days' => 2],
            ['name' => 'cisco-switch-running-config-2026-07-09.cfg', 'size' => 45500, 'age_days' => 6],
        ],
        'db_prod' => [
            ['name' => 'mysql-prod-backup-2026-07-15_23-00.sql.gz', 'size' => 24589000, 'age_days' => 0],
            ['name' => 'mysql-prod-backup-2026-07-14_23-00.sql.gz', 'size' => 24450000, 'age_days' => 1],
            ['name' => 'mysql-prod-backup-2026-07-13_23-00.sql.gz', 'size' => 24390000, 'age_days' => 2],
            ['name' => 'mysql-prod-backup-2026-07-12_23-00.sql.gz', 'size' => 24100000, 'age_days' => 3],
            ['name' => 'mysql-prod-backup-2026-07-11_23-00.sql.gz', 'size' => 23900000, 'age_days' => 4],
        ]
    ];
    
    echo "<h3>Gerando arquivos de backup fictícios nas pastas locais:</h3>";
    foreach ($mock_files as $user => $files) {
        $stmt = $pdo->prepare("SELECT `Dir` FROM `ftp_users` WHERE `User` = ?");
        $stmt->execute([$user]);
        $dir = $stmt->fetchColumn();
        
        if ($dir) {
            $local_path = get_real_ftp_path($dir);
            foreach ($files as $f) {
                $full_path = $local_path . '/' . $f['name'];
                
                // Cria arquivo com o tamanho exato usando dados vazios ou uma pequena string de cabeçalho
                $header = "Simulated backup file for " . $user . "\nFile: " . $f['name'] . "\nSize: " . $f['size'] . " bytes\n";
                $padding_size = max(0, $f['size'] - strlen($header));
                
                file_put_contents($full_path, $header . str_repeat("\0", $padding_size));
                
                // Altera a data de modificação física do arquivo no Windows/Linux para simular dias passados
                $target_time = time() - ($f['age_days'] * 86400);
                touch($full_path, $target_time);
                
                $size_mb = round($f['size'] / 1024 / 1024, 2);
                $date_str = date('d/m/Y H:i', $target_time);
                echo "<p>✔ Criado arquivo: <b style='color:purple;'>{$f['name']}</b> ({$size_mb} MB) em <i>{$date_str}</i></p>";
            }
        }
    }
    
    echo "<p style='color:green; font-weight:bold; margin-top:1.5rem;'>✔ Geração de dados de simulação completa! Você já pode abrir o painel.</p>";
    echo "<p><a href='index.php' style='padding:0.75rem 1.5rem; background:#6366f1; color:white; border-radius:8px; text-decoration:none; font-weight:bold; display:inline-block; margin-top:0.5rem;'>Ir para o Painel de Controle</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ Erro ao simular dados: " . $e->getMessage() . "</p>";
    echo "<p>Certifique-se de realizar a instalação acessando <a href='setup.php'>setup.php</a> primeiro.</p>";
}
