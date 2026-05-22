import 'dart:async';
import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

import '../services/api_service.dart';
import '../theme/app_colors.dart';

/// Splash screen — both logos centered side-by-side above the
/// "NEO V-TRACK · UiTM SEGAMAT" wordmark. Matches the guard-mobile UI kit.
class SplashScreen extends StatefulWidget {
  const SplashScreen({super.key});

  @override
  State<SplashScreen> createState() => _SplashScreenState();
}

class _SplashScreenState extends State<SplashScreen> {
  static const bool _autoAdminLogin = false;

  @override
  void initState() {
    super.initState();
    if (_autoAdminLogin) {
      _attemptAutoAdminLogin();
    } else {
      Timer(const Duration(milliseconds: 1800), () {
        if (mounted) Navigator.pushReplacementNamed(context, '/welcome');
      });
    }
  }

  Future<void> _attemptAutoAdminLogin() async {
    try {
      final api = ApiService();
      final res = await api.login('admin@mail.com', '111111', 'admin');
      if (res['success'] == 1) {
        final user = res['user'];
        if (!mounted) return;
        Navigator.pushReplacementNamed(context, '/dashboard', arguments: {
          'userId': user['id'],
          'name': user['name'],
          'email': user['email'],
          'role': 'admin',
        });
        return;
      }
    } catch (_) {}
    Timer(const Duration(seconds: 1), () {
      if (mounted) Navigator.pushReplacementNamed(context, '/welcome');
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.brandPurpleDeep,
      body: GestureDetector(
        onTap: () {
          if (mounted) Navigator.pushReplacementNamed(context, '/welcome');
        },
        child: Container(
          decoration: const BoxDecoration(
            gradient: LinearGradient(
              begin: Alignment.topCenter,
              end: Alignment.bottomCenter,
              colors: AppColors.heroGradient,
            ),
          ),
          child: SafeArea(
            child: Stack(
              children: [
                Center(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      // Logos side-by-side
                      Row(
                        mainAxisSize: MainAxisSize.min,
                        crossAxisAlignment: CrossAxisAlignment.center,
                        children: [
                          Image.asset(
                            'assets/images/uitm.png',
                            height: 86,
                            errorBuilder: (_, __, ___) => const Icon(
                              Icons.school,
                              size: 86,
                              color: Colors.white,
                            ),
                          ),
                          Container(
                            width: 1.5,
                            height: 64,
                            color: Colors.white.withValues(alpha: 0.22),
                            margin: const EdgeInsets.symmetric(horizontal: 18),
                          ),
                          Container(
                            decoration: BoxDecoration(
                              color: Colors.white,
                              borderRadius: BorderRadius.circular(12),
                              boxShadow: [
                                BoxShadow(
                                  color: Colors.black.withValues(alpha: 0.25),
                                  blurRadius: 24,
                                  offset: const Offset(0, 12),
                                ),
                              ],
                            ),
                            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                            child: Image.asset(
                              'assets/images/kik2.png',
                              height: 70,
                              errorBuilder: (_, __, ___) => const Icon(
                                Icons.directions_car,
                                size: 70,
                                color: AppColors.brandPurple,
                              ),
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 32),
                      // Wordmark
                      RichText(
                        textAlign: TextAlign.center,
                        text: TextSpan(
                          style: GoogleFonts.manrope(
                            fontSize: 22,
                            fontWeight: FontWeight.w800,
                            color: Colors.white,
                            letterSpacing: 0.6,
                          ),
                          children: const [
                            TextSpan(text: 'NEO '),
                            TextSpan(
                              text: 'V-TRACK',
                              style: TextStyle(color: AppColors.brandYellow),
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(height: 6),
                      Text(
                        'UiTM SEGAMAT',
                        style: GoogleFonts.spaceGrotesk(
                          fontSize: 12,
                          fontWeight: FontWeight.w600,
                          color: Colors.white.withValues(alpha: 0.65),
                          letterSpacing: 3.6,
                        ),
                      ),
                      const SizedBox(height: 36),
                      const SizedBox(
                        width: 24,
                        height: 24,
                        child: CircularProgressIndicator(
                          color: AppColors.brandYellow,
                          strokeWidth: 2.5,
                        ),
                      ),
                    ],
                  ),
                ),
                Positioned(
                  bottom: 18,
                  left: 0,
                  right: 0,
                  child: Center(
                    child: Text(
                      '© 2026 NEO V-TRACK · UiTM Cawangan Johor',
                      style: GoogleFonts.jetBrainsMono(
                        color: Colors.white.withValues(alpha: 0.5),
                        fontSize: 10,
                        letterSpacing: 0.6,
                      ),
                    ),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
