import 'package:flutter/material.dart';

/// NEO V-TRACK brand palette — purple identity, yellow signal, white density.
///
/// Tokens mirror the web's `colors_and_type.css`. Legacy member names
/// (`uitmRed`, `uitmBlue`) keep their callsite shape but now resolve to the
/// brand yellow + deep purple.
class AppColors {
  AppColors._();

  // ----- BRAND CORE -----
  static const Color brandPurple     = Color(0xFF5A2EA6); // interactive
  static const Color brandPurpleDeep = Color(0xFF2E1465); // header / dark surfaces
  static const Color brandPurpleSoft = Color(0xFF8B5CF6); // hover
  static const Color brandPurpleTint = Color(0xFFEEE7FB); // tinted surfaces

  static const Color brandYellow     = Color(0xFFFFD23F); // signal — plates, alerts
  static const Color brandYellowDeep = Color(0xFFE5A500);
  static const Color brandYellowTint = Color(0xFFFFF6D1);

  static const Color brandPaper      = Color(0xFFFAF9FC); // page background
  static const Color brandWhite      = Color(0xFFFFFFFF);

  // ----- ALIASES (back-compat) -----
  static const Color primary   = brandPurple;
  static const Color secondary = brandPurpleSoft;
  static const Color uitmRed   = brandYellow;
  static const Color uitmBlue  = brandPurpleDeep;

  // ----- SEMANTIC -----
  static const Color success = Color(0xFF1F9D55);
  static const Color warning = Color(0xFFE5A500);
  static const Color danger  = Color(0xFFC42B2B);

  // ----- NEUTRALS -----
  static const Color dark        = Color(0xFF14121E); // neutral-900
  static const Color lightBg     = brandPaper;
  static const Color cardBorder  = Color(0xFFE4E1EE); // neutral-200
  static const Color bodyText    = Color(0xFF14121E);
  static const Color mutedText   = Color(0xFF6E6788); // neutral-500

  // ----- STAT CARDS (kept for callsites; recoloured into brand family) -----
  static const Color statStaffBorder      = brandPurple;
  static const Color statStudentBorder    = brandPurpleSoft;
  static const Color statVisitorBorder    = brandYellow;
  static const Color statContractorBorder = Color(0xFF1F9D55);

  static const List<Color> staffGradient      = [brandPurple, brandPurpleDeep];
  static const List<Color> studentGradient    = [brandPurpleSoft, brandPurple];
  static const List<Color> visitorGradient    = [brandYellow, brandYellowDeep];
  static const List<Color> contractorGradient = [Color(0xFF1F9D55), Color(0xFF0F7B4A)];
  static const List<Color> totalGradient      = [brandPurple, brandPurpleDeep];

  /// Primary CTA — flat purple (brand voice: "civic, brief"); no gradient.
  static const List<Color> primaryButtonGradient = [brandPurple, brandPurple];

  /// Hero / splash gradient — deep purple wash.
  static const List<Color> heroGradient = [brandPurpleDeep, Color(0xFF1C0E3F)];

  // ----- STICKER STATUS -----
  static const Color stickerAdaBg     = Color(0xFFE3F6EB);
  static const Color stickerAdaText   = Color(0xFF1F9D55);
  static const Color stickerTiadaBg   = Color(0xFFFBE7E7);
  static const Color stickerTiadaText = Color(0xFFC42B2B);
}
