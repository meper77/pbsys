import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

import '../theme/app_colors.dart';

/// Centered brand lockup — both logos side-by-side, then the NEO V-TRACK
/// wordmark over the UiTM SEGAMAT tagline. Reused on splash, welcome, login,
/// register, forgot-password.
class NvBrandHeader extends StatelessWidget {
  const NvBrandHeader({
    super.key,
    this.logoHeight = 56,
    this.wordSize = 22,
    this.subtitle = 'UiTM SEGAMAT',
    this.compact = false,
  });

  final double logoHeight;
  final double wordSize;
  final String subtitle;
  final bool compact;

  @override
  Widget build(BuildContext context) {
    return Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Image.asset(
              'assets/images/uitm.png',
              height: logoHeight,
              errorBuilder: (_, _, _) => const SizedBox.shrink(),
            ),
            Container(
              width: 1.5,
              height: logoHeight * 0.78,
              color: Colors.white.withValues(alpha: 0.22),
              margin: EdgeInsets.symmetric(horizontal: compact ? 12 : 16),
            ),
            Container(
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(10),
                boxShadow: [
                  BoxShadow(
                    color: Colors.black.withValues(alpha: 0.18),
                    blurRadius: 16,
                    offset: const Offset(0, 8),
                  ),
                ],
              ),
              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
              child: Image.asset(
                'assets/images/kik2.png',
                height: logoHeight - 8,
                errorBuilder: (_, _, _) => const SizedBox.shrink(),
              ),
            ),
          ],
        ),
        SizedBox(height: compact ? 16 : 24),
        RichText(
          textAlign: TextAlign.center,
          text: TextSpan(
            style: GoogleFonts.manrope(
              fontSize: wordSize,
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
        const SizedBox(height: 4),
        Text(
          subtitle,
          style: GoogleFonts.spaceGrotesk(
            fontSize: 11,
            fontWeight: FontWeight.w600,
            color: Colors.white.withValues(alpha: 0.65),
            letterSpacing: 3.6,
          ),
        ),
      ],
    );
  }
}
