import 'package:flutter/material.dart';
import '../services/api_service.dart';
import '../theme.dart';

class AIInsightsScreen extends StatefulWidget {
  const AIInsightsScreen({super.key});

  @override
  State<AIInsightsScreen> createState() => _AIInsightsScreenState();
}

class _AIInsightsScreenState extends State<AIInsightsScreen> {
  Map<String, dynamic>? _attendanceRisk;
  Map<String, dynamic>? _performance;
  bool _loadingAttendance = false;
  bool _loadingPerformance = false;

  @override
  void initState() {
    super.initState();
    _loadData();
  }

  Future<void> _loadData() async {
    await Future.wait([_loadAttendanceRisk(), _loadPerformance()]);
  }

  Future<void> _loadAttendanceRisk() async {
    setState(() => _loadingAttendance = true);
    try {
      final response = await ApiService.predictAttendanceRisk();
      if (response['success']) {
        setState(() => _attendanceRisk = response['data']);
      }
    } catch (_) {}
    if (mounted) setState(() => _loadingAttendance = false);
  }

  Future<void> _loadPerformance() async {
    setState(() => _loadingPerformance = true);
    try {
      final response = await ApiService.predictPerformance();
      if (response['success']) {
        setState(() => _performance = response['data']);
      }
    } catch (_) {}
    if (mounted) setState(() => _loadingPerformance = false);
  }

