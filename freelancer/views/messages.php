<!-- MESSAGES -->
<div class="page" id="page-messages">
  <div style="display:grid;grid-template-columns:300px 1fr;background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden;height:calc(100vh - 140px)">
    <!-- Sidebar -->
    <div style="border-right:1px solid var(--border);display:flex;flex-direction:column">
      <div style="padding:15px;border-bottom:1px solid var(--border)"><input type="text" placeholder="Search messages..." style="width:100%;padding:8px 12px;border:1px solid var(--border);border-radius:6px;font-size:13px"></div>
      <div style="flex:1;overflow-y:auto">
        <?php foreach($recentMessages as $m): ?>
          <div style="padding:15px;border-bottom:1px solid var(--border);cursor:pointer;background:<?php echo $m['id']==1?'var(--gl)':'transparent'; ?>" onclick="toast('Message','Loading chat...')">
            <div style="display:flex;justify-content:space-between;margin-bottom:4px">
              <div style="font-weight:700;font-size:13.5px"><?php echo htmlspecialchars($m['sender_name']); ?></div>
              <div style="font-size:11px;color:var(--muted)"><?php echo date('H:i', strtotime($m['created_at'])); ?></div>
            </div>
            <div style="font-size:12.5px;color:var(--muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?php echo htmlspecialchars($m['message_text']); ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
    <!-- Chat Area -->
    <div style="display:flex;flex-direction:column;background:var(--off)">
      <div style="padding:15px;background:white;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:12px">
        <div class="av" style="width:32px;height:32px">C</div>
        <div style="font-weight:700;font-size:14px">ClearPath Finance</div>
      </div>
      <div style="flex:1;padding:20px;display:flex;flex-direction:column;gap:15px;overflow-y:auto">
        <div style="align-self:flex-start;background:white;padding:10px 15px;border-radius:12px;max-width:70%;font-size:13.5px;box-shadow:0 1px 2px rgba(0,0,0,.05)">Hi there, we reviewed your proposal and would like to schedule a call.</div>
        <div style="align-self:flex-end;background:var(--g);color:white;padding:10px 15px;border-radius:12px;max-width:70%;font-size:13.5px">That sounds great! I'm available tomorrow at 10 AM.</div>
      </div>
      <div style="padding:15px;background:white;border-top:1px solid var(--border);display:flex;gap:10px">
        <input type="text" placeholder="Write a message..." style="flex:1;padding:10px;border:1px solid var(--border);border-radius:8px">
        <button class="btn btn-g">Send</button>
      </div>
    </div>
  </div>
</div>
