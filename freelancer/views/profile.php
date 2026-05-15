<!-- PROFILE -->
<div class="page" id="page-profile">
  <div style="max-width:960px;margin:0 auto">
    
    <!-- Profile Header Card -->
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
                <div style="font-size:13.5px;color:var(--muted);margin-bottom:10px">Senior UI/UX Designer & Product Strategist</div>
                <div style="display:flex;gap:8px;flex-wrap:wrap">
                  <span class="badge b-green">✦ Top Rated Plus</span>
                  <span class="badge b-gray">✓ ID Verified</span>
                  <span class="badge b-blue">🟢 Available</span>
                  <span class="badge b-gray">★ 5.0 · 48 reviews</span>
                </div>
              </div>
              <div style="display:flex;gap:12px;flex-shrink:0">
                <div style="text-align:center">
                  <div class="jss-ring" style="margin:0 auto 4px"><div class="jss-inner">96%</div></div>
                  <div style="font-size:10px;color:var(--muted)">JSS</div>
                </div>
                <div style="text-align:center">
                  <div class="profile-ring" style="width:56px;height:56px;margin:0 auto 4px" onclick="toast('Profile','78% complete — add portfolio to reach 90%')"><div class="profile-ring-inner" style="width:44px;height:44px"><div class="profile-ring-val" style="font-size:14px">78%</div><div class="profile-ring-lbl">done</div></div></div>
                  <div style="font-size:10px;color:var(--muted)">Profile</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Bio / Overview -->
    <div class="card" style="margin-bottom:16px">
      <div class="card-head"><h3>Professional Overview</h3></div>
      <div class="card-body">
        <div style="font-size:14px;line-height:1.7;color:var(--muted)">
          <?php echo !empty($user['bio']) ? nl2br(htmlspecialchars($user['bio'])) : 'No overview provided yet. Click "Edit Profile" to add one.'; ?>
        </div>
      </div>
    </div>

    <div class="g2" style="grid-template-columns:1fr 320px;gap:16px">
      <!-- Left: Skills & Portfolio -->
      <div>
        <div class="card" style="margin-bottom:16px">
          <div class="card-head" style="display:flex;justify-content:space-between;align-items:center">
            <div style="display:flex;align-items:center;gap:10px">
              <h3>Skills</h3>
              <span id="skill-count-badge" style="background:var(--gl);color:var(--g);font-size:11px;font-weight:700;padding:2px 8px;border-radius:10px"><?php echo count($userSkills); ?> / 15</span>
            </div>
            <button class="btn btn-g btn-sm" onclick="openSkillSelector()">+ Browse All Skills</button>
          </div>
          <div class="card-body">
            <!-- Quick-add inline input -->
            <div style="margin-bottom:14px">
              <div style="position:relative">
                <input id="quick-skill-input" type="text" placeholder="Type a skill and press Enter to add…"
                  style="width:100%;padding:9px 40px 9px 13px;border:1.5px solid var(--border);border-radius:8px;font-size:13.5px;font-family:inherit;outline:none;transition:border-color .15s"
                  onfocus="this.style.borderColor='var(--g)';showQuickSuggestions(this.value)"
                  onblur="this.style.borderColor='var(--border)';setTimeout(hideQuickSuggestions,180)"
                  oninput="showQuickSuggestions(this.value)"
                  onkeydown="if(event.key==='Enter'){quickAddSkill(this.value);this.value='';hideQuickSuggestions()}else if(event.key==='Escape'){this.value='';hideQuickSuggestions()}">
                <span style="position:absolute;right:12px;top:50%;transform:translateY(-50%);font-size:12px;color:var(--muted2);pointer-events:none">↵</span>
                <!-- Autocomplete dropdown -->
                <div id="quick-suggestions" style="display:none;position:absolute;top:100%;left:0;right:0;background:white;border:1px solid var(--border);border-radius:8px;box-shadow:0 4px 16px rgba(0,0,0,.1);z-index:500;max-height:200px;overflow-y:auto;margin-top:4px"></div>
              </div>
            </div>

            <div id="profile-skills-display" style="display:<?php echo count($userSkills) ? 'flex' : 'none'; ?>;flex-wrap:wrap;gap:8px;margin-bottom:15px">
              <?php foreach($userSkills as $s): ?>
                <span class="skill-tag"><?php echo htmlspecialchars($s); ?> <span class="skill-remove" onclick="removeSkill('<?php echo addslashes($s); ?>')">×</span></span>
              <?php endforeach; ?>
            </div>
            <div id="profile-skills-empty" style="display:<?php echo count($userSkills) ? 'none' : 'block'; ?>;text-align:center;padding:15px;background:var(--off);border-radius:8px;font-size:13px;color:var(--muted);border:1px dashed var(--border)">
              No skills added yet. Add skills to help clients find you.
            </div>

            <!-- Suggested row -->
            <div style="margin-top:16px;padding-top:16px;border-top:1px solid var(--border)">
              <div style="font-size:12px;color:var(--muted);margin-bottom:10px;font-weight:600;text-transform:uppercase;letter-spacing:0.03em">Suggested based on your role</div>
              <div id="suggested-skills-row" style="display:flex;flex-wrap:wrap;gap:8px"></div>
            </div>
          </div>
        </div>

        <!-- Work History -->
        <div class="card">
          <div class="card-head"><h3>Work History</h3></div>
          <div class="card-body">
            <?php if(empty($workHistory)): ?>
              <div style="text-align:center;padding:30px;color:var(--muted);font-size:13px">Completed contracts will appear here.</div>
            <?php else: ?>
              <?php foreach($workHistory as $w): ?>
                <div style="padding-bottom:16px;margin-bottom:16px;border-bottom:1px solid var(--border)">
                  <div style="font-weight:700;font-size:14px;margin-bottom:4px;color:var(--g)"><?php echo htmlspecialchars($w['job_title']); ?></div>
                  <div style="display:flex;gap:10px;margin-bottom:8px">
                    <span style="font-size:12px;color:var(--muted)">★ 5.0</span>
                    <span style="font-size:12px;color:var(--muted)"><?php echo date('M Y', strtotime($w['end_date'])); ?></span>
                  </div>
                  <div style="font-size:13px;line-height:1.6;color:var(--muted)">"Excellent designer, very responsive and delivered high-quality work on time."</div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Right: Stats & Info -->
      <div>
        <div class="card" style="margin-bottom:16px">
          <div class="card-body">
            <div style="margin-bottom:16px">
              <div style="font-size:18px;font-weight:800">$100k+</div>
              <div style="font-size:12px;color:var(--muted)">Total Earnings</div>
            </div>
            <div style="margin-bottom:16px">
              <div style="font-size:18px;font-weight:800">124</div>
              <div style="font-size:12px;color:var(--muted)">Total Jobs</div>
            </div>
            <div>
              <div style="font-size:18px;font-weight:800">4,210</div>
              <div style="font-size:12px;color:var(--muted)">Total Hours</div>
            </div>
          </div>
        </div>
        <div class="card">
          <div class="card-head"><h3>Languages</h3></div>
          <div class="card-body">
            <div style="font-size:13px;margin-bottom:8px"><strong>English:</strong> Native or Bilingual</div>
            <div style="font-size:13px"><strong>German:</strong> Conversational</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
