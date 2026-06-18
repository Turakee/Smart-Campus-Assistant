import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../providers/attendance_provider.dart';
import '../models/attendance.dart';
import '../theme.dart';

class AttendanceSummary extends StatelessWidget {
  const AttendanceSummary({super.key});

  @override
  Widget build(BuildContext context) {
    final provider = context.watch<AttendanceProvider>();

    if (provider.isLoading) {
      return const Center(child: CircularProgressIndicator());
    }

    if (provider.error != null) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(32),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              const Icon(Icons.warning_rounded,
                  size: 48, color: AppTheme.textHint),
              const SizedBox(height: 16),
              Text(provider.error!,
                  style: const TextStyle(color: AppTheme.error)),
              const SizedBox(height: 16),
              ElevatedButton(
                  onPressed: () => provider.loadAttendance(),
                  child: const Text('Retry')),
            ],
          ),
        ),
      );
    }

    return RefreshIndicator(
      onRefresh: () => provider.loadAttendance(),
      child: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          _overallCard(provider),
          const SizedBox(height: 20),
          const Padding(
            padding: EdgeInsets.only(left: 4),
            child: Text('Course-wise Attendance',
                style: TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.bold,
                    color: AppTheme.textPrimary)),
          ),
          const SizedBox(height: 12),
          _courseList(provider),
          const SizedBox(height: 20),
          _riskCard(context, provider),
          const SizedBox(height: 24),
        ],
      ),
    );
  }

  Widget _overallCard(AttendanceProvider provider) {
    final pct = provider.overallPercentage;
    final color = pct >= 75
        ? AppTheme.secondary
        : pct >= 60
            ? AppTheme.warning
            : AppTheme.error;

    return Container(
      padding: const EdgeInsets.all(24),
      decoration: BoxDecoration(
        color: AppTheme.surface,
        borderRadius: BorderRadius.circular(AppTheme.radiusXl),
        border: Border.all(color: AppTheme.border),
      ),
      child: Column(
        children: [
          const Text('Overall Attendance',
              style: TextStyle(
                  fontSize: 18,
                  fontWeight: FontWeight.bold,
                  color: AppTheme.textPrimary)),
          const SizedBox(height: 20),
          SizedBox(
            width: 140,
            height: 140,
            child: Stack(
              alignment: Alignment.center,
              children: [
                SizedBox(
                  width: 140,
                  height: 140,
                  child: CircularProgressIndicator(
                    value: pct / 100,
                    strokeWidth: 10,
                    backgroundColor: AppTheme.border,
                    color: color,
                  ),
                ),
                Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Text('${pct.toStringAsFixed(1)}%',
                        style: TextStyle(
                            fontSize: 32,
                            fontWeight: FontWeight.bold,
                            color: color)),
                    const SizedBox(height: 2),
                    Text(
                        pct >= 75
                            ? 'Good'
                            : pct >= 60
                                ? 'At Risk'
                                : 'Low',
                        style: TextStyle(
                            fontSize: 13,
                            color: color,
                            fontWeight: FontWeight.w500)),
                  ],
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _courseList(AttendanceProvider provider) {
    final courseMap = <int, List<Attendance>>{};
    for (final r in provider.attendanceRecords) {
      courseMap.putIfAbsent(r.courseId, () => []).add(r);
    }

    if (courseMap.isEmpty) {
      return const Padding(
        padding: EdgeInsets.all(16),
        child: Text('No attendance records found',
            style: TextStyle(color: AppTheme.textSecondary),
            textAlign: TextAlign.center),
      );
    }

    return Column(
      children: courseMap.entries.map((entry) {
        final courseId = entry.key;
        final records = entry.value;
        final pct = provider.getAttendancePercentageForCourse(courseId);
        final courseName =
            records.isNotEmpty ? records.first.courseName : 'Unknown';
        final present = records.where((r) => r.isPresent).length;
        final color = pct >= 75
            ? AppTheme.secondary
            : pct >= 60
                ? AppTheme.warning
                : AppTheme.error;

        return Card(
          margin: const EdgeInsets.only(bottom: 8),
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Expanded(
                      child: Text(courseName,
                          style: const TextStyle(
                              fontSize: 15,
                              fontWeight: FontWeight.w600,
                              color: AppTheme.textPrimary)),
                    ),
                    Text('${pct.toStringAsFixed(1)}%',
                        style: TextStyle(
                            fontSize: 18,
                            fontWeight: FontWeight.bold,
                            color: color)),
                  ],
                ),
                const SizedBox(height: 8),
                ClipRRect(
                  borderRadius: BorderRadius.circular(4),
                  child: LinearProgressIndicator(
                    value: pct / 100,
                    backgroundColor: AppTheme.border,
                    color: color,
                    minHeight: 6,
                  ),
                ),
                const SizedBox(height: 6),
                Text('$present/${records.length} classes attended',
                    style: const TextStyle(
                        fontSize: 12, color: AppTheme.textSecondary)),
              ],
            ),
          ),
        );
      }).toList(),
    );
  }

  Widget _riskCard(BuildContext context, AttendanceProvider provider) {
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          colors: AppTheme.accentGradient,
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(AppTheme.radiusLg),
        boxShadow: [
          BoxShadow(
            color: AppTheme.accentGradient.first.withOpacity(0.3),
            blurRadius: 12,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Column(
        children: [
          Row(
            children: [
              Container(
                padding: const EdgeInsets.all(8),
                decoration: BoxDecoration(
                  color: Colors.white.withOpacity(0.2),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: const Icon(Icons.auto_awesome_rounded,
                    color: Colors.white, size: 20),
              ),
              const SizedBox(width: 12),
              const Expanded(
                child: Text('AI Risk Analysis',
                    style: TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.bold,
                        color: Colors.white)),
              ),
            ],
          ),
          const SizedBox(height: 16),
          SizedBox(
            width: double.infinity,
            height: 44,
            child: ElevatedButton.icon(
              onPressed: () async {
                final risk = await provider.predictAttendanceRisk();
                if (risk != null && context.mounted) {
                  _showRiskDialog(context, risk);
                }
              },
              icon: const Icon(Icons.analytics_rounded, size: 18),
              label: const Text('Analyze Now'),
              style: ElevatedButton.styleFrom(
                backgroundColor: Colors.white,
                foregroundColor: AppTheme.accentGradient.first,
              ),
            ),
          ),
        ],
      ),
    );
  }

  void _showRiskDialog(BuildContext context, Map<String, dynamic> risk) {
    final level = (risk['risk_level'] as String? ?? 'low').toLowerCase();
    final color = level == 'high'
        ? AppTheme.error
        : level == 'medium'
            ? AppTheme.warning
            : AppTheme.secondary;

    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(AppTheme.radiusLg)),
        title: Row(
          children: [
            Icon(Icons.analytics_rounded, color: color, size: 24),
            const SizedBox(width: 10),
            const Text('Risk Analysis',
                style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
          ],
        ),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            _riskRow('Risk Level', level.toUpperCase(), color),
            const SizedBox(height: 8),
            _riskRow('Attendance', '${risk['attendance_percentage']}%',
                AppTheme.primary),
            const SizedBox(height: 8),
            _riskRow(
                'Recommendation',
                (risk['recommendations'] as List?)?.first?.toString() ?? 'N/A',
                AppTheme.textSecondary),
          ],
        ),
        actions: [
          TextButton(
              onPressed: () => Navigator.of(ctx).pop(),
              child: const Text('Done')),
        ],
      ),
    );
  }

  Widget _riskRow(String label, String value, Color color) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(label,
            style:
                const TextStyle(fontSize: 12, color: AppTheme.textSecondary)),
        const SizedBox(height: 2),
        Text(value,
            style: TextStyle(
                fontSize: 14, fontWeight: FontWeight.w600, color: color)),
      ],
    );
  }
}
