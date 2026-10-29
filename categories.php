<?php
/**
 * Category Management (Administrator Only)
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

require_role('admin');

$pdo = get_db();
$error = '';

// Handle Create or Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        set_flash('danger', 'Security session expired. Please try again.');
        header('Location: categories.php');
        exit;
    }

    $action = $_POST['action'];

    if ($action === 'create_or_update') {
        $catId = (int)($_POST['category_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $status = in_array($_POST['status'] ?? '', ['active', 'inactive'], true) ? $_POST['status'] : 'active';

        if (empty($name)) {
            set_flash('danger', 'Category name is required.');
        } else {
            if ($catId > 0) {
                // Update
                $stmt = $pdo->prepare("UPDATE categories SET name = ?, description = ?, status = ? WHERE id = ?");
                $stmt->execute([$name, $description, $status, $catId]);
                set_flash('success', "Category '{$name}' updated successfully.");
            } else {
                // Create
                $stmt = $pdo->prepare("INSERT INTO categories (name, description, status, created_at) VALUES (?, ?, ?, NOW())");
                $stmt->execute([$name, $description, $status]);
                set_flash('success', "New category '{$name}' created successfully.");
            }
        }
        header('Location: categories.php');
        exit;
    }

    if ($action === 'delete') {
        $catId = (int)($_POST['category_id'] ?? 0);
        if ($catId > 0) {
            // Nullify category references on existing tickets first
            $pdo->prepare("UPDATE tickets SET category_id = NULL WHERE category_id = ?")->execute([$catId]);
            $pdo->prepare("DELETE FROM categories WHERE id = ?")->execute([$catId]);
            set_flash('success', 'Category deleted successfully.');
        }
        header('Location: categories.php');
        exit;
    }
}

// Fetch all categories with ticket count
$categories = $pdo->query("
    SELECT c.*, COUNT(t.id) AS ticket_count
    FROM categories c
    LEFT JOIN tickets t ON c.id = t.category_id
    GROUP BY c.id
    ORDER BY c.name ASC
")->fetchAll();

$editCategory = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    foreach ($categories as $cat) {
        if ($cat['id'] === $editId) {
            $editCategory = $cat;
            break;
        }
    }
}

$page_title = 'Category Management';
include __DIR__ . '/includes/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <h2 class="fw-bold mb-1"><i class="bi bi-tags text-primary me-2"></i>Ticket Categories</h2>
        <p class="text-muted mb-0">Organize and classify technical issues for efficient routing</p>
    </div>
</div>

<div class="row g-4">
    <!-- Categories List Table -->
    <div class="col-lg-7">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <h5 class="fw-bold mb-0">Available Categories (<?php echo count($categories); ?>)</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="ps-4">Category Name</th>
                                <th>Description</th>
                                <th>Tickets</th>
                                <th>Status</th>
                                <th class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categories as $cat): ?>
                                <tr class="<?php echo ($editCategory && $editCategory['id'] === $cat['id']) ? 'table-primary' : ''; ?>">
                                    <td class="ps-4 fw-bold text-dark">
                                        <?php echo e($cat['name']); ?>
                                    </td>
                                    <td class="small text-muted" style="max-width: 250px;">
                                        <?php echo e($cat['description'] ?: '—'); ?>
                                    </td>
                                    <td>
                                        <a href="tickets.php?category=<?php echo $cat['id']; ?>" class="badge bg-secondary-subtle text-secondary text-decoration-none">
                                            <?php echo $cat['ticket_count']; ?> ticket<?php echo $cat['ticket_count'] == 1 ? '' : 's'; ?>
                                        </a>
                                    </td>
                                    <td>
                                        <?php if ($cat['status'] === 'active'): ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle">Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary-subtle text-secondary border">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end pe-4">
                                        <div class="d-inline-flex gap-1">
                                            <a href="categories.php?edit=<?php echo $cat['id']; ?>" class="btn btn-sm btn-outline-primary" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <form method="POST" action="categories.php" class="d-inline" onsubmit="return confirm('Delete category <?php echo e($cat['name']); ?>? Existing tickets will be set to uncategorized.');">
                                                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="category_id" value="<?php echo $cat['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                                    <i class="bi bi-trash3"></i>
                                                </button>
                                            </form>
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

    <!-- Add / Edit Form -->
    <div class="col-lg-5">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0">
                    <i class="bi <?php echo $editCategory ? 'bi-pencil-square' : 'bi-plus-circle'; ?> text-primary me-1"></i>
                    <?php echo $editCategory ? 'Edit Category' : 'Add New Category'; ?>
                </h5>
                <?php if ($editCategory): ?>
                    <a href="categories.php" class="btn btn-sm btn-outline-secondary">Add New Instead</a>
                <?php endif; ?>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="categories.php" data-prevent-double-submit="true">
                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                    <input type="hidden" name="action" value="create_or_update">
                    <input type="hidden" name="category_id" value="<?php echo $editCategory['id'] ?? 0; ?>">

                    <div class="mb-3">
                        <label for="name" class="form-label">Category Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" value="<?php echo e($editCategory['name'] ?? ''); ?>" placeholder="e.g. Network & Connectivity" required autofocus>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3" placeholder="Brief summary of what falls into this category"><?php echo e($editCategory['description'] ?? ''); ?></textarea>
                    </div>

                    <div class="mb-4">
                        <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="active" <?php echo (($editCategory['status'] ?? 'active') === 'active') ? 'selected' : ''; ?>>Active (Visible to users)</option>
                            <option value="inactive" <?php echo (($editCategory['status'] ?? '') === 'inactive') ? 'selected' : ''; ?>>Inactive (Hidden)</option>
                        </select>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check2-circle me-1"></i>
                            <?php echo $editCategory ? 'Update Category' : 'Save Category'; ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
