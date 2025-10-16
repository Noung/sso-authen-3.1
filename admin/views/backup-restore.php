<?php
$basePath = $GLOBALS['admin_base_path'] ?? '/sso-authen-3/admin/public';
$adminName = $_SESSION['admin_name'] ?? 'Administrator';
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>SSO Admin Panel - Backup & Restore</title>
    <link
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
      rel="stylesheet"
    />
    <link
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
      rel="stylesheet"
    />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
      .backup-card {
        transition: transform 0.2s, box-shadow 0.2s;
      }
      .backup-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
      }
      .file-size {
        font-family: "Courier New", monospace;
        font-size: 0.875rem;
      }
      .backup-actions .btn {
        margin: 0.125rem;
      }
      .progress-container {
        display: none;
      }
      .backup-type-badge {
        font-size: 0.75rem;
      }
      .backup-metadata {
        background-color: #f8f9fa;
        border-radius: 0.375rem;
        padding: 0.75rem;
        font-size: 0.875rem;
      }
      .admin-content {
        margin-bottom: 20px;
      }
    </style>
  </head>
  <body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
      <div class="container-fluid">
        <a class="navbar-brand" href="<?php echo $basePath; ?>">
          <i class="fas fa-shield-alt me-2"></i>SSO Admin Panel
        </a>
        <div class="navbar-nav ms-auto">
          <span class="navbar-text me-3">
            <i class="fas fa-user me-1"></i><?php echo $adminName; ?>
          </span>
          <a class="nav-link" href="<?php echo $basePath; ?>/auth/logout">
            <i class="fas fa-sign-out-alt me-1"></i>Sign out
          </a>
        </div>
      </div>
    </nav>

    <div class="container-fluid">
      <div class="row">
        <!-- Sidebar -->
        <nav class="col-md-3 col-lg-2 d-md-block bg-light sidebar">
          <div class="position-sticky pt-3">
            <ul class="nav flex-column">
              <li class="nav-item">
                <a class="nav-link" href="<?php echo $basePath; ?>">
                  <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                </a>
              </li>
              <li class="nav-item">
                <a class="nav-link" href="<?php echo $basePath; ?>/clients">
                  <i class="fas fa-users me-2"></i>Client Applications
                </a>
              </li>
              <li class="nav-item">
                <a class="nav-link" href="<?php echo $basePath; ?>/statistics">
                  <i class="fas fa-chart-bar me-2"></i>Usage Statistics
                </a>
              </li>
              <li class="nav-item">
                <a class="nav-link" href="<?php echo $basePath; ?>/admin-users">
                  <i class="fas fa-user-shield me-2"></i>Admin Users
                </a>
              </li>
              <li class="nav-item">
                <a class="nav-link active" href="<?php echo $basePath; ?>/backup-restore">
                  <i class="fas fa-database me-2"></i>Backup & Restore
                </a>
              </li>
              <li class="nav-item">
                <a class="nav-link" href="<?php echo $basePath; ?>/settings">
                  <i class="fas fa-cog me-2"></i>System Configuration
                </a>
              </li>
              <li class="nav-item">
                <a class="nav-link" href="<?php echo $basePath; ?>/api-docs.html" target="_blank">
                  <i class="fas fa-book me-2"></i>Documentation
                </a>
              </li>
            </ul>
          </div>
        </nav>

        <!-- Main Content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 admin-content">
          <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
            <h1 class="h2">
              <i class="fas fa-database me-2"></i>Backup & Restore
            </h1>
            <div>
              <button class="btn btn-success" onclick="showCreateBackupModal()">
                <i class="fas fa-plus me-1"></i>Create New Backup
              </button>
              <button class="btn btn-outline-info" onclick="showScheduleInfo()">
                <i class="fas fa-clock me-1"></i>Automation Setup
              </button>
              <!-- <button class="btn btn-outline-secondary" onclick="loadBackups()">
                <i class="fas fa-sync-alt me-1"></i>Refresh
              </button> -->
            </div>
          </div>

          <!-- Quick Info -->
          <div class="row mb-4" style="margin-bottom: 0.5rem !important;">
            <div class="col-12">
              <div class="alert alert-info shadow">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Backup System:</strong> Create, download, and restore
                complete system backups including client configurations, admin
                users, and system settings. Backups are stored securely and can
                be used for disaster recovery or system migration.
              </div>
            </div>
          </div>

          <!-- Progress Container -->
          <div class="progress-container mb-4" id="progressContainer">
            <div class="card">
              <div class="card-body">
                <div class="d-flex align-items-center">
                  <div
                    class="spinner-border spinner-border-sm text-primary me-3"
                    role="status"
                  >
                    <span class="visually-hidden">Loading...</span>
                  </div>
                  <div>
                    <h6 class="mb-1" id="progressTitle">Processing...</h6>
                    <small class="text-muted" id="progressMessage"
                      >Please wait while the operation completes.</small
                    >
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Backup List -->
          <div class="row" id="backupList">
            <div class="col-12 text-center py-5">
              <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading backups...</span>
              </div>
              <p class="mt-3 text-muted">Loading backup list...</p>
            </div>
          </div>
        </main>
      </div>
    </div>

    <!-- Create Backup Modal -->
    <div class="modal fade" id="createBackupModal" tabindex="-1">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">
              <i class="fas fa-plus-circle text-success me-2"></i>Create New
              Backup
            </h5>
            <button
              type="button"
              class="btn-close"
              data-bs-dismiss="modal"
            ></button>
          </div>
          <div class="modal-body">
            <form id="createBackupForm">
              <div class="row">
                <div class="col-md-6">
                  <div class="mb-3">
                    <label for="backupName" class="form-label"
                      >Backup Name</label
                    >
                    <input
                      type="text"
                      class="form-control"
                      id="backupName"
                      placeholder="e.g., weekly_backup_2024"
                    />
                    <div class="form-text">
                      Leave empty for auto-generated name (timestamp-based)
                    </div>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="mb-3">
                    <label for="backupType" class="form-label"
                      >Backup Type</label
                    >
                    <select class="form-select" id="backupType">
                      <option value="full">Full Backup</option>
                      <option value="config">Configuration Only</option>
                      <option value="data">Data Only</option>
                    </select>
                  </div>
                </div>
              </div>

              <div class="mb-3">
                <label for="backupDescription" class="form-label"
                  >Description</label
                >
                <textarea
                  class="form-control"
                  id="backupDescription"
                  rows="2"
                  placeholder="Brief description of this backup"
                ></textarea>
              </div>

              <div class="row">
                <div class="col-md-6">
                  <h6>Include in Backup:</h6>
                  <div class="form-check">
                    <input
                      class="form-check-input"
                      type="checkbox"
                      id="includeClients"
                      checked
                    />
                    <label class="form-check-label" for="includeClients">
                      <i class="fas fa-users me-1"></i>Client Configurations
                    </label>
                  </div>
                  <div class="form-check">
                    <input
                      class="form-check-input"
                      type="checkbox"
                      id="includeUsers"
                      checked
                    />
                    <label class="form-check-label" for="includeUsers">
                      <i class="fas fa-user-shield me-1"></i>Admin Users
                    </label>
                  </div>
                  <div class="form-check">
                    <input
                      class="form-check-input"
                      type="checkbox"
                      id="includeAuditLogs"
                    />
                    <label class="form-check-label" for="includeAuditLogs">
                      <i class="fas fa-history me-1"></i>Audit Logs
                    </label>
                  </div>
                </div>
                <div class="col-md-6">
                  <div
                    class="mb-3"
                    id="auditDaysContainer"
                    style="display: none"
                  >
                    <label for="auditDays" class="form-label"
                      >Audit Log Days</label
                    >
                    <select class="form-select" id="auditDays">
                      <option value="7">Last 7 days</option>
                      <option value="30" selected>Last 30 days</option>
                      <option value="90">Last 90 days</option>
                      <option value="365">Last year</option>
                    </select>
                  </div>
                </div>
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button
              type="button"
              class="btn btn-secondary"
              data-bs-dismiss="modal"
            >
              Cancel
            </button>
            <button
              type="button"
              class="btn btn-success"
              onclick="createBackup()"
            >
              <i class="fas fa-download me-1"></i>Create Backup
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Restore Backup Modal -->
    <div class="modal fade" id="restoreBackupModal" tabindex="-1">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">
              <i class="fas fa-upload text-warning me-2"></i>Restore Backup
            </h5>
            <button
              type="button"
              class="btn-close"
              data-bs-dismiss="modal"
            ></button>
          </div>
          <div class="modal-body">
            <div class="alert alert-warning">
              <i class="fas fa-exclamation-triangle me-2"></i>
              <strong>Warning:</strong> Restoring a backup will modify your
              current system data. Please ensure you have a recent backup before
              proceeding.
            </div>

            <form id="restoreBackupForm">
              <input type="hidden" id="restoreFilename" />

              <div class="backup-metadata mb-4" id="restoreBackupInfo">
                <!-- Backup info will be populated here -->
              </div>

              <div class="row">
                <div class="col-md-6">
                  <h6>Restore Options:</h6>
                  <div class="mb-3">
                    <label for="clientMode" class="form-label"
                      >Client Handling</label
                    >
                    <select class="form-select" id="clientMode">
                      <option value="merge">Merge (keep existing)</option>
                      <option value="replace">Replace (overwrite all)</option>
                      <option value="skip">Skip clients</option>
                    </select>
                  </div>
                  <div class="mb-3">
                    <label for="userMode" class="form-label"
                      >User Handling</label
                    >
                    <select class="form-select" id="userMode">
                      <option value="merge">Merge (keep existing)</option>
                      <option value="replace">Replace (overwrite all)</option>
                      <option value="skip">Skip users</option>
                    </select>
                  </div>
                </div>
                <div class="col-md-6">
                  <h6>Skip Components:</h6>
                  <div class="form-check">
                    <input
                      class="form-check-input"
                      type="checkbox"
                      id="skipClients"
                    />
                    <label class="form-check-label" for="skipClients">
                      Skip Client Configurations
                    </label>
                  </div>
                  <div class="form-check">
                    <input
                      class="form-check-input"
                      type="checkbox"
                      id="skipUsers"
                    />
                    <label class="form-check-label" for="skipUsers">
                      Skip Admin Users
                    </label>
                  </div>
                  <div class="form-check">
                    <input
                      class="form-check-input"
                      type="checkbox"
                      id="skipConfig"
                    />
                    <label class="form-check-label" for="skipConfig">
                      Skip System Configuration
                    </label>
                  </div>
                  <div class="form-check">
                    <input
                      class="form-check-input"
                      type="checkbox"
                      id="skipAudit"
                      checked
                    />
                    <label class="form-check-label" for="skipAudit">
                      Skip Audit Logs
                    </label>
                  </div>
                </div>
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button
              type="button"
              class="btn btn-secondary"
              data-bs-dismiss="modal"
            >
              Cancel
            </button>
            <button
              type="button"
              class="btn btn-warning"
              onclick="restoreBackup()"
            >
              <i class="fas fa-upload me-1"></i>Restore Backup
            </button>
          </div>
        </div>
      </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
      document.addEventListener("DOMContentLoaded", function () {
        loadBackups();

        // Show/hide audit days selector
        document
          .getElementById("includeAuditLogs")
          .addEventListener("change", function () {
            const container = document.getElementById("auditDaysContainer");
            container.style.display = this.checked ? "block" : "none";
          });
      });

      function loadBackups() {
        fetch("<?php echo $basePath; ?>/api/backup/list")
          .then((response) => response.json())
          .then((data) => {
            if (data.success) {
              renderBackupList(data.data);
            } else {
              Swal.fire("Error", data.message, "error");
            }
          })
          .catch((error) => {
            console.error("Error loading backups:", error);
            Swal.fire("Error", "Failed to load backup list", "error");
          });
      }

      function renderBackupList(backups) {
        const container = document.getElementById("backupList");

        if (backups.length === 0) {
          container.innerHTML = `
                    <div class="col-12 text-center py-5">
                        <i class="fas fa-database fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No backups found</h5>
                        <p class="text-muted">Create your first backup to get started</p>
                        <button class="btn btn-success" onclick="showCreateBackupModal()">
                            <i class="fas fa-plus me-1"></i>Create Backup
                        </button>
                    </div>
                `;
          return;
        }

        let html = "";
        backups.forEach((backup) => {
          html += `
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card backup-card shadow h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="mb-0">
                                    <i class="fas fa-file-archive text-primary me-1"></i>
                                    ${backup.filename.replace(".zip", "")}
                                </h6>
                                <span class="badge bg-primary backup-type-badge">ZIP</span>
                            </div>
                            <div class="card-body">
                                <div class="mb-2">
                                    <small class="text-muted">Created:</small><br>
                                    <span>${new Date(
                                      backup.created_at
                                    ).toLocaleString()}</span>
                                </div>
                                <div class="mb-3">
                                    <small class="text-muted">Size:</small><br>
                                    <span class="file-size">${
                                      backup.size_human
                                    }</span>
                                </div>
                                <div class="backup-actions">
                                    <button class="btn btn-sm btn-outline-primary" onclick="downloadBackup('${
                                      backup.filename
                                    }')">
                                        <i class="fas fa-download"></i> Download
                                    </button>
                                    <button class="btn btn-sm btn-outline-warning" onclick="showRestoreModal('${
                                      backup.filename
                                    }')">
                                        <i class="fas fa-upload"></i> Restore
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteBackup('${
                                      backup.filename
                                    }')">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
        });

        container.innerHTML = html;
      }

      function showCreateBackupModal() {
        const modal = new bootstrap.Modal(
          document.getElementById("createBackupModal")
        );
        modal.show();
      }

      function createBackup() {
        const form = document.getElementById("createBackupForm");
        const backupName = document.getElementById("backupName").value.trim();
        const formData = {
          name: backupName || null, // Send null for auto-generation
          description: document.getElementById("backupDescription").value,
          type: document.getElementById("backupType").value,
          exclude_clients: !document.getElementById("includeClients").checked,
          exclude_users: !document.getElementById("includeUsers").checked,
          include_audit_logs:
            document.getElementById("includeAuditLogs").checked,
          audit_days: parseInt(document.getElementById("auditDays").value),
        };

        showProgress("Creating Backup", "Preparing backup data...");

        fetch("<?php echo $basePath; ?>/api/backup/create", {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
          },
          body: JSON.stringify(formData),
        })
          .then((response) => response.json())
          .then((data) => {
            if (data.success) {
              hideProgress();
              bootstrap.Modal.getInstance(
                document.getElementById("createBackupModal")
              ).hide();
              Swal.fire({
                title: "Success!",
                text: data.message,
                icon: "success",
                confirmButtonText: "OK",
              }).then(() => {
                loadBackups();
              });
            } else {
              hideProgress();
              Swal.fire("Error", data.message, "error");
            }
          })
          .catch((error) => {
            hideProgress();
            console.error("Error creating backup:", error);
            Swal.fire("Error", "Failed to create backup", "error");
          });
      }

      function downloadBackup(filename) {
        showProgress("Preparing Download", "Generating download link...");
        
        // Create a temporary link for download
        const downloadUrl = `<?php echo $basePath; ?>/api/backup/download?file=${encodeURIComponent(filename)}`;
        hideProgress();
        
        // Create and trigger download
        const link = document.createElement("a");
        link.href = downloadUrl;
        link.download = filename;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
      }

      function showRestoreModal(filename) {
        document.getElementById("restoreFilename").value = filename;
        
        // Load backup info
        fetch(`<?php echo $basePath; ?>/api/backup/list`)
          .then((response) => response.json())
          .then((data) => {
            if (data.success) {
              const backup = data.data.find(b => b.filename === filename);
              if (backup) {
                const infoDiv = document.getElementById("restoreBackupInfo");
                infoDiv.innerHTML = `
                  <div class="row">
                    <div class="col-md-6">
                      <p><strong>Filename:</strong> ${backup.filename}</p>
                      <p><strong>Created:</strong> ${new Date(backup.created_at).toLocaleString()}</p>
                    </div>
                    <div class="col-md-6">
                      <p><strong>Size:</strong> ${backup.size_human}</p>
                      <p><strong>Type:</strong> ${backup.type || 'Full Backup'}</p>
                    </div>
                  </div>
                `;
              }
            }
          });
        
        const modal = new bootstrap.Modal(
          document.getElementById("restoreBackupModal")
        );
        modal.show();
      }

      function restoreBackup() {
        const filename = document.getElementById("restoreFilename").value;
        const formData = {
          filename: filename,
          client_mode: document.getElementById("clientMode").value,
          user_mode: document.getElementById("userMode").value,
          skip_clients: document.getElementById("skipClients").checked,
          skip_users: document.getElementById("skipUsers").checked,
          skip_config: document.getElementById("skipConfig").checked,
          skip_audit: document.getElementById("skipAudit").checked,
        };

        showProgress("Restoring Backup", "Processing backup restoration...");

        fetch("<?php echo $basePath; ?>/api/backup/restore", {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
          },
          body: JSON.stringify(formData),
        })
          .then((response) => response.json())
          .then((data) => {
            if (data.success) {
              hideProgress();
              bootstrap.Modal.getInstance(
                document.getElementById("restoreBackupModal")
              ).hide();
              Swal.fire({
                title: "Success!",
                text: data.message,
                icon: "success",
                confirmButtonText: "OK",
              }).then(() => {
                loadBackups();
              });
            } else {
              hideProgress();
              Swal.fire("Error", data.message, "error");
            }
          })
          .catch((error) => {
            hideProgress();
            console.error("Error restoring backup:", error);
            Swal.fire("Error", "Failed to restore backup", "error");
          });
      }

      function deleteBackup(filename) {
        Swal.fire({
          title: '<i class="fas fa-exclamation-triangle text-danger me-2"></i>Delete Backup?',
        //   text: "This action cannot be undone. The backup file will be permanently deleted.",
        html: `
            <div class="text-center">
                <p>Are you sure you want to delete this backup?</p>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Warning:</strong> This action cannot be undone. The backup file will be permanently deleted.
                </div>
            </div>
        `,
          icon: "warning",
          showCancelButton: true,
          confirmButtonColor: "#d33",
          cancelButtonColor: "#6c757d",
          confirmButtonText: '<i class="fas fa-trash"></i> Yes, delete it!',
          cancelButtonText: '<i class="fas fa-times me-1"></i>Cancel',
        }).then((result) => {
          if (result.isConfirmed) {
            showProgress("Deleting Backup", "Removing backup file...");
            
            fetch(`<?php echo $basePath; ?>/api/backup/delete?file=${encodeURIComponent(filename)}`, {
              method: "DELETE",
            })
              .then((response) => response.json())
              .then((data) => {
                if (data.success) {
                  hideProgress();
                  Swal.fire({
                    title: '<i class="fas fa-check-circle text-success me-2"></i>Deleted!',
                    text: data.message,
                    icon: "success",
                    confirmButtonText: "OK",
                    confirmButtonColor: '#198754'
                  }).then(() => {
                    loadBackups();
                  });
                } else {
                  hideProgress();
                  Swal.fire("Error", data.message, "error");
                }
              })
              .catch((error) => {
                hideProgress();
                console.error("Error deleting backup:", error);
                Swal.fire("Error", "Failed to delete backup", "error");
              });
          }
        });
      }

      function showScheduleInfo() {
        Swal.fire({
          title: "Backup Automation",
          html: `
            <div class="text-start">
              <p>Backup automation can be configured using system cron jobs:</p>
              <div class="bg-light p-3 rounded">
                <code>
                  # Daily backup at 2:00 AM<br>
                  0 2 * * * /usr/bin/php <?php echo realpath(__DIR__ . '/../../automated_backup.php'); ?> full<br><br>
                  
                  # Weekly backup on Sundays at 3:00 AM<br>
                  0 3 * * 0 /usr/bin/php <?php echo realpath(__DIR__ . '/../../automated_backup.php'); ?> config<br><br>
                  
                  # Monthly backup on the 1st at 4:00 AM<br>
                  0 4 1 * * /usr/bin/php <?php echo realpath(__DIR__ . '/../../automated_backup.php'); ?> data
                </code>
              </div>
              <p class="mt-3">See <code>automated_backup.php</code> for more details.</p>
            </div>
          `,
          icon: "info",
          confirmButtonText: "OK",
        });
      }

      function showProgress(title, message) {
        document.getElementById("progressTitle").textContent = title;
        document.getElementById("progressMessage").textContent = message;
        document.getElementById("progressContainer").style.display = "block";
      }

      function hideProgress() {
        document.getElementById("progressContainer").style.display = "none";
      }
    </script>
  </body>
</html>