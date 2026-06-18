import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';

class ApiService {
  static const String baseUrl = 'http://192.168.18.1:8080/smart_campus/api';
  static const Duration _timeout = Duration(seconds: 30);

  static Future<String?> _getToken() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString('auth_token');
  }

  static Future<Map<String, String>> _getHeaders() async {
    final token = await _getToken();
    return {
      'Content-Type': 'application/json',
      if (token != null) 'Authorization': 'Bearer $token',
    };
  }

  static Future<Map<String, dynamic>> _request(
    Future<http.Response> Function() fn,
  ) async {
    final response = await fn().timeout(_timeout);
    final body = jsonDecode(response.body) as Map<String, dynamic>;
    if (response.statusCode < 200 || response.statusCode >= 300) {
      if (body['success'] == false && body['message'] != null) {
        return body;
      }
      throw HttpException(response.statusCode, response.body);
    }
    return body;
  }

  static Future<Map<String, dynamic>> login(
      String username, String password) async {
    return _request(() => http.post(
          Uri.parse('$baseUrl/auth/login.php'),
          headers: {'Content-Type': 'application/json'},
          body: jsonEncode({'username': username, 'password': password}),
        ));
  }

  static Future<Map<String, dynamic>> register(String username, String email,
      String password, String fullName, String department, int level) async {
    return _request(() => http.post(
          Uri.parse('$baseUrl/auth/register.php'),
          headers: {'Content-Type': 'application/json'},
          body: jsonEncode({
            'username': username,
            'email': email,
            'password': password,
            'full_name': fullName,
            'department': department,
            'level': level,
            'role': 'student',
          }),
        ));
  }

  static Future<Map<String, dynamic>> getStudentSchedule() async {
    final headers = await _getHeaders();
    return _request(() => http.get(
          Uri.parse('$baseUrl/schedule/get.php'),
          headers: headers,
        ));
  }

  static Future<Map<String, dynamic>> getStudentAttendance() async {
    final headers = await _getHeaders();
    return _request(() => http.get(
          Uri.parse('$baseUrl/attendance/get.php'),
          headers: headers,
        ));
  }

  static Future<Map<String, dynamic>> getStudentBookings() async {
    final headers = await _getHeaders();
    return _request(() => http.get(
          Uri.parse('$baseUrl/booking/history.php'),
          headers: headers,
        ));
  }

  static Future<Map<String, dynamic>> submitBooking(
      int resourceId,
      String bookingDate,
      String startTime,
      String endTime,
      String purpose) async {
    final headers = await _getHeaders();
    return _request(() => http.post(
          Uri.parse('$baseUrl/booking/submit.php'),
          headers: headers,
          body: jsonEncode({
            'resource_id': resourceId,
            'booking_date': bookingDate,
            'start_time': startTime,
            'end_time': endTime,
            'purpose': purpose,
          }),
        ));
  }

  static Future<Map<String, dynamic>> getStudentNotifications() async {
    final headers = await _getHeaders();
    return _request(() => http.get(
          Uri.parse('$baseUrl/notifications/get.php'),
          headers: headers,
        ));
  }

  static Future<Map<String, dynamic>> markNotificationAsRead(
      int notificationId) async {
    final headers = await _getHeaders();
    return _request(() => http.post(
          Uri.parse('$baseUrl/notifications/mark-read.php'),
          headers: headers,
          body: jsonEncode({'notification_id': notificationId}),
        ));
  }

  static Future<Map<String, dynamic>> optimizeSchedule() async {
    final headers = await _getHeaders();
    return _request(() => http.post(
          Uri.parse('$baseUrl/ai/optimize-schedule.php'),
          headers: headers,
        ));
  }

  static Future<Map<String, dynamic>> predictAttendanceRisk() async {
    final headers = await _getHeaders();
    return _request(() => http.post(
          Uri.parse('$baseUrl/ai/predict-attendance.php'),
          headers: headers,
        ));
  }

  static Future<Map<String, dynamic>> chatWithAI(String query) async {
    final headers = await _getHeaders();
    return _request(() => http.post(
          Uri.parse('$baseUrl/ai/chatbot.php'),
          headers: headers,
          body: jsonEncode({'query': query}),
        ));
  }

  static Future<Map<String, dynamic>> updateProfile(
      String fullName, String department, int level) async {
    final headers = await _getHeaders();
    return _request(() => http.put(
          Uri.parse('$baseUrl/student/profile.php'),
          headers: headers,
          body: jsonEncode({
            'full_name': fullName,
            'department': department,
            'level': level
          }),
        ));
  }

  static Future<Map<String, dynamic>> changePassword(
      String currentPassword, String newPassword) async {
    final headers = await _getHeaders();
    return _request(() => http.post(
          Uri.parse('$baseUrl/auth/change-password.php'),
          headers: headers,
          body: jsonEncode({
            'current_password': currentPassword,
            'new_password': newPassword
          }),
        ));
  }

  static Future<Map<String, dynamic>> getResources() async {
    final headers = await _getHeaders();
    return _request(() => http.get(
          Uri.parse('$baseUrl/resource/list.php'),
          headers: headers,
        ));
  }

  static Future<Map<String, dynamic>> predictPerformance() async {
    final headers = await _getHeaders();
    return _request(() => http.get(
          Uri.parse('$baseUrl/ai/predict-performance.php'),
          headers: headers,
        ));
  }

  static Future<Map<String, dynamic>> getDashboardStats() async {
    final headers = await _getHeaders();
    return _request(() => http.get(
          Uri.parse('$baseUrl/stats/get.php'),
          headers: headers,
        ));
  }

  static Future<Map<String, dynamic>> getMyCourses() async {
    final headers = await _getHeaders();
    return _request(() => http.get(
          Uri.parse('$baseUrl/course/my-courses.php'),
          headers: headers,
        ));
  }

  static Future<Map<String, dynamic>> getAvailableCourses() async {
    final headers = await _getHeaders();
    return _request(() => http.get(
          Uri.parse('$baseUrl/course/list.php'),
          headers: headers,
        ));
  }

  static Future<Map<String, dynamic>> enrollCourse(int courseId) async {
    final headers = await _getHeaders();
    return _request(() => http.post(
          Uri.parse('$baseUrl/course/enroll.php'),
          headers: headers,
          body: jsonEncode({'course_id': courseId}),
        ));
  }

  static Future<Map<String, dynamic>> unenrollCourse(int courseId) async {
    final headers = await _getHeaders();
    return _request(() => http.post(
          Uri.parse('$baseUrl/course/unenroll.php'),
          headers: headers,
          body: jsonEncode({'course_id': courseId}),
        ));
  }

  static Future<Map<String, dynamic>> markAllNotificationsRead() async {
    final headers = await _getHeaders();
    return _request(() => http.post(
          Uri.parse('$baseUrl/notifications/manage.php'),
          headers: headers,
        ));
  }

  static Future<Map<String, dynamic>> clearAllNotifications() async {
    final headers = await _getHeaders();
    return _request(() => http.delete(
          Uri.parse('$baseUrl/notifications/manage.php'),
          headers: headers,
        ));
  }

  static Future<Map<String, dynamic>> getStudentProfile() async {
    final headers = await _getHeaders();
    return _request(() => http.get(
          Uri.parse('$baseUrl/student/profile.php'),
          headers: headers,
        ));
  }

  static Future<Map<String, dynamic>> resetPassword(
      String email, String newPassword) async {
    return _request(() => http.post(
          Uri.parse('$baseUrl/auth/reset-password.php'),
          headers: {'Content-Type': 'application/json'},
          body: jsonEncode({'email': email, 'new_password': newPassword}),
        ));
  }

  static Future<Map<String, dynamic>> logout() async {
    final headers = await _getHeaders();
    return _request(() => http.post(
          Uri.parse('$baseUrl/auth/logout.php'),
          headers: headers,
        ));
  }
}

class HttpException implements Exception {
  final int statusCode;
  final String body;
  HttpException(this.statusCode, this.body);
  @override
  String toString() => 'HTTP $statusCode: $body';
}
