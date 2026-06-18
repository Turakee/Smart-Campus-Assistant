/**
 * Schedule Optimization Engine
 * AI-Powered Smart Campus Assistant
 * 
 * Implements greedy algorithm with constraint satisfaction
 * for intelligent academic schedule optimization.
 */

#ifndef SCHEDULE_OPTIMIZER_H
#define SCHEDULE_OPTIMIZER_H

#include "ai_core.h"
#include <queue>
#include <limits>

namespace AICampus {

/**
 * Schedule Optimization Constraints
 */
struct OptimizationConstraints {
    int max_hours_per_day;
    int min_break_minutes;
    std::vector<std::string> preferred_times;
    std::vector<std::string> blocked_times;
    std::map<std::string, std::vector<TimeSlot>> existing_slots;
    
    OptimizationConstraints() : max_hours_per_day(6), min_break_minutes(30) {
        preferred_times = {"08:00", "10:00", "14:00"};
    }
};

/**
 * Time Slot with Priority for Sorting
 */
struct PriorityTimeSlot {
    std::string day;
    TimeSlot slot;
    int priority;
    int course_id;
    std::string course_code;
    std::string room;
    
    bool operator<(const PriorityTimeSlot& other) const {
        return priority > other.priority; // Higher priority first for min-heap
    }
};

/**
 * Schedule Optimizer Class
 * 
 * Uses greedy algorithm combined with constraint satisfaction
 * to optimize student schedules.
 */
class ScheduleOptimizer {
private:
    AIConfig config;
    OptimizationConstraints constraints;
    Logger* logger;
    
    // Helper functions
    int calculateTimePriority(const std::string& time);
    bool hasConflict(const std::string& day, const TimeSlot& slot);
    bool satisfiesBreakTime(const std::string& day, const TimeSlot& slot);
    std::vector<TimeSlot> getDaySlots(const std::string& day);
    int countDayHours(const std::string& day);
    double calculateOptimizationScore(const std::vector<ScheduleEntry>& entries);
    
public:
    ScheduleOptimizer(Logger* log = nullptr) : logger(log) {}
    
    void setConstraints(const OptimizationConstraints& cons) {
        constraints = cons;
    }
    
    OptimizationResult optimize(
        const std::vector<Course>& courses,
        const std::vector<ScheduleEntry>& existingSchedules
    );
    
    std::vector<ScheduleEntry> detectConflicts(
        const std::vector<ScheduleEntry>& schedules
    );
    
