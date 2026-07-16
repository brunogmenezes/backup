<?php
/**
 * Dashboard Principal (Adaptado para Tabela ftpd)
 */

$page_title = 'Dashboard';
$page_subtitle = 'Visão geral do sistema de gerenciamento de backups.';
require_once __DIR__ . '/header.php';

// Função para formatar tamanho de arquivos
function format_size_local($bytes) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, 2) . ' ' . $units[$pow];
}

// Função para calcular tamanho recursivo de um diretório
function get_dir_size_local($dir) {
    $size = 0;
    if (!file_exists($dir)) return 0;
    try {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            $size += $file->getSize();
        }
    } catch (Exception $e) {
        // Ignora erros de permissão de leitura
    }
    return $size;
}

// 1. Estatísticas Rápidas
try {
    // Total de dispositivos
    $total_devices = $pdo->query("SELECT COUNT(*) FROM `devices`")->fetchColumn();
    
    // Total de contas FTP (Tabela ftpd de produção)
    $total_ftp_users = $pdo->query("SELECT COUNT(*) FROM `ftpd`")->fetchColumn();
    
    // Total de contas FTP ativas (status = 1)
    $active_ftp_users = $pdo->query("SELECT COUNT(*) FROM `ftpd` WHERE `status` = 1")->fetchColumn();
} catch (PDOException $e) {
    $total_devices = 0;
    $total_ftp_users = 0;
    $active_ftp_users = 0;
}

// 2. Escanear arquivos de backup reais e coletar histórico
$all_files = [];
$total_backup_size = 0;

// Inicializa array para atividade dos últimos 7 dias
$activity_data = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $activity_data[$d] = [
        'day_label' => date('d/M', strtotime("-$i days")),
        'count' => 0
    ];
}

try {
    // Lendo de ftpd
    $stmt = $pdo->query("SELECT f.User, f.Dir, f.device_id, d.name AS device_name FROM ftpd f LEFT JOIN devices d ON f.device_id = d.id");
    $ftp_users = $stmt->fetchAll();
    
    foreach ($ftp_users as $user) {
        $local_dir = get_real_ftp_path($user['Dir']);
        
        if (file_exists($local_dir) && is_dir($local_dir)) {
            $total_backup_size += get_dir_size_local($local_dir);
            
            $files = scandir($local_dir);
            foreach ($files as $file) {
                if ($file === '.' || $file === '..') continue;
                $file_path = $local_dir . '/' . $file;
                
                if (is_file($file_path)) {
                    $mtime = filemtime($file_path);
                    $file_date_key = date('Y-m-d', $mtime);
                    
                    if (isset($activity_data[$file_date_key])) {
                        $activity_data[$file_date_key]['count']++;
                    }
                    
                    $all_files[] = [
                        'name' => $file,
                        'size' => filesize($file_path),
                        'mtime' => $mtime,
                        'user' => $user['User'],
                        'device' => $user['device_name'] ?? 'Nenhum'
                    ];
                }
            }
        }
    }
} catch (Exception $e) {
    // Silencia se tabelas não existirem plenamente
}

// Ordena todos os arquivos por data de modificação decrescente
usort($all_files, function($a, $b) {
    return $b['mtime'] - $a['mtime'];
});

// Seleciona os 5 arquivos mais recentes
$recent_backups = array_slice($all_files, 0, 5);

// Prepara coordenadas para o gráfico dinâmico SVG
$chart_counts = array_column($activity_data, 'count');
$chart_labels = array_column($activity_data, 'day_label');
$max_count = count($chart_counts) > 0 ? max($chart_counts) : 0;
if ($max_count == 0) $max_count = 5;

// Mapeia os pontos para coordenadas SVG (Largura: 700, Altura: 150)
$points = [];
$x_interval = 700 / 6;
$index = 0;
foreach ($chart_counts as $count) {
    $x = $index * $x_interval;
    $y = 130 - ($count / $max_count * 100);
    $points[] = "$x,$y";
    $index++;
}
$points_str = implode(' ', $points);
$area_points_str = "0,130 " . $points_str . " 700,130";
?>

