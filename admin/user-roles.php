<?php
session_start();
require_once '../config/database.php';
require_once '../config/config.php';
require_once '../includes/functions.php';

// Admin kontrolü
requireAdmin();

// Form işlemleri
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlashMessage('Geçersiz form gönderimi.', 'danger');
        redirect('user-roles.php');
    }

    $action = $_POST['action'] ?? '';
    
    try {
        $db->beginTransaction();
        
        switch ($action) {
            case 'add':
                // Yeni rol ekleme
                $name = trim($_POST['name'] ?? '');
                $description = trim($_POST['description'] ?? '');
                
                if (empty($name)) {
                    throw new Exception('Rol adı boş olamaz.');
                }
                
                // Rol adının benzersiz olduğunu kontrol et
                $stmt = $db->prepare("SELECT COUNT(*) FROM user_roles WHERE name = ?");
                $stmt->execute([$name]);
                if ($stmt->fetchColumn() > 0) {
                    throw new Exception('Bu rol adı zaten kullanılıyor.');
                }
                
                $stmt = $db->prepare("INSERT INTO user_roles (name, description) VALUES (?, ?)");
                $stmt->execute([$name, $description]);
                
                logAdminActivity('role_add', [
                    'role_name' => $name,
                    'description' => $description
                ]);
                
                setFlashMessage('Rol başarıyla eklendi.', 'success');
                break;
                
            case 'edit':
                // Rol düzenleme
                $id = filter_var($_POST['id'] ?? 0, FILTER_VALIDATE_INT);
                $name = trim($_POST['name'] ?? '');
                $description = trim($_POST['description'] ?? '');
                
                if (!$id || empty($name)) {
                    throw new Exception('Geçersiz rol bilgileri.');
                }
                
                // Rol adının benzersiz olduğunu kontrol et
                $stmt = $db->prepare("SELECT COUNT(*) FROM user_roles WHERE name = ? AND id != ?");
                $stmt->execute([$name, $id]);
                if ($stmt->fetchColumn() > 0) {
                    throw new Exception('Bu rol adı zaten kullanılıyor.');
                }
                
                $stmt = $db->prepare("UPDATE user_roles SET name = ?, description = ? WHERE id = ?");
                $stmt->execute([$name, $description, $id]);
                
                logAdminActivity('role_edit', [
                    'role_id' => $id,
                    'role_name' => $name,
                    'description' => $description
                ]);
                
                setFlashMessage('Rol başarıyla güncellendi.', 'success');
                break;
                
            case 'delete':
                // Rol silme
                $id = filter_var($_POST['id'] ?? 0, FILTER_VALIDATE_INT);
                
                if (!$id) {
                    throw new Exception('Geçersiz rol ID\'si.');
                }
                
                // Rolün kullanımda olmadığını kontrol et
                $stmt = $db->prepare("SELECT COUNT(*) FROM user_role_relations WHERE role_id = ?");
                $stmt->execute([$id]);
                if ($stmt->fetchColumn() > 0) {
                    throw new Exception('Bu rol kullanıcılara atanmış durumda, önce bu atamayı kaldırın.');
                }
                
                $stmt = $db->prepare("DELETE FROM user_roles WHERE id = ?");
                $stmt->execute([$id]);
                
                logAdminActivity('role_delete', ['role_id' => $id]);
                
                setFlashMessage('Rol başarıyla silindi.', 'success');
                break;
                
            case 'assign':
                // Rol atama
                $user_id = filter_var($_POST['user_id'] ?? 0, FILTER_VALIDATE_INT);
                $role_id = filter_var($_POST['role_id'] ?? 0, FILTER_VALIDATE_INT);
                
                if (!$user_id || !$role_id) {
                    throw new Exception('Geçersiz kullanıcı veya rol ID\'si.');
                }
                
                if (!assignRole($user_id, $role_id, $_SESSION['user_id'])) {
                    throw new Exception('Rol atama işlemi başarısız oldu.');
                }
                
                logAdminActivity('role_assign', [
                    'user_id' => $user_id,
                    'role_id' => $role_id
                ]);
                
                setFlashMessage('Rol başarıyla atandı.', 'success');
                break;
                
            case 'revoke':
                // Rol alma
                $user_id = filter_var($_POST['user_id'] ?? 0, FILTER_VALIDATE_INT);
                $role_id = filter_var($_POST['role_id'] ?? 0, FILTER_VALIDATE_INT);
                
                if (!$user_id || !$role_id) {
                    throw new Exception('Geçersiz kullanıcı veya rol ID\'si.');
                }
                
                if (!revokeRole($user_id, $role_id)) {
                    throw new Exception('Rol alma işlemi başarısız oldu.');
                }
                
                logAdminActivity('role_revoke', [
                    'user_id' => $user_id,
                    'role_id' => $role_id
                ]);
                
                setFlashMessage('Rol başarıyla alındı.', 'success');
                break;
        }
        
        $db->commit();
        
    } catch (Exception $e) {
        $db->rollBack();
        setFlashMessage($e->getMessage(), 'danger');
    }
    
    redirect('user-roles.php');
}

