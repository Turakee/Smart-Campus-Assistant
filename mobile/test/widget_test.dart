import 'package:flutter_test/flutter_test.dart';
import 'package:provider/provider.dart';
import 'package:smart_campus_mobile/main.dart';
import 'package:smart_campus_mobile/providers/auth_provider.dart';
import 'package:smart_campus_mobile/providers/schedule_provider.dart';
import 'package:smart_campus_mobile/providers/attendance_provider.dart';
import 'package:smart_campus_mobile/providers/booking_provider.dart';
import 'package:smart_campus_mobile/providers/notification_provider.dart';

void main() {
  testWidgets('App smoke test', (WidgetTester tester) async {
    // Build our app and trigger a frame.
    // Wrap with necessary providers as the app depends on them.
    await tester.pumpWidget(
      MultiProvider(
        providers: [
          ChangeNotifierProvider(create: (_) => AuthProvider()),
          ChangeNotifierProvider(create: (_) => ScheduleProvider()),
          ChangeNotifierProvider(create: (_) => AttendanceProvider()),
          ChangeNotifierProvider(create: (_) => BookingProvider()),
          ChangeNotifierProvider(create: (_) => NotificationProvider()),
        ],
        child: const SmartCampusApp(),
      ),
    );

    // Verify that the app starts.
    expect(find.byType(SmartCampusApp), findsOneWidget);
  });
}
