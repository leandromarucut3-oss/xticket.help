<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Chat Admin</title>
  <script>
    window.ECHO_CONFIG = {
      key: "{{ env('VITE_PUSHER_APP_KEY', env('PUSHER_APP_KEY', '')) }}",
      host: "{{ env('VITE_PUSHER_HOST', env('PUSHER_HOST', '127.0.0.1')) }}",
      port: {{ env('VITE_PUSHER_PORT', env('PUSHER_PORT', 6001)) }},
      scheme: "{{ env('VITE_PUSHER_SCHEME', env('PUSHER_SCHEME', 'http')) }}",
      cluster: "{{ env('VITE_PUSHER_APP_CLUSTER', '') }}",
    };
  </script>
  @vite(['resources/css/app.css', 'resources/js/admin.js'])
  <style>
    body {
      margin: 0;
      font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
      background: #f4f5f7;
      color: #1c1c1c;
      height: 100vh;
      display: flex;
      flex-direction: column;
    }

    /* Prevent horizontal scroll on small devices */
    html, body { max-width: 100%; overflow-x: hidden; }
    header {
      padding: 16px 24px;
      background: #111111;
      color: #ffffff;
      font-size: 18px;
    }
    .layout {
      flex: 1;
      display: grid;
      grid-template-columns: 280px 1fr;
      min-height: 0;
    }

    @media (max-width: 600px) {
      .layout { grid-template-columns: 1fr; }
      .sidebar { display: none; }
      .chat-panel { padding: 0; }
    }
    .sidebar {
      background: #ffffff;
      border-right: 1px solid #e0e0e0;
      display: flex;
      flex-direction: column;
    }
    .sidebar h2 {
      margin: 0;
      padding: 16px 20px 8px;
      font-size: 14px;
      text-transform: uppercase;
      color: #666666;
    }
    .conversation-list {
      list-style: none;
      margin: 0;
      padding: 0 8px 16px;
      overflow-y: auto;
    }
    .conversation-list li {
      padding: 12px;
      border-radius: 10px;
      cursor: pointer;
      display: flex;
      justify-content: space-between;
      gap: 8px;
      margin-bottom: 6px;
    }
    .conversation-list li.active {
      background: #111111;
      color: #ffffff;
    }
    .conversation-list li .status {
      font-size: 11px;
      padding: 2px 8px;
      border-radius: 999px;
      background: #f0f0f0;
      color: #444444;
    }
    .conversation-list li .status--online {
      background: #dcfce7;
      color: #166534;
    }
    .conversation-list li .status--idle {
      background: #f0f0f0;
      color: #444444;
    }
    .conversation-list li.active .status {
      background: #ffffff;
      color: #111111;
    }
    .chat-panel {
      display: flex;
      flex-direction: column;
      background: #f4f5f7;
    }
    .chat-header {
      padding: 16px 20px;
      border-bottom: 1px solid #e0e0e0;
      background: #ffffff;
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 12px;
    }
    .chat-header__meta {
      display: flex;
      align-items: center;
      gap: 12px;
      font-size: 12px;
      color: #666666;
    }
    .chat-messages {
      flex: 1;
      padding: 20px;
      overflow-y: auto;
      display: flex;
      flex-direction: column;
      gap: 10px;
    }
    .chat-message {
      max-width: 70%;
      padding: 10px 12px;
      border-radius: 12px;
      background: #ffffff;
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.06);
      font-size: 14px;
    }
    .chat-message img,
    .chat-message video {
      max-width: 100%;
      border-radius: 10px;
      display: block;
    }
    .chat-message.user {
      align-self: flex-start;
    }
    .chat-message.admin {
      align-self: flex-end;
      background: #dbeafe;
    }
    .chat-typing {
      display: none;
      align-items: center;
      gap: 6px;
      max-width: 70%;
      padding: 10px 12px;
      border-radius: 12px;
      background: #ffffff;
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.06);
      font-size: 12px;
      color: #666666;
    }
    .typing-dots {
      display: inline-flex;
      gap: 4px;
    }
    .typing-dots span {
      width: 6px;
      height: 6px;
      border-radius: 50%;
      background: #777777;
      display: inline-block;
      animation: admin-typing 1.2s infinite ease-in-out;
    }
    .typing-dots span:nth-child(2) {
      animation-delay: 0.2s;
    }
    .typing-dots span:nth-child(3) {
      animation-delay: 0.4s;
    }
    @keyframes admin-typing {
      0%,
      80%,
      100% {
        transform: translateY(0);
        opacity: 0.4;
      }
      40% {
        transform: translateY(-4px);
        opacity: 1;
      }
    }
    .chat-input {
      padding: 16px 20px;
      border-top: 1px solid #e0e0e0;
      background: #ffffff;
      display: flex;
      gap: 10px;
    }
    .chat-input input {
      flex: 1;
      padding: 10px 12px;
      border: 1px solid #d0d0d0;
      border-radius: 10px;
      font-size: 14px;
    }
    .chat-input button {
      padding: 10px 14px;
      border: 0;
      border-radius: 10px;
      background: #111111;
      color: #ffffff;
      cursor: pointer;
    }
    .chat-attach {
      background: #ffffff;
      color: #111111;
      border: 1px solid #d0d0d0;
      width: 40px;
      height: 40px;
      font-size: 18px;
      line-height: 1;
    }
    .empty-state {
      padding: 32px;
      color: #666666;
      text-align: center;
    }
  </style>
