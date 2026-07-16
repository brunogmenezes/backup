<?php
/**
 * Configurações Gerais do Sistema de Gerenciamento de Backups
 */

// Configurações do Banco de Dados
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'pureftpd'); // Banco padrão do Pure-FTPd
define('DB_USER', 'root');
define('DB_PASS', '');

// Configurações de Mapeamento de Diretórios do Pure-FTPd
define('FTP_BASE_DIR_REMOTE_PREFIX', '/var/pure-ftpd'); // Caminho de produção no Debian

// Detecção automática de Sistema Operacional (Windows/Linux)
// No Windows (Desenvolvimento/Teste) mapeia para pasta local. No Linux (Produção) lê direto da pasta do Pure-FTPd.
if (strpos(strtoupper(PHP_OS), 'WIN') !== false) {
    define('FTP_BASE_DIR_MAPPING_ENABLE', true); 
    define('FTP_BASE_DIR_LOCAL_PREFIX', 'C:/wamp64/www/backup/storage'); 
} else {
    define('FTP_BASE_DIR_MAPPING_ENABLE', false); 
    define('FTP_BASE_DIR_LOCAL_PREFIX', '/var/pure-ftpd'); 
}

// Nome da aplicação
define('APP_NAME', 'Backup Control');

// Função auxiliar para obter o caminho real e acessível de um diretório de usuário FTP
function get_real_ftp_path($db_dir) {
    if (FTP_BASE_DIR_MAPPING_ENABLE) {
        $remote_prefix = rtrim(FTP_BASE_DIR_REMOTE_PREFIX, '/\\');
        
        if (strpos($db_dir, $remote_prefix) === 0) {
            $relative = substr($db_dir, strlen($remote_prefix));
        } else {
            $relative = $db_dir;
        }
        
        $relative = ltrim($relative, '/\\');
        $local_prefix = rtrim(FTP_BASE_DIR_LOCAL_PREFIX, '/\\');
        
        return $local_prefix . '/' . $relative;
    }
    return $db_dir;
}

// Configurações de Criptografia de Senha do Pure-FTPd
// 'plaintext' (texto plano) ou 'md5' (pure-ftpd padrão)
define('FTP_PASSWORD_HASH_MODE', 'plaintext'); // bjnet, bnjnet etc parecem usar senhas em texto plano ("91lS!&*Ke")

// Iniciar sessão se ainda não iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
