/**
 * JSON I/O Processor
 * AI-Powered Smart Campus Assistant
 * 
 * Handles JSON input parsing and output generation
 * for communication with PHP backend.
 */

#ifndef JSON_PROCESSOR_H
#define JSON_PROCESSOR_H

#include "ai_core.h"
#include "schedule_optimizer.h"
#include "attendance_predictor.h"
#include "performance_analyzer.h"
#include "ai_chatbot.h"

namespace AICampus {

/**
 * JSON Input/Output Processor
 */
class JSONProcessor {
private:
    Logger* logger;
    
    // Parse helpers
    std::string extractString(const std::string& json, const std::string& key);
    int extractInt(const std::string& json, const std::string& key);
    double extractDouble(const std::string& json, const std::string& key);
    bool extractBool(const std::string& json, const std::string& key);
    
    std::vector<Course> parseCourses(const std::string& jsonArray);
    std::vector<ScheduleEntry> parseSchedules(const std::string& jsonArray);
    std::vector<AttendanceRecord> parseAttendance(const std::string& jsonArray);
    
    std::string parseQueryString(const std::string& json);
    
public:
    JSONProcessor(Logger* log = nullptr) : logger(log) {}
    
    // Parse input JSON
    struct ParsedInput {
        std::string task;
        std::vector<Course> courses;
        std::vector<ScheduleEntry> existing_schedules;
        std::vector<AttendanceRecord> attendance_records;
        int total_classes;
        int present_count;
        int absent_count;
        std::string query;
        std::string student_name;
    };
    
    ParsedInput parseInput(const std::string& jsonInput);
    
    // Generate output JSON
    std::string generateOptimizationOutput(const OptimizationResult& result);
    std::string generatePredictionOutput(const RiskPrediction& result);
    std::string generateChatbotOutput(const ChatbotResponse& result);
    std::string generatePerformanceOutput(const PerformanceReport& result);
    
