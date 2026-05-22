import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

import '../theme/app_colors.dart';

/// Branded app bar — deep-purple bg, yellow underline, wordmark + optional subtitle.
class NvAppBar extends StatelessWidget implements PreferredSizeWidget {
  const NvAppBar({
    super.key,
    this.title,
    this.subtitle,
    this.actions,
    this.showBack = true,
    this.showLogo = true,
  });

  final String? title;
  final String? subtitle;
  final List<Widget>? actions;
  final bool showBack;
  final bool showLogo;

  @override
  Size get preferredSize => const Size.fromHeight(64);

  @override
  Widget build(BuildContext context) {
    final canPop = Navigator.of(context).canPop();
    return AppBar(
      backgroundColor: AppColors.brandPurpleDeep,
      foregroundColor: Colors.white,
      elevation: 0,
      automaticallyImplyLeading: showBack && canPop,
      iconTheme: const IconThemeData(color: Colors.white),
      shape: const Border(
        bottom: BorderSide(color: AppColors.brandYellow, width: 2),
      ),
      centerTitle: true,
      titleSpacing: 4,
      title: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          if (showLogo && title == null)
            RichText(
              textAlign: TextAlign.center,
              text: TextSpan(
                style: GoogleFonts.manrope(
                  fontSize: 16,
                  fontWeight: FontWeight.w800,
                  color: Colors.white,
                  letterSpacing: 0.3,
                ),
                children: const [
                  TextSpan(text: 'NEO '),
                  TextSpan(
                    text: 'V-TRACK',
                    style: TextStyle(color: AppColors.brandYellow),
                  ),
                ],
              ),
            )
          else
            Text(
              title ?? 'NEO V-TRACK',
              style: GoogleFonts.manrope(
                fontSize: 16,
                fontWeight: FontWeight.w800,
                color: Colors.white,
                letterSpacing: 0.2,
              ),
            ),
          if (subtitle != null)
            Padding(
              padding: const EdgeInsets.only(top: 1),
              child: Text(
                subtitle!.toUpperCase(),
                style: GoogleFonts.spaceGrotesk(
                  fontSize: 10,
                  fontWeight: FontWeight.w600,
                  color: Colors.white.withValues(alpha: 0.6),
                  letterSpacing: 2.0,
                ),
              ),
            ),
        ],
      ),
      actions: actions,
    );
  }
}
