<?php
session_start();

// Sample chat data for demonstration
$sampleChats = [
    [
        'id' => 1,
        'name' => 'Sarah Johnson',
        'avatar' => 'https://images.unsplash.com/photo-1494790108755-2616b612b786?ixlib=rb-4.0.3&auto=format&fit=crop&w=100&q=80',
        'lastMessage' => 'Hey! How are you doing today?',
        'time' => '2:30 PM',
        'unread' => 3,
        'status' => 'online'
    ],
    [
        'id' => 2,
        'name' => 'Mike Chen',
        'avatar' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?ixlib=rb-4.0.3&auto=format&fit=crop&w=100&q=80',
        'lastMessage' => 'The project looks great! 👍',
        'time' => '1:45 PM',
        'unread' => 0,
        'status' => 'away'
    ],
    [
        'id' => 3,
        'name' => 'Emma Wilson',
        'avatar' => 'https://images.unsplash.com/photo-1438761681033-6461ffad8d80?ixlib=rb-4.0.3&auto=format&fit=crop&w=100&q=80',
        'lastMessage' => 'Can we schedule a meeting?',
        'time' => '12:15 PM',
        'unread' => 1,
        'status' => 'online'
    ],
    [
        'id' => 4,
        'name' => 'Alex Rodriguez',
        'avatar' => 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?ixlib=rb-4.0.3&auto=format&fit=crop&w=100&q=80',
        'lastMessage' => 'Thanks for your help!',
        'time' => '11:30 AM',
        'unread' => 0,
        'status' => 'offline'
    ],
    [
        'id' => 5,
        'name' => 'Lisa Park',
        'avatar' => 'https://images.unsplash.com/photo-1544005313-94ddf0286df2?ixlib=rb-4.0.3&auto=format&fit=crop&w=100&q=80',
        'lastMessage' => 'Let me know when you\'re free',
        'time' => '10:20 AM',
        'unread' => 2,
        'status' => 'online'
    ]
];

