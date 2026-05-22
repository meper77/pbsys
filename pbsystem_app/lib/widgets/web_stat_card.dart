import 'package:flutter/material.dart';
import 'package:font_awesome_flutter/font_awesome_flutter.dart';

import '../theme/app_colors.dart';
import '../theme/app_typography.dart';

/// Mirrors the web `.stat-card .stat-*` block from `index.php` lines 502–537:
/// white card, 4 px coloured top border, soft shadow, gradient-filled round
/// icon disc top-left, big number, label, footer line.
class WebStatCard extends StatelessWidget {
  final String label;
  final String count;
  final IconData faIcon;
  final List<Color> iconGradient;
  final Color topBorderColor;
  final String? footer;
  final IconData? footerIcon;
  final VoidCallback? onTap;

  const WebStatCard({
    super.key,
    required this.label,
    required this.count,
    required this.faIcon,
    required this.iconGradient,
    required this.topBorderColor,
    this.footer,
    this.footerIcon,
    this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    final card = Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(10),
        border: const Border.fromBorderSide(BorderSide(color: AppColors.cardBorder)),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.06),
            blurRadius: 10,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          // top accent stripe
          Container(
            height: 4,
            decoration: BoxDecoration(
              color: topBorderColor,
              borderRadius: const BorderRadius.vertical(top: Radius.circular(10)),
            ),
          ),
          Padding(
            padding: const EdgeInsets.fromLTRB(14, 14, 14, 12),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              mainAxisSize: MainAxisSize.min,
              children: [
                Container(
                  width: 44,
                  height: 44,
                  decoration: BoxDecoration(
                    gradient: LinearGradient(
                      begin: Alignment.topLeft,
                      end: Alignment.bottomRight,
                      colors: iconGradient,
                    ),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Center(
                    child: FaIcon(faIcon, color: Colors.white, size: 20),
                  ),
                ),
                const SizedBox(height: 10),
                Text(
                  count,
                  style: AppTypography.statCount(size: 28),
                ),
                const SizedBox(height: 2),
                Text(
                  label,
                  style: const TextStyle(
                    color: AppColors.mutedText,
                    fontWeight: FontWeight.w600,
                    fontSize: 13,
                  ),
                ),
                if (footer != null) ...[
                  const SizedBox(height: 8),
                  Row(
                    children: [
                      if (footerIcon != null) ...[
                        FaIcon(footerIcon, size: 11, color: AppColors.success),
                        const SizedBox(width: 4),
                      ],
                      Flexible(
                        child: Text(
                          footer!,
                          style: const TextStyle(
                            color: AppColors.success,
                            fontSize: 11,
                            fontWeight: FontWeight.w600,
                          ),
                          overflow: TextOverflow.ellipsis,
                        ),
                      ),
                    ],
                  ),
                ],
              ],
            ),
          ),
        ],
      ),
    );

    if (onTap == null) return card;
    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(10),
        child: card,
      ),
    );
  }
}
