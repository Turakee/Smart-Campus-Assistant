/**
 * AI Engine Core Header
 * AI-Powered Smart Campus Assistant
 * 
 * This header defines the core data structures and interfaces
 * for the C++ AI processing engine.
 */

#ifndef AI_ENGINE_CORE_H
#define AI_ENGINE_CORE_H

#include <string>
#include <vector>
#include <map>
#include <unordered_map>
#include <set>
#include <iostream>
#include <fstream>
#include <sstream>
#include <algorithm>
#include <cmath>
#include <ctime>
#include <iomanip>

namespace AICampus {

// ============================================================================
// DATA STRUCTURES
// ============================================================================

struct TimeSlot {
    std::string start_time;
    std::string end_time;
    
    TimeSlot() {}
    TimeSlot(const std::string& start, const std::string& end) 
        : start_time(start), end_time(end) {}
    
    bool overlaps(const TimeSlot& other) const {
        return !(end_time <= other.start_time || start_time >= other.end_time);
    }
    
    int getDurationMinutes() const {
        int startMins = timeToMinutes(start_time);
        int endMins = timeToMinutes(end_time);
        return endMins - startMins;
    }
    
    static int timeToMinutes(const std::string& time) {
        int hours, mins;
        sscanf(time.c_str(), "%d:%d", &hours, &mins);
        return hours * 60 + mins;
    }
};

struct Course {
    int course_id;
    std::string course_name;
    std::string course_code;
    int credit_hours;
    
    Course() : course_id(0), credit_hours(0) {}
    Course(int id, const std::string& name, const std::string& code, int credits)
        : course_id(id), course_name(name), course_code(code), credit_hours(credits) {}
};

struct ScheduleEntry {
    int schedule_id;
    int course_id;
    std::string day_of_week;
    TimeSlot time_slot;
    std::string room_number;
    std::string course_code;
    
    ScheduleEntry() : schedule_id(0), course_id(0) {}
};

struct AttendanceRecord {
    std::string date;
    std::string status;
    
    AttendanceRecord() {}
    AttendanceRecord(const std::string& d, const std::string& s) : date(d), status(s) {}
};

struct StudentData {
    int student_id;
    std::vector<Course> courses;
    std::vector<ScheduleEntry> schedules;
    std::vector<AttendanceRecord> attendance_records;
    
    StudentData() : student_id(0) {}
};

struct RiskPrediction {
    double attendance_percentage;
    int total_classes;
    int present_count;
    int absent_count;
    int consecutive_absences;
    std::string risk_level;
    double risk_score;
    std::vector<std::string> recommendations;
    
    RiskPrediction() : attendance_percentage(0.0), total_classes(0), 
                       present_count(0), absent_count(0), 
                       consecutive_absences(0), risk_score(0.0) {}
};

struct OptimizationResult {
    bool success;
    std::string message;
    std::vector<ScheduleEntry> optimized_slots;
    int conflicts_resolved;
    double optimization_score;
    
    OptimizationResult() : success(false), conflicts_resolved(0), optimization_score(0.0) {}
};

struct ChatbotResponse {
    std::string intent;
    std::string response;
    bool success;
    std::vector<std::string> suggestions;
    std::vector<std::string> details;
    
    ChatbotResponse() : success(false) {}
};

// ============================================================================
// CONFIGURATION
// ============================================================================

struct AIConfig {
    double attendance_threshold_low = 85.0;
    double attendance_threshold_medium = 75.0;
    int max_consecutive_absences_warning = 3;
    int max_hours_per_day = 6;
    int min_break_minutes = 30;
    int min_classes_for_prediction = 3;
    
    std::vector<std::string> preferred_times = {"08:00", "10:00", "14:00"};
    
    std::vector<std::string> days_order = {
        "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"
    };
};

// ============================================================================
// JSON UTILITIES
// ============================================================================

class JSONParser {
public:
    static std::string escapeString(const std::string& s) {
        std::string result;
        for (char c : s) {
            if (c == '"') result += "\\\"";
            else if (c == '\\') result += "\\\\";
            else if (c == '\n') result += "\\n";
            else if (c == '\r') result += "\\r";
            else if (c == '\t') result += "\\t";
            else result += c;
        }
        return result;
    }
    
    static std::string intToString(int val) {
        return std::to_string(val);
    }
    
    static std::string doubleToString(double val, int precision = 2) {
        std::ostringstream ss;
        ss << std::fixed << std::setprecision(precision) << val;
        return ss.str();
    }
    
    static int stringToInt(const std::string& s) {
        return std::stoi(s);
    }
    
    static double stringToDouble(const std::string& s) {
        return std::stod(s);
    }
};

// ============================================================================
// LOGGING
// ============================================================================

class Logger {
private:
    std::ofstream logFile;
    bool initialized;
    
public:
    Logger() : initialized(false) {}
    
    bool init(const std::string& logPath) {
        logFile.open(logPath, std::ios::app);
        initialized = logFile.is_open();
        return initialized;
    }
    
    void log(const std::string& level, const std::string& message) {
        if (!initialized) return;
        
        time_t now = time(nullptr);
        char timestamp[32];
        strftime(timestamp, sizeof(timestamp), "%Y-%m-%d %H:%M:%S", localtime(&now));
        
        logFile << "[" << timestamp << "] [" << level << "] " << message << std::endl;
        logFile.flush();
    }
    
    void info(const std::string& msg) { log("INFO", msg); }
    void error(const std::string& msg) { log("ERROR", msg); }
    void warn(const std::string& msg) { log("WARN", msg); }
    
    ~Logger() {
        if (logFile.is_open()) logFile.close();
    }
};

// ============================================================================
// UTILITY FUNCTIONS
// ============================================================================

inline std::string trim(const std::string& str) {
    size_t start = str.find_first_not_of(" \t\n\r");
    if (start == std::string::npos) return "";
    size_t end = str.find_last_not_of(" \t\n\r");
    return str.substr(start, end - start + 1);
}

inline std::vector<std::string> split(const std::string& str, char delimiter) {
    std::vector<std::string> tokens;
    std::string token;
    std::istringstream iss(str);
    while (std::getline(iss, token, delimiter)) {
        tokens.push_back(trim(token));
    }
    return tokens;
}

inline std::string toLower(const std::string& str) {
    std::string result = str;
    std::transform(result.begin(), result.end(), result.begin(), ::tolower);
    return result;
}

} // namespace AICampus

#endif // AI_ENGINE_CORE_H
