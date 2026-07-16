<?php
/**
 * Gerenciamento de Contas FTP (Pure-FTPd - Tabela ftpd de Produção)
 */

$page_title = 'Contas FTP';
$page_subtitle = 'Cadastre e configure contas de acesso para o Pure-FTPd.';
require_once __DIR__ . '/header.php';

$success_msg = '';
$error_msg = '';

// Função para criptografar senha conforme configuração
function encrypt_ftp_password($plain_password) {
    if (FTP_PASSWORD_HASH_MODE === 'md5') {
        return md5($plain_password);
    }
    return $plain_password; // Plain text (padrão em produção com base no dump)
}

// Processamento de Ações (Add, Edit, Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $username = trim($_POST['user'] ?? '');
        $password = $_POST['password'] ?? '';
        $dir = trim($_POST['dir'] ?? '');
        $uid = intval($_POST['uid'] ?? 2001); // UID 2001 padrão Debian/Wamp do dump
        $gid = intval($_POST['gid'] ?? 2001); // GID 2001 padrão Debian/Wamp do dump
        $ul_bandwidth = intval($_POST['ul_bandwidth'] ?? 0);
        $dl_bandwidth = intval($_POST['dl_bandwidth'] ?? 0);
        $status = intval($_POST['status'] ?? 1);
        $device_id = !empty($_POST['device_id']) ? intval($_POST['device_id']) : null;
        
        // Novos campos da tabela ftpd de produção
        $comment = trim($_POST['comment'] ?? '');
        $ipaccess = trim($_POST['ipaccess'] ?? '*');
        $quota_size = intval($_POST['quota_size'] ?? 0);
        $quota_files = intval($_POST['quota_files'] ?? 0);
        
        if (empty($username) || empty($password) || empty($dir)) {
            $error_msg = 'Usuário, senha e diretório são obrigatórios.';
        } else {
            try {
                // Verifica se usuário já existe
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM `ftpd` WHERE `User` = ?");
                $stmt->execute([$username]);
                if ($stmt->fetchColumn() > 0) {
                    $error_msg = "O usuário FTP '$username' já está cadastrado.";
                } else {
                    $encrypted_pass = encrypt_ftp_password($password);
                    
                    $stmt = $pdo->prepare("INSERT INTO `ftpd` (`User`, `Password`, `Dir`, `Uid`, `Gid`, `ULBandwidth`, `DLBandwidth`, `status`, `comment`, `ipaccess`, `QuotaSize`, `QuotaFiles`, `device_id`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$username, $encrypted_pass, $dir, $uid, $gid, $ul_bandwidth, $dl_bandwidth, $status, $comment, $ipaccess, $quota_size, $quota_files, $device_id]);
                    
                    // Cria dinamicamente a pasta física de simulação se aplicável
                    $local_path = get_real_ftp_path($dir);
                    if (!file_exists($local_path)) {
                        mkdir($local_path, 0777, true);
                    }
                    
                    $success_msg = "Usuário FTP '$username' criado com sucesso!";
                }
            } catch (PDOException $e) {
                $error_msg = "Erro ao criar usuário FTP: " . $e->getMessage();
            }
        }
    }
    
    if ($action === 'edit') {
        $old_username = trim($_POST['old_user'] ?? '');
        $username = trim($_POST['user'] ?? '');
        $password = $_POST['password'] ?? '';
        $dir = trim($_POST['dir'] ?? '');
        $uid = intval($_POST['uid'] ?? 2001);
        $gid = intval($_POST['gid'] ?? 2001);
        $ul_bandwidth = intval($_POST['ul_bandwidth'] ?? 0);
        $dl_bandwidth = intval($_POST['dl_bandwidth'] ?? 0);
        $status = intval($_POST['status'] ?? 1);
        $device_id = !empty($_POST['device_id']) ? intval($_POST['device_id']) : null;
        
        // Novos campos da tabela ftpd de produção
        $comment = trim($_POST['comment'] ?? '');
        $ipaccess = trim($_POST['ipaccess'] ?? '*');
        $quota_size = intval($_POST['quota_size'] ?? 0);
        $quota_files = intval($_POST['quota_files'] ?? 0);
        
        if (empty($old_username) || empty($username) || empty($dir)) {
            $error_msg = 'Usuário e diretório são obrigatórios para edição.';
        } else {
            try {
                if (!empty($password)) {
                    $encrypted_pass = encrypt_ftp_password($password);
                    $stmt = $pdo->prepare("UPDATE `ftpd` SET `User` = ?, `Password` = ?, `Dir` = ?, `Uid` = ?, `Gid` = ?, `ULBandwidth` = ?, `DLBandwidth` = ?, `status` = ?, `comment` = ?, `ipaccess` = ?, `QuotaSize` = ?, `QuotaFiles` = ?, `device_id` = ? WHERE `User` = ?");
                    $stmt->execute([$username, $encrypted_pass, $dir, $uid, $gid, $ul_bandwidth, $dl_bandwidth, $status, $comment, $ipaccess, $quota_size, $quota_files, $device_id, $old_username]);
                } else {
                    $stmt = $pdo->prepare("UPDATE `ftpd` SET `User` = ?, `Dir` = ?, `Uid` = ?, `Gid` = ?, `ULBandwidth` = ?, `DLBandwidth` = ?, `status` = ?, `comment` = ?, `ipaccess` = ?, `QuotaSize` = ?, `QuotaFiles` = ?, `device_id` = ? WHERE `User` = ?");
                    $stmt->execute([$username, $dir, $uid, $gid, $ul_bandwidth, $dl_bandwidth, $status, $comment, $ipaccess, $quota_size, $quota_files, $device_id, $old_username]);
                }
                
                $local_path = get_real_ftp_path($dir);
                if (!file_exists($local_path)) {
                    mkdir($local_path, 0777, true);
                }
                
                $success_msg = "Usuário FTP '$username' atualizado com sucesso!";
            } catch (PDOException $e) {
                $error_msg = "Erro ao editar usuário FTP: " . $e->getMessage();
            }
        }
    }
}

