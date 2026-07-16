<?php
/**
 * Script de Simulação e Geração de Dados de Teste (Estrutura de Produção ftpd)
 */

require_once __DIR__ . '/config.php';

echo "<h2>Geração de Dados de Simulação para Teste (Estrutura ftpd)</h2>";

try {
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    echo "<p style='color:green;'>✔ Conectado ao banco de dados com sucesso.</p>";
    
    if (isset($_GET['clear'])) {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $pdo->exec("TRUNCATE TABLE `ftpd`;");
        $pdo->exec("TRUNCATE TABLE `devices`;");
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
        echo "<p style='color:orange;'>⚠ Dados limpos.</p>";
    }
    
    // Cadastra Dispositivos de Teste
    $devices_to_insert = [
        ['name' => 'Borda Router MikroTik', 'ip' => '192.168.88.1', 'model' => 'CCR2004-16G-2S+', 'description' => 'Roteador principal da infraestrutura de borda.', 'status' => 'active'],
        ['name' => 'Switch Core Cisco', 'ip' => '10.0.0.2', 'model' => 'Catalyst 2960-X', 'description' => 'Switch de núcleo da rede local.', 'status' => 'active'],
        ['name' => 'MySQL Prod Server', 'ip' => '10.0.0.150', 'model' => 'Ubuntu Server LTS VM', 'description' => 'Servidor de banco de dados de produção.', 'status' => 'active'],
        ['name' => 'Firewall pfSense', 'ip' => '192.168.1.1', 'model' => 'Netgate SG-5100', 'description' => 'Firewall e Gateway principal da filial.', 'status' => 'inactive']
    ];
    
    $inserted_devices = [];
    foreach ($devices_to_insert as $dev) {
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
    
    // Cadastra Usuários FTP de Teste (Inserindo na tabela ftpd com UID/GID 2001 e campos adicionais)
    $ftp_users_to_insert = [
        ['user' => 'bjnet', 'pass' => '91lS!&*Ke', 'dir' => '/var/pure-ftpd/bjnet', 'uid' => 2001, 'gid' => 2001, 'ul' => 0, 'dl' => 0, 'status' => 1, 'comment' => 'Conta BJ Net', 'ipaccess' => '*', 'quota_s' => 0, 'quota_f' => 0, 'device' => 'Borda Router MikroTik'],
        ['user' => 'bnjnet', 'pass' => '91lS!&*Ke', 'dir' => '/var/pure-ftpd/bnjnet', 'uid' => 2001, 'gid' => 2001, 'ul' => 0, 'dl' => 0, 'status' => 1, 'comment' => 'Conta BNJ Net', 'ipaccess' => '*', 'quota_s' => 0, 'quota_f' => 0, 'device' => 'Borda Router MikroTik'],
        ['user' => 'fstelecom', 'pass' => '91lS!&*Ke', 'dir' => '/var/pure-ftpd/fstelecom', 'uid' => 2001, 'gid' => 2001, 'ul' => 0, 'dl' => 0, 'status' => 1, 'comment' => 'Conta FS Telecom', 'ipaccess' => '*', 'quota_s' => 0, 'quota_f' => 0, 'device' => 'Switch Core Cisco'],
        ['user' => 'g2telecom', 'pass' => '91lS!&*Ke', 'dir' => '/var/pure-ftpd/g2telecom', 'uid' => 2001, 'gid' => 2001, 'ul' => 0, 'dl' => 0, 'status' => 1, 'comment' => 'Conta G2 Telecom', 'ipaccess' => '*', 'quota_s' => 0, 'quota_f' => 0, 'device' => 'MySQL Prod Server']
    ];
    
    foreach ($ftp_users_to_insert as $ftp) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM `ftpd` WHERE `User` = ?");
        $stmt->execute([$ftp['user']]);
        
        if ($stmt->fetchColumn() == 0) {
            $device_id = $inserted_devices[$ftp['device']] ?? null;
            $stmt = $pdo->prepare("INSERT INTO `ftpd` (`User`, `Password`, `Dir`, `Uid`, `Gid`, `ULBandwidth`, `DLBandwidth`, `status`, `comment`, `ipaccess`, `QuotaSize`, `QuotaFiles`, `device_id`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$ftp['user'], $ftp['pass'], $ftp['dir'], $ftp['uid'], $ftp['gid'], $ftp['ul'], $ftp['dl'], $ftp['status'], $ftp['comment'], $ftp['ipaccess'], $ftp['quota_s'], $ftp['quota_f'], $device_id]);
            echo "<p>✔ Usuário FTP de simulação criado: <b>{$ftp['user']}</b></p>";
        } else {
            echo "<p style='color:gray;'>• Usuário FTP de simulação já existe: <b>{$ftp['user']}</b></p>";
        }
        
        // Cria a pasta física correspondente
        $local_path = get_real_ftp_path($ftp['dir']);
        if (!file_exists($local_path)) {
            mkdir($local_path, 0777, true);
        }
    }
    
    // Cria arquivos de backup simulados
    $mock_files = [
        'bjnet' => [
            ['name' => 'bjnet-backup-2026-07-15.rsc', 'size' => 112000, 'age_days' => 0],
            ['name' => 'bjnet-backup-2026-07-14.rsc', 'size' => 111500, 'age_days' => 1],
            ['name' => 'bjnet-full-2026-07-12.backup', 'size' => 2100000, 'age_days' => 3],
        ],
        'bnjnet' => [
            ['name' => 'bnjnet-config-2026-07-15.cfg', 'size' => 45000, 'age_days' => 0],
            ['name' => 'bnjnet-config-2026-07-13.cfg', 'size' => 44800, 'age_days' => 2],
        ],
        'fstelecom' => [
            ['name' => 'fs-telecom-switch-2026-07-15.cfg', 'size' => 64000, 'age_days' => 0],
            ['name' => 'fs-telecom-switch-2026-07-11.cfg', 'size' => 63500, 'age_days' => 4],
        ],
        'g2telecom' => [
            ['name' => 'g2telecom-mysql-prod-2026-07-15.sql.gz', 'size' => 12450000, 'age_days' => 0],
            ['name' => 'g2telecom-mysql-prod-2026-07-14.sql.gz', 'size' => 12400000, 'age_days' => 1],
        ]
    ];
    
    echo "<h3>Gerando arquivos de backup fictícios nas pastas locais:</h3>";
    foreach ($mock_files as $user => $files) {
        $stmt = $pdo->prepare("SELECT `Dir` FROM `ftpd` WHERE `User` = ?");
        $stmt->execute([$user]);
        $dir = $stmt->fetchColumn();
        
        if ($dir) {
            $local_path = get_real_ftp_path($dir);
            foreach ($files as $f) {
                $full_path = $local_path . '/' . $f['name'];
                
                $header = "Simulated backup file for " . $user . "\nFile: " . $f['name'] . "\nSize: " . $f['size'] . " bytes\n";
                $padding_size = max(0, $f['size'] - strlen($header));
                
                file_put_contents($full_path, $header . str_repeat("\0", $padding_size));
                
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
