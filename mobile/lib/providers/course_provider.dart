import 'package:flutter/material.dart';
import '../models/course.dart';
import '../services/api_service.dart';

class CourseProvider with ChangeNotifier {
  List<Course> _myCourses = [];
  List<Course> _availableCourses = [];
  bool _isLoading = false;
  String? _error;

  List<Course> get myCourses => _myCourses;
  List<Course> get availableCourses => _availableCourses;
  bool get isLoading => _isLoading;
  String? get error => _error;

  Future<void> loadMyCourses() async {
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      final response = await ApiService.getMyCourses();

      if (response['success']) {
        _myCourses = (response['data'] as List)
            .map((item) => Course.fromJson(item))
            .toList();
      } else {
        _error = response['message'];
      }
    } catch (e) {
      _error = 'Failed to load courses';
    }

    _isLoading = false;
    notifyListeners();
  }

  Future<void> loadAvailableCourses() async {
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      final response = await ApiService.getAvailableCourses();

      if (response['success']) {
        _availableCourses = (response['data'] as List)
            .map((item) => Course.fromJson(item))
            .toList();
      } else {
        _error = response['message'];
      }
    } catch (e) {
      _error = 'Failed to load available courses';
    }

    _isLoading = false;
    notifyListeners();
  }

  Future<bool> enroll(int courseId) async {
    try {
      final response = await ApiService.enrollCourse(courseId);

      if (response['success']) {
        await loadMyCourses();
        await loadAvailableCourses();
        return true;
      } else {
        _error = response['message'];
        notifyListeners();
        return false;
      }
    } catch (e) {
      _error = 'Failed to enroll in course';
      notifyListeners();
      return false;
    }
  }

  Future<bool> unenroll(int courseId) async {
    try {
      final response = await ApiService.unenrollCourse(courseId);

      if (response['success']) {
        await loadMyCourses();
        await loadAvailableCourses();
        return true;
      } else {
        _error = response['message'];
        notifyListeners();
        return false;
      }
    } catch (e) {
      _error = 'Failed to unenroll from course';
      notifyListeners();
      return false;
    }
  }
}
