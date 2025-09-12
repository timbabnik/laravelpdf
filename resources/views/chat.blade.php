<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>TimGPT - AI Chat</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600" rel="stylesheet" />
    
    <!-- Styles / Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <style>
        * {
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #0f0f0f;
            color: #ffffff;
            margin: 0;
            padding: 0;
            overflow: hidden;
        }
        
        .chat-container {
            height: 100vh;
            display: flex;
            flex-direction: column;
            background: linear-gradient(135deg, #0f0f0f 0%, #1a1a1a 100%);
            position: relative;
        }
        
        /* Animated background particles */
        .chat-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: 
                radial-gradient(circle at 20% 80%, rgba(120, 119, 198, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(255, 119, 198, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 40% 40%, rgba(120, 219, 255, 0.1) 0%, transparent 50%);
            animation: backgroundShift 20s ease-in-out infinite;
            pointer-events: none;
        }
        
        @keyframes backgroundShift {
            0%, 100% { opacity: 0.3; }
            50% { opacity: 0.6; }
        }
        
        .messages-container {
            flex: 1;
            overflow-y: auto;
            padding: 1rem;
            background: transparent;
            position: relative;
            z-index: 1;
        }
        
        .messages-container::-webkit-scrollbar {
            width: 6px;
        }
        
        .messages-container::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 3px;
        }
        
        .messages-container::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 3px;
        }
        
        .messages-container::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.3);
        }
        
        .message {
            max-width: 800px;
            margin: 0 auto 1.5rem auto;
            display: flex;
            gap: 0.75rem;
            opacity: 0;
            transform: translateY(20px);
            animation: messageSlideIn 0.5s ease-out forwards;
        }
        
        .message.user {
            flex-direction: row-reverse;
        }
        
        @keyframes messageSlideIn {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .message-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: 600;
            position: relative;
            transition: transform 0.2s ease;
        }
        
        .message-avatar:hover {
            transform: scale(1.1);
        }
        
        .message.user .message-avatar {
            background: linear-gradient(135deg, #10a37f, #0d8a6b);
            color: white;
            box-shadow: 0 4px 12px rgba(16, 163, 127, 0.3);
        }
        
        .message.assistant .message-avatar {
            background: linear-gradient(135deg, #5436da, #4c2db8);
            color: white;
            box-shadow: 0 4px 12px rgba(84, 54, 218, 0.3);
        }
        
        .message-content {
            flex: 1;
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 1rem 1.25rem;
            border-radius: 16px;
            line-height: 1.6;
            position: relative;
            transition: all 0.3s ease;
        }
        
        .message-content:hover {
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(255, 255, 255, 0.15);
            transform: translateY(-2px);
        }
        
        .message.user .message-content {
            background: rgba(16, 163, 127, 0.15);
            border-color: rgba(16, 163, 127, 0.3);
        }
        
        .message.user .message-content:hover {
            background: rgba(16, 163, 127, 0.2);
            border-color: rgba(16, 163, 127, 0.4);
        }
        
        .input-container {
            padding: 1.5rem;
            background: rgba(0, 0, 0, 0.4);
            backdrop-filter: blur(20px);
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            position: relative;
            z-index: 2;
        }
        
        .input-wrapper {
            max-width: 800px;
            margin: 0 auto;
            position: relative;
        }
        
        .chat-input {
            width: 100%;
            padding: 1rem 4rem 1rem 1.5rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 24px;
            font-size: 16px;
            outline: none;
            resize: none;
            min-height: 52px;
            max-height: 120px;
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            color: #ffffff;
            transition: all 0.3s ease;
            overflow: hidden;
        }
        
        .chat-input::-webkit-scrollbar {
            display: none;
        }
        
        .chat-input {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
        
        .chat-input::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }
        
        .chat-input:focus {
            border-color: #10a37f;
            box-shadow: 0 0 0 3px rgba(16, 163, 127, 0.2);
            background: rgba(255, 255, 255, 0.08);
        }
        
      
        
        .send-button {
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            width: 36px;
            height: 36px;
            border: none;
            border-radius: 50%;
            background: linear-gradient(135deg, #10a37f, #0d8a6b);
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background-color 0.3s ease;
            box-shadow: 0 4px 12px rgba(16, 163, 127, 0.3);
            font-size: 12px;
            font-weight: 600;
        }
        
        .send-button:hover {
            background: linear-gradient(135deg, #0d8a6b, #0a6b56);
        }
        
        .send-button:active {
            background: linear-gradient(135deg, #0a6b56, #085a47);
        }
        
        .send-button:disabled {
            background: rgba(255, 255, 255, 0.1);
            cursor: not-allowed;
            box-shadow: none;
        }
        
        .typing-indicator {
            display: none;
            align-items: center;
            gap: 0.5rem;
            color: rgba(255, 255, 255, 0.7);
            font-style: italic;
        }
        
        .typing-dots {
            display: flex;
            gap: 3px;
        }
        
        .typing-dot {
            width: 6px;
            height: 6px;
            background: rgba(255, 255, 255, 0.6);
            border-radius: 50%;
            animation: typing 1.4s infinite ease-in-out;
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
                opacity: 0.4;
            }
            30% {
                transform: translateY(-12px);
                opacity: 1;
            }
        }
        
        .welcome-message {
            text-align: center;
            color: rgba(255, 255, 255, 0.6);
            margin-top: 3rem;
            animation: fadeInUp 1s ease-out;
        }
        
        .welcome-message h2 {
            color: #ffffff;
            margin-bottom: 0.5rem;
            font-size: 2rem;
            font-weight: 600;
            background: linear-gradient(135deg, #ffffff, #a0a0a0);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .welcome-message p {
            font-size: 1.1rem;
            opacity: 0.8;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Glow effect for new messages */
        .message.new-message .message-content {
            animation: messageGlow 2s ease-out;
        }
        
        @keyframes messageGlow {
            0% {
                box-shadow: 0 0 20px rgba(16, 163, 127, 0.3);
            }
            100% {
                box-shadow: none;
            }
        }
        
        /* Mobile optimizations */
        @media (max-width: 768px) {
            .messages-container {
                padding: 0.75rem;
            }
            
            .message {
                margin-bottom: 1rem;
            }
            
            .message-content {
                padding: 0.875rem 1rem;
                border-radius: 12px;
            }
            
            .input-container {
                padding: 1rem;
            }
            
            .chat-input {
                padding: 0.875rem 3.5rem 0.875rem 1.25rem;
                font-size: 16px; /* Prevents zoom on iOS */
            }
            
            .send-button {
                width: 32px;
                height: 32px;
            }
        }
        
        /* Loading animation for send button */
        .send-button.loading {
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            from { transform: translateY(-50%) rotate(0deg); }
            to { transform: translateY(-50%) rotate(360deg); }
        }
        
        /* Pulse effect for input focus */
        .input-wrapper:focus-within {
            animation: inputPulse 2s ease-in-out infinite;
        }
        
        @keyframes inputPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.02); }
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="chat-container">
        <!-- Messages Container -->
        <div class="messages-container" id="messagesContainer">
            <div class="welcome-message">
                <h2>Welcome to TimGPT</h2>
                <p>Start a conversation by typing a message below</p>
            </div>
        </div>
        
        <!-- Input Container -->
        <div class="input-container">
            <div class="input-wrapper">
                <textarea 
                    id="chatInput" 
                    class="chat-input" 
                    placeholder="Message ChatGPT..."
                    rows="1"
                ></textarea>
            <button id="sendButton"></button>
            </div>
        </div>
    </div>

    <script>
        class ChatApp {
            constructor() {
                this.messagesContainer = document.getElementById('messagesContainer');
                this.chatInput = document.getElementById('chatInput');
                this.sendButton = document.getElementById('sendButton');
                this.messages = [];
                this.isTyping = false;
                
                this.init();
            }
            
            init() {
                this.setupEventListeners();
                this.autoResizeTextarea();
                this.addWelcomeEffects();
                this.createFloatingParticles();
            }
            
            setupEventListeners() {
                this.sendButton.addEventListener('click', () => this.sendMessage());
                this.chatInput.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter' && !e.shiftKey) {
                        e.preventDefault();
                        this.sendMessage();
                    }
                });
                
                // Auto-resize textarea
                this.chatInput.addEventListener('input', () => this.autoResizeTextarea());
                
                // Add focus effects
                this.chatInput.addEventListener('focus', () => this.addInputFocusEffects());
                this.chatInput.addEventListener('blur', () => this.removeInputFocusEffects());
                
                // Add typing indicator
                this.chatInput.addEventListener('input', () => this.handleTyping());
            }
            
            addWelcomeEffects() {
                // Add subtle animation to welcome message
                setTimeout(() => {
                    const welcomeMessage = document.querySelector('.welcome-message');
                    if (welcomeMessage) {
                        welcomeMessage.style.animation = 'fadeInUp 1s ease-out';
                    }
                }, 500);
            }
            
            createFloatingParticles() {
                // Create floating particles for ambient effect
                for (let i = 0; i < 5; i++) {
                    setTimeout(() => {
                        this.createParticle();
                    }, i * 2000);
                }
            }
            
            createParticle() {
                const particle = document.createElement('div');
                particle.style.cssText = `
                    position: fixed;
                    width: 4px;
                    height: 4px;
                    background: rgba(255, 255, 255, 0.1);
                    border-radius: 50%;
                    pointer-events: none;
                    z-index: 0;
                    left: ${Math.random() * 100}%;
                    top: 100%;
                    animation: floatUp 15s linear infinite;
                `;
                
                document.body.appendChild(particle);
                
                // Remove particle after animation
                setTimeout(() => {
                    if (particle.parentNode) {
                        particle.parentNode.removeChild(particle);
                    }
                }, 15000);
            }
            
            autoResizeTextarea() {
                this.chatInput.style.height = 'auto';
                this.chatInput.style.height = Math.min(this.chatInput.scrollHeight, 120) + 'px';
            }
            
            addInputFocusEffects() {
                this.chatInput.style.transform = 'scale(1.02)';
                this.chatInput.style.boxShadow = '0 0 0 3px rgba(16, 163, 127, 0.2)';
            }
            
            removeInputFocusEffects() {
                this.chatInput.style.transform = 'scale(1)';
                this.chatInput.style.boxShadow = 'none';
            }
            
            handleTyping() {
                if (!this.isTyping && this.chatInput.value.length > 0) {
                    this.isTyping = true;
                } else if (this.isTyping && this.chatInput.value.length === 0) {
                    this.isTyping = false;
                }
            }
            
            sendMessage() {
                const message = this.chatInput.value.trim();
                if (!message) return;
                
                // Add loading effect to send button
                this.sendButton.classList.add('loading');
                this.sendButton.disabled = true;
                
                // Add user message with glow effect
                this.addMessage(message, 'user', true);
                this.chatInput.value = '';
                this.autoResizeTextarea();
                this.isTyping = false;
                
                // Show typing indicator immediately
                this.showTypingIndicator();
                
                // Generate AI response (typing indicator will be hidden when response arrives)
                this.generateAIResponse(message);
            }
            
            addMessage(content, sender, isNew = false) {
                const messageDiv = document.createElement('div');
                messageDiv.className = `message ${sender}${isNew ? ' new-message' : ''}`;
                
                const avatar = document.createElement('div');
                avatar.className = 'message-avatar';
                avatar.textContent = sender === 'user' ? 'U' : 'AI';
                
                // Add avatar hover effect
                avatar.addEventListener('mouseenter', () => {
                    avatar.style.transform = 'scale(1.2)';
                });
                avatar.addEventListener('mouseleave', () => {
                    avatar.style.transform = 'scale(1)';
                });
                
                const messageContent = document.createElement('div');
                messageContent.className = 'message-content';
                messageContent.textContent = content;
                
                // Add message hover effect
                messageContent.addEventListener('mouseenter', () => {
                    messageContent.style.transform = 'translateY(-3px)';
                });
                messageContent.addEventListener('mouseleave', () => {
                    messageContent.style.transform = 'translateY(0)';
                });
                
                messageDiv.appendChild(avatar);
                messageDiv.appendChild(messageContent);
                
                this.messagesContainer.appendChild(messageDiv);
                this.scrollToBottom();
                
                // Add entrance animation
                setTimeout(() => {
                    messageDiv.style.opacity = '1';
                    messageDiv.style.transform = 'translateY(0)';
                }, 100);
                
                this.messages.push({ content, sender, timestamp: new Date() });
                
                // Remove glow effect after animation
                if (isNew) {
                    setTimeout(() => {
                        messageDiv.classList.remove('new-message');
                    }, 2000);
                }
            }
            
            showTypingIndicator() {
                const typingDiv = document.createElement('div');
                typingDiv.className = 'message assistant';
                typingDiv.id = 'typingIndicator';
                
                const avatar = document.createElement('div');
                avatar.className = 'message-avatar';
                avatar.textContent = 'AI';
                
                const typingContent = document.createElement('div');
                typingContent.className = 'message-content typing-indicator';
                typingContent.style.display = 'flex';
                typingContent.innerHTML = `
                    <span>AI is typing</span>
                    <div class="typing-dots">
                        <div class="typing-dot"></div>
                        <div class="typing-dot"></div>
                        <div class="typing-dot"></div>
                    </div>
                `;
                
                typingDiv.appendChild(avatar);
                typingDiv.appendChild(typingContent);
                
                this.messagesContainer.appendChild(typingDiv);
                this.scrollToBottom();
                
                // Add entrance animation
                setTimeout(() => {
                    typingDiv.style.opacity = '1';
                    typingDiv.style.transform = 'translateY(0)';
                }, 100);
            }
            
            hideTypingIndicator() {
                const typingIndicator = document.getElementById('typingIndicator');
                if (typingIndicator) {
                    typingIndicator.style.opacity = '0';
                    typingIndicator.style.transform = 'translateY(-20px)';
                    setTimeout(() => {
                        if (typingIndicator.parentNode) {
                            typingIndicator.parentNode.removeChild(typingIndicator);
                        }
                    }, 300);
                }
            }
            
            async generateAIResponse(userMessage) {
                try {
                    const response = await fetch('/api/chat', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                        },
                        body: JSON.stringify({
                            message: userMessage
                        })
                    });

                    const data = await response.json();

                    // Hide typing indicator and re-enable button
                    this.hideTypingIndicator();
                    this.sendButton.classList.remove('loading');
                    this.sendButton.disabled = false;

                    if (data.success) {
                        this.addMessage(data.message, 'assistant', true);
                    } else {
                        this.addMessage('Sorry, I encountered an error. Please try again.', 'assistant', true);
                        console.error('API Error:', data.error);
                    }
                } catch (error) {
                    console.error('Network Error:', error);
                    
                    // Hide typing indicator and re-enable button on error
                    this.hideTypingIndicator();
                    this.sendButton.classList.remove('loading');
                    this.sendButton.disabled = false;
                    
                    this.addMessage('Sorry, I\'m having trouble connecting. Please check your internet connection and try again.', 'assistant', true);
                }
            }
            
            scrollToBottom() {
                this.messagesContainer.scrollTop = this.messagesContainer.scrollHeight;
            }
        }
        
        // Add CSS for floating particles
        const style = document.createElement('style');
        style.textContent = `
            @keyframes floatUp {
                0% {
                    transform: translateY(0) rotate(0deg);
                    opacity: 0;
                }
                10% {
                    opacity: 1;
                }
                90% {
                    opacity: 1;
                }
                100% {
                    transform: translateY(-100vh) rotate(360deg);
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(style);
        
        // Initialize the chat app when the page loads
        document.addEventListener('DOMContentLoaded', () => {
            new ChatApp();
        });
    </script>
</body>
</html>
