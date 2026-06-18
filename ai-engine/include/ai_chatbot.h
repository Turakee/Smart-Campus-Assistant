/**
 * AI Chatbot Engine
 * AI-Powered Smart Campus Assistant
 * 
 * Implements rule-based chatbot with keyword recognition
 * and intent classification for academic queries.
 */

#ifndef AI_CHATBOT_H
#define AI_CHATBOT_H

#include "ai_core.h"

namespace AICampus {

/**
 * Intent types for chatbot
 */
enum class Intent {
    GREETING,
    SCHEDULE_QUERY,
    ATTENDANCE_QUERY,
    COURSE_QUERY,
    BOOKING_QUERY,
    GENERAL_HELP,
    GOODBYE,
    UNKNOWN
};

/**
 * Chat Context
 */
struct ChatContext {
    std::string student_name;
    std::string department;
    std::string current_course;
    std::map<std::string, std::string> session_data;
};

/**
 * Response Template
 */
struct ResponseTemplate {
    std::string primary;
    std::string follow_up;
    std::vector<std::string> suggestions;
};

/**
 * Chatbot Engine Class
 * 
 * Processes user queries using:
 * - Rule-based intent classification
 * - Keyword recognition
 * - Context-aware responses
 */
class AIChatbot {
private:
    Logger* logger;
    ChatContext context;
    
    // Intent patterns
    std::map<Intent, std::vector<std::string>> intentPatterns;
    
    // Response templates
    std::map<Intent, ResponseTemplate> responses;
    
    void initializePatterns();
    void initializeResponses();
    
    Intent classifyIntent(const std::string& query);
    std::string extractEntity(const std::string& query, const std::string& type);
    std::string generateResponse(Intent intent, const std::string& query);
    
public:
    AIChatbot(Logger* log = nullptr) : logger(log) {
        initializePatterns();
        initializeResponses();
    }
    
    void setContext(const ChatContext& ctx) {
        context = ctx;
    }
    
    ChatContext getContext() const {
        return context;
    }
    