// Ação de Deletar
if (isset($_GET['delete'])) {
    $user_to_delete = trim($_GET['delete']);
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM `ftpd` WHERE `User` = ?");
        $stmt->execute([$user_to_delete]);
        if ($stmt->fetchColumn() > 0) {
            $stmt = $pdo->prepare("DELETE FROM `ftpd` WHERE `User` = ?");
            $stmt->execute([$user_to_delete]);
            $success_msg = "Usuário FTP '$user_to_delete' excluído com sucesso!";
        }
    } catch (PDOException $e) {
        $error_msg = "Erro ao excluir usuário FTP: " . $e->getMessage();
    }
}

// Buscar usuários da tabela ftpd e dispositivos
try {
    $ftp_users = $pdo->query("SELECT f.*, d.name as device_name FROM ftpd f LEFT JOIN devices d ON f.device_id = d.id ORDER BY f.User ASC")->fetchAll();
    $devices = $pdo->query("SELECT id, name FROM devices ORDER BY name ASC")->fetchAll();
} catch (PDOException $e) {
    $ftp_users = [];
    $devices = [];
    $error_msg = "Erro ao buscar dados: " . $e->getMessage();
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
        <input type="text" id="table-search" class="form-control" placeholder="Buscar usuário por login, diretório..." style="padding-left: 2.5rem;">
        <svg viewBox="0 0 24 24" width="18" height="18" stroke="var(--text-muted)" stroke-width="2" fill="none" style="position: absolute; left: 0.85rem; top: 50%; transform: translateY(-50%);">
            <circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line>
        </svg>
    </div>
    
    <button class="btn btn-primary" data-modal-target="addFtpUserModal">
        <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
        Criar Usuário FTP
    </button>
</div>

<!-- Table View -->
<div class="section-container">
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Usuário (FTP)</th>
                    <th>Diretório (Home)</th>
                    <th>Dispositivo Associado</th>
                    <th>UID/GID</th>
                    <th>Restrição IP</th>
                    <th>Quotas (Size/Files)</th>
                    <th>Status</th>
                    <th style="width: 120px; text-align: right;">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($ftp_users) > 0): ?>
                    <?php foreach ($ftp_users as $user): ?>
                        <tr>
                            <td style="font-weight: 600; color: white; font-family: monospace;" title="<?php echo htmlspecialchars($user['comment'] ?? ''); ?>">
                                <?php echo htmlspecialchars($user['User']); ?>
                                <?php if (!empty($user['comment'])): ?>
                                    <span style="display:block; font-size:0.75rem; color:var(--text-muted); font-weight:normal; font-family:var(--font-family);">
                                        <?php echo htmlspecialchars($user['comment']); ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td style="font-family: monospace; font-size: 0.85rem;">
                                <?php echo htmlspecialchars($user['Dir']); ?>
                            </td>
                            <td>
                                <?php if ($user['device_name']): ?>
                                    <span class="badge badge-info"><?php echo htmlspecialchars($user['device_name']); ?></span>
                                <?php else: ?>
                                    <span style="color: var(--text-muted); font-size: 0.85rem;">Nenhum dispositivo</span>
                                <?php endif; ?>
                            </td>
                            <td style="font-size: 0.8rem; color: var(--text-secondary);">
                                <?php echo $user['Uid'] . ' / ' . $user['Gid']; ?>
                            </td>
                            <td style="font-family: monospace; font-size: 0.85rem;">
                                <?php echo htmlspecialchars($user['ipaccess']); ?>
                            </td>
                            <td style="font-size: 0.8rem; color: var(--text-secondary);">
                                <?php 
                                    $q_size = $user['QuotaSize'] > 0 ? $user['QuotaSize'] . ' MB' : 'Ilimitada';
                                    $q_files = $user['QuotaFiles'] > 0 ? $user['QuotaFiles'] . ' arqs' : 'Ilimitados';
                                    echo "Espaço: $q_size <br> Arqs: $q_files";
                                ?>
                            </td>
                            <td>
                                <?php if (intval($user['status']) === 1): ?>
                                    <span class="badge badge-success">Ativo</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Bloqueado</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: right;">
                                <div style="display: inline-flex; gap: 0.5rem;">
                                    <!-- Editar usuário FTP -->
                                    <button class="btn btn-sm" onclick="openEditFtpUserModal(
                                        '<?php echo addslashes($user['User']); ?>', 
                                        '<?php echo addslashes($user['Dir']); ?>', 
                                        <?php echo $user['Uid']; ?>, 
                                        <?php echo $user['Gid']; ?>, 
                                        <?php echo $user['ULBandwidth']; ?>, 
                                        <?php echo $user['DLBandwidth']; ?>, 
                                        <?php echo $user['status']; ?>, 
                                        '<?php echo $user['device_id'] ?? ''; ?>',
                                        '<?php echo addslashes($user['comment'] ?? ''); ?>',
                                        '<?php echo addslashes($user['ipaccess'] ?? '*'); ?>',
                                        <?php echo $user['QuotaSize'] ?? 0; ?>,
                                        <?php echo $user['QuotaFiles'] ?? 0; ?>
                                    )" title="Editar">
                                        <svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2" fill="none"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 1 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                    </button>
                                    <!-- Excluir usuário FTP -->
                                    <a href="ftp_users.php?delete=<?php echo urlencode($user['User']); ?>" class="btn btn-sm btn-danger confirm-action" data-confirm-message="Deseja realmente excluir o usuário FTP '<?php echo addslashes($user['User']); ?>'? Os arquivos físicos não serão excluídos." title="Excluir">
                                        <svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2" fill="none"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr class="no-results">
                        <td colspan="8" class="text-center empty-state" style="text-align: center;">
                            <svg viewBox="0 0 24 24" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle></svg>
                            <p>Nenhuma conta FTP cadastrada.</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Criar Usuário FTP -->
