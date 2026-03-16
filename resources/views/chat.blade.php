<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Support Chat</title>
  @vite(['resources/css/app.css', 'resources/js/chat.js'])
  <style>
    html, body {
      height: 100%;
    }
    body {
      margin: 0;
      font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
      background: #ffffff;
      color: #1c1c1c;
      display: flex;
      justify-content: center;
      align-items: flex-end;
      padding: 0;
    }
    .chat-shell {
      max-width: 500px;
      width: 100%;
      height: 100%;
      max-height: 100vh;
      margin: 0;
      background: #ffffff;
      border-radius: 16px 16px 0 0;
      box-shadow: 0 5px 30px rgba(0, 0, 0, 0.2);
      display: flex;
      flex-direction: column;
      flex: 1;
    }
    @media (max-width: 768px) {
      body {
        padding: 0;
        align-items: stretch;
      }
      .chat-shell {
        max-width: 100%;
        border-radius: 0;
        max-height: 100vh;
      }
    }
    .chat-header {
      padding: 14px 18px;
      border-bottom: 1px solid #e6e6e6;
      font-size: 18px;
      font-weight: 600;
    }
    .chat-messages {
      flex: 1;
      padding: 16px 18px;
      overflow-y: auto;
      display: flex;
      flex-direction: column;
      gap: 10px;
    }
    .chat-message {
      max-width: 80%;
      padding: 10px 12px;
      border-radius: 12px;
      font-size: 14px;
      background: #f2f2f2;
      word-break: break-word;
    }
    .chat-message.user {
      align-self: flex-end;
      background: #dbeafe;
    }
    .chat-message img,
    .chat-message video {
      max-width: 100%;
      border-radius: 10px;
      display: block;
    }
    .chat-connecting {
      display: none;
      align-items: center;
      gap: 8px;
      padding: 12px 16px;
      background: linear-gradient(135deg, #f0f4ff 0%, #f9fafb 100%);
      border-radius: 4px;
      color: #4b5563;
      font-size: 13px;
      font-weight: 500;
      margin: 8px 0;
      border: 1px solid #e5e7eb;
    }
    .chat-connecting.visible {
      display: flex;
    }
    .chat-typing {
      display: none;
      align-items: center;
      gap: 6px;
      padding: 10px 12px;
      border-radius: 12px;
      background: #f0f0f0;
      border: 2px solid #111111;
      font-size: 13px;
      color: #111111;
      width: fit-content;
      font-weight: 500;
    }
    .chat-typing.visible {
      display: flex;
    }
    .typing-dots {
      display: inline-flex;
      gap: 4px;
      align-items: center;
    }
    .typing-dots span {
      width: 8px;
      height: 8px;
      border-radius: 50%;
      background: #111111;
      display: inline-block;
      animation: chat-typing 1.2s infinite ease-in-out;
    }
    .typing-dots span:nth-child(2) {
      animation-delay: 0.2s;
    }
    .typing-dots span:nth-child(3) {
      animation-delay: 0.4s;
    }
    @keyframes chat-typing {
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
      padding: 12px 16px;
      border-top: 1px solid #e6e6e6;
      display: flex;
      gap: 10px;
    }
    .chat-input button,
    .chat-input input {
      border-radius: 10px;
      border: 1px solid #d0d0d0;
    }
    .chat-input input {
      flex: 1;
      padding: 10px 12px;
      font-size: 14px;
    }
    .chat-input button {
      padding: 10px 14px;
      background: #111111;
      color: #ffffff;
      cursor: pointer;
      border: 0;
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
  </style>
</head>
<body>
  <div class="chat-shell" data-chat-root>
    <div class="chat-header">Chat with Support</div>
    <div class="chat-messages" id="chat-messages">
      <div class="chat-connecting visible" id="chat-connecting" aria-hidden="false">
        <span>Connecting to an agent</span>
        <span class="typing-dots">
          <span></span>
          <span></span>
          <span></span>
        </span>
      </div>
      <div class="chat-typing" id="chat-typing" aria-hidden="true">Agent is typing
        <span class="typing-dots">
          <span></span>
          <span></span>
          <span></span>
        </span>
      </div>
    </div>
    <form class="chat-input" id="chat-form">
      <button class="chat-attach" type="button" id="chat-attach" aria-label="Attach file">+</button>
      <input id="chat-text" type="text" placeholder="Type your message..." autocomplete="off">
      <button type="submit">Send</button>
      <input id="chat-file" type="file" accept="image/*,video/*" style="display:none">
    </form>
  </div>
</body>
</html>
