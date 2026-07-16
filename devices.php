<?php
/**
 * Gerenciamento de Dispositivos (Equipamentos)
 */

$page_title = 'Dispositivos';
$page_subtitle = 'Cadastre e gerencie seus equipamentos e associe-os aos backups.';
require_once __DIR__ . '/header.php';

$success_msg = '';
$error_msg = '';

// Processamento de Ações (Add, Edit, Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $ip = trim($_POST['ip'] ?? '');
        $model = trim($_POST['model'] ?? '');
        $status = trim($_POST['status'] ?? 'active');
        $description = trim($_POST['description'] ?? '');
        
        if (empty($name)) {
            $error_msg = 'O nome do dispositivo é obrigatório.';
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO `devices` (`name`, `ip`, `model`, `status`, `description`) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$name, $ip, $model, $status, $description]);
                $success_msg = "Dispositivo '$name' cadastrado com sucesso!";
            } catch (PDOException $e) {
                $error_msg = "Erro ao cadastrar dispositivo: " . $e->getMessage();
            }
        }
    }
    
    if ($action === 'edit') {
        $id = intval($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $ip = trim($_POST['ip'] ?? '');
        $model = trim($_POST['model'] ?? '');
        $status = trim($_POST['status'] ?? 'active');
        $description = trim($_POST['description'] ?? '');
        
        if ($id <= 0 || empty($name)) {
            $error_msg = 'Dados inválidos para edição.';
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE `devices` SET `name` = ?, `ip` = ?, `model` = ?, `status` = ?, `description` = ? WHERE `id` = ?");
                $stmt->execute([$name, $ip, $model, $status, $description, $id]);
                $success_msg = "Dispositivo '$name' atualizado com sucesso!";
            } catch (PDOException $e) {
                $error_msg = "Erro ao editar dispositivo: " . $e->getMessage();
            }
        }
    }
}

// Ação de Deletar (via GET por simplicidade com confirmação segura confirm-action)
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id_to_delete = intval($_GET['delete']);
    try {
        // Busca o nome antes de deletar para fins de log de sucesso
        $stmt = $pdo->prepare("SELECT `name` FROM `devices` WHERE `id` = ?");
        $stmt->execute([$id_to_delete]);
        $device_name = $stmt->fetchColumn();
        
        if ($device_name) {
            $stmt = $pdo->prepare("DELETE FROM `devices` WHERE `id` = ?");
            $stmt->execute([$id_to_delete]);
            $success_msg = "Dispositivo '$device_name' excluído com sucesso!";
        }
    } catch (PDOException $e) {
        $error_msg = "Erro ao excluir dispositivo: " . $e->getMessage();
    }
}

// Busca todos os dispositivos com contagem e nomes de usuários FTP associados
try {
    $sql = "SELECT d.*, GROUP_CONCAT(f.User SEPARATOR ', ') AS ftp_users_list, COUNT(f.User) as ftp_count
            FROM devices d
            LEFT JOIN ftpd f ON d.id = f.device_id
            GROUP BY d.id
            ORDER BY d.name ASC";
    $devices = $pdo->query($sql)->fetchAll();
} catch (PDOException $e) {
    $devices = [];
    $error_msg = "Erro ao buscar dispositivos: " . $e->getMessage();
}
?>

<!-- Alert Feedback -->
<?php if ($success_msg): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($success_msg); ?></div>
<?php endif; ?>
<?php if ($error_msg): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error_msg); ?></div>
<?php endif; ?>

<!-- Actions & Filters Bar -->
<div class="filters-bar" style="justify-content: space-between;">
    <div style="position: relative; flex: 1; max-width: 320px;">
        <input type="text" id="table-search" class="form-control" placeholder="Buscar dispositivo por nome, IP, modelo..." style="padding-left: 2.5rem;">
        <svg viewBox="0 0 24 24" width="18" height="18" stroke="var(--text-muted)" stroke-width="2" fill="none" style="position: absolute; left: 0.85rem; top: 50%; transform: translateY(-50%);">
            <circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line>
        </svg>
    </div>
    
    <button class="btn btn-primary" data-modal-target="addDeviceModal">
        <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
        Adicionar Dispositivo
    </button>
</div>

