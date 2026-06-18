import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../providers/schedule_provider.dart';
import '../models/schedule.dart';
import '../theme.dart';

class ScheduleList extends StatelessWidget {
  const ScheduleList({super.key});

  @override
  Widget build(BuildContext context) {
    final provider = context.watch<ScheduleProvider>();

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
                  onPressed: () => provider.loadSchedules(),
                  child: const Text('Retry')),
            ],
          ),
        ),
      );
    }

    if (provider.schedules.isEmpty) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(32),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Container(
                padding: const EdgeInsets.all(24),
                decoration: BoxDecoration(
                  color: AppTheme.border.withOpacity(0.5),
                  shape: BoxShape.circle,
                ),
                child: const Icon(Icons.schedule_rounded,
                    size: 48, color: AppTheme.textHint),
              ),
              const SizedBox(height: 20),
              const Text('No classes scheduled',
                  style: TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.bold,
                      color: AppTheme.textPrimary)),
              const SizedBox(height: 8),
              const Text('Your schedule will appear here once enrolled',
                  style: TextStyle(fontSize: 14, color: AppTheme.textSecondary),
                  textAlign: TextAlign.center),
            ],
          ),
        ),
      );
    }

    final grouped = _groupByDay(provider.schedules);
    final daysOrder = [
      'Monday',
      'Tuesday',
      'Wednesday',
      'Thursday',
      'Friday',
      'Saturday',
      'Sunday'
    ];
    final today = daysOrder[DateTime.now().weekday - 1];

    return RefreshIndicator(
      onRefresh: () => provider.loadSchedules(),
      child: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          _dayChips(daysOrder, today),
          const SizedBox(height: 16),
          ...daysOrder
              .where((d) => grouped.containsKey(d))
              .map((day) => _daySection(day, grouped[day]!, day == today)),
        ],
      ),
    );
  }

  Widget _dayChips(List<String> days, String today) {
    return SingleChildScrollView(
      scrollDirection: Axis.horizontal,
      child: Row(
        children: days.map((d) {
          final isToday = d == today;
          return Padding(
            padding: const EdgeInsets.only(right: 8),
            child: Chip(
              label: Text(isToday ? 'Today' : d.substring(0, 3),
                  style: TextStyle(
                    fontSize: 13,
                    fontWeight: isToday ? FontWeight.bold : FontWeight.w500,
                    color: isToday ? Colors.white : AppTheme.textSecondary,
                  )),
              backgroundColor: isToday ? AppTheme.primary : AppTheme.surface,
              side: isToday
                  ? BorderSide.none
                  : const BorderSide(color: AppTheme.border),
              padding: const EdgeInsets.symmetric(horizontal: 4),
              visualDensity: VisualDensity.compact,
            ),
          );
        }).toList(),
      ),
    );
  }

  Widget _daySection(String day, List<Schedule> schedules, bool isToday) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Padding(
          padding: const EdgeInsets.only(left: 4, bottom: 8, top: 8),
          child: Row(
            children: [
              Container(
                width: 4,
                height: 18,
                decoration: BoxDecoration(
                  color: isToday ? AppTheme.primary : AppTheme.textHint,
                  borderRadius: BorderRadius.circular(2),
                ),
              ),
              const SizedBox(width: 10),
              Text(
                day,
                style: TextStyle(
                  fontSize: 16,
                  fontWeight: FontWeight.bold,
                  color: isToday ? AppTheme.primary : AppTheme.textPrimary,
                ),
              ),
              if (isToday) ...[
                const SizedBox(width: 8),
                Container(
                  padding:
                      const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                  decoration: BoxDecoration(
                    color: AppTheme.primary.withOpacity(0.1),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: const Text('Today',
                      style: TextStyle(
                          fontSize: 11,
                          fontWeight: FontWeight.bold,
                          color: AppTheme.primary)),
                ),
              ],
            ],
          ),
        ),
        ...schedules.map((s) => _scheduleCard(s)),
        const SizedBox(height: 8),
      ],
    );
  }

  Widget _scheduleCard(Schedule s) {
    final startHour = int.tryParse(s.startTime.split(':').first) ?? 0;
    final isMorning = startHour < 12;

    return Card(
      margin: const EdgeInsets.only(bottom: 8),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Row(
          children: [
            Container(
              width: 56,
              padding: const EdgeInsets.symmetric(vertical: 8),
              decoration: BoxDecoration(
                color: (isMorning ? AppTheme.primary : AppTheme.secondary)
                    .withOpacity(0.1),
                borderRadius: BorderRadius.circular(10),
              ),
              child: Column(
                children: [
                  Text(
                    s.startTime.substring(0, 5),
                    style: TextStyle(
                      fontSize: 14,
                      fontWeight: FontWeight.bold,
                      color: isMorning ? AppTheme.primary : AppTheme.secondary,
                    ),
                  ),
                  const SizedBox(height: 2),
                  Text(
                    s.endTime.substring(0, 5),
                    style: TextStyle(
                      fontSize: 10,
                      color: isMorning ? AppTheme.primary : AppTheme.secondary,
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(width: 16),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    s.courseName,
                    style: const TextStyle(
                        fontSize: 15,
                        fontWeight: FontWeight.w600,
                        color: AppTheme.textPrimary),
                  ),
                  const SizedBox(height: 4),
                  Row(
                    children: [
                      Container(
                        padding: const EdgeInsets.symmetric(
                            horizontal: 8, vertical: 2),
                        decoration: BoxDecoration(
                          color: AppTheme.primary.withOpacity(0.08),
                          borderRadius: BorderRadius.circular(4),
                        ),
                        child: Text(s.courseCode,
                            style: const TextStyle(
                                fontSize: 11,
                                fontWeight: FontWeight.w600,
                                color: AppTheme.primary)),
                      ),
                      if (s.roomNumber != null && s.roomNumber!.isNotEmpty) ...[
                        const SizedBox(width: 8),
                        const Icon(Icons.room_outlined,
                            size: 13, color: AppTheme.textSecondary),
                        const SizedBox(width: 3),
                        Text(s.roomNumber!,
                            style: const TextStyle(
                                fontSize: 12, color: AppTheme.textSecondary)),
                      ],
                    ],
                  ),
                ],
              ),
            ),
            const Icon(Icons.chevron_right_rounded,
                color: AppTheme.textHint, size: 20),
          ],
        ),
      ),
    );
  }

  Map<String, List<Schedule>> _groupByDay(List<Schedule> schedules) {
    final map = <String, List<Schedule>>{};
    for (final s in schedules) {
      map.putIfAbsent(s.dayOfWeek, () => []).add(s);
    }
    for (final day in map.keys) {
      map[day]!.sort((a, b) => a.startTime.compareTo(b.startTime));
    }
    return map;
  }
}
