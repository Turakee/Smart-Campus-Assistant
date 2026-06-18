class User {
  final int userId;
  final String username;
  final String email;
  final String role;
  final String? name;
  final String? department;
  final int? level;

  User({
    required this.userId,
    required this.username,
    required this.email,
    required this.role,
    this.name,
    this.department,
    this.level,
  });

  factory User.fromJson(Map<String, dynamic> json) {
    return User(
      userId: json['user_id'],
      username: json['username'],
      email: json['email'],
      role: json['role'],
      name: json['name'],
      department: json['department'],
      level: json['level'],
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'user_id': userId,
      'username': username,
      'email': email,
      'role': role,
      'name': name,
      'department': department,
      'level': level,
    };
  }
}