// Sample messages for active chat
$sampleMessages = [
    [
        'type' => 'received',
        'message' => 'Hey! How are you doing today?',
        'time' => '2:25 PM',
        'avatar' => 'https://images.unsplash.com/photo-1494790108755-2616b612b786?ixlib=rb-4.0.3&auto=format&fit=crop&w=100&q=80'
    ],
    [
        'type' => 'sent',
        'message' => 'Hi Sarah! I\'m doing great, thanks for asking. How about you?',
        'time' => '2:26 PM'
    ],
    [
        'type' => 'received',
        'message' => 'I\'m good too! Working on some exciting projects. Are you free for a quick call later?',
        'time' => '2:27 PM',
        'avatar' => 'https://images.unsplash.com/photo-1494790108755-2616b612b786?ixlib=rb-4.0.3&auto=format&fit=crop&w=100&q=80'
    ],
    [
        'type' => 'sent',
        'message' => 'Sure! What time works for you?',
        'time' => '2:28 PM'
    ],
    [
        'type' => 'received',
        'message' => 'How about 4 PM? We can discuss the new features for the app.',
        'time' => '2:29 PM',
        'avatar' => 'https://images.unsplash.com/photo-1494790108755-2616b612b786?ixlib=rb-4.0.3&auto=format&fit=crop&w=100&q=80'
    ],
    [
        'type' => 'sent',
        'message' => 'Perfect! See you at 4 PM 👍',
        'time' => '2:30 PM'
    ]
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ChatUI - Modern Messaging Interface</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #6366f1;
            --secondary-color: #f1f5f9;
            --accent-color: #10b981;
            --danger-color: #ef4444;
            --warning-color: #f59e0b;
            --dark-color: #1e293b;
            --light-color: #f8fafc;
            --border-color: #e2e8f0;
            --text-muted: #64748b;
            --online-color: #22c55e;
            --away-color: #f59e0b;
            --offline-color: #94a3b8;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            height: 100vh;
            overflow: hidden;
        }

        .chat-container {
            height: 100vh;
            display: flex;
            background: white;
            box-shadow: 0 0 50px rgba(0, 0, 0, 0.1);
        }

        /* Sidebar Styles */
        .chat-sidebar {
            width: 350px;
            background: var(--light-color);
            border-right: 1px solid var(--border-color);
            display: flex;
            flex-direction: column;
        }

        .sidebar-header {
            padding: 20px;
            background: white;
            border-bottom: 1px solid var(--border-color);
        }

        .sidebar-header h4 {
            color: var(--dark-color);
            font-weight: 600;
            margin-bottom: 10px;
        }

        .search-box {
            position: relative;
        }

        .search-box input {
            width: 100%;
            padding: 12px 40px 12px 16px;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            background: var(--secondary-color);
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .search-box input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        .search-box i {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
        }

        /* Chat List */
        .chat-list {
            flex: 1;
            overflow-y: auto;
            padding: 10px 0;
        }

        .chat-item {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
            background: none;
            width: 100%;
            text-align: left;
        }

        .chat-item:hover {
            background: rgba(99, 102, 241, 0.05);
        }

        .chat-item.active {
            background: rgba(99, 102, 241, 0.1);
            border-right: 3px solid var(--primary-color);
        }

        .chat-avatar {
            position: relative;
            margin-right: 12px;
        }

        .chat-avatar img {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            object-fit: cover;
        }

        .status-indicator {
            position: absolute;
            bottom: 2px;
            right: 2px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            border: 2px solid white;
        }

        .status-online { background: var(--online-color); }
        .status-away { background: var(--warning-color); }
        .status-offline { background: var(--offline-color); }

        .chat-info {
            flex: 1;
            min-width: 0;
        }

        .chat-name {
            font-weight: 600;
            color: var(--dark-color);
            font-size: 14px;
            margin-bottom: 2px;
        }

        .chat-last-message {
            color: var(--text-muted);
            font-size: 13px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .chat-meta {
            text-align: right;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
        }

        .chat-time {
            color: var(--text-muted);
            font-size: 12px;
            margin-bottom: 4px;
        }

        .unread-badge {
            background: var(--primary-color);
            color: white;
            font-size: 11px;
            padding: 2px 6px;
            border-radius: 10px;
            min-width: 18px;
            text-align: center;
        }

        /* Main Chat Area */
        .chat-main {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: white;
        }

        .chat-header {
            padding: 20px;
            background: white;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .chat-header-info {
            display: flex;
            align-items: center;
        }

        .chat-header-avatar {
            margin-right: 12px;
            position: relative;
        }

        .chat-header-avatar img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }

        .chat-header-details h5 {
            color: var(--dark-color);
            font-weight: 600;
            margin-bottom: 2px;
            font-size: 16px;
        }

        .chat-header-status {
            color: var(--online-color);
            font-size: 12px;
            font-weight: 500;
        }

        .chat-actions {
            display: flex;
            gap: 10px;
        }

        .chat-actions button {
            width: 36px;
            height: 36px;
            border: none;
            background: var(--secondary-color);
            border-radius: 8px;
            color: var(--text-muted);
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .chat-actions button:hover {
            background: var(--primary-color);
            color: white;
        }

        /* Messages Area */
        .messages-container {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
            background: #fafbfc;
        }

        .message {
            display: flex;
            margin-bottom: 20px;
            animation: slideIn 0.3s ease;
        }

        .message.sent {
            justify-content: flex-end;
        }

        .message-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 10px;
            margin-top: auto;
        }

        .message.sent .message-avatar {
            display: none;
        }

        .message-content {
            max-width: 70%;
            display: flex;
            flex-direction: column;
        }

        .message.sent .message-content {
            align-items: flex-end;
        }

        .message-bubble {
            padding: 12px 16px;
            border-radius: 18px;
            font-size: 14px;
            line-height: 1.4;
            word-wrap: break-word;
        }

        .message.received .message-bubble {
            background: white;
            color: var(--dark-color);
            border-bottom-left-radius: 6px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
        }

        .message.sent .message-bubble {
            background: var(--primary-color);
            color: white;
            border-bottom-right-radius: 6px;
        }

        .message-time {
            font-size: 11px;
            color: var(--text-muted);
            margin-top: 4px;
        }

        .message.sent .message-time {
            text-align: right;
        }

        /* Message Input */
        .message-input-container {
            padding: 20px;
            background: white;
            border-top: 1px solid var(--border-color);
        }

        .message-input-wrapper {
            display: flex;
            align-items: flex-end;
            gap: 12px;
            background: var(--secondary-color);
            border-radius: 24px;
            padding: 8px;
        }

        .message-input {
            flex: 1;
            border: none;
            background: none;
            padding: 8px 12px;
            resize: none;
            max-height: 120px;
            min-height: 20px;
            font-size: 14px;
            line-height: 1.4;
        }

        .message-input:focus {
            outline: none;
        }

        .input-actions {
            display: flex;
            gap: 8px;
        }

        .input-action-btn {
            width: 32px;
            height: 32px;
            border: none;
            background: none;
            border-radius: 50%;
            color: var(--text-muted);
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .input-action-btn:hover {
            background: rgba(99, 102, 241, 0.1);
            color: var(--primary-color);
        }

        .send-btn {
            background: var(--primary-color) !important;
            color: white !important;
        }

        .send-btn:hover {
            background: #5856eb !important;
        }

        .send-btn:disabled {
            background: var(--text-muted) !important;
            cursor: not-allowed;
        }

        /* Animations */
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Scrollbar Styling */
        .chat-list::-webkit-scrollbar,
        .messages-container::-webkit-scrollbar {
            width: 6px;
        }

        .chat-list::-webkit-scrollbar-track,
        .messages-container::-webkit-scrollbar-track {
            background: transparent;
        }

        .chat-list::-webkit-scrollbar-thumb,
        .messages-container::-webkit-scrollbar-thumb {
            background: var(--border-color);
            border-radius: 3px;
        }

        .chat-list::-webkit-scrollbar-thumb:hover,
        .messages-container::-webkit-scrollbar-thumb:hover {
            background: var(--text-muted);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .chat-sidebar {
                position: absolute;
                left: -350px;
                z-index: 1000;
                height: 100%;
                transition: left 0.3s ease;
            }

            .chat-sidebar.show {
                left: 0;
            }

            .mobile-toggle {
                display: block !important;
            }

            .chat-header-details h5 {
                font-size: 14px;
            }

            .message-content {
                max-width: 85%;
            }
        }

        .mobile-toggle {
            display: none;
        }

        /* Typing Indicator */
        .typing-indicator {
            display: flex;
            align-items: center;
            padding: 10px 20px;
            color: var(--text-muted);
            font-size: 13px;
            font-style: italic;
        }

        .typing-dots {
            display: flex;
            gap: 3px;
            margin-left: 8px;
        }

        .typing-dot {
            width: 4px;
            height: 4px;
            border-radius: 50%;
            background: var(--text-muted);
            animation: typing 1.4s infinite;
        }

        .typing-dot:nth-child(2) {
            animation-delay: 0.2s;
        }

        .typing-dot:nth-child(3) {
            animation-delay: 0.4s;
        }

        @keyframes typing {
            0%, 60%, 100% {
                transform: translateY(0);
                opacity: 0.5;
            }
            30% {
                transform: translateY(-10px);
                opacity: 1;
            }
        }
    </style>
</head>
<body>
    <div class="chat-container">
        <!-- Sidebar -->
        <div class="chat-sidebar" id="chatSidebar">
            <div class="sidebar-header">
                <h4><i class="bi bi-chat-dots me-2"></i>Messages</h4>
                <div class="search-box">
                    <input type="text" placeholder="Search conversations..." id="searchInput">
                    <i class="bi bi-search"></i>
                </div>
            </div>
            
            <div class="chat-list" id="chatList">
                <?php foreach ($sampleChats as $index => $chat): ?>
                    <button class="chat-item <?php echo $index === 0 ? 'active' : ''; ?>" 
                            data-chat-id="<?php echo $chat['id']; ?>"
                            data-name="<?php echo htmlspecialchars($chat['name']); ?>"
                            data-avatar="<?php echo $chat['avatar']; ?>"
                            data-status="<?php echo $chat['status']; ?>">
                        <div class="chat-avatar">
                            <img src="<?php echo $chat['avatar']; ?>" alt="<?php echo htmlspecialchars($chat['name']); ?>">
                            <div class="status-indicator status-<?php echo $chat['status']; ?>"></div>
                        </div>
                        <div class="chat-info">
                            <div class="chat-name"><?php echo htmlspecialchars($chat['name']); ?></div>
                            <div class="chat-last-message"><?php echo htmlspecialchars($chat['lastMessage']); ?></div>
                        </div>
                        <div class="chat-meta">
                            <div class="chat-time"><?php echo $chat['time']; ?></div>
                            <?php if ($chat['unread'] > 0): ?>
                                <div class="unread-badge"><?php echo $chat['unread']; ?></div>
                            <?php endif; ?>
                        </div>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Main Chat Area -->
        <div class="chat-main">
            <div class="chat-header">
                <button class="mobile-toggle btn btn-light me-3" onclick="toggleSidebar()">
                    <i class="bi bi-list"></i>
                </button>
                
                <div class="chat-header-info">
                    <div class="chat-header-avatar">
                        <img src="<?php echo $sampleChats[0]['avatar']; ?>" alt="<?php echo htmlspecialchars($sampleChats[0]['name']); ?>" id="headerAvatar">
                        <div class="status-indicator status-<?php echo $sampleChats[0]['status']; ?>" id="headerStatus"></div>
                    </div>
                    <div class="chat-header-details">
                        <h5 id="headerName"><?php echo htmlspecialchars($sampleChats[0]['name']); ?></h5>
                        <div class="chat-header-status" id="headerStatusText">
                            <?php echo ucfirst($sampleChats[0]['status']); ?>
                        </div>
                    </div>
                </div>

                <div class="chat-actions">
                    <button title="Video Call"><i class="bi bi-camera-video"></i></button>
                    <button title="Voice Call"><i class="bi bi-telephone"></i></button>
                    <button title="More Options"><i class="bi bi-three-dots"></i></button>
                </div>
            </div>

            <div class="messages-container" id="messagesContainer">
                <?php foreach ($sampleMessages as $message): ?>
                    <div class="message <?php echo $message['type']; ?>">
                        <?php if ($message['type'] === 'received'): ?>
                            <img src="<?php echo $message['avatar']; ?>" alt="Avatar" class="message-avatar">
                        <?php endif; ?>
                        <div class="message-content">
                            <div class="message-bubble">
                                <?php echo htmlspecialchars($message['message']); ?>
                            </div>
                            <div class="message-time"><?php echo $message['time']; ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="typing-indicator" id="typingIndicator" style="display: none;">
                <span>Sarah is typing</span>
                <div class="typing-dots">
                    <div class="typing-dot"></div>
                    <div class="typing-dot"></div>
                    <div class="typing-dot"></div>
                </div>
            </div>

            <div class="message-input-container">
                <div class="message-input-wrapper">
                    <textarea class="message-input" 
                              placeholder="Type a message..." 
                              id="messageInput"
                              rows="1"></textarea>
                    <div class="input-actions">
                        <button class="input-action-btn" title="Attach File">
                            <i class="bi bi-paperclip"></i>
                        </button>
                        <button class="input-action-btn" title="Emoji">
                            <i class="bi bi-emoji-smile"></i>
                        </button>
                        <button class="input-action-btn send-btn" id="sendBtn" title="Send Message">
                            <i class="bi bi-send"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Chat functionality
        class ChatUI {
            constructor() {
                this.messageInput = document.getElementById('messageInput');
                this.sendBtn = document.getElementById('sendBtn');
                this.messagesContainer = document.getElementById('messagesContainer');
                this.chatList = document.getElementById('chatList');
                this.searchInput = document.getElementById('searchInput');
                this.typingIndicator = document.getElementById('typingIndicator');

                this.initEventListeners();
                this.scrollToBottom();
                this.autoResizeTextarea();
            }

            initEventListeners() {
                // Send message
                this.sendBtn.addEventListener('click', () => this.sendMessage());
                this.messageInput.addEventListener('keypress', (e) => {
                    if (e.key === 'Enter' && !e.shiftKey) {
                        e.preventDefault();
                        this.sendMessage();
                    }
                });

                // Auto-resize textarea
                this.messageInput.addEventListener('input', () => {
                    this.autoResizeTextarea();
                    this.updateSendButton();
                });

                // Chat selection
                this.chatList.addEventListener('click', (e) => {
                    const chatItem = e.target.closest('.chat-item');
                    if (chatItem) {
                        this.selectChat(chatItem);
                    }
                });

                // Search functionality
                this.searchInput.addEventListener('input', (e) => {
                    this.searchChats(e.target.value);
                });

                // Simulate typing indicator
                this.messageInput.addEventListener('input', () => {
                    this.simulateTyping();
                });
            }

            sendMessage() {
                const message = this.messageInput.value.trim();
                if (!message) return;

                // Add sent message
                this.addMessage('sent', message, this.getCurrentTime());
                this.messageInput.value = '';
                this.autoResizeTextarea();
                this.updateSendButton();
                this.scrollToBottom();

                // Simulate received message after delay
                setTimeout(() => {
                    this.simulateReceivedMessage();
                }, 1000 + Math.random() * 2000);
            }

            addMessage(type, text, time, avatar = null) {
                const messageDiv = document.createElement('div');
                messageDiv.className = `message ${type}`;
                
                let avatarHtml = '';
                if (type === 'received' && avatar) {
                    avatarHtml = `<img src="${avatar}" alt="Avatar" class="message-avatar">`;
                }

                messageDiv.innerHTML = `
                    ${avatarHtml}
                    <div class="message-content">
                        <div class="message-bubble">${this.escapeHtml(text)}</div>
                        <div class="message-time">${time}</div>
                    </div>
                `;

                this.messagesContainer.appendChild(messageDiv);
                this.scrollToBottom();
            }

            simulateReceivedMessage() {
                const responses = [
                    "That sounds great!",
                    "I agree with you 👍",
                    "Let me think about that...",
                    "Absolutely!",
                    "That's a good point",
                    "Thanks for sharing!",
                    "I'll get back to you on that",
                    "Sounds like a plan!",
                    "Perfect! 😊"
                ];

                const randomResponse = responses[Math.floor(Math.random() * responses.length)];
                const currentAvatar = document.getElementById('headerAvatar').src;
                
                this.showTypingIndicator();
                
                setTimeout(() => {
                    this.hideTypingIndicator();
                    this.addMessage('received', randomResponse, this.getCurrentTime(), currentAvatar);
                }, 1500);
            }

            showTypingIndicator() {
                this.typingIndicator.style.display = 'flex';
                this.scrollToBottom();
            }

            hideTypingIndicator() {
                this.typingIndicator.style.display = 'none';
            }

            simulateTyping() {
                // Clear existing timeout
                clearTimeout(this.typingTimeout);
                
                // Show typing indicator
                this.showTypingIndicator();
                
                // Hide after 3 seconds of no input
                this.typingTimeout = setTimeout(() => {
                    this.hideTypingIndicator();
                }, 3000);
            }

            selectChat(chatItem) {
                // Remove active class from all items
                document.querySelectorAll('.chat-item').forEach(item => {
                    item.classList.remove('active');
                });

                // Add active class to selected item
                chatItem.classList.add('active');

                // Update header
                const name = chatItem.dataset.name;
                const avatar = chatItem.dataset.avatar;
                const status = chatItem.dataset.status;

                document.getElementById('headerName').textContent = name;
                document.getElementById('headerAvatar').src = avatar;
                document.getElementById('headerStatus').className = `status-indicator status-${status}`;
                document.getElementById('headerStatusText').textContent = status.charAt(0).toUpperCase() + status.slice(1);

                // Clear unread badge
                const unreadBadge = chatItem.querySelector('.unread-badge');
                if (unreadBadge) {
                    unreadBadge.remove();
                }

                // Load chat messages (in real app, you'd load from server)
                this.loadChatMessages(chatItem.dataset.chatId);
            }

            loadChatMessages(chatId) {
                // Clear current messages
                this.messagesContainer.innerHTML = '';

                // Simulate loading different messages for different chats
                const messageSets = {
                    '1': [
                        { type: 'received', message: 'Hey! How are you doing today?', time: '2:25 PM' },
                        { type: 'sent', message: 'Hi Sarah! I\'m doing great, thanks for asking.', time: '2:26 PM' }
                    ],
                    '2': [
                        { type: 'received', message: 'The project looks great! 👍', time: '1:40 PM' },
                        { type: 'sent', message: 'Thanks Mike! Glad you like it.', time: '1:42 PM' }
                    ],
                    '3': [
                        { type: 'received', message: 'Can we schedule a meeting?', time: '12:10 PM' },
                        { type: 'sent', message: 'Sure! When works for you?', time: '12:12 PM' }
                    ]
                };

                const messages = messageSets[chatId] || [];
                const currentAvatar = document.getElementById('headerAvatar').src;

                messages.forEach(msg => {
                    this.addMessage(msg.type, msg.message, msg.time, msg.type === 'received' ? currentAvatar : null);
                });
            }

            searchChats(query) {
                const chatItems = document.querySelectorAll('.chat-item');
                
                chatItems.forEach(item => {
                    const name = item.querySelector('.chat-name').textContent.toLowerCase();
                    const message = item.querySelector('.chat-last-message').textContent.toLowerCase();
                    
                    if (name.includes(query.toLowerCase()) || message.includes(query.toLowerCase())) {
                        item.style.display = 'flex';
                    } else {
                        item.style.display = 'none';
                    }
                });
            }

            autoResizeTextarea() {
                this.messageInput.style.height = 'auto';
                this.messageInput.style.height = Math.min(this.messageInput.scrollHeight, 120) + 'px';
            }

            updateSendButton() {
                const hasText = this.messageInput.value.trim().length > 0;
                this.sendBtn.disabled = !hasText;
            }

            scrollToBottom() {
                setTimeout(() => {
                    this.messagesContainer.scrollTop = this.messagesContainer.scrollHeight;
                }, 100);
            }

            getCurrentTime() {
                const now = new Date();
                return now.toLocaleTimeString('en-US', { 
                    hour: 'numeric', 
                    minute: '2-digit',
                    hour12: true 
                });
            }

            escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }
        }

        // Mobile sidebar toggle
        function toggleSidebar() {
            const sidebar = document.getElementById('chatSidebar');
            sidebar.classList.toggle('show');
        }

        // Initialize chat when DOM is loaded
        document.addEventListener('DOMContentLoaded', () => {
            new ChatUI();
        });

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', (e) => {
            const sidebar = document.getElementById('chatSidebar');
            const toggle = document.querySelector('.mobile-toggle');
            
            if (window.innerWidth <= 768 && 
                !sidebar.contains(e.target) && 
                !toggle.contains(e.target) && 
                sidebar.classList.contains('show')) {
                sidebar.classList.remove('show');
            }
        });

        // Handle window resize
        window.addEventListener('resize', () => {
            const sidebar = document.getElementById('chatSidebar');
            if (window.innerWidth > 768) {
                sidebar.classList.remove('show');
            }
        });
    </script>
</body>
</html>