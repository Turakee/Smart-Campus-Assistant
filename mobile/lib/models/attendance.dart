class Attendance {
  final int attendanceId;
  final int courseId;
  final String courseName;
  final String date;
  final String status;

  Attendance({
    required this.attendanceId,
    required this.courseId,
    required this.courseName,
    required this.date,
    required this.status,
  });

  factory Attendance.fromJson(Map<String, dynamic> json) {
    return Attendance(
      attendanceId: json['attendance_id'],
      courseId: json['course_id'],
      courseName: json['course_name'],
      date: json['date'],
      status: json['status'],
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'attendance_id': attendanceId,
      'course_id': courseId,
      'course_name': courseName,
      'date': date,
      'status': status,
    };
  }

  bool get isPresent => status == 'present';
  bool get isAbsent => status == 'absent';
  bool get isLate => status == 'late';
  bool get isExcused => status == 'excused';
}
