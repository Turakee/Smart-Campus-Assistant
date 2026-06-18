import 'package:flutter/material.dart';

class NotificationBadge extends StatelessWidget {
  final Widget icon;
  final int count;

  const NotificationBadge({
    super.key,
    required this.icon,
    required this.count,
  });

  @override
  Widget build(BuildContext context) {
    return Badge(
      isLabelVisible: count > 0,
      label: Text(
        count > 99 ? '99+' : count.toString(),
        style: const TextStyle(fontSize: 10, fontWeight: FontWeight.bold),
      ),
      child: icon,
    );
  }
}