</head>
<body>
  <header>Support Chat Admin</header>
  <div class="layout">
    <aside class="sidebar">
      <div style="padding:16px;border-bottom:1px solid #eaeaea">
        <form id="invite-form">
          <label style="display:block;font-size:12px;color:#666;margin-bottom:6px">Generate Invite for Username (optional)</label>
          <input id="invite-username" name="username" placeholder="username" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:6px;margin-bottom:8px">
          <div style="display:flex;gap:8px">
            <input id="invite-days" name="expires_days" type="number" placeholder="Expire days" style="width:120px;padding:8px;border:1px solid #ddd;border-radius:6px">
            <button id="invite-generate" type="button" style="padding:8px 10px;background:#111;color:#fff;border-radius:6px;border:0">Generate</button>
          </div>
          <div id="invite-result" style="margin-top:8px;font-size:13px;word-break:break-all"></div>
        </form>
      </div>
      <h2>Conversations</h2>
      <ul class="conversation-list" id="conversation-list"></ul>
    </aside>
    <section class="chat-panel">
      <div class="chat-header">
        <h3 id="chat-title">Select a conversation</h3>
        <div class="chat-header__meta">
          <span id="chat-status"></span>
        </div>
      </div>
      <div class="chat-messages" id="chat-messages">
        <div class="empty-state" id="empty-state">No conversation selected.</div>
        <div class="chat-typing" id="chat-typing" aria-hidden="true">User is typing
          <span class="typing-dots">
            <span></span>
            <span></span>
            <span></span>
          </span>
        </div>
      </div>
      <form class="chat-input" id="chat-form">
        <button class="chat-attach" type="button" id="chat-attach" aria-label="Attach file">+</button>
        <input id="chat-text" type="text" placeholder="Type a reply..." autocomplete="off" disabled>
        <button type="submit" disabled>Send</button>
        <input id="chat-file" type="file" accept="image/*,video/*" style="display:none">
      </form>
    </section>
  </div>
