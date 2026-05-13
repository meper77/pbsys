import 'package:flutter/material.dart';
import 'dart:async';
import '../services/api_service.dart';

class SplashScreen extends StatefulWidget {
  const SplashScreen({super.key});

  @override
  State<SplashScreen> createState() => _SplashScreenState();
}

class _SplashScreenState extends State<SplashScreen>
    with SingleTickerProviderStateMixin {
  late AnimationController _controller;
  late Animation<double> _animation;

  // Enable only for local dev testing. Automatically attempts admin login and
  // navigates to dashboard if successful. Set to false for normal behavior.
  static const bool _autoAdminLogin = false;

  @override
  void initState() {
    super.initState();

    // ===== Animation for logo =====
    _controller = AnimationController(
      vsync: this,
      duration: const Duration(seconds: 2),
    );

    _animation = CurvedAnimation(
      parent: _controller,
      curve: Curves.easeOutBack,
    );

    _controller.forward();

    if (_autoAdminLogin) {
      // Try automatic admin login for developer smoke tests, then navigate.
      _attemptAutoAdminLogin();
    } else {
      // ===== Move to WelcomeScreen after 3 seconds =====
      Timer(const Duration(seconds: 3), () {
        Navigator.pushReplacementNamed(context, '/welcome');
      });
    }
  }

  Future<void> _attemptAutoAdminLogin() async {
    try {
      final api = ApiService();
      final res = await api.login('admin@mail.com', '111111', 'admin');
      if (res['success'] == 1) {
        final user = res['user'];
        Navigator.pushReplacementNamed(context, '/dashboard', arguments: {
          'userId': user['id'],
          'name': user['name'],
          'email': user['email'],
          'role': 'admin',
        });
        return;
      }
    } catch (e) {
      // ignore
    }

    // fallback to normal welcome screen
    Timer(const Duration(seconds: 1), () {
      Navigator.pushReplacementNamed(context, '/welcome');
    });
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      // Use full primary color as background
      backgroundColor: const Color(0xFF4B2E83),
      body: Center(
        child: ScaleTransition(
          scale: _animation,
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Image.asset(
                'assets/images/uitm.png', // UiTM logo
                width: 120,
                height: 120,
              ),
              const SizedBox(height: 20),
              const Text(
                'NEO V-TRACK',
                style: TextStyle(
                  fontSize: 28,
                  fontWeight: FontWeight.bold,
                  color: Colors.white,
                  letterSpacing: 1.2,
                ),
              ),
              const SizedBox(height: 8),
              const Text(
                'Vehicle Management & Monitoring System',
                style: TextStyle(
                  fontSize: 14,
                  color: Colors.white70,
                ),
                textAlign: TextAlign.center,
              ),
            ],
          ),
        ),
      ),
    );
  }
}