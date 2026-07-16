<?php
/**
 * Configurações Gerais do Sistema de Gerenciamento de Backups
 */

// Configurações do Banco de Dados
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'backup_manager');
define('DB_USER', 'root');
define('DB_PASS', '');

// Configurações de Mapeamento de Diretórios do Pure-FTPd
// Isso permite que o painel web acesse os arquivos de backup mesmo se estiver rodando 
// em um sistema diferente (ex: Windows no desenvolvimento e Linux em produção)
define('FTP_BASE_DIR_MAPPING_ENABLE', true); 
define('FTP_BASE_DIR_REMOTE_PREFIX', '/home/ftpusers'); // Caminho salvo no banco (Linux)
define('FTP_BASE_DIR_LOCAL_PREFIX', 'C:/wamp64/www/backup/storage'); // Caminho acessível pelo PHP (Windows)

// Nome da aplicação
define('APP_NAME', 'Backup Control');

// Função auxiliar para obter o caminho real e acessível de um diretório de usuário FTP
function get_real_ftp_path($db_dir) {
    if (FTP_BASE_DIR_MAPPING_ENABLE) {
        // Remove a barra no início e fim do prefixo remoto para garantir correspondência limpa
        $remote_prefix = rtrim(FTP_BASE_DIR_REMOTE_PREFIX, '/\\');
        
        // Se o caminho do banco começa com o prefixo remoto
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
// Valores possíveis: 'plaintext' (texto plano) ou 'md5' (pure-ftpd padrão)
define('FTP_PASSWORD_HASH_MODE', 'md5');

// Iniciar sessão se ainda não iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
