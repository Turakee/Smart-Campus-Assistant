<?php

function callAIEngine($input, $type) {
    $aiExecutable = dirname(__DIR__) . '/ai-engine/bin/ai_engine.exe';
    $requestId = uniqid('', true);
    $inputFile = dirname(__DIR__) . '/ai-engine/input/' . $type . '_input_' . $requestId . '.json';
    $outputFile = dirname(__DIR__) . '/ai-engine/output/' . $type . '_output_' . $requestId . '.json';

    if (!file_exists($aiExecutable)) {
        return null;
    }

    @mkdir(dirname($inputFile), 0755, true);
    @mkdir(dirname($outputFile), 0755, true);

    try {
        file_put_contents($inputFile, json_encode($input));

        $command = escapeshellcmd($aiExecutable) . ' ' .
                   escapeshellarg($inputFile) . ' ' .
                   escapeshellarg($outputFile);

        $output = [];
        exec($command, $output);

        if (file_exists($outputFile)) {
            $result = json_decode(file_get_contents($outputFile), true);
            @unlink($inputFile);
            @unlink($outputFile);
            return $result;
        }
    } catch (Exception $e) {
        error_log("AI Engine error: " . $e->getMessage());
    }

    @unlink($inputFile);
    @unlink($outputFile);
    return null;
}
