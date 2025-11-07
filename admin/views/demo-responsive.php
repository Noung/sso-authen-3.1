<?php

/**
 * Demo Page - Responsive Layout
 * Shows how to use the new responsive template system
 */

// Set page variables
$pageTitle = 'Responsive Demo - SSO Admin';
$currentPage = 'dashboard';

// Optional custom styles for this page
$additionalStyles = "
    .demo-card {
        border-left: 4px solid #0d6efd;
        transition: transform 0.2s ease;
    }
    .demo-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    .feature-icon {
        font-size: 2rem;
        color: #0d6efd;
    }
";

// Include responsive header
include __DIR__ . '/partials/header.php';
?>

<!-- Page Header -->
<div class="admin-page-header">
    <div class="admin-page-title">
        <h1 class="h2">
            <i class="fas fa-mobile-alt me-2"></i>Responsive Layout Demo
        </h1>
        <div class="btn-toolbar">
            <button class="btn btn-primary" onclick="testMobileMenu()">
                <i class="fas fa-bars me-1"></i>Test Menu
            </button>
        </div>
    </div>
</div>

<!-- Alert -->
<div class="alert alert-info alert-dismissible fade show" role="alert">
    <i class="fas fa-info-circle me-2"></i>
    <strong>Responsive Design Active!</strong> Try resizing your browser or use DevTools to test different screen sizes.
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>

<!-- Features Grid -->
<div class="row mb-4">
    <div class="col-md-6 col-lg-3 mb-3">
        <div class="card demo-card h-100">
            <div class="card-body text-center">
                <i class="fas fa-bars feature-icon mb-3"></i>
                <h5 class="card-title">Hamburg Menu</h5>
                <p class="card-text text-muted">Mobile-friendly sidebar toggle with smooth animations</p>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3 mb-3">
        <div class="card demo-card h-100">
            <div class="card-body text-center">
                <i class="fas fa-mobile-alt feature-icon mb-3"></i>
                <h5 class="card-title">Mobile First</h5>
                <p class="card-text text-muted">Optimized for phones, tablets, and desktops</p>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3 mb-3">
        <div class="card demo-card h-100">
            <div class="card-body text-center">
                <i class="fas fa-bolt feature-icon mb-3"></i>
                <h5 class="card-title">Fast & Smooth</h5>
                <p class="card-text text-muted">Hardware-accelerated CSS transitions</p>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3 mb-3">
        <div class="card demo-card h-100">
            <div class="card-body text-center">
                <i class="fas fa-universal-access feature-icon mb-3"></i>
                <h5 class="card-title">Accessible</h5>
                <p class="card-text text-muted">ARIA labels and keyboard navigation support</p>
            </div>
        </div>
    </div>
</div>

<!-- Responsive Test Cards -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-check-circle me-2"></i>Responsive Breakpoints</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Device</th>
                                <th>Width</th>
                                <th>Sidebar Behavior</th>
                                <th>Navigation</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><i class="fas fa-desktop text-primary"></i> Desktop</td>
                                <td>≥992px</td>
                                <td><span class="badge bg-success">Fixed & Visible</span></td>
                                <td>Always visible</td>
                            </tr>
                            <tr>
                                <td><i class="fas fa-tablet-alt text-info"></i> Tablet</td>
                                <td>768px - 991px</td>
                                <td><span class="badge bg-warning">Collapsible</span></td>
                                <td>Hamburg menu</td>
                            </tr>
                            <tr>
                                <td><i class="fas fa-mobile-alt text-danger"></i> Phone</td>
                                <td>≤767px</td>
                                <td><span class="badge bg-danger">Full Overlay</span></td>
                                <td>Hamburg menu</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Features List -->
<div class="row mt-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h6 class="mb-0"><i class="fas fa-check me-2"></i>Included Features</h6>
            </div>
            <div class="card-body">
                <ul class="mb-0">
                    <li>✅ Hamburg menu for mobile navigation</li>
                    <li>✅ Smooth slide-in/out animations</li>
                    <li>✅ Overlay background on mobile</li>
                    <li>✅ Auto-close on link click (mobile)</li>
                    <li>✅ ESC key support</li>
                    <li>✅ Touch-friendly tap targets</li>
                    <li>✅ Responsive typography</li>
                    <li>✅ Optimized spacing</li>
                </ul>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-info text-white">
                <h6 class="mb-0"><i class="fas fa-keyboard me-2"></i>Keyboard Shortcuts</h6>
            </div>
            <div class="card-body">
                <table class="table table-sm mb-0">
                    <tr>
                        <td><kbd>ESC</kbd></td>
                        <td>Close sidebar (mobile)</td>
                    </tr>
                    <tr>
                        <td><kbd>Tab</kbd></td>
                        <td>Navigate through links</td>
                    </tr>
                    <tr>
                        <td><kbd>Enter</kbd></td>
                        <td>Activate focused link</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Testing Instructions -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card border-primary">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-vial me-2"></i>How to Test Responsive Design</h5>
            </div>
            <div class="card-body">
                <h6>Using Browser DevTools:</h6>
                <ol>
                    <li>Press <kbd>F12</kbd> to open DevTools</li>
                    <li>Press <kbd>Ctrl</kbd> + <kbd>Shift</kbd> + <kbd>M</kbd> to toggle device toolbar</li>
                    <li>Select different devices:
                        <ul>
                            <li>iPhone SE (375px) - Mobile view</li>
                            <li>iPad (768px) - Tablet view</li>
                            <li>Desktop (1920px) - Desktop view</li>
                        </ul>
                    </li>
                    <li>Test the hamburg menu functionality</li>
                    <li>Try clicking the overlay to close the sidebar</li>
                </ol>

                <div class="alert alert-warning mt-3 mb-0">
                    <i class="fas fa-lightbulb me-2"></i>
                    <strong>Pro Tip:</strong> Use Chrome's Device Mode to simulate touch events and test the mobile experience accurately.
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Page-specific scripts
$pageScripts = "
<script>
    function testMobileMenu() {
        toggleSidebar();
        
        setTimeout(() => {
            Swal.fire({
                title: 'Hamburg Menu',
                html: '<p>The sidebar should now be <strong>open</strong> (if on mobile) or <strong>always visible</strong> (if on desktop).</p><p>Try these actions:</p><ul class=\"text-start\"><li>Click the overlay to close</li><li>Press ESC key to close</li><li>Click a menu link to navigate</li></ul>',
                icon: 'info',
                confirmButtonText: 'Got it!'
            });
        }, 300);
    }
    
    // Show current screen size
    function updateScreenSize() {
        const width = window.innerWidth;
        let device = '';
        
        if (width >= 992) device = 'Desktop';
        else if (width >= 768) device = 'Tablet';
        else device = 'Mobile';
        
        console.log('Current view:', device, '(' + width + 'px)');
    }
    
    window.addEventListener('resize', updateScreenSize);
    updateScreenSize();
</script>
";

// Include footer
include __DIR__ . '/partials/footer.php';
?>