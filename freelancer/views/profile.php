<?php
// Query all reviews given to this freelancer from the database dynamically
$db = getDB();
$reviewsQuery = $db->prepare("
    SELECT r.*, j.title as job_title, u.name as client_name, u.country as client_country
    FROM reviews r
    JOIN contracts c ON r.contract_id = c.id
    JOIN jobs j ON c.job_id = j.id
    JOIN users u ON r.reviewer_id = u.id
    WHERE r.reviewee_id = ?
    ORDER BY r.created_at DESC
");
$reviewsQuery->execute([$user['id']]);
$allReviews = $reviewsQuery->fetchAll(PDO::FETCH_ASSOC);
?>
<!-- PROFILE -->
<div class="page" id="page-profile">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:10px">
    <div style="font-size:20px;font-weight:700">My Profile</div>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
      <button type="button" class="btn btn-w btn-sm" onclick="copyProfileShareLink(<?php echo (int)$user['id']; ?>)">🔗 Copy profile link</button>
      <a href="<?php echo baseUrl('f/' . encodeFreelancerId($user['id'])); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-w btn-sm" style="text-decoration:none">View public page</a>
      <button class="btn btn-w btn-sm" onclick="openModal('edit-profile')">✏️ Edit Profile</button>
    </div>
  </div>

  <!-- Profile header card -->
  <div class="card" style="margin-bottom:16px">
    <div class="card-body">
      <div style="display:flex;gap:16px;align-items:flex-start">
        <div class="profile-av-wrap" style="width:68px;height:68px;border-radius:50%;background:#c8f135;color:var(--forest);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:24px;flex-shrink:0;overflow:hidden">
          <?php if (!empty($user['avatar_url'])): ?>
            <img src="<?php echo baseUrl($user['avatar_url']); ?>" style="width:100%;height:100%;object-fit:cover">
          <?php else: ?>
            <?php echo strtoupper(substr($user['name'], 0, 2)); ?>
          <?php endif; ?>
        </div>
        <div style="flex:1;min-width:0">
          <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:10px;flex-wrap:wrap">
            <div>
              <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;cursor:pointer;group" onclick="openModal('edit-profile')" title="Click to edit profile">
                <div style="font-size:19px;font-weight:700"><?php echo htmlspecialchars($user['name']); ?></div>
                <span style="font-size:11px;color:var(--g);border:1px solid var(--g);border-radius:4px;padding:1px 7px;font-weight:600;opacity:0.7">✏️ Edit</span>
              </div>
              <!-- Subtitle row -->
              <div style="display:flex;align-items:center;gap:4px;flex-wrap:wrap;margin-bottom:8px;cursor:pointer" onclick="openModal('edit-profile')" title="Click to edit profile">
                <span id="field-title" style="font-size:13.5px;color:var(--muted)"><?php echo htmlspecialchars((string)($user['title'] ?: 'Professional Specialist')); ?></span>
                <span style="color:var(--border);font-size:13px">·</span>
                <span id="field-rate" style="font-size:13.5px;color:var(--muted)">$<?php echo number_format($user['hourly_rate'] ?: 0, 2); ?>/hr</span>
                <span style="color:var(--border);font-size:13px">·</span>
                <span id="field-location" style="font-size:13.5px;color:var(--muted)"><?php echo htmlspecialchars((string)getCountryName($user['country'] ?? 'Global')); ?></span>
              </div>
              <div style="display:flex;gap:6px;flex-wrap:wrap">
                <?php 
                  $dynStats = getFreelancerStats($user['id']);
                  if ($dynStats['badge'] === 'expert_vetted'): 
                ?>
                  <span class="badge b-purple">⭐ Expert Vetted</span>
                <?php elseif ($dynStats['badge'] === 'top_rated_plus'): ?>
                  <span class="badge b-green">✦ Top Rated Plus</span>
                <?php elseif ($dynStats['badge'] === 'top_rated'): ?>
                  <span class="badge b-green">✦ Top Rated</span>
                <?php elseif ($dynStats['badge'] === 'rising_talent'): ?>
                  <span class="badge b-blue">↑ Rising Talent</span>
                <?php endif; ?>
                <span class="badge <?php echo $user['is_verified'] ? 'b-green' : 'b-gray'; ?>">✓ <?php echo $user['is_verified'] ? 'ID Verified' : 'Unverified'; ?></span>
                
                <?php
                  $avail = $user['availability'] ?? 'available';
                  $availLabel = 'Available';
                  $availDot = '🟢';
                  $availClass = 'b-blue';
                  if ($avail === 'limited') { $availLabel = 'Limited'; $availDot = '🟡'; $availClass = 'b-yellow'; }
                  if ($avail === 'unavailable') { $availLabel = 'Unavailable'; $availDot = '🔴'; $availClass = 'b-gray'; }
                ?>
                <span class="badge <?php echo $availClass; ?>"><?php echo $availDot; ?> <?php echo $availLabel; ?></span>
                <span class="badge b-gray">★ <?php echo $dynStats['rating']; ?> · <?php echo $dynStats['reviews_count']; ?> reviews</span>
              </div>
            </div>
            <div style="display:flex;gap:12px;flex-shrink:0">
              <div style="text-align:center">
                <?php 
                  $jssPerc = ($fStats['jss'] === 'N/A') ? 0 : (int)$fStats['jss'];
                  $jssBg = "conic-gradient(var(--g) 0% {$jssPerc}%, var(--border) {$jssPerc}%)";
                ?>
                <div class="jss-ring" style="margin:0 auto 4px; background:<?php echo $jssBg; ?>; <?php echo ($fStats['jss'] === 'N/A') ? 'border-color:var(--border)' : ''; ?>">
                  <div class="jss-inner" style="<?php echo ($fStats['jss'] === 'N/A') ? 'color:var(--muted)' : ''; ?>"><?php echo $fStats['jss']; ?></div>
                </div>
                <div style="font-size:10px;color:var(--muted)">JSS</div>
              </div>
              <div style="text-align:center">
                <?php
                  $completeness = 50; // Base
                  if(!empty($user['bio'])) $completeness += 20;
                  if(!empty($userSkills)) $completeness += 20;
                  if($user['is_verified']) $completeness += 10;
                  $completeness = min(100, $completeness);
                ?>
                <div class="profile-ring" style="width:56px;height:56px;margin:0 auto 4px" onclick="toast('Profile','<?php echo $completeness; ?>% complete')"><div class="profile-ring-inner" style="width:44px;height:44px"><div class="profile-ring-val" style="font-size:14px"><?php echo $completeness; ?>%</div><div class="profile-ring-lbl">done</div></div></div>
                <div style="font-size:10px;color:var(--muted)">Profile</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="g2" style="align-items:start">
    <!-- LEFT -->
    <div>
      <div class="card" style="margin-bottom:16px">
        <div class="card-head"><h3>Professional Overview</h3></div>
        <div class="card-body">
          <div style="font-size:14px;line-height:1.7;color:var(--dark3)">
            <?php echo !empty($user['bio']) ? nl2br(htmlspecialchars($user['bio'])) : 'No overview provided yet. Click "Edit Profile" to add one.'; ?>
          </div>
        </div>
      </div>

      <div class="card" style="margin-bottom:16px">
        <div class="card-head">
          <div style="display:flex;align-items:center;gap:8px">
            <h3>Skills & Expertise</h3>
            <span id="skill-count-badge" style="background:var(--gl);color:var(--g);font-size:11px;font-weight:700;padding:2px 8px;border-radius:10px"><?php echo count($userSkills); ?> / 15</span>
          </div>
          <button class="btn btn-g btn-sm" onclick="openSkillSelector()">+ Browse All Skills</button>
        </div>
        <div class="card-body">
          <div style="margin-bottom:14px">
            <div style="position:relative">
              <input id="quick-skill-input" type="text" placeholder="Type a skill and press Enter to add…"
                style="width:100%;padding:9px 40px 9px 13px;border:1.5px solid var(--border);border-radius:8px;font-size:13.5px;font-family:inherit;outline:none"
                onfocus="this.style.borderColor='var(--g)'" onblur="this.style.borderColor='var(--border)'"
                onkeydown="if(event.key==='Enter'){quickAddSkill(this.value);this.value=''}">
              <span style="position:absolute;right:12px;top:50%;transform:translateY(-50%);font-size:12px;color:var(--muted2)">↵</span>
            </div>
          </div>

          <div id="profile-skills-display" style="display:flex;flex-wrap:wrap;gap:6px">
            <?php foreach($userSkills as $s): ?>
              <span class="skill-tag"><?php echo htmlspecialchars($s); ?> <span class="skill-remove" onclick="removeSkill('<?php echo addslashes($s); ?>')">×</span></span>
            <?php endforeach; ?>
            <?php if(empty($userSkills)): ?>
              <div id="profile-skills-empty" style="text-align:center;padding:10px 0;color:var(--muted);font-size:13px">No skills added yet.</div>
            <?php endif; ?>
          </div>

          <!-- Suggested skills -->
          <div style="margin-top:16px;padding-top:16px;border-top:1px solid var(--border)">
            <div style="font-size:11.5px;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:.05em;margin-bottom:10px">✨ Suggested for you</div>
            <div id="suggested-skills-row" style="display:flex;flex-wrap:wrap;gap:8px"></div>
          </div>
        </div>
      </div>
      <!-- Work History & Client Reviews -->
      <div class="card" style="margin-bottom:16px">
        <div class="card-head" style="border-bottom:1px solid var(--border);padding-bottom:14px">
          <div style="display:flex;align-items:center;gap:8px">
            <h3>Work History & Client Reviews (<?php echo count($allReviews); ?>)</h3>
          </div>
        </div>
        <div class="card-body" style="padding:0">
          <?php if (empty($allReviews)): ?>
            <div style="text-align:center;padding:35px 20px;color:var(--muted);font-size:13.5px">
              <div style="font-size:32px;margin-bottom:10px">⭐</div>
              <strong>No client feedback recorded yet.</strong><br>
              <span style="font-size:12px;color:var(--muted2);display:inline-block;margin-top:4px">Completed contracts with mutual ratings will appear here.</span>
            </div>
          <?php else: ?>
            <div style="display:flex;flex-direction:column">
              <?php foreach ($allReviews as $rev): 
                $stars = str_repeat('★', (int)round($rev['rating'])) . str_repeat('☆', 5 - (int)round($rev['rating']));
                $formattedDate = date('M d, Y', strtotime($rev['created_at']));
              ?>
                <div style="padding:20px;border-bottom:1px solid var(--border);display:flex;flex-direction:column;gap:8px">
                  <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px">
                    <h4 style="margin:0;font-size:14px;color:var(--dark);font-weight:700"><?php echo htmlspecialchars($rev['job_title']); ?></h4>
                    <span style="font-size:13px;font-weight:700;color:#b45309;white-space:nowrap"><?php echo $stars; ?> <?php echo number_format($rev['rating'], 1); ?></span>
                  </div>
                  <p style="margin:0;font-size:13px;line-height:1.6;color:var(--dark3);font-style:italic">
                    "<?php echo htmlspecialchars($rev['feedback'] ?: 'No comment provided.'); ?>"
                  </p>
                  <div style="display:flex;gap:10px;font-size:11.5px;color:var(--muted2)">
                    <span>By <?php echo htmlspecialchars($rev['client_name']); ?> (📍 <?php echo htmlspecialchars($rev['client_country'] ?: 'Global'); ?>)</span>
                    <span>·</span>
                    <span><?php echo $formattedDate; ?></span>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- RIGHT -->
    <div>
      <div class="card" style="margin-bottom:14px">
        <div class="card-head"><h3>Share profile</h3></div>
        <div class="card-body">
          <p style="font-size:12px;color:var(--muted);margin:0 0 12px;line-height:1.5">Share this link with clients or on social media. Anyone can view your public profile.</p>
          <input type="text" readonly id="my-public-profile-url" value="<?php echo htmlspecialchars(baseUrl('f/' . encodeFreelancerId($user['id']))); ?>" style="width:100%;padding:10px 12px;font-size:12px;border:1.5px solid var(--border);border-radius:8px;background:#f9fafb;margin-bottom:10px;font-family:inherit" onclick="this.select()">
          <div style="display:flex;gap:8px;flex-wrap:wrap">
            <button type="button" class="btn btn-g btn-sm" style="flex:1;min-width:100px" onclick="copyProfileShareLink(<?php echo (int)$user['id']; ?>)">Copy link</button>
            <a href="<?php echo baseUrl('f/' . encodeFreelancerId($user['id'])); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-w btn-sm" style="flex:1;min-width:100px;text-align:center;text-decoration:none">Preview</a>
          </div>
        </div>
      </div>

      <div class="card" style="margin-bottom:14px">
        <div class="card-head"><h3>Profile Info</h3></div>
        <div class="card-body">
          <div style="display:flex;flex-direction:column;gap:12px">
            <div style="display:flex;justify-content:space-between;font-size:13px">
              <span style="color:var(--muted)">Rate</span>
              <strong style="color:var(--dark)">$<?php echo number_format($user['hourly_rate'] ?: 0, 2); ?>/hr</strong>
            </div>
            <div style="display:flex;justify-content:space-between;font-size:13px">
              <span style="color:var(--muted)">Location</span>
              <span style="color:var(--dark);font-weight:600"><?php echo htmlspecialchars((string)getCountryName($user['country'] ?? 'Global')); ?></span>
            </div>
            <div style="display:flex;justify-content:space-between;font-size:13px">
              <span style="color:var(--muted)">Member since</span>
              <span style="color:var(--dark);font-weight:600"><?php echo date('F Y', strtotime($user['created_at'])); ?></span>
            </div>
            <div style="display:flex;justify-content:space-between;font-size:13px">
              <span style="color:var(--muted)">Total earned</span>
              <strong style="color:var(--g)">$<?php echo number_format($fStats['total_earned'], 2); ?>+</strong>
            </div>
            <?php if ($activeAgency): ?>
            <div style="display:flex;justify-content:space-between;font-size:13px">
              <span style="color:var(--muted)">Complete agency earnings</span>
              <strong style="color:var(--g)">$<?php echo number_format((float)$completeAgencyEarnings, 2); ?></strong>
            </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <div class="card" style="margin-bottom:14px">
        <div class="card-head"><h3>Agency Profile</h3></div>
        <div class="card-body">
          <?php if (!$isAgencyAccount): ?>
            <p style="font-size:12px;color:var(--muted);margin:0 0 12px;line-height:1.5">Switch to an agency account to submit proposals as your agency and let teammates manage chats for running agency contracts.</p>
            <button class="btn btn-g btn-sm" style="width:100%;justify-content:center" onclick="convertToAgencyAccount()">Convert to Agency Account</button>
          <?php elseif (!$activeAgency): ?>
            <p style="font-size:12px;color:var(--muted);margin:0 0 12px;line-height:1.5">Your account is in agency mode. Create your agency to start adding members.</p>
            <input id="agency-name-input" type="text" placeholder="Agency name" style="width:100%;padding:9px 11px;border:1px solid var(--border);border-radius:8px;font-size:12px;margin-bottom:8px">
            <textarea id="agency-description-input" rows="2" placeholder="Agency description (optional)" style="width:100%;padding:9px 11px;border:1px solid var(--border);border-radius:8px;font-size:12px;resize:vertical;margin-bottom:8px"></textarea>
            <button class="btn btn-g btn-sm" style="width:100%;justify-content:center" onclick="createAgencyProfile()">Create Agency</button>
          <?php else: ?>
            <div style="font-size:13px;font-weight:700;color:var(--dark);margin-bottom:2px"><?php echo htmlspecialchars($activeAgency['name']); ?></div>
            <div style="font-size:11.5px;color:var(--muted);margin-bottom:10px">Role: <?php echo ucfirst((string)$activeAgency['member_role']); ?></div>
            <?php if (!empty($activeAgency['description'])): ?>
              <p style="font-size:12px;color:var(--muted);margin:0 0 12px;line-height:1.5"><?php echo nl2br(htmlspecialchars((string)$activeAgency['description'])); ?></p>
            <?php endif; ?>

            <?php if (in_array((string)$activeAgency['member_role'], ['owner', 'admin'], true)): ?>
              <div style="display:flex;flex-direction:column;gap:8px;margin-bottom:12px;padding:10px;border:1px dashed var(--border);border-radius:8px;background:#fafafa">
                <input id="agency-edit-name" type="text" placeholder="Agency name" value="<?php echo htmlspecialchars((string)$activeAgency['name']); ?>" style="padding:9px 11px;border:1px solid var(--border);border-radius:8px;font-size:12px">
                <textarea id="agency-edit-description" rows="2" placeholder="Agency description (optional)" style="padding:9px 11px;border:1px solid var(--border);border-radius:8px;font-size:12px;resize:vertical"><?php echo htmlspecialchars((string)($activeAgency['description'] ?? '')); ?></textarea>
                <div style="display:flex;gap:8px;flex-wrap:wrap">
                  <button class="btn btn-g btn-sm" style="flex:1;justify-content:center;min-width:120px" onclick="updateAgencyProfile()">Save Agency</button>
                  <button class="btn btn-sm" style="background:#ef4444;color:#fff;border:none;flex:1;justify-content:center;min-width:120px" onclick="deleteAgencyProfile()">Delete Agency</button>
                </div>
              </div>

              <div style="display:flex;flex-direction:column;gap:8px;margin-bottom:10px">
                <input id="agency-member-email" type="email" placeholder="Freelancer email" style="padding:9px 11px;border:1px solid var(--border);border-radius:8px;font-size:12px">
                <select id="agency-member-role" style="padding:9px 11px;border:1px solid var(--border);border-radius:8px;font-size:12px">
                  <option value="member">Member</option>
                  <option value="admin">Admin</option>
                </select>
              </div>
              <button class="btn btn-w btn-sm" style="width:100%;justify-content:center;margin-bottom:8px" onclick="addAgencyMember()">Invite Member</button>
              <p style="margin:0 0 12px;font-size:11px;color:var(--muted);line-height:1.5">Only freelancers with verified Remoworkers profiles can be invited. They will receive an email with Accept/Decline options.</p>
            <?php endif; ?>

            <div style="font-size:11px;color:var(--muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:8px">Members</div>
            <div style="display:flex;flex-direction:column;gap:6px;max-height:180px;overflow:auto">
              <?php foreach ($agencyMembers as $m): ?>
                <div style="display:flex;align-items:center;justify-content:space-between;padding:8px 10px;border:1px solid var(--border);border-radius:8px">
                  <div style="min-width:0">
                    <div style="font-size:12px;font-weight:600;color:var(--dark);white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?php echo htmlspecialchars($m['name']); ?></div>
                    <div style="font-size:11px;color:var(--muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?php echo htmlspecialchars($m['email']); ?></div>
                  </div>
                  <div style="display:flex;align-items:center;gap:6px;flex-shrink:0">
                    <span class="badge b-gray" style="font-size:10px"><?php echo ucfirst((string)$m['role']); ?></span>
                    <?php if (($m['status'] ?? '') === 'pending'): ?>
                      <span class="badge b-yellow" style="font-size:10px">Pending</span>
                    <?php endif; ?>
                  </div>
                </div>
              <?php endforeach; ?>
              <?php if (empty($agencyMembers)): ?>
                <div style="font-size:12px;color:var(--muted);text-align:center;padding:10px 0">No members yet.</div>
              <?php endif; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <?php if(!$user['is_verified']): ?>
        <div class="card" style="border:1.5px solid #fde68a;background:#fffbeb">
          <div class="card-body" style="padding:14px 16px">
            <div style="display:flex;align-items:center;gap:12px">
              <div style="width:40px;height:40px;border-radius:10px;background:#fef3c7;display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0">🪪</div>
              <div style="flex:1;min-width:0">
                <div style="font-size:13px;font-weight:700;margin-bottom:2px">Identity Not Verified</div>
                <div style="font-size:12px;color:#92400e;line-height:1.5">Verify your ID to unlock trust.</div>
              </div>
              <button class="btn btn-sm" style="background:#f59e0b;color:white;border:none;flex-shrink:0" onclick="showPage('verification')">Verify →</button>
            </div>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>
