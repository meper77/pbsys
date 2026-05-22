import 'package:flutter/material.dart';

import '../theme/app_colors.dart';

/// Coloured pill matching `.sticker-badge-ada` / `.sticker-badge-tiada` on
/// the web vehicle list pages.
class StickerBadge extends StatelessWidget {
  final String? status;     // 'ADA' or 'TIADA' (case-insensitive)
  final String? stickerNo;  // shown when status == ADA
  const StickerBadge({super.key, required this.status, this.stickerNo});

  @override
  Widget build(BuildContext context) {
    final s = (status ?? '').toUpperCase();
    if (s == 'ADA' && (stickerNo ?? '').isNotEmpty) {
      return _pill(stickerNo!, AppColors.stickerAdaBg, AppColors.stickerAdaText);
    }
    if (s == 'TIADA') {
      return _pill('TIADA', AppColors.stickerTiadaBg, AppColors.stickerTiadaText);
    }
    return const Text('-', style: TextStyle(color: AppColors.mutedText));
  }

  Widget _pill(String text, Color bg, Color fg) => Container(
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
        decoration: BoxDecoration(
          color: bg,
          borderRadius: BorderRadius.circular(20),
        ),
        child: Text(
          text,
          style: TextStyle(color: fg, fontWeight: FontWeight.w600, fontSize: 11, letterSpacing: 0.4),
        ),
      );
}
