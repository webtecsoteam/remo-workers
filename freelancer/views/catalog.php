<!-- CATALOG (SERVICES) -->
<div class="page" id="page-catalog">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:25px">
    <div>
      <div style="font-size:24px;font-weight:700;margin-bottom:4px">Project Catalog</div>
      <div style="font-size:13px;color:var(--muted)">Manage your pre-packaged services</div>
    </div>
    <div style="display:flex;gap:12px;align-items:center">
      <div style="font-size:13px;color:var(--muted);font-weight:600"><?php echo count($myServices); ?> Projects</div>
      <button class="btn btn-g" style="padding:10px 20px;border-radius:8px" onclick="openModal('create-service')">+ Create Project</button>
    </div>
  </div>
  
  <div style="display:grid;grid-template-columns:repeat(auto-fill, minmax(300px, 1fr));gap:25px">
    <?php if(empty($myServices)): ?>
      <div class="card" style="grid-column:1/-1;text-align:center;padding:80px 20px;border-radius:16px;border:2px dashed #ddd;background:transparent">
        <div style="font-size:54px;margin-bottom:20px">📦</div>
        <div style="font-size:18px;font-weight:700;margin-bottom:10px;color:var(--dark)">Showcase your expertise</div>
        <div style="font-size:14px;color:var(--muted);max-width:450px;margin:0 auto 25px;line-height:1.6">Project Catalog™ lets you sell your services in a way that's easy for clients to buy immediately. Create a project and start earning.</div>
        <button class="btn btn-g" style="padding:12px 30px;font-size:15px;font-weight:700" onclick="openModal('create-service')">Get Started</button>
      </div>
    <?php else: ?>
      <?php foreach($myServices as $s): ?>
        <div class="card" style="padding:0;overflow:hidden;border-radius:12px;transition:transform 0.2s;cursor:pointer" onmouseover="this.style.transform='translateY(-5px)'" onmouseout="this.style.transform='none'">
          <div style="height:170px;background:#f3f4f6 url('<?php echo $s['image_url'] ?: 'https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=500&auto=format&fit=crop&q=60'; ?>') center/cover;position:relative">
            <div style="position:absolute;bottom:10px;right:10px;background:rgba(255,255,255,0.95);padding:4px 10px;border-radius:6px;font-size:11px;font-weight:700;color:var(--dark)">
              <?php echo $s['delivery_days']; ?> days delivery
            </div>
          </div>
          <div style="padding:20px">
            <div style="font-weight:700;font-size:15px;margin-bottom:8px;color:var(--dark);line-height:1.4;height:42px;overflow:hidden"><?php echo htmlspecialchars($s['title']); ?></div>
            <div style="font-size:12.5px;color:var(--muted);margin-bottom:15px;height:36px;overflow:hidden;line-height:1.5"><?php echo htmlspecialchars($s['description']); ?></div>
            <div style="display:flex;justify-content:space-between;align-items:center;border-top:1px solid #f1f5f9;padding-top:15px">
              <div>
                <span style="font-size:11px;color:var(--muted);display:block;text-transform:uppercase;letter-spacing:0.5px">Starting at</span>
                <span style="font-size:16px;font-weight:800;color:var(--dark)">$<?php echo number_format($s['price']); ?></span>
              </div>
              <button class="btn" style="padding:6px 15px;font-size:12px;border:1px solid #ddd;background:white;color:var(--dark);font-weight:600;border-radius:15px" onclick="toast('Edit','Opening editor...')">Edit Project</button>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>
