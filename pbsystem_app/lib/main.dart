import 'package:flutter/material.dart';

import 'services/api_service.dart';
import 'screens/splash_screen.dart'; // <-- new splash screen
import 'screens/welcome_screen.dart';
import 'screens/login_screen.dart';
import 'screens/register_screen.dart';
import 'screens/forgot_password_screen.dart';
import 'screens/dashboard_screen.dart';
import 'screens/search_car_screen.dart';
import 'screens/vehicle_category_screen.dart';
import 'screens/profile_screen.dart';
import 'screens/about_system_screen.dart'; // <-- new screen
import 'screens/report_vehicle_screen.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await ApiService.configure();
  runApp(const PBSystemApp());
}

class PBSystemApp extends StatelessWidget {
  const PBSystemApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'NEO.V-TRACK',
      debugShowCheckedModeBanner: false,
      initialRoute: '/splash', // <-- splash as first screen

      theme: ThemeData(
        useMaterial3: true,
        colorSchemeSeed: Colors.deepPurple,
        scaffoldBackgroundColor: const Color(0xFFF6F7FB),
        appBarTheme: const AppBarTheme(
          centerTitle: true,
          elevation: 0,
          backgroundColor: Colors.white,
          foregroundColor: Colors.black,
        ),
        inputDecorationTheme: InputDecorationTheme(
          filled: true,
          fillColor: Colors.white,
          border: OutlineInputBorder(
            borderRadius: BorderRadius.circular(12),
            borderSide: BorderSide.none,
          ),
        ),
        elevatedButtonTheme: ElevatedButtonThemeData(
          style: ElevatedButton.styleFrom(
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(12),
            ),
          ),
        ),
      ),

      onGenerateRoute: (settings) {
        switch (settings.name) {
          case '/splash': // <-- splash screen
            return MaterialPageRoute(
              builder: (_) => const SplashScreen(),
            );

          case '/welcome':
            return MaterialPageRoute(
              builder: (_) => const WelcomeScreen(),
            );

          case '/login_user':
            final args = settings.arguments as Map<String, dynamic>?;
            return MaterialPageRoute(
              builder: (_) => LoginScreen(
                role: args?['role'] ?? 'user',
              ),
            );

          case '/register':
            return MaterialPageRoute(
              builder: (_) => const RegisterScreen(),
            );

          case '/forgot':
            return MaterialPageRoute(
              builder: (_) => const ForgotPasswordScreen(),
            );

          case '/dashboard':
            final args = settings.arguments as Map<String, dynamic>?;
            return MaterialPageRoute(
              builder: (_) => DashboardScreen(
                userId: args!['userId'],
                name: args['name'],
                email: args['email'],
                role: args['role'] ?? 'user',
              ),
            );

          case '/search_car':
            return MaterialPageRoute(
              builder: (_) => const SearchCarScreen(),
            );

          case '/vehicle_category':
            final args = settings.arguments as Map<String, dynamic>?;
            return MaterialPageRoute(
              builder: (_) => VehicleCategoryScreen(
                title: (args?['title'] ?? 'Vehicles') as String,
                status: (args?['status'] ?? '') as String,
                showAll: (args?['showAll'] ?? false) as bool,
              ),
            );

          case '/profile':
            final args = settings.arguments as Map<String, dynamic>?;
            return MaterialPageRoute(
              builder: (_) => ProfileScreen(
                userId: args!['userId'],
                name: args['name'],
                email: args['email'],
              ),
            );

          case '/about_system': // <-- added route
            return MaterialPageRoute(
              builder: (_) => const AboutSystemScreen(),
            );

          case '/report_vehicle':
            final args = settings.arguments as Map<String, dynamic>?;
            return MaterialPageRoute(
              builder: (_) => ReportVehicleScreen(
                vehicle: args?['vehicle'] as Map<String, dynamic>?,
                reporterName: (args?['reporterName'] ?? '') as String,
                reporterEmail: (args?['reporterEmail'] ?? '') as String,
                reporterRole: (args?['reporterRole'] ?? 'user') as String,
                reporterId: (args?['reporterId'] ?? 0) as int,
              ),
            );

          default:
            return MaterialPageRoute(
              builder: (_) => const Scaffold(
                body: Center(child: Text('404 - Page not found')),
              ),
            );
        }
      },
    );
  }
}
