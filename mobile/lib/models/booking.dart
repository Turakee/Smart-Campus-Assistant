class Booking {
  final int bookingId;
  final int resourceId;
  final String resourceName;
  final String bookingDate;
  final String startTime;
  final String endTime;
  final String? purpose;
  final String status;

  Booking({
    required this.bookingId,
    required this.resourceId,
    required this.resourceName,
    required this.bookingDate,
    required this.startTime,
    required this.endTime,
    this.purpose,
    required this.status,
  });

  factory Booking.fromJson(Map<String, dynamic> json) {
    return Booking(
      bookingId: json['booking_id'],
      resourceId: json['resource_id'],
      resourceName: json['resource_name'],
      bookingDate: json['booking_date'],
      startTime: json['start_time'],
      endTime: json['end_time'],
      purpose: json['purpose'],
      status: json['status'],
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'booking_id': bookingId,
      'resource_id': resourceId,
      'resource_name': resourceName,
      'booking_date': bookingDate,
      'start_time': startTime,
      'end_time': endTime,
      'purpose': purpose,
      'status': status,
    };
  }

  bool get isPending => status == 'pending';
  bool get isApproved => status == 'approved';
}
