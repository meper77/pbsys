import 'package:flutter/material.dart';
import 'package:font_awesome_flutter/font_awesome_flutter.dart';
import 'package:google_fonts/google_fonts.dart';

import '../theme/app_colors.dart';

/// Welcome / role-pick screen, NEO V-TRACK branded. Centered logo lockup,
/// wordmark, and two primary actions: user login + admin login.
class WelcomeScreen extends StatelessWidget {
  const WelcomeScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.brandPurpleDeep,
      body: Container(
        decoration: const BoxDecoration(
          gradient: LinearGradient(
            begin: Alignment.topCenter,
            end: Alignment.bottomCenter,
            colors: AppColors.heroGradient,
          ),
        ),
        child: SafeArea(
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: 24),
            child: Column(
              children: [
                const Spacer(),
                Row(
                  mainAxisSize: MainAxisSize.min,
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    Image.asset('assets/images/uitm.png',
                        height: 64,
                        errorBuilder: (_, __, ___) => const SizedBox.shrink()),
                    Container(
                      width: 1.5,
                      height: 48,
                      color: Colors.white.withValues(alpha: 0.22),
                      margin: const EdgeInsets.symmetric(horizontal: 16),
                    ),
                    Container(
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(10),
                      ),
                      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                      child: Image.asset('assets/images/kik2.png',
                          height: 56,
                          errorBuilder: (_, __, ___) =>
                              const SizedBox.shrink()),
                    ),
                  ],
                ),
                const SizedBox(height: 24),
                RichText(
                  textAlign: TextAlign.center,
                  text: TextSpan(
                    style: GoogleFonts.manrope(
                      fontSize: 26,
                      fontWeight: FontWeight.w800,
                      color: Colors.white,
                      letterSpacing: 0.4,
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
                const SizedBox(height: 12),
                Text(
                  'Sistem Pengurusan Maklumat Kenderaan',
                  textAlign: TextAlign.center,
                  style: GoogleFonts.spaceGrotesk(
                    fontSize: 13,
                    color: Colors.white.withValues(alpha: 0.75),
                  ),
                ),
                const SizedBox(height: 44),
                SizedBox(
                  width: double.infinity,
                  child: ElevatedButton.icon(
                    onPressed: () => Navigator.pushNamed(
                      context, '/login_user',
                      arguments: {'role': 'user'},
                    ),
                    icon: const FaIcon(FontAwesomeIcons.user,
                        size: 14, color: AppColors.brandPurpleDeep),
                    label: Text(
                      'Sign in',
                      style: GoogleFonts.spaceGrotesk(
                        fontSize: 14,
                        fontWeight: FontWeight.w700,
                        color: AppColors.brandPurpleDeep,
                        letterSpacing: 0.4,
                      ),
                    ),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: AppColors.brandYellow,
                      foregroundColor: AppColors.brandPurpleDeep,
                      elevation: 0,
                      padding: const EdgeInsets.symmetric(vertical: 14),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(10),
                      ),
                    ),
                  ),
                ),
                const SizedBox(height: 12),
                SizedBox(
                  width: double.infinity,
                  child: OutlinedButton.icon(
                    onPressed: () => Navigator.pushNamed(
                      context, '/login_user',
                      arguments: {'role': 'admin'},
                    ),
                    icon: const FaIcon(FontAwesomeIcons.userShield,
                        size: 14, color: Colors.white),
                    label: Text(
                      'Sign in as admin',
                      style: GoogleFonts.spaceGrotesk(
                        fontSize: 14,
                        fontWeight: FontWeight.w600,
                        color: Colors.white,
                        letterSpacing: 0.3,
                      ),
                    ),
                    style: OutlinedButton.styleFrom(
                      side: BorderSide(
                          color: Colors.white.withValues(alpha: 0.4)),
                      padding: const EdgeInsets.symmetric(vertical: 14),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(10),
                      ),
                    ),
                  ),
                ),
                const Spacer(),
                Text(
                  '© 2026 NEO V-TRACK · UiTM Cawangan Johor',
                  style: GoogleFonts.jetBrainsMono(
                    color: Colors.white.withValues(alpha: 0.5),
                    fontSize: 10,
                    letterSpacing: 0.6,
                  ),
                ),
                const SizedBox(height: 12),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
