import 'package:flutter/material.dart';
import '../models/notification.dart';
import '../services/api_service.dart';

class NotificationProvider with ChangeNotifier {
  List<NotificationModel> _notifications = [];
  bool _isLoading = false;
  String? _error;
  int _unreadCount = 0;

  List<NotificationModel> get notifications => _notifications;
  bool get isLoading => _isLoading;
  String? get error => _error;
  int get unreadCount => _unreadCount;

  Future<void> loadNotifications() async {
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      final response = await ApiService.getStudentNotifications();

      if (response['success']) {
        final data = response['data'];
        if (data is Map<String, dynamic> && data.containsKey('notifications')) {
          final notifications = data['notifications'];
          _notifications = (notifications as List)
              .map((item) => NotificationModel.fromJson(item))
              .toList();
          _unreadCount = data['unread_count'] as int? ??
              _notifications.where((n) => !n.isRead).length;
        } else if (data is List) {
          _notifications = data
              .map((item) =>
                  NotificationModel.fromJson(item as Map<String, dynamic>))
              .toList();
          _unreadCount = _notifications.where((n) => !n.isRead).length;
        } else {
          _error = 'Unexpected notification response format';
        }
      } else {
        _error = response['message'];
      }
    } catch (e) {
      _error = 'Failed to load notifications';
    }

    _isLoading = false;
    notifyListeners();
  }

  Future<bool> markAsRead(int notificationId) async {
    try {
      final response = await ApiService.markNotificationAsRead(notificationId);

      if (response['success']) {
        final index = _notifications
            .indexWhere((n) => n.notificationId == notificationId);
        if (index != -1) {
          _notifications[index] = NotificationModel(
            notificationId: _notifications[index].notificationId,
            message: _notifications[index].message,
            type: _notifications[index].type,
            isRead: true,
            createdAt: _notifications[index].createdAt,
          );
          _unreadCount = _notifications.where((n) => !n.isRead).length;
          notifyListeners();
        }
        return true;
      } else {
        _error = response['message'];
        notifyListeners();
        return false;
      }
    } catch (e) {
      _error = 'Failed to mark notification as read';
      notifyListeners();
      return false;
    }
  }

  Future<bool> markAllAsRead() async {
    try {
      final response = await ApiService.markAllNotificationsRead();

      if (response['success']) {
        _notifications = _notifications
            .map((n) => NotificationModel(
                  notificationId: n.notificationId,
                  message: n.message,
                  type: n.type,
                  isRead: true,
                  createdAt: n.createdAt,
                ))
            .toList();
        _unreadCount = 0;
        notifyListeners();
        return true;
      }
      return false;
    } catch (e) {
      return false;
    }
  }

  Future<bool> clearAll() async {
    try {
      final response = await ApiService.clearAllNotifications();

      if (response['success']) {
        _notifications.clear();
        _unreadCount = 0;
        notifyListeners();
        return true;
      }
      return false;
    } catch (e) {
      return false;
    }
  }
}
