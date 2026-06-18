class Schedule {
  final int scheduleId;
  final int courseId;
  final String courseCode;
  final String courseName;
  final String dayOfWeek;
  final String startTime;
  final String endTime;
  final String? roomNumber;

  Schedule({
    required this.scheduleId,
    required this.courseId,
    required this.courseCode,
    required this.courseName,
    required this.dayOfWeek,
    required this.startTime,
    required this.endTime,
    this.roomNumber,
  });

  factory Schedule.fromJson(Map<String, dynamic> json) {
    return Schedule(
      scheduleId: json['schedule_id'],
      courseId: json['course_id'],
      courseCode: json['course_code'],
      courseName: json['course_name'],
      dayOfWeek: json['day_of_week'],
      startTime: json['start_time'],
      endTime: json['end_time'],
      roomNumber: json['room_number'],
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'schedule_id': scheduleId,
      'course_id': courseId,
      'course_code': courseCode,
      'course_name': courseName,
      'day_of_week': dayOfWeek,
      'start_time': startTime,
      'end_time': endTime,
      'room_number': roomNumber,
    };
  }
}
