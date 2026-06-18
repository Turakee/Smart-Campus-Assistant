/**
 * AI Campus Engine - Main Entry Point
 * AI-Powered Smart Campus Assistant
 * 
 * This is the main executable that processes AI requests
 * from the PHP backend.
 * 
 * Usage:
 *   ai_engine.exe <input_file> <output_file>
 *   ai_engine.exe --task <task_type> --input <json>
 * 
 * Tasks:
 *   optimize-schedule  - Optimize student schedule
 *   predict-attendance - Predict attendance risk
 *   chatbot           - Process chatbot query
 *   analyze-performance - Analyze academic performance
 */

#include <iostream>
#include <fstream>
#include <sstream>
#include <string>
#include <vector>
#include <map>
#include <algorithm>
#include <cmath>
#include <ctime>
#include <iomanip>
#include <limits>

#include "include/ai_core.h"
#include "include/schedule_optimizer.h"
#include "include/attendance_predictor.h"
#include "include/performance_analyzer.h"
#include "include/ai_chatbot.h"
#include "include/json_processor.h"

using namespace AICampus;

// Global logger
Logger g_logger;

void printUsage(const char* programName) {
    std::cout << "AI Campus Engine - Smart Campus Assistant\n";
    std::cout << "========================================\n\n";
    std::cout << "Usage:\n";
    std::cout << "  " << programName << " <input_file> <output_file>\n";
    std::cout << "  " << programName << " --task <task> --input <json>\n\n";
    std::cout << "Tasks:\n";
    std::cout << "  optimize-schedule     - Optimize student schedule\n";
    std::cout << "  predict-attendance    - Predict attendance risk\n";
    std::cout << "  analyze-performance   - Analyze academic performance\n";
    std::cout << "  chatbot              - Process chatbot query\n";
    std::cout << "  help                 - Show this help\n\n";
    std::cout << "Examples:\n";
    std::cout << "  " << programName << " input.json output.json\n";
    std::cout << "  " << programName << " --task predict-attendance --input '{\"task\":\"predict-attendance\",...}'\n";
}

std::string readFile(const std::string& filename) {
    std::ifstream file(filename);
    if (!file.is_open()) {
        return "";
    }
    
    std::stringstream buffer;
    buffer << file.rdbuf();
    return buffer.str();
}

bool writeFile(const std::string& filename, const std::string& content) {
    std::ofstream file(filename);
    if (!file.is_open()) {
        return false;
    }
    
    file << content;
    file.close();
    return true;
}

std::string processTask(const std::string& task, const std::string& inputJson) {
    JSONProcessor processor(&g_logger);
    std::string output;
    
    try {
        if (task == "optimize-schedule") {
            g_logger.info("Processing schedule optimization task");
            
            auto input = processor.parseInput(inputJson);
            ScheduleOptimizer optimizer(&g_logger);
            
            OptimizationConstraints constraints;
            constraints.max_hours_per_day = 6;
            constraints.min_break_minutes = 30;
            optimizer.setConstraints(constraints);
            
            auto result = optimizer.optimize(input.courses, input.existing_schedules);
            output = processor.generateOptimizationOutput(result);
            
        } else if (task == "predict-attendance") {
            g_logger.info("Processing attendance prediction task");
            
            auto input = processor.parseInput(inputJson);
            AttendanceRiskPredictor predictor(&g_logger);
            
            RiskPrediction result;
            
            if (!input.attendance_records.empty()) {
                result = predictor.predict(input.attendance_records);
            } else if (input.total_classes > 0) {
                result = predictor.predictWithStats(
                    input.total_classes,
                    input.present_count,
                    input.absent_count,
                    input.attendance_records
                );
            } else {
                // Return empty prediction
                result.risk_level = "low";
                result.attendance_percentage = 0;
                result.total_classes = 0;
                result.present_count = 0;
                result.absent_count = 0;
            }
            
            output = processor.generatePredictionOutput(result);
            
        } else if (task == "analyze-performance") {
            g_logger.info("Processing performance analysis task");
            
            auto input = processor.parseInput(inputJson);
            PerformanceAnalyzer analyzer(&g_logger);
            
            auto report = analyzer.analyze(input.attendance_records, input.courses);
            output = processor.generatePerformanceOutput(report);
            
        } else if (task == "chatbot") {
            g_logger.info("Processing chatbot query");
            
            auto input = processor.parseInput(inputJson);
            AIChatbot chatbot(&g_logger);
            auto response = chatbot.processQuery(input.query);
            output = processor.generateChatbotOutput(response);
            
        } else {
            output = processor.generateError("Unknown task: " + task);
        }
        
    } catch (const std::exception& e) {
        g_logger.error(std::string("Exception: ") + e.what());
        output = processor.generateError(std::string("Processing error: ") + e.what());
    }
    
    return output;
}

