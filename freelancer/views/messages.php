<!-- MESSAGES -->
<div class="page" id="page-messages">
  <div class="msg-container" style="background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden;height:calc(100vh - 140px)">
    <style>
      .msg-container{display:grid;grid-template-columns:300px 1fr}
      @media(max-width:900px){
        .msg-container{grid-template-columns:1fr}
        .msg-container.chat-open .msg-sidebar{display:none}
        .msg-container:not(.chat-open) .msg-window{display:none}
      }
    </style>
    <!-- Sidebar -->
    <div class="msg-sidebar" style="border-right:1px solid var(--border);display:flex;flex-direction:column">
      <div style="padding:15px;border-bottom:1px solid var(--border)">
        <input type="text" placeholder="Search messages..." style="width:100%;padding:8px 12px;border:1px solid var(--border);border-radius:6px;font-size:13px" onkeyup="filterConversations(this.value)">
      </div>
      <div style="flex:1;overflow-y:auto" id="conversations-list">
        <?php if(empty($conversations)): ?>
          <div style="padding:20px;text-align:center;color:var(--muted);font-size:13px">No conversations yet.</div>
        <?php else: ?>
          <?php foreach($conversations as $c): 
            $initials = strtoupper(substr($c['other_name'], 0, 1) . substr(explode(' ', $c['other_name'])[1] ?? '', 0, 1));
            $isUnread = ($c['is_read'] == 0 && $c['sender_id'] != $user['id']);
            $time = date('H:i', strtotime($c['last_time']));
          ?>
            <div class="msg-item <?php echo $isUnread ? 'unread' : ''; ?>" style="padding:15px;border-bottom:1px solid var(--border);cursor:pointer;" onclick="loadChat(<?php echo $c['other_id']; ?>, '<?php echo addslashes($c['other_name']); ?>', '<?php echo $initials; ?>', this)">
              <div style="display:flex;justify-content:space-between;margin-bottom:4px;align-items:center">
                <div style="font-weight:700;font-size:13.5px;display:flex;align-items:center;gap:8px">
                  <?php echo htmlspecialchars($c['other_name']); ?>
                  <?php if($isUnread): ?><span style="width:8px;height:8px;background:var(--g);border-radius:50%"></span><?php endif; ?>
                </div>
                <div style="font-size:11px;color:var(--muted)"><?php echo $time; ?></div>
              </div>
              <div style="font-size:12.5px;color:var(--muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?php echo htmlspecialchars($c['last_message']); ?></div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
    <!-- Chat Area -->
    <div class="msg-window" style="display:flex;flex-direction:column;background:var(--off)" id="chat-window">
      <div style="flex:1;display:flex;align-items:center;justify-content:center;color:var(--muted);flex-direction:column;gap:15px">
        <span style="font-size:40px">💬</span>
        <div>Select a conversation to start chatting</div>
      </div>
    </div>
  </div>
</div>
