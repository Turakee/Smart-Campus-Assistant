import 'package:flutter/material.dart';
import '../models/booking.dart';
import '../services/api_service.dart';

class BookingProvider with ChangeNotifier {
  List<Booking> _bookings = [];
  bool _isLoading = false;
  String? _error;

  List<Booking> get bookings => _bookings;
  bool get isLoading => _isLoading;
  String? get error => _error;

  Future<void> loadBookings() async {
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      final response = await ApiService.getStudentBookings();

      if (response['success']) {
        _bookings = (response['data'] as List)
            .map((item) => Booking.fromJson(item))
            .toList();
      } else {
        _error = response['message'];
      }
    } catch (e) {
      _error = 'Failed to load bookings';
    }

    _isLoading = false;
    notifyListeners();
  }

  Future<bool> submitBooking(int resourceId, String bookingDate,
      String startTime, String endTime, String purpose) async {
    _isLoading = true;
    notifyListeners();

    try {
      final response = await ApiService.submitBooking(
          resourceId, bookingDate, startTime, endTime, purpose);

      if (response['success']) {
        // Reload bookings
        await loadBookings();
        _isLoading = false;
        notifyListeners();
        return true;
      } else {
        _error = response['message'];
        _isLoading = false;
        notifyListeners();
        return false;
      }
    } catch (e) {
      _error = 'Failed to submit booking';
      _isLoading = false;
      notifyListeners();
      return false;
    }
  }
}