    ChatbotResponse processQuery(const std::string& query);
    std::string getHelp();
    std::string getGreeting();
};

// ============================================================================
// IMPLEMENTATION
// ============================================================================

void AIChatbot::initializePatterns() {
    // Schedule patterns
    intentPatterns[Intent::SCHEDULE_QUERY] = {
        "schedule", "class", "timetable", "when", "time", "day",
        "next class", "class time", "class schedule", " timetable",
        "when is", "what time", "which day", "slot", "period"
    };
    
    // Attendance patterns
    intentPatterns[Intent::ATTENDANCE_QUERY] = {
        "attendance", "present", "absent", "miss", "attended",
        "attendance percentage", "my attendance", "present count",
        "how many", "attendance record", "attendance status"
    };
    
    // Course patterns
    intentPatterns[Intent::COURSE_QUERY] = {
        "course", "subject", "class", "lecture", "credit",
        "enrolled", "my course", "course list", "which course"
    };
    
    // Booking patterns
    intentPatterns[Intent::BOOKING_QUERY] = {
        "book", "booking", "reserve", "room", "lab", "facility",
        "classroom", "schedule room", "book room", "reservation"
    };
    
    // Help patterns
    intentPatterns[Intent::GENERAL_HELP] = {
        "help", "assist", "support", "how", "what can", "available",
        "features", "commands", "options", "guide"
    };
    
    // Greeting patterns
    intentPatterns[Intent::GREETING] = {
        "hello", "hi", "hey", "good morning", "good afternoon",
        "good evening", "greetings", "howdy", "yo"
    };
    
    // Goodbye patterns
    intentPatterns[Intent::GOODBYE] = {
        "bye", "goodbye", "see you", "later", "thanks", "thank you",
        "exit", "quit", "done", "finished"
    };
}

void AIChatbot::initializeResponses() {
    responses[Intent::GREETING] = {
        "Hello! I'm your AI Campus Assistant. How can I help you today?",
        "I can assist you with schedules, attendance, courses, and more.",
        {"View my schedule", "Check attendance", "Help with booking"}
    };
    
    responses[Intent::SCHEDULE_QUERY] = {
        "I can help you with your class schedule.",
        "Would you like to see your schedule for today or this week?",
        {"Show today's classes", "View weekly schedule", "Next class"}
    };
    
    responses[Intent::ATTENDANCE_QUERY] = {
        "I can check your attendance records.",
        "Your current attendance percentage and status will be displayed.",
        {"Show attendance details", "View attendance history", "Attendance report"}
    };
    
    responses[Intent::COURSE_QUERY] = {
        "I can show you your enrolled courses and course information.",
        "What would you like to know about your courses?",
        {"List my courses", "Course details", "Credit hours"}
    };
    
    responses[Intent::BOOKING_QUERY] = {
        "I can help you book campus facilities.",
        "Would you like to book a classroom, lab, or other facility?",
        {"Book a room", "View my bookings", "Cancel booking"}
    };
    
    responses[Intent::GENERAL_HELP] = {
        "I'm your AI Campus Assistant. Here's what I can help with:",
        "You can ask me about schedules, attendance, courses, or book facilities.",
        {
            "When is my next class?",
            "What's my attendance percentage?",
            "Show my enrolled courses",
            "Help with room booking"
        }
    };
    
    responses[Intent::GOODBYE] = {
        "Goodbye! Have a great day!",
        "Feel free to return if you need any assistance.",
        {}
    };
    
    responses[Intent::UNKNOWN] = {
        "I'm not sure I understand that query.",
        "Try asking about schedules, attendance, courses, or room booking.",
        {"Help", "Show my schedule", "Check attendance"}
    };
}

Intent AIChatbot::classifyIntent(const std::string& query) {
    std::string lowerQuery = toLower(query);
    
    int maxMatches = 0;
    Intent bestIntent = Intent::UNKNOWN;
    
    for (const auto& pair : intentPatterns) {
        int matches = 0;
        for (const auto& pattern : pair.second) {
            if (lowerQuery.find(pattern) != std::string::npos) {
                matches++;
            }
        }
        
        if (matches > maxMatches) {
            maxMatches = matches;
            bestIntent = pair.first;
        }
    }
    
    return bestIntent;
}

std::string AIChatbot::extractEntity(const std::string& query, const std::string& type) {
    std::string lowerQuery = toLower(query);
    
    if (type == "day") {
        std::vector<std::string> days = {
            "monday", "tuesday", "wednesday", "thursday", "friday", "saturday", "sunday",
            "today", "tomorrow", "week"
        };
        for (const auto& day : days) {
            if (lowerQuery.find(day) != std::string::npos) {
                return day;
            }
        }
    }
    
    if (type == "time") {
        // Simple time extraction
        size_t pos = lowerQuery.find("at");
        if (pos != std::string::npos && pos + 3 < query.length()) {
            return query.substr(pos + 3, 5);
        }
    }
    
    return "";
}

std::string AIChatbot::generateResponse(Intent intent, const std::string& query) {
    auto it = responses.find(intent);
    if (it == responses.end()) {
        return responses[Intent::UNKNOWN].primary;
    }
    
    return it->second.primary;
}

inline std::string getIntentName(Intent intent);

ChatbotResponse AIChatbot::processQuery(const std::string& query) {
    ChatbotResponse response;
    
    if (logger) {
        logger->info("Processing chatbot query: " + query);
    }
    
    // Classify intent
    Intent intent = classifyIntent(query);
    response.intent = getIntentName(intent);
    
    // Generate response
    response.response = generateResponse(intent, query);
    response.success = true;
    
    // Populate suggestions from response template
    auto it = responses.find(intent);
    if (it != responses.end()) {
        response.suggestions = it->second.suggestions;
    }
    
    // Add context-specific information
    if (intent == Intent::SCHEDULE_QUERY) {
        std::string day = extractEntity(query, "day");
        if (!day.empty()) {
            response.response += " [Filtering by: " + day + "]";
        }
        response.details.push_back("Today's schedule loaded from your courses");
    } else if (intent == Intent::ATTENDANCE_QUERY) {
        response.details.push_back("Attendance data from your academic records");
    } else if (intent == Intent::COURSE_QUERY) {
        response.details.push_back("Course information from your enrollment");
    }
    
    if (logger) {
        logger->info("Intent classified as: " + response.intent);
    }
    
    return response;
}

std::string AIChatbot::getHelp() {
    return responses[Intent::GENERAL_HELP].primary + "\n\n" +
           "Available commands:\n" +
           "- View my schedule\n" +
           "- Check attendance\n" +
           "- Show my courses\n" +
           "- Book a room\n" +
           "- Help\n" +
           "- Exit";
}

std::string AIChatbot::getGreeting() {
    return responses[Intent::GREETING].primary;
}

// Helper function to get intent name
inline std::string getIntentName(Intent intent) {
    switch (intent) {
        case Intent::GREETING: return "greeting";
        case Intent::SCHEDULE_QUERY: return "schedule_query";
        case Intent::ATTENDANCE_QUERY: return "attendance_query";
        case Intent::COURSE_QUERY: return "course_query";
        case Intent::BOOKING_QUERY: return "booking_query";
        case Intent::GENERAL_HELP: return "help";
        case Intent::GOODBYE: return "goodbye";
        default: return "unknown";
    }
}

} // namespace AICampus

#endif // AI_CHATBOT_H