    // Error output
    std::string generateError(const std::string& message);
};

// ============================================================================
// IMPLEMENTATION
// ============================================================================

std::string JSONProcessor::extractString(const std::string& json, const std::string& key) {
    std::string pattern = "\"" + key + "\"";
    size_t pos = json.find(pattern);
    if (pos == std::string::npos) return "";
    
    pos = json.find(":", pos);
    if (pos == std::string::npos) return "";
    pos++;
    
    while (pos < json.length() && (json[pos] == ' ' || json[pos] == '\"')) pos++;
    
    size_t end = pos;
    bool inString = true;
    while (end < json.length()) {
        if (json[end] == '"' && (end == 0 || json[end-1] != '\\')) {
            inString = !inString;
            if (!inString) break;
        }
        end++;
    }
    
    return json.substr(pos, end - pos);
}

int JSONProcessor::extractInt(const std::string& json, const std::string& key) {
    std::string value = extractString(json, key);
    return value.empty() ? 0 : std::stoi(value);
}

double JSONProcessor::extractDouble(const std::string& json, const std::string& key) {
    std::string value = extractString(json, key);
    return value.empty() ? 0.0 : std::stod(value);
}

bool JSONProcessor::extractBool(const std::string& json, const std::string& key) {
    std::string value = extractString(json, key);
    return value == "true" || value == "1";
}

std::vector<Course> JSONProcessor::parseCourses(const std::string& jsonArray) {
    std::vector<Course> courses;
    
    size_t start = jsonArray.find('[');
    size_t end = jsonArray.find(']');
    if (start == std::string::npos || end == std::string::npos) return courses;
    
    std::string arrayContent = jsonArray.substr(start + 1, end - start - 1);
    
    size_t objStart = 0;
    while ((objStart = arrayContent.find('{', objStart)) != std::string::npos) {
        size_t objEnd = arrayContent.find('}', objStart);
        if (objEnd == std::string::npos) break;
        
        std::string obj = arrayContent.substr(objStart, objEnd - objStart + 1);
        
        Course course;
        course.course_id = extractInt(obj, "course_id");
        course.course_name = extractString(obj, "course_name");
        course.course_code = extractString(obj, "course_code");
        course.credit_hours = extractInt(obj, "credit_hours");
        
        if (course.course_id > 0) {
            courses.push_back(course);
        }
        
        objStart = objEnd + 1;
    }
    
    return courses;
}

std::vector<ScheduleEntry> JSONProcessor::parseSchedules(const std::string& jsonArray) {
    std::vector<ScheduleEntry> schedules;
    
    size_t start = jsonArray.find('[');
    size_t end = jsonArray.find(']');
    if (start == std::string::npos || end == std::string::npos) return schedules;
    
    std::string arrayContent = jsonArray.substr(start + 1, end - start - 1);
    
    size_t objStart = 0;
    while ((objStart = arrayContent.find('{', objStart)) != std::string::npos) {
        size_t objEnd = arrayContent.find('}', objStart);
        if (objEnd == std::string::npos) break;
        
        std::string obj = arrayContent.substr(objStart, objEnd - objStart + 1);
        
        ScheduleEntry entry;
        entry.schedule_id = extractInt(obj, "schedule_id");
        entry.course_id = extractInt(obj, "course_id");
        entry.day_of_week = extractString(obj, "day_of_week");
        entry.course_code = extractString(obj, "course_code");
        
        // Parse time slot
        std::string startTime = extractString(obj, "start_time");
        std::string endTime = extractString(obj, "end_time");
        entry.time_slot = TimeSlot(startTime, endTime);
        
        entry.room_number = extractString(obj, "room_number");
        
        schedules.push_back(entry);
        
        objStart = objEnd + 1;
    }
    
    return schedules;
}

std::vector<AttendanceRecord> JSONProcessor::parseAttendance(const std::string& jsonArray) {
    std::vector<AttendanceRecord> records;
    
    size_t start = jsonArray.find('[');
    size_t end = jsonArray.find(']');
    if (start == std::string::npos || end == std::string::npos) return records;
    
    std::string arrayContent = jsonArray.substr(start + 1, end - start - 1);
    
    size_t objStart = 0;
    while ((objStart = arrayContent.find('{', objStart)) != std::string::npos) {
        size_t objEnd = arrayContent.find('}', objStart);
        if (objEnd == std::string::npos) break;
        
        std::string obj = arrayContent.substr(objStart, objEnd - objStart + 1);
        
        AttendanceRecord record;
        record.date = extractString(obj, "date");
        record.status = extractString(obj, "status");
        
        if (!record.date.empty()) {
            records.push_back(record);
        }
        
        objStart = objEnd + 1;
    }
    
    return records;
}

std::string JSONProcessor::parseQueryString(const std::string& json) {
    return extractString(json, "query");
}

JSONProcessor::ParsedInput JSONProcessor::parseInput(const std::string& jsonInput) {
    ParsedInput input;
    
    input.task = extractString(jsonInput, "task");
    input.total_classes = extractInt(jsonInput, "total_classes");
    input.present_count = extractInt(jsonInput, "present_count");
    input.absent_count = extractInt(jsonInput, "absent_count");
    input.student_name = extractString(jsonInput, "student_name");
    input.query = parseQueryString(jsonInput);
    
    // Parse courses array
    size_t coursesPos = jsonInput.find("\"courses\"");
    if (coursesPos != std::string::npos) {
        size_t arrStart = jsonInput.find('[', coursesPos);
        size_t arrEnd = jsonInput.find(']', coursesPos);
        if (arrStart != std::string::npos && arrEnd != std::string::npos) {
            input.courses = parseCourses(jsonInput.substr(arrStart, arrEnd - arrStart + 1));
        }
    }
    
    // Parse schedules array
    size_t schedPos = jsonInput.find("\"existing_schedules\"");
    if (schedPos != std::string::npos) {
        size_t arrStart = jsonInput.find('[', schedPos);
        size_t arrEnd = jsonInput.find(']', schedPos);
        if (arrStart != std::string::npos && arrEnd != std::string::npos) {
            input.existing_schedules = parseSchedules(jsonInput.substr(arrStart, arrEnd - arrStart + 1));
        }
    }
    
    // Parse attendance array
    size_t attPos = jsonInput.find("\"attendance_records\"");
    if (attPos != std::string::npos) {
        size_t arrStart = jsonInput.find('[', attPos);
        size_t arrEnd = jsonInput.find(']', attPos);
        if (arrStart != std::string::npos && arrEnd != std::string::npos) {
            input.attendance_records = parseAttendance(jsonInput.substr(arrStart, arrEnd - arrStart + 1));
        }
    }
    
    return input;
}

std::string JSONProcessor::generateOptimizationOutput(const OptimizationResult& result) {
    std::ostringstream json;
    json << "{\n";
    json << "  \"success\": " << (result.success ? "true" : "false") << ",\n";
    json << "  \"message\": \"" << JSONParser::escapeString(result.message) << "\",\n";
    json << "  \"conflicts_resolved\": " << result.conflicts_resolved << ",\n";
    json << "  \"optimization_score\": " << result.optimization_score << ",\n";
    json << "  \"optimized_slots\": [\n";
    
    for (size_t i = 0; i < result.optimized_slots.size(); ++i) {
        const auto& slot = result.optimized_slots[i];
        json << "    {\n";
        json << "      \"course_code\": \"" << JSONParser::escapeString(slot.course_code) << "\",\n";
        json << "      \"day\": \"" << JSONParser::escapeString(slot.day_of_week) << "\",\n";
        json << "      \"time\": \"" << slot.time_slot.start_time << "-" << slot.time_slot.end_time << "\",\n";
        json << "      \"room\": \"" << JSONParser::escapeString(slot.room_number) << "\"\n";
        json << "    }" << (i < result.optimized_slots.size() - 1 ? "," : "") << "\n";
    }
    
    json << "  ]\n";
    json << "}";
    
    return json.str();
}

std::string JSONProcessor::generatePredictionOutput(const RiskPrediction& result) {
    std::ostringstream json;
    json << "{\n";
    json << "  \"success\": true,\n";
    json << "  \"attendance_percentage\": " << std::fixed << std::setprecision(2) << result.attendance_percentage << ",\n";
    json << "  \"total_classes\": " << result.total_classes << ",\n";
    json << "  \"present_count\": " << result.present_count << ",\n";
    json << "  \"absent_count\": " << result.absent_count << ",\n";
    json << "  \"consecutive_absences\": " << result.consecutive_absences << ",\n";
    json << "  \"risk_level\": \"" << result.risk_level << "\",\n";
    json << "  \"risk_score\": " << result.risk_score << ",\n";
    json << "  \"recommendations\": [";
    
    for (size_t i = 0; i < result.recommendations.size(); ++i) {
        json << "\"" << JSONParser::escapeString(result.recommendations[i]) << "\"";
        if (i < result.recommendations.size() - 1) json << ", ";
    }
    
    json << "]\n";
    json << "}";
    
    return json.str();
}

std::string JSONProcessor::generateChatbotOutput(const ChatbotResponse& result) {
    std::ostringstream json;
    json << "{\n";
    json << "  \"success\": " << (result.success ? "true" : "false") << ",\n";
    json << "  \"intent\": \"" << JSONParser::escapeString(result.intent) << "\",\n";
    json << "  \"response\": \"" << JSONParser::escapeString(result.response) << "\",\n";
    json << "  \"suggestions\": [";
    for (size_t i = 0; i < result.suggestions.size(); ++i) {
        json << "\"" << JSONParser::escapeString(result.suggestions[i]) << "\"";
        if (i < result.suggestions.size() - 1) json << ", ";
    }
    json << "],\n";
    json << "  \"details\": [";
    for (size_t i = 0; i < result.details.size(); ++i) {
        json << "\"" << JSONParser::escapeString(result.details[i]) << "\"";
        if (i < result.details.size() - 1) json << ", ";
    }
    json << "]\n";
    json << "}";
    
    return json.str();
}

std::string JSONProcessor::generatePerformanceOutput(const PerformanceReport& result) {
    std::ostringstream json;
    json << "{\n";
    json << "  \"success\": true,\n";
    json << "  \"predicted_score\": " << std::fixed << std::setprecision(1) << result.health_score << ",\n";
    json << "  \"predicted_grade\": \"";

    std::string predictedGrade = "F";
    double gradePoints = 0.0;
    if (result.health_score >= 90) {
        predictedGrade = "A";
        gradePoints = 4.0;
    } else if (result.health_score >= 80) {
        predictedGrade = "B";
        gradePoints = 3.0;
    } else if (result.health_score >= 70) {
        predictedGrade = "C";
        gradePoints = 2.0;
    } else if (result.health_score >= 60) {
        predictedGrade = "D";
        gradePoints = 1.0;
    }

    json << JSONParser::escapeString(predictedGrade) << "\",\n";
    json << "  \"grade_points\": " << std::fixed << std::setprecision(1) << gradePoints << ",\n";

    std::string riskLevel = "low";
    if (result.health_score < 60) {
        riskLevel = "high";
    } else if (result.health_score < 75) {
        riskLevel = "medium";
    }

    json << "  \"risk_level\": \"" << JSONParser::escapeString(riskLevel) << "\",\n";
    json << "  \"health_score\": " << std::fixed << std::setprecision(2) << result.health_score << ",\n";
    json << "  \"summary\": \"" << JSONParser::escapeString(result.summary) << "\",\n";
    json << "  \"metrics\": {\n";
    json << "    \"overall_score\": " << std::fixed << std::setprecision(2) << result.metrics.overall_score << ",\n";
    json << "    \"attendance_score\": " << std::fixed << std::setprecision(2) << result.metrics.attendance_score << ",\n";
    json << "    \"consistency_score\": " << std::fixed << std::setprecision(2) << result.metrics.consistency_score << ",\n";
    json << "    \"engagement_score\": " << std::fixed << std::setprecision(2) << result.metrics.engagement_score << "\n";
    json << "  },\n";
    json << "  \"overall_recommendations\": [";
    
    for (size_t i = 0; i < result.overall_recommendations.size(); ++i) {
        json << "\"" << JSONParser::escapeString(result.overall_recommendations[i]) << "\"";
        if (i < result.overall_recommendations.size() - 1) json << ", ";
    }
    
    json << "],\n";
    json << "  \"attendance_percentage\": " << std::fixed << std::setprecision(2) << result.metrics.attendance_score << "\n";
    json << "}";
    
    return json.str();
}

std::string JSONProcessor::generateError(const std::string& message) {
    std::ostringstream json;
    json << "{\n";
    json << "  \"success\": false,\n";
    json << "  \"error\": \"" << JSONParser::escapeString(message) << "\"\n";
    json << "}";
    return json.str();
}

} // namespace AICampus

#endif // JSON_PROCESSOR_H
