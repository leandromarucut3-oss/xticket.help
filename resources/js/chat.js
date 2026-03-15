import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

let chatMessages;
let chatForm;
let chatInput;
let chatTyping;
let attachButton;
let fileInput;

const sessionKey = 'chat_conversation_id';
let conversationId = localStorage.getItem(sessionKey);
let typingTimer = null;
let isTyping = false;
let pollTimer = null;

const POLL_INTERVAL_MS = 5000;

function initializeDOMElements() {
  chatMessages = document.getElementById('chat-messages');
  chatForm = document.getElementById('chat-form');
  chatInput = document.getElementById('chat-text');
  chatTyping = document.getElementById('chat-typing');
  attachButton = document.getElementById('chat-attach');
  fileInput = document.getElementById('chat-file');

  if (!chatForm || !chatMessages) {
    console.error('Chat form or messages element not found');
    return false;
  }
  return true;
}

function setupEventListeners() {
  if (!chatForm) return;

  chatForm.addEventListener('submit', async (event) => {
    event.preventDefault();
    const text = chatInput.value.trim();
    if (!text) {
      return;
    }
    const payload = { messageType: 'text', text };
    appendMessage(payload, 'user');
    await sendMessage(payload);
    chatInput.value = '';
    if (isTyping) {
      isTyping = false;
      await sendTyping(false);
    }
  });

  chatInput.addEventListener('input', async () => {
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

  chatInput.addEventListener('blur', async () => {
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
    if (!file) {
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
    appendMessage(payload, 'user');
    await sendMessage(payload);
    fileInput.value = '';
  });
}

const pusherKey = import.meta.env?.VITE_PUSHER_APP_KEY || (window.ECHO_CONFIG && window.ECHO_CONFIG.key) || '';

let echo = null;
// Use global Echo instance from bootstrap.js if available
if (window.Echo) {
  echo = window.Echo;
} else if (pusherKey) {
  // Fallback: create Echo instance if not available globally
  try {
    const Echo = await import('laravel-echo');
    const Pusher = await import('pusher-js');
    window.Pusher = Pusher;
    const pusherHost = import.meta.env?.VITE_PUSHER_HOST || (window.ECHO_CONFIG && window.ECHO_CONFIG.host) || window.location.hostname;
    const pusherPort = Number(import.meta.env?.VITE_PUSHER_PORT || (window.ECHO_CONFIG && window.ECHO_CONFIG.port) || 6001);
    const pusherScheme = import.meta.env?.VITE_PUSHER_SCHEME || (window.ECHO_CONFIG && window.ECHO_CONFIG.scheme) || 'http';
    const pusherCluster = import.meta.env?.VITE_PUSHER_APP_CLUSTER || (window.ECHO_CONFIG && window.ECHO_CONFIG.cluster) || 'mt1';
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
    console.warn('Realtime chat disabled:', error);
    echo = null;
  }
}

function appendMessage(payload, role) {
  const msg = document.createElement('div');
  msg.className = `chat-message ${role}`;
  if (!chatMessages.contains(chatTyping)) {
    chatMessages.appendChild(chatTyping);
  }
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
  if (chatMessages.contains(chatTyping)) {
    chatMessages.insertBefore(msg, chatTyping);
  } else {
    chatMessages.appendChild(msg);
  }
  chatMessages.scrollTop = chatMessages.scrollHeight;
  notifyParentSize();
}

async function createConversation() {
  const response = await fetch('/api/conversations', { method: 'POST' });
  const data = await response.json();
  conversationId = data.conversationId;
  localStorage.setItem(sessionKey, conversationId);
}

async function loadHistory() {
  if (!conversationId) {
    return;
  }
  const response = await fetch(`/api/conversations/${conversationId}/messages`);
  const messages = await response.json();
  const placeholder = chatMessages.querySelector('[data-chat-static="placeholder"]');
  const typingRow = chatTyping;

  chatMessages.innerHTML = '';
  messages.forEach((message) => {
    appendMessage({
      messageType: message.message_type,
      text: message.text,
      fileUrl: message.file_url,
      fileName: message.file_name,
      fileMime: message.file_mime,
    }, message.sender_role === 'user' ? 'user' : '');
  });
  if (!messages.length && placeholder) {
    chatMessages.appendChild(placeholder);
  }
  if (typingRow) {
    chatMessages.appendChild(typingRow);
  }
  notifyParentSize();
}

function notifyParentSize() {
  if (!window.parent || window.parent === window) {
    return;
  }
  const needsExpand = chatMessages.scrollHeight > chatMessages.clientHeight + 120;
  window.parent.postMessage({ type: 'chat:expand', expand: needsExpand }, '*');
}

function setTyping(isVisible) {
  if (!chatMessages.contains(chatTyping)) {
    chatMessages.appendChild(chatTyping);
  }
  chatTyping.style.display = isVisible ? 'flex' : 'none';
  chatTyping.setAttribute('aria-hidden', isVisible ? 'false' : 'true');
}

async function sendTyping(isTypingValue) {
  if (!conversationId) {
    return;
  }
  await fetch(`/api/conversations/${conversationId}/typing`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ sender_role: 'user', is_typing: isTypingValue }),
  });
}

async function sendMessage(payload) {
  if (!conversationId) {
    return;
  }
  await fetch(`/api/conversations/${conversationId}/messages`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      sender_role: 'user',
      message_type: payload.messageType,
      text: payload.text,
      file_url: payload.fileUrl,
      file_name: payload.fileName,
      file_mime: payload.fileMime,
    }),
  });
}

function registerChannel() {
  if (!conversationId || !echo) {
    return;
  }
  echo
    .channel(`conversation.${conversationId}`)
    .listen('message.sent', (event) => {
      if (event.senderRole === 'admin') {
        appendMessage({
          messageType: event.messageType,
          text: event.text,
          fileUrl: event.fileUrl,
          fileName: event.fileName,
          fileMime: event.fileMime,
        }, '');
        setTyping(false);
      }
    })
    .listen('typing.updated', (event) => {
      if (event.senderRole === 'admin') {
        setTyping(!!event.isTyping);
      }
    });
}

async function init() {
  if (!conversationId) {
    await createConversation();
  }
  registerChannel();
  await loadHistory();
  if (!echo) {
    if (pollTimer) {
      clearInterval(pollTimer);
    }
    pollTimer = setInterval(loadHistory, POLL_INTERVAL_MS);
  }
}

function initializeChat() {
  if (!initializeDOMElements()) {
    console.error('Failed to initialize chat elements');
    return;
  }
  setupEventListeners();
  init();
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initializeChat);
} else {
  initializeChat();
}
