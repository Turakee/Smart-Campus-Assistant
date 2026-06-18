import 'package:flutter/material.dart';
import 'package:flutter_tts/flutter_tts.dart';
import 'package:speech_to_text/speech_to_text.dart' as stt;
import '../services/api_service.dart';
import '../theme.dart';

class ChatbotScreen extends StatefulWidget {
  const ChatbotScreen({super.key});

  @override
  State<ChatbotScreen> createState() => _ChatbotScreenState();
}

class _ChatbotScreenState extends State<ChatbotScreen> {
  final _messageController = TextEditingController();
  final _scrollController = ScrollController();
  final List<ChatMessage> _messages = [];
  bool _isLoading = false;
  bool _isListening = false;
  bool _speechEnabled = false;
  bool _ttsEnabled = true;

  late stt.SpeechToText _speech;
  late FlutterTts _tts;

  final List<String> _suggestions = [
    'When is my next class?',
    'What is my attendance?',
    'Show my courses',
    'Book a room',
    'Help',
  ];

  @override
  void initState() {
    super.initState();
    _speech = stt.SpeechToText();
    _tts = FlutterTts();
    _initSpeech();
    _initTts();
    _messages.add(ChatMessage(
      text: 'Hello! I\'m your AI Campus Assistant. How can I help you today?',
      isBot: true,
    ));
  }

  Future<void> _initSpeech() async {
    final available = await _speech.initialize();
    if (mounted) setState(() => _speechEnabled = available);
  }

  Future<void> _initTts() async {
    await _tts.setLanguage('en-US');
    await _tts.setSpeechRate(0.45);
    await _tts.setPitch(1.0);
  }

  @override
  void dispose() {
    _messageController.dispose();
    _scrollController.dispose();
    _speech.stop();
    _tts.stop();
    super.dispose();
  }

