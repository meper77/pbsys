import 'package:flutter/material.dart';

class WelcomeScreen extends StatelessWidget {
  const WelcomeScreen({super.key});

  // ==== UiTM Official Colors ====
  static const primaryColor = Color(0xFF4B2E83); // Ungu UiTM
  static const secondaryColor = Color(0xFFF3C143); // Kuning Emas
  static const neutralWhite = Color(0xFFFFFFFF);
  static const neutralGrey = Color(0xFFF5F5F5);
  static const textPrimary = Color(0xFF000000);
  static const textSecondary = Color(0xFF333333);

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Container(
        width: double.infinity,
        height: double.infinity,
        decoration: const BoxDecoration(
          gradient: LinearGradient(
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
            colors: [
              primaryColor,
              Color(0xFF6A1B9A),
              Color(0xFF7B1FA2),
            ],
          ),
        ),
        child: Stack(
          children: [
            // ===== Glow frames (restored but UiTM colors) =====
            _glowFrame(top: -120, left: -80, rotation: -0.5, color: secondaryColor.withOpacity(0.3)),
            _glowFrame(bottom: -140, right: -100, rotation: 0.5, color: secondaryColor.withOpacity(0.3)),

            SafeArea(
              child: Center(
                child: Padding(
                  padding: const EdgeInsets.all(24),
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      const Spacer(),

                      // ==== Logos row ====
                      Row(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Image.asset('assets/images/uitm.png', height: 70),
                          const SizedBox(width: 20),
                          Image.asset('assets/images/kik2.png', height: 70),
                        ],
                      ),

                      const SizedBox(height: 28),

                      // ==== Welcome Text ====
                      Text(
                        'Welcome to NEO V-TRACK',
                        style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                              color: neutralWhite,
                              fontWeight: FontWeight.bold,
                              shadows: [
                                Shadow(
                                  blurRadius: 5,
                                  color: secondaryColor.withOpacity(0.6),
                                  offset: const Offset(2, 2),
                                ),
                              ],
                            ),
                        textAlign: TextAlign.center,
                      ),

                      const SizedBox(height: 10),

                      Text(
                        'Manage & monitor vehicles easily',
                        style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                              color: Colors.white70,
                            ),
                        textAlign: TextAlign.center,
                      ),

                      const SizedBox(height: 40),

                      // ==== Enter to Login Button ====
                      SizedBox(
                        width: double.infinity,
                        height: 52,
                        child: ElevatedButton(
                          onPressed: () {
                            Navigator.pushNamed(context, '/login_user');
                          },
                          style: ElevatedButton.styleFrom(
                            backgroundColor: secondaryColor,
                            foregroundColor: primaryColor,
                            shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(12),
                            ),
                            elevation: 6,
                          ),
                          child: const Text(
                            'ENTER TO LOGIN',
                            style: TextStyle(
                              fontSize: 18,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                        ),
                      ),

                      const Spacer(),

                      // ==== Footer ====
                      Text(
                        '© 2026 NEO V-TRACK. All rights reserved.',
                        style: Theme.of(context).textTheme.bodySmall?.copyWith(
                              color: Colors.white70,
                            ),
                      ),
                    ],
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  // ===== Glow frame decoration ====
  Widget _glowFrame({
    double? top,
    double? bottom,
    double? left,
    double? right,
    required double rotation,
    required Color color,
  }) {
    return Positioned(
      top: top,
      bottom: bottom,
      left: left,
      right: right,
      child: Transform.rotate(
        angle: rotation,
        child: Container(
          width: 320,
          height: 320,
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(40),
            border: Border.all(
              color: color.withOpacity(0.2),
              width: 2,
            ),
            boxShadow: [
              BoxShadow(
                color: color.withOpacity(0.5),
                blurRadius: 30,
              ),
            ],
          ),
        ),
      ),
    );
  }
}

