import 'package:flutter/material.dart';
import '../models/attendance.dart';
import '../services/api_service.dart';

class AttendanceProvider with ChangeNotifier {
  List<Attendance> _attendanceRecords = [];
  bool _isLoading = false;
  String? _error;
  double _overallPercentage = 0.0;

  List<Attendance> get attendanceRecords => _attendanceRecords;
  bool get isLoading => _isLoading;
  String? get error => _error;
  double get overallPercentage => _overallPercentage;

  Future<void> loadAttendance() async {
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      final response = await ApiService.getStudentAttendance();

      if (response['success']) {
        _attendanceRecords = (response['data']['records'] as List)
            .map((item) => Attendance.fromJson(item))
            .toList();
        _overallPercentage =
            response['data']['stats']['percentage']?.toDouble() ?? 0.0;
      } else {
        _error = response['message'];
      }
    } catch (e) {
      _error = 'Failed to load attendance';
    }

    _isLoading = false;
    notifyListeners();
  }

  Future<Map<String, dynamic>?> predictAttendanceRisk() async {
    try {
      final response = await ApiService.predictAttendanceRisk();

      if (response['success']) {
        return response['data'];
      } else {
        _error = response['message'];
        notifyListeners();
        return null;
      }
    } catch (e) {
      _error = 'Failed to predict attendance risk';
      notifyListeners();
      return null;
    }
  }

  List<Attendance> getAttendanceForCourse(int courseId) {
    return _attendanceRecords
        .where((record) => record.courseId == courseId)
        .toList();
  }

  double getAttendancePercentageForCourse(int courseId) {
    final courseRecords = getAttendanceForCourse(courseId);
    if (courseRecords.isEmpty) return 0.0;

    final presentCount = courseRecords.where((r) => r.isPresent).length;
    return (presentCount / courseRecords.length) * 100;
  }
}
