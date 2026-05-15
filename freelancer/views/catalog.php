<!-- CATALOG (SERVICES) -->
<div class="page" id="page-catalog">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px">
    <div style="font-size:20px;font-weight:700">My Project Catalog</div>
    <button class="btn btn-g" onclick="openModal('create-service')">+ Create Project</button>
  </div>
  
  <div class="g2" style="grid-template-columns:repeat(auto-fill, minmax(280px, 1fr))">
    <?php if(empty($myServices)): ?>
      <div class="card" style="grid-column:1/-1;text-align:center;padding:50px">
        <div style="font-size:48px;margin-bottom:15px">📦</div>
        <div style="font-size:16px;font-weight:700;margin-bottom:8px">Create your first project</div>
        <div style="font-size:14px;color:var(--muted);max-width:400px;margin:0 auto 20px">Project Catalog™ lets you sell your services in a way that's easy for clients to buy immediately.</div>
        <button class="btn btn-g" onclick="openModal('create-service')">Get Started</button>
      </div>
    <?php else: ?>
      <?php foreach($myServices as $s): ?>
        <div class="card" style="padding:0;overflow:hidden">
          <div style="height:140px;background:var(--gl);display:flex;align-items:center;justify-content:center;font-size:40px">🛠️</div>
          <div style="padding:15px">
            <div style="font-weight:700;font-size:14px;margin-bottom:6px"><?php echo htmlspecialchars($s['title']); ?></div>
            <div style="font-size:12px;color:var(--muted);margin-bottom:12px;height:34px;overflow:hidden"><?php echo htmlspecialchars($s['description']); ?></div>
            <div style="display:flex;justify-content:space-between;align-items:center">
              <div style="font-size:13px;font-weight:700">From $<?php echo number_format($s['price']); ?></div>
              <button class="btn btn-w btn-sm" onclick="toast('Edit','Opening editor...')">Edit</button>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>
