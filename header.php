<?php
/**
 * Cabeçalho e Menu Lateral Compartilhado
 */

require_once __DIR__ . '/db.php';

// Verifica autenticação
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' . APP_NAME : APP_NAME; ?></title>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    
    <!-- CSS Principal -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="app-container">
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="brand">
            <div class="brand-icon">
                <svg viewBox="0 0 24 24"><path d="M12,15.5A2.5,2.5 0 0,1 9.5,13A2.5,2.5 0 0,1 12,10.5A2.5,2.5 0 0,1 14.5,13A2.5,2.5 0 0,1 12,15.5M19.43,12.97C19.47,12.65 19.5,12.33 19.5,12C19.5,11.67 19.47,11.34 19.43,11L21.54,9.37C21.73,9.22 21.78,8.95 21.66,8.73L19.66,5.27C19.54,5.05 19.27,4.96 19.05,5.05L16.56,6.05C16.04,5.66 15.47,5.34 14.86,5.08L14.47,2.42C14.43,2.18 14.22,2 13.97,2H9.97C9.72,2 9.51,2.18 9.47,2.42L9.08,5.08C8.47,5.34 7.9,5.66 7.38,6.05L4.89,5.05C4.67,4.96 4.4,5.05 4.27,5.27L2.27,8.73C2.15,8.95 2.2,9.22 2.39,9.37L4.5,11C4.46,11.34 4.43,11.67 4.43,12C4.43,12.33 4.46,12.65 4.5,13L2.39,14.63C2.2,14.78 2.15,15.05 2.27,15.27L4.27,18.73C4.4,18.95 4.67,19.04 4.89,18.95L7.38,17.95C7.9,18.34 8.47,18.66 9.08,18.92L9.47,21.58C9.51,21.82 9.72,22 9.97,22H13.97C14.22,22 14.43,21.82 14.47,21.58L14.86,18.92C15.47,18.66 16.04,18.34 16.56,17.95L19.05,18.95C19.27,19.04 19.54,18.95 19.66,18.73L21.66,15.27C21.78,15.05 21.73,14.78 21.54,14.63L19.43,12.97Z"/></svg>
            </div>
            <span class="brand-name"><?php echo APP_NAME; ?></span>
        </div>
        
        <nav class="sidebar-nav">
            <ul class="menu-list">
                <li class="menu-item <?php echo $current_page === 'index.php' ? 'active' : ''; ?>">
                    <a href="index.php">
                        <svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none"><rect x="3" y="3" width="7" height="9"></rect><rect x="14" y="3" width="7" height="5"></rect><rect x="14" y="12" width="7" height="9"></rect><rect x="3" y="16" width="7" height="5"></rect></svg>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="menu-item <?php echo $current_page === 'devices.php' ? 'active' : ''; ?>">
                    <a href="devices.php">
                        <svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none"><rect x="2" y="2" width="20" height="8" rx="2" ry="2"></rect><rect x="2" y="14" width="20" height="8" rx="2" ry="2"></rect><line x1="6" y1="6" x2="6.01" y2="6"></line><line x1="6" y1="18" x2="6.01" y2="18"></line></svg>
                        <span>Dispositivos</span>
                    </a>
                </li>
                <li class="menu-item <?php echo $current_page === 'ftp_users.php' ? 'active' : ''; ?>">
                    <a href="ftp_users.php">
                        <svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                        <span>Contas FTP</span>
                    </a>
                </li>
                <li class="menu-item <?php echo $current_page === 'backups.php' ? 'active' : ''; ?>">
                    <a href="backups.php">
                        <svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>
                        <span>Backups</span>
                    </a>
                </li>
            </ul>
        </nav>
        
        <div class="sidebar-footer">
            <div class="user-profile">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($_SESSION['admin_name'], 0, 1)); ?>
                </div>
                <div class="user-info">
                    <div class="user-name"><?php echo htmlspecialchars($_SESSION['admin_name']); ?></div>
                    <div class="user-role">Administrador</div>
                </div>
            </div>
            <a href="logout.php" class="btn btn-danger btn-sm confirm-action" data-confirm-message="Deseja realmente sair?" style="width: 100%; justify-content: center;">
                <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none" style="margin-right: 0.25rem;"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                Sair
            </a>
        </div>
    </aside>

    <!-- Main View Content -->
    <main class="main-content">
        <!-- Top Bar Header -->
        <header class="top-header">
            <div class="page-title">
                <h1><?php echo isset($page_title) ? $page_title : 'Painel de Controle'; ?></h1>
                <p><?php echo isset($page_subtitle) ? $page_subtitle : 'Gerenciamento de backups dos equipamentos.'; ?></p>
            </div>
            <div class="top-header-actions">
                <!-- Data / Hora local em português -->
                <span style="color: var(--text-muted); font-size: 0.85rem;">
                    <?php echo date('d/m/Y H:i'); ?>
                </span>
            </div>
        </header>
