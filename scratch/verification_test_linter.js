
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
    const res = await fetch('1', {
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
