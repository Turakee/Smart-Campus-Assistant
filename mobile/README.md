# Smart Campus Mobile App

This is the Flutter mobile application for the AI-Powered Smart Campus Assistant.

## Features

- Student authentication and profile management
- Class schedule viewing and AI optimization
- Attendance tracking and risk prediction
- Campus resource booking
- Push notifications
- AI chatbot integration
- Voice-enabled interaction

## Setup Instructions

1. Ensure Flutter is installed on your system
2. Navigate to the mobile directory
3. Run `flutter pub get` to install dependencies
4. Configure the API base URL in `lib/services/api_service.dart`
5. Run `flutter run` to start the app

## Project Structure

```
lib/
├── main.dart              # App entry point
├── models/                # Data models
├── providers/             # State management
├── screens/               # UI screens
├── services/              # API and utility services
└── widgets/               # Reusable UI components
```

## Dependencies

- http: For API communication
- shared_preferences: Local data storage
- provider: State management
- flutter_local_notifications: Push notifications
- speech_to_text: Voice input
- flutter_tts: Text-to-speech output
- intl: Date/time formatting

## API Integration

The app communicates with the PHP backend API. Update the `baseUrl` in `api_service.dart` to match your server configuration.

## Building for Production

- Android: `flutter build apk`
- iOS: `flutter build ios` (requires macOS)

## Permissions

The app requires the following permissions:
- Internet access
- Notification access
- Microphone access (for voice features)

## Notes

This is a basic implementation. Additional features like offline mode, advanced caching, and biometric authentication can be added in future updates.