<!-- Table View -->
<div class="section-container">
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Modelo</th>
                    <th>Endereço IP</th>
                    <th>Status</th>
                    <th>Usuários FTP Associados</th>
                    <th>Descrição</th>
                    <th style="width: 120px; text-align: right;">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($devices) > 0): ?>
                    <?php foreach ($devices as $dev): ?>
                        <tr>
                            <td style="font-weight: 600; color: white;">
                                <?php echo htmlspecialchars($dev['name']); ?>
                            </td>
                            <td>
                                <?php echo $dev['model'] ? htmlspecialchars($dev['model']) : '<span style="color: var(--text-muted);">Não especificado</span>'; ?>
                            </td>
                            <td style="font-family: monospace;">
                                <?php echo $dev['ip'] ? htmlspecialchars($dev['ip']) : '<span style="color: var(--text-muted);">Nenhum</span>'; ?>
                            </td>
                            <td>
                                <?php if ($dev['status'] === 'active'): ?>
                                    <span class="badge badge-success">Ativo</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Inativo</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($dev['ftp_count'] > 0): ?>
                                    <span class="badge badge-info" title="<?php echo htmlspecialchars($dev['ftp_users_list']); ?>">
                                        <?php echo $dev['ftp_count']; ?> conta(s) (<?php echo htmlspecialchars($dev['ftp_users_list']); ?>)
                                    </span>
                                <?php else: ?>
                                    <span style="color: var(--text-muted); font-size: 0.85rem;">Nenhuma conta associada</span>
                                <?php endif; ?>
                            </td>
                            <td style="max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: var(--text-secondary);" title="<?php echo htmlspecialchars($dev['description'] ?? ''); ?>">
                                <?php echo $dev['description'] ? htmlspecialchars($dev['description']) : '<span style="color: var(--text-muted); font-style: italic;">Sem descrição</span>'; ?>
                            </td>
                            <td style="text-align: right;">
                                <div style="display: inline-flex; gap: 0.5rem;">
                                    <!-- Editar dispositivo -->
                                    <button class="btn btn-sm" onclick="openEditDeviceModal(
                                        <?php echo $dev['id']; ?>, 
                                        '<?php echo addslashes($dev['name']); ?>', 
                                        '<?php echo addslashes($dev['ip'] ?? ''); ?>', 
                                        '<?php echo addslashes($dev['model'] ?? ''); ?>', 
                                        '<?php echo $dev['status']; ?>', 
                                        '<?php echo addslashes($dev['description'] ?? ''); ?>'
                                    )" title="Editar">
                                        <svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2" fill="none"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 1 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                    </button>
                                    <!-- Excluir dispositivo -->
                                    <a href="devices.php?delete=<?php echo $dev['id']; ?>" class="btn btn-sm btn-danger confirm-action" data-confirm-message="Deseja realmente excluir o dispositivo '<?php echo addslashes($dev['name']); ?>'? Todos os usuários FTP continuarão existindo, mas perderão a associação." title="Excluir">
                                        <svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2" fill="none"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr class="no-results">
                        <td colspan="7" class="text-center empty-state" style="text-align: center;">
                            <svg viewBox="0 0 24 24" stroke-width="2"><rect x="2" y="2" width="20" height="8" rx="2" ry="2"></rect><rect x="2" y="14" width="20" height="8" rx="2" ry="2"></rect></svg>
                            <p>Nenhum dispositivo cadastrado ainda.</p>
                            <p style="font-size: 0.85rem; margin-top: 0.25rem;">Clique no botão superior para cadastrar seu primeiro equipamento.</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Adicionar Dispositivo -->
<div class="modal-overlay" id="addDeviceModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Adicionar Dispositivo</h3>
            <button class="modal-close" data-modal-close>&times;</button>
        </div>
        <form action="" method="POST">
            <input type="hidden" name="action" value="add">
            
            <div class="form-group">
                <label for="name">Nome do Dispositivo *</label>
                <input type="text" id="name" name="name" class="form-control" placeholder="Ex: Router Borda Mikrotik" required>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="ip">Endereço IP</label>
                    <input type="text" id="ip" name="ip" class="form-control" placeholder="Ex: 192.168.1.1">
                </div>
                <div class="form-group">
                    <label for="model">Modelo / Hardware</label>
                    <input type="text" id="model" name="model" class="form-control" placeholder="Ex: RB4011">
                </div>
            </div>
            
            <div class="form-group">
                <label for="status">Status do Dispositivo</label>
                <select id="status" name="status" class="form-control">
                    <option value="active">Ativo</option>
                    <option value="inactive">Inativo</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="description">Descrição</label>
                <textarea id="description" name="description" class="form-control" rows="3" placeholder="Informações adicionais do equipamento..."></textarea>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn" data-modal-close>Cancelar</button>
                <button type="submit" class="btn btn-primary">Salvar Dispositivo</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Editar Dispositivo -->
<div class="modal-overlay" id="editDeviceModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Editar Dispositivo</h3>
            <button class="modal-close" data-modal-close>&times;</button>
        </div>
        <form action="" method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" id="edit-device-id" name="id">
            
            <div class="form-group">
                <label for="edit-device-name">Nome do Dispositivo *</label>
                <input type="text" id="edit-device-name" name="name" class="form-control" required>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="edit-device-ip">Endereço IP</label>
                    <input type="text" id="edit-device-ip" name="ip" class="form-control">
                </div>
                <div class="form-group">
                    <label for="edit-device-model">Modelo / Hardware</label>
                    <input type="text" id="edit-device-model" name="model" class="form-control">
                </div>
            </div>
            
            <div class="form-group">
                <label for="edit-device-status">Status do Dispositivo</label>
                <select id="edit-device-status" name="status" class="form-control">
                    <option value="active">Ativo</option>
                    <option value="inactive">Inativo</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="edit-device-description">Descrição</label>
                <textarea id="edit-device-description" name="description" class="form-control" rows="3"></textarea>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn" data-modal-close>Cancelar</button>
                <button type="submit" class="btn btn-primary">Salvar Alterações</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
