<?php
require_once '../config/database.php';
require_once '../includes/auth-check.php';

$groupId = $_GET['group_id'] ?? null;

if (!$groupId) {
  header('Location: groups.php');
  exit;
}

$groupId = (int)$groupId;

// Get group and verify membership
$groupStmt = $pdo->prepare(
  'SELECT tg.id, tg.name, gm.is_admin FROM tontine_groups tg
   JOIN group_members gm ON tg.id = gm.group_id
   WHERE tg.id = ? AND gm.user_id = ?'
);
$groupStmt->execute([$groupId, $_SESSION['user_id']]);
$group = $groupStmt->fetch();

if (!$group) {
  http_response_code(403);
  die('Access denied');
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($group['name']) ?> - Chat</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
  <link href="assets/css/custom.css?v=20260708-2" rel="stylesheet">
  <style>
    .chat-container { display: flex; flex-direction: column; height: calc(100vh - 220px); }
    .chat-messages { flex: 1; overflow-y: auto; padding: 1.5rem; background: #fafafa; }
    .chat-message { margin-bottom: 1.5rem; display: flex; gap: 0.75rem; align-items: flex-start; }
    .chat-message.own { justify-content: flex-end; }
    .chat-message-bubble { padding: 0.75rem 1rem; border-radius: 12px; max-width: 70%; }
    .chat-message.own .chat-message-bubble { background: linear-gradient(135deg, #A21CAF, #7C3AED); color: white; }
    .chat-message:not(.own) .chat-message-bubble { background: white; color: #111; border: 1px solid rgba(124,58,237,0.1); }
    .chat-message-time { font-size: 0.75rem; color: #999; margin-top: 0.25rem; }
    .chat-file { margin-top: 0.5rem; }
    .chat-file-preview { max-width: 300px; border-radius: 8px; margin-top: 0.5rem; }
    .chat-file-link { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.5rem 0.75rem; background: rgba(255,255,255,0.2); border-radius: 6px; text-decoration: none; color: inherit; }
    .chat-input-area { padding: 1.5rem; border-top: 1px solid rgba(124,58,237,0.1); background: white; }
    .chat-input-form { display: flex; gap: 0.75rem; align-items: flex-end; }
    .chat-input-form input { flex: 1; border-radius: 20px; padding: 0.75rem 1rem; border: 1px solid rgba(124,58,237,0.2); }
    .chat-input-form button { border-radius: 50%; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .file-input-wrapper { position: relative; overflow: hidden; }
    .file-input-wrapper input[type="file"] { position: absolute; left: -9999px; }
    .upload-progress { font-size: 0.85rem; color: var(--primary-purple); }
    .sender-name { font-size: 0.85rem; font-weight: 600; color: #666; margin-bottom: 0.25rem; }
    @media (max-width: 991.98px) { .chat-container { height: calc(100vh - 280px); } }
  </style>
</head>
<body>
<?php include '../includes/header.php'; ?>
<?php include '../includes/navbar.php'; ?>

<div class="app-main">
  <div class="app-content">
    <div class="d-flex align-items-center justify-content-between mb-3">
      <div>
        <h2 class="mb-0"><?= htmlspecialchars($group['name']) ?></h2>
        <p class="text-muted mb-0">Group Chat</p>
      </div>
    </div>

    <!-- Chat Container -->
    <div class="chat-container">
      <div class="chat-messages" id="chatMessages"></div>
      <div class="chat-input-area">
        <form class="chat-input-form" id="messageForm" enctype="multipart/form-data">
          <input type="text" id="messageInput" placeholder="Type a message..." maxlength="5000">
          <div class="file-input-wrapper">
            <button type="button" class="btn btn-outline-primary" id="fileBtn" title="Attach file">
              <i class="bi bi-paperclip"></i>
            </button>
            <input type="file" id="fileInput" accept="image/*,video/*,.pdf,.doc,.docx,.txt">
          </div>
          <button type="submit" class="btn btn-primary">
            <i class="bi bi-send"></i>
          </button>
        </form>
        <div id="uploadProgress" class="upload-progress mt-2" style="display: none;"></div>
      </div>
    </div>
  </div>
</div>

<?php include '../includes/footer.php'; ?>

<script>
const groupId = <?= $groupId ?>;
const userId = <?= $_SESSION['user_id'] ?>;

// Load messages on page load
loadMessages();

// File button click
document.getElementById('fileBtn').addEventListener('click', () => {
  document.getElementById('fileInput').click();
});

// Message form submit
document.getElementById('messageForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  const input = document.getElementById('messageInput');
  const fileInput = document.getElementById('fileInput');
  const message = input.value.trim();
  
  const formData = new FormData();
  formData.append('group_id', groupId);
  formData.append('message', message);
  
  if (fileInput.files.length > 0) {
    const file = fileInput.files[0];
    if (file.size > 50 * 1024 * 1024) {
      alert('File too large (max 50MB)');
      return;
    }
    formData.append('file', file);
    showUploadProgress('Uploading...');
  }
  
  if (!message && fileInput.files.length === 0) return;
  
  try {
    const endpoint = fileInput.files.length > 0 ? 'api/upload_file.php' : 'api/send_message.php';
    console.log('Sending to:', endpoint);
    console.log('FormData:', Object.fromEntries(formData));
    
    const res = await fetch(endpoint, {
      method: 'POST',
      body: formData
    });
    
    console.log('Response status:', res.status);
    const data = await res.json();
    console.log('Response data:', data);
    
    if (res.ok && data.success) {
      input.value = '';
      fileInput.value = '';
      showUploadProgress('');
      console.log('Message sent, loading messages...');
      setTimeout(() => loadMessages(), 500);
    } else {
      alert('Error: ' + (data.error || 'Unknown error'));
    }
  } catch (err) {
    console.error('Error:', err);
    alert('Error sending message: ' + err.message);
  }
});

function showUploadProgress(msg) {
  const prog = document.getElementById('uploadProgress');
  if (msg) {
    prog.textContent = msg;
    prog.style.display = 'block';
  } else {
    prog.style.display = 'none';
  }
}

async function loadMessages() {
  try {
    const url = `api/get_messages.php?group_id=${groupId}`;
    console.log('Fetching messages from:', url);
    const res = await fetch(url);
    const data = await res.json();
    console.log('Response:', data);
    
    const container = document.getElementById('chatMessages');
    container.innerHTML = '';
    
    if (!data.messages || data.messages.length === 0) {
      container.innerHTML = '<p class="text-center text-muted">No messages yet. Start chatting!</p>';
      console.log('No messages found');
      return;
    }
    
    console.log('Loading', data.messages.length, 'messages');
    data.messages.forEach(msg => {
      const time = new Date(msg.created_at).toLocaleTimeString();
      const isOwn = msg.user_id == userId;
      const div = document.createElement('div');
      div.className = `chat-message ${isOwn ? 'own' : ''}`;
      
      let fileHtml = '';
      if (msg.file_path) {
        const filename = msg.file_path.split('/').pop();
        const fileUrl = `api/serve_file.php?group_id=${groupId}&file=${filename}`;
        
        if (msg.file_type === 'image') {
          fileHtml = `<img src="${fileUrl}" alt="image" class="chat-file-preview" style="max-width: 300px; border-radius: 8px;">`;
        } else if (msg.file_type === 'video') {
          fileHtml = `<video class="chat-file-preview" controls style="max-width: 300px; border-radius: 8px;">
            <source src="${fileUrl}">
            Your browser doesn't support video playback.
          </video>`;
        } else {
          fileHtml = `<a href="${fileUrl}" target="_blank" class="chat-file-link">
            <i class="bi bi-file-earmark"></i>
            ${escapeHtml(msg.file_name)}
          </a>`;
        }
      }
      
      div.innerHTML = `
        <div style="width: 100%;">
          <div class="sender-name">${escapeHtml(msg.sender_name)}</div>
          <div class="chat-message-bubble">
            ${msg.message ? escapeHtml(msg.message) : ''}
            ${fileHtml}
          </div>
          <div class="chat-message-time">${time}</div>
        </div>
      `;
      container.appendChild(div);
    });
    
    container.scrollTop = container.scrollHeight;
  } catch (err) {
    console.error('Error loading messages:', err);
  }
}

function escapeHtml(text) {
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}

// Auto-refresh messages every 3 seconds
setInterval(loadMessages, 3000);
</script>
</body>
</html>
