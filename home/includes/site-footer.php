<?php
require_once __DIR__ . '/public_template.php';

// Homepage uses publicTemplateRenderHeader() which opens <main>.
// Render the matching closing wrapper + footer HTML so inner pages match exactly.
publicTemplateRenderFooter();
