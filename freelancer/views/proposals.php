<!-- PROPOSALS -->
<div class="page" id="page-proposals">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
    <div style="font-size:22px;font-weight:700">My Proposals</div>
    <div style="font-size:13px;color:var(--muted)">Total: <?php echo count($submittedProposals); ?></div>
  </div>

  <div class="tab-bar">
    <div class="tab on" onclick="setTab(this)">Active</div>
    <div class="tab" onclick="setTab(this)">Submitted</div>
    <div class="tab" onclick="setTab(this)">Archived</div>
  </div>

  <div class="card" style="border-radius:12px">
    <div class="card-head" style="background:#f9fafb;padding:15px 22px">
      <h3 style="font-size:15px" id="proposals-list-title">Active Proposals</h3>
    </div>
    <div class="card-body" id="proposals-list" style="padding:0">
      <!-- Proposals will be rendered here by JS -->
      <div style="padding:40px;text-align:center;color:var(--muted)">Loading proposals...</div>
    </div>
  </div>
</div>
