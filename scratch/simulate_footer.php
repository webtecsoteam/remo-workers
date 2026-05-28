<?php
// Mocking the environment
function baseUrl($p = "") { return "http://example.com/" . $p; }
$allJobs = [];
$savedJobs = [];
$submittedProposals = [];
$allContracts = [];
$user = ['connects' => 50, 'name' => 'Test User'];

// Include the footer and capture output
ob_start();
include 'freelancer/includes/footer.php';
$output = ob_get_clean();

// Check if output contains valid JS script block
if (preg_match('/<script>(.*?)<\/script>/s', $output, $matches)) {
    $js = $matches[1];
    // Remove PHP tags (already processed)
    file_put_contents('scratch/simulated_footer.js', $js);
    echo "JS extracted to scratch/simulated_footer.js\n";
} else {
    echo "No script block found\n";
}
