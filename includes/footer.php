  <?php if (!defined('NO_SIDEBAR')): ?>
  </div>
</main>
  <?php endif; ?>

  <div id="lock-screen-overlay" class="lock-screen-overlay">
    <div class="lock-screen-card">
      <h3 id="lock-screen-title">Enter your PIN</h3>
      <p id="lock-screen-subtitle">App locked. Enter the 4-digit PIN to continue.</p>
      <div class="form-group">
        <input id="lock-pin-input" type="password" class="form-control lock-pin-input" maxlength="4" inputmode="numeric" pattern="[0-9]*" autocomplete="one-time-code" readonly>
      </div>
      <div id="lock-pin-error" class="text-danger small mb-3"></div>
      <div class="lock-keypad-row">
        <button type="button" class="btn btn-outline-secondary lock-keypad-button" data-key="1">1</button>
        <button type="button" class="btn btn-outline-secondary lock-keypad-button" data-key="2">2</button>
        <button type="button" class="btn btn-outline-secondary lock-keypad-button" data-key="3">3</button>
      </div>
      <div class="lock-keypad-row">
        <button type="button" class="btn btn-outline-secondary lock-keypad-button" data-key="4">4</button>
        <button type="button" class="btn btn-outline-secondary lock-keypad-button" data-key="5">5</button>
        <button type="button" class="btn btn-outline-secondary lock-keypad-button" data-key="6">6</button>
      </div>
      <div class="lock-keypad-row">
        <button type="button" class="btn btn-outline-secondary lock-keypad-button" data-key="7">7</button>
        <button type="button" class="btn btn-outline-secondary lock-keypad-button" data-key="8">8</button>
        <button type="button" class="btn btn-outline-secondary lock-keypad-button" data-key="9">9</button>
      </div>
      <div class="lock-keypad-row">
        <button type="button" class="btn btn-outline-secondary lock-keypad-button" data-key="clear">C</button>
        <button type="button" class="btn btn-outline-secondary lock-keypad-button" data-key="0">0</button>
        <button type="button" class="btn btn-outline-secondary lock-keypad-button" data-key="back">←</button>
      </div>
      <button id="lock-submit-button" type="button" class="btn btn-cta mt-3">Unlock</button>
    </div>
  </div>

  <div id="balance-pin-modal" class="lock-screen-overlay">
    <div class="lock-screen-card">
      <h3>Reveal Balances</h3>
      <p>Enter your 4-digit PIN to reveal this balance.</p>
      <div class="form-group">
        <input id="balance-pin-input" type="password" class="form-control lock-pin-input" maxlength="4" inputmode="numeric" pattern="[0-9]*" autocomplete="one-time-code">
      </div>
      <div id="balance-pin-error" class="text-danger small mb-3"></div>
      <div class="d-flex gap-2">
        <button id="balance-pin-submit" type="button" class="btn btn-cta flex-fill">Submit</button>
        <button id="balance-pin-cancel" type="button" class="btn btn-secondary flex-fill">Cancel</button>
      </div>
    </div>
  </div>

  <style>
  .lock-screen-overlay { display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.85); z-index: 2000; align-items: center; justify-content: center; padding: 1.5rem; }
  .lock-screen-overlay.active { display: flex; }
  .lock-screen-card { width: 100%; max-width: 420px; background: #ffffff; border-radius: 24px; box-shadow: 0 32px 80px rgba(15, 23, 42, 0.2); padding: 2rem; text-align: center; }
  .lock-screen-card h3 { margin-bottom: 0.5rem; font-size: 1.75rem; }
  .lock-screen-card p { color: #6b7280; margin-bottom: 1.5rem; }
  .lock-pin-input { width: 100%; text-align: center; font-size: 1.5rem; letter-spacing: 0.5rem; padding: 1rem; border-radius: 16px; border: 2px solid #e5e7eb; background: #f9fafb; }
  .lock-keypad-row { display: flex; gap: 0.75rem; margin-bottom: 0.75rem; }
  .lock-keypad-button { flex: 1; font-size: 1.2rem; padding: 1rem; border-radius: 14px; }
  .locked body { filter: blur(3px); }
  </style>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="assets/js/main.js"></script>
</body>
</html>
