import 'package:flutter/material.dart';
import '../models/schedule.dart';
import '../services/api_service.dart';

class ScheduleProvider with ChangeNotifier {
  List<Schedule> _schedules = [];
  bool _isLoading = false;
  bool _isOptimizing = false;
  String? _error;
  Map<String, dynamic>? _optimizeResult;

  List<Schedule> get schedules => _schedules;
  bool get isLoading => _isLoading;
  bool get isOptimizing => _isOptimizing;
  String? get error => _error;
  Map<String, dynamic>? get optimizeResult => _optimizeResult;

  Future<void> loadSchedules() async {
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      final response = await ApiService.getStudentSchedule();

      if (response['success']) {
        _schedules = (response['data'] as List)
            .map((item) => Schedule.fromJson(item))
            .toList();
      } else {
        _error = response['message'];
      }
    } catch (e) {
      _error = 'Failed to load schedules';
    }

    _isLoading = false;
    notifyListeners();
  }

  Future<Map<String, dynamic>?> optimizeSchedule() async {
    if (_isOptimizing) return null;

    _isOptimizing = true;
    _error = null;
    _optimizeResult = null;
    notifyListeners();

    try {
      final response = await ApiService.optimizeSchedule();
      final data = response['data'] as Map<String, dynamic>?;

      if (response['success'] && data != null) {
        _optimizeResult = data;
        await loadSchedules();
        _isOptimizing = false;
        notifyListeners();
        return data;
      } else {
        _error = response['message'] ?? 'Optimization failed';
        _isOptimizing = false;
        notifyListeners();
        return null;
      }
    } catch (e) {
      _error = 'Failed to optimize schedule';
      _isOptimizing = false;
      notifyListeners();
      return null;
    }
  }

  void clearOptimizeResult() {
    _optimizeResult = null;
    notifyListeners();
  }

  List<Schedule> getSchedulesForDay(String day) {
    return _schedules.where((schedule) => schedule.dayOfWeek == day).toList();
  }
}
