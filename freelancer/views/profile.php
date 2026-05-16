<!-- PROFILE -->
<div class="page" id="page-profile">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
    <div style="font-size:20px;font-weight:700">My Profile</div>
    <button class="btn btn-w btn-sm" onclick="openModal('edit-profile')">✏️ Edit Profile</button>
  </div>

  <!-- Profile header card -->
  <div class="card" style="margin-bottom:16px">
    <div class="card-body">
      <div style="display:flex;gap:16px;align-items:flex-start">
        <div style="width:68px;height:68px;border-radius:50%;background:#c8f135;color:var(--forest);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:24px;flex-shrink:0"><?php echo strtoupper(substr($user['name'], 0, 2)); ?></div>
        <div style="flex:1;min-width:0">
          <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:10px;flex-wrap:wrap">
            <div>
              <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;cursor:pointer;group" onclick="openModal('edit-profile')" title="Click to edit profile">
                <div style="font-size:19px;font-weight:700"><?php echo htmlspecialchars($user['name']); ?></div>
                <span style="font-size:11px;color:var(--g);border:1px solid var(--g);border-radius:4px;padding:1px 7px;font-weight:600;opacity:0.7">✏️ Edit</span>
              </div>
              <!-- Subtitle row -->
              <div style="display:flex;align-items:center;gap:4px;flex-wrap:wrap;margin-bottom:8px;cursor:pointer" onclick="openModal('edit-profile')" title="Click to edit profile">
                <span id="field-title" style="font-size:13.5px;color:var(--muted)"><?php echo htmlspecialchars($user['title'] ?: 'Professional Specialist'); ?></span>
                <span style="color:var(--border);font-size:13px">·</span>
                <span id="field-rate" style="font-size:13.5px;color:var(--muted)">$<?php echo number_format($user['hourly_rate'] ?: 0, 2); ?>/hr</span>
                <span style="color:var(--border);font-size:13px">·</span>
                <span id="field-location" style="font-size:13.5px;color:var(--muted)"><?php echo htmlspecialchars($user['country'] ?: 'Global'); ?></span>
              </div>
              <div style="display:flex;gap:6px;flex-wrap:wrap">
                <?php if ($fStats['is_top_rated']): ?>
                  <span class="badge b-green">✦ Top Rated Plus</span>
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
                <span class="badge b-gray">★ 0.0 · <?php echo $fStats['completed_contracts']; ?> reviews</span>
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
    </div>

    <!-- RIGHT -->
    <div>
      <div class="card" style="margin-bottom:14px">
        <div class="card-head"><h3>Profile Info</h3></div>
        <div class="card-body">
          <table class="tbl" style="font-size:13px">
            <tr><td style="color:var(--muted);width:120px">Rate</td><td><strong>$<?php echo number_format($user['hourly_rate'] ?: 0, 2); ?>/hr</strong></td></tr>
            <tr><td style="color:var(--muted)">Location</td><td><?php echo htmlspecialchars($user['country'] ?: 'Global'); ?></td></tr>
            <tr><td style="color:var(--muted)">Member since</td><td><?php echo date('F Y', strtotime($user['created_at'])); ?></td></tr>
            <tr><td style="color:var(--muted)">Total earned</td><td><strong>$<?php echo number_format($fStats['total_earned'], 2); ?>+</strong></td></tr>
          </table>
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
