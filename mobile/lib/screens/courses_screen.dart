import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../providers/course_provider.dart';
import '../models/course.dart';
import '../theme.dart';

class CoursesScreen extends StatefulWidget {
  const CoursesScreen({super.key});

  @override
  State<CoursesScreen> createState() => _CoursesScreenState();
}

class _CoursesScreenState extends State<CoursesScreen>
    with SingleTickerProviderStateMixin {
  late TabController _tabController;

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 2, vsync: this);
    _loadData();
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  void _loadData() {
    context.read<CourseProvider>().loadMyCourses();
    context.read<CourseProvider>().loadAvailableCourses();
  }

  @override
  Widget build(BuildContext context) {
    final provider = context.watch<CourseProvider>();
    final enrolledIds = provider.myCourses.map((c) => c.courseId).toSet();

    return Scaffold(
      appBar: AppBar(
        title: const Text('My Courses'),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh_rounded),
            onPressed: _loadData,
          ),
        ],
        bottom: TabBar(
          controller: _tabController,
          tabs: const [
            Tab(text: 'Enrolled'),
            Tab(text: 'Available'),
          ],
        ),
      ),
      body: provider.isLoading
          ? const Center(child: CircularProgressIndicator())
          : TabBarView(
              controller: _tabController,
              children: [
                _buildEnrolledTab(provider, enrolledIds),
                _buildAvailableTab(provider, enrolledIds),
              ],
            ),
    );
  }

  Widget _buildEnrolledTab(CourseProvider provider, Set<int> enrolledIds) {
    if (provider.myCourses.isEmpty) {
      return _emptyState(Icons.library_books_rounded, 'No courses enrolled',
          'Browse available courses and enroll to get started');
    }

    final totalCredits =
        provider.myCourses.fold<int>(0, (sum, c) => sum + (c.creditHours ?? 0));

    return RefreshIndicator(
      onRefresh: () async {
        await provider.loadMyCourses();
        await provider.loadAvailableCourses();
      },
      child: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              gradient: const LinearGradient(
                  colors: [Color(0xFF4F46E5), Color(0xFF7C3AED)]),
              borderRadius: BorderRadius.circular(AppTheme.radiusLg),
            ),
            child: Row(
              children: [
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text('${provider.myCourses.length} Courses',
                          style: const TextStyle(
                              fontSize: 24,
                              fontWeight: FontWeight.bold,
                              color: Colors.white)),
                      Text('$totalCredits Total Credits',
                          style: TextStyle(
                              fontSize: 14,
                              color: Colors.white.withOpacity(0.8))),
                    ],
                  ),
                ),
                Container(
                  padding: const EdgeInsets.all(10),
                  decoration: BoxDecoration(
                    color: Colors.white.withOpacity(0.2),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: const Icon(Icons.school_rounded,
                      color: Colors.white, size: 28),
                ),
              ],
            ),
          ),
          const SizedBox(height: 16),
          ...provider.myCourses.map((course) =>
              _courseCard(course, isEnrolled: true, provider: provider)),
        ],
      ),
    );
  }

  Widget _buildAvailableTab(CourseProvider provider, Set<int> enrolledIds) {
    if (provider.availableCourses.isEmpty) {
      return _emptyState(Icons.search_rounded, 'No courses available',
          'All available courses are displayed here');
    }

    return RefreshIndicator(
      onRefresh: () async {
        await provider.loadAvailableCourses();
        await provider.loadMyCourses();
      },
      child: ListView(
        padding: const EdgeInsets.all(16),
        children: provider.availableCourses.map((course) {
          final enrolled = enrolledIds.contains(course.courseId);
          return _courseCard(course, isEnrolled: enrolled, provider: provider);
        }).toList(),
      ),
    );
  }

  Widget _courseCard(Course course,
      {required bool isEnrolled, required CourseProvider provider}) {
    return Card(
      margin: const EdgeInsets.only(bottom: 10),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Container(
                  padding:
                      const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                  decoration: BoxDecoration(
                    color: AppTheme.primary.withOpacity(0.1),
                    borderRadius: BorderRadius.circular(6),
                  ),
                  child: Text(course.courseCode,
                      style: const TextStyle(
                          fontSize: 12,
                          fontWeight: FontWeight.bold,
                          color: AppTheme.primary)),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: Text(course.courseName,
                      style: const TextStyle(
                          fontSize: 15,
                          fontWeight: FontWeight.w600,
                          color: AppTheme.textPrimary)),
                ),
              ],
            ),
            const SizedBox(height: 10),
            Row(
              children: [
                if (course.creditHours != null) ...[
                  const Icon(Icons.hourglass_empty,
                      size: 14, color: AppTheme.textSecondary),
                  const SizedBox(width: 4),
                  Text('${course.creditHours} Credits',
                      style: const TextStyle(
                          fontSize: 12, color: AppTheme.textSecondary)),
                  const SizedBox(width: 16),
                ],
                if (course.lecturerName != null) ...[
                  const Icon(Icons.person_outline,
                      size: 14, color: AppTheme.textSecondary),
                  const SizedBox(width: 4),
                  Expanded(
                    child: Text(course.lecturerName!,
                        style: const TextStyle(
                            fontSize: 12, color: AppTheme.textSecondary)),
                  ),
                ],
              ],
            ),
            const SizedBox(height: 12),
            SizedBox(
              width: double.infinity,
              child: ElevatedButton.icon(
                onPressed: isEnrolled
                    ? () => _confirmUnenroll(context, course, provider)
                    : () => _enroll(context, course, provider),
                icon: Icon(
                    isEnrolled
                        ? Icons.exit_to_app_rounded
                        : Icons.add_circle_outline_rounded,
                    size: 18),
                label: Text(isEnrolled ? 'Drop Course' : 'Enroll'),
                style: ElevatedButton.styleFrom(
                  backgroundColor: isEnrolled
                      ? AppTheme.error.withOpacity(0.1)
                      : AppTheme.primary,
                  foregroundColor: isEnrolled ? AppTheme.error : Colors.white,
                  elevation: 0,
                  padding: const EdgeInsets.symmetric(vertical: 14),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Future<void> _enroll(
      BuildContext context, Course course, CourseProvider provider) async {
    final success = await provider.enroll(course.courseId);
    if (context.mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(success
              ? 'Enrolled in ${course.courseName}'
              : (provider.error ?? 'Failed to enroll')),
          backgroundColor: success ? AppTheme.secondary : AppTheme.error,
        ),
      );
    }
  }

  Future<void> _confirmUnenroll(
      BuildContext context, Course course, CourseProvider provider) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(AppTheme.radiusLg)),
        title: const Text('Drop Course'),
        content: Text(
            'Are you sure you want to drop ${course.courseName} (${course.courseCode})?'),
        actions: [
          TextButton(
              onPressed: () => Navigator.of(ctx).pop(false),
              child: const Text('Cancel')),
          ElevatedButton(
            onPressed: () => Navigator.of(ctx).pop(true),
            style: ElevatedButton.styleFrom(backgroundColor: AppTheme.error),
            child: const Text('Drop', style: TextStyle(color: Colors.white)),
          ),
        ],
      ),
    );

    if (confirmed == true && context.mounted) {
      final success = await provider.unenroll(course.courseId);
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(success
                ? 'Dropped ${course.courseName}'
                : (provider.error ?? 'Failed to drop')),
            backgroundColor: success ? AppTheme.secondary : AppTheme.error,
          ),
        );
      }
    }
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
                    fontSize: 14,
                    color: AppTheme.textPrimary,
                    fontWeight: FontWeight.w500),
                textAlign: TextAlign.center),
          ],
        ),
      ),
    );
  }
}
