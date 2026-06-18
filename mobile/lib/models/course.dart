class Course {
  final int courseId;
  final String courseName;
  final String courseCode;
  final int? creditHours;
  final String? lecturerName;

  Course({
    required this.courseId,
    required this.courseName,
    required this.courseCode,
    this.creditHours,
    this.lecturerName,
  });

  factory Course.fromJson(Map<String, dynamic> json) {
    return Course(
      courseId: json['course_id'],
      courseName: json['course_name'],
      courseCode: json['course_code'],
      creditHours: json['credit_hours'],
      lecturerName: json['lecturer_name'],
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'course_id': courseId,
      'course_name': courseName,
      'course_code': courseCode,
      'credit_hours': creditHours,
      'lecturer_name': lecturerName,
    };
  }
}
