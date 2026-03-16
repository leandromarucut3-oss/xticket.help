import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

const listEl = document.getElementById('conversation-list');
const titleEl = document.getElementById('chat-title');
const statusEl = document.getElementById('chat-status');
const messagesEl = document.getElementById('chat-messages');
const typingEl = document.getElementById('chat-typing');
const emptyState = document.getElementById('empty-state');
const form = document.getElementById('chat-form');
const input = document.getElementById('chat-text');
const sendBtn = form.querySelector('button[disabled]') || form.querySelector('button[type="submit"]');
const attachButton = document.getElementById('chat-attach');
const fileInput = document.getElementById('chat-file');

let activeConversation = null;
let typingTimer = null;
let isTyping = false;
let channel = null;

const pusherKey = import.meta.env?.VITE_PUSHER_APP_KEY || (window.ECHO_CONFIG && window.ECHO_CONFIG.key) || '';
let pusherHost = import.meta.env?.VITE_PUSHER_HOST || (window.ECHO_CONFIG && window.ECHO_CONFIG.host) || '';
if (!pusherHost || pusherHost === '127.0.0.1' || pusherHost === 'localhost') {
  pusherHost = window.location.hostname;
}
const pusherPort = Number(import.meta.env?.VITE_PUSHER_PORT || (window.ECHO_CONFIG && window.ECHO_CONFIG.port) || 6001);
const pusherScheme = import.meta.env?.VITE_PUSHER_SCHEME || (window.ECHO_CONFIG && window.ECHO_CONFIG.scheme) || 'http';
const pusherCluster = import.meta.env?.VITE_PUSHER_APP_CLUSTER || (window.ECHO_CONFIG && window.ECHO_CONFIG.cluster) || 'mt1';

const ACTIVE_WINDOW_MS = 2 * 60 * 1000;
const POLL_INTERVAL_MS = 5000;

let echo = null;
if (pusherKey) {
  try {
    echo = new Echo({
      broadcaster: 'pusher',
      key: pusherKey,
      wsHost: pusherHost,
      wsPort: pusherPort,
      wssPort: pusherPort,
      forceTLS: pusherScheme === 'https',
      cluster: pusherCluster,
      disableStats: true,
      enabledTransports: ['ws', 'wss'],
    });
  } catch (error) {
    console.warn('Realtime admin updates disabled:', error);
    echo = null;
  }
}

function getConversationStatus(conversation) {
  const updatedAt = Date.parse(conversation.updated_at || conversation.updatedAt || '');
  if (!updatedAt) {
    return 'idle';
  }
  const isOnline = Date.now() - updatedAt < ACTIVE_WINDOW_MS;
  return isOnline ? 'online' : 'idle';
}

function renderConversationItem(conversation) {
  const status = getConversationStatus(conversation);
  const item = document.createElement('li');
  item.dataset.conversationId = conversation.id;
  const label = conversation.username && conversation.username.trim() !== '' ? conversation.username : conversation.id;
  item.innerHTML = `<span>${label}</span><span class="status status--${status}">${status}</span>`;
  item.addEventListener('click', () => selectConversation(conversation.id));
  return item;
}

async function loadConversations() {
  try {
    console.log('📥 Loading conversations...');
    const response = await fetch('/api/conversations');
    const data = await response.json();
    console.log(`✓ Loaded ${data.length} conversations:`, data);
    const currentActive = activeConversation;
    listEl.innerHTML = '';
    data.forEach((conversation) => {
      const item = renderConversationItem(conversation);
      if (currentActive && conversation.id === currentActive) {
        item.classList.add('active');
      }
      listEl.appendChild(item);
    });
  } catch (error) {
    console.error('✗ Error loading conversations:', error);
  }
}

function markConversationOnline(conversationId) {
  const existing = listEl.querySelector(`[data-conversation-id="${conversationId}"]`);
  if (existing) {
    const statusTag = existing.querySelector('.status');
    if (statusTag) {
      statusTag.textContent = 'online';
      statusTag.className = 'status status--online';
    }
    return;
  }
  listEl.prepend(renderConversationItem({
    id: conversationId,
    updated_at: new Date().toISOString(),
  }));
}

function addMessage(payload, role) {
  if (!messagesEl.contains(typingEl)) {
    messagesEl.appendChild(typingEl);
  }
  const msg = document.createElement('div');
  msg.className = `chat-message ${role}`;
  if (payload.messageType === 'file') {
    if (payload.fileMime && payload.fileMime.startsWith('image/')) {
      const img = document.createElement('img');
      img.src = payload.fileUrl;
      img.alt = payload.fileName || 'image';
      msg.appendChild(img);
    } else if (payload.fileMime && payload.fileMime.startsWith('video/')) {
      const video = document.createElement('video');
      video.src = payload.fileUrl;
      video.controls = true;
      msg.appendChild(video);
    }
    const link = document.createElement('a');
    link.href = payload.fileUrl;
    link.textContent = payload.fileName || 'Download file';
    link.target = '_blank';
    link.rel = 'noopener';
    link.style.display = 'block';
    link.style.marginTop = '6px';
    msg.appendChild(link);
  } else {
    msg.textContent = payload.text || '';
  }
  if (messagesEl.contains(typingEl)) {
    messagesEl.insertBefore(msg, typingEl);
  } else {
    messagesEl.appendChild(msg);
  }
  // Always scroll to bottom when new message arrives
  setTimeout(() => {
    messagesEl.scrollTop = messagesEl.scrollHeight;
  }, 0);
}

