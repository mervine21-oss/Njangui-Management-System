// Main JavaScript for DigiTon (vanilla JS)
document.addEventListener('DOMContentLoaded', function(){
  // Placeholder for future dynamic interactions (wallet updates, AJAX calls)
  const joinBtn = document.getElementById('joinBtn');
  if (joinBtn) {
    joinBtn.addEventListener('click', function(e){
      const groupId = this.getAttribute('data-group-id');
      this.disabled = true;
      this.textContent = 'Joining...';
      fetch('api/join_group.php', {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ group_id: groupId })
      }).then(r => r.json()).then(data => {
        if (data.ok) {
          alert(data.message || 'Joined group');
          // update seats remaining if provided
          if (typeof data.seats_remaining !== 'undefined') {
            const text = document.querySelector('.mb-3 strong');
            // reload page for simplicity
            window.location.reload();
          }
        } else {
          alert(data.error || 'Could not join group');
          joinBtn.disabled = false;
          joinBtn.textContent = 'Join Group';
        }
      }).catch(err => {
        console.error(err);
        alert('Network error');
        joinBtn.disabled = false;
        joinBtn.textContent = 'Join Group';
      });
    });
  }

  // App background lock PIN
  const LOCK_STORAGE_KEY = 'digiton_lock_pin_hash';
  const LOCK_TIMEOUT_MS = 5 * 60 * 1000; // 5 minutes
  const lockOverlay = document.getElementById('lock-screen-overlay');
  const lockTitle = document.getElementById('lock-screen-title');
  const lockSubtitle = document.getElementById('lock-screen-subtitle');
  const lockInput = document.getElementById('lock-pin-input');
  const lockSubmit = document.getElementById('lock-submit-button');
  const lockError = document.getElementById('lock-pin-error');
  const keypadButtons = document.querySelectorAll('.lock-keypad-button');

  if (lockOverlay && lockInput && lockSubmit) {
    let inactivityTimer = null;

    const getStoredPinHash = () => localStorage.getItem(LOCK_STORAGE_KEY);

    const hexEncode = buffer => {
      return Array.from(new Uint8Array(buffer)).map(b => b.toString(16).padStart(2, '0')).join('');
    };

    const sha256 = async value => {
      const data = new TextEncoder().encode(value);
      const hash = await crypto.subtle.digest('SHA-256', data);
      return hexEncode(hash);
    };

    const isPinSet = () => Boolean(getStoredPinHash());

    const showLockScreen = reason => {
      if (lockOverlay.classList.contains('active')) return;
      const mode = isPinSet() ? 'verify' : 'setup';
      lockTitle.textContent = mode === 'verify' ? 'Enter your PIN' : 'Create a 4-digit PIN';
      lockSubtitle.textContent = mode === 'verify'
        ? 'App locked. Enter your PIN to continue.'
        : 'No PIN is set yet. Choose a 4-digit code to unlock the app.';
      lockError.textContent = '';
      lockInput.value = '';
      lockOverlay.classList.add('active');
      document.body.classList.add('locked');
    };

    const hideLockScreen = () => {
      lockOverlay.classList.remove('active');
      document.body.classList.remove('locked');
      resetInactivityTimer();
    };

    const resetInactivityTimer = () => {
      clearTimeout(inactivityTimer);
      if (!lockOverlay.classList.contains('active')) {
        inactivityTimer = setTimeout(() => showLockScreen('inactive'), LOCK_TIMEOUT_MS);
      }
    };

    const submitPin = async () => {
      const pin = lockInput.value.trim();
      if (!/^[0-9]{4}$/.test(pin)) {
        lockError.textContent = 'Please enter a 4-digit code.';
        return;
      }
      if (!isPinSet()) {
        const hash = await sha256(pin);
        localStorage.setItem(LOCK_STORAGE_KEY, hash);
        hideLockScreen();
        return;
      }
      const storedHash = getStoredPinHash();
      const enteredHash = await sha256(pin);
      if (enteredHash === storedHash) {
        hideLockScreen();
      } else {
        lockError.textContent = 'Incorrect PIN. Please try again.';
      }
    };

    keypadButtons.forEach(button => {
      button.addEventListener('click', () => {
        const key = button.dataset.key;
        if (key === 'back') {
          lockInput.value = lockInput.value.slice(0, -1);
        } else if (key === 'clear') {
          lockInput.value = '';
        } else if (/^[0-9]$/.test(key) && lockInput.value.length < 4) {
          lockInput.value += key;
        }
      });
    });

    lockSubmit.addEventListener('click', submitPin);
    lockInput.addEventListener('keydown', event => {
      if (event.key === 'Enter') {
        event.preventDefault();
        submitPin();
      }
    });

    ['mousemove', 'keydown', 'mousedown', 'touchstart', 'scroll'].forEach(eventName => {
      document.addEventListener(eventName, resetInactivityTimer);
    });
    document.addEventListener('visibilitychange', () => {
      if (document.hidden) {
        showLockScreen('background');
      }
    });

    resetInactivityTimer();
  }

  // Balance reveal toggle with PIN validation
  const BALANCE_PIN_KEY = LOCK_STORAGE_KEY;
  const maskValues = value => '••••••';
  const balanceButtons = document.querySelectorAll('.balance-toggle-btn');
  const balancePinModal = document.getElementById('balance-pin-modal');
  const balancePinInput = document.getElementById('balance-pin-input');
  const balancePinSubmit = document.getElementById('balance-pin-submit');
  const balancePinCancel = document.getElementById('balance-pin-cancel');
  const balancePinError = document.getElementById('balance-pin-error');
  let balanceRevealTarget = null;

  const showBalancePinModal = (button) => {
    balanceRevealTarget = button;
    balancePinError.textContent = '';
    balancePinInput.value = '';
    balancePinModal.classList.add('active');
    balancePinInput.focus();
  };

  const hideBalancePinModal = () => {
    balancePinModal.classList.remove('active');
    balanceRevealTarget = null;
  };

  const verifyPinHash = async (pin) => {
    const storedHash = localStorage.getItem(BALANCE_PIN_KEY);
    if (!storedHash) {
      balancePinError.textContent = 'No PIN is set yet. Unlock the app first.';
      return false;
    }
    const data = new TextEncoder().encode(pin.trim());
    const hashBuffer = await crypto.subtle.digest('SHA-256', data);
    const enteredHash = Array.from(new Uint8Array(hashBuffer)).map(b => b.toString(16).padStart(2, '0')).join('');
    return enteredHash === storedHash;
  };

  const revealBalances = async (button) => {
    if (!balanceRevealTarget) return;
    const container = balanceRevealTarget.closest('.card') || balanceRevealTarget.closest('.stat-card');
    if (!container) return;

    const localMaskables = container.querySelectorAll('.maskable-value');
    localMaskables.forEach(el => {
      el.textContent = el.dataset.value || '0.00';
    });

    balanceRevealTarget.innerHTML = '<i class="bi bi-eye-slash"></i>';
    balanceRevealTarget.disabled = true;

    setTimeout(() => {
      localMaskables.forEach(el => el.textContent = maskValues(el.dataset.value || '0.00'));
      if (balanceRevealTarget) {
        balanceRevealTarget.innerHTML = '<i class="bi bi-eye"></i>';
        balanceRevealTarget.disabled = false;
      }
    }, 30000);
    hideBalancePinModal();
  };

  balanceButtons.forEach(button => {
    button.addEventListener('click', () => showBalancePinModal(button));
  });

  if (balancePinSubmit) {
    balancePinSubmit.addEventListener('click', async () => {
      const pin = balancePinInput.value.trim();
      if (!/^[0-9]{4}$/.test(pin)) {
        balancePinError.textContent = 'Enter a valid 4-digit PIN.';
        return;
      }
      if (await verifyPinHash(pin)) {
        await revealBalances(balanceRevealTarget);
      } else {
        balancePinError.textContent = 'Incorrect PIN.';
      }
    });
  }

  if (balancePinCancel) {
    balancePinCancel.addEventListener('click', () => hideBalancePinModal());
  }

  if (balancePinInput) {
    balancePinInput.addEventListener('keydown', event => {
      if (event.key === 'Enter') {
        event.preventDefault();
        balancePinSubmit.click();
      }
    });
  }
});
