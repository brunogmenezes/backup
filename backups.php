<?php
/**
 * Explorador e Gerenciador de Arquivos de Backup (Tabela ftpd)
 */

require_once __DIR__ . '/db.php'; // Inicia a sessão e conecta ao banco de dados

// Segurança de Download de Arquivo
if (isset($_GET['download']) && isset($_GET['user'])) {
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        header('HTTP/1.0 403 Forbidden');
        exit('Acesso negado.');
    }
    
    $download_user = trim($_GET['user']);
    $download_file = basename($_GET['download']);
    
    try {
        // Query adaptada para ftpd
        $stmt = $pdo->prepare("SELECT `Dir` FROM `ftpd` WHERE `User` = ?");
        $stmt->execute([$download_user]);
        $dir = $stmt->fetchColumn();
        
        if ($dir) {
            $local_path = get_real_ftp_path($dir);
            $file_path = $local_path . '/' . $download_file;
            
            $real_file_path = realpath($file_path);
            $real_local_path = realpath($local_path);
            
            if ($real_file_path && $real_local_path && strpos($real_file_path, $real_local_path) === 0 && file_exists($real_file_path)) {
                header('Content-Description: File Transfer');
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="' . basename($real_file_path) . '"');
                header('Expires: 0');
                header('Cache-Control: must-revalidate');
                header('Pragma: public');
                header('Content-Length: ' . filesize($real_file_path));
                
                ob_clean();
                flush();
                readfile($real_file_path);
                exit;
            }
        }
    } catch (PDOException $e) {
        // Silencia
    }
    
    header('HTTP/1.0 404 Not Found');
    exit('Arquivo não encontrado.');
}

// Ação de Excluir Backup
$success_msg = '';
$error_msg = '';

if (isset($_GET['delete_file']) && isset($_GET['user'])) {
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        header('Location: login.php');
        exit;
    }
    
    $delete_user = trim($_GET['user']);
    $delete_file = basename($_GET['delete_file']);
    
    try {
        // Query adaptada para ftpd
        $stmt = $pdo->prepare("SELECT `Dir` FROM `ftpd` WHERE `User` = ?");
        $stmt->execute([$delete_user]);
        $dir = $stmt->fetchColumn();
        
        if ($dir) {
            $local_path = get_real_ftp_path($dir);
            $file_path = $local_path . '/' . $delete_file;
            
            $real_file_path = realpath($file_path);
            $real_local_path = realpath($local_path);
            
            if ($real_file_path && $real_local_path && strpos($real_file_path, $real_local_path) === 0 && file_exists($real_file_path)) {
                if (unlink($real_file_path)) {
                    $success_msg = "Arquivo de backup '$delete_file' excluído com sucesso!";
                } else {
                    $error_msg = "Não foi possível excluir o arquivo fisicamente.";
                }
            } else {
                $error_msg = "Arquivo não encontrado ou fora do diretório do usuário.";
            }
        }
    } catch (PDOException $e) {
        $error_msg = "Erro ao buscar caminho no banco: " . $e->getMessage();
    }
}

// Inicia cabeçalhos visuais
$page_title = 'Arquivos de Backup';
$page_subtitle = 'Explore, baixe e remova os arquivos de backup enviados pelos equipamentos.';
require_once __DIR__ . '/header.php';

// Filtros da URL
$filter_device = isset($_GET['device_id']) && $_GET['device_id'] !== '' ? intval($_GET['device_id']) : null;
$filter_user = isset($_GET['user']) && $_GET['user'] !== '' ? trim($_GET['user']) : null;

// Carrega listas para os filtros
try {
    $devices = $pdo->query("SELECT id, name FROM devices ORDER BY name ASC")->fetchAll();
    // Query adaptada para ftpd
    $ftp_users_list = $pdo->query("SELECT User, device_id FROM ftpd ORDER BY User ASC")->fetchAll();
} catch (PDOException $e) {
    $devices = [];
    $ftp_users_list = [];
}

// Monta lista de diretórios a escanear com base nos filtros
$scans = [];

try {
    if ($filter_user) {
        // Query adaptada para ftpd
        $stmt = $pdo->prepare("SELECT f.User, f.Dir, d.name as device_name FROM ftpd f LEFT JOIN devices d ON f.device_id = d.id WHERE f.User = ?");
        $stmt->execute([$filter_user]);
        $scans = $stmt->fetchAll();
    } elseif ($filter_device) {
        // Query adaptada para ftpd
        $stmt = $pdo->prepare("SELECT f.User, f.Dir, d.name as device_name FROM ftpd f INNER JOIN devices d ON f.device_id = d.id WHERE f.device_id = ?");
        $stmt->execute([$filter_device]);
        $scans = $stmt->fetchAll();
    } else {
        // Query adaptada para ftpd
        $scans = $pdo->query("SELECT f.User, f.Dir, d.name as device_name FROM ftpd f LEFT JOIN devices d ON f.device_id = d.id")->fetchAll();
    }
} catch (PDOException $e) {
    $error_msg = "Erro ao buscar usuários para escaneamento: " . $e->getMessage();
}

