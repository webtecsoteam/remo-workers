<?php
/**
 * Auth / marketing modal shell (rendered at end of body so Bootstrap cannot hide it).
 */
?>
<div class="rw-overlay" id="overlay" onclick="closeModal(event)">
  <div class="rw-modal" id="modal">
    <div class="modal-head">
      <h2 id="modal-title">Details</h2>
      <button type="button" class="modal-close" onclick="closeModal()" aria-label="Close">✕</button>
    </div>
    <div class="modal-body" id="modal-body"></div>
  </div>
</div>
