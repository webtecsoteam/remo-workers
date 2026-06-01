<!-- MESSAGES -->
<div class="page" id="page-messages">
  <div class="msg-container" id="msg-container">
    <!-- Sidebar -->
    <div class="msg-sidebar">
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
            <div class="msg-item <?php echo $isUnread ? 'unread' : ''; ?>" style="border-radius:0;margin:0;padding:12px 14px;display:flex;gap:12px;align-items:center;border-bottom:1px solid var(--border);cursor:pointer" onclick="loadChat(<?php echo $c['other_id']; ?>, '<?php echo addslashes($c['other_name']); ?>', '<?php echo $initials; ?>', this, '<?php echo $c['other_avatar'] ?? ''; ?>')">
              <div class="av" style="width:40px;height:40px;position:relative;flex-shrink:0;display:flex;align-items:center;justify-content:center;border-radius:50%">
                <?php if (!empty($c['other_avatar'])): ?>
                  <img src="<?php echo baseUrl($c['other_avatar']); ?>" style="width:100%;height:100%;border-radius:50%;object-fit:cover" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                  <div style="display:none;background:var(--gl);color:var(--forest);width:100%;height:100%;align-items:center;justify-content:center;border-radius:50%;font-weight:bold;font-size:13px"><?php echo $initials; ?></div>
                <?php else: ?>
                  <div style="background:var(--gl);color:var(--forest);width:100%;height:100%;display:flex;align-items:center;justify-content:center;border-radius:50%;font-weight:bold;font-size:13px"><?php echo $initials; ?></div>
                <?php endif; ?>
              </div>
              <div class="msg-meta" style="flex:1;min-width:0">
                <div class="msg-name" style="display:flex;justify-content:space-between;font-weight:700;font-size:13.5px;color:var(--dark)"><?php echo htmlspecialchars($c['other_name']); ?><span class="msg-time" style="font-size:11px;color:var(--muted);font-weight:400"><?php echo $time; ?></span></div>
                <div class="msg-text" style="font-size:12.5px;color:var(--muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-top:2px"><?php echo htmlspecialchars($c['last_message']); ?></div>
              </div>
              <?php if($isUnread): ?><div class="msg-dot" style="width:8px;height:8px;background:var(--g);border-radius:50%;flex-shrink:0"></div><?php endif; ?>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
    <!-- Chat Area -->
    <div class="msg-window" id="chat-window">
      <div style="flex:1;display:flex;align-items:center;justify-content:center;color:var(--muted);flex-direction:column;gap:15px">
        <span style="font-size:40px">💬</span>
        <div>Select a conversation to start chatting</div>
      </div>
    </div>
  </div>
</div>