// Rolleri getir
$roles = $db->query("
    SELECT r.*, 
           (SELECT COUNT(*) FROM user_role_relations WHERE role_id = r.id) as user_count,
           GROUP_CONCAT(DISTINCT p.permission_name) as permissions
    FROM user_roles r
    LEFT JOIN role_permissions p ON r.id = p.role_id
    GROUP BY r.id, r.name, r.description, r.created_at
    ORDER BY r.name
")->fetchAll();

$page_title = 'Kullanıcı Rolleri';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> - Admin Paneli</title>
    <!-- Tabler.io CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta17/dist/css/tabler.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="page">
        <?php require_once 'includes/sidebar.php'; ?>
        
        <div class="page-wrapper">
            <!-- Sayfa başlığı -->
            <div class="page-header d-print-none">
                <div class="container-xl">
                    <div class="row g-2 align-items-center">
                        <div class="col">
                            <h2 class="page-title">
                                <?= $page_title ?>
                            </h2>
                        </div>
                        <div class="col-auto ms-auto">
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addRoleModal">
                                <i class="fas fa-plus"></i> Yeni Rol Ekle
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Sayfa içeriği -->
            <div class="page-body">
                <div class="container-xl">
                    <div class="card">
                        <div class="table-responsive">
                            <table class="table table-vcenter card-table">
                                <thead>
                                    <tr>
                                        <th>Rol Adı</th>
                                        <th>Açıklama</th>
                                        <th>İzinler</th>
                                        <th>Kullanıcı Sayısı</th>
                                        <th>Oluşturulma</th>
                                        <th class="w-1"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($roles as $role): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($role['name']) ?></td>
                                        <td class="text-muted">
                                            <?= htmlspecialchars($role['description'] ?? '') ?>
                                        </td>
                                        <td>
                                            <?php if ($role['permissions']): ?>
                                                <?php foreach (explode(',', $role['permissions']) as $permission): ?>
                                                    <span class="badge bg-blue me-1">
                                                        <?= htmlspecialchars($permission) ?>
                                                    </span>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <span class="text-muted">İzin yok</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="user-count-display"><?= $role['user_count'] ?></span>
                                            <?php if ($role['user_count'] > 0): ?>
                                            <a href="#" class="ms-2 text-primary" 
                                               onclick="viewRoleUsers(<?= $role['id'] ?>, '<?= htmlspecialchars($role['name']) ?>')">
                                                <i class="fas fa-eye me-1"></i>Görüntüle
                                            </a>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-muted">
                                            <?= formatDate($role['created_at']) ?>
                                        </td>
                                        <td>
                                            <div class="btn-list flex-nowrap">
                                                <button class="btn btn-icon btn-primary" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#editRoleModal"
                                                        data-role-id="<?= $role['id'] ?>"
                                                        data-role-name="<?= htmlspecialchars($role['name']) ?>"
                                                        data-role-description="<?= htmlspecialchars($role['description'] ?? '') ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <?php if ($role['user_count'] == 0): ?>
                                                <form method="post" class="d-inline" 
                                                      onsubmit="return confirm('Bu rolü silmek istediğinize emin misiniz?')">
                                                    <?= getCSRFToken() ?>
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id" value="<?= $role['id'] ?>">
                                                    <button type="submit" class="btn btn-icon btn-danger">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php require_once 'includes/footer.php'; ?>
        </div>
    </div>
    
    <!-- Yeni Rol Ekleme Modal -->
    <div class="modal modal-blur fade" id="addRoleModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <form method="post">
                    <?= getCSRFToken() ?>
                    <input type="hidden" name="action" value="add">
                    
                    <div class="modal-header">
                        <h5 class="modal-title">Yeni Rol Ekle</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label required">Rol Adı</label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Açıklama</label>
                            <textarea class="form-control" name="description" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">
                            İptal
                        </button>
                        <button type="submit" class="btn btn-primary ms-auto">
                            Rol Ekle
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Rol Düzenleme Modal -->
    <div class="modal modal-blur fade" id="editRoleModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <form method="post">
                    <?= getCSRFToken() ?>
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="editRoleId">
                    
                    <div class="modal-header">
                        <h5 class="modal-title">Rol Düzenle</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label required">Rol Adı</label>
                            <input type="text" class="form-control" name="name" id="editRoleName" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Açıklama</label>
                            <textarea class="form-control" name="description" id="editRoleDescription" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">
                            İptal
                        </button>
                        <button type="submit" class="btn btn-primary ms-auto">
                            Değişiklikleri Kaydet
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Kullanıcıları Görüntüleme Modal -->
    <div class="modal modal-blur fade" id="viewUsersModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Rol Kullanıcıları: <span id="roleNameTitle"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-vcenter">
                            <thead>
                                <tr>
                                    <th>Kullanıcı Adı</th>
                                    <th>E-posta</th>
                                    <th>Atanma Tarihi</th>
                                    <th>İşlem</th>
                                </tr>
                            </thead>
                            <tbody id="roleUsersTableBody">
                                <tr>
                                    <td colspan="4" class="text-center">Yükleniyor...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">
                        Kapat
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabler Core -->
    <script src="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/js/tabler.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Rol düzenleme modalı için veri aktarımı
        const editRoleModal = document.getElementById('editRoleModal');
        if (editRoleModal) {
            editRoleModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const roleId = button.getAttribute('data-role-id');
                const roleName = button.getAttribute('data-role-name');
                const roleDescription = button.getAttribute('data-role-description');
                
                editRoleModal.querySelector('#editRoleId').value = roleId;
                editRoleModal.querySelector('#editRoleName').value = roleName;
                editRoleModal.querySelector('#editRoleDescription').value = roleDescription;
            });
        }
    });

    // Rol kullanıcılarını görüntüleme fonksiyonu
    function viewRoleUsers(roleId, roleName) {
        const modal = new bootstrap.Modal(document.getElementById('viewUsersModal'));
        document.getElementById('roleNameTitle').textContent = roleName;
        document.getElementById('roleUsersTableBody').innerHTML = `
            <tr>
                <td colspan="4" class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Yükleniyor...</span>
                    </div>
                </td>
            </tr>
        `;
        
        // AJAX isteği ile kullanıcıları getir
        fetch(`api/get_role_users.php?role_id=${roleId}`)
            .then(async response => {
                console.log('API Response Status:', response.status);
                console.log('API Response Headers:', [...response.headers.entries()]);
                
                const responseText = await response.text();
                console.log('API Response Text:', responseText);
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${responseText}`);
                }
                
                try {
                    return JSON.parse(responseText);
                } catch (e) {
                    throw new Error(`JSON Parse Error: ${e.message}\nResponse: ${responseText}`);
                }
            })
            .then(response => {
                if (!response.success) {
                    throw new Error(response.error || 'Bilinmeyen bir hata oluştu');
                }
                
                const tbody = document.getElementById('roleUsersTableBody');
                tbody.innerHTML = '';
                
                const users = response.data;
                if (users.length === 0) {
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="4" class="text-center">Bu role atanmış kullanıcı bulunmuyor.</td>
                        </tr>
                    `;
                    return;
                }
                
                users.forEach(user => {
                    tbody.innerHTML += `
                        <tr>
                            <td>${user.username}</td>
                            <td>${user.email}</td>
                            <td>${user.assigned_at}</td>
                            <td>
                                <form method="post" class="d-inline" onsubmit="return confirm('Bu kullanıcının rol atamasını kaldırmak istediğinize emin misiniz?')">
                                    <?= getCSRFToken() ?>
                                    <input type="hidden" name="action" value="revoke">
                                    <input type="hidden" name="user_id" value="${user.id}">
                                    <input type="hidden" name="role_id" value="${roleId}">
                                    <button type="submit" class="btn btn-sm btn-danger">
                                        <i class="fas fa-times me-1"></i>Rolü Kaldır
                                    </button>
                                </form>
                            </td>
                        </tr>
                    `;
                });
                
                // Kullanıcı sayısını güncelle
                const userCountCell = document.querySelector(`tr:has([data-role-id="${roleId}"]) .user-count-display`);
                if (userCountCell) {
                    userCountCell.textContent = users.length;
                }
            })
            .catch(error => {
                console.error('API Hatası:', error);
                document.getElementById('roleUsersTableBody').innerHTML = `
                    <tr>
                        <td colspan="4" class="text-center text-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Kullanıcılar yüklenirken bir hata oluştu: ${error.message}
                        </td>
                    </tr>
                `;
            });
        
        modal.show();
    }
    </script>
</body>
</html> 