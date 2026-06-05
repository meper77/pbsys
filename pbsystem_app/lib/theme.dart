import 'package:flutter/material.dart';

/// NEO V-TRACK brand palette (navy + yellow).
class NV {
  static const Color navy = Color(0xFF2D1B69);
  static const Color navyDeep = Color(0xFF1A1040);
  static const Color purple = Color(0xFF667EEA);
  static const Color yellow = Color(0xFFFFC83D);
  static const Color ink = Color(0xFF1C1B2E);
  static const Color muted = Color(0xFF6B6B80);
  static const Color surface = Color(0xFFF6F7FB);
  static const Color ok = Color(0xFF1F9D55);
  static const Color warn = Color(0xFFE08A00);
  static const Color danger = Color(0xFFD64545);

  static ThemeData theme() {
    final base = ThemeData(useMaterial3: true, brightness: Brightness.light);
    return base.copyWith(
      scaffoldBackgroundColor: surface,
      colorScheme: ColorScheme.fromSeed(
        seedColor: navy,
        primary: navy,
        secondary: yellow,
        brightness: Brightness.light,
      ),
      appBarTheme: const AppBarTheme(
        backgroundColor: navy,
        foregroundColor: Colors.white,
        elevation: 0,
        centerTitle: false,
      ),
      cardTheme: CardThemeData(
        elevation: 0,
        color: Colors.white,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(14),
          side: const BorderSide(color: Color(0x14000000)),
        ),
        margin: EdgeInsets.zero,
      ),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: Colors.white,
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: const BorderSide(color: Color(0x22000000)),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: const BorderSide(color: Color(0x22000000)),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: const BorderSide(color: navy, width: 1.5),
        ),
      ),
      filledButtonTheme: FilledButtonThemeData(
        style: FilledButton.styleFrom(
          backgroundColor: navy,
          foregroundColor: Colors.white,
          padding: const EdgeInsets.symmetric(vertical: 16, horizontal: 20),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
          textStyle: const TextStyle(fontWeight: FontWeight.w700, fontSize: 15),
        ),
      ),
    );
  }

  /// Tone color for a vehicle category (owner.status).
  static Color categoryColor(String status) {
    switch (status.toLowerCase()) {
      case 'staf':
      case 'staff':
        return purple;
      case 'pelajar':
      case 'student':
        return navy;
      case 'pelawat':
      case 'visitor':
        return warn;
      case 'kontraktor':
      case 'contractor':
        return ok;
      default:
        return muted;
    }
  }
}
