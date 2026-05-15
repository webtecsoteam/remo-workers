<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/classes/Auth.php';

$user = Auth::user();
if (!$user) {
    redirect(baseUrl('?error=login_required'));
}

$db = getDB();

// Get user documents
$stmt = $db->prepare("SELECT * FROM user_documents WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user['id']]);
$docs = $stmt->fetchAll();

// Get user verification status
$uStmt = $db->prepare("SELECT is_verified, status FROM users WHERE id = ?");
$uStmt->execute([$user['id']]);
$userData = $uStmt->fetch();

include __DIR__ . '/includes/header.php';
?>

<style>
    .v-container { max-width: 800px; margin: 40px auto; padding: 0 20px; }
    .v-card { background: white; border-radius: 16px; border: 1px solid var(--border); padding: 32px; box-shadow: var(--sh2); }
    .v-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 30px; }
    .v-title h1 { font-size: 28px; font-weight: 700; color: var(--dark); margin-bottom: 4px; }
    .v-title p { color: var(--muted); font-size: 14px; }
    
    .status-banner { padding: 16px 20px; border-radius: 12px; display: flex; align-items: center; gap: 12px; margin-bottom: 30px; }
    .status-banner.verified { background: #e6f5e6; color: #0e8f00; border: 1px solid #c8e6c8; }
    .status-banner.pending { background: #fff8e6; color: #b27b16; border: 1px solid #ffeeba; }
    .status-banner.unverified { background: #fdf2f2; color: #dc2626; border: 1px solid #fecaca; }
    
    .upload-box { border: 2px dashed var(--border); border-radius: 12px; padding: 40px; text-align: center; transition: all 0.2s; cursor: pointer; background: var(--off); }
    .upload-box:hover { border-color: var(--g); background: var(--gl); }
    .upload-ico { font-size: 32px; margin-bottom: 12px; display: block; }
    
    .doc-list { margin-top: 40px; }
    .doc-item { display: flex; align-items: center; justify-content: space-between; padding: 16px; border: 1px solid var(--border); border-radius: 12px; margin-bottom: 12px; }
    .doc-info { display: flex; align-items: center; gap: 12px; }
    .doc-icon { width: 40px; height: 40px; background: var(--gl); color: var(--g); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 18px; }
    
    .badge { padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; text-transform: uppercase; }
    .badge-pending { background: #fff8e6; color: #b27b16; }
    .badge-approved { background: #e6f5e6; color: #0e8f00; }
    .badge-rejected { background: #fdf2f2; color: #dc2626; }
    
    .btn-upload { background: var(--g); color: white; border: none; padding: 12px 24px; border-radius: 8px; font-weight: 600; cursor: pointer; width: 100%; margin-top: 20px; transition: background 0.2s; }
    .btn-upload:hover { background: var(--gd); }
</style>

<div class="v-container">
    <div class="v-card">
        <div class="v-header">
            <div class="v-title">
                <h1>Verification Center</h1>
                <p>Upload documents to verify your identity and unlock full features.</p>
            </div>
            <?php if ($userData['is_verified']): ?>
                <span class="badge badge-approved">Verified Account</span>
            <?php endif; ?>
        </div>

        <?php if ($userData['is_verified']): ?>
            <div class="status-banner verified">
                <span style="font-size: 20px;">✓</span>
                <div>
                    <strong>Identity Verified</strong>
                    <p style="font-size: 13px; opacity: 0.8;">Your account is fully verified. You can now post jobs and withdraw funds without restrictions.</p>
                </div>
            </div>
        <?php elseif (!empty($docs) && $docs[0]['status'] === 'pending'): ?>
            <div class="status-banner pending">
                <span style="font-size: 20px;">🕒</span>
                <div>
                    <strong>Verification Pending</strong>
                    <p style="font-size: 13px; opacity: 0.8;">Your documents are being reviewed by our team. This usually takes 24-48 hours.</p>
                </div>
            </div>
        <?php else: ?>
            <div class="status-banner unverified">
                <span style="font-size: 20px;">!</span>
                <div>
                    <strong>Not Verified</strong>
                    <p style="font-size: 13px; opacity: 0.8;">Please upload a valid ID (Passport, National ID, or Driver's License) to verify your account.</p>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!$userData['is_verified']): ?>
            <form action="<?php echo baseUrl('upload-doc'); ?>" method="POST" enctype="multipart/form-data" id="uploadForm">
                <div class="form-group" style="margin-bottom: 20px;">
                    <label>Document Type</label>
                    <select name="doc_type" required class="form-control" style="width: 100%; padding: 12px; border: 1px solid var(--border); border-radius: 8px;">
                        <option value="National ID">National ID Card</option>
                        <option value="Passport">International Passport</option>
                        <option value="Drivers License">Driver's License</option>
                    </select>
                </div>

                <div class="upload-box" onclick="document.getElementById('fileInput').click()">
                    <span class="upload-ico">📄</span>
                    <p id="fileName">Click to upload or drag and drop</p>
                    <small style="color: var(--muted);">Supported formats: JPG, PNG, PDF (Max 5MB)</small>
                    <input type="file" id="fileInput" name="document" accept=".jpg,.jpeg,.png,.pdf" required style="display: none;" onchange="updateFileName(this)">
                </div>

                <button type="submit" class="btn-upload">Submit for Verification</button>
            </form>
        <?php endif; ?>

        <?php if (!empty($docs)): ?>
            <div class="doc-list">
                <h3 style="font-size: 18px; margin-bottom: 16px;">Upload History</h3>
                <?php foreach ($docs as $doc): ?>
                    <div class="doc-item">
                        <div class="doc-info">
                            <div class="doc-icon">📄</div>
                            <div>
                                <div style="font-weight: 600;"><?php echo htmlspecialchars($doc['doc_type']); ?></div>
                                <div style="font-size: 12px; color: var(--muted);"><?php echo date('M d, Y', strtotime($doc['created_at'])); ?></div>
                            </div>
                        </div>
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <span class="badge badge-<?php echo $doc['status']; ?>"><?php echo $doc['status']; ?></span>
                            <?php if ($doc['status'] === 'rejected'): ?>
                                <button onclick="alert('Rejection Reason: <?php echo htmlspecialchars($doc['rejection_reason']); ?>')" class="btn btn-outline btn-sm">Reason</button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function updateFileName(input) {
    const fileName = input.files[0] ? input.files[0].name : "Click to upload or drag and drop";
    document.getElementById('fileName').textContent = fileName;
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