  void _listen() async {
    if (!_speechEnabled) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Speech recognition not available')),
      );
      return;
    }

    if (_isListening) {
      await _speech.stop();
      if (mounted) setState(() => _isListening = false);
      return;
    }

    setState(() => _isListening = true);
    await _speech.listen(
      onResult: (result) {
        setState(() {
          _messageController.text = result.recognizedWords;
        });
      },
      onSoundLevelChange: (level) {
        // Can be used for wave animation
      },
      listenFor: const Duration(seconds: 30),
      pauseFor: const Duration(seconds: 3),
      listenOptions: stt.SpeechListenOptions(
        partialResults: true,
      ),
      localeId: 'en_US',
    );

    if (mounted) {
      setState(() => _isListening = false);
      final text = _messageController.text.trim();
      if (text.isNotEmpty) {
        _sendMessage(text);
      }
    }
  }

  Future<void> _speak(String text) async {
    if (!_ttsEnabled) return;
    try {
      if (await _tts.isLanguageAvailable('en-US') == true) {
        await _tts.speak(text);
      }
    } catch (_) {}
  }

  Future<void> _sendMessage(String text) async {
    if (text.trim().isEmpty) return;

    final msgText = text.trim();
    _messageController.clear();
    setState(() {
      _messages.add(ChatMessage(text: msgText, isBot: false));
      _isLoading = true;
    });
    _scrollToBottom();

    try {
      final response = await ApiService.chatWithAI(msgText);
      if (response['success']) {
        final data = response['data'];
        final botText = data['response'] ?? 'I\'m not sure I understand.';
        setState(() {
          _messages.add(ChatMessage(
            text: botText,
            isBot: true,
            suggestions: List<String>.from(data['suggestions'] ?? []),
            details: List<String>.from(data['details'] ?? []),
          ));
        });
        _speak(botText);
      } else {
        final errText =
            response['message'] ?? 'Sorry, I couldn\'t process your request.';
        setState(() {
          _messages.add(ChatMessage(text: errText, isBot: true));
        });
      }
    } catch (e) {
      setState(() {
        _messages.add(ChatMessage(
            text: 'Connection error. Please try again.', isBot: true));
      });
    }

    setState(() => _isLoading = false);
    _scrollToBottom();
  }

  void _scrollToBottom() {
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (_scrollController.hasClients) {
        _scrollController.animateTo(
          _scrollController.position.maxScrollExtent,
          duration: const Duration(milliseconds: 300),
          curve: Curves.easeOut,
        );
      }
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('AI Assistant'),
        actions: [
          IconButton(
            icon: Icon(
                _ttsEnabled
                    ? Icons.volume_up_rounded
                    : Icons.volume_off_rounded,
                color: _ttsEnabled ? null : AppTheme.textHint),
            tooltip: 'Toggle voice',
            onPressed: () => setState(() => _ttsEnabled = !_ttsEnabled),
          ),
        ],
      ),
      body: Container(
        decoration: BoxDecoration(
          gradient: LinearGradient(
            begin: Alignment.topCenter,
            end: Alignment.bottomCenter,
            colors: [AppTheme.primary.withOpacity(0.03), AppTheme.background],
          ),
        ),
        child: Column(
          children: [
            Expanded(
              child: ListView.builder(
                controller: _scrollController,
                padding: const EdgeInsets.fromLTRB(16, 16, 16, 8),
                itemCount: _messages.length,
                itemBuilder: (context, index) {
                  final msg = _messages[index];
                  return _buildMessageBubble(msg);
                },
              ),
            ),
            if (_messages.length <= 2)
              Padding(
                padding: const EdgeInsets.fromLTRB(16, 0, 16, 8),
                child: SizedBox(
                  width: double.infinity,
                  child: Wrap(
                    spacing: 8,
                    runSpacing: 8,
                    children: _suggestions
                        .map((s) => Material(
                              color: AppTheme.primary.withOpacity(0.08),
                              borderRadius: BorderRadius.circular(20),
                              child: InkWell(
                                borderRadius: BorderRadius.circular(20),
                                onTap: () => _sendMessage(s),
                                child: Padding(
                                  padding: const EdgeInsets.symmetric(
                                      horizontal: 12, vertical: 8),
                                  child: Row(
                                    mainAxisSize: MainAxisSize.min,
                                    children: [
                                      const Icon(
                                          Icons.chat_bubble_outline_rounded,
                                          size: 14,
                                          color: AppTheme.primary),
                                      const SizedBox(width: 6),
                                      Text(s,
                                          style: const TextStyle(
                                              fontSize: 13,
                                              color: AppTheme.textPrimary)),
                                    ],
                                  ),
                                ),
                              ),
                            ))
                        .toList(),
                  ),
                ),
              ),
            if (_isLoading)
              Container(
                padding:
                    const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                alignment: Alignment.centerLeft,
                child: Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Container(
                      padding: const EdgeInsets.all(8),
                      decoration: BoxDecoration(
                        color: AppTheme.surface,
                        borderRadius: BorderRadius.circular(16),
                        border: Border.all(color: AppTheme.border),
                      ),
                      child: Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          _dot(0),
                          const SizedBox(width: 4),
                          _dot(400),
                          const SizedBox(width: 4),
                          _dot(800),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
            _buildInputBar(),
          ],
        ),
      ),
    );
  }

  Widget _dot(int delay) {
    return AnimatedOpacity(
      opacity: 1,
      duration: const Duration(milliseconds: 600),
      child: TweenAnimationBuilder<double>(
        tween: Tween(begin: 0.3, end: 1.0),
        duration: const Duration(milliseconds: 600),
        builder: (context, value, child) {
          return Container(
            width: 8,
            height: 8,
            decoration: BoxDecoration(
              color: AppTheme.primary.withOpacity(value),
              shape: BoxShape.circle,
            ),
          );
        },
      ),
    );
  }

  Widget _buildMessageBubble(ChatMessage msg) {
    return Column(
      crossAxisAlignment:
          msg.isBot ? CrossAxisAlignment.start : CrossAxisAlignment.end,
      children: [
        Container(
          margin: const EdgeInsets.only(bottom: 4),
          constraints: BoxConstraints(
              maxWidth: MediaQuery.of(context).size.width * 0.78),
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
          decoration: BoxDecoration(
            color: msg.isBot ? AppTheme.surface : AppTheme.primary,
            borderRadius: BorderRadius.only(
              topLeft: const Radius.circular(18),
              topRight: const Radius.circular(18),
              bottomLeft: msg.isBot ? Radius.zero : const Radius.circular(18),
              bottomRight: msg.isBot ? const Radius.circular(18) : Radius.zero,
            ),
            boxShadow: msg.isBot
                ? [
                    BoxShadow(
                        color: Colors.black.withOpacity(0.06),
                        blurRadius: 4,
                        offset: const Offset(0, 2))
                  ]
                : [],
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              if (msg.isBot)
                Padding(
                  padding: const EdgeInsets.only(bottom: 6),
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Container(
                        padding: const EdgeInsets.all(3),
                        decoration: const BoxDecoration(
                          color: AppTheme.primary,
                          shape: BoxShape.circle,
                        ),
                        child: const Icon(Icons.smart_toy_rounded,
                            size: 12, color: Colors.white),
                      ),
                      const SizedBox(width: 6),
                      const Text('AI Assistant',
                          style: TextStyle(
                              fontSize: 11,
                              color: AppTheme.primary,
                              fontWeight: FontWeight.bold)),
                    ],
                  ),
                ),
              Text(
                msg.text,
                style: TextStyle(
                  color: msg.isBot ? AppTheme.textPrimary : Colors.white,
                  fontSize: 15,
                  height: 1.4,
                ),
              ),
            ],
          ),
        ),
        if (msg.isBot)
          Padding(
            padding: const EdgeInsets.only(left: 8, bottom: 4),
            child: Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                GestureDetector(
                  onTap: () => _speak(msg.text),
                  child: const Icon(Icons.volume_up_rounded,
                      size: 14, color: AppTheme.textHint),
                ),
                const SizedBox(width: 12),
                GestureDetector(
                  onTap: () {
                    setState(() {
                      _messages.removeWhere((m) => m == msg);
                    });
                  },
                  child: const Icon(Icons.delete_outline_rounded,
                      size: 14, color: AppTheme.textHint),
                ),
              ],
            ),
          ),
        if (msg.details != null && msg.details!.isNotEmpty)
          Container(
            margin: EdgeInsets.only(
                left: msg.isBot ? 16 : 0, right: msg.isBot ? 0 : 16, bottom: 4),
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(
              color: AppTheme.surface,
              borderRadius: BorderRadius.circular(12),
              border: Border.all(color: AppTheme.border),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: msg.details!
                  .map((d) => Padding(
                        padding: const EdgeInsets.symmetric(vertical: 2),
                        child: Row(
                          children: [
                            const Icon(Icons.check_circle_rounded,
                                size: 14, color: AppTheme.secondary),
                            const SizedBox(width: 8),
                            Expanded(
                                child: Text(d,
                                    style: const TextStyle(
                                        fontSize: 13,
                                        color: AppTheme.textSecondary))),
                          ],
                        ),
                      ))
                  .toList(),
            ),
          ),
        if (msg.suggestions != null && msg.suggestions!.isNotEmpty)
          Padding(
            padding: EdgeInsets.only(
                left: msg.isBot ? 16 : 0,
                right: msg.isBot ? 0 : 16,
                bottom: 12),
            child: Wrap(
              spacing: 8,
              runSpacing: 4,
              children: msg.suggestions!
                  .map((s) => ActionChip(
                        label: Text(s, style: const TextStyle(fontSize: 12)),
                        onPressed: () => _sendMessage(s),
                        padding: const EdgeInsets.symmetric(horizontal: 4),
                        visualDensity: VisualDensity.compact,
                      ))
                  .toList(),
            ),
          ),
      ],
    );
  }

  Widget _buildInputBar() {
    return Container(
      padding: const EdgeInsets.fromLTRB(16, 8, 16, 16),
      decoration: BoxDecoration(
        color: AppTheme.surface,
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.08),
            blurRadius: 8,
            offset: const Offset(0, -2),
          ),
        ],
      ),
      child: Row(
        children: [
          Expanded(
            child: TextField(
              controller: _messageController,
              decoration: InputDecoration(
                hintText: 'Ask me anything...',
                hintStyle: const TextStyle(color: AppTheme.textHint),
                border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(24),
                  borderSide: BorderSide.none,
                ),
                filled: true,
                fillColor: AppTheme.background,
                contentPadding:
                    const EdgeInsets.symmetric(horizontal: 20, vertical: 12),
              ),
              onSubmitted: (v) => _sendMessage(v),
              textInputAction: TextInputAction.send,
            ),
          ),
          const SizedBox(width: 8),
          Container(
            decoration: BoxDecoration(
              color: _isListening ? AppTheme.error : AppTheme.primary,
              shape: BoxShape.circle,
            ),
            child: IconButton(
              onPressed: _listen,
              icon: Icon(
                _isListening ? Icons.mic_rounded : Icons.mic_none_rounded,
                color: Colors.white,
              ),
            ),
          ),
          const SizedBox(width: 8),
          Container(
            decoration: const BoxDecoration(
              color: AppTheme.primary,
              shape: BoxShape.circle,
            ),
            child: IconButton(
              onPressed: () => _sendMessage(_messageController.text),
              icon: const Icon(Icons.send_rounded, color: Colors.white),
            ),
          ),
        ],
      ),
    );
  }
}

class ChatMessage {
  final String text;
  final bool isBot;
  final List<String>? suggestions;
  final List<String>? details;

  ChatMessage({
    required this.text,
    required this.isBot,
    this.suggestions,
    this.details,
  });
}