</div>
  <script>
    (function(){
      const btn = document.getElementById('invite-generate');
      const result = document.getElementById('invite-result');
      btn?.addEventListener('click', async function(){
        result.textContent = 'Generating...';
        const username = document.getElementById('invite-username').value;
        const days = document.getElementById('invite-days').value;
        const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        try {
          const res = await fetch('/admin/invites', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': token,
              'Accept': 'application/json'
            },
            body: JSON.stringify({ username: username || null, expires_days: days || null })
          });
          const data = await res.json();
          if (data.link) {
            result.innerHTML = 'Invite link: <a href="'+data.link+'" target="_blank">'+data.link+'</a>';
          } else {
            result.textContent = JSON.stringify(data);
          }
        } catch (err) {
          result.textContent = 'Error generating invite';
        }
      });
    })();
  </script>
  <script>
    (function(){
      const attachBtn = document.getElementById('chat-attach');
      const chatInput = document.getElementById('chat-text');
      const fileInput = document.getElementById('chat-file');
      const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

      // create popover
      const pop = document.createElement('div');
      pop.id = 'add-popover';
      pop.style.position = 'absolute';
      pop.style.minWidth = '280px';
      pop.style.background = '#fff';
      pop.style.boxShadow = '0 8px 24px rgba(0,0,0,0.12)';
      pop.style.borderRadius = '8px';
      pop.style.padding = '10px';
      pop.style.display = 'none';
      pop.style.zIndex = '2000';
      pop.innerHTML = `
        <div style="display:flex;flex-direction:column;gap:8px">
          <button id="popover-upload" type="button" style="padding:10px;border-radius:6px;border:0;background:#fff;text-align:left">📤 Upload image</button>
          <div style="border-top:1px solid #eee;padding-top:8px">
            <div style="display:flex;gap:8px;margin-bottom:8px">
              <input id="popover-saved-input" placeholder="Add saved reply" style="flex:1;padding:8px;border:1px solid #ddd;border-radius:6px">
              <button id="popover-saved-add" type="button" style="padding:8px 10px;background:#0b74de;color:#fff;border-radius:6px;border:0">Add</button>
            </div>
            <div id="popover-saved-list" style="display:flex;flex-direction:column;gap:6px;max-height:220px;overflow:auto"></div>
          </div>
        </div>
      `;
      document.body.appendChild(pop);

      function positionPopover(){
        const rect = attachBtn.getBoundingClientRect();
        // position above the button if possible
        const left = window.scrollX + rect.left;
        let top = window.scrollY + rect.top - pop.offsetHeight - 8;
        if (top < 8) top = window.scrollY + rect.bottom + 8;
        pop.style.left = left + 'px';
        pop.style.top = top + 'px';
      }

      async function fetchReplies(){
        try {
          const res = await fetch('/admin/saved-replies');
          if (!res.ok) return [];
          return await res.json();
        } catch (e) { return []; }
      }

      function renderSaved(list){
        const listEl = document.getElementById('popover-saved-list');
        listEl.innerHTML = '';
        list.forEach(item => {
          const b = document.createElement('button');
          b.type = 'button';
          b.style.padding = '8px';
          b.style.border = '0';
          b.style.textAlign = 'left';
          b.style.background = '#f9f9fb';
          b.style.borderRadius = '6px';
          b.style.cursor = 'pointer';
          b.textContent = item.text;
          b.title = item.text;
          b.addEventListener('click', ()=>{
            if (chatInput) {
              chatInput.value = item.text;
              chatInput.disabled = false;
              const sendBtn = chatInput.nextElementSibling;
              if (sendBtn && sendBtn.tagName === 'BUTTON') sendBtn.disabled = false;
              chatInput.focus();
            }
            pop.style.display = 'none';
          });
          listEl.appendChild(b);
        });
      }

      attachBtn.addEventListener('click', async (e) => {
        if (pop.style.display === 'block') { pop.style.display = 'none'; return; }
        pop.style.display = 'block';
        positionPopover();
        const list = await fetchReplies();
        renderSaved(list);
      });

      window.addEventListener('resize', ()=>{ if (pop.style.display === 'block') positionPopover(); });

      document.addEventListener('click', (ev)=>{
        if (!pop.contains(ev.target) && ev.target !== attachBtn) pop.style.display = 'none';
      });

      // upload image
      document.addEventListener('click', function(ev){
        if (ev.target && ev.target.id === 'popover-upload'){
          fileInput.click();
          pop.style.display = 'none';
        }
      });

      // add saved reply
      document.addEventListener('click', async function(ev){
        if (ev.target && ev.target.id === 'popover-saved-add'){
          const input = document.getElementById('popover-saved-input');
          const text = input.value.trim();
          if (!text) return;
          const btn = ev.target;
          btn.disabled = true;
          try {
            const res = await fetch('/admin/saved-replies', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'Accept': 'application/json'
              },
              body: JSON.stringify({ text })
            });
            if (res.ok) {
              input.value = '';
              const list = await fetchReplies();
              renderSaved(list);
            }
          } finally { btn.disabled = false; }
        }
      });
    })();
  </script>
</body>
</html>