// Escaneia os arquivos físicos
$backup_files = [];
foreach ($scans as $scan) {
    $local_dir = get_real_ftp_path($scan['Dir']);
    
    if (file_exists($local_dir) && is_dir($local_dir)) {
        $files = scandir($local_dir);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;
            $file_path = $local_dir . '/' . $file;
            
            if (is_file($file_path)) {
                $backup_files[] = [
                    'name' => $file,
                    'size' => filesize($file_path),
                    'mtime' => filemtime($file_path),
                    'user' => $scan['User'],
                    'device' => $scan['device_name'] ?? 'Nenhum'
                ];
            }
        }
    }
}

// Ordena os backups por data de modificação decrescente (mais recentes primeiro)
usort($backup_files, function($a, $b) {
    return $b['mtime'] - $a['mtime'];
});

// Helper de formatação de tamanho
function format_size_backups($bytes) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, 2) . ' ' . $units[$pow];
}
?>

<!-- Alert Feedback -->
<?php if ($success_msg): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($success_msg); ?></div>
<?php endif; ?>
<?php if ($error_msg): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error_msg); ?></div>
<?php endif; ?>

<!-- Filters Form -->
<form action="" method="GET" class="section-container" style="padding: 1.25rem;">
    <div class="filters-bar" style="margin-bottom: 0;">
        <div class="form-group filter-select" style="margin-bottom: 0; flex: 1;">
            <label for="device_id" style="font-size: 0.8rem; margin-bottom: 0.25rem;">Dispositivo</label>
            <select id="device_id" name="device_id" class="form-control" onchange="this.form.submit()">
                <option value="">-- Todos os Dispositivos --</option>
                <?php foreach ($devices as $dev): ?>
                    <option value="<?php echo $dev['id']; ?>" <?php echo $filter_device === $dev['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($dev['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group filter-select" style="margin-bottom: 0; flex: 1;">
            <label for="user" style="font-size: 0.8rem; margin-bottom: 0.25rem;">Usuário FTP</label>
            <select id="user" name="user" class="form-control" onchange="this.form.submit()">
                <option value="">-- Todos os Usuários FTP --</option>
                <?php foreach ($ftp_users_list as $fu): ?>
                    <option value="<?php echo htmlspecialchars($fu['User']); ?>" <?php echo $filter_user === $fu['User'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($fu['User']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div style="margin-top: 1.25rem; display: flex; gap: 0.5rem;">
            <button type="submit" class="btn btn-primary">
                Filtrar
            </button>
            <?php if ($filter_device || $filter_user): ?>
                <a href="backups.php" class="btn">Limpar Filtros</a>
            <?php endif; ?>
        </div>
    </div>
</form>

<!-- File List Explorer -->
<div class="section-container">
    <div class="section-header">
        <h2 class="section-title">Backups Disponíveis (<?php echo count($backup_files); ?>)</h2>
        
        <div style="position: relative; width: 260px;">
            <input type="text" id="table-search" class="form-control" placeholder="Pesquisar nesta listagem..." style="padding-left: 2rem; padding-top: 0.5rem; padding-bottom: 0.5rem; font-size: 0.85rem;">
            <svg viewBox="0 0 24 24" width="14" height="14" stroke="var(--text-muted)" stroke-width="2" fill="none" style="position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%);">
                <circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
        </div>
    </div>

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Nome do Arquivo</th>
                    <th>Dispositivo</th>
                    <th>Usuário FTP</th>
                    <th>Tamanho</th>
                    <th>Última Modificação</th>
                    <th style="width: 120px; text-align: right;">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($backup_files) > 0): ?>
                    <?php foreach ($backup_files as $file): ?>
                        <tr>
                            <td style="font-weight: 500; color: white;">
                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                    <div class="file-icon" style="background: rgba(99, 102, 241, 0.08); width: 28px; height: 28px;">
                                        <svg viewBox="0 0 24 24" width="14" height="14" stroke="var(--primary)" stroke-width="2" fill="none"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                                    </div>
                                    <?php echo htmlspecialchars($file['name']); ?>
                                </div>
                            </td>
                            <td>
                                <span class="badge badge-info"><?php echo htmlspecialchars($file['device']); ?></span>
                            </td>
                            <td>
                                <span style="font-family: monospace; color: var(--text-secondary);"><?php echo htmlspecialchars($file['user']); ?></span>
                            </td>
                            <td><?php echo format_size_backups($file['size']); ?></td>
                            <td class="text-muted"><?php echo date('d/m/Y H:i:s', $file['mtime']); ?></td>
                            <td style="text-align: right;">
                                <div style="display: inline-flex; gap: 0.5rem;">
                                    <a href="backups.php?download=<?php echo urlencode($file['name']); ?>&user=<?php echo urlencode($file['user']); ?>" class="btn btn-sm btn-primary" title="Baixar Backup">
                                        <svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2" fill="none"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                                    </a>
                                    <a href="backups.php?delete_file=<?php echo urlencode($file['name']); ?>&user=<?php echo urlencode($file['user']); ?><?php echo $filter_device ? '&device_id=' . $filter_device : ''; ?><?php echo $filter_user ? '&user=' . urlencode($filter_user) : ''; ?>" class="btn btn-sm btn-danger confirm-action" data-confirm-message="Deseja realmente excluir permanentemente o arquivo de backup '<?php echo addslashes($file['name']); ?>'?" title="Excluir Backup">
                                        <svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2" fill="none"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr class="no-results">
                        <td colspan="6" class="text-center empty-state" style="text-align: center;">
                            <svg viewBox="0 0 24 24" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>
                            <p>Nenhum backup encontrado para os critérios de busca.</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
