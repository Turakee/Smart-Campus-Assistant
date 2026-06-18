/**
 * Academic Performance Analyzer
 * AI-Powered Smart Campus Assistant
 * 
 * Analyzes academic patterns, generates insights,
 * and provides performance-based recommendations.
 */

#ifndef PERFORMANCE_ANALYZER_H
#define PERFORMANCE_ANALYZER_H

#include "ai_core.h"

namespace AICampus {

/**
 * Performance Metrics
 */
struct PerformanceMetrics {
    double overall_score;
    double attendance_score;
    double consistency_score;
    double engagement_score;
    
    PerformanceMetrics() : overall_score(0.0), attendance_score(0.0), 
                          consistency_score(0.0), engagement_score(0.0) {}
};

/**
 * Academic Insight
 */
struct AcademicInsight {
    std::string category;
    std::string title;
    std::string description;
    double significance;  // 0-1
    std::vector<std::string> suggestions;
    
    AcademicInsight() : significance(0.0) {}
};

/**
 * Performance Report
 */
struct PerformanceReport {
    PerformanceMetrics metrics;
    std::vector<AcademicInsight> insights;
    std::string summary;
    double health_score;  // 0-100
    std::vector<std::string> overall_recommendations;
    
    PerformanceReport() : health_score(0.0) {}
};

/**
 * Performance Analyzer Class
 * 
 * Analyzes academic data to generate:
 * - Performance metrics
 * - Academic insights
 * - Health scores
 * - Recommendations
 */
class PerformanceAnalyzer {
private:
    AIConfig config;
    Logger* logger;
    
    PerformanceMetrics calculateMetrics(
        const std::vector<AttendanceRecord>& records,
        const std::vector<Course>& courses
    );
    
    std::vector<AcademicInsight> generateInsights(
        const std::vector<AttendanceRecord>& records,
        const PerformanceMetrics& metrics
    );
    
    double calculateHealthScore(const PerformanceMetrics& metrics);
    
public:
    PerformanceAnalyzer(Logger* log = nullptr) : logger(log) {}
    
    PerformanceReport analyze(
        const std::vector<AttendanceRecord>& records,
        const std::vector<Course>& courses
    );
    
