/**
 * Attendance Risk Prediction Engine
 * AI-Powered Smart Campus Assistant
 * 
 * Implements weighted prediction model for attendance risk analysis
 * based on attendance rate, trend analysis, and threshold comparison.
 */

#ifndef ATTENDANCE_PREDICTOR_H
#define ATTENDANCE_PREDICTOR_H

#include "ai_core.h"

namespace AICampus {

/**
 * Prediction weights for risk calculation
 */
struct PredictionWeights {
    double attendance_rate_weight = 0.5;
    double trend_weight = 0.3;
    double consecutive_absence_weight = 0.2;
    
    // Thresholds
    double high_risk_threshold = 0.7;      // Risk score above 70%
    double medium_risk_threshold = 0.4;    // Risk score above 40%
    double attendance_low_threshold = 75.0; // Below 75% attendance
    double attendance_med_threshold = 85.0; // Below 85% attendance
};

/**
 * Trend Analysis Data
 */
struct TrendAnalysis {
    double slope;           // Positive = improving, Negative = declining
    int improving_weeks;
    int declining_weeks;
    std::string trend_direction; // "improving", "declining", "stable"
    
    TrendAnalysis() : slope(0.0), improving_weeks(0), declining_weeks(0), trend_direction("stable") {}
};

/**
 * Attendance Risk Predictor Class
 * 
 * Uses weighted combination of:
 * - Attendance rate
 * - Trend analysis
 * - Consecutive absences
 * to predict attendance risk levels.
 */
class AttendanceRiskPredictor {
private:
    AIConfig config;
    PredictionWeights weights;
    Logger* logger;
    
    double calculateAttendanceRate(
        int present, int total
    );
    
    TrendAnalysis analyzeTrend(
        const std::vector<AttendanceRecord>& records
    );
    
    int countConsecutiveAbsences(
        const std::vector<AttendanceRecord>& records
    );
    
    double calculateRiskScore(
        double attendanceRate,
        const TrendAnalysis& trend,
        int consecutiveAbsences
    );
    
    std::string determineRiskLevel(double riskScore);
    
    std::vector<std::string> generateRecommendations(
        double attendanceRate,
        const TrendAnalysis& trend,
        int consecutiveAbsences,
        const std::string& riskLevel
    );
    
public:
    AttendanceRiskPredictor(Logger* log = nullptr) : logger(log) {}
    
    void setWeights(const PredictionWeights& w) {
        weights = w;
    }
    
    RiskPrediction predict(
        const std::vector<AttendanceRecord>& records
    );
    
    RiskPrediction predictWithStats(
        int totalClasses,
        int presentCount,
        int absentCount,
        const std::vector<AttendanceRecord>& recentRecords
    );
    
