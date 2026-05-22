import 'package:flutter/material.dart';
import 'package:font_awesome_flutter/font_awesome_flutter.dart';

import '../theme/app_colors.dart';

/// Section header used inside white content cards. Mirrors `.content-title`
/// on the web — bold text with a thin bottom rule.
class WebSectionTitle extends StatelessWidget {
  final String title;
  final IconData? icon;
  final Color? accent;

  const WebSectionTitle({
    super.key,
    required this.title,
    this.icon,
    this.accent,
  });

  @override
  Widget build(BuildContext context) {
    final c = accent ?? AppColors.primary;
    return Container(
      padding: const EdgeInsets.only(bottom: 12),
      margin: const EdgeInsets.only(bottom: 12),
      decoration: const BoxDecoration(
        border: Border(bottom: BorderSide(color: AppColors.cardBorder)),
      ),
      child: Row(
        children: [
          if (icon != null) ...[
            FaIcon(icon, color: c, size: 16),
            const SizedBox(width: 8),
          ],
          Expanded(
            child: Text(
              title,
              style: TextStyle(
                color: AppColors.dark,
                fontWeight: FontWeight.w700,
                fontSize: 17,
              ),
            ),
          ),
        ],
      ),
    );
  }
}