function setTyping(isVisible) {
  if (isVisible) {
    typingEl.style.display = 'flex';
    typingEl.setAttribute('aria-hidden', 'false');
    // Update status indicator
    if (statusEl) {
      statusEl.textContent = '✏️ typing...';
      statusEl.style.color = '#0b74de';
      statusEl.style.fontWeight = '600';
    }
    // Update conversation list status
    if (activeConversation) {
      const item = listEl.querySelector(`[data-conversation-id="${activeConversation}"]`);
      if (item) {
        const status = item.querySelector('.status');
        if (status) {
          status.textContent = '✏️ typing';
          status.className = 'status status--typing';
        }
      }
    }
    console.log('👤 User is typing...');
  } else {
    typingEl.style.display = 'none';
    typingEl.setAttribute('aria-hidden', 'true');
    // Restore status indicator
    if (statusEl) {
      statusEl.textContent = 'active';
      statusEl.style.color = '';
      statusEl.style.fontWeight = '';
    }
    // Update conversation list status
    if (activeConversation) {
      const item = listEl.querySelector(`[data-conversation-id="${activeConversation}"]`);
      if (item) {
        const status = item.querySelector('.status');
        if (status) {
          status.textContent = 'active';
          status.className = 'status status--online';
        }
      }
    }
    console.log('👤 User stopped typing');
  }
  // Scroll to bottom when typing indicator appears
  if (isVisible) {
    setTimeout(() => {
      messagesEl.scrollTop = messagesEl.scrollHeight;
    }, 0);
  }
}

let lastMessageId = 0;
let messagePollingTimer = null;

async function loadMessages() {
  if (!activeConversation) {
    return;
  }
  const response = await fetch(`/api/conversations/${activeConversation}/messages`);
  const messages = await response.json();
  const typingRow = typingEl;
  const emptyRow = emptyState;
  messagesEl.innerHTML = '';
  messages.forEach((message) => {
    addMessage({
      messageType: message.message_type,
      text: message.text,
      fileUrl: message.file_url,
      fileName: message.file_name,
      fileMime: message.file_mime,
    }, message.sender_role === 'admin' ? 'admin' : 'user');
    lastMessageId = Math.max(lastMessageId, message.id || 0);
  });
  console.log(`Loaded ${messages.length} messages. Last message ID: ${lastMessageId}`);
  if (!messages.length && emptyRow) {
    emptyRow.style.display = 'block';
    messagesEl.appendChild(emptyRow);
  } else if (emptyRow) {
    emptyRow.style.display = 'none';
  }
  if (typingRow) {
    messagesEl.appendChild(typingRow);
  }
  // Scroll to bottom after messages are rendered
  setTimeout(() => {
    messagesEl.scrollTop = messagesEl.scrollHeight;
  }, 0);
}

async function pollForNewMessages() {
  if (!activeConversation) {
    return;
  }
  try {
    const response = await fetch(`/api/conversations/${activeConversation}/messages`);
    const messages = await response.json();
    const newMessages = messages.filter((msg) => (msg.id || 0) > lastMessageId);
    newMessages.forEach((message) => {
      addMessage({
        messageType: message.message_type,
        text: message.text,
        fileUrl: message.file_url,
        fileName: message.file_name,
        fileMime: message.file_mime,
      }, message.sender_role === 'admin' ? 'admin' : 'user');
      lastMessageId = Math.max(lastMessageId, message.id || 0);
    });
    if (newMessages.length > 0) {
      console.log(`Polled ${newMessages.length} new messages for conversation ${activeConversation}`);
    }
  } catch (error) {
    console.error('Error polling for messages:', error);
  }
}