  Color _riskColor(String? level) {
    switch (level?.toLowerCase()) {
      case 'high':
        return AppTheme.error;
      case 'medium':
        return AppTheme.warning;
      default:
        return AppTheme.secondary;
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('AI Insights'),
        actions: [
          IconButton(
              icon: const Icon(Icons.refresh_rounded), onPressed: _loadData),
        ],
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16),
        child: Column(
          children: [
            _attendanceCard(),
            const SizedBox(height: 16),
            _performanceCard(),
          ],
        ),
      ),
    );
  }

  Widget _attendanceCard() {
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: AppTheme.surface,
        borderRadius: BorderRadius.circular(AppTheme.radiusXl),
        border: Border.all(color: AppTheme.border),
      ),
      child: _loadingAttendance
          ? const Center(
              child: Padding(
                  padding: EdgeInsets.all(32),
                  child: CircularProgressIndicator()))
          : Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Container(
                      padding: const EdgeInsets.all(10),
                      decoration: BoxDecoration(
                        color: AppTheme.primary.withOpacity(0.1),
                        borderRadius: BorderRadius.circular(10),
                      ),
                      child: const Icon(Icons.health_and_safety_rounded,
                          color: AppTheme.primary, size: 22),
                    ),
                    const SizedBox(width: 12),
                    const Expanded(
                      child: Text('Attendance Risk',
                          style: TextStyle(
                              fontSize: 18,
                              fontWeight: FontWeight.bold,
                              color: AppTheme.textPrimary)),
                    ),
                    if (_attendanceRisk != null)
                      Container(
                        padding: const EdgeInsets.symmetric(
                            horizontal: 10, vertical: 4),
                        decoration: BoxDecoration(
                          color: _riskColor(_attendanceRisk!['risk_level'])
                              .withOpacity(0.1),
                          borderRadius: BorderRadius.circular(20),
                        ),
                        child: Text(
                          (_attendanceRisk!['risk_level'] ?? 'N/A')
                              .toString()
                              .toUpperCase(),
                          style: TextStyle(
                            fontSize: 11,
                            fontWeight: FontWeight.bold,
                            color: _riskColor(_attendanceRisk!['risk_level']),
                          ),
                        ),
                      ),
                  ],
                ),
                const SizedBox(height: 20),
                if (_attendanceRisk != null) ...[
                  Row(
                    children: [
                      _statItem(
                          'Attendance',
                          '${_attendanceRisk!['attendance_percentage'] ?? 0}%',
                          AppTheme.primary),
                      const SizedBox(width: 8),
                      _statItem(
                          'Present',
                          '${_attendanceRisk!['present'] ?? 0}',
                          AppTheme.secondary),
                      const SizedBox(width: 8),
                      _statItem('Absent', '${_attendanceRisk!['absent'] ?? 0}',
                          AppTheme.error),
                    ],
                  ),
                  const SizedBox(height: 16),
                  ClipRRect(
                    borderRadius: BorderRadius.circular(6),
                    child: LinearProgressIndicator(
                      value: ((_attendanceRisk!['attendance_percentage'] ?? 0)
                              as num) /
                          100,
                      backgroundColor: AppTheme.border,
                      color: _riskColor(_attendanceRisk!['risk_level']),
                      minHeight: 8,
                    ),
                  ),
                  if (_attendanceRisk!['consecutive_absences'] != null &&
                      (_attendanceRisk!['consecutive_absences'] as int) >
                          0) ...[
                    const SizedBox(height: 12),
                    Container(
                      padding: const EdgeInsets.all(10),
                      decoration: BoxDecoration(
                        color: AppTheme.error.withOpacity(0.08),
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: Row(
                        children: [
                          const Icon(Icons.warning_rounded,
                              size: 16, color: AppTheme.error),
                          const SizedBox(width: 8),
                          Text(
                              '${_attendanceRisk!['consecutive_absences']} consecutive absences!',
                              style: const TextStyle(
                                  fontSize: 13,
                                  color: AppTheme.error,
                                  fontWeight: FontWeight.w600)),
                        ],
                      ),
                    ),
                  ],
                  if (_attendanceRisk!['recommendations'] != null)
                    ...(_attendanceRisk!['recommendations'] as List)
                        .map((r) => Padding(
                              padding: const EdgeInsets.symmetric(vertical: 4),
                              child: Row(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  const Icon(Icons.lightbulb_outline,
                                      size: 16, color: AppTheme.warning),
                                  const SizedBox(width: 8),
                                  Expanded(
                                      child: Text(r.toString(),
                                          style: const TextStyle(
                                              fontSize: 13,
                                              color: AppTheme.textSecondary))),
                                ],
                              ),
                            )),
                ] else
                  const Text('Tap refresh to analyze attendance',
                      style: TextStyle(color: AppTheme.textSecondary)),
              ],
            ),
    );
  }

  Widget _performanceCard() {
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: AppTheme.surface,
        borderRadius: BorderRadius.circular(AppTheme.radiusXl),
        border: Border.all(color: AppTheme.border),
      ),
      child: _loadingPerformance
          ? const Center(
              child: Padding(
                  padding: EdgeInsets.all(32),
                  child: CircularProgressIndicator()))
          : Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Container(
                      padding: const EdgeInsets.all(10),
                      decoration: BoxDecoration(
                        color: AppTheme.accentGradient.first.withOpacity(0.1),
                        borderRadius: BorderRadius.circular(10),
                      ),
                      child: const Icon(Icons.trending_up_rounded,
                          color: AppTheme.warning, size: 22),
                    ),
                    const SizedBox(width: 12),
                    const Expanded(
                      child: Text('Performance Prediction',
                          style: TextStyle(
                              fontSize: 18,
                              fontWeight: FontWeight.bold,
                              color: AppTheme.textPrimary)),
                    ),
                  ],
                ),
                const SizedBox(height: 20),
                if (_performance != null) ...[
                  Row(
                    children: [
                      _statItem(
                          'Grade',
                          _performance!['predicted_grade'] ?? 'N/A',
                          AppTheme.primary),
                      const SizedBox(width: 8),
                      _statItem(
                          'Score',
                          '${_performance!['predicted_score'] ?? 0}%',
                          _performance!['risk_level'] == 'high'
                              ? AppTheme.error
                              : AppTheme.secondary),
                      const SizedBox(width: 8),
                      Container(
                        padding: const EdgeInsets.symmetric(
                            horizontal: 10, vertical: 4),
                        decoration: BoxDecoration(
                          color: _riskColor(_performance!['risk_level'])
                              .withOpacity(0.1),
                          borderRadius: BorderRadius.circular(20),
                        ),
                        child: Text(
                          (_performance!['risk_level'] ?? 'N/A')
                              .toString()
                              .toUpperCase(),
                          style: TextStyle(
                            fontSize: 11,
                            fontWeight: FontWeight.bold,
                            color: _riskColor(_performance!['risk_level']),
                          ),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 16),
                  ClipRRect(
                    borderRadius: BorderRadius.circular(6),
                    child: LinearProgressIndicator(
                      value: ((_performance!['predicted_score'] ?? 0) as num) /
                          100,
                      backgroundColor: AppTheme.border,
                      color: _riskColor(_performance!['risk_level']),
                      minHeight: 8,
                    ),
                  ),
                  const SizedBox(height: 12),
                  Text(
                      '${_performance!['present'] ?? 0} present + ${_performance!['excused'] ?? 0} excused / ${_performance!['total_classes'] ?? 0} classes',
                      style: const TextStyle(
                          fontSize: 13, color: AppTheme.textSecondary)),
                  Text(
                      '${_performance!['courses_enrolled'] ?? 0} courses enrolled',
                      style: const TextStyle(
                          fontSize: 13, color: AppTheme.textSecondary)),
                  if (_performance!['recommendations'] != null) ...[
                    const SizedBox(height: 12),
                    const Divider(),
                    const SizedBox(height: 8),
                    const Text('Recommendations',
                        style: TextStyle(
                            fontSize: 15,
                            fontWeight: FontWeight.bold,
                            color: AppTheme.textPrimary)),
                    const SizedBox(height: 8),
                    ...(_performance!['recommendations'] as List)
                        .map((r) => Padding(
                              padding: const EdgeInsets.symmetric(vertical: 3),
                              child: Row(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  const Icon(Icons.check_circle_outline,
                                      size: 16, color: AppTheme.secondary),
                                  const SizedBox(width: 8),
                                  Expanded(
                                      child: Text(r.toString(),
                                          style: const TextStyle(
                                              fontSize: 13,
                                              color: AppTheme.textSecondary))),
                                ],
                              ),
                            )),
                  ],
                ] else
                  const Text('Tap predict to analyze performance',
                      style: TextStyle(color: AppTheme.textSecondary)),
              ],
            ),
    );
  }

  Widget _statItem(String label, String value, Color color) {
    return Expanded(
      child: Container(
        padding: const EdgeInsets.all(12),
        decoration: BoxDecoration(
          color: color.withOpacity(0.06),
          borderRadius: BorderRadius.circular(10),
        ),
        child: Column(
          children: [
            Text(value,
                style: TextStyle(
                    fontSize: 20,
                    fontWeight: FontWeight.bold,
                    color: color,
                    height: 1)),
            const SizedBox(height: 4),
            Text(label,
                style: const TextStyle(
                    fontSize: 11, color: AppTheme.textSecondary)),
          ],
        ),
      ),
    );
  }
}
