/**
 * JavaScript do Painel de Controle de Backup
 */

document.addEventListener('DOMContentLoaded', function() {
    // 1. Gerenciamento de Modais
    const openModalBtns = document.querySelectorAll('[data-modal-target]');
    const closeModalBtns = document.querySelectorAll('[data-modal-close]');
    const modals = document.querySelectorAll('.modal-overlay');

    openModalBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('data-modal-target');
            const modal = document.getElementById(targetId);
            if (modal) {
                modal.classList.add('active');
                document.body.style.overflow = 'hidden'; // Evita scroll ao fundo
            }
        });
    });

    closeModalBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const modal = this.closest('.modal-overlay');
            if (modal) {
                modal.classList.remove('active');
                document.body.style.overflow = '';
            }
        });
    });

    // Fecha modal clicando fora dele
    modals.forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.remove('active');
                document.body.style.overflow = '';
            }
        });
    });

    // 2. Pesquisa/Filtragem Dinâmica em Tabelas
    const searchInput = document.getElementById('table-search');
    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            const query = this.value.toLowerCase();
            const tableBody = document.querySelector('table tbody');
            if (!tableBody) return;
            
            const rows = tableBody.querySelectorAll('tr:not(.no-results)');
            let matches = 0;

            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(query)) {
                    row.style.display = '';
                    matches++;
                } else {
                    row.style.display = 'none';
                }
            });

            // Gerencia linha de "sem resultados" se necessário
            let noResultsRow = tableBody.querySelector('.no-results');
            if (matches === 0 && rows.length > 0) {
                if (!noResultsRow) {
                    noResultsRow = document.createElement('tr');
                    noResultsRow.className = 'no-results';
                    const cols = tableBody.closest('table').querySelectorAll('thead th').length;
                    noResultsRow.innerHTML = `<td colspan="${cols}" class="text-center empty-state" style="text-align: center; padding: 2rem;">
                        <svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                        <p>Nenhum registro encontrado</p>
                    </td>`;
                    tableBody.appendChild(noResultsRow);
                } else {
                    noResultsRow.style.display = '';
                }
            } else if (noResultsRow) {
                noResultsRow.style.display = 'none';
            }
        });
    }

    // 3. Efeitos de Confirmação Segura
    const confirmActions = document.querySelectorAll('.confirm-action');
    confirmActions.forEach(element => {
        element.addEventListener('click', function(e) {
            const message = this.getAttribute('data-confirm-message') || 'Tem certeza que deseja realizar esta ação?';
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    });

    // 4. Auto-ocultar alertas após 5 segundos
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s ease-out';
            alert.style.opacity = '0';
            setTimeout(() => {
                alert.remove();
            }, 500);
        }, 5000);
    });
});

/**
 * Preenche o modal de edição de dispositivo com os dados atuais
 */
function openEditDeviceModal(id, name, ip, model, status, description) {
    const modal = document.getElementById('editDeviceModal');
    if (!modal) return;

    modal.querySelector('#edit-device-id').value = id;
    modal.querySelector('#edit-device-name').value = name;
    modal.querySelector('#edit-device-ip').value = ip;
    modal.querySelector('#edit-device-model').value = model;
    modal.querySelector('#edit-device-status').value = status;
    modal.querySelector('#edit-device-description').value = description;

    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

/**
 * Preenche o modal de edição de usuário FTP com os dados atuais
 */
function openEditFtpUserModal(username, dir, uid, gid, ul_bandwidth, dl_bandwidth, status, device_id) {
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

    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
}
