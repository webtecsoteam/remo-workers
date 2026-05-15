<?php
/**
 * =============================================
 * Route 3: REMOWORKERS DASHBOARD (Freelancer)
 * =============================================
 * Freelancer dashboard for finding work and managing profile
 * URL: /remoworkers-dashboard
 */

$pageTitle = 'Freelancer Dashboard | ' . APP_NAME;
$section = 'remoworkers-dashboard';
include BASE_PATH . '/includes/header.php';
?>

<main class="dashboard">
    <div class="container">
        <div class="dashboard-header">
            <h1 class="dashboard-title">Freelancer Dashboard</h1>
            <p class="dashboard-subtitle">Find work, manage proposals, and grow your freelance career</p>
        </div>

        <div class="dashboard-grid">
            <!-- Sidebar -->
            <aside class="sidebar">
                <nav class="sidebar-nav">
                    <a href="<?php echo baseUrl('remoworkers-dashboard'); ?>" class="sidebar-link active">
                        <i class="fas fa-th-large"></i> Overview
                    </a>
                    <a href="<?php echo baseUrl('remoworkers-dashboard/find-work'); ?>" class="sidebar-link">
                        <i class="fas fa-search"></i> Find Work
                    </a>
                    <a href="<?php echo baseUrl('remoworkers-dashboard/proposals'); ?>" class="sidebar-link">
                        <i class="fas fa-paper-plane"></i> My Proposals
                    </a>
                    <a href="<?php echo baseUrl('remoworkers-dashboard/contracts'); ?>" class="sidebar-link">
                        <i class="fas fa-handshake"></i> Contracts
                    </a>
                    <a href="<?php echo baseUrl('remoworkers-dashboard/earnings'); ?>" class="sidebar-link">
                        <i class="fas fa-wallet"></i> Earnings
                    </a>
                    <a href="<?php echo baseUrl('remoworkers-dashboard/profile'); ?>" class="sidebar-link">
                        <i class="fas fa-user"></i> My Profile
                    </a>
                    <a href="<?php echo baseUrl('remoworkers-dashboard/messages'); ?>" class="sidebar-link">
                        <i class="fas fa-comments"></i> Messages
                    </a>
                    <a href="<?php echo baseUrl('remoworkers-dashboard/settings'); ?>" class="sidebar-link">
                        <i class="fas fa-cog"></i> Settings
                    </a>
                </nav>
            </aside>

            <!-- Main Content -->
            <div class="dashboard-content">
                <!-- Metric Cards -->
                <div class="grid-4">
                    <div class="metric-card">
                        <div class="metric-icon purple"><i class="fas fa-paper-plane"></i></div>
                        <div class="metric-info">
                            <div class="metric-value">15</div>
                            <div class="metric-label">Proposals Sent</div>
                        </div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-icon green"><i class="fas fa-handshake"></i></div>
                        <div class="metric-info">
                            <div class="metric-value">3</div>
                            <div class="metric-label">Active Contracts</div>
                        </div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-icon pink"><i class="fas fa-star"></i></div>
                        <div class="metric-info">
                            <div class="metric-value">4.9</div>
                            <div class="metric-label">Rating</div>
                        </div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-icon yellow"><i class="fas fa-dollar-sign"></i></div>
                        <div class="metric-info">
                            <div class="metric-value">$8.2K</div>
                            <div class="metric-label">Total Earned</div>
                        </div>
                    </div>
                </div>

                <!-- Available Jobs -->
                <div class="data-table-wrapper">
                    <div class="data-table-header">
                        <h3 class="data-table-title">Recommended Jobs</h3>
                        <a href="<?php echo baseUrl('remoworkers-dashboard/find-work'); ?>" class="btn btn-primary">
                            <i class="fas fa-search"></i> Browse All Jobs
                        </a>
                    </div>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Job Title</th>
                                <th>Client</th>
                                <th>Budget</th>
                                <th>Skills</th>
                                <th>Posted</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Full Stack Developer - SaaS Platform</td>
                                <td>TechCorp Inc.</td>
                                <td>$5,000</td>
                                <td><span class="badge badge-info">React</span> <span class="badge badge-info">Node.js</span></td>
                                <td>2 hours ago</td>
                            </tr>
                            <tr>
                                <td>WordPress Plugin Development</td>
                                <td>Digital Agency</td>
                                <td>$1,500</td>
                                <td><span class="badge badge-info">PHP</span> <span class="badge badge-info">WordPress</span></td>
                                <td>5 hours ago</td>
                            </tr>
                            <tr>
                                <td>Landing Page Design & Development</td>
                                <td>StartupXYZ</td>
                                <td>$800</td>
                                <td><span class="badge badge-info">HTML</span> <span class="badge badge-info">CSS</span></td>
                                <td>1 day ago</td>
                            </tr>
                            <tr>
                                <td>REST API for E-commerce</td>
                                <td>ShopGlobal</td>
                                <td>$3,200</td>
                                <td><span class="badge badge-info">PHP</span> <span class="badge badge-info">MySQL</span></td>
                                <td>1 day ago</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- My Active Contracts -->
                <div class="data-table-wrapper">
                    <div class="data-table-header">
                        <h3 class="data-table-title">My Active Contracts</h3>
                    </div>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Project</th>
                                <th>Client</th>
                                <th>Rate</th>
                                <th>Hours/Week</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>E-commerce Platform Build</td>
                                <td>Global Retail Ltd.</td>
                                <td>$45/hr</td>
                                <td>30</td>
                                <td><span class="badge badge-success">Active</span></td>
                            </tr>
                            <tr>
                                <td>Mobile App API</td>
                                <td>AppVenture</td>
                                <td>$2,800 Fixed</td>
                                <td>—</td>
                                <td><span class="badge badge-success">Active</span></td>
                            </tr>
                            <tr>
                                <td>Dashboard Redesign</td>
                                <td>DataFlow Inc.</td>
                                <td>$50/hr</td>
                                <td>20</td>
                                <td><span class="badge badge-warning">Pending Review</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include BASE_PATH . '/includes/footer.php'; ?>