int main(int argc, char* argv[]) {
    // Initialize logger
    g_logger.init("ai_engine.log");
    g_logger.info("AI Engine started");
    
    // Handle help
    if (argc > 1 && (std::string(argv[1]) == "--help" || std::string(argv[1]) == "help")) {
        printUsage(argv[0]);
        return 0;
    }
    
    std::string task;
    std::string inputJson;
    std::string outputFile;
    
    // Parse arguments
    if (argc >= 3) {
        std::string arg1(argv[1]);
        
        if (arg1 == "--task" && argc >= 5 && std::string(argv[2]) == "--input") {
            // --task <task> --input <json>
            task = argv[3];
            inputJson = argv[4];
        } else {
            // <input_file> <output_file>
            inputJson = readFile(arg1);
            outputFile = argv[2];
            
            // Extract task from JSON
            JSONProcessor proc;
            auto input = proc.parseInput(inputJson);
            task = input.task;
        }
    } else if (argc == 2) {
        // Single argument - assume it's a task with inline input
        std::string arg(argv[1]);
        if (arg.find("task") != std::string::npos) {
            inputJson = arg;
            JSONProcessor proc;
            auto input = proc.parseInput(inputJson);
            task = input.task;
        } else {
            printUsage(argv[0]);
            return 1;
        }
    } else {
        // Interactive mode
        printUsage(argv[0]);
        std::cout << "\nInteractive Mode:\n";
        
        while (true) {
            std::cout << "\n> ";
            std::string line;
            std::getline(std::cin, line);
            
            if (line.empty()) continue;
            if (line == "exit" || line == "quit") break;
            
            // Simple task detection
            std::string t = "chatbot";
            if (line.find("schedule") != std::string::npos) t = "optimize-schedule";
            else if (line.find("attendance") != std::string::npos || line.find("risk") != std::string::npos) t = "predict-attendance";
            else if (line.find("performance") != std::string::npos || line.find("analyze") != std::string::npos) t = "analyze-performance";
            
            std::string input = "{\"task\":\"" + t + "\",\"query\":\"" + line + "\"}";
            std::string output = processTask(t, input);
            std::cout << output << std::endl;
        }
        
        g_logger.info("AI Engine stopped by user");
        return 0;
    }
    
    // Process the task
    if (task.empty()) {
        std::cerr << "Error: No task specified\n";
        g_logger.error("No task specified");
        printUsage(argv[0]);
        return 1;
    }
    
    if (inputJson.empty()) {
        std::cerr << "Error: No input data\n";
        g_logger.error("No input data");
        return 1;
    }
    
    g_logger.info("Processing task: " + task);
    
    std::string output = processTask(task, inputJson);
    
    if (!outputFile.empty()) {
        if (writeFile(outputFile, output)) {
            g_logger.info("Output written to: " + outputFile);
            std::cout << "Success: Output written to " << outputFile << std::endl;
        } else {
            g_logger.error("Failed to write output to: " + outputFile);
            std::cerr << "Error: Failed to write output file\n";
            return 1;
        }
    } else {
        std::cout << output << std::endl;
    }
    
    g_logger.info("AI Engine completed successfully");
    return 0;
}
