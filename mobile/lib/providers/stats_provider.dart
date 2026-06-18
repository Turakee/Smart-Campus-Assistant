import 'package:flutter/material.dart';
import '../services/api_service.dart';

class StatsProvider with ChangeNotifier {
  int _courses = 0;
  double _attendanceRate = 0;
  int _pendingBookings = 0;
  int _approvedBookings = 0;
  bool _isLoading = false;
  String? _error;

  int get courses => _courses;
  double get attendanceRate => _attendanceRate;
  int get pendingBookings => _pendingBookings;
  int get approvedBookings => _approvedBookings;
  bool get isLoading => _isLoading;
  String? get error => _error;

  Future<void> loadStats() async {
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      final response = await ApiService.getDashboardStats();

      if (response['success']) {
        final data = response['data'];
        _courses = data['courses'] ?? 0;
        _attendanceRate = (data['attendance'] ?? 0).toDouble();
        _pendingBookings = data['bookings_pending'] ?? 0;
        _approvedBookings = data['bookings_approved'] ?? 0;
      } else {
        _error = response['message'];
      }
    } catch (e) {
      _error = 'Failed to load stats';
    }

    _isLoading = false;
    notifyListeners();
  }
}
