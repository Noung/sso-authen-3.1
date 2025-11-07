<?php
// Page configuration for responsive template
$pageTitle = 'System Configuration';
$currentPage = 'settings';

// Get basePath from GLOBALS
$basePath = $GLOBALS['admin_base_path'] ?? '/sso-authen-3/admin/public';

$additionalStyles = '
<style>
    .card-header {
        font-weight: bold;
    }
    .secret-key-display {
        font-family: monospace;
        background-color: #f8f9fa;
        padding: 10px;
        border-radius: 4px;
        word-break: break-all;
    }
    .btn-copy {
        margin-left: 10px;
    }
    .history-table th {
        font-weight: bold;
    }
    .secret-key-cell {
        font-family: monospace;
        font-size: 0.8rem;
        max-width: 200px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .secret-key-full {
        display: none;
        font-family: monospace;
        background-color: #f8f9fa;
        padding: 10px;
        border-radius: 4px;
        word-break: break-all;
        margin-top: 5px;
    }
</style>
';
$additionalScripts = '
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="' . $basePath . '/js/shared.js?v=' . time() . '"></script>
';

// Get current JWT secret key from config
$currentSecret = '';
$configPath = __DIR__ . '/../../config/config.php';
if (file_exists($configPath)) {
    $configContent = file_get_contents($configPath);
    if (preg_match("/define\('JWT_SECRET_KEY',\s*'([^']*)'\);/", $configContent, $matches)) {
        $currentSecret = $matches[1];
    } elseif (preg_match('/define\("JWT_SECRET_KEY",\s*"([^"]*)"\);/', $configContent, $matches)) {
        $currentSecret = $matches[1];
    }
}

// Get secret key history
require_once __DIR__ . '/../src/Models/JwtSecretHistory.php';
$historyResult = SsoAdmin\Models\JwtSecretHistory::getAll(1, 50);
$historyData = $historyResult['data'] ?? [];

// Include responsive header
include __DIR__ . '/partials/header.php';
?>

<!-- Define basePath for JavaScript -->
<script>
    const basePath = "<?php echo $basePath; ?>";
</script>

<!-- Main Content -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-cog me-2"></i>System Configuration</h1>
</div>

<div class="row">
    <div class="col-12">
        <div class="card mb-4 shadow">
            <div class="card-header">
                <h5 id="time-round">
                    <i class="fas fa-key me-2"></i>JWT Secret Key Management
                </h5>
            </div>
            <div class="card-body">
                <p class="card-text">
                    The JWT Secret Key is used to sign authentication tokens for client applications.
                    It should be a long, random string that is kept secret.
                </p>

                <div class="mb-3">
                    <label class="form-label">Current Secret Key:</label>
                    <div class="input-group mt-2">
                        <input type="password" class="form-control" id="currentSecretKey" value="<?php echo htmlspecialchars($currentSecret); ?>" readonly>
                        <button class="btn btn-outline-secondary" onclick="toggleJwtSecret()" id="toggleJwtBtn" title="Show JWT Secret">
                            <i class="fas fa-eye" id="toggleJwtIcon"></i> Show
                        </button>
                        <button class="btn btn-outline-secondary" onclick="copyToClipboardText('<?php echo htmlspecialchars($currentSecret); ?>')" title="Copy JWT Secret">
                            <i class="fas fa-copy"></i> Copy
                        </button>
                    </div>
                </div>

                <div class="mb-3">
                    <button class="btn btn-primary" onclick="generateNewSecret()">
                        <i class="fas fa-sync-alt me-2"></i>Generate New Secret Key
                    </button>
                    <button class="btn btn-success ms-2" onclick="saveSecretKey()" disabled id="saveButton">
                        <i class="fas fa-save me-2"></i>Save Changes
                    </button>
                </div>

                <div class="alert alert-warning mt-3">
                    <h5><i class="fas fa-exclamation-triangle me-2"></i>Security Warning</h5>
                    <p class="mb-0">
                        Changing the JWT Secret Key will invalidate all existing tokens.
                        All users will need to re-authenticate. Make sure to update all
                        client applications with the new key.
                    </p>
                </div>
            </div>
        </div>

        <div class="card shadow">
            <div class="card-header">
                <h5 id="time-round">
                    <i class="fas fa-history me-2"></i>JWT Secret Key History
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover history-table" id="historyTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Secret Key</th>
                                <th>Created By</th>
                                <th>Created At</th>
                                <th>Notes</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($historyData as $record): ?>
                                <?php $maskedKey = substr($record['secret_key'], 0, 10) . '**********' . substr($record['secret_key'], -10); ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($record['id']); ?></td>
                                    <td class="secret-key-cell">
                                        <div id="masked-<?php echo $record['id']; ?>"><?php echo htmlspecialchars($maskedKey); ?></div>
                                        <div id="full-<?php echo $record['id']; ?>" class="secret-key-full"><?php echo htmlspecialchars($record['secret_key']); ?></div>
                                    </td>
                                    <td><?php echo htmlspecialchars($record['created_by']); ?></td>
                                    <td><?php echo htmlspecialchars($record['created_at']); ?></td>
                                    <td><?php echo htmlspecialchars($record['notes'] ?? 'N/A'); ?></td>
                                    <td>
                                        <?php if ($record['is_active']): ?>
                                            <span class="badge bg-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // JWT Secret show/hide toggle function
    function toggleJwtSecret() {
        const field = document.getElementById("currentSecretKey");
        const btn = document.getElementById("toggleJwtBtn");
        const icon = document.getElementById("toggleJwtIcon");

        if (field.type === "password") {
            // Show the secret
            field.type = "text";
            icon.className = "fas fa-eye-slash";
            btn.innerHTML = '<i class="fas fa-eye-slash" id="toggleJwtIcon"></i> Hide';
            btn.title = "Hide JWT Secret";
        } else {
            // Hide the secret
            field.type = "password";
            icon.className = "fas fa-eye";
            btn.innerHTML = '<i class="fas fa-eye" id="toggleJwtIcon"></i> Show';
            btn.title = "Show JWT Secret";
        }
    }

    // Helper function to copy text to clipboard
    function copyToClipboardText(text) {
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(() => {
                showCopySuccess();
            }).catch(err => {
                fallbackCopyTextToClipboard(text);
            });
        } else {
            fallbackCopyTextToClipboard(text);
        }
    }

    function fallbackCopyTextToClipboard(text) {
        const textArea = document.createElement("textarea");
        textArea.value = text;
        textArea.style.top = "0";
        textArea.style.left = "0";
        textArea.style.position = "fixed";
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();

        try {
            document.execCommand("copy");
            showCopySuccess();
        } catch (err) {
            console.error("Fallback: Oops, unable to copy", err);
            Swal.fire("Copy Failed", "Unable to copy to clipboard", "error");
        }

        document.body.removeChild(textArea);
    }

    function showCopySuccess() {
        showCustomToast("Copied to clipboard successfully!", "success");
    }

    let newSecret = "";

    // Initialize DataTable
    $(document).ready(function() {
        $("#historyTable").DataTable({
            "order": [
                [0, "desc"]
            ],
            "pageLength": 10,
            "responsive": true,
            "columnDefs": [{
                "orderable": false,
                "targets": [1]
            }]
        });
    });

    function generateNewSecret() {
        // Generate a random secret key
        const chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*()_+-=[]{}|;:,.<>?";
        let secret = "";
        for (let i = 0; i < 64; i++) {
            secret += chars.charAt(Math.floor(Math.random() * chars.length));
        }

        newSecret = secret;
        document.getElementById("currentSecretKey").value = secret;
        document.getElementById("saveButton").disabled = false;

        // Update the copy button with the new secret
        const copyButtons = document.querySelectorAll("button[onclick^=\"copyToClipboardText\"][title=\"Copy JWT Secret\"]");
        copyButtons[0].setAttribute("onclick", "copyToClipboardText('" + secret + "')");
    }

    function saveSecretKey() {
        if (!newSecret) return;

        Swal.fire({
            title: "Are you sure?",
            text: "This will invalidate all existing tokens and users will need to re-authenticate!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#d33",
            cancelButtonColor: "#3085d6",
            confirmButtonText: "Yes, update it!",
            cancelButtonText: "Cancel"
        }).then((result) => {
            if (result.isConfirmed) {
                // Show loading indicator
                const saveButton = document.getElementById("saveButton");
                const originalText = saveButton.innerHTML;
                saveButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Saving...';
                saveButton.disabled = true;

                // Send API request to update the secret key
                fetch(basePath + "/api/update-jwt-secret", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json"
                        },
                        body: JSON.stringify({
                            secret_key: newSecret,
                            notes: "Updated via admin panel"
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Show success message
                            Swal.fire({
                                title: "Updated!",
                                text: "JWT Secret Key updated successfully! All existing tokens have been invalidated.",
                                icon: "success",
                                confirmButtonText: "OK"
                            }).then(() => {
                                // Reset button state
                                document.getElementById("saveButton").disabled = true;
                                // Reload page to show updated key
                                location.reload();
                            });

                            // Log the action
                            console.log("JWT secret updated successfully");
                        } else {
                            // Show error message
                            Swal.fire({
                                title: "Error!",
                                text: "Error updating JWT Secret Key: " + data.message,
                                icon: "error",
                                confirmButtonText: "OK"
                            });

                            // Restore button state
                            saveButton.innerHTML = originalText;
                            saveButton.disabled = false;
                        }
                    })
                    .catch(error => {
                        console.error("Error updating JWT secret:", error);
                        Swal.fire({
                            title: "Error!",
                            text: "Error updating JWT Secret Key. Please try again.",
                            icon: "error",
                            confirmButtonText: "OK"
                        });

                        // Restore button state
                        saveButton.innerHTML = originalText;
                        saveButton.disabled = false;
                    });
            }
        });
    }
</script>

<?php
// Include responsive footer
include __DIR__ . '/partials/footer.php';
?>