<!-- CATALOG (SERVICES) -->
<div class="page" id="page-catalog">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px">
    <div style="font-size:20px;font-weight:700">My Services</div>
    <button class="btn btn-g btn-lg" onclick="openModal('create-service')">+ Add a Service</button>
  </div>
  
  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:16px">
    <?php foreach($myServices as $s): ?>
      <div style="background:white;border:1px solid var(--border);border-radius:10px;overflow:hidden;transition:all .2s;position:relative" class="service-card" onmouseover="this.style.borderColor='var(--g)'" onmouseout="this.style.borderColor='var(--border)'">
        <!-- Action Buttons -->
        <div style="position:absolute;top:10px;right:10px;display:flex;gap:5px;z-index:5">
          <button class="btn btn-sm" style="padding:4px 8px;background:rgba(255,255,255,0.9);border:1px solid var(--border);border-radius:6px" title="Edit" onclick="editService(<?php echo htmlspecialchars(json_encode($s)); ?>)">✏️</button>
          <button class="btn btn-sm" style="padding:4px 8px;background:rgba(255,255,255,0.9);border:1px solid #fee2e2;color:#ef4444;border-radius:6px" title="Delete" onclick="deleteService(<?php echo $s['id']; ?>)">🗑️</button>
        </div>

        <div style="height:100px;background:var(--gl);display:flex;align-items:center;justify-content:center;font-size:44px">
          <?php if($s['image_url']): ?>
            <img src="<?php echo htmlspecialchars($s['image_url']); ?>" style="width:100%;height:100%;object-fit:cover">
          <?php else: ?>
            📦
          <?php endif; ?>
        </div>
        <div style="padding:14px 16px">
          <div style="font-size:11px;font-weight:700;text-transform:uppercase;color:var(--g);margin-bottom:5px">Project</div>
          <div style="font-size:14px;font-weight:700;margin-bottom:8px;height:40px;overflow:hidden;line-height:1.4"><?php echo htmlspecialchars($s['title']); ?></div>
          <div style="font-size:12px;color:var(--muted);margin-bottom:10px;height:36px;overflow:hidden;line-height:1.5"><?php echo htmlspecialchars($s['description']); ?></div>
          <div style="display:flex;justify-content:space-between;border-top:1px solid var(--border);padding-top:10px;align-items:center">
            <div>
              <span style="font-size:11px;color:var(--muted);display:block">Starts at</span>
              <span style="font-size:15px;font-weight:700">$<?php echo number_format($s['price']); ?></span>
            </div>
            <span style="font-size:11px;color:var(--muted);background:var(--off);padding:2px 8px;border-radius:4px"><?php echo $s['delivery_days']; ?>d delivery</span>
          </div>
        </div>
      </div>
    <?php endforeach; ?>

    <!-- Add New Placeholder -->
    <div style="background:white;border:2px dashed var(--border);border-radius:10px;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:28px;cursor:pointer;transition:border-color .2s;min-height:200px" onclick="openModal('create-service')" onmouseover="this.style.borderColor='var(--g)'" onmouseout="this.style.borderColor='var(--border)'">
      <div style="font-size:36px;margin-bottom:10px">➕</div>
      <div style="font-size:14px;font-weight:700;margin-bottom:4px">Add a Service</div>
      <div style="font-size:12.5px;color:var(--muted);text-align:center">Create a package clients can buy instantly</div>
    </div>
  </div>
</div>
