import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

import 'app_colors.dart';

/// NEO V-TRACK type system:
///   - Manrope        → display / headings
///   - Space Grotesk  → UI body, labels, buttons
///   - JetBrains Mono → plates, IDs, time, code
class AppTypography {
  AppTypography._();

  /// Body text theme (Space Grotesk) layered on top of [base].
  static TextTheme textTheme(TextTheme base) {
    final body = GoogleFonts.spaceGroteskTextTheme(base);
    final displayFont = GoogleFonts.manrope;

    return body.copyWith(
      displayLarge:   displayFont(textStyle: base.displayLarge,   fontWeight: FontWeight.w800, color: AppColors.dark, letterSpacing: -0.5),
      displayMedium:  displayFont(textStyle: base.displayMedium,  fontWeight: FontWeight.w800, color: AppColors.dark, letterSpacing: -0.4),
      displaySmall:   displayFont(textStyle: base.displaySmall,   fontWeight: FontWeight.w700, color: AppColors.dark, letterSpacing: -0.3),
      headlineLarge:  displayFont(textStyle: base.headlineLarge,  fontWeight: FontWeight.w800, color: AppColors.dark),
      headlineMedium: displayFont(textStyle: base.headlineMedium, fontWeight: FontWeight.w700, color: AppColors.dark),
      headlineSmall:  displayFont(textStyle: base.headlineSmall,  fontWeight: FontWeight.w700, color: AppColors.dark),
      titleLarge:     displayFont(textStyle: base.titleLarge,     fontWeight: FontWeight.w700, color: AppColors.dark),
      titleMedium:    body.titleMedium?.copyWith(fontWeight: FontWeight.w600, color: AppColors.dark),
      titleSmall:     body.titleSmall?.copyWith(fontWeight: FontWeight.w600, color: AppColors.dark),
      bodyLarge:      body.bodyLarge?.copyWith(color: AppColors.bodyText),
      bodyMedium:     body.bodyMedium?.copyWith(color: AppColors.bodyText),
      bodySmall:      body.bodySmall?.copyWith(color: AppColors.mutedText),
      labelLarge:     body.labelLarge?.copyWith(fontWeight: FontWeight.w600),
      labelMedium:    body.labelMedium?.copyWith(fontWeight: FontWeight.w600),
    );
  }

  /// Plate / matric / time mono — JetBrains Mono.
  static TextStyle plateMono({double size = 18, Color? color}) =>
      GoogleFonts.jetBrainsMono(
        fontSize: size,
        fontWeight: FontWeight.w700,
        letterSpacing: 1.2,
        color: color ?? AppColors.bodyText,
      );

  /// Big numeric counters — Manrope.
  static TextStyle statCount({double size = 30, Color? color}) =>
      GoogleFonts.manrope(
        fontSize: size,
        fontWeight: FontWeight.w800,
        color: color ?? AppColors.dark,
        height: 1,
        letterSpacing: -0.5,
      );

  /// Wordmark — Manrope 800, used in splash and headers.
  static TextStyle wordmark({double size = 22, Color? color}) =>
      GoogleFonts.manrope(
        fontSize: size,
        fontWeight: FontWeight.w800,
        letterSpacing: 0.5,
        color: color ?? Colors.white,
      );

  /// Eyebrow — uppercase, tracked.
  static TextStyle eyebrow({Color? color}) => GoogleFonts.spaceGrotesk(
        fontSize: 11,
        fontWeight: FontWeight.w700,
        letterSpacing: 1.8,
        color: color ?? AppColors.brandPurple,
      );
}