<!-- Metrics Panel -->
<div class="card-grid">
    <div class="metric-card">
        <div class="metric-info">
            <h3>Dispositivos</h3>
            <div class="metric-value"><?php echo $total_devices; ?></div>
        </div>
        <div class="metric-icon purple">
            <svg viewBox="0 0 24 24" stroke-width="2"><rect x="2" y="2" width="20" height="8" rx="2" ry="2"></rect><rect x="2" y="14" width="20" height="8" rx="2" ry="2"></rect><line x1="6" y1="6" x2="6.01" y2="6"></line><line x1="6" y1="18" x2="6.01" y2="18"></line></svg>
        </div>
    </div>
    
    <div class="metric-card">
        <div class="metric-info">
            <h3>Contas FTP Ativas</h3>
            <div class="metric-value"><?php echo $active_ftp_users; ?><span style="font-size: 1rem; color: var(--text-muted); font-weight: normal;"> / <?php echo $total_ftp_users; ?></span></div>
        </div>
        <div class="metric-icon blue">
            <svg viewBox="0 0 24 24" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
        </div>
    </div>
    
    <div class="metric-card">
        <div class="metric-info">
            <h3>Espaço Total de Backups</h3>
            <div class="metric-value"><?php echo format_size_local($total_backup_size); ?></div>
        </div>
        <div class="metric-icon green">
            <svg viewBox="0 0 24 24" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l-7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
        </div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; margin-bottom: 2.5rem; flex-wrap: wrap;" class="dashboard-sections">
    <!-- Activity Chart Section -->
    <div class="section-container" style="margin-bottom: 0;">
        <div class="section-header">
            <h2 class="section-title">Atividade de Backup (Últimos 7 Dias)</h2>
            <span style="color: var(--text-muted); font-size: 0.85rem;">Volume de uploads diários</span>
        </div>
        
        <div class="chart-container">
            <svg viewBox="0 0 700 150" class="chart-svg" preserveAspectRatio="none">
                <defs>
                    <linearGradient id="chart-gradient" x1="0" y1="0" x2="1" y2="0">
                        <stop offset="0%" stop-color="#6366f1" />
                        <stop offset="100%" stop-color="#06b6d4" />
                    </linearGradient>
                    <linearGradient id="area-gradient" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stop-color="#6366f1" stop-opacity="0.4"/>
                        <stop offset="100%" stop-color="#6366f1" stop-opacity="0.0"/>
                    </linearGradient>
                </defs>
                
                <line x1="0" y1="30" x2="700" y2="30" class="chart-grid-line" />
                <line x1="0" y1="80" x2="700" y2="80" class="chart-grid-line" />
                <line x1="0" y1="130" x2="700" y2="130" class="chart-grid-line" style="stroke: rgba(255, 255, 255, 0.1);" />
                
                <polygon points="<?php echo $area_points_str; ?>" class="chart-area" />
                <polyline points="<?php echo $points_str; ?>" class="chart-line" />
                
                <?php
                $index = 0;
                foreach ($chart_counts as $count) {
                    $x = $index * $x_interval;
                    $y = 130 - ($count / $max_count * 100);
                    echo "<circle cx='$x' cy='$y' class='chart-dot' data-count='$count' title='Uploads: $count'></circle>";
                    if ($count > 0) {
                        echo "<text x='$x' y='" . ($y - 12) . "' fill='white' font-size='10' font-weight='bold' text-anchor='middle'>$count</text>";
                    }
                    echo "<text x='$x' y='148' fill='#9ca3af' font-size='10' font-family='Inter' text-anchor='middle'>{$chart_labels[$index]}</text>";
                    $index++;
                }
                ?>
            </svg>
        </div>
    </div>

    <!-- Actions Section -->
    <div class="section-container" style="margin-bottom: 0; display: flex; flex-direction: column;">
        <div class="section-header">
            <h2 class="section-title">Ações Rápidas</h2>
        </div>
        <div style="display: flex; flex-direction: column; gap: 0.75rem; flex: 1; justify-content: center;">
            <a href="devices.php" class="btn btn-primary" style="justify-content: center; padding: 0.85rem;">
                <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="2" fill="none"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                Cadastrar Dispositivo
            </a>
            <a href="ftp_users.php" class="btn" style="justify-content: center; padding: 0.85rem;">
                <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="2" fill="none"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><line x1="19" y1="8" x2="19" y2="14"></line><line x1="16" y1="11" x2="22" y2="11"></line></svg>
                Criar Conta FTP
            </a>
            <a href="backups.php" class="btn" style="justify-content: center; padding: 0.85rem;">
                <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="2" fill="none"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>
                Explorar Backups
            </a>
        </div>
    </div>
</div>

<!-- Recent Backups Table -->
<div class="section-container">
    <div class="section-header">
        <h2 class="section-title">Últimos Backups Enviados</h2>
        <a href="backups.php" class="btn btn-sm">Ver todos</a>
    </div>
    
    <div class="table-responsive">
        <?php if (count($recent_backups) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Nome do Arquivo</th>
                        <th>Dispositivo</th>
                        <th>Usuário FTP</th>
                        <th>Tamanho</th>
                        <th>Data de Envio</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_backups as $file): ?>
                        <tr>
                            <td style="font-weight: 500; color: white;">
                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                    <svg viewBox="0 0 24 24" width="16" height="16" stroke="var(--primary)" stroke-width="2" fill="none"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                                    <?php echo htmlspecialchars($file['name']); ?>
                                </div>
                            </td>
                            <td>
                                <span class="badge badge-info"><?php echo htmlspecialchars($file['device']); ?></span>
                            </td>
                            <td>
                                <span style="font-family: monospace; color: var(--text-secondary);"><?php echo htmlspecialchars($file['user']); ?></span>
                            </td>
                            <td><?php echo format_size_local($file['size']); ?></td>
                            <td class="text-muted"><?php echo date('d/m/Y H:i:s', $file['mtime']); ?></td>
                            <td>
                                <a href="backups.php?download=<?php echo urlencode($file['name']); ?>&user=<?php echo urlencode($file['user']); ?>" class="btn btn-sm btn-primary" title="Baixar">
                                    <svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2" fill="none"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-state">
                <svg viewBox="0 0 24 24" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l-7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
                <p>Nenhum backup recebido ainda.</p>
                <p style="font-size: 0.85rem; margin-top: 0.25rem;">Envie arquivos via FTP para as pastas dos usuários para visualizá-los aqui.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
    @media (max-width: 900px) {
        .dashboard-sections {
            grid-template-columns: 1fr !important;
        }
    }
</style>

<?php require_once __DIR__ . '/footer.php'; ?>
