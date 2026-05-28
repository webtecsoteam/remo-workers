<?php
/**
 * Platform skill taxonomy (matches client/freelancer UW_CATS / SKILL_TREE).
 *
 * @return array<string, array<string, list<string>>>
 */
function getSkillTree(): array
{
    static $tree = null;
    if ($tree !== null) {
        return $tree;
    }

    $tree = [
        'Accounting & Consulting' => [
            'Personal & Professional Coaching' => ['Career Coaching', 'Personal Coaching'],
            'Accounting & Bookkeeping' => ['Accounting', 'Bookkeeping'],
            'Financial Planning' => ['Financial Analysis & Modeling', 'Financial Management/CFO'],
            'Recruiting & Human Resources' => ['HR Administration', 'Recruiting & Talent Sourcing', 'Training & Development'],
            'Management Consulting & Analysis' => ['Business Analysis & Strategy', 'Instructional Design', 'Management Consulting'],
            'Other - Accounting & Consulting' => ['Tax Preparation'],
        ],
        'Admin Support' => [
            'Data Entry & Transcription Services' => ['Data Entry', 'Manual Transcription'],
            'Virtual Assistance' => ['Executive Virtual Assistance', 'Legal Virtual Assistance', 'Medical Virtual Assistance', 'Ecommerce Management', 'Personal Virtual Assistance', 'General Virtual Assistance'],
            'Project Management' => ['Business Project Management', 'Supply Chain & Logistics Project Management', 'Construction & Engineering Project Management', 'Development & IT Project Management', 'Healthcare Project Management', 'Digital Project Management'],
            'Market Research & Product Reviews' => ['Web & Software Product Research', 'Market Research', 'General Research Services', 'Product Reviews', 'Qualitative Research', 'Quantitative Research'],
        ],
        'Customer Service' => [
            'Community Management & Tagging' => ['Community Management', 'Content Moderation', 'Visual Tagging & Processing'],
            'Customer Service & Tech Support' => ['Customer Onboarding', 'Email, Phone & Chat Support', 'Customer Success', 'IT Support', 'Tech Support'],
        ],
        'Data Science & Analytics' => [
            'Data Analysis & Testing' => ['Data Analytics', 'Data Visualization', 'Experimentation & Testing'],
            'Data Extraction/ETL' => ['Data Extraction', 'Data Processing'],
            'Data Mining & Management' => ['Data Engineering', 'Data Mining'],
            'AI & Machine Learning' => ['Generative AI Modeling', 'AI Data Annotation & Labeling', 'Deep Learning', 'Knowledge Representation', 'Machine Learning'],
        ],
        'Design & Creative' => [
            'Art & Illustration' => ['Portraits & Caricatures', 'Cartoons & Comics', 'Fine Art', 'Illustration', 'Pattern Design'],
            'Audio & Music Production' => ['AI Speech & Audio Generation', 'Audio Editing', 'Audio Production', 'Songwriting & Music Composition', 'Music Production'],
            'Branding & Logo Design' => ['Brand Identity Design', 'Logo Design'],
            'NFT, AR/VR & Game Art' => ['NFT Art', 'Game Art', 'AR/VR Design'],
            'Graphic, Editorial & Presentation Design' => ['AI Image Generation & Editing', 'Art Direction', 'Creative Direction', 'Editorial Design', 'Graphic Design', 'Image Editing', 'Packaging Design', 'Presentation Design'],
            'Performing Arts' => ['Acting', 'Music Performance', 'Singing', 'Voice Talent'],
            'Photography' => ['Local Photography', 'Product Photography'],
            'Product Design' => ['Fashion Design', 'Jewelry Design', 'Product & Industrial Design'],
            'Video & Animation' => ['AI Video Generation & Editing', 'Motion Graphics', '3D Animation', '2D Animation', 'Video Editing', 'Videography', 'Video Production', 'Visual Effects'],
        ],
        'Engineering & Architecture' => [
            'Building & Landscape Architecture' => ['Architectural Design', 'Landscape Architecture'],
            'Chemical Engineering' => ['Chemical & Process Engineering'],
            'Civil & Structural Engineering' => ['Building Information Modeling', 'Civil Engineering', 'Structural Engineering'],
            'Electrical & Electronic Engineering' => ['Electrical Engineering', 'Electronic Engineering'],
            'Interior & Trade Show Design' => ['Trade Show Design', 'Interior Design'],
            'Energy & Mechanical Engineering' => ['Energy Engineering', 'Mechanical Engineering'],
            'Physical Sciences' => ['Biology', 'Chemistry', 'Mathematics', 'Physics', 'STEM Tutoring'],
            '3D Modeling & CAD' => ['CAD', '3D Modeling & Rendering'],
            'Contract Manufacturing' => ['Logistics & Supply Chain Management', 'Sourcing & Procurement'],
        ],
        'IT & Networking' => [
            'Database Management & Administration' => ['Database Administration'],
            'ERP/CRM Software' => ['Business Applications Development', 'Systems Engineering'],
            'Information Security & Compliance' => ['IT Compliance', 'Information Security', 'Network Security'],
            'Network & System Administration' => ['Network Administration', 'Systems Administration'],
            'DevOps & Solution Architecture' => ['Cloud Engineering', 'DevOps Engineering', 'Solution Architecture'],
        ],
        'Legal' => [
            'Corporate & Contract Law' => ['Business & Corporate Law', 'Intellectual Property Law', 'Paralegal Services'],
            'International & Immigration Law' => ['Immigration Law', 'International Law'],
            'Finance & Tax Law' => ['Securities & Finance Law', 'Tax Law'],
            'Public Law' => ['Labor & Employment Law', 'Regulatory Law'],
        ],
        'Sales & Marketing' => [
            'Digital Marketing' => ['Display Advertising', 'Campaign Management', 'Email Marketing', 'Marketing Automation', 'Search Engine Marketing', 'SEO', 'Social Media Marketing'],
            'Lead Generation & Telemarketing' => ['Sales & Business Development', 'Lead Generation', 'Telemarketing'],
            'Marketing, PR & Brand Strategy' => ['Brand Strategy', 'Content Strategy', 'Marketing Strategy', 'Public Relations', 'Social Media Strategy'],
        ],
        'Translation' => [
            'Language Tutoring & Interpretation' => ['Live Interpretation', 'Sign Language Interpretation', 'Language Tutoring'],
            'Translation & Localization Services' => ['Language Localization', 'Legal Document Translation', 'Medical Document Translation', 'Technical Document Translation', 'General Translation Services'],
        ],
        'Web, Mobile & Software Dev' => [
            'Blockchain, NFT & Cryptocurrency' => ['Blockchain & NFT Development', 'Crypto Coins & Tokens', 'Crypto Wallet Development'],
            'AI Apps & Integration' => ['AI Chatbot Development', 'AI Integration'],
            'Desktop Application Development' => ['Desktop Software Development'],
            'Ecommerce Development' => ['Ecommerce Website Development'],
            'Game Design & Development' => ['Video Game Development'],
            'Mobile Development' => ['Mobile App Development', 'Mobile Game Development'],
            'Other - Software Development' => ['AR/VR Development', 'Database Development', 'Emerging Tech', 'Firmware Development', 'Coding Tutoring'],
            'Product Management & Scrum' => ['Product Management', 'Scrum Leadership'],
            'QA Testing' => ['Automation Testing', 'Manual Testing'],
            'Scripts & Utilities' => ['Scripting & Automation'],
            'Web & Mobile Design' => ['Mobile Design', 'Prototyping', 'UX/UI Design', 'Web Design'],
            'Web Development' => ['Back-End Development', 'CMS Development', 'Front-End Development', 'Full Stack Development'],
        ],
        'Writing' => [
            'Sales & Marketing Copywriting' => ['Ad & Email Copywriting', 'Marketing Copywriting', 'Sales Copywriting'],
            'Content Writing' => ['Web & UX Writing', 'Article & Blog Writing', 'AI Content Writing', 'Creative Writing', 'Ghostwriting', 'Scriptwriting', 'Writing Tutoring'],
            'Editing & Proofreading Services' => ['Proofreading', 'Copy Editing'],
            'Professional & Business Writing' => ['Academic & Research Writing', 'Legal Writing', 'Medical Writing', 'Resume & Cover Letter Writing', 'Business & Proposal Writing', 'Grant Writing', 'Technical Writing'],
        ],
    ];

    return $tree;
}

/**
 * @param list<array{tree: string, subs?: list<string>|null, exclude_subs?: list<string>}> $sources
 * @return list<string>
 */
function skillTreeCollectSpecialties(array $sources, ?array $tree = null): array
{
    $tree = $tree ?? getSkillTree();
    $skills = [];

    foreach ($sources as $source) {
        $treeKey = $source['tree'] ?? '';
        if ($treeKey === '' || !isset($tree[$treeKey])) {
            continue;
        }
        $onlySubs = $source['subs'] ?? null;
        $excludeSubs = $source['exclude_subs'] ?? [];

        foreach ($tree[$treeKey] as $subName => $specialties) {
            if ($onlySubs !== null && !in_array($subName, $onlySubs, true)) {
                continue;
            }
            if (in_array($subName, $excludeSubs, true)) {
                continue;
            }
            foreach ($specialties as $skill) {
                $skills[] = $skill;
            }
        }
    }

    return array_values(array_unique($skills));
}
