<?php
/**
 * =============================================
 * Route 2: UPWORK CLIENT DASHBOARD
 * =============================================
 * Client-side dashboard for hiring and managing freelancers
 * URL: /upwork-client
 */

$pageTitle = 'Client Dashboard | ' . APP_NAME;
$section = 'upwork-client';
include BASE_PATH . '/includes/header.php';
?>

<main class="dashboard">
    <div class="container">
        <div class="dashboard-header">
            <h1 class="dashboard-title">Client Dashboard</h1>
            <p class="dashboard-subtitle">Manage your projects, hire talent, and track progress</p>
        </div>

        <div class="dashboard-grid">
            <!-- Sidebar -->
            <aside class="sidebar">
                <nav class="sidebar-nav">
                    <a href="<?php echo baseUrl('upwork-client'); ?>" class="sidebar-link active">
                        <i class="fas fa-th-large"></i> Overview
                    </a>
                    <a href="<?php echo baseUrl('upwork-client/jobs'); ?>" class="sidebar-link">
                        <i class="fas fa-briefcase"></i> My Jobs
                    </a>
                    <a href="<?php echo baseUrl('upwork-client/talent'); ?>" class="sidebar-link">
                        <i class="fas fa-users"></i> Browse Talent
                    </a>
                    <a href="<?php echo baseUrl('upwork-client/proposals'); ?>" class="sidebar-link">
                        <i class="fas fa-file-alt"></i> Proposals
                    </a>
                    <a href="<?php echo baseUrl('upwork-client/contracts'); ?>" class="sidebar-link">
                        <i class="fas fa-handshake"></i> Contracts
                    </a>
                    <a href="<?php echo baseUrl('upwork-client/payments'); ?>" class="sidebar-link">
                        <i class="fas fa-credit-card"></i> Payments
                    </a>
                    <a href="<?php echo baseUrl('upwork-client/messages'); ?>" class="sidebar-link">
                        <i class="fas fa-comments"></i> Messages
                    </a>
                    <a href="<?php echo baseUrl('upwork-client/settings'); ?>" class="sidebar-link">
                        <i class="fas fa-cog"></i> Settings
                    </a>
                </nav>
            </aside>

            <!-- Main Content -->
            <div class="dashboard-content">
                <!-- Metric Cards -->
                <div class="grid-4">
                    <div class="metric-card">
                        <div class="metric-icon purple"><i class="fas fa-briefcase"></i></div>
                        <div class="metric-info">
                            <div class="metric-value">8</div>
                            <div class="metric-label">Active Jobs</div>
                        </div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-icon green"><i class="fas fa-file-alt"></i></div>
                        <div class="metric-info">
                            <div class="metric-value">24</div>
                            <div class="metric-label">Proposals</div>
                        </div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-icon pink"><i class="fas fa-handshake"></i></div>
                        <div class="metric-info">
                            <div class="metric-value">5</div>
                            <div class="metric-label">Contracts</div>
                        </div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-icon yellow"><i class="fas fa-dollar-sign"></i></div>
                        <div class="metric-info">
                            <div class="metric-value">$12.5K</div>
                            <div class="metric-label">Total Spent</div>
                        </div>
                    </div>
                </div>

                <!-- Recent Jobs Table -->
                <div class="data-table-wrapper">
                    <div class="data-table-header">
                        <h3 class="data-table-title">Recent Job Postings</h3>
                        <a href="<?php echo baseUrl('upwork-client/jobs'); ?>" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Post New Job
                        </a>
                    </div>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Job Title</th>
                                <th>Category</th>
                                <th>Budget</th>
                                <th>Proposals</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>React.js Frontend Developer</td>
                                <td>Web Development</td>
                                <td>$3,000</td>
                                <td>12</td>
                                <td><span class="badge badge-success">Active</span></td>
                            </tr>
                            <tr>
                                <td>UI/UX Designer for Mobile App</td>
                                <td>Design</td>
                                <td>$2,500</td>
                                <td>8</td>
                                <td><span class="badge badge-success">Active</span></td>
                            </tr>
                            <tr>
                                <td>SEO Content Writer</td>
                                <td>Content Writing</td>
                                <td>$800</td>
                                <td>18</td>
                                <td><span class="badge badge-warning">In Review</span></td>
                            </tr>
                            <tr>
                                <td>PHP Backend API Development</td>
                                <td>Backend</td>
                                <td>$4,500</td>
                                <td>6</td>
                                <td><span class="badge badge-info">Draft</span></td>
                            </tr>
                            <tr>
                                <td>Logo & Brand Identity Design</td>
                                <td>Design</td>
                                <td>$1,200</td>
                                <td>22</td>
                                <td><span class="badge badge-danger">Closed</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Active Contracts -->
                <div class="data-table-wrapper">
                    <div class="data-table-header">
                        <h3 class="data-table-title">Active Contracts</h3>
                    </div>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Freelancer</th>
                                <th>Project</th>
                                <th>Rate</th>
                                <th>Progress</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Alex Johnson</td>
                                <td>E-commerce Platform</td>
                                <td>$45/hr</td>
                                <td>75%</td>
                                <td><span class="badge badge-success">Active</span></td>
                            </tr>
                            <tr>
                                <td>Sarah Chen</td>
                                <td>Mobile App UI Redesign</td>
                                <td>$3,000 Fixed</td>
                                <td>40%</td>
                                <td><span class="badge badge-success">Active</span></td>
                            </tr>
                            <tr>
                                <td>Mike Davis</td>
                                <td>API Integration</td>
                                <td>$55/hr</td>
                                <td>90%</td>
                                <td><span class="badge badge-warning">Review</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include BASE_PATH . '/includes/footer.php'; ?>
