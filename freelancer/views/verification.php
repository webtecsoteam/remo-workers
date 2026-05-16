<?php
$db = getDB();
$userId = $user['id'];
$emailVerified = Auth::isEmailVerified($user);

// Get user documents
$stmt = $db->prepare("SELECT * FROM user_documents WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$userId]);
$docs = $stmt->fetchAll();

// Get user verification status from users table
$uStmt = $db->prepare("SELECT is_verified, status, email_verified_at FROM users WHERE id = ?");
$uStmt->execute([$userId]);
$userData = $uStmt->fetch();

$isVerified = (bool)($userData['is_verified'] ?? false);
$hasPending = false;
foreach($docs as $d) { if($d['status'] === 'pending') { $hasPending = true; break; } }

$vStatus = 'unverified';
if ($isVerified) $vStatus = 'verified';
elseif ($hasPending) $vStatus = 'pending';
?>

<div class="page" id="page-verification">
  <div style="width:100%;padding:20px">

    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px">
      <div>
        <div style="font-size:24px;font-weight:800;color:var(--dark)">Account Verification</div>
        <div style="font-size:13.5px;color:var(--muted);margin-top:4px">Verify your email and identity before applying to jobs.</div>
      </div>
      <?php if ($vStatus === 'verified'): ?>
        <span class="badge b-green" style="font-size:12px;padding:6px 14px">✅ Verified</span>
      <?php elseif ($vStatus === 'pending'): ?>
        <span class="badge b-yellow" style="font-size:12px;padding:6px 14px">⏳ Pending</span>
      <?php else: ?>
        <span class="badge b-red" style="font-size:12px;padding:6px 14px">⚠️ Unverified</span>
      <?php endif; ?>
    </div>

    <?php if ($vStatus === 'verified'): ?>
      <div class="card" style="border:2px solid var(--g);background:var(--gl)">
        <div class="card-body" style="text-align:center;padding:40px 30px">
          <div style="font-size:52px;margin-bottom:20px">🛡️</div>
          <div style="font-size:22px;font-weight:800;margin-bottom:10px;color:var(--forest)">You are Verified!</div>
          <div style="font-size:14px;color:#166534;line-height:1.7;max-width:450px;margin:0 auto 24px">
            Your identity has been successfully confirmed. You now have the "ID Verified" badge on your profile, higher withdrawal limits, and access to premium enterprise contracts.
          </div>
          <button class="btn btn-g" onclick="showPage('profile')">View My Profile</button>
        </div>
      </div>
    <?php elseif ($vStatus === 'pending'): ?>
      <div class="card" style="border:1px solid #ffeeba;background:#fffcf0">
        <div class="card-body" style="text-align:center;padding:40px 30px">
          <div style="font-size:52px;margin-bottom:20px">🕒</div>
          <div style="font-size:22px;font-weight:800;margin-bottom:10px;color:#854d0e">Verification in Progress</div>
          <div style="font-size:14px;color:#92400e;line-height:1.7;max-width:450px;margin:0 auto 24px">
            Our team is currently reviewing your documents. This process typically takes <strong>1–3 business days</strong>. We'll notify you via email and on your dashboard as soon as the review is complete.
          </div>
          <div style="display:inline-flex;align-items:center;gap:8px;background:white;border:1px solid #ffeeba;border-radius:10px;padding:12px 20px;font-size:13.5px;font-weight:600;color:#854d0e">
            <span>🔒</span> Current Status: <strong>Under Review</strong>
          </div>
        </div>
      </div>
    <?php else: ?>
      <?php if (!$emailVerified): ?>
      <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:12px;padding:16px 20px;margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;gap:15px;flex-wrap:wrap">
        <div>
          <div style="font-weight:700;font-size:14.5px;color:#1e40af;margin-bottom:4px">Step 1: Verify your email</div>
          <div style="font-size:13px;color:#1d4ed8">Click the button to open your verification link.</div>
        </div>
        <button class="btn" style="background:#2563eb;color:white;border:none" onclick="requestEmailVerification()">Verify Email →</button>
      </div>
      <?php endif; ?>
      <!-- UNVERIFIED FLOW -->
      <div id="v-flow-container">
        <!-- Why verify banner -->
        <div style="background:linear-gradient(135deg,#16281a 0%,#1f3a23 100%);border-radius:14px;padding:24px 28px;margin-bottom:24px;display:flex;align-items:center;gap:24px;flex-wrap:wrap">
          <div style="font-size:42px">🛡️</div>
          <div style="flex:1;min-width:260px">
            <div style="font-size:17px;font-weight:800;color:white;margin-bottom:8px">Why verify your identity?</div>
            <div style="display:flex;flex-wrap:wrap;gap:12px 20px">
              <span style="font-size:13px;color:rgba(255,255,255,.8);display:flex;align-items:center;gap:6px"><span style="color:#c8f135">✓</span> Earn the "ID Verified" badge</span>
              <span style="font-size:13px;color:rgba(255,255,255,.8);display:flex;align-items:center;gap:6px"><span style="color:#c8f135">✓</span> Higher withdrawal limits</span>
              <span style="font-size:13px;color:rgba(255,255,255,.8);display:flex;align-items:center;gap:6px"><span style="color:#c8f135">✓</span> Increased client confidence</span>
              <span style="font-size:13px;color:rgba(255,255,255,.8);display:flex;align-items:center;gap:6px"><span style="color:#c8f135">✓</span> Access to premium contracts</span>
            </div>
          </div>
        </div>

        <!-- Steps progress -->
        <div style="display:flex;align-items:center;gap:0;margin-bottom:24px;background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden">
          <div id="vstep-1" style="flex:1;padding:16px 20px;border-right:1px solid var(--border);cursor:pointer;transition:background .15s;background:var(--gl)" onclick="switchVStep(1)">
            <div style="display:flex;align-items:center;gap:10px">
              <div id="vstep-1-ico" style="width:26px;height:26px;border-radius:50%;background:var(--g);color:white;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:800;flex-shrink:0">1</div>
              <div>
                <div style="font-size:13px;font-weight:700">Choose Document</div>
                <div style="font-size:11.5px;color:var(--muted)">Select ID type</div>
              </div>
            </div>
          </div>
          <div id="vstep-2" style="flex:1;padding:16px 20px;border-right:1px solid var(--border);cursor:pointer;transition:background .15s" onclick="switchVStep(2)">
            <div style="display:flex;align-items:center;gap:10px">
              <div id="vstep-2-ico" style="width:26px;height:26px;border-radius:50%;background:var(--border);color:var(--muted);display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:800;flex-shrink:0">2</div>
              <div>
                <div style="font-size:13px;font-weight:700">Upload Document</div>
                <div style="font-size:11.5px;color:var(--muted)">Front & back photos</div>
              </div>
            </div>
          </div>
          <div id="vstep-3" style="flex:1;padding:16px 20px;cursor:pointer;transition:background .15s" onclick="switchVStep(3)">
            <div style="display:flex;align-items:center;gap:10px">
              <div id="vstep-3-ico" style="width:26px;height:26px;border-radius:50%;background:var(--border);color:var(--muted);display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:800;flex-shrink:0">3</div>
              <div>
                <div style="font-size:13px;font-weight:700">Review & Submit</div>
                <div style="font-size:11.5px;color:var(--muted)">Confirm & send</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Step panels -->
        <div id="vpanel-1" class="card" style="margin-bottom:20px">
          <div class="card-head"><h3>Step 1 — Choose Document Type</h3></div>
          <div class="card-body">
            <div style="font-size:14px;color:var(--muted);margin-bottom:20px">Select the government-issued document you want to use for verification.</div>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:15px" id="doc-type-grid">
              <div class="doc-type-card" id="dtype-passport" onclick="selectDocType('passport','dtype-passport')" style="border:2px solid var(--border);border-radius:12px;padding:24px 16px;cursor:pointer;transition:all .2s;text-align:center">
                <div style="font-size:40px;margin-bottom:12px">🛂</div>
                <div style="font-size:14.5px;font-weight:700;margin-bottom:5px">Passport</div>
                <div style="font-size:12px;color:var(--muted)">Photo page only</div>
              </div>
              <div class="doc-type-card" id="dtype-national-id" onclick="selectDocType('national-id','dtype-national-id')" style="border:2px solid var(--border);border-radius:12px;padding:24px 16px;cursor:pointer;transition:all .2s;text-align:center">
                <div style="font-size:40px;margin-bottom:12px">🪪</div>
                <div style="font-size:14.5px;font-weight:700;margin-bottom:5px">National ID</div>
                <div style="font-size:12px;color:var(--muted)">Front & back</div>
              </div>
              <div class="doc-type-card" id="dtype-drivers" onclick="selectDocType('drivers','dtype-drivers')" style="border:2px solid var(--border);border-radius:12px;padding:24px 16px;cursor:pointer;transition:all .2s;text-align:center">
                <div style="font-size:40px;margin-bottom:12px">🚗</div>
                <div style="font-size:14.5px;font-weight:700;margin-bottom:5px">Driver's Licence</div>
                <div style="font-size:12px;color:var(--muted)">Front & back</div>
              </div>
            </div>
            <div id="dtype-selected-bar" style="display:none;margin-top:20px;background:var(--gl);border:1px solid #c3e6c3;border-radius:10px;padding:12px 16px;align-items:center;gap:12px">
              <span style="font-size:18px">✅</span>
              <span id="dtype-selected-text" style="font-size:14px;font-weight:700;color:var(--g)">Passport selected</span>
              <button class="btn btn-g" style="margin-left:auto" onclick="switchVStep(2)">Next: Upload →</button>
            </div>
          </div>
        </div>

        <div id="vpanel-2" class="card" style="margin-bottom:20px;display:none">
          <div class="card-head">
            <h3>Step 2 — Upload Your Document</h3>
            <span id="upload-doc-label" class="badge b-green">Passport</span>
          </div>
          <div class="card-body">
            <div style="font-size:14px;color:var(--muted);margin-bottom:20px;line-height:1.7">
              Upload a clear, colour photo or scan. Make sure all four corners are visible and text is readable. Files must be <strong>JPG, PNG, or PDF</strong> and under <strong>10 MB</strong>.
            </div>

            <div class="g2" style="gap:20px;margin-bottom:24px">
              <div>
                <div style="font-size:13px;font-weight:700;margin-bottom:10px" id="front-label">📄 Front side / Photo page</div>
                <div id="vdrop-front"
                  ondragover="event.preventDefault();this.style.borderColor='var(--g)';this.style.background='var(--gl)'"
                  ondragleave="this.style.borderColor='var(--border)';this.style.background='var(--off)'"
                  ondrop="handleVDrop(event,'front')"
                  style="border:2px dashed var(--border);border-radius:12px;padding:32px 20px;text-align:center;background:var(--off);cursor:pointer;transition:all .2s;min-height:160px;display:flex;flex-direction:column;justify-content:center;align-items:center"
                  onclick="document.getElementById('vinput-front').click()">
                  <div id="vfront-preview" style="display:none;flex-direction:column;align-items:center;gap:10px"></div>
                  <div id="vfront-placeholder">
                    <div style="font-size:32px;margin-bottom:10px">📤</div>
                    <div style="font-size:13px;font-weight:700;margin-bottom:4px">Click to upload</div>
                    <div style="font-size:11.5px;color:var(--muted)">Max 10 MB</div>
                  </div>
                </div>
                <input type="file" id="vinput-front" accept=".jpg,.jpeg,.png,.pdf" style="display:none" onchange="handleVFileInput(this.files,'front')">
              </div>

              <div id="vback-section">
                <div style="font-size:13px;font-weight:700;margin-bottom:10px">📄 Back side</div>
                <div id="vdrop-back"
                  ondragover="event.preventDefault();this.style.borderColor='var(--g)';this.style.background='var(--gl)'"
                  ondragleave="this.style.borderColor='var(--border)';this.style.background='var(--off)'"
                  ondrop="handleVDrop(event,'back')"
                  style="border:2px dashed var(--border);border-radius:12px;padding:32px 20px;text-align:center;background:var(--off);cursor:pointer;transition:all .2s;min-height:160px;display:flex;flex-direction:column;justify-content:center;align-items:center"
                  onclick="document.getElementById('vinput-back').click()">
                  <div id="vback-preview" style="display:none;flex-direction:column;align-items:center;gap:10px"></div>
                  <div id="vback-placeholder">
                    <div style="font-size:32px;margin-bottom:10px">📤</div>
                    <div style="font-size:13px;font-weight:700;margin-bottom:4px">Click to upload</div>
                    <div style="font-size:11.5px;color:var(--muted)">Max 10 MB</div>
                  </div>
                </div>
                <input type="file" id="vinput-back" accept=".jpg,.jpeg,.png,.pdf" style="display:none" onchange="handleVFileInput(this.files,'back')">
              </div>
            </div>

            <div style="background:#f0f9ff;border:1px solid #bae6fd;border-radius:10px;padding:16px;font-size:13px;color:#0369a1;line-height:1.7;margin-bottom:24px">
              <strong>📸 Best results</strong>: Use a flat surface with good lighting. Ensure no blur, flash glare, or cropped edges.
            </div>

            <div style="display:flex;gap:12px">
              <button class="btn btn-w" onclick="switchVStep(1)">← Back</button>
              <button class="btn btn-g" style="flex:1;justify-content:center" id="vnext-2" onclick="validateAndGoStep3()" disabled style="opacity:.5">Next: Review →</button>
            </div>
          </div>
        </div>

        <div id="vpanel-3" class="card" style="margin-bottom:20px;display:none">
          <div class="card-head"><h3>Step 3 — Review & Submit</h3></div>
          <div class="card-body">
            <div style="font-size:14px;color:var(--muted);margin-bottom:20px;line-height:1.7">Please review your uploaded document and confirm your details. Verification typically takes 1–3 business days.</div>

            <div id="vreview-content" style="margin-bottom:24px"></div>

            <div style="background:var(--off);border:1px solid var(--border);border-radius:10px;padding:18px;margin-bottom:24px">
              <div style="font-size:13.5px;font-weight:700;margin-bottom:14px;color:var(--dark)">Confirm Details</div>
              <div class="g2" style="gap:15px">
                <div class="fg" style="margin-bottom:0"><label>Full Legal Name</label><input type="text" value="<?php echo htmlspecialchars($user['name']); ?>" id="vlegal-name"></div>
                <div class="fg" style="margin-bottom:0"><label>Nationality</label><input type="text" placeholder="e.g. United Kingdom" id="vnationality"></div>
                <div class="fg" style="margin-bottom:0"><label>Date of Birth</label><input type="date" id="vdob"></div>
                <div class="fg" style="margin-bottom:0"><label>Document ID Number</label><input type="text" placeholder="e.g. G12345678" id="vdoc-number"></div>
              </div>
            </div>

            <label style="display:flex;align-items:flex-start;gap:12px;cursor:pointer;font-size:13px;color:var(--dark3);line-height:1.65;margin-bottom:24px">
              <input type="checkbox" id="vconsent" style="margin-top:4px;accent-color:var(--g);width:16px;height:16px;flex-shrink:0" onchange="toggleVSubmit()">
              <span>I consent to RemoWorkers processing my personal data and identity document for the purpose of identity verification, in accordance with the <a href="#" style="color:var(--g);text-decoration:underline">Privacy Policy</a>.</span>
            </label>

            <div style="display:flex;gap:12px">
              <button class="btn btn-w" onclick="switchVStep(2)">← Back</button>
              <button class="btn btn-g" style="flex:1;justify-content:center;opacity:.5" id="vsubmit-btn" disabled onclick="submitVerification()">🛡️ Submit for Verification</button>
            </div>
          </div>
        </div>

        <div id="vpanel-done" style="display:none">
          <div class="card" style="border:2px solid var(--g);background:var(--gl)">
            <div class="card-body" style="text-align:center;padding:45px 30px">
              <div style="font-size:60px;margin-bottom:20px">🎉</div>
              <div style="font-size:24px;font-weight:800;margin-bottom:12px;color:var(--forest)">Submitted Successfully!</div>
              <div style="font-size:14.5px;color:#166534;line-height:1.7;max-width:450px;margin:0 auto 24px">Your documents have been received. Our compliance team will review your submission within <strong>1–3 business days</strong>. You'll receive a notification once verified.</div>
              <button class="btn btn-g" onclick="location.reload()">Back to Verification</button>
            </div>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <?php if (!empty($docs)): ?>
      <div style="margin-top:40px">
        <h3 style="font-size:16px;font-weight:800;margin-bottom:16px;color:var(--dark)">Document History</h3>
        <div class="card" style="border-radius:12px">
          <?php foreach ($docs as $doc): ?>
            <div style="padding:16px 20px;border-bottom:1px solid #f1f5f1;display:flex;align-items:center;justify-content:space-between">
              <div style="display:flex;align-items:center;gap:14px">
                <div style="width:40px;height:40px;background:var(--off);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:20px">📄</div>
                <div>
                  <div style="font-weight:700;font-size:14px;color:var(--dark)"><?php echo htmlspecialchars($doc['doc_type']); ?></div>
                  <div style="font-size:11.5px;color:var(--muted)"><?php echo date('M d, Y · h:i A', strtotime($doc['created_at'])); ?></div>
                </div>
              </div>
              <div>
                <?php 
                  $bCls = $doc['status'] === 'approved' ? 'b-green' : ($doc['status'] === 'pending' ? 'b-yellow' : 'b-red');
                ?>
                <span class="badge <?php echo $bCls; ?>" style="text-transform:capitalize;padding:4px 12px;border-radius:6px"><?php echo $doc['status']; ?></span>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>

  </div>
</div>

<style>
.doc-type-card:hover { border-color: var(--g)!important; background: var(--gl); }
.doc-type-card.selected { border-color: var(--g)!important; background: var(--gl); box-shadow: 0 0 0 3px rgba(20,168,0,0.1); }
</style>

<script>
let vDocType = null;
let vFiles = { front: null, back: null };

function selectDocType(type, id) {
  vDocType = type;
  document.querySelectorAll('.doc-type-card').forEach(c => c.classList.remove('selected'));
  document.getElementById(id).classList.add('selected');
  
  const bar = document.getElementById('dtype-selected-bar');
  const text = document.getElementById('dtype-selected-text');
  bar.style.display = 'flex';
  const label = type === 'passport' ? 'Passport' : (type === 'national-id' ? 'National ID' : "Driver's Licence");
  text.textContent = label + ' selected';
  
  // Update step 2 labels
  document.getElementById('upload-doc-label').textContent = label;
  document.getElementById('front-label').textContent = type === 'passport' ? '📄 Passport Photo Page' : '📄 Front Side';
  document.getElementById('vback-section').style.display = type === 'passport' ? 'none' : 'block';
  
  // Scroll to bar
  bar.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function switchVStep(n) {
  if (n === 2 && !vDocType) { toast('Selection Required', 'Please choose an ID type first'); return; }
  if (n === 3) { buildVReview(); }
  
  [1,2,3].forEach(i => {
    const panel = document.getElementById('vpanel-' + i);
    const step  = document.getElementById('vstep-' + i);
    const ico   = document.getElementById('vstep-' + i + '-ico');
    if (!panel) return;
    panel.style.display = (i === n) ? 'block' : 'none';
    if (step) step.style.background = (i === n) ? 'var(--gl)' : 'white';
    if (ico) {
      if (i < n) {
        ico.style.background = 'var(--g)'; ico.style.color = 'white'; ico.textContent = '✓';
      } else if (i === n) {
        ico.style.background = 'var(--g)'; ico.style.color = 'white'; ico.textContent = String(i);
      } else {
        ico.style.background = 'var(--border)'; ico.style.color = 'var(--muted)'; ico.textContent = String(i);
      }
    }
  });
  window.scrollTo({ top: 0, behavior: 'smooth' });
}

function handleVDrop(e, side) {
  e.preventDefault();
  const zone = document.getElementById('vdrop-' + side);
  if (zone) { zone.style.borderColor = 'var(--border)'; zone.style.background = 'var(--off)'; }
  if (e.dataTransfer.files && e.dataTransfer.files[0]) handleVFileInput(e.dataTransfer.files, side);
}

function handleVFileInput(files, side) {
  if (!files || !files[0]) return;
  const f = files[0];
  const maxMB = 10;
  if (f.size > maxMB * 1024 * 1024) { toast('File Too Large', 'Maximum file size is 10 MB'); return; }
  vFiles[side] = f;

  const placeholder = document.getElementById('v' + side + '-placeholder');
  const preview = document.getElementById('v' + side + '-preview');
  if (placeholder) placeholder.style.display = 'none';
  if (preview) {
    preview.style.display = 'flex';
    const isImg = f.type.startsWith('image/');
    preview.innerHTML = isImg
      ? `<img src="${URL.createObjectURL(f)}" style="max-height:100px;max-width:100%;border-radius:8px;object-fit:contain;box-shadow:0 4px 12px rgba(0,0,0,.1)">`
      : `<div style="font-size:40px">📄</div>`;
    preview.innerHTML += `
      <div style="font-size:12px;font-weight:700;color:var(--dark);margin-top:6px;max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${f.name}</div>
      <button class="btn btn-w btn-sm" style="margin-top:6px;padding:4px 10px" onclick="event.stopPropagation();clearVFile('${side}')">✕ Remove</button>`;
  }
  checkVStep2Ready();
}

function clearVFile(side) {
  vFiles[side] = null;
  document.getElementById('v' + side + '-placeholder').style.display = 'block';
  const preview = document.getElementById('v' + side + '-preview');
  if (preview) { preview.style.display = 'none'; preview.innerHTML = ''; }
  document.getElementById('vinput-' + side).value = '';
  checkVStep2Ready();
}

function checkVStep2Ready() {
  const needBack = (vDocType !== 'passport');
  const ready = vFiles.front && (!needBack || vFiles.back);
  const btn = document.getElementById('vnext-2');
  if (btn) { btn.disabled = !ready; btn.style.opacity = ready ? '1' : '.45'; }
}

function validateAndGoStep3() {
  const needBack = (vDocType !== 'passport');
  if (!vFiles.front) { toast('Upload Required', 'Please upload the front of your document'); return; }
  if (needBack && !vFiles.back) { toast('Upload Required', 'Please upload the back of your document'); return; }
  switchVStep(3);
}

function buildVReview() {
  const labels = { passport:'Passport', 'national-id':'National ID', drivers:"Driver's Licence" };
  const needBack = vDocType !== 'passport';
  let html = `<div style="display:grid;grid-template-columns:${needBack?'1fr 1fr':'1fr'};gap:15px;margin-bottom:10px">`;
  ['front', needBack ? 'back' : null].filter(Boolean).forEach(side => {
    const f = vFiles[side];
    const isImg = f && f.type.startsWith('image/');
    html += `<div style="border:1px solid var(--border);border-radius:10px;overflow:hidden;background:white">
      <div style="background:var(--off);padding:8px 15px;font-size:12px;font-weight:700;border-bottom:1px solid var(--border)">${side==='front'? (vDocType==='passport'?'Photo Page':'Front Side') : 'Back Side'}</div>
      <div style="padding:15px;text-align:center">
        ${f ? (isImg ? `<img src="${URL.createObjectURL(f)}" style="max-height:100px;max-width:100%;border-radius:6px;object-fit:contain">` : `<div style="font-size:32px;margin:10px 0">📄</div>`) : '<div style="color:var(--muted);font-size:12px">No file</div>'}
        ${f ? `<div style="font-size:11px;color:var(--muted);margin-top:8px;max-width:180px;margin-left:auto;margin-right:auto;overflow:hidden;text-overflow:ellipsis">${f.name}</div>` : ''}
      </div>
    </div>`;
  });
  html += `</div>
  <div style="font-size:13px;font-weight:800;color:var(--g);text-align:center">Document Type: ${labels[vDocType]||vDocType}</div>`;
  const rc = document.getElementById('vreview-content');
  if (rc) rc.innerHTML = html;
}

function toggleVSubmit() {
  const consent = document.getElementById('vconsent');
  const btn = document.getElementById('vsubmit-btn');
  const ready = consent && consent.checked;
  if (btn) { btn.disabled = !ready; btn.style.opacity = ready ? '1' : '.5'; }
}

async function submitVerification() {
  const btn = document.getElementById('vsubmit-btn');
  const name = document.getElementById('vlegal-name').value;
  const nationality = document.getElementById('vnationality').value;
  const dob = document.getElementById('vdob').value;
  const docNum = document.getElementById('vdoc-number').value;

  if (!name.trim()) { toast('Required', 'Enter your full legal name'); return; }
  
  btn.disabled = true;
  btn.innerHTML = '🛡️ Processing...';

  const fd = new FormData();
  fd.append('doc_type', vDocType);
  fd.append('legal_name', name);
  fd.append('nationality', nationality);
  fd.append('dob', dob);
  fd.append('doc_number', docNum);
  fd.append('front', vFiles.front);
  if (vFiles.back) fd.append('back', vFiles.back);

  try {
    const res = await fetch('<?php echo baseUrl("actions/submit_verification.php"); ?>', {
      method: 'POST',
      body: fd
    });
    const data = await res.json();
    if (data.success) {
      document.getElementById('v-flow-container').innerHTML = document.getElementById('vpanel-done').innerHTML;
      toast('Submitted! 🎉', 'Verification under review');
      setTimeout(() => location.reload(), 3000);
    } else {
      toast('Error', data.error || 'Failed to submit');
      btn.disabled = false;
      btn.innerHTML = '🛡️ Submit for Verification';
    }
  } catch (e) {
    toast('Error', 'Connection failed');
    btn.disabled = false;
    btn.innerHTML = '🛡️ Submit for Verification';
  }
}
</script>