    std::vector<PerformanceReport> batchAnalyze(
        const std::vector<std::vector<AttendanceRecord>>& allRecords,
        const std::vector<std::vector<Course>>& allCourses
    );
};

// ============================================================================
// IMPLEMENTATION
// ============================================================================

PerformanceMetrics PerformanceAnalyzer::calculateMetrics(
    const std::vector<AttendanceRecord>& records,
    const std::vector<Course>& courses
) {
    PerformanceMetrics metrics;
    
    if (records.empty()) return metrics;
    
    // Attendance Score (based on presence rate)
    int total = static_cast<int>(records.size());
    int present = 0, late = 0;
    
    for (const auto& record : records) {
        if (record.status == "present") present++;
        else if (record.status == "late") late++;
    }
    
    metrics.attendance_score = (static_cast<double>(present) / total) * 100.0;
    double latePenalty = (static_cast<double>(late) / total) * 10.0;
    metrics.attendance_score -= latePenalty;
    
    // Consistency Score (based on regularity)
    // Count week-to-week consistency
    int consistent_weeks = 0;
    int total_weeks = 0;
    
    if (records.size() >= 2) {
        // Group by weeks (simplified - assumes sorted by date)
        int week_size = 5; // Assume 5 records per week
        for (size_t i = 0; i + week_size <= records.size(); i += week_size) {
            int week_present = 0;
            for (size_t j = i; j < i + week_size && j < records.size(); ++j) {
                if (records[j].status == "present") week_present++;
            }
            if (week_present >= 3) consistent_weeks++;
            total_weeks++;
        }
    }
    
    if (total_weeks > 0) {
        metrics.consistency_score = (static_cast<double>(consistent_weeks) / total_weeks) * 100.0;
    }
    
    // Engagement Score (based on attendance trend and participation)
    double engagement = 0.0;
    
    // Higher engagement for consistent attendance
    engagement += metrics.attendance_score * 0.6;
    
    // Higher engagement for consistent weeks
    engagement += metrics.consistency_score * 0.4;
    
    metrics.engagement_score = engagement;
    
    // Overall Score
    metrics.overall_score = (
        metrics.attendance_score * 0.5 +
        metrics.consistency_score * 0.3 +
        metrics.engagement_score * 0.2
    );
    
    return metrics;
}

std::vector<AcademicInsight> PerformanceAnalyzer::generateInsights(
    const std::vector<AttendanceRecord>& records,
    const PerformanceMetrics& metrics
) {
    std::vector<AcademicInsight> insights;
    
    // Late attendance insight
    int late_count = 0;
    for (const auto& record : records) {
        if (record.status == "late") late_count++;
    }
    
    if (late_count > 0) {
        AcademicInsight late_insight;
        late_insight.category = "Punctuality";
        late_insight.title = "Late Arrivals Detected";
        late_insight.description = "You have " + std::to_string(late_count) + 
                                  " late arrivals. Being on time can improve your learning experience.";
        late_insight.significance = std::min(1.0, late_count / 10.0);
        late_insight.suggestions.push_back("Leave home earlier to arrive on time");
        late_insight.suggestions.push_back("Set reminders before your classes");
        insights.push_back(late_insight);
    }
    
    // Consistency insight
    if (metrics.consistency_score < 70) {
        AcademicInsight consistency_insight;
        consistency_insight.category = "Consistency";
        consistency_insight.title = "Inconsistent Attendance";
        consistency_insight.description = std::string("Your attendance pattern shows inconsistency. ") +
                                         "Regular attendance improves learning outcomes.";
        consistency_insight.significance = 0.8;
        consistency_insight.suggestions.push_back("Create a study schedule");
        consistency_insight.suggestions.push_back("Track your attendance weekly");
        insights.push_back(consistency_insight);
    } else if (metrics.consistency_score >= 90) {
        AcademicInsight consistency_insight;
        consistency_insight.category = "Consistency";
        consistency_insight.title = "Excellent Consistency";
        consistency_insight.description = "Outstanding! Your attendance is highly consistent.";
        consistency_insight.significance = 0.9;
        consistency_insight.suggestions.push_back("Keep up the great work!");
        insights.push_back(consistency_insight);
    }
    
    // Overall performance insight
    if (metrics.overall_score >= 85) {
        AcademicInsight excellent_insight;
        excellent_insight.category = "Performance";
        excellent_insight.title = "Excellent Academic Engagement";
        excellent_insight.description = std::string("Your overall academic engagement is excellent. ") +
                                       "You're on track for success!";
        excellent_insight.significance = 0.95;
        excellent_insight.suggestions.push_back("Maintain your current habits");
        excellent_insight.suggestions.push_back("Consider helping classmates");
        insights.push_back(excellent_insight);
    } else if (metrics.overall_score < 60) {
        AcademicInsight warning_insight;
        warning_insight.category = "Performance";
        warning_insight.title = "Academic Support Needed";
        warning_insight.description = std::string("Your academic engagement needs improvement. ") +
                                      "Consider seeking support from advisors.";
        warning_insight.significance = 0.9;
        warning_insight.suggestions.push_back("Meet with your academic advisor");
        warning_insight.suggestions.push_back("Join study groups");
        warning_insight.suggestions.push_back("Use campus support services");
        insights.push_back(warning_insight);
    }
    
    return insights;
}

double PerformanceAnalyzer::calculateHealthScore(const PerformanceMetrics& metrics) {
    // Health score is a 0-100 scale
    double health = metrics.overall_score;
    
    // Boost for consistency
    health += metrics.consistency_score * 0.1;
    
    // Cap at 100
    return std::min(100.0, std::max(0.0, health));
}

PerformanceReport PerformanceAnalyzer::analyze(
    const std::vector<AttendanceRecord>& records,
    const std::vector<Course>& courses
) {
    PerformanceReport report;
    
    if (logger) {
        logger->info("Starting performance analysis");
    }
    
    // Calculate metrics
    report.metrics = calculateMetrics(records, courses);
    
    // Generate insights
    report.insights = generateInsights(records, report.metrics);
    
    // Calculate health score
    report.health_score = calculateHealthScore(report.metrics);
    
    // Generate summary
    std::ostringstream summary;
    summary << "Overall Performance: " << std::fixed << std::setprecision(1) 
            << report.metrics.overall_score << "% | ";
    summary << "Attendance: " << report.metrics.attendance_score << "% | ";
    summary << "Consistency: " << report.metrics.consistency_score << "%";
    report.summary = summary.str();
    
    // Overall recommendations
    if (report.health_score >= 80) {
        report.overall_recommendations.push_back("Continue your excellent work!");
        report.overall_recommendations.push_back("Consider peer tutoring to strengthen your knowledge");
    } else if (report.health_score >= 60) {
        report.overall_recommendations.push_back("Your performance is good but can improve");
        report.overall_recommendations.push_back("Focus on consistency in attending classes");
    } else {
        report.overall_recommendations.push_back("Please take immediate action to improve attendance");
        report.overall_recommendations.push_back("Schedule a meeting with your academic advisor");
        report.overall_recommendations.push_back("Identify and address barriers to attendance");
    }
    
    if (logger) {
        logger->info("Performance analysis complete. Health score: " + 
                    std::to_string(report.health_score));
    }
    
    return report;
}

std::vector<PerformanceReport> PerformanceAnalyzer::batchAnalyze(
    const std::vector<std::vector<AttendanceRecord>>& allRecords,
    const std::vector<std::vector<Course>>& allCourses
) {
    std::vector<PerformanceReport> reports;
    
    size_t size = std::min(allRecords.size(), allCourses.size());
    for (size_t i = 0; i < size; ++i) {
        PerformanceReport report = analyze(allRecords[i], allCourses[i]);
        reports.push_back(report);
    }
    
    return reports;
}

} // namespace AICampus

#endif // PERFORMANCE_ANALYZER_H
