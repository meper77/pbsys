import 'package:flutter/material.dart';

import '../theme/app_colors.dart';
import '../theme/app_typography.dart';

/// Malaysian rear-plate motif: yellow chip, black border, JetBrains Mono.
/// Sizes: `small` (table rows), `medium` (default), `large` (headlines).
class NvPlateChip extends StatelessWidget {
  const NvPlateChip(this.plate, {super.key, this.size = NvPlateSize.medium});

  final String plate;
  final NvPlateSize size;

  @override
  Widget build(BuildContext context) {
    final (fontSize, hPad, vPad, border, radius) = switch (size) {
      NvPlateSize.small  => (12.0, 8.0, 2.0, 1.25, 4.0),
      NvPlateSize.medium => (16.0, 11.0, 4.0, 1.75, 5.0),
      NvPlateSize.large  => (24.0, 16.0, 8.0, 2.5, 7.0),
    };
    return Container(
      padding: EdgeInsets.symmetric(horizontal: hPad, vertical: vPad),
      decoration: BoxDecoration(
        color: AppColors.brandYellow,
        border: Border.all(color: AppColors.dark, width: border),
        borderRadius: BorderRadius.circular(radius),
      ),
      child: Text(
        plate.toUpperCase(),
        style: AppTypography.plateMono(
          size: fontSize,
          color: AppColors.dark,
        ).copyWith(letterSpacing: 1.2),
      ),
    );
  }
}

enum NvPlateSize { small, medium, large }