    std::vector<RiskPrediction> batchPredict(
        const std::vector<std::vector<AttendanceRecord>>& allRecords
    );
};

// ============================================================================
// IMPLEMENTATION
// ============================================================================

double AttendanceRiskPredictor::calculateAttendanceRate(int present, int total) {
    if (total == 0) return 0.0;
    return (static_cast<double>(present) / total) * 100.0;
}

TrendAnalysis AttendanceRiskPredictor::analyzeTrend(
    const std::vector<AttendanceRecord>& records
) {
    TrendAnalysis trend;
    
    if (records.size() < 2) {
        return trend;
    }
    
    // Simple linear regression for trend
    int n = static_cast<int>(records.size());
    double sumX = 0, sumY = 0, sumXY = 0, sumX2 = 0;
    
    for (int i = 0; i < n; ++i) {
        int y = (records[i].status == "present") ? 1 : 0;
        sumX += i;
        sumY += y;
        sumXY += i * y;
        sumX2 += i * i;
    }
    
    double denominator = n * sumX2 - sumX * sumX;
    if (denominator != 0) {
        trend.slope = (n * sumXY - sumX * sumY) / denominator;
    }
    
    // Count improving/declining weeks (comparing consecutive records)
    for (size_t i = 1; i < records.size(); ++i) {
        bool currentPresent = (records[i].status == "present");
        bool prevPresent = (records[i-1].status == "present");
        
        if (currentPresent && !prevPresent) {
            trend.improving_weeks++;
        } else if (!currentPresent && prevPresent) {
            trend.declining_weeks++;
        }
    }
    
    // Determine trend direction
    if (trend.slope > 0.1) {
        trend.trend_direction = "improving";
    } else if (trend.slope < -0.1) {
        trend.trend_direction = "declining";
    } else {
        trend.trend_direction = "stable";
    }
    
    return trend;
}

int AttendanceRiskPredictor::countConsecutiveAbsences(
    const std::vector<AttendanceRecord>& records
) {
    int count = 0;
    
    // Records are assumed to be sorted by date descending (most recent first)
    for (const auto& record : records) {
        if (record.status == "absent") {
            count++;
        } else {
            break;
        }
    }
    
    return count;
}

double AttendanceRiskPredictor::calculateRiskScore(
    double attendanceRate,
    const TrendAnalysis& trend,
    int consecutiveAbsences
) {
    double score = 0.0;
    
    // Attendance rate contribution (inverse - lower attendance = higher risk)
    double attendanceRisk = (100.0 - attendanceRate) / 100.0;
    score += attendanceRisk * weights.attendance_rate_weight;
    
    // Trend contribution
    double trendRisk = 0.5; // Neutral baseline
    if (trend.trend_direction == "declining") {
        trendRisk = 0.8 + std::min(0.2, -trend.slope);
    } else if (trend.trend_direction == "improving") {
        trendRisk = 0.2 - std::min(0.2, trend.slope);
    }
    score += trendRisk * weights.trend_weight;
    
    // Consecutive absences contribution
    double consecutiveRisk = std::min(1.0, consecutiveAbsences / 5.0);
    score += consecutiveRisk * weights.consecutive_absence_weight;
    
    return std::max(0.0, std::min(1.0, score));
}

std::string AttendanceRiskPredictor::determineRiskLevel(double riskScore) {
    if (riskScore >= weights.high_risk_threshold) {
        return "high";
    } else if (riskScore >= weights.medium_risk_threshold) {
        return "medium";
    }
    return "low";
}

std::vector<std::string> AttendanceRiskPredictor::generateRecommendations(
    double attendanceRate,
    const TrendAnalysis& trend,
    int consecutiveAbsences,
    const std::string& riskLevel
) {
    std::vector<std::string> recommendations;
    
    // Attendance-based recommendations
    if (attendanceRate < weights.attendance_low_threshold) {
        recommendations.push_back(
            "Your attendance is below the required 75% threshold. Immediate action needed."
        );
        recommendations.push_back(
            "Consider attending extra sessions or meeting with your academic advisor."
        );
    } else if (attendanceRate < weights.attendance_med_threshold) {
        recommendations.push_back(
            "Your attendance is approaching the risk zone. Try to maintain consistent attendance."
        );
        recommendations.push_back(
            "Set a goal to attend all remaining classes to improve your rate."
        );
    } else {
        recommendations.push_back(
            "Great job maintaining good attendance! Keep it up."
        );
    }
    
    // Trend-based recommendations
    if (trend.trend_direction == "declining") {
        recommendations.push_back(
            "Warning: Your attendance has been declining recently. Identify barriers and seek support."
        );
    } else if (trend.trend_direction == "improving") {
        recommendations.push_back(
            "Your attendance is improving - well done! Continue this positive trend."
        );
    }
    
    // Consecutive absence recommendations
    if (consecutiveAbsences >= 3) {
        recommendations.push_back(
            "Critical: " + std::to_string(consecutiveAbsences) + 
            " consecutive absences detected. Please address this immediately."
        );
    } else if (consecutiveAbsences >= 1) {
        recommendations.push_back(
            "You have had " + std::to_string(consecutiveAbsences) + 
            " consecutive absence(s). Try not to miss more classes."
        );
    }
    
    // High risk specific
    if (riskLevel == "high") {
        recommendations.push_back(
            "URGENT: You are at high risk of academic failure due to attendance."
        );
        recommendations.push_back(
            "Please contact your student services or academic advisor immediately."
        );
    }
    
    return recommendations;
}

RiskPrediction AttendanceRiskPredictor::predict(
    const std::vector<AttendanceRecord>& records
) {
    RiskPrediction result;
    
    if (logger) {
        logger->info("Starting attendance risk prediction for " + 
                    std::to_string(records.size()) + " records");
    }
    
    // Calculate basic statistics
    int total = static_cast<int>(records.size());
    int present = 0, absent = 0;
    
    for (const auto& record : records) {
        if (record.status == "present") present++;
        else if (record.status == "absent") absent++;
    }
    
    result.total_classes = total;
    result.present_count = present;
    result.absent_count = absent;
    result.attendance_percentage = calculateAttendanceRate(present, total);
    
    // Analyze trend
    TrendAnalysis trend = analyzeTrend(records);
    
    // Count consecutive absences
    result.consecutive_absences = countConsecutiveAbsences(records);
    
    // Calculate overall risk score
    double riskScore = calculateRiskScore(
        result.attendance_percentage,
        trend,
        result.consecutive_absences
    );
    
    result.risk_score = riskScore * 100.0;
    result.risk_level = determineRiskLevel(riskScore);
    
    // Generate recommendations
    result.recommendations = generateRecommendations(
        result.attendance_percentage,
        trend,
        result.consecutive_absences,
        result.risk_level
    );
    
    if (logger) {
        logger->info("Prediction complete. Risk level: " + result.risk_level + 
                    ", Score: " + std::to_string(result.risk_score));
    }
    
    return result;
}

RiskPrediction AttendanceRiskPredictor::predictWithStats(
    int totalClasses,
    int presentCount,
    int absentCount,
    const std::vector<AttendanceRecord>& recentRecords
) {
    RiskPrediction result;
    
    result.total_classes = totalClasses;
    result.present_count = presentCount;
    result.absent_count = absentCount;
    result.attendance_percentage = calculateAttendanceRate(presentCount, totalClasses);
    
    // Analyze trend from recent records
    TrendAnalysis trend = analyzeTrend(recentRecords);
    
    // Count consecutive absences
    result.consecutive_absences = countConsecutiveAbsences(recentRecords);
    
    // Calculate risk score
    double riskScore = calculateRiskScore(
        result.attendance_percentage,
        trend,
        result.consecutive_absences
    );
    
    result.risk_score = riskScore * 100.0;
    result.risk_level = determineRiskLevel(riskScore);
    
    // Generate recommendations
    result.recommendations = generateRecommendations(
        result.attendance_percentage,
        trend,
        result.consecutive_absences,
        result.risk_level
    );
    
    return result;
}

std::vector<RiskPrediction> AttendanceRiskPredictor::batchPredict(
    const std::vector<std::vector<AttendanceRecord>>& allRecords
) {
    std::vector<RiskPrediction> results;
    
    for (size_t i = 0; i < allRecords.size(); ++i) {
        RiskPrediction pred = predict(allRecords[i]);
        results.push_back(pred);
    }
    
    return results;
}

} // namespace AICampus

#endif // ATTENDANCE_PREDICTOR_H