<div class="modal-overlay" id="addFtpUserModal">
    <div class="modal-content" style="max-width: 600px;">
        <div class="modal-header">
            <h3 class="modal-title">Criar Conta FTP</h3>
            <button class="modal-close" data-modal-close>&times;</button>
        </div>
        <form action="" method="POST">
            <input type="hidden" name="action" value="add">
            
            <div class="form-row">
                <div class="form-group">
                    <label for="add-user">Login (Usuário) *</label>
                    <input type="text" id="add-user" name="user" class="form-control" placeholder="Ex: router_core" required>
                </div>
                <div class="form-group">
                    <label for="add-password">Senha FTP *</label>
                    <input type="password" id="add-password" name="password" class="form-control" placeholder="Digite uma senha" required autocomplete="new-password">
                </div>
            </div>
            
            <div class="form-group">
                <label for="add-dir">Diretório Home *</label>
                <input type="text" id="add-dir" name="dir" class="form-control" placeholder="Ex: /var/pure-ftpd/router_core" required>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="add-device-id">Dispositivo Associado</label>
                    <select id="add-device-id" name="device_id" class="form-control">
                        <option value="">-- Nenhum dispositivo --</option>
                        <?php foreach ($devices as $dev): ?>
                            <option value="<?php echo $dev['id']; ?>"><?php echo htmlspecialchars($dev['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="add-ipaccess">Restrição de IP</label>
                    <input type="text" id="add-ipaccess" name="ipaccess" class="form-control" value="*">
                </div>
            </div>

            <div class="form-group">
                <label for="add-comment">Comentário / Observação</label>
                <input type="text" id="add-comment" name="comment" class="form-control" placeholder="Ex: Backup do Switch Central">
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="add-uid">UID (User ID)</label>
                    <input type="number" id="add-uid" name="uid" class="form-control" value="2001" required>
                </div>
                <div class="form-group">
                    <label for="add-gid">GID (Group ID)</label>
                    <input type="number" id="add-gid" name="gid" class="form-control" value="2001" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="add-quota-size">Quota de Espaço (MB)</label>
                    <input type="number" id="add-quota-size" name="quota_size" class="form-control" value="0" placeholder="0 = ilimitado">
                </div>
                <div class="form-group">
                    <label for="add-quota-files">Quota de Arquivos</label>
                    <input type="number" id="add-quota-files" name="quota_files" class="form-control" value="0" placeholder="0 = ilimitado">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="add-ul-bandwidth">Limite Upload (KB/s)</label>
                    <input type="number" id="add-ul-bandwidth" name="ul_bandwidth" class="form-control" value="0">
                </div>
                <div class="form-group">
                    <label for="add-dl-bandwidth">Limite Download (KB/s)</label>
                    <input type="number" id="add-dl-bandwidth" name="dl_bandwidth" class="form-control" value="0">
                </div>
            </div>
            
            <div class="form-group">
                <label for="add-status">Status da Conta</label>
                <select id="add-status" name="status" class="form-control">
                    <option value="1">Ativo</option>
                    <option value="0">Bloqueado</option>
                </select>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn" data-modal-close>Cancelar</button>
                <button type="submit" class="btn btn-primary">Criar Usuário</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Editar Usuário FTP -->
<div class="modal-overlay" id="editFtpUserModal">
    <div class="modal-content" style="max-width: 600px;">
        <div class="modal-header">
            <h3 class="modal-title">Editar Conta FTP</h3>
            <button class="modal-close" data-modal-close>&times;</button>
        </div>
        <form action="" method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" id="edit-ftp-old-user" name="old_user">
            
            <div class="form-row">
                <div class="form-group">
                    <label for="edit-ftp-user">Login (Usuário) *</label>
                    <input type="text" id="edit-ftp-user" name="user" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="edit-ftp-password">Senha FTP (Vazio para manter a atual)</label>
                    <input type="password" id="edit-ftp-password" name="password" class="form-control" placeholder="••••••••" autocomplete="new-password">
                </div>
            </div>
            
            <div class="form-group">
                <label for="edit-ftp-dir">Diretório Home *</label>
                <input type="text" id="edit-ftp-dir" name="dir" class="form-control" required>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="edit-ftp-device-id">Dispositivo Associado</label>
                    <select id="edit-ftp-device-id" name="device_id" class="form-control">
                        <option value="">-- Nenhum dispositivo --</option>
                        <?php foreach ($devices as $dev): ?>
                            <option value="<?php echo $dev['id']; ?>"><?php echo htmlspecialchars($dev['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="edit-ftp-ipaccess">Restrição de IP</label>
                    <input type="text" id="edit-ftp-ipaccess" name="ipaccess" class="form-control">
                </div>
            </div>

            <div class="form-group">
                <label for="edit-ftp-comment">Comentário / Observação</label>
                <input type="text" id="edit-ftp-comment" name="comment" class="form-control">
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="edit-ftp-uid">UID (User ID)</label>
                    <input type="number" id="edit-ftp-uid" name="uid" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="edit-ftp-gid">GID (Group ID)</label>
                    <input type="number" id="edit-ftp-gid" name="gid" class="form-control" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="edit-ftp-quota-size">Quota de Espaço (MB)</label>
                    <input type="number" id="edit-ftp-quota-size" name="quota_size" class="form-control">
                </div>
                <div class="form-group">
                    <label for="edit-ftp-quota-files">Quota de Arquivos</label>
                    <input type="number" id="edit-ftp-quota-files" name="quota_files" class="form-control">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="edit-ftp-ul-bandwidth">Limite Upload (KB/s)</label>
                    <input type="number" id="edit-ftp-ul-bandwidth" name="ul_bandwidth" class="form-control">
                </div>
                <div class="form-group">
                    <label for="edit-ftp-dl-bandwidth">Limite Download (KB/s)</label>
                    <input type="number" id="edit-ftp-dl-bandwidth" name="dl_bandwidth" class="form-control">
                </div>
            </div>
            
            <div class="form-group">
                <label for="edit-ftp-status">Status da Conta</label>
                <select id="edit-ftp-status" name="status" class="form-control">
                    <option value="1">Ativo</option>
                    <option value="0">Bloqueado</option>
                </select>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn" data-modal-close>Cancelar</button>
                <button type="submit" class="btn btn-primary">Salvar Alterações</button>
            </div>
        </form>
    </div>
</div>

<!-- Script Adicional para auto-sugestão e preenchimento no modal de Edição -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const addUsernameInput = document.getElementById('add-user');
    const addDirInput = document.getElementById('add-dir');
    
    if (addUsernameInput && addDirInput) {
        addUsernameInput.addEventListener('input', function() {
            const val = this.value.trim().toLowerCase().replace(/[^a-z0-9_\-]/g, '');
            const remotePrefix = '<?php echo rtrim(FTP_BASE_DIR_REMOTE_PREFIX, "/\\"); ?>';
            if (val) {
                addDirInput.value = remotePrefix + '/' + val;
            } else {
                addDirInput.value = '';
            }
        });
    }
});

// Sobrescreve a função global para contemplar os novos campos de produção
function openEditFtpUserModal(username, dir, uid, gid, ul_bandwidth, dl_bandwidth, status, device_id, comment, ipaccess, quota_size, quota_files) {
    const modal = document.getElementById('editFtpUserModal');
    if (!modal) return;

    modal.querySelector('#edit-ftp-user').value = username;
    modal.querySelector('#edit-ftp-old-user').value = username;
    modal.querySelector('#edit-ftp-dir').value = dir;
    modal.querySelector('#edit-ftp-uid').value = uid;
    modal.querySelector('#edit-ftp-gid').value = gid;
    modal.querySelector('#edit-ftp-ul-bandwidth').value = ul_bandwidth;
    modal.querySelector('#edit-ftp-dl-bandwidth').value = dl_bandwidth;
    modal.querySelector('#edit-ftp-status').value = status;
    modal.querySelector('#edit-ftp-device-id').value = device_id;
    
    modal.querySelector('#edit-ftp-comment').value = comment;
    modal.querySelector('#edit-ftp-ipaccess').value = ipaccess;
    modal.querySelector('#edit-ftp-quota-size').value = quota_size;
    modal.querySelector('#edit-ftp-quota-files').value = quota_files;

    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
