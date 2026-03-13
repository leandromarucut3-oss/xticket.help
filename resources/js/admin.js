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

const pusherKey = import.meta.env?.VITE_PUSHER_APP_KEY || '';
const pusherHost = import.meta.env?.VITE_PUSHER_HOST || window.location.hostname;
const pusherPort = Number(import.meta.env?.VITE_PUSHER_PORT || 6001);
const pusherScheme = import.meta.env?.VITE_PUSHER_SCHEME || 'http';
const pusherCluster = import.meta.env?.VITE_PUSHER_APP_CLUSTER || 'mt1';

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
  item.innerHTML = `<span>${conversation.id}</span><span class="status status--${status}">${status}</span>`;
  item.addEventListener('click', () => selectConversation(conversation.id));
  return item;
}

async function loadConversations() {
  const response = await fetch('/api/conversations');
  const data = await response.json();
  const currentActive = activeConversation;
  listEl.innerHTML = '';
  data.forEach((conversation) => {
    const item = renderConversationItem(conversation);
    if (currentActive && conversation.id === currentActive) {
      item.classList.add('active');
    }
    listEl.appendChild(item);
  });
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
  messagesEl.scrollTop = messagesEl.scrollHeight;
}

function setTyping(isVisible) {
  typingEl.style.display = isVisible ? 'flex' : 'none';
  typingEl.setAttribute('aria-hidden', isVisible ? 'false' : 'true');
}

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
  });
  if (!messages.length && emptyRow) {
    emptyRow.style.display = 'block';
    messagesEl.appendChild(emptyRow);
  } else if (emptyRow) {
    emptyRow.style.display = 'none';
  }
  if (typingRow) {
    messagesEl.appendChild(typingRow);
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
}

async function sendTyping(isTypingValue) {
  if (!activeConversation || !echo) {
    return;
  }
  await fetch(`/api/conversations/${activeConversation}/typing`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ sender_role: 'admin', is_typing: isTypingValue }),
  });
}

async function sendMessage(payload) {
  if (!activeConversation) {
    return;
  }
  await fetch(`/api/conversations/${activeConversation}/messages`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
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
      markConversationOnline(event.conversationId);
      console.log('New conversation:', event.conversationId);
    })
    .listen('typing.updated', (event) => {
      if (event.senderRole === 'user') {
        markConversationOnline(event.conversationId);
      }
    });
}

form.addEventListener('submit', async (event) => {
  event.preventDefault();
  const text = input.value.trim();
  if (!text) {
    return;
  }
  const payload = { messageType: 'text', text };
  addMessage(payload, 'admin');
  await sendMessage(payload);
  input.value = '';
  if (isTyping) {
    isTyping = false;
    await sendTyping(false);
  }
});

input.addEventListener('input', async () => {
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

attachButton.addEventListener('click', () => {
  fileInput.click();
});

fileInput.addEventListener('change', async () => {
  const file = fileInput.files?.[0];
  if (!file || !activeConversation) {
    return;
  }
  const formData = new FormData();
  formData.append('file', file);
  const response = await fetch('/api/uploads', {
    method: 'POST',
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
