import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../providers/auth_provider.dart';
import '../providers/stats_provider.dart';
import '../providers/course_provider.dart';
import '../providers/schedule_provider.dart';
import '../providers/attendance_provider.dart';
import '../providers/booking_provider.dart';
import '../providers/notification_provider.dart';
import '../widgets/dashboard_card.dart';
import '../widgets/schedule_list.dart';
import '../widgets/attendance_summary.dart';
import '../widgets/notification_badge.dart';
import '../models/user.dart';
import '../models/booking.dart';
import '../theme.dart';
import 'profile_screen.dart';
import 'chatbot_screen.dart';
import 'ai_insights_screen.dart';
import 'booking_form_screen.dart';
import 'courses_screen.dart';

class DashboardScreen extends StatefulWidget {
  const DashboardScreen({super.key});

  @override
  State<DashboardScreen> createState() => _DashboardScreenState();
}

class _DashboardScreenState extends State<DashboardScreen> {
  int _selectedIndex = 0;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      _loadData();
    });
  }

  void _loadData() {
    context.read<StatsProvider>().loadStats();
    context.read<ScheduleProvider>().loadSchedules();
    context.read<AttendanceProvider>().loadAttendance();
    context.read<BookingProvider>().loadBookings();
    context.read<NotificationProvider>().loadNotifications();
    context.read<CourseProvider>().loadMyCourses();
  }

  @override
  Widget build(BuildContext context) {
    final user = context.watch<AuthProvider>().user;
    final notifProvider = context.watch<NotificationProvider>();
    final attendanceProvider = context.watch<AttendanceProvider>();
    final scheduleProvider = context.watch<ScheduleProvider>();
    final statsProvider = context.watch<StatsProvider>();
    final bookingProvider = context.watch<BookingProvider>();

    final courseProvider = context.watch<CourseProvider>();

    final screens = <Widget>[
      _buildHome(user, attendanceProvider, scheduleProvider, statsProvider,
          bookingProvider, courseProvider),
      const ScheduleList(),
      const AttendanceSummary(),
      _buildBookings(bookingProvider),
      _buildNotifications(notifProvider),
    ];

    return Scaffold(
      appBar: AppBar(
        title: Text(_selectedIndex == 0 ? 'Home' : _titles[_selectedIndex]),
        leading: _selectedIndex == 0
            ? Padding(
                padding: const EdgeInsets.only(left: 16),
                child: CircleAvatar(
                  backgroundColor: AppTheme.primary.withOpacity(0.1),
                  child: Text(
                    (user?.name ?? 'S')[0].toUpperCase(),
                    style: const TextStyle(
                      color: AppTheme.primary,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                ),
              )
            : null,
        actions: _selectedIndex == 4
            ? [
                IconButton(
                  icon: const Icon(Icons.done_all_rounded),
                  tooltip: 'Mark all read',
                  onPressed: notifProvider.notifications.any((n) => !n.isRead)
                      ? () => notifProvider.markAllAsRead()
                      : null,
                ),
                IconButton(
                  icon: const Icon(Icons.clear_all_rounded),
                  tooltip: 'Clear all',
                  onPressed: notifProvider.notifications.isNotEmpty
                      ? () => notifProvider.clearAll()
                      : null,
                ),
                IconButton(
                  icon: const Icon(Icons.refresh_rounded),
                  onPressed: _loadData,
                ),
              ]
            : [
                IconButton(
                  icon: const Icon(Icons.refresh_rounded),
                  onPressed: _loadData,
                ),
                IconButton(
                  icon: const Icon(Icons.person_outline),
                  onPressed: () => Navigator.of(context).push(
                      MaterialPageRoute(builder: (_) => const ProfileScreen())),
                ),
              ],
      ),
      body: AnimatedSwitcher(
        duration: const Duration(milliseconds: 250),
        child: KeyedSubtree(
          key: ValueKey(_selectedIndex),
          child: screens[_selectedIndex],
        ),
      ),
      bottomNavigationBar: NavigationBar(
        selectedIndex: _selectedIndex,
        onDestinationSelected: (i) => setState(() => _selectedIndex = i),
        animationDuration: const Duration(milliseconds: 300),
        destinations: [
          const NavigationDestination(
              icon: Icon(Icons.home_rounded), label: 'Home'),
          const NavigationDestination(
              icon: Icon(Icons.schedule_rounded), label: 'Schedule'),
          const NavigationDestination(
              icon: Icon(Icons.check_circle_rounded), label: 'Attendance'),
          const NavigationDestination(
              icon: Icon(Icons.book_rounded), label: 'Bookings'),
          NavigationDestination(
            icon: NotificationBadge(
              icon: const Icon(Icons.notifications_rounded),
              count: notifProvider.unreadCount,
            ),
            label: 'Alerts',
          ),
        ],
      ),
    );
  }

  static const _titles = [
    'Home',
    'Schedule',
    'Attendance',
    'Bookings',
    'Alerts'
  ];

  Widget _buildHome(
      User? user,
      AttendanceProvider attendance,
      ScheduleProvider schedule,
      StatsProvider stats,
      BookingProvider bookings,
      CourseProvider courses) {
    final approvedBookings =
        bookings.bookings.where((b) => b.isApproved).take(3).toList();
    final pendingBookings =
        bookings.bookings.where((b) => b.isPending).take(3).toList();

    return RefreshIndicator(
      onRefresh: () async => _loadData(),
      child: SingleChildScrollView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Hello, ${user?.name ?? 'Student'}!',
              style: const TextStyle(
                fontSize: 26,
                fontWeight: FontWeight.bold,
                color: AppTheme.textPrimary,
              ),
            ),
            const SizedBox(height: 4),
            Text(
              user?.department ?? 'Manage your academic life',
              style: const TextStyle(
                fontSize: 14,
                color: AppTheme.textSecondary,
              ),
            ),
            const SizedBox(height: 24),
            Row(
              children: [
                Expanded(
                  child: DashboardCard(
                    title: 'Courses',
                    value: stats.isLoading ? '...' : '${stats.courses}',
                    icon: Icons.school_rounded,
                    gradient: AppTheme.primaryGradient,
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: DashboardCard(
                    title: 'Attendance',
                    value: stats.isLoading
                        ? '...'
                        : '${stats.attendanceRate.toStringAsFixed(1)}%',
                    icon: Icons.check_circle_rounded,
                    gradient: AppTheme.secondaryGradient,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 12),
            Row(
              children: [
                Expanded(
                  child: DashboardCard(
                    title: 'Pending',
                    value: stats.isLoading ? '...' : '${stats.pendingBookings}',
                    icon: Icons.pending_actions_rounded,
                    gradient: AppTheme.accentGradient,
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: DashboardCard(
                    title: 'Approved',
                    value:
                        stats.isLoading ? '...' : '${stats.approvedBookings}',
                    icon: Icons.verified_rounded,
                    gradient: AppTheme.successGradient,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 16),
            _sectionHeader('Today\'s Schedule', Icons.schedule_rounded, null),
            const SizedBox(height: 8),
            ..._todayScheduleCards(schedule),
            const SizedBox(height: 12),
            Row(
              children: [
                Expanded(
                  child: DashboardCard(
                    title: 'AI Insights',
                    value: 'Tap to view',
                    icon: Icons.auto_awesome_rounded,
                    gradient: AppTheme.accentGradient,
                    onTap: () => Navigator.of(context).push(
                      MaterialPageRoute(
                          builder: (_) => const AIInsightsScreen()),
                    ),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: DashboardCard(
                    title: 'AI Chat',
                    value: 'Tap to ask',
                    icon: Icons.smart_toy_rounded,
                    gradient: const [Color(0xFF8B5CF6), Color(0xFFA78BFA)],
                    onTap: () => Navigator.of(context).push(
                      MaterialPageRoute(builder: (_) => const ChatbotScreen()),
                    ),
                  ),
                ),
              ],
            ),
            if (pendingBookings.isNotEmpty) ...[
              const SizedBox(height: 20),
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  const Text('Pending Bookings',
                      style: TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.bold,
                          color: AppTheme.textPrimary)),
                  TextButton(
                    onPressed: () => setState(() => _selectedIndex = 3),
                    child: const Text('View All'),
                  ),
                ],
              ),
              ...pendingBookings.map((b) => _miniBookingCard(b, pending: true)),
            ],
            if (approvedBookings.isNotEmpty) ...[
              const SizedBox(height: 16),
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  const Text('Approved Bookings',
                      style: TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.bold,
                          color: AppTheme.textPrimary)),
                  TextButton(
                    onPressed: () => setState(() => _selectedIndex = 3),
                    child: const Text('View All'),
                  ),
                ],
              ),
              ...approvedBookings
                  .map((b) => _miniBookingCard(b, pending: false)),
            ],
            const SizedBox(height: 20),
            _sectionHeader('My Enrolled Courses', Icons.book_rounded, null),
            const SizedBox(height: 8),
            ..._enrolledCoursesSection(courses),
            const SizedBox(height: 16),
            _sectionHeader(
                'Recent Attendance', Icons.check_circle_rounded, null),
            const SizedBox(height: 8),
            ..._recentAttendanceSection(attendance),
            const SizedBox(height: 20),
            const Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text(
                  'Quick Actions',
                  style: TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.bold,
                    color: AppTheme.textPrimary,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 14),
            LayoutBuilder(
              builder: (context, constraints) {
                const spacing = 10.0;
                final itemWidth = (constraints.maxWidth - spacing * 3) / 4;
                return Wrap(
                  spacing: spacing,
                  runSpacing: spacing,
                  children: [
                    SizedBox(
                      width: itemWidth,
                      child: schedule.isOptimizing
                          ? _quickAction(
                              icon: Icons.hourglass_top_rounded,
                              label: 'Working...',
                              gradient: AppTheme.accentGradient,
                              onTap: null,
                            )
                          : _quickAction(
                              icon: Icons.bolt_rounded,
                              label: 'Optimize',
                              gradient: AppTheme.accentGradient,
                              onTap: () async {
                                final p = context.read<ScheduleProvider>();
                                final result = await p.optimizeSchedule();
                                if (!context.mounted) return;
                                if (result != null) {
                                  _showOptimizeResult(context, result);
                                } else if (p.error != null) {
                                  ScaffoldMessenger.of(context).showSnackBar(
                                    SnackBar(
                                      content: Text(p.error!),
                                      backgroundColor: AppTheme.error,
                                    ),
                                  );
                                }
                              },
                            ),
                    ),
                    SizedBox(
                      width: itemWidth,
                      child: _quickAction(
                        icon: Icons.add_circle_rounded,
                        label: 'Booking',
                        gradient: AppTheme.primaryGradient,
                        onTap: () => Navigator.of(context).push(
                          MaterialPageRoute(
                              builder: (_) => const BookingFormScreen()),
                        ),
                      ),
                    ),
                    SizedBox(
                      width: itemWidth,
                      child: _quickAction(
                        icon: Icons.library_books_rounded,
                        label: 'Courses',
                        gradient: AppTheme.successGradient,
                        onTap: () => Navigator.of(context).push(
                          MaterialPageRoute(
                              builder: (_) => const CoursesScreen()),
                        ),
                      ),
                    ),
                    SizedBox(
                      width: itemWidth,
                      child: _quickAction(
                        icon: Icons.person_rounded,
                        label: 'Profile',
                        gradient: const [Color(0xFF8B5CF6), Color(0xFFA78BFA)],
                        onTap: () => Navigator.of(context).push(
                          MaterialPageRoute(
                              builder: (_) => const ProfileScreen()),
                        ),
                      ),
                    ),
                  ],
                );
              },
            ),
            const SizedBox(height: 24),
          ],
        ),
      ),
    );
  }

  Widget _sectionHeader(String title, IconData icon, VoidCallback? onTap) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Row(
          children: [
            Icon(icon, size: 18, color: AppTheme.primary),
            const SizedBox(width: 8),
            Text(title,
                style: const TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.bold,
                    color: AppTheme.textPrimary)),
          ],
        ),
        if (onTap != null)
          TextButton(onPressed: onTap, child: const Text('View All')),
      ],
    );
  }

  List<Widget> _todayScheduleCards(ScheduleProvider schedule) {
    if (schedule.isLoading) {
      return const [
        Card(
          child: Padding(
            padding: EdgeInsets.all(20),
            child: Center(child: Text('Loading...')),
          ),
        ),
      ];
    }
    if (schedule.schedules.isEmpty) {
      return const [
        Card(
          child: Padding(
            padding: EdgeInsets.all(20),
            child: Center(
              child: Text('No classes today',
                  style: TextStyle(color: AppTheme.textSecondary)),
            ),
          ),
        ),
      ];
    }
    final days = [
      'Monday',
      'Tuesday',
      'Wednesday',
      'Thursday',
      'Friday',
      'Saturday',
      'Sunday'
    ];
    final today = days[DateTime.now().weekday - 1];
    final todayClasses = schedule.getSchedulesForDay(today);
    if (todayClasses.isEmpty) {
      return const [
        Card(
          child: Padding(
            padding: EdgeInsets.all(20),
            child: Center(
              child: Text('No classes today',
                  style: TextStyle(color: AppTheme.textSecondary)),
            ),
          ),
        ),
      ];
    }
    return todayClasses.take(3).map((s) {
      return Card(
        margin: const EdgeInsets.only(bottom: 6),
        child: ListTile(
          dense: true,
          contentPadding:
              const EdgeInsets.symmetric(horizontal: 12, vertical: 2),
          leading: Container(
            width: 44,
            padding: const EdgeInsets.symmetric(vertical: 6),
            decoration: BoxDecoration(
              color: AppTheme.primary.withOpacity(0.1),
              borderRadius: BorderRadius.circular(8),
            ),
            child: Column(
              children: [
                Text(s.startTime.substring(0, 5),
                    style: const TextStyle(
                        fontSize: 11,
                        fontWeight: FontWeight.bold,
                        color: AppTheme.primary)),
              ],
            ),
          ),
          title: Text(s.courseName,
              style:
                  const TextStyle(fontSize: 13, fontWeight: FontWeight.w600)),
          subtitle: Text('${s.courseCode}  •  ${s.roomNumber ?? 'TBA'}',
              style: const TextStyle(fontSize: 11)),
        ),
      );
    }).toList();
  }

  List<Widget> _enrolledCoursesSection(CourseProvider courses) {
    if (courses.isLoading) {
      return const [
        Card(
          child: Padding(
            padding: EdgeInsets.all(20),
            child: Center(child: Text('Loading...')),
          ),
        ),
      ];
    }
    if (courses.myCourses.isEmpty) {
      return const [
        Card(
          child: Padding(
            padding: EdgeInsets.all(20),
            child: Center(
              child: Text('No enrolled courses yet',
                  style: TextStyle(color: AppTheme.textSecondary)),
            ),
          ),
        ),
      ];
    }
    return courses.myCourses.take(5).map((c) {
      return Card(
        margin: const EdgeInsets.only(bottom: 6),
        child: ListTile(
          dense: true,
          contentPadding:
              const EdgeInsets.symmetric(horizontal: 12, vertical: 2),
          leading: Container(
            padding: const EdgeInsets.all(6),
            decoration: BoxDecoration(
              color: AppTheme.primary.withOpacity(0.1),
              borderRadius: BorderRadius.circular(8),
            ),
            child: const Icon(Icons.book_rounded,
                color: AppTheme.primary, size: 18),
          ),
          title: Text(c.courseName,
              style:
                  const TextStyle(fontSize: 13, fontWeight: FontWeight.w600)),
          subtitle: Text('${c.courseCode}  •  ${c.creditHours ?? 3} Credits',
              style: const TextStyle(fontSize: 11)),
        ),
      );
    }).toList();
  }

  List<Widget> _recentAttendanceSection(AttendanceProvider attendance) {
    if (attendance.isLoading) {
      return const [
        Card(
          child: Padding(
            padding: EdgeInsets.all(20),
            child: Center(child: Text('Loading...')),
          ),
        ),
      ];
    }
    if (attendance.attendanceRecords.isEmpty) {
      return const [
        Card(
          child: Padding(
            padding: EdgeInsets.all(20),
            child: Center(
              child: Text('No attendance records',
                  style: TextStyle(color: AppTheme.textSecondary)),
            ),
          ),
        ),
      ];
    }
    return attendance.attendanceRecords.take(5).map((a) {
      final statusColor = a.isPresent
          ? AppTheme.secondary
          : a.isLate
              ? AppTheme.warning
              : a.isExcused
                  ? AppTheme.primary
                  : AppTheme.error;
      final statusLabel = a.isPresent
          ? 'Present'
          : a.isLate
              ? 'Late'
              : a.isExcused
                  ? 'Excused'
                  : 'Absent';
      return Card(
        margin: const EdgeInsets.only(bottom: 6),
        child: ListTile(
          dense: true,
          contentPadding:
              const EdgeInsets.symmetric(horizontal: 12, vertical: 2),
          leading: Container(
            padding: const EdgeInsets.all(6),
            decoration: BoxDecoration(
              color: statusColor.withOpacity(0.1),
              borderRadius: BorderRadius.circular(8),
            ),
            child: Icon(
              a.isPresent ? Icons.check_circle_rounded : Icons.cancel_rounded,
              color: statusColor,
              size: 18,
            ),
          ),
          title: Text(a.courseName,
              style:
                  const TextStyle(fontSize: 13, fontWeight: FontWeight.w600)),
          subtitle: Text(a.date, style: const TextStyle(fontSize: 11)),
          trailing: Container(
            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
            decoration: BoxDecoration(
              color: statusColor.withOpacity(0.1),
              borderRadius: BorderRadius.circular(12),
            ),
            child: Text(statusLabel,
                style: TextStyle(
                    fontSize: 10,
                    fontWeight: FontWeight.bold,
                    color: statusColor)),
          ),
        ),
      );
    }).toList();
  }

  Widget _miniBookingCard(Booking booking, {bool pending = false}) {
    final color = pending ? AppTheme.warning : AppTheme.secondary;
    return Card(
      margin: const EdgeInsets.only(bottom: 6),
      child: ListTile(
        dense: true,
        contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 2),
        leading: Container(
          padding: const EdgeInsets.all(6),
          decoration: BoxDecoration(
            color: color.withOpacity(0.1),
            borderRadius: BorderRadius.circular(8),
          ),
          child: Icon(Icons.meeting_room_rounded, color: color, size: 18),
        ),
        title: Text(booking.resourceName,
            style: const TextStyle(
                fontSize: 13,
                fontWeight: FontWeight.w600,
                color: AppTheme.textPrimary)),
        subtitle: Text(booking.bookingDate,
            style:
                const TextStyle(fontSize: 11, color: AppTheme.textSecondary)),
        trailing: Container(
          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
          decoration: BoxDecoration(
            color: color.withOpacity(0.1),
            borderRadius: BorderRadius.circular(12),
          ),
          child: Text(booking.status.toUpperCase(),
              style: TextStyle(
                  fontSize: 10, fontWeight: FontWeight.bold, color: color)),
        ),
      ),
    );
  }

  Widget _quickAction({
    required IconData icon,
    required String label,
    required List<Color> gradient,
    VoidCallback? onTap,
  }) {
    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(AppTheme.radiusMd),
        child: Container(
          padding: const EdgeInsets.symmetric(vertical: 16, horizontal: 6),
          decoration: BoxDecoration(
            gradient: LinearGradient(
                colors: gradient,
                begin: Alignment.topLeft,
                end: Alignment.bottomRight),
            borderRadius: BorderRadius.circular(AppTheme.radiusMd),
            boxShadow: [
              BoxShadow(
                color: gradient.first.withOpacity(0.25),
                blurRadius: 10,
                offset: const Offset(0, 3),
              ),
            ],
          ),
          child: Column(
            children: [
              Icon(icon, color: Colors.white, size: 22),
              const SizedBox(height: 8),
              Text(
                label,
                style: const TextStyle(
                  color: Colors.white,
                  fontSize: 10,
                  fontWeight: FontWeight.w600,
                  height: 1.2,
                ),
                textAlign: TextAlign.center,
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildBookings(BookingProvider bookingProvider) {
    if (bookingProvider.isLoading) {
      return const Center(child: CircularProgressIndicator());
    }
    if (bookingProvider.bookings.isEmpty) {
      return _emptyState(Icons.book_rounded, 'No bookings yet',
          'Book a room or facility to get started');
    }
    return RefreshIndicator(
      onRefresh: () async => context.read<BookingProvider>().loadBookings(),
      child: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          Padding(
            padding: const EdgeInsets.only(bottom: 16),
            child: SizedBox(
              width: double.infinity,
              height: 48,
              child: ElevatedButton.icon(
                onPressed: () => Navigator.of(context).push(
                  MaterialPageRoute(builder: (_) => const BookingFormScreen()),
                ),
                icon: const Icon(Icons.add_rounded),
                label: const Text('New Booking'),
              ),
            ),
          ),
          ...bookingProvider.bookings.map((booking) {
            final statusColor = booking.isApproved
                ? AppTheme.secondary
                : booking.isPending
                    ? AppTheme.warning
                    : AppTheme.error;
            return Card(
              margin: const EdgeInsets.only(bottom: 8),
              child: ListTile(
                contentPadding:
                    const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                leading: Container(
                  padding: const EdgeInsets.all(10),
                  decoration: BoxDecoration(
                    color: statusColor.withOpacity(0.1),
                    borderRadius: BorderRadius.circular(10),
                  ),
                  child: Icon(Icons.meeting_room_rounded,
                      color: statusColor, size: 22),
                ),
                title: Text(booking.resourceName,
                    style: const TextStyle(fontWeight: FontWeight.w600)),
                subtitle: Text(
                  '${booking.bookingDate}\n${booking.startTime} - ${booking.endTime}',
                  style: const TextStyle(
                      fontSize: 12, color: AppTheme.textSecondary),
                ),
                trailing: Container(
                  padding:
                      const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                  decoration: BoxDecoration(
                    color: statusColor.withOpacity(0.1),
                    borderRadius: BorderRadius.circular(20),
                  ),
                  child: Text(
                    booking.status.toUpperCase(),
                    style: TextStyle(
                        fontSize: 11,
                        fontWeight: FontWeight.bold,
                        color: statusColor),
                  ),
                ),
              ),
            );
          }),
        ],
      ),
    );
  }

  Widget _buildNotifications(NotificationProvider provider) {
    if (provider.isLoading) {
      return const Center(child: CircularProgressIndicator());
    }
    if (provider.error != null) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              const Icon(Icons.error_outline,
                  size: 56, color: Colors.redAccent),
              const SizedBox(height: 16),
              Text(provider.error!,
                  textAlign: TextAlign.center,
                  style: const TextStyle(
                      fontSize: 16, color: AppTheme.textPrimary)),
              const SizedBox(height: 16),
              ElevatedButton(
                onPressed: provider.loadNotifications,
                child: const Text('Retry'),
              ),
            ],
          ),
        ),
      );
    }
    if (provider.notifications.isEmpty) {
      return _emptyState(Icons.notifications_rounded, 'No notifications',
          'You\'re all caught up!');
    }
    return RefreshIndicator(
      onRefresh: () async => provider.loadNotifications(),
      child: ListView.builder(
        padding: const EdgeInsets.all(16),
        itemCount: provider.notifications.length,
        itemBuilder: (context, index) {
          final notif = provider.notifications[index];
          final typeColor = notif.type == 'danger'
              ? AppTheme.error
              : notif.type == 'warning'
                  ? AppTheme.warning
                  : AppTheme.primary;
          return Card(
            color: notif.isRead ? null : AppTheme.primary.withOpacity(0.03),
            margin: const EdgeInsets.only(bottom: 8),
            child: ListTile(
              contentPadding:
                  const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
              leading: Container(
                padding: const EdgeInsets.all(8),
                decoration: BoxDecoration(
                  color: typeColor.withOpacity(0.1),
                  borderRadius: BorderRadius.circular(10),
                ),
                child: Icon(
                  notif.type == 'danger'
                      ? Icons.warning_rounded
                      : Icons.info_rounded,
                  color: typeColor,
                  size: 20,
                ),
              ),
              title: Text(notif.message,
                  style: const TextStyle(
                      fontSize: 14, fontWeight: FontWeight.w500)),
              subtitle: Text(notif.createdAt,
                  style:
                      const TextStyle(fontSize: 12, color: AppTheme.textHint)),
              trailing: !notif.isRead
                  ? Container(
                      width: 8,
                      height: 8,
                      decoration: const BoxDecoration(
                        color: AppTheme.primary,
                        shape: BoxShape.circle,
                      ),
                    )
                  : null,
              onTap: () {
                if (!notif.isRead) provider.markAsRead(notif.notificationId);
              },
            ),
          );
        },
      ),
    );
  }

  void _showOptimizeResult(BuildContext context, Map<String, dynamic> result) {
    final score = result['optimization_score'];
    final conflicts = result['conflicts_resolved'] ?? 0;
    final slots = (result['optimized_slots'] as List?) ?? [];
    final engine =
        result['ai_engine_used'] == true ? 'AI Engine' : 'Built-in Optimizer';

    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        title: Row(
          children: [
            Container(
              padding: const EdgeInsets.all(8),
              decoration: BoxDecoration(
                color: AppTheme.secondary.withOpacity(0.1),
                borderRadius: BorderRadius.circular(12),
              ),
              child: const Icon(Icons.auto_awesome_rounded,
                  color: AppTheme.secondary, size: 22),
            ),
            const SizedBox(width: 12),
            const Expanded(
              child: Text('Optimization Complete',
                  style: TextStyle(fontSize: 17, fontWeight: FontWeight.bold)),
            ),
          ],
        ),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                const Text('Score: ',
                    style:
                        TextStyle(fontSize: 14, color: AppTheme.textSecondary)),
                Text('$score/100',
                    style: const TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.bold,
                        color: AppTheme.secondary)),
              ],
            ),
            const SizedBox(height: 6),
            Row(
              children: [
                const Text('Conflicts resolved: ',
                    style:
                        TextStyle(fontSize: 14, color: AppTheme.textSecondary)),
                Text('$conflicts',
                    style: const TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.bold,
                        color: AppTheme.primary)),
              ],
            ),
            const SizedBox(height: 6),
            Row(
              children: [
                const Text('Engine: ',
                    style:
                        TextStyle(fontSize: 14, color: AppTheme.textSecondary)),
                Text(engine,
                    style: const TextStyle(
                        fontSize: 14,
                        fontWeight: FontWeight.w500,
                        color: AppTheme.textPrimary)),
              ],
            ),
            if (slots.isNotEmpty) ...[
              const SizedBox(height: 12),
              const Text('Optimized Slots:',
                  style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold)),
              const SizedBox(height: 6),
              SizedBox(
                width: double.infinity,
                child: Wrap(
                  spacing: 6,
                  runSpacing: 4,
                  children: slots.take(8).map<Widget>((s) {
                    final day = s['day'] ?? '';
                    final time = s['time'] ?? '';
                    final code = s['course_code'] ?? '';
                    return Container(
                      padding: const EdgeInsets.symmetric(
                          horizontal: 8, vertical: 4),
                      decoration: BoxDecoration(
                        color: AppTheme.primary.withOpacity(0.08),
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: Text('$day $time  $code',
                          style: const TextStyle(
                              fontSize: 11, color: AppTheme.primary)),
                    );
                  }).toList(),
                ),
              ),
            ],
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx),
            child: const Text('Done'),
          ),
        ],
      ),
    );
  }

  Widget _emptyState(IconData icon, String title, String subtitle) {
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
              child: Icon(icon, size: 48, color: AppTheme.textHint),
            ),
            const SizedBox(height: 20),
            Text(title,
                style: const TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.bold,
                    color: AppTheme.textPrimary)),
            const SizedBox(height: 8),
            Text(subtitle,
                style: const TextStyle(
                    fontSize: 14, color: AppTheme.textSecondary),
                textAlign: TextAlign.center),
          ],
        ),
      ),
    );
  }
}