    OptimizationResult generateRecommendations(
        const std::vector<Course>& courses,
        const std::vector<ScheduleEntry>& schedules
    );
};

// ============================================================================
// IMPLEMENTATION
// ============================================================================

int ScheduleOptimizer::calculateTimePriority(const std::string& time) {
    int priority = 0;
    int timeMins = TimeSlot::timeToMinutes(time);
    
    // Higher priority for preferred times
    for (const auto& pref : constraints.preferred_times) {
        int prefMins = TimeSlot::timeToMinutes(pref);
        int diff = std::abs(timeMins - prefMins);
        if (diff <= 30) {
            priority += (30 - diff);
        }
    }
    
    // Morning classes get slight boost
    if (timeMins >= 480 && timeMins <= 720) { // 8:00 - 12:00
        priority += 10;
    }
    
    return priority;
}

std::vector<TimeSlot> ScheduleOptimizer::getDaySlots(const std::string& day) {
    if (constraints.existing_slots.find(day) == constraints.existing_slots.end()) {
        return {};
    }
    return constraints.existing_slots[day];
}

int ScheduleOptimizer::countDayHours(const std::string& day) {
    int totalMins = 0;
    for (const auto& slot : getDaySlots(day)) {
        totalMins += slot.getDurationMinutes();
    }
    return totalMins / 60;
}

bool ScheduleOptimizer::hasConflict(const std::string& day, const TimeSlot& slot) {
    for (const auto& existing : getDaySlots(day)) {
        if (slot.overlaps(existing)) {
            return true;
        }
    }
    return false;
}

bool ScheduleOptimizer::satisfiesBreakTime(const std::string& day, const TimeSlot& slot) {
    auto daySlots = getDaySlots(day);
    if (daySlots.empty()) return true;
    
    int slotStart = TimeSlot::timeToMinutes(slot.start_time);
    int slotEnd = TimeSlot::timeToMinutes(slot.end_time);
    
    for (const auto& existing : daySlots) {
        int existStart = TimeSlot::timeToMinutes(existing.start_time);
        int existEnd = TimeSlot::timeToMinutes(existing.end_time);
        
        // Check break before this slot
        if (slotStart > existEnd) {
            int breakTime = slotStart - existEnd;
            if (breakTime < constraints.min_break_minutes && breakTime > 0) {
                return false;
            }
        }
        
        // Check break after this slot
        if (existStart > slotEnd) {
            int breakTime = existStart - slotEnd;
            if (breakTime < constraints.min_break_minutes && breakTime > 0) {
                return false;
            }
        }
    }
    
    return true;
}

std::vector<ScheduleEntry> ScheduleOptimizer::detectConflicts(
    const std::vector<ScheduleEntry>& schedules
) {
    std::vector<ScheduleEntry> conflicts;
    std::map<std::string, std::vector<ScheduleEntry>> byDay;
    
    // Group by day
    for (const auto& entry : schedules) {
        byDay[entry.day_of_week].push_back(entry);
    }
    
    // Check each day for conflicts
    for (auto& pair : byDay) {
        const auto& daySlots = pair.second;
        for (size_t i = 0; i < daySlots.size(); ++i) {
            for (size_t j = i + 1; j < daySlots.size(); ++j) {
                if (daySlots[i].time_slot.overlaps(daySlots[j].time_slot)) {
                    // Check if not same course
                    if (daySlots[i].course_id != daySlots[j].course_id) {
                        conflicts.push_back(daySlots[i]);
                        conflicts.push_back(daySlots[j]);
                    }
                }
            }
        }
    }
    
    // Remove duplicates
    std::sort(conflicts.begin(), conflicts.end(), 
              [](const ScheduleEntry& a, const ScheduleEntry& b) {
                  return a.schedule_id < b.schedule_id;
              });
    conflicts.erase(std::unique(conflicts.begin(), conflicts.end(),
                               [](const ScheduleEntry& a, const ScheduleEntry& b) {
                                   return a.schedule_id == b.schedule_id;
                               }), conflicts.end());
    
    return conflicts;
}

double ScheduleOptimizer::calculateOptimizationScore(
    const std::vector<ScheduleEntry>& entries
) {
    if (entries.empty()) return 0.0;
    
    double score = 100.0;
    
    // Deduct for conflicts
    auto conflicts = detectConflicts(entries);
    score -= conflicts.size() * 15.0;
    
    // Check time distribution
    std::map<std::string, int> dayCount;
    for (const auto& entry : entries) {
        dayCount[entry.day_of_week]++;
    }
    
    // Penalize if too many classes on one day
    for (const auto& pair : dayCount) {
        if (pair.second > config.max_hours_per_day) {
            score -= (pair.second - config.max_hours_per_day) * 5.0;
        }
    }
    
    // Bonus for preferred times
    for (const auto& entry : entries) {
        int priority = calculateTimePriority(entry.time_slot.start_time);
        if (priority > 20) {
            score += 2.0;
        }
    }
    
    return std::max(0.0, std::min(100.0, score));
}

OptimizationResult ScheduleOptimizer::optimize(
    const std::vector<Course>& courses,
    const std::vector<ScheduleEntry>& existingSchedules
) {
    OptimizationResult result;
    
    if (logger) logger->info("Starting schedule optimization for " + 
                            std::to_string(courses.size()) + " courses");
    
    // Initialize existing slots for conflict detection
    for (const auto& sched : existingSchedules) {
        constraints.existing_slots[sched.day_of_week].push_back(sched.time_slot);
    }
    
    // Detect existing conflicts
    auto conflicts = detectConflicts(existingSchedules);
    result.conflicts_resolved = conflicts.size() / 2; // Each conflict involves 2 entries
    
    // Build priority queue with all possible slots
    std::priority_queue<PriorityTimeSlot> pq;
    
    // Generate time slots for each course
    for (const auto& course : courses) {
        for (const auto& day : config.days_order) {
            // Try common time slots
            std::vector<std::pair<std::string, std::string>> timeSlots = {
                {"08:00", "10:00"},
                {"10:00", "12:00"},
                {"12:00", "14:00"},
                {"14:00", "16:00"},
                {"16:00", "18:00"}
            };
            
            for (const auto& ts : timeSlots) {
                TimeSlot slot(ts.first, ts.second);
                
                // Check constraints
                if (hasConflict(day, slot)) continue;
                if (!satisfiesBreakTime(day, slot)) continue;
                if (countDayHours(day) >= config.max_hours_per_day) continue;
                
                int priority = calculateTimePriority(ts.first);
                
                pq.push({day, slot, priority, course.course_id, course.course_code, ""});
            }
        }
    }
    
    // Select best slots without conflicts
    std::set<int> assignedCourses;
    std::map<std::string, std::vector<TimeSlot>> selectedSlots;
    
    while (!pq.empty() && assignedCourses.size() < courses.size()) {
        auto top = pq.top();
        pq.pop();
        
        // Check if course already assigned
        if (assignedCourses.count(top.course_id)) continue;
        
        // Check if day has capacity
        if (countDayHours(top.day) >= config.max_hours_per_day) continue;
        
        // Check if no conflict with selected slots
        bool conflict = false;
        for (const auto& slot : selectedSlots[top.day]) {
            if (top.slot.overlaps(slot)) {
                conflict = true;
                break;
            }
        }
        if (conflict) continue;
        
        // Assign this slot
        selectedSlots[top.day].push_back(top.slot);
        assignedCourses.insert(top.course_id);
        
        ScheduleEntry entry;
        entry.course_id = top.course_id;
        entry.day_of_week = top.day;
        entry.time_slot = top.slot;
        entry.course_code = top.course_code;
        entry.room_number = top.room;
        result.optimized_slots.push_back(entry);
    }
    
    // Calculate optimization score
    result.optimization_score = calculateOptimizationScore(result.optimized_slots);
    result.success = true;
    result.message = "Schedule optimization completed successfully";
    
    if (logger) {
        logger->info("Optimization complete. Score: " + 
                    std::to_string(result.optimization_score) + 
                    ", Conflicts resolved: " + std::to_string(result.conflicts_resolved));
    }
    
    return result;
}

OptimizationResult ScheduleOptimizer::generateRecommendations(
    const std::vector<Course>& courses,
    const std::vector<ScheduleEntry>& schedules
) {
    OptimizationResult result;
    result.success = true;
    
    // Analyze current schedule
    auto conflicts = detectConflicts(schedules);
    
    if (!conflicts.empty()) {
        result.message = "Conflicts detected in current schedule";
        result.conflicts_resolved = conflicts.size() / 2;
    } else {
        result.message = "No conflicts found. Consider time optimization.";
    }
    
    // Calculate distribution
    std::map<std::string, int> dayDistribution;
    for (const auto& sched : schedules) {
        dayDistribution[sched.day_of_week]++;
    }
    
    // Check for unbalanced distribution
    int maxDay = 0, minDay = INT_MAX;
    for (const auto& pair : dayDistribution) {
        maxDay = std::max(maxDay, pair.second);
        minDay = std::min(minDay, pair.second);
    }
    
    if (maxDay - minDay > 2) {
        result.message += " Schedule distribution could be more balanced.";
    }
    
    result.optimization_score = calculateOptimizationScore(schedules);
    result.optimized_slots = schedules; // Return original with score
    
    return result;
}

} // namespace AICampus

#endif // SCHEDULE_OPTIMIZER_H
