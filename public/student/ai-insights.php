<?php
require_once '../../config/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: ../index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Insights - Smart Campus Assistant</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🎓</text></svg>">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        .chatbot-container {
            background: var(--light);
            border-radius: var(--radius);
            padding: 20px;
        }
        .chat-messages {
            height: 250px;
            overflow-y: auto;
            margin-bottom: 15px;
            padding: 10px;
            background: white;
            border-radius: var(--radius-sm);
            border: 1px solid #e0e0e0;
        }
        .chat-message {
            margin-bottom: 12px;
            display: flex;
        }
        .chat-message.user {
            justify-content: flex-end;
        }
        .chat-message .message-content {
            max-width: 80%;
            padding: 10px 15px;
            border-radius: 15px;
            font-size: 14px;
            line-height: 1.4;
        }
        .chat-message.bot .message-content {
            background: var(--primary);
            color: white;
            border-bottom-left-radius: 5px;
        }
        .chat-message.bot .message-content i {
            margin-right: 8px;
        }
        .chat-message.user .message-content {
            background: #e3f2fd;
            color: #1565c0;
            border-bottom-right-radius: 5px;
        }
        .suggestion-btns {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 8px;
        }
        .suggestion-btn {
            padding: 6px 12px;
            font-size: 12px;
            background: white;
            border: 1px solid var(--primary);
            color: var(--primary);
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .suggestion-btn:hover {
            background: var(--primary);
            color: white;
        }
        .chat-input-container {
            display: flex;
            gap: 10px;
        }
        .chat-input-container input {
            flex: 1;
            padding: 12px 15px;
            border: 1px solid #e0e0e0;
            border-radius: var(--radius-sm);
            font-size: 14px;
        }
        .chat-input-container input:focus {
            outline: none;
            border-color: var(--primary);
        }
        .chat-input-container button {
            padding: 12px 20px;
        }
        .typing-indicator {
            display: flex;
            gap: 4px;
            padding: 10px 15px;
            background: var(--primary);
            color: white;
            border-radius: 15px;
            width: fit-content;
        }
        .typing-indicator span {
            width: 8px;
            height: 8px;
            background: white;
            border-radius: 50%;
            animation: bounce 1.4s infinite ease-in-out;
        }
        .typing-indicator span:nth-child(1) { animation-delay: -0.32s; }
        .typing-indicator span:nth-child(2) { animation-delay: -0.16s; }
        @keyframes bounce {
            0%, 80%, 100% { transform: scale(0); }
            40% { transform: scale(1); }
        }
    </style>
</head>
<body>
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="logo-icon"><i class="fas fa-graduation-cap"></i></div>
            <h3>Smart Campus Assistant</h3>
        </div>
        <nav class="sidebar-nav">
            <a href="dashboard.php" class="nav-item"><i class="fas fa-home"></i> Dashboard</a>
            <a href="schedule.php" class="nav-item"><i class="fas fa-calendar-alt"></i> Schedule</a>
            <a href="attendance.php" class="nav-item"><i class="fas fa-clipboard-check"></i> Attendance</a>
            <a href="courses.php" class="nav-item"><i class="fas fa-book"></i> Courses</a>
            <a href="booking.php" class="nav-item"><i class="fas fa-door-open"></i> Bookings</a>
            <a href="ai-insights.php" class="nav-item active"><i class="fas fa-robot"></i> AI Insights</a>
            <a href="profile.php" class="nav-item"><i class="fas fa-user-circle"></i> Profile</a>
            <a href="#" onclick="logout()" class="nav-item"><i class="fas fa-right-from-bracket"></i> Logout</a>
        </nav>
    </aside>

    <div class="sidebar-backdrop" onclick="toggleSidebar()"></div>

    <main class="main-content">
        <header class="top-header">
            <div class="header-left">
                <button class="mobile-toggle" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
                <h2><i class="fas fa-robot"></i> AI Academic Insights</h2>
            </div>
            <div class="user-profile">
                <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['name'], 0, 1)); ?></div>
                <span><?php echo htmlspecialchars($_SESSION['name']); ?></span>
            </div>
        </header>

        <div class="dashboard-container">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon purple"><i class="fas fa-brain"></i></div>
                    <div class="stat-details">
                        <h3 id="aiScore">-</h3>
                        <p>AI Health Score</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon green"><i class="fas fa-check-circle"></i></div>
                    <div class="stat-details">
                        <h3 id="attendancePercent">0%</h3>
                        <p>Attendance Rate</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon orange"><i class="fas fa-exclamation-triangle"></i></div>
                    <div class="stat-details">
                        <h3 id="riskLevel">-</h3>
                        <p>Risk Level</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon teal"><i class="fas fa-calendar-check"></i></div>
                    <div class="stat-details">
                        <h3 id="optimizationScore">-</h3>
                        <p>Schedule Efficiency</p>
                    </div>
                </div>
            </div>

            <div class="section-card">
                <div class="section-header">
                    <h3><i class="fas fa-heart-pulse"></i> Attendance Risk Analysis</h3>
                    <button class="btn btn-primary" onclick="runAttendancePrediction()">
                        <i class="fas fa-sync"></i> Refresh Analysis
                    </button>
                </div>
                <div id="attendanceInsight" class="insight-card">
                    <div class="notification-empty">
                        <div class="loading-spinner"></div>
                        <p>Click refresh to analyze your attendance</p>
                    </div>
                </div>
            </div>

            <div class="section-card">
                <div class="section-header">
                    <h3><i class="fas fa-wand-magic"></i> Schedule Optimization</h3>
                    <button class="btn btn-primary" onclick="runScheduleOptimization()">
                        <i class="fas fa-wand-magic-sparkles"></i> Optimize Now
                    </button>
                </div>
                <div id="scheduleInsight" class="insight-card">
                    <div class="notification-empty">
                        <i class="fas fa-calendar-alt" style="font-size: 48px;"></i>
                        <p>Click optimize to analyze your schedule</p>
                    </div>
                </div>
            </div>

            <div class="section-card">
                <div class="section-header">
                    <h3><i class="fas fa-graduation-cap"></i> Performance Prediction</h3>
                    <button class="btn btn-primary" onclick="runPerformancePrediction()">
                        <i class="fas fa-sync"></i> Predict Grade
                    </button>
                </div>
                <div id="performanceInsight" class="insight-card">
                    <div class="notification-empty">
                        <i class="fas fa-chart-line" style="font-size: 48px;"></i>
                        <p>Click predict to analyze your academic performance</p>
                    </div>
                </div>
            </div>

            <div class="section-card">
                <div class="section-header">
                    <h3><i class="fas fa-lightbulb"></i> AI Recommendations</h3>
                </div>
                <ul class="recommendation-list" id="recommendationList">
                    <li style="color: var(--gray);">
                        <i class="fas fa-info-circle" style="color: var(--gray);"></i>
                        Run the attendance analysis to get personalized recommendations
                    </li>
                </ul>
            </div>

            <div class="section-card">
                <div class="section-header">
                    <h3><i class="fas fa-comments"></i> AI Campus Assistant</h3>
                </div>
                <div class="chatbot-container">
                    <div class="chat-messages" id="chatMessages">
                        <div class="chat-message bot">
                            <div class="message-content">
                                <i class="fas fa-robot"></i>
                                Hello! I'm your AI Campus Assistant. How can I help you today?
                            </div>
                        </div>
                    </div>
                    <div class="chat-input-container">
                        <input type="text" id="chatInput" placeholder="Ask me anything about your schedule, attendance, courses..." onkeypress="handleChatKeypress(event)">
                        <button class="btn btn-secondary" id="voiceBtn" onclick="toggleVoiceInput()" title="Voice Input">
                            <i class="fas fa-microphone"></i>
                        </button>
                        <button class="btn btn-primary" onclick="sendChatMessage()">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="../assets/js/student.js?v=<?php echo filemtime('../assets/js/student.js'); ?>"></script>
    <script>
        async function sendChatMessage() {
            const input = document.getElementById('chatInput');
            const query = input.value.trim();
            if (!query) return;
            
            addChatMessage(query, 'user');
            input.value = '';
            showTypingIndicator();
            
            try {
                const response = await fetch('../../api/ai/chatbot.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ query: query })
                });
                
                const data = await response.json();
                
                removeTypingIndicator();
                
                if (data.success && data.data) {
                    addChatMessage(data.data.response, 'bot');
                    
                    if (data.data.suggestions && data.data.suggestions.length > 0) {
                        addSuggestions(data.data.suggestions);
                    }
                    
                    if (data.data.details) {
                        addChatDetails(data.data.details);
                    }
                } else {
                    addChatMessage(data.message || 'Sorry, I couldn\'t process your request. Please try again.', 'bot');
                }
            } catch (error) {
                removeTypingIndicator();
                console.error('Chat error:', error);
                addChatMessage('Sorry, I\'m having trouble connecting. Error: ' + (error.message || 'Connection failed'), 'bot');
            }
        }

        function handleChatKeypress(event) {
            if (event.key === 'Enter') sendChatMessage();
        }

        function addChatMessage(content, type) {
            const container = document.getElementById('chatMessages');
            const messageDiv = document.createElement('div');
            messageDiv.className = 'chat-message ' + type;
            
            const contentDiv = document.createElement('div');
            contentDiv.className = 'message-content';
            
            if (type === 'bot') {
                contentDiv.innerHTML = '<i class="fas fa-robot"></i>' + escapeHtml(content);
            } else {
                contentDiv.textContent = content;
            }
            
            messageDiv.appendChild(contentDiv);
            container.appendChild(messageDiv);
            container.scrollTop = container.scrollHeight;
        }

        function addSuggestions(suggestions) {
            const container = document.getElementById('chatMessages');
            const messageDiv = document.createElement('div');
            messageDiv.className = 'chat-message bot';
            
            const contentDiv = document.createElement('div');
            contentDiv.className = 'message-content';
            
            const btnsDiv = document.createElement('div');
            btnsDiv.className = 'suggestion-btns';
            
            suggestions.forEach(s => {
                const btn = document.createElement('button');
                btn.className = 'suggestion-btn';
                btn.textContent = s;
                btn.onclick = () => {
                    document.getElementById('chatInput').value = s;
                    sendChatMessage();
                };
                btnsDiv.appendChild(btn);
            });
            
            contentDiv.appendChild(btnsDiv);
            messageDiv.appendChild(contentDiv);
            container.appendChild(messageDiv);
            container.scrollTop = container.scrollHeight;
        }

        function addChatDetails(details) {
            const container = document.getElementById('chatMessages');
            const messageDiv = document.createElement('div');
            messageDiv.className = 'chat-message bot';
            
            const contentDiv = document.createElement('div');
            contentDiv.className = 'message-content';
            
            const ul = document.createElement('ul');
            ul.style.margin = '10px 0 0 0';
            ul.style.paddingLeft = '20px';
            
            details.forEach(d => {
                const li = document.createElement('li');
                li.textContent = d;
                ul.appendChild(li);
            });
            
            contentDiv.innerHTML = '<i class="fas fa-robot"></i>';
            contentDiv.appendChild(ul);
            messageDiv.appendChild(contentDiv);
            container.appendChild(messageDiv);
            container.scrollTop = container.scrollHeight;
        }

        function showTypingIndicator() {
            const container = document.getElementById('chatMessages');
            const indicator = document.createElement('div');
            indicator.id = 'typingIndicator';
            indicator.className = 'chat-message bot';
            indicator.innerHTML = '<div class="typing-indicator"><span></span><span></span><span></span></div>';
            container.appendChild(indicator);
            container.scrollTop = container.scrollHeight;
        }

        function removeTypingIndicator() {
            const indicator = document.getElementById('typingIndicator');
            if (indicator) indicator.remove();
        }

        // Voice Input Functionality
        let recognition = null;
        let isListening = false;

        function toggleVoiceInput() {
            if (!('webkitSpeechRecognition' in window) && !('SpeechRecognition' in window)) {
                alert('Voice input is not supported in your browser. Please use Chrome or Edge.');
                return;
            }

            const voiceBtn = document.getElementById('voiceBtn');
            const chatInput = document.getElementById('chatInput');

            if (isListening) {
                stopVoiceInput();
            } else {
                startVoiceInput();
            }
        }

        function startVoiceInput() {
            const voiceBtn = document.getElementById('voiceBtn');
            const chatInput = document.getElementById('chatInput');

            // Initialize speech recognition
            const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
            recognition = new SpeechRecognition();
            recognition.continuous = false;
            recognition.interimResults = false;
            recognition.lang = 'en-US';

            recognition.onstart = function() {
                isListening = true;
                voiceBtn.classList.add('btn-danger');
                voiceBtn.classList.remove('btn-secondary');
                voiceBtn.innerHTML = '<i class="fas fa-stop"></i>';
                chatInput.placeholder = 'Listening... Speak now';
                addChatMessage('🎤 Listening... Please speak your question', 'bot');
            };

            recognition.onresult = function(event) {
                const transcript = event.results[0][0].transcript;
                chatInput.value = transcript;
                stopVoiceInput();
                // Auto-send after voice input
                setTimeout(() => sendChatMessage(), 500);
            };

            recognition.onerror = function(event) {
                console.error('Speech recognition error:', event.error);
                stopVoiceInput();
                addChatMessage('❌ Voice input failed. Please try again or type your message.', 'bot');
            };

            recognition.onend = function() {
                stopVoiceInput();
            };

            recognition.start();
        }

        function stopVoiceInput() {
            if (recognition) {
                recognition.stop();
            }
            
            const voiceBtn = document.getElementById('voiceBtn');
            const chatInput = document.getElementById('chatInput');
            
            isListening = false;
            voiceBtn.classList.remove('btn-danger');
            voiceBtn.classList.add('btn-secondary');
            voiceBtn.innerHTML = '<i class="fas fa-microphone"></i>';
            chatInput.placeholder = 'Ask me anything about your schedule, attendance, courses...';
        }
    </script>
</body>
</html>