function selectConversation(conversationId) {
  activeConversation = conversationId;
  titleEl.textContent = `Conversation ${conversationId}`;
  statusEl.textContent = 'active';
  input.disabled = false;
  sendBtn.disabled = false;
  emptyState.style.display = 'none';
  Array.from(listEl.children).forEach((item) => {
    item.classList.toggle('active', item.dataset.conversationId === conversationId);
  });
  setTyping(false);
  lastMessageId = 0; // Reset message ID for this conversation

  if (channel) {
    channel.stopListening('.message.sent');
    channel.stopListening('.typing.updated');
  }
  if (echo) {
    channel = echo.channel(`conversation.${conversationId}`)
      .listen('message.sent', (event) => {
        if (event.senderRole === 'user') {
          addMessage({
            messageType: event.messageType,
            text: event.text,
            fileUrl: event.fileUrl,
            fileName: event.fileName,
            fileMime: event.fileMime,
          }, 'user');
          setTyping(false);
          console.log('User message received:', event);
        }
      })
      .listen('typing.updated', (event) => {
        if (event.senderRole === 'user') {
          setTyping(!!event.isTyping);
          console.log('User typing:', event.isTyping);
        }
      });
  }
  loadMessages();

  // Start polling for this conversation as backup
  if (messagePollingTimer) {
    clearInterval(messagePollingTimer);
  }
  messagePollingTimer = setInterval(pollForNewMessages, POLL_INTERVAL_MS);
}

async function sendTyping(isTypingValue) {
  if (!activeConversation || !echo) {
    return;
  }
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
  await fetch(`/api/conversations/${activeConversation}/typing`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': csrfToken,
    },
    body: JSON.stringify({ sender_role: 'admin', is_typing: isTypingValue }),
  });
}

async function sendMessage(payload) {
  if (!activeConversation) {
    return;
  }
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
  await fetch(`/api/conversations/${activeConversation}/messages`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': csrfToken,
    },
    body: JSON.stringify({
      sender_role: 'admin',
      message_type: payload.messageType,
      text: payload.text,
      file_url: payload.fileUrl,
      file_name: payload.fileName,
      file_mime: payload.fileMime,
    }),
  });
}

if (echo) {
  echo.channel('admin')
    .listen('message.sent', (event) => {
      console.log('✓ New message broadcast received on admin channel:', event);
      markConversationOnline(event.conversationId || event.conversation_id);
      console.log('Marked conversation online:', event.conversationId || event.conversation_id);

      // If the admin is viewing this conversation, add the message immediately
      if (activeConversation === (event.conversationId || event.conversation_id)) {
        addMessage({
          messageType: event.messageType,
          text: event.text,
          fileUrl: event.fileUrl,
          fileName: event.fileName,
          fileMime: event.fileMime,
        }, event.senderRole === 'admin' ? 'admin' : 'user');
        lastMessageId = Math.max(lastMessageId, event.id || 0);
      }
    })
    .listen('typing.updated', (event) => {
      console.log('Typing update on admin channel:', event);
      if (event.senderRole === 'user') {
        markConversationOnline(event.conversationId);
        // If viewing this conversation, update typing indicator
        if (activeConversation === event.conversationId && event.isTyping) {
          setTyping(true);
        }
      }
    });
} else {
  console.warn('Echo/Pusher not available, polling conversations instead');
  // Fallback: poll for new conversations
  setInterval(loadConversations, POLL_INTERVAL_MS);
}

// Load conversations immediately on page load
console.log('🚀 Initializing admin panel...');
loadConversations();
// Also set up polling as a backup
setInterval(loadConversations, POLL_INTERVAL_MS);

form.addEventListener('submit', async (event) => {
  event.preventDefault();
  const text = input.value.trim();
  if (!text) {
    return;
  }
  const payload = { messageType: 'text', text };
  // Don't add message locally - let the broadcast event handle it
  // This prevents duplication
  await sendMessage(payload);
  input.value = '';
  input.style.height = 'auto';
  if (isTyping) {
    isTyping = false;
    await sendTyping(false);
  }
});

input.addEventListener('input', async () => {
  // Auto-expand textarea
  input.style.height = 'auto';
  input.style.height = input.scrollHeight + 'px';

  if (!isTyping) {
    isTyping = true;
    await sendTyping(true);
  }
  if (typingTimer) {
    clearTimeout(typingTimer);
  }
  typingTimer = setTimeout(async () => {
    if (isTyping) {
      isTyping = false;
      await sendTyping(false);
    }
  }, 1200);
});

input.addEventListener('blur', async () => {
  if (isTyping) {
    isTyping = false;
    await sendTyping(false);
  }
});

// Attachment behaviour: handled by the admin UI popover (in blade) to allow saved replies or upload

fileInput.addEventListener('change', async () => {
  const file = fileInput.files?.[0];
  if (!file || !activeConversation) {
    return;
  }
  const formData = new FormData();
  formData.append('file', file);
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
  const response = await fetch('/api/uploads', {
    method: 'POST',
    headers: {
      'X-CSRF-TOKEN': csrfToken,
    },
    body: formData,
  });
  const data = await response.json();
  if (!data.fileUrl) {
    return;
  }
  const payload = {
    messageType: 'file',
    text: '',
    fileUrl: data.fileUrl,
    fileName: data.fileName,
    fileMime: data.fileMime,
  };
  addMessage(payload, 'admin');
  await sendMessage(payload);
  fileInput.value = '';
});

loadConversations();
setInterval(loadConversations, POLL_INTERVAL_MS);
